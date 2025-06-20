<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Closure;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Log\LogManager;
use Keepsuit\LaravelOpenTelemetry\Facades\Tracer;


class TrackRequestMiddleware
{
    /**
     * @var LogManager|Logger
     */
    protected $logger;

    protected $startTime;

    protected $span;

    /**
     * Constructor del middleware.
     * Laravel inyectará el LogManager aquí.
     *
     * @param LogManager $logger
     */
    public function __construct(LogManager $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            $this->startTime = microtime(true);
            $this->span = Tracer::newSpan('sample trace');
            $this->span->start();

            $this->logger->channel('otlp')->info('Request started.', [
                'endpoint' => $request->url(),
                'method' => $request->method(),
                'timestamp_utc' => now()->toIso8601String(),
            ]);
        } catch (\Throwable $e) {
            $this->logger->error("Error on request tracking" . $e->getMessage());
        }

        $response = $next($request);
        return $response;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function terminate(Request $request, Response $response): void
    {
        try {
            $endTime = microtime(true);
            $responseTime = intval(($endTime - $this->startTime) * 1000);
            $this->logger->channel('otlp')->info('Request finished.', [
                'response_time' => $responseTime,
            ]);

            $this->span->end();

        } catch (\Throwable $e) {
            $this->logger->channel('sadsadasd')->error("Error on request tracking: " . $e->getMessage());
        }
    }
}