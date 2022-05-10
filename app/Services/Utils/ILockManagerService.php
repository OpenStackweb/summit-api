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
use Closure;
/**
 * Interface ILockManagerService
 * @package App\Services\Utils
 */
interface ILockManagerService
{
    const DefaultLifetime = 3600;
    /**
     * @param string $name
     * @param int $lifetime
     * @throws UnacquiredLockException
     * @return mixed
     */
    public function acquireLock(string $name,int $lifetime = self::DefaultLifetime);
    /**
     * @param  string $name
     * @return mixed
     */
    public function releaseLock(string $name);

    /**
     * @param string $name
     * @param Closure $callback
     * @param int $lifetime
     * @return mixed
     */
    public function lock(string $name, Closure $callback, int $lifetime = self::DefaultLifetime);
}