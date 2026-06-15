<?php namespace App\Services\Utils;
/*
 * Copyright 2022 OpenStack Foundation
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
use App\Services\Utils\Exceptions\UnacquiredLockException;
use Illuminate\Support\Facades\Log;
use libs\utils\ICacheService;
use Exception;
use Closure;
/**
 * Class LockManagerService
 * @package App\Services\Utils
 */
final class LockManagerService implements ILockManagerService {

    const MaxRetries        = 3;
    const BackOffMultiplier = 2.0;
    const BackOffBaseInterval = 100000; // microseconds

    /**
     * @var ICacheService
     */
    private $cache_service;

    /**
     * LockManagerService constructor.
     * @param ICacheService $cache_service
     */
    public function __construct(ICacheService $cache_service){
        $this->cache_service = $cache_service;
    }

    /**
     * @param string $name
     * @param int $lifetime
     * @return string ownership token — pass to releaseLock
     * @throws UnacquiredLockException
     */
    public function acquireLock(string $name, int $lifetime = 3600): string
    {
        Log::debug(sprintf("LockManagerService::acquireLock name %s lifetime %s", $name, $lifetime));
        if ($lifetime <= 0) {
            throw new \InvalidArgumentException("Lock lifetime must be greater than zero seconds.");
        }
        $token   = bin2hex(random_bytes(16));
        $attempt = 0;
        do {
            $success = $this->cache_service->addSingleValue($name, $token, $lifetime);
            if ($success) {
                return $token;
            }
            $wait_interval = (int)(self::BackOffBaseInterval * (self::BackOffMultiplier ** $attempt));
            Log::debug(sprintf("LockManagerService::acquireLock name %s retrying in %s µs (attempt %s)", $name, $wait_interval, $attempt));
            usleep($wait_interval);
            if ($attempt >= (self::MaxRetries - 1)) {
                Log::error(sprintf("LockManagerService::acquireLock name %s lifetime %s ERROR MAX RETRIES attempt %s", $name, $lifetime, $attempt));
                throw new UnacquiredLockException(sprintf("lock name %s", $name));
            }
            ++$attempt;
        } while (1);
    }

    /**
     * @param string $name
     * @param string $token ownership token returned by acquireLock
     */
    public function releaseLock(string $name, string $token): void
    {
        Log::debug(sprintf("LockManagerService::releaseLock name %s", $name));
        $this->cache_service->deleteIfValueMatches($name, $token);
    }

    /**
     * @param string $name
     * @param Closure $callback
     * @param int $lifetime
     * @return mixed
     * @throws UnacquiredLockException
     * @throws Exception
     */
    public function lock(string $name, Closure $callback, int $lifetime = 3600): mixed
    {
        $token  = null;
        $result = null;
        Log::debug(sprintf("LockManagerService::lock name %s lifetime %s", $name, $lifetime));

        try {
            $token  = $this->acquireLock($name, $lifetime);
            Log::debug(sprintf("LockManagerService::lock name %s calling callback", $name));
            $result = $callback($this);
        }
        catch(UnacquiredLockException $ex) {
            Log::warning($ex);
            throw $ex;
        }
        catch(Exception $ex) {
            Log::error($ex);
            throw $ex;
        }
        finally {
            if ($token !== null) {
                $this->releaseLock($name, $token);
            }
        }
        return $result;
    }

}
