<?php

namespace Tests\OpenTelemetry;

use App\Audit\AuditLogOtlpStrategy;
use App\Audit\AuditContext;
use OpenTelemetry\API\Trace\StatusCode;

class AuditEventTypesTest extends OpenTelemetryTestCase
{
    private const MOCK_ENTITY_ID = 999;
    private const MOCK_ENTITY_TITLE = 'Test Entity';
    private const NEW_ENTITY_TITLE = 'New Entity';
    private const TEST_TYPE = 'test';
    private const OLD_TITLE = 'Old Title';
    private const NEW_TITLE = 'New Title';
    private const DELETED_ID = 999;
    private const CLEANUP_REASON = 'Test cleanup';
    private const USER_ID = 999;
    private const USER_EMAIL = 'test@example.com';
    private const USER_FIRST_NAME = 'Test';
    private const USER_LAST_NAME = 'User';
    private const TEST_APP = 'test-app';
    private const TEST_FLOW = 'test-flow';
    private const TEST_ROUTE = 'api.test';
    private const TEST_HTTP_METHOD = 'POST';
    private const TEST_CLIENT_IP = '127.0.0.1';
    private const TEST_USER_AGENT = 'Test-Agent/1.0';
    private const SPAN_CREATION = 'test.audit.creation';
    private const SPAN_UPDATE = 'test.audit.update';
    private const SPAN_DELETION = 'test.audit.deletion';
    private const CREATION_COMPLETED = 'Creation audit completed';
    private const UPDATE_COMPLETED = 'Update audit completed';
    private const DELETION_COMPLETED = 'Deletion audit completed';

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
        $span = $tracer->spanBuilder(self::SPAN_CREATION)->startSpan();
        $spanScope = $span->activate();

        try {
            $mockEntity = (object) ['id' => self::MOCK_ENTITY_ID, 'title' => self::MOCK_ENTITY_TITLE];
            $data = ['title' => self::NEW_ENTITY_TITLE, 'type' => self::TEST_TYPE];
            $ctx = $this->createAuditContext();

            $this->auditStrategy->audit(
                $mockEntity,
                $data,
                AuditLogOtlpStrategy::EVENT_ENTITY_CREATION,
                $ctx
            );

            $span->setStatus(StatusCode::STATUS_OK, self::CREATION_COMPLETED);
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
        $span = $tracer->spanBuilder(self::SPAN_UPDATE)->startSpan();
        $spanScope = $span->activate();

        try {
            $mockEntity = (object) ['id' => self::MOCK_ENTITY_ID, 'title' => self::MOCK_ENTITY_TITLE];
            $data = ['title' => [self::OLD_TITLE, self::NEW_TITLE]];
            $ctx = $this->createAuditContext();

            $this->auditStrategy->audit(
                $mockEntity,
                $data,
                AuditLogOtlpStrategy::EVENT_ENTITY_UPDATE,
                $ctx
            );

            $span->setStatus(StatusCode::STATUS_OK, self::UPDATE_COMPLETED);
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
        $span = $tracer->spanBuilder(self::SPAN_DELETION)->startSpan();
        $spanScope = $span->activate();

        try {
            $mockEntity = (object) ['id' => self::MOCK_ENTITY_ID, 'title' => self::MOCK_ENTITY_TITLE];
            $data = ['deleted_id' => self::DELETED_ID, 'reason' => self::CLEANUP_REASON];
            $ctx = $this->createAuditContext();

            $this->auditStrategy->audit(
                $mockEntity,
                $data,
                AuditLogOtlpStrategy::EVENT_ENTITY_DELETION,
                $ctx
            );

            $span->setStatus(StatusCode::STATUS_OK, self::DELETION_COMPLETED);
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

    private function createAuditContext(): AuditContext
    {
        return new AuditContext(
            userId: self::USER_ID,
            userEmail: self::USER_EMAIL,
            userFirstName: self::USER_FIRST_NAME,
            userLastName: self::USER_LAST_NAME,
            uiApp: self::TEST_APP,
            uiFlow: self::TEST_FLOW,
            route: self::TEST_ROUTE,
            rawRoute: self::TEST_ROUTE,
            httpMethod: self::TEST_HTTP_METHOD,
            clientIp: self::TEST_CLIENT_IP,
            userAgent: self::TEST_USER_AGENT,
        );
    }
}
