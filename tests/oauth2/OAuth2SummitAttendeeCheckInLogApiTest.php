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
use App\Models\Foundation\Main\IGroup;
use Illuminate\Support\Facades\App;
use models\summit\SummitAccessLevelType;
use models\summit\SummitAttendee;
use models\summit\SummitAttendeeCheckInLog;
use services\model\ISummitOrderService;

/**
 * Check in / check out audit log (ADR-005): writers on the 3 call sites + read API.
 * Class OAuth2SummitAttendeeCheckInLogApiTest
 * @package Tests
 */
class OAuth2SummitAttendeeCheckInLogApiTest extends ProtectedApiTestCase
{
    use InsertSummitTestData;

    protected function setUp(): void
    {
        $this->current_group = IGroup::TrackChairs;
        parent::setUp();
        self::$defaultMember = self::$member;
        self::insertSummitTestData();
    }

    protected function tearDown(): void
    {
        self::clearSummitTestData();
        parent::tearDown();
    }

    private function getAttendee(): SummitAttendee
    {
        $attendee = self::$summit->getAttendeeByMember(self::$defaultMember);
        $this->assertNotNull($attendee);
        return $attendee;
    }

    private function setCheckedIn(SummitAttendee $attendee, bool $value): void
    {
        $attendee->setSummitHallCheckedIn($value);
        self::$em->persist($attendee);
        self::$em->flush();
    }

    private function updateAttendee(SummitAttendee $attendee, array $extra)
    {
        $params = [
            'id' => self::$summit->getId(),
            'attendee_id' => $attendee->getId(),
        ];

        $data = array_merge([
            'first_name' => $attendee->getFirstName(),
            'surname' => $attendee->getSurname(),
            'email' => $attendee->getEmail(),
        ], $extra);

        return $this->action(
            "PUT",
            "OAuth2SummitAttendeesApiController@updateAttendee",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );
    }

