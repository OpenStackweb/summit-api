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
use Illuminate\Support\Facades\Queue;
use PhpAmqpLib\Message\AMQPMessage;
use Tests\TestCase;
use VladimirYuldashev\LaravelQueueRabbitMQ\Queue\RabbitMQQueue;

/**
 * Live-broker proof that a released job actually comes back.
 *
 * The unit tests in SponsorServicesMQJobRetryTest mock RabbitMQQueue, so they
 * cannot see broker-side failures: a delay queue re-declared with different
 * arguments is a PRECONDITION_FAILED channel error, and a dead-letter routing
 * key with no matching binding drops the message silently. This test runs
 * release() against the real broker and asserts the message is REDELIVERED,
 * with its original event type and incremented attempt count.
 *
 * Skips itself when no broker is reachable (e.g. a CI job without the
 * rabbitmq service container).
 */
final class SponsorServicesMQJobReleaseIntegrationTest extends TestCase
{
    private ?RabbitMQQueue $queue = null;

    private string $queue_name = '';

    private string $exchange_name = '';

    protected function setUp(): void
    {
        parent::setUp();

        try {
            $queue = Queue::connection('sponsor_users_sync_consumer');
            $channel = $queue->getChannel(); // force the lazy connection open
        } catch (\Throwable $ex) {
            $this->markTestSkipped("RabbitMQ broker not reachable: {$ex->getMessage()}");
        }

        $this->queue = $queue;

        $suffix = uniqid();
        $this->queue_name = "test-sponsor-users-release-{$suffix}";
        $this->exchange_name = "test-sponsor-users-ex-{$suffix}";

        // A direct exchange bound only by the event-type routing key, mirroring
        // the production topology (sponsor-users-api-message-broker).
        $channel->exchange_declare($this->exchange_name, 'direct', false, false, true);
        $channel->queue_declare($this->queue_name, false, true, false, false);
        $channel->queue_bind($this->queue_name, $this->exchange_name, EventTypes::AUTH_USER_ADDED_TO_GROUP);
    }

    protected function tearDown(): void
    {
        if (!is_null($this->queue)) {
            foreach ([$this->queue_name, $this->queue_name . '.delay.1000'] as $queue) {
                try {
                    $this->queue->getChannel(true)->queue_delete($queue);
                } catch (\Throwable $ex) {
                    // already gone (x-expires) or never created - nothing to clean
                }
            }
            try {
                $this->queue->getChannel(true)->exchange_delete($this->exchange_name);
            } catch (\Throwable $ex) {
            }
        }
        parent::tearDown();
    }

    public function testReleasedJobIsRedeliveredWithItsOriginalEventType(): void
    {
        $body = [
            'user_external_id' => 1,
            'sponsor_id'       => 2,
            'summit_id'        => 3,
            'group_slug'       => 'sponsors',
        ];

        $this->queue->getChannel()->basic_publish(
            new AMQPMessage(json_encode($body), [
                'content_type'  => 'application/json',
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
            ]),
            $this->exchange_name,
            EventTypes::AUTH_USER_ADDED_TO_GROUP
        );

        // First delivery: the routing key IS the event type.
        $job = $this->popWithinSeconds(5);
        $this->assertInstanceOf(SponsorServicesMQJob::class, $job);
        $this->assertSame(EventTypes::AUTH_USER_ADDED_TO_GROUP, $job->getEventType());
        $this->assertSame(1, $job->attempts());

        // The real broker enforces queue-argument equivalence and binding
        // resolution - the two failure modes the mocked unit tests cannot see.
        $job->release(1);

        $redelivered = $this->popWithinSeconds(10);
        $this->assertNotNull($redelivered, 'the released message must be redelivered after the delay');
        $this->assertSame(
            EventTypes::AUTH_USER_ADDED_TO_GROUP,
            $redelivered->getEventType(),
            'the original event type must survive redelivery'
        );
        $this->assertSame(2, $redelivered->attempts(), 'the attempt count must carry over');
        $this->assertSame(3, $redelivered->maxTries());

        $data = $redelivered->payload()['data'];
        foreach ($body as $key => $value) {
            $this->assertSame($value, $data[$key] ?? null, "original payload key {$key} must be preserved");
        }

        $redelivered->delete(); // ack, leave the queue clean
    }

    /**
     * The delay queue carries x-expires, so the broker DELETES it once idle.
     * A later release in the same long-lived worker process must re-create it
     * - an implementation that only declares once per process (e.g. by caching
     * the declared name) publishes the retry into a deleted queue and the
     * default exchange drops it silently.
     */
    public function testASecondReleaseAfterTheDelayQueueExpiredStillRedelivers(): void
    {
        $this->queue->getChannel()->basic_publish(
            new AMQPMessage(json_encode(['user_external_id' => 1]), [
                'content_type'  => 'application/json',
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
            ]),
            $this->exchange_name,
            EventTypes::AUTH_USER_ADDED_TO_GROUP
        );

        $job = $this->popWithinSeconds(5);
        $this->assertNotNull($job);
        $job->release(1); // creates <queue>.delay.1000 (x-expires 2000)

        $redelivered = $this->popWithinSeconds(10);
        $this->assertNotNull($redelivered, 'first retry must be redelivered');

        // Let the (now idle) delay queue hit its x-expires and be deleted.
        sleep(3);

        $redelivered->release(1);

        $third = $this->popWithinSeconds(10);
        $this->assertNotNull($third, 'a release after the delay queue expired must re-create it and still redeliver');
        $this->assertSame(EventTypes::AUTH_USER_ADDED_TO_GROUP, $third->getEventType());
        $this->assertSame(3, $third->attempts());

        $third->delete();
    }

    private function popWithinSeconds(int $max_seconds): ?SponsorServicesMQJob
    {
        $deadline = microtime(true) + $max_seconds;
        do {
            $job = $this->queue->pop($this->queue_name);
            if (!is_null($job)) {
                return $job;
            }
            usleep(200000);
        } while (microtime(true) < $deadline);

        return null;
    }
}
