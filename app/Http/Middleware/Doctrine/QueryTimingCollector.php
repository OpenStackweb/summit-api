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

    /**
     * SQL text of every statement executed since reset(), or null while not capturing. Off by
     * default: it exists so a test can tell which statements a request repeats, not just how many.
     * @var string[]|null
     */
    public static ?array $statements = null;

    public static function record(float $startedAt, ?string $sql = null): void
    {
        self::$totalMs += (microtime(true) - $startedAt) * 1000.0;
        self::$count++;
        if (self::$statements !== null && $sql !== null) self::$statements[] = $sql;
    }

    public static function reset(): void
    {
        self::$totalMs = 0.0;
        self::$count = 0;
        if (self::$statements !== null) self::$statements = [];
    }
}
