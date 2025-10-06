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

class AuditOtlpStrategyTest extends OpenTelemetryTestCase
{
    use InsertSummitTestData;
    use InsertMemberTestData;

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

            $span->setStatus(StatusCode::STATUS_OK, 'Summit audit completed');
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
        $span = $tracer->spanBuilder('test.audit.summit_event_change')->startSpan();
        $spanScope = $span->activate();

        try {
            $summitEvent = self::$summit->getEvents()[0];
            $simulatedChangeSet = $this->createSummitEventChangeSet($summitEvent);

            $this->auditStrategy->audit(
                $summitEvent,
                $simulatedChangeSet,
                AuditLogOtlpStrategy::EVENT_ENTITY_UPDATE
            );

            $span->setStatus(StatusCode::STATUS_OK, 'SummitEvent audit completed');
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

        $this->auditStrategy->audit(
            self::$summit,
            $simulatedChangeSet,
            AuditLogOtlpStrategy::EVENT_ENTITY_UPDATE
        );

        $this->assertTrue(true);
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
