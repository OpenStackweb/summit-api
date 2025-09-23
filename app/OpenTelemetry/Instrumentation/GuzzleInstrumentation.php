<?php

namespace App\OpenTelemetry\Instrumentation;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Illuminate\Support\Facades\Log;
use Keepsuit\LaravelOpenTelemetry\Instrumentation\Instrumentation;
use Keepsuit\LaravelOpenTelemetry\Support\HttpClient\GuzzleTraceMiddleware;

class GuzzleInstrumentation implements Instrumentation
{
    private bool $logsEnabled;
    private static bool $macroRegistered = false;

    public function register(array $options): void
    {
        $this->logsEnabled = $options['enableLogs'] ?? false;

        if (!($options['enabled'] ?? true)) {
            return;
        }

        $this->log('info', 'GuzzleInstrumentation::register called', [
            'enabled' => $options['enabled'] ?? 'not set',
            'manual' => $options['manual'] ?? 'not set',
            'enableLogs' => $this->logsEnabled,
            'options' => $options
        ]);

        $manual = $options['manual'] ?? false;

        if ($manual) {
            $this->registerManualMode();
        } else {
            $this->registerAutoMode();
        }
    }

    protected function registerManualMode(): void
    {
        $logsEnabled = $this->logsEnabled;

        if (!self::$macroRegistered) {
            // @phpstan-ignore-next-line
            Client::macro('withTrace', function () use ($logsEnabled) {
                $config = $this->getConfig();

                $handler = $config['handler'] ?? HandlerStack::create();
                if (!($handler instanceof HandlerStack)) {
                    $handler = HandlerStack::create($handler);
                }

                if (!$this->hasTracingMiddleware($handler)) {
                    $handler->push(GuzzleTraceMiddleware::make());
                    if ($logsEnabled) {
                        Log::debug('[GuzzleInstrumentation] withTrace(): Added tracing middleware - baggage will be injected');
                    }
                } else {
                    if ($logsEnabled) {
                        Log::debug('[GuzzleInstrumentation] withTrace(): Tracing middleware already present, skipping');
                    }
                }

                $config['handler'] = $handler;

                return new Client($config);
            });

            self::$macroRegistered = true;
        }

        $this->log('debug', 'GuzzleInstrumentation: MANUAL mode registered - use $client->withTrace()');
    }

    protected function registerAutoMode(): void
    {
        $logsEnabled = $this->logsEnabled;

        app()->bind(Client::class, function ($app, $parameters) use ($logsEnabled) {
            if ($logsEnabled) {
                Log::debug('[GuzzleInstrumentation] Creating Guzzle Client with AUTO tracing');
            }

            $config = $parameters['config'] ?? [];

            if (!isset($config['handler'])) {
                $stack = HandlerStack::create();
                $stack->push(GuzzleTraceMiddleware::make());
                $config['handler'] = $stack;

                if ($logsEnabled) {
                    Log::debug('[GuzzleInstrumentation] AUTO mode: Added tracing middleware to new Guzzle client - baggage will be injected');
                }
            } else {
                $handler = $config['handler'];
                if ($handler instanceof HandlerStack) {
                    if (!$this->hasTracingMiddleware($handler)) {
                        $handler->push(GuzzleTraceMiddleware::make());
                        if ($logsEnabled) {
                            Log::debug('[GuzzleInstrumentation] AUTO mode: Added tracing middleware to existing HandlerStack - baggage will be injected');
                        }
                    } else {
                        if ($logsEnabled) {
                            Log::debug('[GuzzleInstrumentation] AUTO mode: Tracing middleware already present in HandlerStack, skipping');
                        }
                    }
                }
            }

            return new Client($config);
        });

        if ($this->logsEnabled) {
            $this->log('debug', 'GuzzleInstrumentation: AUTO mode registered - all new Client() will have tracing');
        }
    }

    protected function hasTracingMiddleware(HandlerStack $stack): bool
    {
        try {
            $reflection = new \ReflectionClass($stack);
            $stackProperty = $reflection->getProperty('stack');
            $stackProperty->setAccessible(true);
            $middlewares = $stackProperty->getValue($stack);

            foreach ($middlewares as $middleware) {
                if (isset($middleware[0]) && $middleware[0] instanceof \Closure) {
                    continue;
                }

                if (strpos(get_class($middleware[0] ?? ''), 'Trace') !== false) {
                    return true;
                }
            }

            return false;
        } catch (\Exception $e) {
            $this->log('warning', 'Could not detect existing tracing middleware', ['error' => $e->getMessage()]);
            return false;
        }
    }

    private function log(string $level, string $message, array $context = []): void
    {
        if (!$this->logsEnabled) {
            return;
        }

        Log::$level('[GuzzleInstrumentation] ' . $message, $context);
    }
}
