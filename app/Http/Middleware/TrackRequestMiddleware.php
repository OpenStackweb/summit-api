<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Keepsuit\LaravelOpenTelemetry\Facades\Tracer;
use Illuminate\Log\LogManager;
use OpenTelemetry\API\Baggage\Baggage;
use OpenTelemetry\Context\ScopeInterface;

class TrackRequestMiddleware
{
    private const ATTRIBUTE_START_TIME = '_start_time';
    private const EVENT_REQUEST_STARTED = 'request.started';
    private const EVENT_REQUEST_FINISHED = 'request.finished';

    protected LogManager $logger;
    private ?ScopeInterface $baggageScope = null;
    private bool $shouldTrack;

    public function __construct(LogManager $logger)
    {
        $this->logger = $logger;
        $this->shouldTrack = env('APP_ENV') !== 'testing' &&
            config('opentelemetry.enhance_requests', true);
    }

    public function handle(Request $request, Closure $next)
    {
        if (!$this->shouldTrack) {
            return $next($request);
        }

        try {
            $request->attributes->set(self::ATTRIBUTE_START_TIME, microtime(true));

            if ($span = Tracer::activeSpan()) {
                if ($ray = $request->header('cf-ray')) {
                    $span->setAttribute('cloudflare.ray_id', $ray);

                    $baggage = Baggage::getCurrent()
                        ->toBuilder()
                        ->set('cf-ray', $ray)
                        ->set('request_id', $request->header('x-request-id', uniqid('req_')))
                        ->set('user_agent', substr($request->userAgent() ?? 'unknown', 0, 100))
                        ->build();

                    $this->baggageScope = $baggage->activate();
                }

                $span->addEvent(self::EVENT_REQUEST_STARTED, [
                    'method' => $request->method(),
                    'url'    => $request->fullUrl(),
                ]);
            }
        } catch (\Throwable $e) {
            $this->logger->channel('single')->error("Error on request tracking: " . $e->getMessage());
        }

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        if (!$this->shouldTrack) {
            return;
        }

        try {
            $start = (float) $request->attributes->get(self::ATTRIBUTE_START_TIME, microtime(true));
            $ms = (int) ((microtime(true) - $start) * 1000);

            if ($span = Tracer::activeSpan()) {
                $span->setAttribute('app.response_ms', $ms);
                $span->setAttribute('http.status_code', $response->getStatusCode());
                $span->addEvent(self::EVENT_REQUEST_FINISHED, ['response_ms' => $ms]);
            }
        } catch (\Throwable $e) {
            $this->logger->channel('single')->error("Error on request tracking: " . $e->getMessage());
        } finally {
            if ($this->baggageScope) {
                $this->baggageScope->detach();
                $this->baggageScope = null;
            }
        }
    }
}
