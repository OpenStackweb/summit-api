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

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPRuntimeException;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Class RabbitPublisherService
 * @package App\Services\Utils
 */
final class RabbitPublisherService {
  const WAIT_BEFORE_RECONNECT_uS = 1000000;

  /**
   * @var string
   */
  private $host;

  /**
   * @var int`
   */
  private $port;

  /**
   * @var string
   */
  private $login;

  /**
   * @var string
   */
  private $password;

  /**
   * @var string
   */
  private $exchange;

  /**
   * @var string
   */
  private $vhost;

  public function __construct(string $exchange) {
    $this->host = Config::get("rabbitmq.host");
    $this->port = Config::get("rabbitmq.port");
    $this->login = Config::get("rabbitmq.user");
    $this->password = Config::get("rabbitmq.password");
    $this->vhost = Config::get("rabbitmq.vhost");
    $this->exchange = $exchange;
  }

  /**
   * @return AMQPStreamConnection
   */
  private function connect(): AMQPStreamConnection {
    Log::debug(
      sprintf(
        "RabbitPublisherService::connect %s %s %s %s %s",
        $this->host,
        $this->port,
        $this->login,
        $this->password,
        $this->vhost,
      ),
    );

    return new AMQPStreamConnection(
      $this->host,
      $this->port,
      $this->login,
      $this->password,
      $this->vhost,
    );
  }

  /**
   * @param $connection
   */
  private function cleanup_connection($connection) {
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
   * @throws \Exception
   */
  private function publishMessage(AMQPChannel $channel, array $payload): void {
    $now = new \DateTime("now", new \DateTimeZone("UTC"));
    $payload["published_at"] = $now->getTimestamp();

    $message = new AMQPMessage(json_encode($payload), [
      "content_type" => "application/json",
      "delivery_mode" => AMQPMessage::DELIVERY_MODE_PERSISTENT,
    ]);

    Log::debug(
      sprintf(
        "RabbitPublisherService::publishMessage publishing message %s",
        json_encode($payload),
      ),
    );

    $channel->basic_publish($message, $this->exchange, "", true, false);
  }

  /**
   * @param array $payload
   * @throws \Exception
   */
  public function publish(array $payload): void {
    $connection = null;
    $done = false;
    while (!$done) {
      try {
        $connection = $this->connect();

        $channel = $connection->channel();

        $channel->exchange_declare($this->exchange, AMQPExchangeType::FANOUT, false, true, false);

        $this->publishMessage($channel, $payload);
        $channel->close();
        $connection->close();
        $done = true;
        Log::debug(sprintf("RabbitPublisherService::process message published to QUEUE"));
      } catch (AMQPRuntimeException $ex) {
        Log::error($ex);
        $this->cleanup_connection($connection);
        usleep(self::WAIT_BEFORE_RECONNECT_uS);
      } catch (\RuntimeException $ex) {
        Log::error($ex);
        $this->cleanup_connection($connection);
        usleep(self::WAIT_BEFORE_RECONNECT_uS);
      } catch (\ErrorException $ex) {
        Log::error($ex);
        $this->cleanup_connection($connection);
        usleep(self::WAIT_BEFORE_RECONNECT_uS);
      }
    }
  }
}
