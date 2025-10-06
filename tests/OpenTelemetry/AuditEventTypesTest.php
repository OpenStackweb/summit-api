<?php

namespace Tests\OpenTelemetry;

use App\Audit\AuditLogOtlpStrategy;
use OpenTelemetry\API\Trace\StatusCode;

class AuditEventTypesTest extends OpenTelemetryTestCase
{
    private AuditLogOtlpStrategy $auditStrategy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->auditStrategy = $this->app->make(AuditLogOtlpStrategy::class);
    }

    public function testEntityCreationAudit(): void
    {
        if (!$this->isOpenTelemetryEnabled()) {
            $this->markTestSkipped('OpenTelemetry is disabled');
        }

        $tracer = $this->app->make(\OpenTelemetry\API\Trace\TracerInterface::class);
        $span = $tracer->spanBuilder('test.audit.creation')->startSpan();
        $spanScope = $span->activate();

        try {
            $mockEntity = (object) ['id' => 999, 'title' => 'Test Entity'];
            $data = ['title' => 'New Entity', 'type' => 'test'];

            $this->auditStrategy->audit(
                $mockEntity,
                $data,
                AuditLogOtlpStrategy::EVENT_ENTITY_CREATION
            );

            $span->setStatus(StatusCode::STATUS_OK, 'Creation audit completed');
            $this->assertTrue(true);
        } catch (\Exception $e) {
            $span->recordException($e);
            throw $e;
        } finally {
            $span->end();
            $spanScope->detach();
        }
    }

    public function testEntityUpdateAudit(): void
    {
        if (!$this->isOpenTelemetryEnabled()) {
            $this->markTestSkipped('OpenTelemetry is disabled');
        }

        $tracer = $this->app->make(\OpenTelemetry\API\Trace\TracerInterface::class);
        $span = $tracer->spanBuilder('test.audit.update')->startSpan();
        $spanScope = $span->activate();

        try {
            $mockEntity = (object) ['id' => 999, 'title' => 'Test Entity'];
            $data = ['title' => ['Old Title', 'New Title']];

            $this->auditStrategy->audit(
                $mockEntity,
                $data,
                AuditLogOtlpStrategy::EVENT_ENTITY_UPDATE
            );

            $span->setStatus(StatusCode::STATUS_OK, 'Update audit completed');
            $this->assertTrue(true);
        } catch (\Exception $e) {
            $span->recordException($e);
            throw $e;
        } finally {
            $span->end();
            $spanScope->detach();
        }
    }

    public function testEntityDeletionAudit(): void
    {
        if (!$this->isOpenTelemetryEnabled()) {
            $this->markTestSkipped('OpenTelemetry is disabled');
        }

        $tracer = $this->app->make(\OpenTelemetry\API\Trace\TracerInterface::class);
        $span = $tracer->spanBuilder('test.audit.deletion')->startSpan();
        $spanScope = $span->activate();

        try {
            $mockEntity = (object) ['id' => 999, 'title' => 'Test Entity'];
            $data = ['deleted_id' => 999, 'reason' => 'Test cleanup'];

            $this->auditStrategy->audit(
                $mockEntity,
                $data,
                AuditLogOtlpStrategy::EVENT_ENTITY_DELETION
            );

            $span->setStatus(StatusCode::STATUS_OK, 'Deletion audit completed');
            $this->assertTrue(true);
        } catch (\Exception $e) {
            $span->recordException($e);
            throw $e;
        } finally {
            $span->end();
            $spanScope->detach();
        }
    }

    /**
     * Check if OpenTelemetry is enabled
     */
    private function isOpenTelemetryEnabled(): bool
    {
        return getenv('OTEL_SERVICE_ENABLED') === 'true';
    }
}
