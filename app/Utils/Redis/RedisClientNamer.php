<?php namespace App\Utils\Redis;
/*
 * Copyright 2025 OpenStack Foundation
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 **/

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
        Log::debug(sprintf("RedisClientNamer::ensure runtimeTag %s", $runtimeTag));
        static $done = [];
        $host = gethostname() ?: php_uname('n');
        $pid  = function_exists('getmypid') ? getmypid() : random_int(1000, 9999);
        $tag  = $runtimeTag ?? (app()->runningInConsole() ? 'cli' : 'http');
        $key  = $tag . ':' . $pid;
        Log::debug(sprintf("RedisClientNamer::ensure host %s pid %s key %s", $host, $pid, $key));
        if (isset($done[$key])) return;
        $done[$key] = true;

        $redisConfig = config('database.redis', []);
        if (!$redisConfig){
            Log::warning("RedisClientNamer::ensure missing database.redis config");
            return;
        }

        // all defined connections
        $conns = $connections ?? array_values(array_filter(array_keys($redisConfig), fn($k) => $k !== 'options'));
        Log::debug(sprintf("RedisClientNamer::ensure got %s connections", count($conns)));
        foreach ($conns as $conn) {
            try {
                $cfg  = $redisConfig[$conn] ?? [];
                Log::debug(sprintf("RedisClientNamer::ensure processing connection %s name %s", $conn, $cfg['name']));
                $base = $cfg['name']
                    ?? ($redisConfig['default']['name'] ?? config('app.name', 'laravel'));

                $full = sprintf('%s:%s:%s:%d:%s', $base, $tag, $host, $pid, $conn);

                $client = Redis::connection($conn);
                $curr   = null;
                try { $curr = $client->client('getname'); } catch (\Throwable $e) {}

                if ($curr !== $full) {
                    Log::debug(sprintf("RedisClientNamer::ensure connection %s  set name %s", $conn, $full));
                    $client->client('setname', $full);
                }
            } catch (\Throwable $e) {
              Log::debug("RedisClientNamer::ensure Redis setname failed [$conn]: ".$e->getMessage());
            }
        }
    }
}
