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

use Illuminate\Redis\Connectors\PredisConnector;

/**
 * Class ResilientPredisConnector
 *
 * Wraps the stock PredisConnector, reusing all its config/TLS/option
 * handling, and swaps the returned connection to ResilientPredisConnection.
 */
class ResilientPredisConnector extends PredisConnector
{
    /**
     * @inheritdoc
     */
    public function connect(array $config, array $options)
    {
        $connection = parent::connect($config, $options);

        $retryLimit = (int) ($config['retry_limit'] ?? 2);
        $retryDelay = (int) ($config['retry_delay'] ?? 50);

        return new ResilientPredisConnection(
            $connection->client(),
            $retryLimit,
            $retryDelay
        );
    }
}
