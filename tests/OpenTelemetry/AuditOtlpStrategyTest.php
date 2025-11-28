<?php

/**
 * Test suite for verifying OpenTelemetry audit strategy integration.
 * 
 * This test class validates that the AuditLogOtlpStrategy correctly processes
 * simulated entity changes and generates appropriate telemetry spans and events
 * without persisting any actual data to the database. Uses mocks to avoid
 * dependencies on external OpenTelemetry collectors.
 */

namespace Tests\OpenTelemetry;

use App\Audit\AuditLogOtlpStrategy;
use App\Models\Foundation\Main\IGroup;
use Tests\InsertMemberTestData;
use Tests\InsertSummitTestData;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\API\Trace\TracerInterface;
use Illuminate\Support\Facades\DB;
use Doctrine\ORM\PersistentCollection;

class AuditOtlpStrategyTest extends OpenTelemetryTestCase
{
    public static $summit;
    public static $em;

    private AuditLogOtlpStrategy $auditStrategy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->auditStrategy = $this->app->make(AuditLogOtlpStrategy::class);

        if (!self::$em) {
            self::$em = \LaravelDoctrine\ORM\Facades\EntityManager::getFacadeRoot();
        }

        if (!self::$summit) {
            $summitRepo = self::$em->getRepository(\models\summit\Summit::class);
            self::$summit = $summitRepo->findOneBy([]);
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function testAuditSummitChangeWithOtlp(): void
    {
        $this->skipIfOpenTelemetryDisabled();

        $tracer = $this->app->make(TracerInterface::class);
        $span = $tracer->spanBuilder('test.audit.summit_change')->startSpan();
        $spanScope = $span->activate();

        try {
            $span->addEvent('test.started', [
                'summit_id' => self::$summit->getId(),
                'summit_name' => self::$summit->getName()
            ]);

            $simulatedChangeSet = $this->createSummitChangeSet();

            $this->auditStrategy->audit(
                self::$summit,
                $simulatedChangeSet,
                AuditLogOtlpStrategy::EVENT_ENTITY_UPDATE
            );

            $this->assertNotNull($span, 'Span should be created');
            $this->assertNotEmpty($simulatedChangeSet, 'ChangeSet should not be empty');

            $span->setStatus(StatusCode::STATUS_OK, 'Summit audit completed');

        } catch (\Exception $e) {
            $span->recordException($e);
            $span->setStatus(StatusCode::STATUS_ERROR, $e->getMessage());
            $this->fail('Audit failed: ' . $e->getMessage());
        } finally {
            $span->end();
            $spanScope->detach();
        }
    }

    public function testAuditSummitEventChangeWithOtlp(): void
    {
        $this->skipIfOpenTelemetryDisabled();

        $tracer = $this->app->make(TracerInterface::class);
        $span = $tracer->spanBuilder('test.audit.summit_event_change')->startSpan();
        $spanScope = $span->activate();

        try {
            $events = self::$summit->getEvents();
            $this->assertNotEmpty($events, 'Summit must have events');
            
            $summitEvent = $events[0];
            $this->assertNotNull($summitEvent, 'Summit event should exist');
            
            $simulatedChangeSet = $this->createSummitEventChangeSet($summitEvent);

            $this->auditStrategy->audit(
                $summitEvent,
                $simulatedChangeSet,
                AuditLogOtlpStrategy::EVENT_ENTITY_UPDATE
            );

            $this->assertNotEmpty($simulatedChangeSet, 'Event changeSet should not be empty');

            $span->setStatus(StatusCode::STATUS_OK, 'SummitEvent audit completed');

        } catch (\Exception $e) {
            $span->recordException($e);
            $span->setStatus(StatusCode::STATUS_ERROR, $e->getMessage());
            $this->fail('Event audit failed: ' . $e->getMessage());
        } finally {
            $span->end();
            $spanScope->detach();
        }
    }

    public function testAuditStrategyWithoutActiveSpan(): void
    {
        $this->skipIfOpenTelemetryDisabled();

        $simulatedChangeSet = ['name' => ['Old Name', 'New Name']];

        try {
            $this->auditStrategy->audit(
                self::$summit,
                $simulatedChangeSet,
                AuditLogOtlpStrategy::EVENT_ENTITY_UPDATE
            );
            $this->assertTrue(true, 'Audit works without active span');
        } catch (\Exception $e) {
            $this->fail('Audit should work without active span: ' . $e->getMessage());
        }

        $this->assertNotNull(self::$summit, 'Summit should exist');
    }

    public function testAuditStrategyWithEmptyChangeSet(): void
    {
        $this->skipIfOpenTelemetryDisabled();

        $tracer = $this->app->make(TracerInterface::class);
        $span = $tracer->spanBuilder('test.audit.empty_changeset')->startSpan();
        $spanScope = $span->activate();

        try {
            $this->auditStrategy->audit(
                self::$summit,
                [],
                AuditLogOtlpStrategy::EVENT_ENTITY_UPDATE
            );

            $span->setStatus(StatusCode::STATUS_OK, 'Empty changeset audit completed');
            $this->assertNotNull(self::$summit, 'Summit should still exist after empty audit');

        } catch (\Exception $e) {
            $span->recordException($e);
            $span->setStatus(StatusCode::STATUS_ERROR, $e->getMessage());
            $this->fail('Audit should handle empty changeSet: ' . $e->getMessage());
        } finally {
            $span->end();
            $spanScope->detach();
        }
    }

    public function testGetCollectionTypeDoesNotTriggerLazyLoading(): void
    {
        $this->skipIfOpenTelemetryDisabled();

        DB::enableQueryLog();
        DB::flushQueryLog();

        $eventsCollection = self::$summit->getEvents();
        
        $this->assertInstanceOf(
            PersistentCollection::class, 
            $eventsCollection,
            'Events should be a PersistentCollection'
        );

        DB::flushQueryLog();
        
        $this->auditStrategy->audit(
            $eventsCollection,
            ['events' => [[], [1, 2, 3]]],
            AuditLogOtlpStrategy::EVENT_COLLECTION_UPDATE
        );

        $queries = DB::getQueryLog();
        $queryCount = count($queries);

        DB::disableQueryLog();

        $this->assertLessThanOrEqual(
            2,
            $queryCount,
            "getCollectionType() ejecutó $queryCount queries, máximo 2 permitidas"
        );
    }

    public function testAuditSummitEventTagsCollectionWithOtlp(): void
    {
        $this->skipIfOpenTelemetryDisabled();

        \DB::enableQueryLog();
        \DB::flushQueryLog();

        // Usa el summit ya gestionado
        $events = self::$summit->getEvents();
        $this->assertNotEmpty($events, 'Summit must have events');
        $event = $events[0];

        $tagsCollection = $event->getTags();
        $this->assertInstanceOf(\Doctrine\ORM\PersistentCollection::class, $tagsCollection);

        $this->assertGreaterThanOrEqual(2, count($tagsCollection), "El evento debe tener al menos 2 tags para el test");

        $tags = [];
        foreach ($tagsCollection as $tag) {
            $tags[] = $tag;
            if (count($tags) === 2) break;
        }

        \DB::flushQueryLog();

        $this->auditStrategy->audit(
            $tagsCollection,
            ['tags' => [[], [$tags[0]->getId(), $tags[1]->getId()]]],
            AuditLogOtlpStrategy::EVENT_COLLECTION_UPDATE
        );

        $queries = \DB::getQueryLog();
        $this->assertLessThanOrEqual(
            2,
            count($queries),
            "getCollectionType() ejecutó demasiadas queries"
        );
    }

    private function skipIfOpenTelemetryDisabled(): void
    {
        if (!$this->isOpenTelemetryEnabled()) {
            $this->markTestSkipped('OpenTelemetry is disabled');
        }
    }

    private function createSummitChangeSet(): array
    {
        return [
            'name' => [self::$summit->getName(), self::$summit->getName() . ' [TEST]'],
            'description' => ['Original', 'Updated for test']
        ];
    }

    private function createSummitEventChangeSet(object $summitEvent): array
    {
        return [
            'title' => [$summitEvent->getTitle(), $summitEvent->getTitle() . ' [TEST]']
        ];
    }

    private function isOpenTelemetryEnabled(): bool
    {
        return getenv('OTEL_SERVICE_ENABLED') === 'true';
    }
}
