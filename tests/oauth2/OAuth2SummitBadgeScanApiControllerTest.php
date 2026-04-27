<?php namespace Tests;
/**
 * Copyright 2019 OpenStack Foundation
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
use App\Models\Foundation\Summit\ExtraQuestions\SummitSponsorExtraQuestionType;
use Libs\ModelSerializers\AbstractSerializer;
use models\main\Group;
use models\summit\Sponsor;
use models\summit\SummitAttendee;
use models\summit\SummitAttendeeBadge;
use models\summit\SummitAttendeeTicket;
use models\summit\SummitLeadReportSetting;
use models\summit\SummitOrder;
/**
 * Class OAuth2SummitBadgeScanApiControllerTest
 */
class OAuth2SummitBadgeScanApiControllerTest extends ProtectedApiTestCase
{
    use InsertSummitTestData;

    private $external_sponsor_group;

    private $sponsor_group;

    protected function setUp():void
    {
        parent::setUp();
        self::$defaultMember = self::$member;
        self::$defaultMember2 = self::$member2;
        self::insertSummitTestData();

        $this->external_sponsor_group = new Group();
        $this->external_sponsor_group->setCode(IGroup::SponsorExternalUsers);
        $this->external_sponsor_group->setTitle(IGroup::SponsorExternalUsers);
        self::$em->persist($this->external_sponsor_group);

        $this->sponsor_group = new Group();
        $this->sponsor_group->setCode(IGroup::Sponsors);
        $this->sponsor_group->setTitle(IGroup::Sponsors);
        self::$em->persist($this->sponsor_group);

        // Pre-wire self::$member as a permissioned sponsor user for sponsors[0] so that
        // tests which exercise the happy path do not need per-test boilerplate.
        self::$member->add2Group($this->sponsor_group);
        $sponsor0 = self::$sponsors[0];
        $sponsor0->addUser(self::$member);
        self::$em->persist(self::$member);
        self::$em->persist($sponsor0);
        self::$em->flush();

        // Write IGroup::Sponsors into Sponsor_Users.Permissions so hasSponsorMembershipsFor passes.
        self::$member->addSponsorPermission($sponsor0->getId(), IGroup::Sponsors);
    }

    protected function tearDown():void
    {
        self::clearSummitTestData();
        parent::tearDown();
    }

