<?php namespace App\Services\Model\Imp;
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

use App\Services\Model\AbstractService;
use App\Services\Model\IProcessScheduleEntityLifeCycleEventService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use libs\utils\ITransactionService;
use models\summit\ISummitRepository;
use models\summit\Summit;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Exception\AMQPRuntimeException;

/**
 * Class ProcessScheduleEntityLifeCycleEventService
 * @package App\Services\Model\Imp
 */
final class ProcessScheduleEntityLifeCycleEventService
    extends AbstractService
    implements IProcessScheduleEntityLifeCycleEventService

{

    const WAIT_BEFORE_RECONNECT_uS = 1000000;

    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    private $host;

    private $port;

    private $login;

    private $password;

    private $exchange;

    private $vhost;

    /**
     * @param ISummitRepository $summit_repository
     * @param ITransactionService $tx_service
     */
    public function __construct
    (
        ISummitRepository   $summit_repository,
        ITransactionService $tx_service
    )
    {
        parent::__construct($tx_service);
        $this->summit_repository = $summit_repository;
        $this->host = Config::get('rabbitmq.host');
        $this->port =  Config::get('rabbitmq.port');
        $this->login = Config::get('rabbitmq.user');
        $this->password =   Config::get('rabbitmq.password');
        $this->vhost =  Config::get('rabbitmq.vhost');
        $this->exchange = 'entities-updates-broker';
    }

    private function connect(): AMQPStreamConnection
    {

        Log::debug
        (
            sprintf
            (
                "ProcessScheduleEntityLifeCycleEventService::connect %s %s %s %s %s",
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

    private function cleanup_connection($connection)
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
     * @param int $summit_id
     * @param int $entity_id
     * @param string $entity_type
     * @param string $entity_operator
     */
    private function publishMessage(AMQPChannel $channel, int $summit_id, int $entity_id, string $entity_type, string $entity_operator):void{

        $now = new \DateTime('now', new \DateTimeZone('UTC'));

        $payload = [
            'summit_id' => $summit_id,
            'entity_id' => $entity_id,
            'entity_type' => $entity_type,
            'entity_operator' => $entity_operator,
            'published_at' => $now->getTimestamp(),
        ];

        $message = new AMQPMessage(json_encode($payload),
            [
                'content_type' => 'application/json',
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
            ]);

        Log::debug(sprintf("ProcessScheduleEntityLifeCycleEventService::publishMessage publishing message %s", json_encode($payload)));

        $channel->basic_publish($message, $this->exchange, '', true, false);
    }

    /**
     * @param string $entity_operator
     * @param int $summit_id
     * @param int $entity_id
     * @param string $entity_type
     */
    public function process(string $entity_operator, int $summit_id, int $entity_id, string $entity_type): void
    {
        $this->tx_service->transaction(function () use ($entity_operator, $summit_id, $entity_id, $entity_type) {

            Log::debug
            (
                sprintf
                (
                    "ProcessScheduleEntityLifeCycleEventService::process %s %s %s %s",
                    $summit_id,
                    $entity_id,
                    $entity_type,
                    $entity_operator
                )
            );

            $connection = null;
            $done = false;
            while (!$done) {
                try {

                    $connection = $this->connect();

                    $channel = $connection->channel();

                    $channel->exchange_declare
                    (
                        $this->exchange,
                        AMQPExchangeType::FANOUT,
                        false,
                        true,
                        false
                    );

                    if ($entity_type === 'PresentationSpeaker') {
                        foreach ($this->summit_repository->getNotEnded() as $summit) {
                            if (!$summit instanceof Summit) continue;
                            if ($summit->getSpeaker($entity_id)) {
                                // speaker is present on this summit
                                $this->publishMessage($channel, $summit->getId(), $entity_id, $entity_type, $entity_operator);
                            }
                        }

                    }
                    else {
                        $this->publishMessage($channel, $summit_id, $entity_id, $entity_type, $entity_operator);
                    }

                    $channel->close();
                    $connection->close();
                    $done = true;
                    Log::debug(sprintf("ProcessScheduleEntityLifeCycleEventService::process message published to QUEUE"));

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

        });
    }
}