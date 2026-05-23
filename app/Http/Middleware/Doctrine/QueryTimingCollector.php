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

    /** @var array<string, array{count:int, totalMs:float, sample:string}> */
    public static array $patterns = [];

    public static function record(float $startedAt, ?string $sql = null): void
    {
        $ms = (microtime(true) - $startedAt) * 1000.0;
        self::$totalMs += $ms;
        self::$count++;

        if ($sql !== null) {
            $pattern = self::normalize($sql);
            if (!isset(self::$patterns[$pattern])) {
                self::$patterns[$pattern] = ['count' => 0, 'totalMs' => 0.0, 'sample' => $sql];
            }
            self::$patterns[$pattern]['count']++;
            self::$patterns[$pattern]['totalMs'] += $ms;
        }
    }

    public static function reset(): void
    {
        self::$totalMs = 0.0;
        self::$count = 0;
        self::$patterns = [];
    }

    /**
     * Returns the top N most-repeated SQL patterns, sorted by count descending.
     *
     * @return array<int, array{pattern:string, count:int, totalMs:float, sample:string}>
     */
    public static function topPatterns(int $limit = 10): array
    {
        $rows = [];
        foreach (self::$patterns as $pattern => $stats) {
            if ($stats['count'] < 2) continue; // only interested in repeats
            $rows[] = [
                'pattern' => $pattern,
                'count'   => $stats['count'],
                'totalMs' => round($stats['totalMs'], 1),
                'sample'  => $stats['sample'],
            ];
        }
        usort($rows, fn($a, $b) => $b['count'] <=> $a['count']);
        return array_slice($rows, 0, $limit);
    }

    /**
     * Replace numeric and quoted literals with ? so that "SELECT x WHERE id = 7570"
     * and "SELECT x WHERE id = 7571" map to the same pattern, surfacing N+1s.
     */
    private static function normalize(string $sql): string
    {
        // Collapse positional and named params
        $s = preg_replace('/\?|:[a-zA-Z_][a-zA-Z0-9_]*/', '?', $sql);
        // Collapse quoted strings
        $s = preg_replace("/'[^']*'/", '?', $s);
        // Collapse numbers
        $s = preg_replace('/\b\d+\b/', '?', $s);
        // Collapse runs of whitespace
        $s = preg_replace('/\s+/', ' ', $s);
        return trim($s);
    }
}
