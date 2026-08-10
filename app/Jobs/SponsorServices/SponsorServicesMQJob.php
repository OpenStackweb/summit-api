<?php namespace App\Jobs\SponsorServices;

/**
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

use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use VladimirYuldashev\LaravelQueueRabbitMQ\Queue\Jobs\RabbitMQJob as BaseJob;

class SponsorServicesMQJob extends BaseJob
{
    public int $tries = 3;

    /**
     * Seconds to wait before the 2nd and 3rd attempt, as the comma-separated
     * string Worker::calculateBackoff() explodes.
     *
     * These events race the IDP's own user-updated event, which is what refreshes
     * a member's groups locally. Retrying immediately loses that race every time;
     * for auth_user_added_to_sponsor_and_summit that is terminal, because no
     * companion group event follows to repair the state.
     */
    const RetryBackoff = '30,120';

    /**
     * Body key that carries the original event type across a release(): a
     * released message is dead-lettered back through the default exchange, so
     * it is redelivered with the QUEUE NAME as its routing key and the event
     * type would otherwise be lost (see release()).
     */
    const EventTypeKey = 'x_event_type';

    private const KnownEventTypes = [
        EventTypes::AUTH_USER_ADDED_TO_GROUP,
        EventTypes::AUTH_USER_REMOVED_FROM_GROUP,
        EventTypes::AUTH_USER_ADDED_TO_SPONSOR_AND_SUMMIT,
        EventTypes::AUTH_USER_REMOVED_FROM_SPONSOR_AND_SUMMIT,
        EventTypes::AUTH_USER_REMOVED_FROM_SUMMIT,
    ];

    /**
     * The event type driving handler selection. On a first delivery it is the
     * message's routing key; on a redelivery after release() the routing key is
     * the queue name and the original event type travels in the body. Handlers
     * that branch on the event type must use this, never the raw routing key.
     *
     * @return string
     */
    public function getEventType(): string
    {
        $routing_key = $this->getRabbitMQMessage()->getRoutingKey();
        if (in_array($routing_key, self::KnownEventTypes, true)) {
            return $routing_key;
        }

        $body = json_decode($this->getRawBody(), true);
        return $body[self::EventTypeKey] ?? $routing_key;
    }

    /**
     * Get the decoded body of the job.
     *
     * Note the maxTries/backoff keys: Job::maxTries() and Job::backoff() read the
     * PAYLOAD, not the properties of the handler class this payload names. Without
     * them the worker falls back to the command's own options, and the entry point
     * runs `doctrine:queue:work sponsor_users_sync_consumer` with no flags - so the
     * defaults of --tries=1 and --backoff=0 apply and a single failure is final.
     *
     * @return array
     */
    public function payload(): array
    {
        $routing_key = $this->getEventType();

        switch ($routing_key) {
            case EventTypes::AUTH_USER_ADDED_TO_GROUP:
            case EventTypes::AUTH_USER_REMOVED_FROM_GROUP:
                $job = 'App\Jobs\SponsorServices\UpdateSponsorMemberGroupsMQJob@handle';
                break;
            case EventTypes::AUTH_USER_ADDED_TO_SPONSOR_AND_SUMMIT:
                $job = 'App\Jobs\SponsorServices\AddSponsorMemberMQJob@handle';
                break;
            case EventTypes::AUTH_USER_REMOVED_FROM_SPONSOR_AND_SUMMIT:
            case EventTypes::AUTH_USER_REMOVED_FROM_SUMMIT:
                $job = 'App\Jobs\SponsorServices\RemoveSponsorMemberMQJob@handle';
                break;
            default:
                Log::warning('Received an unknown routing key', ['routing_key' => $routing_key, 'message' => $this->getRawBody()]);
                return [];
        }
        return [
            'job' => $job,
            'data' => json_decode($this->getRawBody(), true),
            'maxTries' => $this->tries,
            'backoff' => self::RetryBackoff,
        ];
    }

    /**
     * Release the job back into the queue for a retry.
     *
     * The library implementation publishes into a delay queue whose dead-letter
     * exchange is the consumer exchange (sponsor-users-api-message-broker) and
     * whose dead-letter routing key is this queue's NAME. That exchange is
     * direct and only binds the five auth_user_* routing keys, so the expired
     * retry is unroutable and RabbitMQ silently drops it - the retry policy
     * would lose every failed event instead of retrying it.
     *
     * Dead-letter through the DEFAULT exchange instead: it routes by queue name
     * with no binding required. Redelivery rewrites the routing key to the queue
     * name, so the original event type is preserved in the body (EventTypeKey)
     * for getEventType() to recover.
     *
     * @param int $delay
     */
    public function release($delay = 0): void
    {
        $this->released = true;

        $ttl = $this->secondsUntil($delay) * 1000;
        if ($ttl <= 0) {
            // Never skip the delay queue: publishing straight to the consumer
            // exchange with the queue name as routing key is unroutable (above).
            $ttl = 1000;
        }
        $delay_queue = $this->queue . '.delay.' . $ttl;

        // Declare + publish DIRECTLY on the channel, bypassing laterRaw():
        //  - laterRaw() re-declares the delay queue with its own dead-letter
        //    arguments, which the broker rejects (PRECONDITION_FAILED,
        //    inequivalent args) once the queue exists with ours;
        //  - suppressing that re-declare by priming the declared-names cache
        //    would mean the queue is declared only once per worker process,
        //    while x-expires DELETES it when idle - every later release would
        //    then publish into a deleted queue and be dropped silently.
        // An unconditional queue_declare per release re-creates the queue when
        // it expired and is a no-op (equivalent args) when it did not.
        $channel = $this->rabbitmq->getChannel();

        $channel->queue_declare($delay_queue, false, true, false, false, false, new AMQPTable([
            'x-dead-letter-exchange'    => '',
            'x-dead-letter-routing-key' => $this->queue,
            'x-message-ttl'             => $ttl,
            'x-expires'                 => $ttl * 2,
        ]));

        // Preserve the original event type across the redelivery (idempotent:
        // a second release keeps the value written by the first one).
        $body = json_decode($this->getRawBody(), true) ?? [];
        $body[self::EventTypeKey] = $body[self::EventTypeKey] ?? $this->getEventType();

        $channel->basic_publish(
            new AMQPMessage(json_encode($body), [
                'content_type'        => 'application/json',
                'delivery_mode'       => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                'correlation_id'      => uniqid('', true),
                // attempts() reads this header on the next delivery.
                'application_headers' => new AMQPTable(['laravel' => ['attempts' => $this->attempts()]]),
            ]),
            '', // default exchange: routes by queue name, no binding required
            $delay_queue
        );

        // The retry was republished as a new message; ack the current one.
        $this->rabbitmq->ack($this);
    }
}
