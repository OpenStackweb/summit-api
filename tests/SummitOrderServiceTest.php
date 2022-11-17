<?php namespace Tests;
/**
 * Copyright 2022 OpenStack Foundation
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

use App\Jobs\Emails\SummitAttendeeAllTicketsEditionEmail;
use App\Models\Foundation\Main\IGroup;
use App\Services\Model\ISummitOrderService;
use Illuminate\Support\Facades\App;
/**
 * Class SummitOrderServiceTest
 */
final class SummitOrderServiceTest extends TestCase
{
    use InsertSummitTestData;

    use InsertMemberTestData;

    protected function setUp(): void
    {
        parent::setUp();

        self::insertMemberTestData(IGroup::TrackChairs);
        self::$defaultMember = self::$member;
        self::insertTestData();
    }

    protected function tearDown(): void
    {
        self::clearTestData();
        self::clearMemberTestData();
        parent::tearDown();
    }

    public function testProcessSummitOrderReminders() {

        $service = App::make(ISummitOrderService::class);
        $service->processSummitOrderReminders(self::$summit);
    }
}