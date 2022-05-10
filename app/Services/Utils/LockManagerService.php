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

    const MaxRetries = 3;
    const BackOffMultiplier = 10;
    const BackOffBaseInterval = 1000;
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
     * @return LockManagerService
     * @throws UnacquiredLockException
     */
    public function acquireLock(string $name, int $lifetime = 3600):LockManagerService
    {
        Log::debug(sprintf("LockManagerService::acquireLock name %s lifetime %s",$name, $lifetime));
        $attempt  = 0 ;
        do {
            $time = time() + $lifetime + 1;
            $success = $this->cache_service->addSingleValue($name, $time, $time);
            if($success) return $this;
            ++$attempt;
            $wait_interval = self::BackOffBaseInterval * self::BackOffMultiplier ^ $attempt;
            Log::debug(sprintf("LockManagerService::acquireLock name %s retrying in %s attempt %s", $name, $wait_interval, $attempt));
            usleep($wait_interval);
            if($attempt > self::MaxRetries) {
                // only one time we could use this handle
                Log::error(sprintf("LockManagerService::acquireLock name %s lifetime %s ERROR MAX RETRIES attempt %s", $name, $lifetime, $attempt));
                throw new UnacquiredLockException(sprintf("lock name %s", $name));
            }
        } while(1);
    }

    /**
     * @param string $name
     * @return $this
     */
    public function releaseLock(string $name):LockManagerService
    {
        Log::debug(sprintf("LockManagerService::releaseLock name %s",$name));
        $this->cache_service->delete($name);
        return $this;
    }

    /**
     * @param string $name
     * @param Closure $callback
     * @param int $lifetime
     * @return null
     * @throws UnacquiredLockException
     * @throws Exception
     */
    public function lock(string $name, Closure $callback, int $lifetime = 3600)
    {
        $result = null;
        Log::debug(sprintf("LockManagerService::lock name %s lifetime %s", $name, $lifetime));

        try
        {
            $this->acquireLock($name, $lifetime);
            Log::debug(sprintf("LockManagerService::lock name %s calling callback", $name));
            $result = $callback($this);
        }
        catch(UnacquiredLockException $ex)
        {
            Log::warning($ex);
            throw $ex;
        }
        catch(Exception $ex)
        {
            Log::error($ex);
            throw $ex;
        }
        finally {
            $this->releaseLock($name);
        }
        return $result;
    }

}