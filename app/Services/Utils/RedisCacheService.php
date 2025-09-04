<?php namespace services\utils;
/**
 * Copyright 2015 OpenStack Foundation
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
use libs\utils\ICacheService;
use Predis\Connection\ConnectionException as PredisConnectionException;
use \Illuminate\Support\Facades\Redis;
/**
 * Class RedisCacheService
 * Cache Service Implementation Based on REDIS
 * http://redis.io
 * @package services
 */
class RedisCacheService implements ICacheService
{

    const Connection = 'default';
    const MaxRetries = 3;
    //services
    private $redis = null;

    public function __construct()
    {
        try {
            // try to re init the connection bc background process
            Redis::purge(self::Connection);
            $this->redis = Redis::connection(self::Connection);
        } catch (PredisConnectionException|\RedisException $ex) {
            $metadata = null;
            try {
                if ($ex instanceof PredisConnectionException && method_exists($ex, 'getConnection')) {
                    $res = $ex->getConnection()->getResource();
                    if (is_resource($res)) $metadata = @stream_get_meta_data($res);
                }
            } catch (\Throwable $ignored) {}
            Log::error(sprintf("RedisCacheService::__construct %s %s %s", $ex->getCode(), $ex->getMessage(),  var_export($metadata, true)));
        }
    }

    public function __destruct()
    {
        try {
            if (!is_null($this->redis)) {
                $this->redis->disconnect();
                $this->redis = null;
            }
        } catch (PredisConnectionException|\RedisException $ex) {
            $metadata = null;
            try {
                if ($ex instanceof PredisConnectionException && method_exists($ex, 'getConnection')) {
                    $res = $ex->getConnection()->getResource();
                    if (is_resource($res)) $metadata = @stream_get_meta_data($res);
                }
            } catch (\Throwable $ignored) {}
            Log::error
            (
                sprintf
                (
                    "RedisCacheService::__destruct %s %s %s",
                    $ex->getCode(),
                    $ex->getMessage(),
                    var_export($metadata, true)
                )
            );
        }
        catch(\Exception $ex){
            Log::warning($ex);
        }
    }

    /**
     * @param callable $callback
     * @param mixed $defaultReturn
     * @param int $maxRetries
     * @return mixed|null
     */
    private function retryOnConnectionError
    (
        callable $callback,
        $defaultReturn = null,
        int $maxRetries = self::MaxRetries,
        int $baseDelayMs = 100
    )
    {
        $attempt = 0;
        do {
            try {
                // Ensure we have a connection before invoking the callback
                if (!$this->redis) {
                    Log::debug("RedisCacheService::retryOnConnectionError trying to get a new connection ...");
                    $this->redis = Redis::connection(self::Connection);
                }
                return $callback($this->redis);
            } catch (PredisConnectionException|\RedisException $ex) {
                ++$attempt;
                // Safe metadata (Predis only)
                $metadata = null;
                try {
                    if ($ex instanceof PredisConnectionException && method_exists($ex, 'getConnection')) {
                        $res = $ex->getConnection()->getResource();
                        if (is_resource($res)) $metadata = @stream_get_meta_data($res);
                    }
                } catch (\Throwable $ignored) {}
                Log::warning($ex);
                Log::warning
                (
                    sprintf
                    (
                        "RedisCacheService::retryOnConnectionError code %s msg %s metadata %s attempt %s, trying to reconnect...",
                        $ex->getCode(),
                        $ex->getMessage(),
                        var_export($metadata, true),
                        $attempt
                    )
                );

                if ($attempt > $maxRetries) {
                    Log::error(sprintf("RedisCacheService::retryOnConnectionError max retries reached %s!.", $maxRetries));
                    Log::error($ex);
                    break;
                }

                // Exponential backoff with jitter (cap at ~2s)

                $delay = min(2000, (int)($baseDelayMs * (2 ** ($attempt - 1))));
                $jitter = random_int(0, (int)($delay * 0.2));
                usleep(1000 * ($delay + $jitter));

                try { Redis::purge(self::Connection); } catch (\Throwable $ignored) {}
                try { $this->redis = Redis::connection(self::Connection); } catch (\Throwable $e) {
                    Log::warning('RedisCacheService::retryOnConnectionError Redis reconnection failed', ['message' => $e->getMessage()]);
                    // keep looping; next iteration will try again
                    $this->redis = null;
                }
            }
        } while (true);
        return $defaultReturn;
    }

