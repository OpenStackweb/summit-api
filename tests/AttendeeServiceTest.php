<?php namespace Tests;
/**
 * Copyright 2018 OpenStack Foundation
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
use App\Jobs\Emails\SummitAttendeeRegistrationIncompleteReminderEmail;
use App\Models\Foundation\Main\IGroup;
use App\Services\Model\IAttendeeService;
use Illuminate\Support\Facades\App;
/**
 * Class AttendeeServiceTest
 */
final class AttendeeServiceTest extends TestCase
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

    public function testRedeemPromoCodes(){

        $service = App::make(IAttendeeService::class);
        $repo   =  EntityManager::getRepository(\models\summit\Summit::class);
        $summit = $repo->getById(24);
        $service->updateRedeemedPromoCodes($summit);
    }

    public function testSendAllAttendeeTickets() {

        $service = App::make(IAttendeeService::class);

        $payload = [
            "email_flow_event"  => SummitAttendeeAllTicketsEditionEmail::EVENT_SLUG,
        ];

        $service->send(self::$summit->getId(), $payload);
    }

    public function testSendAllAttendeeTicketsByAttendeeIds() {

        $service = App::make(IAttendeeService::class);

        $payload = [
            "email_flow_event"  => SummitAttendeeAllTicketsEditionEmail::EVENT_SLUG,
            "attendees_ids"     => [self::$summit->getAttendees()[0]->getId()],
        ];

        $service->send(self::$summit->getId(), $payload);
    }

    public function testSendRegistrationIncompleteReminderByAttendeeIds() {

        $service = App::make(IAttendeeService::class);

        $payload = [
            "email_flow_event"  => SummitAttendeeRegistrationIncompleteReminderEmail::EVENT_SLUG,
            "attendees_ids"     => [self::$summit->getAttendees()[0]->getId()],
        ];

        $service->send(self::$summit->getId(), $payload);
    }
}