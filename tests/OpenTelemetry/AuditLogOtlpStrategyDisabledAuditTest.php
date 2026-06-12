<?php

namespace Tests\OpenTelemetry;

use App\Audit\AuditContext;
use App\Audit\AuditLogOtlpStrategy;
use App\Audit\IAuditLogFormatterFactory;
use Illuminate\Support\Facades\Queue;

class AuditLogOtlpStrategyDisabledAuditTest extends OpenTelemetryTestCase
{
    public function testAuditReturnsEarlyWhenSubjectAuditIsDisabled(): void
    {
        $factory = $this->createMock(IAuditLogFormatterFactory::class);
        $factory->expects($this->once())
            ->method('isAuditDisabled')
            ->willReturn(true);
        $factory->expects($this->never())
            ->method('make');

        $strategy = new AuditLogOtlpStrategy($factory);
        $enabledProperty = (new \ReflectionClass($strategy))->getProperty('enabled');
        $enabledProperty->setAccessible(true);
        $enabledProperty->setValue($strategy, true);

        Queue::fake();

        $strategy->audit(
            new \stdClass(),
            ['name' => ['old', 'new']],
            AuditLogOtlpStrategy::EVENT_ENTITY_UPDATE,
            new AuditContext()
        );

        Queue::assertNothingPushed();
    }
}
