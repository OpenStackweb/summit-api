<?php

namespace App\Http\Middleware;

use Closure;
use Doctrine\ORM\EntityManagerInterface;
use LaravelDoctrine\ORM\Facades\Registry;
use models\utils\SilverstripeBaseModel;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

class ServerTimingDoctrine
{
    /** @var EntityManagerInterface */
    private EntityManagerInterface $em;


    public function __construct()
    {
        $this->em =  Registry::getManager(SilverstripeBaseModel::EntityManager);
    }

    public function handle($request, Closure $next): Response
    {
        $start = microtime(true);
        $conn = $this->em->getConnection();
        $cfg  = $conn->getConfiguration();

        $dbMs = 0.0;

        // --- DBAL 2.x: DebugStack ---
        if (class_exists(\Doctrine\DBAL\Logging\DebugStack::class) && method_exists($cfg, 'setSQLLogger')) {
            $debugStack = new \Doctrine\DBAL\Logging\DebugStack();
            $prevLogger = $cfg->getSQLLogger() ?? null;
            $cfg->setSQLLogger($debugStack);

            try {
                /** @var Response $response */
                $response = $next($request);
            } finally {
                foreach ($debugStack->queries as $q) {
                    $dbMs += isset($q['executionMS']) ? (float) $q['executionMS'] : 0.0;
                }
                $cfg->setSQLLogger($prevLogger);
            }

            // --- DBAL 3.x: Logging\Middleware (PSR-3) ---
        } elseif (class_exists(\Doctrine\DBAL\Logging\Middleware::class) && method_exists($cfg, 'setMiddlewares')) {


            $collector = new class implements LoggerInterface {
                public float $dbMs = 0.0;
                public function log($level, $message, array $context = []): void {
                    if (isset($context['duration_ms'])) {
                        $this->dbMs += (float) $context['duration_ms'];
                    } elseif (isset($context['executionMS'])) {
                        $this->dbMs += (float) $context['executionMS'];
                    } elseif (isset($context['duration'])) {
                        $this->dbMs += (float) $context['duration'];
                    }
                }
                public function emergency($m, array $c = []):void { $this->log('emergency', $m, $c); }
                public function alert($m, array $c = []):void     { $this->log('alert', $m, $c); }
                public function critical($m, array $c = []):void  { $this->log('critical', $m, $c); }
                public function error($m, array $c = []):void     { $this->log('error', $m, $c); }
                public function warning($m, array $c = []):void   { $this->log('warning', $m, $c); }
                public function notice($m, array $c = []) :void   { $this->log('notice', $m, $c); }
                public function info($m, array $c = []):void      { $this->log('info', $m, $c); }
                public function debug($m, array $c = []):void   { $this->log('debug', $m, $c); }
            };

            $mw   = new \Doctrine\DBAL\Logging\Middleware($collector);
            $prev = method_exists($cfg, 'getMiddlewares') ? $cfg->getMiddlewares() : [];
            $cfg->setMiddlewares(array_merge($prev, [$mw]));

            try {
                /** @var Response $response */
                $response = $next($request);
            } finally {
                $dbMs = $collector->dbMs;
                $cfg->setMiddlewares($prev);
            }

            // --- Fallback
        } else {
            /** @var Response $response */
            $response = $next($request);
        }


        $totalMs = (microtime(true) - $start) * 1000.0;
        $bootMs  = defined('LARAVEL_START') ? max(($start - LARAVEL_START) * 1000.0, 0.0) : 0.0;
        $appMs   = max($totalMs - $dbMs, 0.0);

        // Al setear el header:
        $response->headers->set(
            'Server-Timing',
            sprintf('boot;dur=%.1f, db;dur=%.1f, app;dur=%.1f, total;dur=%.1f', $bootMs, $dbMs, $appMs, $totalMs)
        );
        $response->headers->set('Timing-Allow-Origin', '*');

        return $response;
    }
}
