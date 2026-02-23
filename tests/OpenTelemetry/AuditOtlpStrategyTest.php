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
use App\Audit\AuditContext;
use App\Jobs\EmitAuditLogJob;
use App\Models\Foundation\Main\IGroup;
use Tests\InsertMemberTestData;
use Tests\InsertSummitTestData;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\API\Trace\TracerInterface;
use Illuminate\Support\Facades\Queue;

class AuditOtlpStrategyTest extends OpenTelemetryTestCase
{
    use InsertSummitTestData;
    use InsertMemberTestData;

    private const TEST_APP = 'test-app';
    private const TEST_FLOW = 'test-flow';
    private const TEST_ROUTE = 'api.summits.update';
    private const TEST_HTTP_METHOD = 'PUT';
    private const TEST_CLIENT_IP = '127.0.0.1';
    private const TEST_USER_AGENT = 'Test-Agent/1.0';
    private const SPAN_SUMMIT_CHANGE = 'test.audit.summit_change';
    private const SPAN_SUMMIT_EVENT_CHANGE = 'test.audit.summit_event_change';
    private const SPAN_NO_ACTIVE_SPAN = 'test.audit.no_span';
    private const SPAN_EMPTY_CHANGESET = 'test.audit.empty_changeset';
    private const SUFFIX_TEST = '[TEST]';
    private const SUFFIX_UPDATED = '[UPDATED]';
    private const SPAN_EVENT_STARTED = 'test.started';
    private const EVENT_COMPLETED = 'Summit audit completed';
    private const EVENT_CHANGE_COMPLETED = 'SummitEvent audit completed';
    private const EVENT_EMPTY_COMPLETED = 'Empty changeset audit completed';

    private AuditLogOtlpStrategy $auditStrategy;

    protected function setUp(): void
    {
        parent::setUp();

        self::insertMemberTestData(IGroup::TrackChairs);
        self::$defaultMember = self::$member;
        self::insertSummitTestData();

        $this->auditStrategy = $this->app->make(AuditLogOtlpStrategy::class);
    }

    protected function tearDown(): void
    {
        self::clearSummitTestData();
        self::clearMemberTestData();
        parent::tearDown();
    }

    public function testAuditSummitChangeWithOtlp(): void
    {
        $this->skipIfOpenTelemetryDisabled();

        $tracer = $this->app->make(TracerInterface::class);
        $span = $tracer->spanBuilder(self::SPAN_SUMMIT_CHANGE)->startSpan();
        $spanScope = $span->activate();

        try {
            $span->addEvent(self::SPAN_EVENT_STARTED, [
                'summit_id' => self::$summit->getId(),
                'summit_name' => self::$summit->getName()
            ]);

            $simulatedChangeSet = $this->createSummitChangeSet();
            $ctx = $this->createAuditContext();

            $this->auditStrategy->audit(
                self::$summit,
                $simulatedChangeSet,
                AuditLogOtlpStrategy::EVENT_ENTITY_UPDATE,
                $ctx
            );

            $span->setStatus(StatusCode::STATUS_OK, self::EVENT_COMPLETED);
            $this->assertTrue(true);

        } catch (\Exception $e) {
            $span->recordException($e);
            $span->setStatus(StatusCode::STATUS_ERROR, $e->getMessage());
            throw $e;
        } finally {
            $span->end();
            $spanScope->detach();
        }
    }

    public function testAuditSummitEventChangeWithOtlp(): void
    {
        $this->skipIfOpenTelemetryDisabled();

        $tracer = $this->app->make(TracerInterface::class);
        $span = $tracer->spanBuilder(self::SPAN_SUMMIT_EVENT_CHANGE)->startSpan();
        $spanScope = $span->activate();

        try {
            $summitEvent = self::$summit->getEvents()[0];
            $simulatedChangeSet = $this->createSummitEventChangeSet($summitEvent);
            $ctx = $this->createAuditContext();

            $this->auditStrategy->audit(
                $summitEvent,
                $simulatedChangeSet,
                AuditLogOtlpStrategy::EVENT_ENTITY_UPDATE,
                $ctx
            );

            $span->setStatus(StatusCode::STATUS_OK, self::EVENT_CHANGE_COMPLETED);
            $this->assertTrue(true);

        } catch (\Exception $e) {
            $span->recordException($e);
            $span->setStatus(StatusCode::STATUS_ERROR, $e->getMessage());
            throw $e;
        } finally {
            $span->end();
            $spanScope->detach();
        }
    }

