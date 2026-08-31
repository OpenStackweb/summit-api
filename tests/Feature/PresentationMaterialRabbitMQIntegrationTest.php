<?php namespace Tests\Feature;
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

use App\Events\ScheduleEntityLifeCycleEvent;
use App\Jobs\ProcessScheduleEntityLifeCycleEvent;
use App\Services\Model\IProcessScheduleEntityLifeCycleEventService;
use Illuminate\Support\Facades\Config;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use Tests\InsertSummitTestData;
use Tests\TestCase;

/**
 * Class PresentationMaterialRabbitMQIntegrationTest
 *
 * Full end-to-end coverage of the trace requested by the ticket:
 * ScheduleEntityLifeCycleEvent -> EventServiceProvider listener -> job payload
 * (already covered by PresentationMaterialEventDispatchTest via Queue::fake())
 * -> ProcessScheduleEntityLifeCycleEvent::handle() -> ProcessScheduleEntityLifeCycleEventService::process()
 * -> RabbitPublisherService::publish() -> real message on the entities-updates-broker
 * exchange.
 *
 * Runs the job for real (no Queue::fake()) against the actual RabbitMQ broker
 * from docker-compose (rabbitmq_sponsor_services, reachable on the
 * summit-api-local-net network the app container is also on) and asserts the
 * published AMQP payload's summit_id is non-zero for a PresentationMediaUpload.
 *
 * @package Tests\Feature
 */
class PresentationMaterialRabbitMQIntegrationTest extends TestCase
{
    use InsertSummitTestData;

    const EXCHANGE = 'entities-updates-broker';

    private ?AMQPStreamConnection $consumer_connection = null;

    private ?AMQPChannel $consumer_channel = null;

    private ?string $consumer_queue = null;

    /**
     * @var array<string, mixed>
     */
    private array $original_rabbitmq_config = [];

    protected function setUp(): void
    {
        parent::setUp();
        self::insertSummitTestData();

        $this->original_rabbitmq_config = Config::get('rabbitmq');

        // The default rabbitmq.* config (RABBITMQ_HOST=host.docker.internal:5672)
        // has nothing listening in this environment. Point it at the broker this
        // container can actually reach, reusing the same credentials docker-compose
        // already configures (as plain env vars, not literals) for rabbitmq_sponsor_services.
        Config::set('rabbitmq.host', env('DOMAIN_EVENTS_RABBITMQ_HOST', 'rabbitmq_sponsor_services'));
        Config::set('rabbitmq.port', 5672);
        Config::set('rabbitmq.user', env('DOMAIN_EVENTS_RABBITMQ_LOGIN', env('RABBITMQ_LOGIN', 'guest')));
        Config::set('rabbitmq.password', env('DOMAIN_EVENTS_RABBITMQ_PASSWORD', env('RABBITMQ_PASSWORD', 'guest')));
        Config::set('rabbitmq.vhost', env('DOMAIN_EVENTS_RABBITMQ_VHOST', '/'));
        // Force IProcessScheduleEntityLifeCycleEventService (a singleton) to
        // rebuild its internal RabbitPublisherService against the config above,
        // in case anything already resolved it with the default (unreachable) host.
        app()->forgetInstance(IProcessScheduleEntityLifeCycleEventService::class);

        $this->consumer_connection = new AMQPStreamConnection(
            config('rabbitmq.host'),
            config('rabbitmq.port'),
            config('rabbitmq.user'),
            config('rabbitmq.password'),
            config('rabbitmq.vhost'),
        );
        $this->consumer_channel = $this->consumer_connection->channel();
        // Must match the type/durable/auto_delete the real publisher declares with
        // (RabbitPublisherService defaults: fanout, durable=true, auto_delete=false)
        // or RabbitMQ rejects the redeclaration.
        $this->consumer_channel->exchange_declare(self::EXCHANGE, AMQPExchangeType::FANOUT, false, true, false);
        [$this->consumer_queue] = $this->consumer_channel->queue_declare('', false, false, true, true);
        $this->consumer_channel->queue_bind($this->consumer_queue, self::EXCHANGE);
    }

    public function tearDown(): void
    {
        try {
            $this->consumer_channel?->close();
            $this->consumer_connection?->close();
        } catch (\Throwable $ex) {
            // best-effort cleanup
        }
        // Undo the setUp() overrides so later tests in this process see the
        // original rabbitmq config and a fresh service singleton, not this
        // test's real-broker configuration.
        Config::set('rabbitmq', $this->original_rabbitmq_config);
        app()->forgetInstance(IProcessScheduleEntityLifeCycleEventService::class);
        self::clearSummitTestData();
        parent::tearDown();
    }

    private function drainQueue(): void
    {
        while ($this->consumer_channel->basic_get($this->consumer_queue, true)) {
            // discard any message left over from a previous run
        }
    }

    public function testMediaUploadUpdateEndToEndPublishesNonZeroSummitId(): void
    {
        $media_upload = null;
        foreach (self::$presentations as $presentation) {
            $candidate = $presentation->getMediaUploads()->first();
            if ($candidate !== false) {
                $media_upload = $candidate;
                break;
            }
        }
        $this->assertNotNull($media_upload, 'Pre-condition: fixtures must include a media upload');

        // Compute summit_id exactly the way ScheduleEntity's PostPersist/PostUpdate/
        // PreRemove hooks do (private _getSummitId(), resolved via reflection),
        // instead of assuming PresentationMaterial::getSummitId() exists - so this
        // test also fails meaningfully pre-fix (summit_id resolves to 0) rather
        // than erroring on a missing method.
        $rc = new \ReflectionClass($media_upload);
        $get_summit_id = $rc->getMethod('_getSummitId');
        $get_summit_id->setAccessible(true);
        $summit_id = $get_summit_id->invoke($media_upload);
        $entity_id = $media_upload->getId();
        $this->assertGreaterThan(0, $summit_id, 'Pre-condition: presentation must belong to a summit, and _getSummitId() must resolve it (this is the exact bug being fixed)');

        $this->drainQueue();

        // Run the real job/service/publisher chain, exactly as the
        // EventServiceProvider listener would when the queue worker picks it up.
        $job = new ProcessScheduleEntityLifeCycleEvent(
            ScheduleEntityLifeCycleEvent::Operation_Update,
            $summit_id,
            $entity_id,
            'PresentationMediaUpload'
        );
        $job->handle(app(IProcessScheduleEntityLifeCycleEventService::class));

        // The queue is bound to the whole fanout exchange, so it can receive
        // unrelated messages from other activity on the same broker. Skip past
        // anything that isn't this update before asserting on it.
        $payload = null;
        for ($i = 0; $i < 30 && is_null($payload); $i++) {
            $message = $this->consumer_channel->basic_get($this->consumer_queue, true);
            if (is_null($message)) {
                usleep(100000);
                continue;
            }
            $candidate = json_decode($message->getBody(), true);
            if (!is_array($candidate)) {
                continue;
            }
            if (($candidate['entity_type'] ?? null) === 'PresentationMediaUpload'
                && ($candidate['entity_id'] ?? null) === $entity_id) {
                $payload = $candidate;
            }
        }

        $this->assertNotNull($payload, 'Expected a PresentationMediaUpload message for this entity on the entities-updates-broker exchange');
        $this->assertSame($summit_id, $payload['summit_id'], 'Published summit_id must be the real summit id, not 0');
    }
}
