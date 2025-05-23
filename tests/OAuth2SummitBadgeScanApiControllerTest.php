<?php namespace Tests;
use App\Models\Foundation\Main\IGroup;
use App\Models\Foundation\Summit\ExtraQuestions\SummitSponsorExtraQuestionType;
use LaravelDoctrine\ORM\Facades\EntityManager;
use Libs\ModelSerializers\AbstractSerializer;
use models\summit\Sponsor;
use models\summit\Summit;
use models\summit\SummitLeadReportSetting;

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

/**
 * Class OAuth2SummitBadgeScanApiControllerTest
 */
class OAuth2SummitBadgeScanApiControllerTest extends ProtectedApiTestCase
{
    use InsertSummitTestData;

    use InsertMemberTestData;

    protected function setUp():void
    {
        parent::setUp();
        self::insertMemberTestData(IGroup::TrackChairs);
        self::$defaultMember = self::$member;
        self::insertSummitTestData();
    }

    protected function tearDown():void
    {
        self::clearSummitTestData();
        self::clearMemberTestData();
        parent::tearDown();
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

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitBadgeScanApiController@add",
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