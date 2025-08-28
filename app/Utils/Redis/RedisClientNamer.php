<?php

namespace App\Utils\Redis;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class RedisClientNamer
{
    /**
     * set CLIENT SETNAME once per process (worker) and per runtime  “tag”.
     * $runtimeTag: 'octane' | 'horizon' | 'fpm' | 'cli'
     */
    public static function ensure(?string $runtimeTag = null, ?array $connections = null): void
    {
        static $done = [];
        $host = gethostname() ?: php_uname('n');
        $pid  = function_exists('getmypid') ? getmypid() : random_int(1000, 9999);
        $tag  = $runtimeTag ?? (app()->runningInConsole() ? 'cli' : 'http');
        $key  = $tag . ':' . $pid;

        if (isset($done[$key])) return;
        $done[$key] = true;

        $redisConfig = config('database.redis', []);
        if (!$redisConfig) return;

        // all defined connections
        $conns = $connections ?? array_values(array_filter(array_keys($redisConfig), fn($k) => $k !== 'options'));

        foreach ($conns as $conn) {
            try {
                $cfg  = $redisConfig[$conn] ?? [];
                $base = $cfg['name']
                    ?? ($redisConfig['default']['name'] ?? config('app.name', 'laravel'));

                $full = sprintf('%s:%s:%s:%d:%s', $base, $tag, $host, $pid, $conn);

                $client = Redis::connection($conn);
                $curr   = null;
                try { $curr = $client->client('getname'); } catch (\Throwable $e) {}

                if ($curr !== $full) {
                    $client->client('setname', $full);
                }
            } catch (\Throwable $e) {
              Log::debug("Redis setname failed [$conn]: ".$e->getMessage());
            }
        }
    }
}
