<?php namespace Tests;
/*
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

use App\ModelSerializers\Summit\SponsorBadgeScanCSVSerializer;
use ReflectionMethod;

final class SponsorBadgeScanCSVSerializerTest extends TestCase
{
    private function uniqueKey(array $taken, string $label, string $name, int $id): string
    {
        $method = new ReflectionMethod(SponsorBadgeScanCSVSerializer::class, 'getUniqueColumnKey');
        $method->setAccessible(true);
        return $method->invoke(null, $taken, $label, $name, $id);
    }

    public function testNoCollisionKeepsLabel(): void
    {
        $this->assertSame('company size', $this->uniqueKey(['notes' => 'x'], 'company size', 'COMPANY_SIZE', 1));
    }

    public function testCollisionWithFixedColumnUsesQuestionName(): void
    {
        $this->assertSame('notes (notes_q)', $this->uniqueKey(['notes' => 'scan notes'], 'notes', 'NOTES_Q', 10));
    }

    public function testCollisionWithSameLabelAndNameUsesQuestionId(): void
    {
        $taken = ['notes' => 'a', 'notes (notes_q)' => 'b'];
        $this->assertSame('notes (notes_q-11)', $this->uniqueKey($taken, 'notes', 'notes_q', 11));
    }
}
