<?php namespace App\Redis;
/**
 * Copyright 2026 OpenStack Foundation
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

use Illuminate\Redis\Connections\PredisConnection;
use Illuminate\Support\Facades\Log;
use Predis\Connection\ConnectionException;

/**
 * Class ResilientPredisConnection
 *
 * Extends the default PredisConnection to add automatic retry with
 * reconnect for idempotent Redis commands on transient connection failures.
 *
 * Non-idempotent commands (INCR, LPUSH, RPUSH, EVAL, etc.) are never
 * retried because the command may have already been executed on the
 * server before the read-side of the connection failed.
 */
class ResilientPredisConnection extends PredisConnection
{
    private int $retryLimit;

    private int $retryDelay;

    /**
     * Commands that are safe to retry after a connection failure.
     * A command is safe when executing it twice produces the same result.
     */
    private const IDEMPOTENT_COMMANDS = [
        // reads
        'GET', 'MGET', 'HGET', 'HGETALL', 'HMGET', 'HEXISTS', 'HLEN', 'HKEYS', 'HVALS',
        'LLEN', 'LRANGE', 'LINDEX',
        'SCARD', 'SMEMBERS', 'SISMEMBER',
        'ZCARD', 'ZCOUNT', 'ZRANGE', 'ZRANGEBYSCORE', 'ZREVRANGEBYSCORE', 'ZSCORE', 'ZRANK', 'ZREVRANK',
        'EXISTS', 'TYPE', 'TTL', 'PTTL', 'KEYS', 'SCAN', 'HSCAN', 'SSCAN', 'ZSCAN',
        'INFO', 'PING', 'DBSIZE', 'TIME', 'STRLEN', 'GETRANGE',
        // idempotent writes
        'SET', 'SETEX', 'PSETEX', 'MSET', 'SETNX', 'GETSET',
        'HSET', 'HMSET', 'HSETNX',
        'DEL', 'HDEL', 'UNLINK',
        'EXPIRE', 'EXPIREAT', 'PEXPIRE', 'PEXPIREAT', 'PERSIST',
        'SADD', 'SREM',
        'ZADD', 'ZREM', 'ZREMRANGEBYSCORE', 'ZREMRANGEBYRANK',
    ];

    /**
     * @param \Predis\Client $client
     * @param int $retryLimit Max number of retries (0 = no retries, behaves like stock PredisConnection)
     * @param int $retryDelay Base delay in milliseconds between retries (doubled each attempt)
     */
    public function __construct($client, int $retryLimit = 2, int $retryDelay = 50)
    {
        parent::__construct($client);
        $this->retryLimit = $retryLimit;
        $this->retryDelay = $retryDelay;
    }

    /**
     * @inheritdoc
     */
    public function command($method, array $parameters = [])
    {
        try {
            return parent::command($method, $parameters);
        } catch (ConnectionException $e) {
            if (!$this->isIdempotent($method)) {
                throw $e;
            }
            return $this->retryCommand($method, $parameters, $e);
        }
    }

    /**
     * Retry an idempotent command after reconnecting.
     */
    private function retryCommand(string $method, array $parameters, ConnectionException $previous): mixed
    {
        $lastException = $previous;

        for ($attempt = 1; $attempt <= $this->retryLimit; $attempt++) {
            $delay = $this->retryDelay * (2 ** ($attempt - 1)); // exponential back-off

            Log::warning('ResilientPredisConnection: retrying command', [
                'command' => strtoupper($method),
                'attempt' => $attempt,
                'max_retries' => $this->retryLimit,
                'delay_ms' => $delay,
                'error' => $previous->getMessage(),
            ]);

            usleep($delay * 1000);

            try {
                $this->client->disconnect();

                return parent::command($method, $parameters);
            } catch (ConnectionException $e) {
                $lastException = $e;
            }
        }

        Log::error('ResilientPredisConnection: all retries exhausted', [
            'command' => strtoupper($method),
            'retries' => $this->retryLimit,
            'error' => $lastException->getMessage(),
        ]);

        throw $lastException;
    }

    private function isIdempotent(string $method): bool
    {
        return in_array(strtoupper($method), self::IDEMPOTENT_COMMANDS, true);
    }
}