    private function getLogs(int $attendee_id, array $extra = [])
    {
        $params = array_merge([
            'id' => self::$summit->getId(),
            'attendee_id' => $attendee_id,
            'page' => 1,
            'per_page' => 50,
            'order' => '+id',
        ], $extra);

        $response = $this->action(
            "GET",
            "OAuth2SummitAttendeeCheckInLogApiController@getAllByAttendee",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $this->assertResponseStatus(200);
        $page = json_decode($response->getContent());
        $this->assertNotNull($page);
        return $page;
    }

    public function testAdminCheckInIsLogged(): void
    {
        $attendee = $this->getAttendee();
        $this->setCheckedIn($attendee, false);

        $this->updateAttendee($attendee, ['summit_hall_checked_in' => true]);
        $this->assertResponseStatus(201);

        $page = $this->getLogs($attendee->getId());
        $this->assertEquals(1, $page->total);
        $log = $page->data[0];
        $this->assertEquals(SummitAttendeeCheckInLog::ActionCheckedIn, $log->action);
        $this->assertEquals(SummitAttendeeCheckInLog::SourceAdminUI, $log->source);
        $this->assertEquals($attendee->getId(), $log->attendee_id);
        $this->assertEquals(self::$defaultMember->getId(), $log->actor_id);
        $this->assertTrue(empty($log->reason));
    }

    public function testAdminCheckOutWithoutReasonIsRejected(): void
    {
        $attendee = $this->getAttendee();
        $this->setCheckedIn($attendee, true);

        $this->updateAttendee($attendee, ['summit_hall_checked_in' => false]);
        $this->assertResponseStatus(412);

        $this->updateAttendee($attendee, ['summit_hall_checked_in' => false, 'reason' => '   ']);
        $this->assertResponseStatus(412);

        self::$em->clear();
        $attendee = self::$em->find(SummitAttendee::class, $attendee->getId());
        $this->assertTrue($attendee->hasCheckedIn());
        $this->assertEquals(0, $this->getLogs($attendee->getId())->total);
    }

    public function testAdminCheckOutWithReasonIsLogged(): void
    {
        $attendee = $this->getAttendee();
        $this->setCheckedIn($attendee, true);

        $this->updateAttendee($attendee, [
            'summit_hall_checked_in' => false,
            'reason' => 'Left the venue',
        ]);
        $this->assertResponseStatus(201);

        $page = $this->getLogs($attendee->getId(), ['expand' => 'actor']);
        $this->assertEquals(1, $page->total);
        $log = $page->data[0];
        $this->assertEquals(SummitAttendeeCheckInLog::ActionCheckedOut, $log->action);
        $this->assertEquals(SummitAttendeeCheckInLog::SourceAdminUI, $log->source);
        $this->assertEquals('Left the venue', $log->reason);
        $this->assertEquals(self::$defaultMember->getId(), $log->actor->id);
        $this->assertNotEmpty($log->client_id);

        // boolean/date read model keeps working
        self::$em->clear();
        $attendee = self::$em->find(SummitAttendee::class, $attendee->getId());
        $this->assertFalse($attendee->hasCheckedIn());
        $this->assertNull($attendee->getSummitHallCheckedInDate());
    }

    public function testUpdateWithoutStateChangeDoesNotLog(): void
    {
        $attendee = $this->getAttendee();
        $this->setCheckedIn($attendee, true);

        // same value
        $this->updateAttendee($attendee, ['summit_hall_checked_in' => true]);
        $this->assertResponseStatus(201);
        // field not sent
        $this->updateAttendee($attendee, []);
        $this->assertResponseStatus(201);

        $this->assertEquals(0, $this->getLogs($attendee->getId())->total);
    }

    public function testCreateAttendeeAlreadyCheckedInIsLogged(): void
    {
        $response = $this->action(
            "POST",
            "OAuth2SummitAttendeesApiController@addAttendee",
            ['id' => self::$summit->getId()],
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode([
                'member_id' => self::$member2->getId(),
                'summit_hall_checked_in' => true,
            ])
        );
        $this->assertResponseStatus(201);
        $attendee = json_decode($response->getContent());

        $page = $this->getLogs($attendee->id);
        $this->assertEquals(1, $page->total);
        $this->assertEquals(SummitAttendeeCheckInLog::ActionCheckedIn, $page->data[0]->action);
        $this->assertEquals(SummitAttendeeCheckInLog::SourceAdminUI, $page->data[0]->source);
    }

    public function testCreateAttendeeNotCheckedInIsNotLogged(): void
    {
        $response = $this->action(
            "POST",
            "OAuth2SummitAttendeesApiController@addAttendee",
            ['id' => self::$summit->getId()],
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode(['member_id' => self::$member2->getId()])
        );
        $this->assertResponseStatus(201);
        $attendee = json_decode($response->getContent());

        $this->assertEquals(0, $this->getLogs($attendee->id)->total);
    }

    public function testBadgeScanCheckInIsLogged(): void
    {
        $attendee = $this->getAttendee();
        $this->setCheckedIn($attendee, false);
        $badge = $attendee->getFirstTicket()->getBadge();

        $this->action(
            "PUT",
            "OAuth2SummitBadgeScanApiController@checkIn",
            ['id' => self::$summit->getId()],
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode(['qr_code' => $badge->generateQRCode()])
        );
        $this->assertResponseStatus(201);

        $page = $this->getLogs($attendee->getId());
        $this->assertEquals(1, $page->total);
        $this->assertEquals(SummitAttendeeCheckInLog::ActionCheckedIn, $page->data[0]->action);
        $this->assertEquals(SummitAttendeeCheckInLog::SourceBadgeScan, $page->data[0]->source);

        // already checked in: rejected and nothing else is logged
        $this->action(
            "PUT",
            "OAuth2SummitBadgeScanApiController@checkIn",
            ['id' => self::$summit->getId()],
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode(['qr_code' => $badge->generateQRCode()])
        );
        $this->assertResponseStatus(412);
        $this->assertEquals(1, $this->getLogs($attendee->getId())->total);
    }

    public function testBadgePrintCheckInIsLogged(): void
    {
        $attendee = $this->getAttendee();
        $this->setCheckedIn($attendee, false);
        $ticket = $attendee->getFirstTicket();
        $badge = $ticket->getBadge();

        // make the fixture badge printable
        $in_person = new SummitAccessLevelType();
        $in_person->setName(SummitAccessLevelType::IN_PERSON);
        self::$summit->addBadgeAccessLevelType($in_person);
        $badge->getType()->addAccessLevel($in_person);
        $badge->getType()->addAllowedViewType(self::$default_badge_view_type);
        self::$em->flush();

        $service = App::make(ISummitOrderService::class);

        // check_in = false: printed but not checked in => nothing logged
        $service->printAttendeeBadge(
            self::$summit, $ticket->getId(), self::$default_badge_view_type->getName(), self::$defaultMember, ['check_in' => false]
        );
        $this->assertEquals(0, $this->getLogs($attendee->getId())->total);

        // default: checks in on print
        $service->printAttendeeBadge(
            self::$summit, $ticket->getId(), self::$default_badge_view_type->getName(), self::$defaultMember
        );
        $page = $this->getLogs($attendee->getId());
        $this->assertEquals(1, $page->total);
        $this->assertEquals(SummitAttendeeCheckInLog::SourceBadgePrint, $page->data[0]->source);
        $this->assertEquals(self::$defaultMember->getId(), $page->data[0]->actor_id);

        // already checked in: printing again does not log again
        $service->printAttendeeBadge(
            self::$summit, $ticket->getId(), self::$default_badge_view_type->getName(), self::$defaultMember
        );
        $this->assertEquals(1, $this->getLogs($attendee->getId())->total);
    }

    public function testFilterByAction(): void
    {
        $attendee = $this->getAttendee();
        $this->setCheckedIn($attendee, false);

        $this->updateAttendee($attendee, ['summit_hall_checked_in' => true]);
        $this->updateAttendee($attendee, ['summit_hall_checked_in' => false, 'reason' => 'test']);

        $this->assertEquals(2, $this->getLogs($attendee->getId())->total);

        $page = $this->getLogs($attendee->getId(), ['filter' => 'action==CHECKED_OUT']);
        $this->assertEquals(1, $page->total);
        $this->assertEquals('test', $page->data[0]->reason);

        $page = $this->getLogs($attendee->getId(), ['filter' => 'source==BADGE_SCAN']);
        $this->assertEquals(0, $page->total);
    }

    public function testExportCSV(): void
    {
        $attendee = $this->getAttendee();
        $this->setCheckedIn($attendee, false);
        $this->updateAttendee($attendee, ['summit_hall_checked_in' => true]);

        $response = $this->action(
            "GET",
            "OAuth2SummitAttendeeCheckInLogApiController@getAllByAttendeeCSV",
            ['id' => self::$summit->getId(), 'attendee_id' => $attendee->getId()],
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $this->assertResponseStatus(200);
        $content = $response->getContent();
        $this->assertStringContainsString('action', $content);
        $this->assertStringContainsString('CHECKED_IN', $content);
        $this->assertStringContainsString('ADMIN_UI', $content);
    }

    public function testUnknownAttendeeReturns404(): void
    {
        $this->action(
            "GET",
            "OAuth2SummitAttendeeCheckInLogApiController@getAllByAttendee",
            ['id' => self::$summit->getId(), 'attendee_id' => 99999999],
            [],
            [],
            [],
            $this->getAuthHeaders()
        );
        $this->assertResponseStatus(404);
    }
}
