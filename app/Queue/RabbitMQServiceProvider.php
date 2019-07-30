<?php namespace App\Queue;
/**
 * Copyright 2020 OpenStack Foundation
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

use App\Queue\RabbitMQ\RabbitMQConnector;
use Illuminate\Queue\QueueManager;
use Illuminate\Support\ServiceProvider;

/**
 * Class RabbitMQServiceProvider
 * @package App\Queue
 */
class RabbitMQServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
    }

    /**
     * Register the application's event listeners.
     *
     * @return void
     */
    public function boot(): void
    {
        /** @var QueueManager $queue */
        $queue = $this->app['queue'];

        $queue->addConnector('rabbitmq', function () {
            return new RabbitMQConnector($this->app['events']);
        });
    }
}