    public function testAddEncryptedBadgeScan(){
        // set test data
        self::$member->clearGroups();
        self::$member->add2Group($this->sponsor_group);
        self::$member->add2Group($this->external_sponsor_group);
        self::$em->persist(self::$member);
        self::$em->flush();

        $sponsor = self::$summit->getSummitSponsors()[0];
        $sponsor->addUser(self::$member);
        self::$em->persist($sponsor);
        self::$em->flush();

        self::$member->addSponsorPermission($sponsor->getId(), IGroup::SponsorExternalUsers);

        $attendee =  self::$summit->getAttendees()[0];

        self::$summit->setQRCodesEncKey('35NVOF4I5T6AAM28IJPKB8KRUW98KPDO');
        self::$em->persist(self::$summit);
        self::$em->flush();

        $this->assertTrue($sponsor->hasUser(self::$member));
        $this->assertGreaterThan(0, self::$member->getAccessibleSponsorsBySummit(self::$summit)->count());

        $badge = $attendee->getFirstTicket()->getBadge();
        $badge_qr_code = $badge->generateQRCode();

        $params = [
            'id' => self::$summit->getId()
        ];

        $data = [
            'qr_code' => base64_encode($badge_qr_code),
            'scan_date' => 1572019200,
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitBadgeScanApiController@add",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $this->assertResponseStatus(201);
        $content = $response->getContent();
        $badge_scan = json_decode($content);
        $this->assertNotNull($badge_scan);
        $this->assertEquals(self::$member->getId(), $badge_scan->scanned_by_id);
        $this->assertEquals($badge->getId(), $badge_scan->badge_id);
    }

    public function testAddBadgeScanWithOneSponsorPerMember(){
        self::$member->clearGroups();
        self::$member->add2Group($this->sponsor_group);
        self::$em->persist(self::$member);
        self::$em->flush();

        $sponsor = self::$summit->getSummitSponsors()[0];
        $sponsor->addUser(self::$member);
        self::$em->persist($sponsor);
        self::$em->flush();

        $params = [
            'id' => self::$summit->getId(),
        ];

        $attendee = self::$summit->getAttendeeByMemberId(self::$defaultMember->getId());
        $badge = $attendee->getFirstTicket()->getBadge();

        $data = [
            'qr_code' => $badge->generateQRCode(),
            'scan_date' => 1572019200,
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitBadgeScanApiController@add",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $scan = json_decode($content);
        $this->assertTrue(!is_null($scan));
        $this->assertEquals(\models\summit\SponsorBadgeScan::Source_QR, $scan->source);
        return $scan;
    }

    public function testAddBadgeScanByAttendeeEmail(){
        self::$member->clearGroups();
        self::$member->add2Group($this->sponsor_group);
        self::$em->persist(self::$member);
        self::$em->flush();

        $sponsor = self::$summit->getSummitSponsors()[0];
        $sponsor->addUser(self::$member);
        self::$em->persist($sponsor);
        self::$em->flush();

        // Build a dedicated single-ticket attendee+order+badge inline to avoid the
        // shared-badge quirk in InsertSummitTestData (defaultMember has 5 tickets
        // sharing one badge, so only the last ticket's TicketID FK is persisted).
        $attendee = new SummitAttendee();
        $attendee->setEmail('badge-scan-email-target@example.com');
        $attendee->setFirstName('Badge');
        $attendee->setSurname('ScanTarget');

        $order = new SummitOrder();
        $order->setOwner(self::$defaultMember);
        $order->setSummit(self::$summit);

        $ticket = new SummitAttendeeTicket();
        $ticket->setTicketType(self::$default_ticket_type);
        $ticket->activate();
        $order->addTicket($ticket);
        $attendee->addTicket($ticket);

        $badge = new SummitAttendeeBadge();
        $badge->setType(self::$default_badge_type);
        $ticket->setBadge($badge);

        $order->setPaid();
        $order->generateNumber();
        $ticket->generateNumber();
        $ticket->generateQRCode();
        $badge->generateQRCode();

        self::$summit->addAttendee($attendee);
        self::$summit->addOrder($order);
        self::$em->persist($attendee);
        self::$em->persist($order);
        self::$em->flush();

        $params = [
            'id' => self::$summit->getId(),
        ];

        $data = [
            'attendee_email' => $attendee->getEmail(),
            'scan_date' => 1572019200,
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitBadgeScanApiController@add",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $scan = json_decode($content);
        $this->assertTrue(!is_null($scan));
        $this->assertEquals(self::$member->getId(), $scan->scanned_by_id);
        $this->assertEquals($badge->getId(), $scan->badge_id);
        $this->assertEquals(\models\summit\SponsorBadgeScan::Source_Attendee_Email, $scan->source);
        return $scan;
    }

    public function testAddBadgeScanByAttendeeEmailWithNoQRCode(){
        self::$member->clearGroups();
        self::$member->add2Group($this->sponsor_group);
        self::$em->persist(self::$member);
        self::$em->flush();

        $sponsor = self::$summit->getSummitSponsors()[0];
        $sponsor->addUser(self::$member);
        self::$em->persist($sponsor);
        self::$em->flush();

        // Build a dedicated single-ticket attendee+order+badge inline, but
        // DO NOT generate the badge QR code to reproduce the null QR code bug.
        $attendee = new SummitAttendee();
        $attendee->setEmail('badge-scan-no-qr@example.com');
        $attendee->setFirstName('NoQR');
        $attendee->setSurname('Badge');

        $order = new SummitOrder();
        $order->setOwner(self::$defaultMember);
        $order->setSummit(self::$summit);

        $ticket = new SummitAttendeeTicket();
        $ticket->setTicketType(self::$default_ticket_type);
        $ticket->activate();
        $order->addTicket($ticket);
        $attendee->addTicket($ticket);

        $badge = new SummitAttendeeBadge();
        $badge->setType(self::$default_badge_type);
        $ticket->setBadge($badge);

        $order->setPaid();
        $order->generateNumber();
        $ticket->generateNumber();
        $ticket->generateQRCode();
        // OMIT: $badge->generateQRCode(); — this is the bug trigger

        self::$summit->addAttendee($attendee);
        self::$summit->addOrder($order);
        self::$em->persist($attendee);
        self::$em->persist($order);
        self::$em->flush();

        $params = [
            'id' => self::$summit->getId(),
        ];

        $data = [
            'attendee_email' => $attendee->getEmail(),
            'scan_date' => 1572019200,
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitBadgeScanApiController@add",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $scan = json_decode($content);
        $this->assertTrue(!is_null($scan));
        $this->assertEquals(self::$member->getId(), $scan->scanned_by_id);
        $this->assertEquals($badge->getId(), $scan->badge_id);
        $this->assertEquals(\models\summit\SponsorBadgeScan::Source_Attendee_Email, $scan->source);
    }

    public function testAddBadgeScanMissingQrCodeAndEmailFails(){
        self::$member->clearGroups();
        self::$member->add2Group($this->sponsor_group);
        self::$em->persist(self::$member);
        self::$em->flush();

        $sponsor = self::$summit->getSummitSponsors()[0];
        $sponsor->addUser(self::$member);
        self::$em->persist($sponsor);
        self::$em->flush();

        $params = [
            'id' => self::$summit->getId(),
        ];

        $data = [
            'scan_date' => 1572019200,
        ];

        $this->action(
            "POST",
            "OAuth2SummitBadgeScanApiController@add",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $this->assertResponseStatus(412);
    }

    public function testAddBadgeScanFailsWhenSponsorHasNoPermissionSlug()
    {
        // member2 is in the global sponsors group so hasSponsorMembershipsFor doesn't
        // short-circuit, but the Sponsor_Users row has no permission slug
        // (simulates the MQ group event never arriving).
        self::$member2->add2Group($this->sponsor_group);
        $sponsor = self::$sponsors[0];
        $sponsor->addUser(self::$member2);
        self::$em->persist(self::$member2);
        self::$em->persist($sponsor);
        self::$em->flush();
        // Sponsor_Users row was created with Permissions = NULL — deliberately no addSponsorPermission call.

        // Impersonate member2 for this request.
        self::$service->setUserId(self::$member2->getUserExternalId());
        self::$service->setUserExternalId(self::$member2->getUserExternalId());
        self::$service->setUserEmail(self::$member2->getEmail());
        self::$service->setUserFirstName(self::$member2->getFirstName());
        self::$service->setUserLastName(self::$member2->getLastName());

        $params = [
            'id' => self::$summit->getId(),
        ];

        $attendee = self::$summit->getAttendeeByMemberId(self::$defaultMember->getId());
        $badge = $attendee->getFirstTicket()->getBadge();

        $data = [
            'qr_code'   => $badge->generateQRCode(),
            'scan_date' => 1572019200,
        ];

        $this->action(
            "POST",
            "OAuth2SummitBadgeScanApiController@add",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $this->assertResponseStatus(412);
    }

    public function testAddBadgeScanByUnknownAttendeeEmailFails(){
        self::$member->clearGroups();
        self::$member->add2Group($this->sponsor_group);
        self::$em->persist(self::$member);
        self::$em->flush();

        $sponsor = self::$summit->getSummitSponsors()[0];
        $sponsor->addUser(self::$member);
        self::$em->persist($sponsor);
        self::$em->flush();

        $params = [
            'id' => self::$summit->getId(),
        ];

        $data = [
            'attendee_email' => 'no-such-attendee@example.com',
            'scan_date' => 1572019200,
        ];

        $this->action(
            "POST",
            "OAuth2SummitBadgeScanApiController@add",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $this->assertResponseStatus(404);
    }

    public function testAddBadgeScanWithMultipleSponsorsWithoutSponsorId()
    {
        self::$member->clearGroups();
        self::$member->add2Group($this->sponsor_group);
        self::$em->persist(self::$member);
        self::$em->flush();

        $sponsor1 = self::$sponsors[0];
        $sponsor1->addUser(self::$member);
        self::$em->persist($sponsor1);

        $sponsor2 = self::$sponsors[1];
        $sponsor2->addUser(self::$member);
        self::$em->persist($sponsor2);

        self::$em->flush();

        self::$member->addSponsorPermission($sponsor1->getId(), IGroup::Sponsors);
        self::$member->addSponsorPermission($sponsor2->getId(), IGroup::Sponsors);

        $this->assertGreaterThan(1, self::$member->getAccessibleSponsorsBySummit(self::$summit)->count());

        $params = [
            'id' => self::$summit->getId(),
        ];

        $attendee = self::$summit->getAttendeeByMemberId(self::$defaultMember->getId());
        $badge = $attendee->getFirstTicket()->getBadge();

        $data = [
            'qr_code'   => $badge->generateQRCode(),
            'scan_date' => 1572019200,
        ];

        $this->action(
            "POST",
            "OAuth2SummitBadgeScanApiController@add",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $this->assertResponseStatus(412);
    }

    public function testAddBadgeScanWithMultipleSponsorsWithSponsorId()
    {
        self::$member->clearGroups();
        self::$member->add2Group($this->sponsor_group);
        self::$em->persist(self::$member);
        self::$em->flush();

        $sponsor1 = self::$sponsors[0];
        $sponsor1->addUser(self::$member);
        self::$em->persist($sponsor1);

        $sponsor2 = self::$sponsors[1];
        $sponsor2->addUser(self::$member);
        self::$em->persist($sponsor2);

        self::$em->flush();

        self::$member->addSponsorPermission($sponsor1->getId(), IGroup::Sponsors);
        self::$member->addSponsorPermission($sponsor2->getId(), IGroup::Sponsors);

        $this->assertGreaterThan(1, self::$member->getAccessibleSponsorsBySummit(self::$summit)->count());

        $params = [
            'id' => self::$summit->getId(),
        ];

        $attendee = self::$summit->getAttendeeByMemberId(self::$defaultMember->getId());
        $badge = $attendee->getFirstTicket()->getBadge();

        $data = [
            'qr_code'    => $badge->generateQRCode(),
            'scan_date'  => 1572019200,
            'sponsor_id' => $sponsor1->getId(),
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitBadgeScanApiController@add",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $scan = json_decode($content);
        $this->assertNotNull($scan);
        $this->assertEquals(self::$member->getId(), $scan->scanned_by_id);
        $this->assertEquals($badge->getId(), $scan->badge_id);
        $this->assertEquals($sponsor1->getId(), $scan->sponsor_id);
    }

    public function testUpdateBadgeScan(){
        $scan = $this->testAddBadgeScanWithOneSponsorPerMember();

        $params = [
            'id' => self::$summit->getId(),
            'scan_id' => $scan->id,
        ];

        $data = [
            'extra_questions' => [
                ['question_id' => 519, 'answer' => 'None'],
            ],
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitBadgeScanApiController@update",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $scan = json_decode($content);
        $this->assertTrue(!is_null($scan));
        return $scan;
    }

    public function testGetAllMyBadgeScans(){
        self::$member->clearGroups();
        self::$member->add2Group($this->sponsor_group);
        self::$em->persist(self::$member);
        self::$em->flush();

        $sponsor = self::$summit->getSummitSponsors()[0];
        $sponsor->addUser(self::$member);
        self::$em->persist($sponsor);
        self::$em->flush();

        $params = [
            'id'    =>  self::$summit->getId(),
            'filter'=> 'attendee_email=@santi',
            'expand' => 'sponsor,badge,badge.ticket,badge.ticket.owner,extra_question_answers'
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitBadgeScanApiController@getAllMyBadgeScans",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $data = json_decode($content);
        $this->assertTrue(!is_null($data));
        return $data;
    }

    public function testCheckInBadgeScan(){
        $params = [
            'id' => self::$summit->getId(),
        ];

        $attendee = self::$summit->getAttendeeByMemberId(self::$defaultMember->getId());
        $badge = $attendee->getFirstTicket()->getBadge();
        $data = [
            'qr_code' => $badge->generateQRCode(),
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitBadgeScanApiController@checkIn",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $scan = json_decode($content);
        $this->assertTrue(!is_null($scan));
        return $scan;
    }

    public function testGetAllSummitBadgeScans(){

        $params = [
            'id'    =>  self::$summit->getId(),
            'expand' => 'sponsor,badge,badge.ticket,badge.ticket.owner,extra_question_answers'
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitBadgeScanApiController@getAllBySummit",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $page = json_decode($content);
        $this->assertTrue(!is_null($page));
        return $page;
    }

    public function testGetSummitBadgeScan(){
        $badge_scan = $this->testAddBadgeScanWithOneSponsorPerMember();

        $params = [
            'id'      =>  self::$summit->getId(),
            'scan_id' =>  $badge_scan->id,
            'expand'  => 'sponsor,badge,badge.ticket,badge.ticket.owner,extra_question_answers'
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitBadgeScanApiController@get",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $scan = json_decode($content);
        $this->assertTrue(!is_null($scan));
        return $scan;
    }

    public function testExportSummitBadgeScans(){

        $this->testAddBadgeScanWithOneSponsorPerMember();

        $params = [
            'id'    =>  self::$summit->getId(),
            'columns'  => 'scan_date,attendee_first_name,attendee_last_name,attendee_email,attendee_company',
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitBadgeScanApiController@getAllBySummitCSV",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $this->assertNotEmpty($content);
    }

    public function testExportSummitBadgeScansWithReportSettingsRestriction(){

        $this->testAddBadgeScanWithOneSponsorPerMember();

        $sponsor = self::$summit->getSummitSponsors()[0];
        if (!$sponsor instanceof Sponsor) self::fail();

        $sponsor_question = $sponsor->getExtraQuestions()[0];
        if (!$sponsor_question instanceof SummitSponsorExtraQuestionType) self::fail();

        $params = [
            'id'    =>  self::$summit->getId(),
            'columns'  => 'scan_date,attendee_first_name,attendee_last_name,attendee_email,attendee_company',
        ];

        // set up allowed columns

        $allowed_columns = [
            'scan_date',
            'extra_questions' => [
                [
                    'id'   => $sponsor_question->getId(),
                    'name' => $sponsor_question->getName()
                ]
            ]
        ];

        $data = [
            'allowed_columns' => $allowed_columns
        ];

        $this->action(
            "PUT",
            "OAuth2SummitApiController@updateLeadReportSettings",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $this->assertResponseStatus(201);

        $response = $this->action(
            "GET",
            "OAuth2SummitBadgeScanApiController@getAllBySummitCSV",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $this->assertNotEmpty($content);
        $this->assertTrue(str_contains($content, AbstractSerializer::getCSVLabel($sponsor_question->getLabel())));
        $this->assertTrue(str_contains($content, 'scan_date'));
    }

    public function testExportSummitBadgeScansWithAllReportSettingsRestriction(){

        $this->testAddBadgeScanWithOneSponsorPerMember();

        $sponsor = self::$summit->getSummitSponsors()[0];
        if (!$sponsor instanceof Sponsor) self::fail();

        $sponsor_question = $sponsor->getExtraQuestions()[0];
        if (!$sponsor_question instanceof SummitSponsorExtraQuestionType) self::fail();

        $params = [
            'id'    =>  self::$summit->getId(),
            'columns'  => 'scan_date,attendee_first_name,attendee_last_name,attendee_email,attendee_company',
        ];

        $allowed_columns = [
            'scan_date',
            SummitLeadReportSetting::AttendeeExtraQuestionsKey => [],
            SummitLeadReportSetting::SponsorExtraQuestionsKey => []
        ];

        $data = [
            'allowed_columns' => $allowed_columns
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitApiController@updateLeadReportSettings",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $settings = json_decode($content);

        $this->assertResponseStatus(201);

        $response = $this->action(
            "GET",
            "OAuth2SummitBadgeScanApiController@getAllBySummitCSV",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $this->assertNotEmpty($content);
    }
}
