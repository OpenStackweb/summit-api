<?php namespace Tests;
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

use App\Services\Model\ISummitScheduleSettingsService;
use Illuminate\Support\Facades\App;
use models\exceptions\ValidationException;

/**
 * Class SummitScheduleSettingsServiceTest
 */
final class SummitScheduleSettingsServiceTest extends BrowserKitTestCase
{
    use InsertSummitTestData;

    protected function setUp(): void
    {
        parent::setUp();
        self::insertSummitTestData();
    }

    protected function tearDown(): void
    {
        self::clearSummitTestData();
        parent::tearDown();
    }

    /**
     * SummitScheduleSettingsService::seedDefaults() (SummitScheduleSettingsService.php:141)
     * loops calling add() (:60, its own nested transaction) for 'schedule-main' then
     * 'my-schedule-main' - no try/catch. Summit::addScheduleSetting() throws
     * ValidationException("Key %s already exists") if a config with that key already exists
     * (Summit.php:6433-6439). Pre-seeding 'my-schedule-main' makes the loop's FIRST iteration
     * ('schedule-main') commit successfully, then the SECOND ('my-schedule-main') fails -
     * rolling back the whole seedDefaults() call, including the first, already-committed config.
     */
    public function testSeedDefaultsRollsBackAlreadyCommittedScheduleMainWhenMyScheduleMainKeyAlreadyExists()
    {
        $service = App::make(ISummitScheduleSettingsService::class);

        $service->add(self::$summit, ['key' => 'my-schedule-main']);

        try {
            $service->seedDefaults(self::$summit);
            $this->fail('Expected ValidationException was not thrown');
        } catch (ValidationException $ex) {
            $this->assertStringContainsString('my-schedule-main', $ex->getMessage());
            $this->assertStringContainsString('already exists', $ex->getMessage());
        }

        self::$em->clear();
        self::$summit = self::$summit_repository->find(self::$summit->getId());

        $has_schedule_main = false;
        foreach (self::$summit->getScheduleSettings() as $config) {
            if ($config->getKey() === 'schedule-main') {
                $has_schedule_main = true;
            }
        }
        $this->assertFalse($has_schedule_main);
    }
}
