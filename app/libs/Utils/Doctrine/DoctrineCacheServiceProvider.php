<?php namespace App\libs\Utils\Doctrine;
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

use Illuminate\Contracts\Cache\Factory;
use Illuminate\Support\ServiceProvider;
use LaravelDoctrine\ORM\Configuration\Cache\CacheManager;
use LaravelDoctrine\ORM\Configuration\Cache\RedisCacheProvider;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\Psr16Adapter;

class CustomRedisCacheProvider extends RedisCacheProvider
{

    /** @param mixed[] $settings */
    public function resolve(array $settings = []): CacheItemPoolInterface
    {
        $store = $settings['store'] ?? $this->store ?? null;

        if ($store === null) {
            throw new \InvalidArgumentException('Please specify the `store` when using the "illuminate" cache driver.');
        }

        return new Psr16Adapter($this->cache->store($store), $settings['namespace'] ?? '', $settings['default_lifetime'] ?? 0);
    }
}
/**
 * Class DoctrineCacheServiceProvider
 * @package App\libs\Utils\Doctrine
 */
class DoctrineCacheServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->resolving(CacheManager::class, function (CacheManager $cache) {
            // Override the 'redis' driver
            $cache->extend('redis', function (array $settings, $app) {
                $res = new CustomRedisCacheProvider($app->make(Factory::class));
                return $res->resolve($settings);
            });
        });
    }
}