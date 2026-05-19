<?php

namespace Tests\Unit\Audit;

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

use App\Audit\AbstractAuditLogFormatter;
use App\Audit\AuditContext;
use App\Audit\AuditLogOtlpStrategy;
use App\Audit\IAuditLogFormatterFactory;
use App\Audit\Interfaces\IAuditStrategy;
use App\Jobs\EmitAuditLogJob;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

final class AuditLogOtlpStrategyNoOpSuppressionTest extends TestCase
{
    public function testAuditSkipsDispatchWhenNoMeaningfulChanges(): void
    {
        Bus::fake();
        Config::set('opentelemetry.enabled', true);

        $formatterFactory = new class implements IAuditLogFormatterFactory {
            public function make(AuditContext $ctx, mixed $subject, string $event_type): ?\App\Audit\IAuditLogFormatter
            {
                return new class($event_type) extends AbstractAuditLogFormatter {
                    public function format(mixed $subject, array $change_set): ?string
                    {
                        return 'should-not-be-used';
                    }
                };
            }
        };

        $strategy = new AuditLogOtlpStrategy($formatterFactory);

        $dtUtc = new DateTimeImmutable('2026-01-02 03:04:05', new DateTimeZone('UTC'));
        $dtSameInstantOtherTz = new DateTimeImmutable('2026-01-02 00:04:05', new DateTimeZone('America/Argentina/Buenos_Aires'));

        $strategy->audit(
            subject: (object) ['id' => 1],
            change_set: ['submission_begin_date' => [$dtUtc, $dtSameInstantOtherTz]],
            event_type: IAuditStrategy::EVENT_ENTITY_UPDATE,
            ctx: new AuditContext()
        );

        Bus::assertNotDispatched(EmitAuditLogJob::class);
    }

    public function testAuditDispatchesWhenMeaningfulChangesExist(): void
    {
        Bus::fake();
        Config::set('opentelemetry.enabled', true);

        $formatterFactory = new class implements IAuditLogFormatterFactory {
            public function make(AuditContext $ctx, mixed $subject, string $event_type): ?\App\Audit\IAuditLogFormatter
            {
                return new class($event_type) extends AbstractAuditLogFormatter {
                    public function format(mixed $subject, array $change_set): ?string
                    {
                        return 'ok';
                    }
                };
            }
        };

        $strategy = new AuditLogOtlpStrategy($formatterFactory);

        $strategy->audit(
            subject: (object) ['id' => 1],
            change_set: ['name' => ['a', 'b']],
            event_type: IAuditStrategy::EVENT_ENTITY_UPDATE,
            ctx: new AuditContext()
        );

        Bus::assertDispatched(EmitAuditLogJob::class);
    }
}

