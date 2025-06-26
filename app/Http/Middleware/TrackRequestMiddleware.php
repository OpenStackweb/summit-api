<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Closure;
use Illuminate\Support\Str;
use \OpenTelemetry\API\Trace\SpanInterface;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Log\LogManager;
use Keepsuit\LaravelOpenTelemetry\Facades\Tracer;


class TrackRequestMiddleware
{
    /**
     * @var LogManager
     */
    protected LogManager $logger;

    /**
     * @var float
     */
    protected float $startTime = 0;

    /**
     * @var SpanInterface
     */
    protected SpanInterface $span;

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
        if(env('APP_ENV') === 'testing') {
            // Skip tracking in testing environment
            return $next($request);
        }
        try {
            // generating dynamic id for span with configurable prefix
            $spanId = env('TRACE_SPAN_PREFIX', 'SPAN') . '_' . Str::uuid();
            $this->startTime = microtime(true);
            $this->span = Tracer::newSpan($spanId)->start();

            $this->logger->channel('otlp')->info('Request started.', [
                'endpoint' => $request->url(),
                'method' => $request->method(),
                'timestamp_utc' => now()->toIso8601String(),
            ]);
        } catch (\Throwable $e) {
            // forcing 'single' channel in case otlp log fails
            $this->logger->channel('single')->error("Error on request tracking" . $e->getMessage());
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
        if(env('APP_ENV') === 'testing') {
            // Skip tracking in testing environment
            return;
        }
        try {
            $endTime = microtime(true);
            $responseTime = intval(($endTime - $this->startTime) * 1000);
            $this->logger->channel('otlp')->info('Request finished.', [
                'response_time' => $responseTime,
            ]);

            if (isset($this->span)) {
                $this->span->end();
            }

        } catch (\Throwable $e) {
            // forcing 'single' channel in case otlp log fails
            $this->logger->channel('single')->error("Error on request tracking: " . $e->getMessage());
        }
    }
}