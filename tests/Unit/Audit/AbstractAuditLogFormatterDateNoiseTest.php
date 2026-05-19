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
use App\Audit\Interfaces\IAuditStrategy;
use DateTime;
use DateTimeZone;
use Tests\TestCase;

final class AbstractAuditLogFormatterDateNoiseTest extends TestCase
{
    public function testBuildChangeDetailsIgnoresDateTimeSameSecondNoise(): void
    {
        $formatter = new ExposingAuditFormatter(IAuditStrategy::EVENT_ENTITY_UPDATE);

        $oldUtc = new DateTime('2026-01-02 03:04:05', new DateTimeZone('UTC'));
        $newLocalSameSecond = new DateTime('2026-01-02 00:04:05', new DateTimeZone('America/Argentina/Buenos_Aires'));

        $details = $formatter->publicBuildChangeDetails([
            'submission_begin_date' => [$oldUtc, $newLocalSameSecond],
        ]);

        $this->assertSame('properties without changes registered', $details);
        $this->assertFalse($formatter->hasMeaningfulChanges([
            'submission_begin_date' => [$oldUtc, $newLocalSameSecond],
        ]));
    }

    public function testBuildChangeDetailsIgnoresMultipleDateFieldsWithSameInstant(): void
    {
        $formatter = new ExposingAuditFormatter(IAuditStrategy::EVENT_ENTITY_UPDATE);

        $old = new DateTime('2025-07-02 07:00:00', new DateTimeZone('UTC'));
        $newSame = new DateTime('2025-07-02 07:00:00', new DateTimeZone('UTC'));

        $details = $formatter->publicBuildChangeDetails([
            'submission_begin_date' => [$old, $newSame],
            'submission_end_date' => [$old, $newSame],
            'selection_begin_date' => [$old, $newSame],
            'selection_end_date' => [$old, $newSame],
        ]);

        $this->assertSame('properties without changes registered', $details);
    }

    public function testBuildChangeDetailsStillShowsRealDateTimeChange(): void
    {
        $formatter = new ExposingAuditFormatter(IAuditStrategy::EVENT_ENTITY_UPDATE);

        $old = new DateTime('2026-01-02 03:04:05', new DateTimeZone('UTC'));
        $new = new DateTime('2026-01-02 03:04:06', new DateTimeZone('UTC'));

        $details = $formatter->publicBuildChangeDetails([
            'submission_begin_date' => [$old, $new],
        ]);

        $this->assertStringContainsString('submission_begin_date', $details);
        $this->assertStringContainsString('03:04:05', $details);
        $this->assertStringContainsString('03:04:06', $details);
        $this->assertTrue($formatter->hasMeaningfulChanges([
            'submission_begin_date' => [$old, $new],
        ]));
    }

    public function testBuildChangeDetailsKeepsOtherNonDateChanges(): void
    {
        $formatter = new ExposingAuditFormatter(IAuditStrategy::EVENT_ENTITY_UPDATE);

        $oldUtc = new DateTime('2026-01-02 03:04:05', new DateTimeZone('UTC'));
        $newLocalSameSecond = new DateTime('2026-01-02 00:04:05', new DateTimeZone('America/Argentina/Buenos_Aires'));

        $details = $formatter->publicBuildChangeDetails([
            'submission_begin_date' => [$oldUtc, $newLocalSameSecond],
            'name' => ['Old', 'New'],
        ]);

        $this->assertStringContainsString('1 field(s) modified', $details);
        $this->assertStringContainsString('Property "name" has changed', $details);
        $this->assertStringNotContainsString('submission_begin_date', $details);
    }
}

final class ExposingAuditFormatter extends AbstractAuditLogFormatter
{
    public function publicBuildChangeDetails(array $change_set): string
    {
        return $this->buildChangeDetails($change_set);
    }

    public function format(mixed $subject, array $change_set): ?string
    {
        return null;
    }
}
