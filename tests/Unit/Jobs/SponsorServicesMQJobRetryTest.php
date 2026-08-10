<?php namespace Tests\Unit\Jobs;
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

use App\Jobs\SponsorServices\EventTypes;
use App\Jobs\SponsorServices\SponsorServicesMQJob;
use Mockery;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;
use VladimirYuldashev\LaravelQueueRabbitMQ\Queue\RabbitMQQueue;

/**
 * The queue worker reads the retry policy from Job::maxTries() and
 * Job::backoff(), and BOTH read the job's PAYLOAD - not the properties of the
 * handler class the payload names. The `public int $tries` declared on
 * AddSponsorMemberMQJob and friends is therefore invisible to the worker.
 *
 * With nothing in the payload the worker falls back to the command options, and
 * the container entry point runs `doctrine:queue:work sponsor_users_sync_consumer`
 * with no flags - so --tries=1 / --backoff=0 apply and one failure is terminal.
 *
 * These tests pin the payload keys that make the retry policy real.
 */
final class SponsorServicesMQJobRetryTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function jobForRoutingKey(string $routing_key, array $extra_body = []): SponsorServicesMQJob
    {
        $message = new AMQPMessage('');
        $message->setDeliveryInfo(1, false, 'sponsor_users', $routing_key);

        $job = Mockery::mock(SponsorServicesMQJob::class)->makePartial();
        $job->shouldReceive('getRabbitMQMessage')->andReturn($message);
        $job->shouldReceive('getRawBody')->andReturn(json_encode(array_merge([
            'data' => [
                'user_external_id' => 1,
                'sponsor_id'       => 2,
                'summit_id'        => 3,
                'group_slug'       => 'sponsors',
            ],
        ], $extra_body)));

        return $job;
    }

    public static function routingKeyProvider(): array
    {
        return [
            'added to sponsor and summit' => [EventTypes::AUTH_USER_ADDED_TO_SPONSOR_AND_SUMMIT],
            'added to group'              => [EventTypes::AUTH_USER_ADDED_TO_GROUP],
            'removed from group'          => [EventTypes::AUTH_USER_REMOVED_FROM_GROUP],
            'removed from summit'         => [EventTypes::AUTH_USER_REMOVED_FROM_SUMMIT],
        ];
    }

    #[DataProvider('routingKeyProvider')]
    public function testWorkerSeesMoreThanASingleAttempt(string $routing_key): void
    {
        $this->assertSame(3, $this->jobForRoutingKey($routing_key)->maxTries());
    }

    #[DataProvider('routingKeyProvider')]
    public function testWorkerSeesAnIncreasingBackoff(string $routing_key): void
    {
        $backoff = $this->jobForRoutingKey($routing_key)->backoff();

        // Worker::calculateBackoff() explodes this on commas and indexes it by attempt.
        $delays = array_map('intval', explode(',', (string)$backoff));

        $this->assertCount(2, $delays, 'one delay per retry after the first attempt');
        $this->assertGreaterThan(0, $delays[0], 'the first retry must not be immediate');
        $this->assertGreaterThan($delays[0], $delays[1], 'the backoff must grow');
    }

    /**
     * An unknown routing key returns an empty payload and must not claim a retry
     * policy - there is no handler to retry.
     */
    public function testUnknownRoutingKeyCarriesNoRetryPolicy(): void
    {
        $job = $this->jobForRoutingKey('some_unknown_routing_key');

        $this->assertNull($job->maxTries());
        $this->assertNull($job->backoff());
    }

    // -------------------------------------------------------------------------
    // Redelivery after release()
    // -------------------------------------------------------------------------

    public static function redeliveredEventProvider(): array
    {
        return [
            'added to sponsor and summit'   => [EventTypes::AUTH_USER_ADDED_TO_SPONSOR_AND_SUMMIT, 'App\Jobs\SponsorServices\AddSponsorMemberMQJob@handle'],
            'added to group'                => [EventTypes::AUTH_USER_ADDED_TO_GROUP, 'App\Jobs\SponsorServices\UpdateSponsorMemberGroupsMQJob@handle'],
            'removed from group'            => [EventTypes::AUTH_USER_REMOVED_FROM_GROUP, 'App\Jobs\SponsorServices\UpdateSponsorMemberGroupsMQJob@handle'],
            'removed from summit'           => [EventTypes::AUTH_USER_REMOVED_FROM_SUMMIT, 'App\Jobs\SponsorServices\RemoveSponsorMemberMQJob@handle'],
            'removed from sponsor + summit' => [EventTypes::AUTH_USER_REMOVED_FROM_SPONSOR_AND_SUMMIT, 'App\Jobs\SponsorServices\RemoveSponsorMemberMQJob@handle'],
        ];
    }

    /**
     * A released message is dead-lettered back through the DEFAULT exchange, so
     * it is redelivered with the QUEUE NAME as its routing key - the original
     * event type only survives inside the body (release() puts it there). The
     * job must still resolve the right handler and keep its retry policy.
     */
    #[DataProvider('redeliveredEventProvider')]
    public function testRedeliveredMessageResolvesOriginalEventType(string $event_type, string $expected_handler): void
    {
        $job = $this->jobForRoutingKey(
            'sponsor-users-api-summit-api-badge-scans-queue', // != any EventTypes constant
            [SponsorServicesMQJob::EventTypeKey => $event_type]
        );

        $this->assertSame($event_type, $job->getEventType());
        $this->assertSame($expected_handler, $job->payload()['job'] ?? null);
        $this->assertSame(3, $job->maxTries());
        $this->assertNotNull($job->backoff());
    }

    /**
     * release() must NOT use the library's delay-queue arguments: those
     * dead-letter into the consumer exchange (direct, only the five auth_user_*
     * bindings) with the queue name as routing key - unroutable, silently
     * dropped. It must dead-letter through the DEFAULT exchange (routes by
     * queue name, no binding needed) and preserve the original event type in
     * the republished body, because redelivery overwrites the routing key.
     */
    public function testReleaseDeadLettersBackThroughTheDefaultExchange(): void
    {
        $queue_name = 'sponsor-users-api-summit-api-badge-scans-queue';
        $original_body = ['user_external_id' => 1, 'sponsor_id' => 2, 'summit_id' => 3];

        $message = new AMQPMessage(json_encode($original_body));
        $message->setDeliveryInfo(1, false, 'sponsor_users', EventTypes::AUTH_USER_ADDED_TO_SPONSOR_AND_SUMMIT);

        $rabbitmq = Mockery::mock(RabbitMQQueue::class);

        $rabbitmq->shouldReceive('declareQueue')->once()->with(
            $queue_name . '.delay.30000',
            true,
            false,
            [
                'x-dead-letter-exchange'    => '',
                'x-dead-letter-routing-key' => $queue_name,
                'x-message-ttl'             => 30000,
                'x-expires'                 => 60000,
            ]
        );

        $republished = null;
        $rabbitmq->shouldReceive('laterRaw')->once()->with(
            30,
            Mockery::on(function ($payload) use (&$republished) {
                $republished = $payload;
                return is_string($payload);
            }),
            $queue_name,
            1 // first attempt
        );

        $rabbitmq->shouldReceive('ack')->once();

        $job = new SponsorServicesMQJob(app(), $rabbitmq, $message, 'rabbitmq', $queue_name);
        $job->release(30);

        $this->assertTrue($job->isReleased());

        $body = json_decode($republished, true);
        $this->assertSame(
            EventTypes::AUTH_USER_ADDED_TO_SPONSOR_AND_SUMMIT,
            $body[SponsorServicesMQJob::EventTypeKey] ?? null,
            'the original event type must survive redelivery in the body'
        );
        foreach ($original_body as $key => $value) {
            $this->assertSame($value, $body[$key] ?? null, "original payload key {$key} must be preserved");
        }
    }

    /**
     * A second release (the message already carries the event type from the
     * first one) must keep the ORIGINAL event type, not overwrite it with the
     * redelivered routing key (the queue name).
     */
    public function testReleaseOfARedeliveredMessageKeepsTheOriginalEventType(): void
    {
        $queue_name = 'sponsor-users-api-summit-api-badge-scans-queue';

        // As redelivered: routing key already the queue name, event type in body.
        $message = new AMQPMessage(json_encode([
            'user_external_id' => 1,
            SponsorServicesMQJob::EventTypeKey => EventTypes::AUTH_USER_ADDED_TO_GROUP,
        ]));
        $message->setDeliveryInfo(1, false, 'sponsor_users', $queue_name);

        $rabbitmq = Mockery::mock(RabbitMQQueue::class);
        $rabbitmq->shouldReceive('declareQueue')->once();

        $republished = null;
        $rabbitmq->shouldReceive('laterRaw')->once()->with(
            120,
            Mockery::on(function ($payload) use (&$republished) {
                $republished = $payload;
                return is_string($payload);
            }),
            $queue_name,
            1
        );
        $rabbitmq->shouldReceive('ack')->once();

        $job = new SponsorServicesMQJob(app(), $rabbitmq, $message, 'rabbitmq', $queue_name);
        $job->release(120);

        $body = json_decode($republished, true);
        $this->assertSame(EventTypes::AUTH_USER_ADDED_TO_GROUP, $body[SponsorServicesMQJob::EventTypeKey] ?? null);
    }
}
