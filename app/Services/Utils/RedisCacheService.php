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
use Illuminate\Support\Facades\Redis;
use libs\utils\ICacheService;
use Predis\Connection\ConnectionException;

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
            Log::debug(sprintf("RedisCacheService::__construct connected to redis server."));
        } catch (ConnectionException $ex) {
            $resource = $ex->getConnection()->getResource();
            $metadata = var_export(stream_get_meta_data($resource), true);
            Log::error(sprintf("RedisCacheService::__construct %s %s %s", $ex->getCode(), $ex->getMessage(), $metadata));
        }
    }

    public function __destruct()
    {
        try {
            if (!is_null($this->redis)) {
                Log::debug(sprintf("RedisCacheService::__destruct disconnecting from redis server"));
                $this->redis->disconnect();
                $this->redis = null;
            }
        } catch (ConnectionException $ex) {
            $resource = $ex->getConnection()->getResource();
            $metadata = var_export(stream_get_meta_data($resource), true);
            Log::error(sprintf("RedisCacheService::__destruct %s %s %s", $ex->getCode(), $ex->getMessage(), $metadata));
        }
    }

    /**
     * @param callable $callback
     * @param mixed $defaultReturn
     * @param int $maxRetries
     * @return mixed|null
     */
    private function retryOnConnectionError(callable $callback, $defaultReturn = null, int $maxRetries = self::MaxRetries)
    {
        $times = 0;
        do {
            try {
                return $callback();
            } catch (ConnectionException $ex) {
                $resource = $ex->getConnection()->getResource();
                $metadata = var_export(stream_get_meta_data($resource), true);
                Log::error($ex);
                Log::error(sprintf("RedisCacheService::retryOnConnectionError code %s msg %s metadata %s, trying to reconnect...", $ex->getCode(), $ex->getMessage(), $metadata));
                Redis::purge(self::Connection);
                $this->redis = Redis::connection(self::Connection);
                ++$times;
                if ($times > $maxRetries) {
                    Log::debug(sprintf("RedisCacheService::retryOnConnectionError max retries reached %s", $maxRetries));
                    break;
                }
            }
        } while (true);
        return $defaultReturn;
    }

    public function boot()
    {
        if (is_null($this->redis)) {
            $this->redis = Redis::connection();
        }
    }

    /**
     * @param $key
     * @return mixed
     */
    public function delete($key)
    {
        return $this->retryOnConnectionError(function () use ($key) {
            $res = 0;
            if ($this->redis->exists($key)) {
                $res = $this->redis->del($key);
            }
            return $res;
        }, 0);
    }

    public function deleteArray(array $keys)
    {
        return $this->retryOnConnectionError(function () use ($keys) {
            if (count($keys) > 0) {
                $this->redis->del($keys);
            }
        });
    }

    /**
     * @param $key
     * @return bool
     */
    public function exists($key)
    {
        return $this->retryOnConnectionError(function () use ($key) {
            return $this->redis->exists($key) > 0;
        }, false);
    }

    /**
     * @param $name
     * @param array $values
     * @return mixed
     */
    public function getHash($name, array $values)
    {
        return $this->retryOnConnectionError(function () use ($name, $values) {
            $res = [];
            if ($this->redis->exists($name)) {
                $cache_values = $this->redis->hmget($name, $values);
                for ($i = 0; $i < count($cache_values); $i++) {
                    $res[$values[$i]] = $cache_values[$i];
                }
            }
            return $res;
        }, []);
    }

    public function storeHash($name, array $values, $ttl = 0)
    {
        return $this->retryOnConnectionError(function () use ($name, $values, $ttl) {
            $res = false;
            //stores in REDIS
            if (!$this->redis->exists($name)) {
                $this->redis->hmset($name, $values);
                $res = true;
                //sets expiration time
                if ($ttl > 0) {
                    $this->redis->expire($name, $ttl);
                }
            }
            return $res;
        }, false);
    }

    public function incCounter($counter_name, $ttl = 0)
    {
        return $this->retryOnConnectionError(function () use ($counter_name, $ttl) {
            if ($this->redis->setnx($counter_name, 1)) {
                $this->redis->expire($counter_name, $ttl);
                return 1;
            } else {
                return (int)$this->redis->incr($counter_name);
            }
        }, 0);
    }

    public function incCounterIfExists($counter_name)
    {
        return $this->retryOnConnectionError(function () use ($counter_name) {
            $res = false;
            if ($this->redis->exists($counter_name)) {
                $this->redis->incr($counter_name);
                $res = true;
            }
            return $res;
        }, false);
    }

    public function addMemberSet($set_name, $member)
    {
        return $this->retryOnConnectionError(function () use ($set_name, $member) {
            return $this->redis->sadd($set_name, $member);
        });
    }

    public function deleteMemberSet($set_name, $member)
    {
        return $this->retryOnConnectionError(function () use ($set_name, $member) {
            return $this->redis->srem($set_name, $member);
        });
    }

    public function getSet($set_name)
    {
        return $this->retryOnConnectionError(function () use ($set_name) {
            return $this->redis->smembers($set_name);
        });
    }

    public function getSingleValue($key)
    {
        return $this->retryOnConnectionError(function () use ($key) {
            return $this->redis->get($key);
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
        return $this->retryOnConnectionError(function () use ($key, $value, $ttl) {
            if ($ttl > 0) {
                return $this->redis->setex($key, $ttl, $value);
            }
            return $this->redis->set($key, $value);
        });
    }

    public function addSingleValue($key, $value, $ttl = 0)
    {
        return $this->retryOnConnectionError(function () use ($key, $value, $ttl) {
            $res = $this->redis->setnx($key, $value);
            if ($res && $ttl > 0) {
                $this->redis->expire($key, $ttl);
            }
            return $res;
        });
    }

    public function setKeyExpiration($key, $ttl)
    {
        return $this->retryOnConnectionError(function () use ($key, $ttl) {
            return $this->redis->expire($key, intval($ttl));
        });
    }

    /**Returns the remaining time to live of a key that has a timeout.
     * @param string $key
     * @return int
     */
    public function ttl($key)
    {
        return $this->retryOnConnectionError(function () use ($key) {
            return (int)$this->redis->ttl($key);
        }, 0);
    }
}