    public function boot()
    {
        if (is_null($this->redis)) {
            try {
                Redis::purge(self::Connection);
                $this->redis = Redis::connection(self::Connection);
            } catch (\Throwable $e) {
                Log::warning('RedisCacheService::retryOnConnectionError Redis boot failed', ['message' => $e->getMessage()]);
                $this->redis = null;
            }
        }
    }

    /**
     * @param $key
     * @return mixed
     */
    public function delete($key)
    {
        return $this->retryOnConnectionError(function ($conn) use ($key) {
            // DEL is idempotent: returns 0 if key doesn't exist
            return (int)$conn->del($key);
        }, 0);
    }

    public function deleteArray(array $keys)
    {
        return $this->retryOnConnectionError(function ($conn) use ($keys) {
            if (count($keys) === 0) return 0;
            // Variadic is widely supported; chunk if you expect huge lists
            return (int)$conn->del(...$keys);
        }, 0);
    }

    /**
     * @param $key
     * @return bool
     */
    public function exists($key)
    {
        return $this->retryOnConnectionError(function ($conn) use ($key) {
            return $conn->exists($key) > 0;
        }, false);
    }

    /**
     * @param $name
     * @param array $values
     * @return mixed
     */
    public function getHash($name, array $values)
    {
        return $this->retryOnConnectionError(function ($conn) use ($name, $values) {
            $res = [];
            if ($conn->exists($name)) {
                $cache_values = $conn->hmget($name, $values);
                for ($i = 0; $i < count($cache_values); $i++) {
                    $res[$values[$i]] = $cache_values[$i];
                }
            }
            return $res;
        }, []);
    }

    public function storeHash($name, array $values, $ttl = 0)
    {
        return $this->retryOnConnectionError(function ($conn) use ($name, $values, $ttl) {
            $res = false;
            //stores in REDIS
            if (!$conn->exists($name)) {
                $conn->hmset($name, $values);
                $res = true;
                //sets expiration time
                if ($ttl > 0) {
                    $conn->expire($name, $ttl);
                }
            }
            return $res;
        }, false);
    }

    public function incCounter($counter_name, $ttl = 0)
    {
        return $this->retryOnConnectionError(function ($conn) use ($counter_name, $ttl) {
            if ($conn->setnx($counter_name, 1)) {
                if ($ttl > 0) $conn->expire($counter_name, (int)$ttl);
                return 1;
            }
            return (int)$conn->incr($counter_name);
        }, 0);
    }

    public function incCounterIfExists($counter_name)
    {
        return $this->retryOnConnectionError(function ($conn) use ($counter_name) {
            $res = false;
            if ($conn->exists($counter_name)) {
                $conn->incr($counter_name);
                $res = true;
            }
            return $res;
        }, false);
    }

    public function addMemberSet($set_name, $member)
    {
        return $this->retryOnConnectionError(function ($conn) use ($set_name, $member) {
            return $conn->sadd($set_name, $member);
        });
    }

    public function deleteMemberSet($set_name, $member)
    {
        return $this->retryOnConnectionError(function ($conn) use ($set_name, $member) {
            return $conn->srem($set_name, $member);
        });
    }

    public function getSet($set_name)
    {
        return $this->retryOnConnectionError(function ($conn) use ($set_name) {
            return $conn->smembers($set_name);
        });
    }

    public function getSingleValue($key)
    {
        return $this->retryOnConnectionError(function ($conn) use ($key) {
            return $conn->get($key);
        });
    }

    /**
     * @param $key
     * @param $value
     * @param int $ttl in seconds
     * @return mixed
     */
    public function setSingleValue($key, $value, $ttl = 0)
    {
        return $this->retryOnConnectionError(function ($conn) use ($key, $value, $ttl) {
            if ($ttl > 0) {
                return $conn->setex($key, $ttl, $value);
            }
            return $conn->set($key, $value);
        });
    }

    public function addSingleValue($key, $value, $ttl = 0)
    {
        return $this->retryOnConnectionError(function ($conn) use ($key, $value, $ttl) {
            $res = $conn->setnx($key, $value);
            if ($res && $ttl > 0) {
                $conn->expire($key, $ttl);
            }
            return $res;
        });
    }

    public function setKeyExpiration($key, $ttl)
    {
        return $this->retryOnConnectionError(function ($conn) use ($key, $ttl) {
            return $conn->expire($key, intval($ttl));
        });
    }

    /**Returns the remaining time to live of a key that has a timeout.
     * @param string $key
     * @return int
     */
    public function ttl($key)
    {
        return $this->retryOnConnectionError(function ($conn) use ($key) {
            return (int)$conn->ttl($key);
        }, 0);
    }
}