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


use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPRuntimeException;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;
use RuntimeException;

/**
 * Class RabbitPublisherService
 * @package App\Services\Utils
 */
final class RabbitPublisherService implements IPublisherService
{

    const WAIT_BEFORE_RECONNECT_uS = 1000000;

    private string $host;

    private int $port;

    private string $login;

    private string $password;

    private string $exchange;

    private string $vhost;

    private string $exchange_type;

    private bool $passive;

    private bool $durable;

    private bool $auto_delete;

    public function __construct(
        string $exchange,
        string $host = null,
        int $port = null,
        string $login = null,
        string $password = null,
        string $vhost = null,
        string $exchange_type = AMQPExchangeType::FANOUT,
        bool $passive = false,
        bool $durable = true,
        bool $auto_delete = false,
    ){
        $this->host = $host ?? Config::get('rabbitmq.host');
        $this->port =  $port ?? Config::get('rabbitmq.port');
        $this->login = $login ?? Config::get('rabbitmq.user');
        $this->password = $password ?? Config::get('rabbitmq.password');
        $this->vhost = $vhost ?? Config::get('rabbitmq.vhost');
        $this->exchange = $exchange;
        $this->exchange_type = $exchange_type;
        $this->passive = $passive;
        $this->durable = $durable;
        $this->auto_delete = $auto_delete;
    }

    /**
     * @return AMQPStreamConnection
     * @throws \Exception
     */
    private function connect(): AMQPStreamConnection
    {
        Log::debug
        (
            sprintf
            (
                "RabbitPublisherService::connect %s %s %s %s %s",
                $this->host,
                $this->port,
                $this->login,
                $this->password,
                $this->vhost
            )
        );

        return new AMQPStreamConnection(
            $this->host,
            $this->port,
            $this->login,
            $this->password,
            $this->vhost
        );
    }

    /**
     * @param $connection
     */
    private function cleanup_connection($connection): void
    {
        // Connection might already be closed.
        // Ignoring exceptions.
        try {
            if ($connection !== null) {
                $connection->close();
            }
        } catch (\ErrorException $ex) {
            Log::warning($ex);
        }
    }

    /**
     * @param AMQPChannel $channel
     * @param array $payload
     * @param string $routing_key
     * @throws \DateMalformedStringException
     */
    private function publishMessage(AMQPChannel $channel, array $payload, string $routing_key = ''):void{

        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $payload['published_at'] = $now->getTimestamp();

        $message = new AMQPMessage(json_encode($payload),
            [
                'content_type' => 'application/json',
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
            ]);

        Log::debug(sprintf("RabbitPublisherService::publishMessage publishing message %s", json_encode($payload)));

        $channel->basic_publish($message, $this->exchange, $routing_key, true, false);
    }

    /**
     * @param array $payload
     * @param string $routing_key
     * @throws Exception
     */
    public function publish(array $payload, string $routing_key = ''):void
    {
        $connection = null;
        $done = false;
        while (!$done) {
            try {
                $connection = $this->connect();

                $channel = $connection->channel();

                $channel->exchange_declare
                (
                    $this->exchange,
                    $this->exchange_type,
                    $this->passive,
                    $this->durable,
                    $this->auto_delete
                );

                $this->publishMessage($channel, $payload, $routing_key);
                $channel->close();
                $connection->close();
                $done = true;
                Log::debug('RabbitPublisherService::publish - message published to QUEUE');
            } catch (AMQPRuntimeException $ex) {
                Log::error($ex);
                $this->cleanup_connection($connection);
                usleep(self::WAIT_BEFORE_RECONNECT_uS);
            } catch (RuntimeException $ex) {
                Log::error($ex);
                $this->cleanup_connection($connection);
                usleep(self::WAIT_BEFORE_RECONNECT_uS);
            } catch (Exception $ex) {
                Log::error($ex);
                $this->cleanup_connection($connection);
                usleep(self::WAIT_BEFORE_RECONNECT_uS);
            }
        }
    }
}