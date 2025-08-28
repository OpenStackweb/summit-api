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

        $all = config('database.redis', []);
        if (!$all){
            Log::warning("RedisClientNamer::ensure missing database.redis config");
            return;
        }

        // all defined connections
        // Build the list of REAL connection names:
        // keep only entries that are arrays and look like a server (host/url)
        $conns = array_values(array_filter(
            $onlyConnections ?? array_keys($all),
            function ($name) use ($all) {
                if (in_array($name, ['options','client','cluster'], true)) return false;
                $v = $all[$name] ?? null;
                return is_array($v) && (isset($v['host']) || isset($v['url']));
            }
        ));


        Log::debug(sprintf("RedisClientNamer::ensure got %s connections", count($conns)));
        foreach ($conns as $conn) {
            try {

                $cx = Redis::connection($conn);
                // Base name from config or fallback
                $cfg  = $all[$conn] ?? [];
                Log::debug(sprintf("RedisClientNamer::ensure processing connection %s name %s", $conn, $cfg['name']));
                $base = $cfg['name'] ?? ($all['default']['name'] ?? config('app.name', 'app'));

                $full = sprintf('%s:%s:%s:%d:%s', $base, $runtimeTag, $host, $pid, $conn);

                // Use the portable way: send the CLIENT command explicitly
                $before = null;
                try { $before = $cx->command('client', ['getname']); } catch (\Throwable $e) {}

                $cx->command('client', ['setname', $full]);
                $after = $cx->command('client', ['getname']);

                Log::debug('RedisClientNamer::ensure setname ok', [
                    'conn' => $conn, 'before' => $before, 'after' => $after
                ]);

            } catch (\Throwable $e) {
                Log::error('RedisClientNamer::ensure setname FAILED', [
                    'conn' => $conn, 'err' => $e->getMessage()
                ]);
            }
        }
    }
}
