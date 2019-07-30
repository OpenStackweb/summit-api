<?php namespace App\Queue\RabbitMQ;
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
use Illuminate\Container\Container;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Queue\Job as JobContract;
use Illuminate\Queue\Jobs\Job;
use Illuminate\Support\Arr;
use PhpAmqpLib\Exception\AMQPProtocolChannelException;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
/**
 * Class RabbitMQJob
 * @package App\Queue\RabbitMQ
 */
class RabbitMQJob extends Job implements JobContract
{
    /**
     * The RabbitMQ queue instance.
     *
     * @var RabbitMQQueue
     */
    protected $rabbitmq;

    /**
     * The RabbitMQ message instance.
     *
     * @var AMQPMessage
     */
    protected $message;

    /**
     * The JSON decoded version of "$message".
     *
     * @var array
     */
    protected $decoded;

    public function __construct(
        Container $container,
        RabbitMQQueue $rabbitmq,
        AMQPMessage $message,
        string $connectionName,
        string $queue
    ) {
        $this->container = $container;
        $this->rabbitmq = $rabbitmq;
        $this->message = $message;
        $this->connectionName = $connectionName;
        $this->queue = $queue;
        $this->decoded = $this->payload();
    }

    /**
     * {@inheritdoc}
     */
    public function getJobId()
    {
        return $this->decoded['id'] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function getRawBody(): string
    {
        return $this->message->getBody();
    }

    /**
     * {@inheritdoc}
     */
    public function attempts(): int
    {
        if (! $data = $this->getRabbitMQMessageHeaders()) {
            return 1;
        }

        $laravelAttempts = (int) Arr::get($data, 'laravel.attempts', 0);

        return $laravelAttempts + 1;
    }

    /**
     * {@inheritdoc}
     */
    public function markAsFailed(): void
    {
        parent::markAsFailed();

        // We must tel rabbitMQ this Job is failed
        // The message must be rejected when the Job marked as failed, in case rabbitMQ wants to do some extra magic.
        // like: Death lettering the message to an other exchange/routing-key.
        $this->rabbitmq->reject($this);
    }

    /**
     * {@inheritdoc}
     *
     * @throws BindingResolutionException
     */
    public function delete(): void
    {
        parent::delete();

        // When delete is called and the Job was not failed, the message must be acknowledged.
        // This is because this is a controlled call by a developer. So the message was handled correct.
        if (! $this->failed) {
            $this->rabbitmq->ack($this);
        }
    }

    /**
     * Release the job back into the queue.
     *
     * @param int $delay
     * @throws AMQPProtocolChannelException
     */
    public function release($delay = 0): void
    {
        parent::release();

        // Always create a new message when this Job is released
        $this->rabbitmq->laterRaw($delay, $this->message->getBody(), $this->queue, $this->attempts());

        // Releasing a Job means the message was failed to process.
        // Because this Job message is always recreated and pushed as new message, this Job message is correctly handled.
        // We must tell rabbitMQ this job message can be removed by acknowledging the message.
        $this->rabbitmq->ack($this);
    }

    /**
     * Get the underlying RabbitMQ connection.
     *
     * @return RabbitMQQueue
     */
    public function getRabbitMQ(): RabbitMQQueue
    {
        return $this->rabbitmq;
    }

    /**
     * Get the underlying RabbitMQ message.
     *
     * @return AMQPMessage
     */
    public function getRabbitMQMessage(): AMQPMessage
    {
        return $this->message;
    }

    /**
     * Get the headers from the rabbitMQ message.
     *
     * @return array|null
     */
    protected function getRabbitMQMessageHeaders(): ?array
    {
        /** @var AMQPTable|null $headers */
        if (! $headers = Arr::get($this->message->get_properties(), 'application_headers')) {
            return null;
        }

        return $headers->getNativeData();
    }
}
