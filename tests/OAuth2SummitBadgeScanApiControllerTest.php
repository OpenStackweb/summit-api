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
use models\summit\SummitLeadReportSetting;
/**
 * Class OAuth2SummitBadgeScanApiControllerTest
 */
class OAuth2SummitBadgeScanApiControllerTest extends ProtectedApiTestCase
{
    use InsertSummitTestData;

    protected function setUp():void
    {
        parent::setUp();
        self::$defaultMember = self::$member;
        self::$defaultMember2 = self::$member2;
        self::insertSummitTestData();
    }

    protected function tearDown():void
    {
        self::clearSummitTestData();
        parent::tearDown();
    }

    public function testAddEncryptedBadgeScan(){
        // set test data
        $external_sponsor_group = new Group();
        $external_sponsor_group->setCode(IGroup::SponsorExternalUsers);
        $external_sponsor_group->setTitle(IGroup::SponsorExternalUsers);
        self::$em->persist($external_sponsor_group);
        self::$member->clearGroups();
        self::$member->add2Group($external_sponsor_group);
        self::$em->persist(self::$member);
        self::$em->flush();

        $attendee =  self::$summit->getAttendees()[0];
        $sponsor = self::$summit->getSummitSponsors()[0];
        $sponsor->addUser(self::$member);
        self::$em->persist($sponsor);
        self::$em->flush();

        self::$summit->setQRCodesEncKey('35NVOF4I5T6AAM28IJPKB8KRUW98KPDO');
        self::$em->persist(self::$summit);
        self::$em->flush();

        $this->assertTrue($sponsor->hasUser(self::$member));
        $this->assertNotNull(self::$member->getSponsorBySummit(self::$summit));

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

    public function testAddBadgeScan(){
        $params = [
            'id' => self::$summit->getId(),
        ];

        $attendee = self::$summit->getAttendeeByMemberId(self::$defaultMember->getId());

        $data = [
            'qr_code' => sprintf(
            "%s|%s|%s|%s",
                self::$summit->getBadgeQRPrefix(),
                $attendee->getTickets()[0]->getNumber(),
                $attendee->getEmail(),
                $attendee->getFullName(),
            ),
            'scan_date' => 1572019200,
            'extra_questions' => [
                ['question_id' => 519, 'answer' => 'XL'],
                ['question_id' => 520, 'answer' => 'None'],
            ],
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
        return $scan;
    }

    public function testUpdateBadgeScan(){
        $scan = $this->testAddBadgeScan();

        $params = [
            'id' => self::$summit->getId(),
            'scan_id' => $scan->id,
        ];

        $data = [
            'extra_questions' => [
                ['question_id' => 519, 'answer' => 'None'],
            ],
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitBadgeScanApiController@update",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $scan = json_decode($content);
        $this->assertTrue(!is_null($scan));
        return $scan;
    }

    public function testGetAllMyBadgeScans(){

        $params = [
            'id'    =>  self::$summit->getId(),
            'filter'=> 'attendee_email=@santi',
            'expand' => 'sponsor,badge,badge.ticket,badge.ticket.owner,extra_question_answers'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitBadgeScanApiController@getAllMyBadgeScans",
            $params,
            [],
            [],
            [],
            $headers
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

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $attendee = self::$summit->getAttendeeByMemberId(self::$defaultMember->getId());

        $data = [
            'qr_code' => sprintf
            (
                "%s|%s|%s|%s",
                self::$summit->getBadgeQRPrefix(),
                $attendee->getTickets()[0]->getNumber(),
                $attendee->getEmail(),
                $attendee->getFullName(),
            ),
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitBadgeScanApiController@checkIn",
            $params,
            [],
            [],
            [],
            $headers,
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

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitBadgeScanApiController@getAllBySummit",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $page = json_decode($content);
        $this->assertTrue(!is_null($page));
        return $page;
    }

    public function testGetSummitBadgeScan(){
        $badge_scan = $this->testAddBadgeScan();

        $params = [
            'id'      =>  self::$summit->getId(),
            'scan_id' =>  $badge_scan->id,
            'expand'  => 'sponsor,badge,badge.ticket,badge.ticket.owner,extra_question_answers'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitBadgeScanApiController@get",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $scan = json_decode($content);
        $this->assertTrue(!is_null($scan));
        return $scan;
    }

    public function testExportSummitBadgeScans(){

        $params = [
            'id'    =>  self::$summit->getId(),
            'columns'  => 'scan_date,attendee_first_name,attendee_last_name,attendee_email,attendee_company',
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitBadgeScanApiController@getAllBySummitCSV",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $this->assertNotEmpty($content);
    }

    public function testExportSummitBadgeScansWithReportSettingsRestriction(){

        $sponsor = self::$summit->getSummitSponsors()[0];
        if (!$sponsor instanceof Sponsor) self::fail();

        $sponsor_question = $sponsor->getExtraQuestions()[0];
        if (!$sponsor_question instanceof SummitSponsorExtraQuestionType) self::fail();

        $params = [
            'id'    =>  self::$summit->getId(),
            'columns'  => 'scan_date,attendee_first_name,attendee_last_name,attendee_email,attendee_company',
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
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
            $headers,
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
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $this->assertNotEmpty($content);
        $this->assertTrue(str_contains($content, AbstractSerializer::getCSVLabel($sponsor_question->getLabel())));
        $this->assertTrue(str_contains($content, 'scan_date'));
    }

    public function testExportSummitBadgeScansWithAllReportSettingsRestriction(){

        $sponsor = self::$summit->getSummitSponsors()[0];
        if (!$sponsor instanceof Sponsor) self::fail();

        $sponsor_question = $sponsor->getExtraQuestions()[0];
        if (!$sponsor_question instanceof SummitSponsorExtraQuestionType) self::fail();

        $params = [
            'id'    =>  self::$summit->getId(),
            'columns'  => 'scan_date,attendee_first_name,attendee_last_name,attendee_email,attendee_company',
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $data = [
            'allowed_columns' => [
                SummitLeadReportSetting::AttendeeExtraQuestionsKey => [],
                SummitLeadReportSetting::SponsorExtraQuestionsKey => []
            ]
        ];

        $this->action(
            "PUT",
            "OAuth2SummitApiController@updateLeadReportSettings",
            $params,
            [],
            [],
            [],
            $headers,
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
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $this->assertNotEmpty($content);
        $this->assertTrue(!str_contains($content, 'scan_date'));
    }
}