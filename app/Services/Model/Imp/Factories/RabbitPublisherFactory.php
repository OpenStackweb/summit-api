<?php namespace App\Services\Model\Imp\Factories;

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

use App\Services\Utils\IPublisherService;
use App\Services\Utils\RabbitPublisherService;
use Illuminate\Support\Facades\Config;

class RabbitPublisherFactory
{
    /**
     * @param string $connection_key
     * @param string|null $publisher_name
     * @return IPublisherService
     */
    public static function make(string $connection_key, ?string $publisher_name = null): IPublisherService
    {
        $host_settings_path = "queue.connections.{$connection_key}.hosts.0";
        $exchange_settings_path = "queue.connections.{$connection_key}.options.exchange";

        $host_settings = Config::get($host_settings_path);

        if (!$host_settings) {
            throw new \InvalidArgumentException("Host setting not found at {$host_settings_path}");
        }

        $exchange_settings = Config::get($exchange_settings_path);

        if ($exchange_settings) {
            return new RabbitPublisherService(
                $publisher_name ?? $exchange_settings['name'],
                $host_settings['host'],
                $host_settings['port'],
                $host_settings['user'],
                $host_settings['password'],
                $host_settings['vhost'],
                $exchange_settings['type'],
                $exchange_settings['passive'],
                $exchange_settings['durable'],
                $exchange_settings['auto_delete'],
            );
        }

        return new RabbitPublisherService(
            $publisher_name,
            $host_settings['host'],
            $host_settings['port'],
            $host_settings['user'],
            $host_settings['password'],
            $host_settings['vhost']
        );
    }
}