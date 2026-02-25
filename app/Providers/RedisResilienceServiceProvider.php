<?php namespace App\Providers;
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

use App\Redis\ResilientPredisConnector;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Redis\RedisManager;
use Illuminate\Support\ServiceProvider;

/**
 * Class RedisResilienceServiceProvider
 *
 * Registers the "predis_resilient" Redis driver which adds automatic
 * retry-with-reconnect for idempotent commands on transient failures.
 *
 * To activate, set REDIS_CLIENT=predis_resilient in your .env.
 */
class RedisResilienceServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->afterResolving('redis', function (RedisManager $redis, Application $app) {
            $redis->extend('predis_resilient', function () {
                return new ResilientPredisConnector();
            });
        });
    }
}
