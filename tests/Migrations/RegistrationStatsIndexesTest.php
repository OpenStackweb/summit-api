<?php namespace Tests\Migrations;
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

use Illuminate\Support\Facades\DB;
use Tests\BrowserKitTestCase;

/**
 * RED test (Approach D / D-half-2):
 * Asserts the 4 composite indexes introduced by the Approach D migration exist in the schema.
 * FAILS today (migration not yet written). PASSES after Task 2 adds the migration.
 *
 * Class RegistrationStatsIndexesTest
 * @package Tests\Migrations
 */
final class RegistrationStatsIndexesTest extends BrowserKitTestCase
{
    /**
     * Asserts that all 4 Approach D composite indexes are present in the schema.
     * Relies on BrowserKitTestCase running `doctrine:migrations:migrate` before tests,
     * so the new migration is applied automatically once the file is added.
     */
    public function testRequiredCompositeIndexesExistAfterMigration(): void
    {
        $required = [
            'SummitAttendeeTicket' => [
                'IDX_SummitAttendeeTicket_Stats',
                'IDX_SummitAttendeeTicket_BoughtDate',
            ],
            'SummitAttendee' => [
                'IDX_SummitAttendee_HallCheckIn',
                'IDX_SummitAttendee_VirtualCheckIn',
            ],
        ];

        $missing = [];
        foreach ($required as $tableName => $indexNames) {
            foreach ($indexNames as $indexName) {
                $rows = DB::connection('model')->select(
                    "SELECT INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS
                     WHERE TABLE_SCHEMA = DATABASE()
                       AND TABLE_NAME    = ?
                       AND INDEX_NAME    = ?
                     LIMIT 1",
                    [$tableName, $indexName]
                );
                if (empty($rows)) {
                    $missing[] = "{$tableName}.{$indexName}";
                }
            }
        }

        $this->assertEmpty(
            $missing,
            'The following Approach D composite indexes are missing — ' .
            'ensure the migration (Version20260429<timestamp>.php) was created and applied: ' .
            implode(', ', $missing)
        );
    }
}
