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
use DateTimeImmutable;
use DateTimeZone;
use Tests\TestCase;

final class AbstractAuditLogFormatterHasMeaningfulChangesTest extends TestCase
{
    /**
     * @dataProvider provideNoOpChangeSets
     */
    public function testHasMeaningfulChangesReturnsFalseForNoOps(array $changeSet): void
    {
        $formatter = new ExposingMeaningfulAuditFormatter(IAuditStrategy::EVENT_ENTITY_UPDATE);
        $this->assertFalse($formatter->hasMeaningfulChanges($changeSet));
        $this->assertSame(AbstractAuditLogFormatter::NO_CHANGES_REGISTERED_MESSAGE, $formatter->publicBuildChangeDetails($changeSet));
    }

    /**
     * @dataProvider provideMeaningfulChangeSets
     */
    public function testHasMeaningfulChangesReturnsTrueForRealChanges(array $changeSet): void
    {
        $formatter = new ExposingMeaningfulAuditFormatter(IAuditStrategy::EVENT_ENTITY_UPDATE);
        $this->assertTrue($formatter->hasMeaningfulChanges($changeSet));
        $this->assertNotSame(AbstractAuditLogFormatter::NO_CHANGES_REGISTERED_MESSAGE, $formatter->publicBuildChangeDetails($changeSet));
    }

    public static function provideNoOpChangeSets(): array
    {
        $dtUtc = new DateTimeImmutable('2026-01-02 03:04:05', new DateTimeZone('UTC'));
        $dtSameInstantOtherTz = new DateTimeImmutable('2026-01-02 00:04:05', new DateTimeZone('America/Argentina/Buenos_Aires'));

        return [
            'same-string' => [[
                'name' => ['abc', 'abc'],
            ]],
            'same-bool' => [[
                'is_enabled' => [true, true],
            ]],
            'same-null' => [[
                'submission_lock_down_presentation_status_date' => [null, null],
            ]],
            'datetime-same-instant-different-tz' => [[
                'submission_begin_date' => [$dtUtc, $dtSameInstantOtherTz],
            ]],
        ];
    }

    public static function provideMeaningfulChangeSets(): array
    {
        $dt1 = new DateTimeImmutable('2026-01-02 03:04:05', new DateTimeZone('UTC'));
        $dt2 = new DateTimeImmutable('2026-01-02 03:04:06', new DateTimeZone('UTC'));

        return [
            'string-changed' => [[
                'name' => ['abc', 'def'],
            ]],
            'bool-changed' => [[
                'is_hidden' => [false, true],
            ]],
            'null-to-value' => [[
                'max_submission_allowed_per_user' => [null, 3],
            ]],
            'datetime-changed' => [[
                'submission_begin_date' => [$dt1, $dt2],
            ]],
        ];
    }
}

final class ExposingMeaningfulAuditFormatter extends AbstractAuditLogFormatter
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
