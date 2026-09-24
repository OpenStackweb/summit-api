<?php

namespace Tests\Unit\Entities;

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

use App\Models\Foundation\Summit\Factories\PresentationFactory;
use models\summit\Presentation;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * attending_media is a boolean column. The payload may omit it (summit-admin strips it
 * and SelectionPlan::curatePayloadByPresentationAllowedQuestions() drops it when the
 * selection plan does not allow that question), in which case the stored value must be
 * left alone; when present it must land as a strict bool, never as int 0 / 1.
 */
class PresentationFactoryTest extends TestCase
{
    public function testPayloadWithoutAttendingMediaLeavesNewPresentationAsBoolFalse(): void
    {
        $presentation = new Presentation();

        PresentationFactory::populate($presentation, []);

        $this->assertSame(false, $presentation->getAttendingMedia());
    }

    public function testPayloadWithoutAttendingMediaKeepsCurrentValue(): void
    {
        $presentation = new Presentation();
        $presentation->setAttendingMedia(true);

        PresentationFactory::populate($presentation, ['title' => 'Edited title']);

        $this->assertSame(true, $presentation->getAttendingMedia());
    }

    public static function attendingMediaPayloadProvider(): array
    {
        return [
            'bool true'    => [true, true],
            'string true'  => ['true', true],
            'string 1'     => ['1', true],
            'int 1'        => [1, true],
            'bool false'   => [false, false],
            'string false' => ['false', false],
            'string 0'     => ['0', false],
            'int 0'        => [0, false],
        ];
    }

    #[DataProvider('attendingMediaPayloadProvider')]
    public function testAttendingMediaPayloadIsStoredAsStrictBool($payload_value, bool $expected): void
    {
        $presentation = new Presentation();
        $presentation->setAttendingMedia(!$expected);

        PresentationFactory::populate($presentation, ['attending_media' => $payload_value]);

        $this->assertSame($expected, $presentation->getAttendingMedia());
    }
}
