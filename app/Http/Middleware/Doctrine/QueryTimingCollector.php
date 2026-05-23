<?php

namespace App\Http\Middleware\Doctrine;

/**
 * Request-scoped accumulator for SQL execution times.
 *
 * Static so that the DBAL Driver Middleware (instantiated by Doctrine on
 * connection creation) and the request lifecycle middleware (ServerTimingDoctrine)
 * can share state without needing dependency injection through Doctrine's
 * internals. Reset at request start by ServerTimingDoctrine.
 */
class QueryTimingCollector
{
    public static float $totalMs = 0.0;
    public static int $count = 0;

    public static function record(float $startedAt): void
    {
        self::$totalMs += (microtime(true) - $startedAt) * 1000.0;
        self::$count++;
    }

    public static function reset(): void
    {
        self::$totalMs = 0.0;
        self::$count = 0;
    }
}