    public function testAuditStrategyWithoutActiveSpan(): void
    {
        $this->skipIfOpenTelemetryDisabled();

        $simulatedChangeSet = ['name' => ['Old Name', 'New Name']];
        $ctx = $this->createAuditContext();

        $this->auditStrategy->audit(
            self::$summit,
            $simulatedChangeSet,
            AuditLogOtlpStrategy::EVENT_ENTITY_UPDATE,
            $ctx
        );

        $this->assertTrue(true);
    }

    public function testAuditStrategyWithEmptyChangeSet(): void
    {
        $this->skipIfOpenTelemetryDisabled();

        $tracer = $this->app->make(TracerInterface::class);
        $span = $tracer->spanBuilder(self::SPAN_EMPTY_CHANGESET)->startSpan();
        $spanScope = $span->activate();

        try {
            $ctx = $this->createAuditContext();
            $this->auditStrategy->audit(
                self::$summit,
                [],
                AuditLogOtlpStrategy::EVENT_ENTITY_UPDATE,
                $ctx
            );

            $span->setStatus(StatusCode::STATUS_OK, self::EVENT_EMPTY_COMPLETED);
            $this->assertTrue(true);

        } catch (\Exception $e) {
            $span->recordException($e);
            $span->setStatus(StatusCode::STATUS_ERROR, $e->getMessage());
            throw $e;
        } finally {
            $span->end();
            $spanScope->detach();
        }
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
            'name' => [self::$summit->getName(), self::$summit->getName() . self::SUFFIX_TEST],
            'description' => ['Original', 'Updated for test']
        ];
    }

    private function createSummitEventChangeSet(object $summitEvent): array
    {
        return [
            'title' => [$summitEvent->getTitle(), $summitEvent->getTitle() . self::SUFFIX_TEST]
        ];
    }

    private function isOpenTelemetryEnabled(): bool
    {
        return getenv('OTEL_SERVICE_ENABLED') === 'true';
    }

    private function createAuditContext(): AuditContext
    {
        return new AuditContext(
            userId: self::$member->getId(),
            userEmail: self::$member->getEmail(),
            userFirstName: self::$member->getFirstName(),
            userLastName: self::$member->getLastName(),
            uiApp: self::TEST_APP,
            uiFlow: self::TEST_FLOW,
            route: self::TEST_ROUTE,
            rawRoute: self::TEST_ROUTE,
            httpMethod: self::TEST_HTTP_METHOD,
            clientIp: self::TEST_CLIENT_IP,
            userAgent: self::TEST_USER_AGENT,
        );
    }

    
    public function testAuditSummitEntityPopulatesSummitIdCorrectly(): void
    {
        $this->skipIfOpenTelemetryDisabled();
        
        Queue::fake();

        $ctx = $this->createAuditContext();
        $simulatedChangeSet = [
            'name' => [self::$summit->getName(), self::$summit->getName() . self::SUFFIX_UPDATED]
        ];

        $this->auditStrategy->audit(
            self::$summit,
            $simulatedChangeSet,
            AuditLogOtlpStrategy::EVENT_ENTITY_UPDATE,
            $ctx
        );

        Queue::assertPushed(EmitAuditLogJob::class, function ($job) {
            $this->assertArrayHasKey('audit.summit_id', $job->auditData);
            $this->assertEquals((string)self::$summit->getId(), $job->auditData['audit.summit_id']);
            $this->assertEquals('Summit', $job->auditData['audit.entity']);
            return true;
        });
    }
}
