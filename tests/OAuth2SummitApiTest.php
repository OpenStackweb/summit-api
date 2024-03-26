<?php namespace Tests;
/**
 * Copyright 2015 OpenStack Foundation
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

use App\Models\Foundation\Summit\IStatsConstants;
use LaravelDoctrine\ORM\Facades\EntityManager;
use Illuminate\Http\UploadedFile;
use App\Services\Apis\ExternalScheduleFeeds\IExternalScheduleFeedFactory;
use App\Models\Foundation\Main\IGroup;
use models\summit\SummitLeadReportSetting;

/**
 * Class OAuth2SummitApiTest
 */
final class OAuth2SummitApiTest extends ProtectedApiTest
{

    use InsertSummitTestData;

    protected function setUp():void
    {
        parent::setUp();
        self::insertSummitTestData();
    }

    public function tearDown():void
    {
        self::clearSummitTestData();
        \Mockery::close();
    }

    public function createApplication()
    {
        $app = parent::createApplication();

        $fileUploaderMock = \Mockery::mock(\App\Http\Utils\IFileUploader::class)
            ->shouldIgnoreMissing();

        $fileUploaderMock->shouldReceive('build')->andReturn(new \models\main\File());

        $app->instance(\App\Http\Utils\IFileUploader::class, $fileUploaderMock);

        return $app;
    }

    public function testGetSummits()
    {

        $start = time();
        $params = ['relations'=>'none'];

        $headers = array("HTTP_Authorization" => " Bearer " . $this->access_token);
        $response = $this->action(
            "GET",
            "OAuth2SummitApiController@getSummits",
            $params,
            array(),
            array(),
            array(),
            $headers
        );

        $content = $response->getContent();
        $summits = json_decode($content);
        $end   = time();
        $delta = $end - $start;
        echo "execution call " . $delta . " seconds ...";
        $this->assertTrue(!is_null($summits));
        $this->assertResponseStatus(200);
    }

    public function testGetAllSummits()
    {

        $start = time();
        $params = [
            'relations' => 'none',
            'expand'    => 'none',
        ];

        $headers = ["HTTP_Authorization" => " Bearer " . $this->access_token];
        $response = $this->action(
            "GET",
            "OAuth2SummitApiController@getAllSummits",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $summits = json_decode($content);
        $end   = time();
        $delta = $end - $start;
        echo "execution call " . $delta . " seconds ...";
        $this->assertTrue(!is_null($summits));
        $this->assertTrue($summits->total == 1);
        $this->assertResponseStatus(200);
    }

    public function testGetAllSummitsNoPermissions()
    {
        self::setMemberDefaultGroup(IGroup::SummitAdministrators);

        $start = time();
        $params = [
            'relations' => 'none',
            'expand'    => 'none',
        ];

        $headers = ["HTTP_Authorization" => " Bearer " . $this->access_token];
        $response = $this->action(
            "GET",
            "OAuth2SummitApiController@getAllSummits",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $summits = json_decode($content);
        $end   = time();
        $delta = $end - $start;
        echo "execution call " . $delta . " seconds ...";
        $this->assertResponseStatus(403);
    }

    public function testGetAllSummitsAndPaymentProfiles()
    {
        $start = time();
        $params = [
            'relations' => 'payment_profiles',
            'expand'    => 'none',
        ];

        $headers = ["HTTP_Authorization" => " Bearer " . $this->access_token];
        $response = $this->action(
            "GET",
            "OAuth2SummitApiController@getAllSummits",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $summits = json_decode($content);
        $end   = time();
        $delta = $end - $start;
        echo "execution call " . $delta . " seconds ...";
        $this->assertTrue(!is_null($summits));
        $this->assertResponseStatus(200);
    }

    public function testGetSummit($summit_id = 31)
    {

        $params = [
            //'expand' => 'schedule',
            'id'     => $summit_id
        ];

        $headers = array("HTTP_Authorization" => " Bearer " . $this->access_token);
        $start = time();
        $response = $this->action(
            "GET",
            "OAuth2SummitApiController@getSummit",
            $params,
            array(),
            array(),
            array(),
            $headers
        );
        $end   = time();
        $delta = $end - $start;
        echo "execution call " . $delta . " seconds ...";
        $content = $response->getContent();
        $summit = json_decode($content);
        $this->assertTrue(!is_null($summit));
        $this->assertResponseStatus(200);

        $response = $this->action(
            "GET",
            "OAuth2SummitApiController@getSummit",
            $params,
            array(),
            array(),
            array(),
            $headers
        );

        $content = $response->getContent();
        $summit  = json_decode($content);
        $this->assertTrue(!is_null($summit));
        $this->assertTrue(count($summit->schedule) > 0);
        $this->assertResponseStatus(200);
    }

    public function testGetSummit2($summit_id = 12)
    {

        $params = [

            'expand' => 'event_types,tracks',
            'id'     => $summit_id
        ];

        $headers = array("HTTP_Authorization" => " Bearer " . $this->access_token);
        $start = time();
        $response = $this->action(
            "GET",
            "OAuth2SummitApiController@getSummit",
            $params,
            array(),
            array(),
            array(),
            $headers
        );
        $end   = time();
        $delta = $end - $start;
        echo "execution call " . $delta . " seconds ...";
        $content = $response->getContent();
        $summit = json_decode($content);
        $this->assertTrue(!is_null($summit));
        $this->assertResponseStatus(200);

        $response = $this->action(
            "GET",
            "OAuth2SummitApiController@getSummit",
            $params,
            array(),
            array(),
            array(),
            $headers
        );

        $content = $response->getContent();
        $summit  = json_decode($content);
        $this->assertTrue(!is_null($summit));
        $this->assertTrue(count($summit->schedule) > 0);
        $this->assertResponseStatus(200);
    }

    public function testAddSummitAlreadyExistsName(){
        $params = [
        ];

        $data = [
            'name'         => 'Vancouver, BC',
            'start_date'   => 1522853212,
            'end_date'     => 1542853212,
            'time_zone_id' => 'America/Argentina/Buenos_Aires',
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitApiController@addSummit",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(412);
    }

    public function testAddSummitFeedNull(){
        $params = [
        ];
        $name        = str_random(16).'_summit';
        $data = [
            'name'         => $name,
            'start_date'   => 1522853212,
            'end_date'     => 1542853212,
            'time_zone_id' => 'America/Argentina/Buenos_Aires',
            'submission_begin_date' => null,
            'submission_end_date' => null,
            'api_feed_type' => null,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitApiController@addSummit",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $summit = json_decode($content);
        $this->assertTrue(!is_null($summit));
        return $summit;
    }

    public function testAddSummitFeedEmpty(){
        $params = [
        ];
        $name        = str_random(16).'_summit';
        $data = [
            'name'         => $name,
            'start_date'   => 1522853212,
            'end_date'     => 1542853212,
            'time_zone_id' => 'America/Argentina/Buenos_Aires',
            'submission_begin_date' => null,
            'submission_end_date' => null,
            'api_feed_type' => '',
            'api_feed_url'  =>  '',
            'api_feed_key'  => ''
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitApiController@addSummit",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $summit = json_decode($content);
        $this->assertTrue(!is_null($summit));
        return $summit;
    }

    public function testAddSummit(){
        $params = [
        ];
        $name        = str_random(16).'_summit';
        $data = [
            'name'         => $name,
            'start_date'   => 1522853212,
            'end_date'     => 1542853212,
            'time_zone_id' => 'America/Argentina/Buenos_Aires',
            'submission_begin_date' => null,
            'submission_end_date' => null,
            'api_feed_type' => IExternalScheduleFeedFactory::SchedType,
            'api_feed_url'  =>  'https://localhost.com',
            'api_feed_key'  => 'secret'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitApiController@addSummit",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $summit = json_decode($content);
        $this->assertTrue(!is_null($summit));
        return $summit;
    }

    public function testAddSummitFeedType412(){
        $params = [
        ];
        $name        = str_random(16).'_summit';
        $data = [
            'name'         => $name,
            'start_date'   => 1522853212,
            'end_date'     => 1542853212,
            'time_zone_id' => 'America/Argentina/Buenos_Aires',
            'submission_begin_date' => null,
            'submission_end_date' => null,
            'api_feed_type' => IExternalScheduleFeedFactory::SchedType,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitApiController@addSummit",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(412);
    }

    public function testUpdateSummitAlreadyActiveError(){
        $summit = $this->testAddSummit();
        $params = [
            'id' => $summit->id
        ];
        $data = [
             'active' => 1
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitApiController@updateSummit",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(412);
    }

    public function testUpdateSummitTitle(){
        $summit = $this->testAddSummit();
        $params = [
            'id' => $summit->id
        ];
        $data = [
            'name' => $summit->name.' update!'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitApiController@updateSummit",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $summit = json_decode($content);
        $this->assertTrue(!is_null($summit));

        return $summit;
    }

    public function testDeleteSummit(){

        $summit = $this->testAddSummit();
        $params = [
            'id' => 31
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitApiController@deleteSummit",
            $params,
            [],
            [],
            [],
            $headers

        );

        $content = $response->getContent();
        $this->assertResponseStatus(204);

    }

    public function testGetSummitMin($summit_id = 23)
    {

        $params = array
        (
            'id'     => $summit_id,
            'expand' =>'event_types',
        );

        $headers = array("HTTP_Authorization" => " Bearer " . $this->access_token);
        $start = time();
        $response = $this->action(
            "GET",
            "OAuth2SummitApiController@getSummit",
            $params,
            array(),
            array(),
            array(),
            $headers
        );
        $end   = time();
        $delta = $end - $start;
        echo "execution call " . $delta . " seconds ...";
        $content = $response->getContent();
        $summit = json_decode($content);
        $this->assertTrue(!is_null($summit));
        $this->assertResponseStatus(200);

        $response = $this->action(
            "GET",
            "OAuth2SummitApiController@getSummit",
            $params,
            array(),
            array(),
            array(),
            $headers
        );

        $content = $response->getContent();
        $summit  = json_decode($content);
        $this->assertTrue(!is_null($summit));
        $this->assertResponseStatus(200);
    }

    public function testGetCurrentSummit()
    {

        $params = array
        (
           // 'expand' => 'schedule',
            'id'     => self::$summit->getId()
        );

        $headers = array("HTTP_Authorization" => " Bearer " . $this->access_token);
        $response = $this->action(
            "GET",
            "OAuth2SummitApiController@getSummit",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $summit = json_decode($content);
        $this->assertTrue(!is_null($summit));
    }

    public function testGetCurrentSummitRegStats()
    {

        $params = array
        (
            'id' => self::$summit->getId(),
            'filter' => [
                'start_date>=1661449232',
               // 'end_date<=1661459232',
            ]
        );

        $headers = array("HTTP_Authorization" => " Bearer " . $this->access_token);
        $response = $this->action(
            "GET",
            "OAuth2SummitApiController@getAllSummitByIdOrSlugRegistrationStats",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $stats = json_decode($content);
        $this->assertTrue(!is_null($stats));
    }

    public function testGetAttendeesCheckinsOverTimeStats()
    {
        $params = array
        (
            'id' => self::$summit->getId(),
            'page'     => 1,
            'per_page' => 5,
            'filter' => [
                'start_date>=1688578812',
                'end_date<=1688924412',
            ],
            'group_by' => IStatsConstants::GroupByHour
        );

        $headers = array("HTTP_Authorization" => " Bearer " . $this->access_token);
        $response = $this->action(
            "GET",
            "OAuth2SummitApiController@getAttendeesCheckinsOverTimeStats",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $stats = json_decode($content);
        $this->assertTrue(!is_null($stats));
    }

    public function testGetCurrentSummitSpeakers()
    {
        $params = [

            'id'       => self::$summit->getId(),
            'page'     => 1,
            'per_page' => 50,
            'order'    => '+first_name,-last_name'
        ];

        $headers = array("HTTP_Authorization" => " Bearer " . $this->access_token);
        $response = $this->action(
            "GET",
            "OAuth2SummitSpeakersApiController@getSpeakers",
            $params,
            array(),
            array(),
            array(),
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $speakers = json_decode($content);
        $this->assertTrue(!is_null($speakers));
    }

    public function testAllSpeakers()
    {
        $params = [

            'page'     => 1,
            'per_page' => 15,
            'filter'   => 'first_name=@John,last_name=@Bryce,email=@sebastian@',
            'order'    => '+first_name,-last_name'
        ];

        $headers = array("HTTP_Authorization" => " Bearer " . $this->access_token);
        $response = $this->action(
            "GET",
            "OAuth2SummitSpeakersApiController@getAll",
            $params,
            array(),
            array(),
            array(),
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $speakers = json_decode($content);
        $this->assertTrue(!is_null($speakers));
    }

    public function testAllSpeakersFilterByFullName()
    {
        $params = [

            'page'     => 1,
            'per_page' => 15,
            'filter'   => 'full_name=@Bryce',
            'order'    => '+first_name,-last_name'
        ];

        $headers = array("HTTP_Authorization" => " Bearer " . $this->access_token);
        $response = $this->action(
            "GET",
            "OAuth2SummitSpeakersApiController@getAll",
            $params,
            array(),
            array(),
            array(),
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $speakers = json_decode($content);
        $this->assertTrue(!is_null($speakers));
    }

    public function testCurrentSummitMyAttendeeFail404()
    {
        App::singleton('App\Models\ResourceServer\IAccessTokenService', 'AccessTokenServiceStub2');

        $params = array
        (
            'expand'       => 'schedule',
            'id'           => 6,
            'attendee_id'  => 'me',
            'access_token' => $this->access_token
        );

        $headers  = array("HTTP_Authorization" => " Bearer " . $this->access_token);
        $response = $this->action(
            "GET",
            "OAuth2SummitAttendeesApiController@getAttendee",
            $params,
            array(),
            array(),
            array(),
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(404);
    }

    public function testCurrentSummitMyAttendeeOK()
    {
        $params = array
        (
            'expand' => 'schedule,ticket_type,speaker,feedback',
            'id' => 6,
            'attendee_id' => 1215
        );

        $headers = array("HTTP_Authorization" => " Bearer " . $this->access_token);
        $response = $this->action(
            "GET",
            "OAuth2SummitAttendeesApiController@getAttendee",
            $params,
            array(),
            array(),
            array(),
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $attendee = json_decode($content);
        $this->assertTrue(!is_null($attendee));
    }

    public function testCurrentSummitMyAttendeeSchedule()
    {
        $params = array
        (
            'id' => 22,
            'attendee_id' => 'me'
        );

        $headers = array("HTTP_Authorization" => " Bearer " . $this->access_token);
        $response = $this->action(
            "GET",
            "OAuth2SummitAttendeesApiController@getAttendeeSchedule",
            $params,
            array(),
            array(),
            array(),
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $attendee = json_decode($content);
        $this->assertTrue(!is_null($attendee));
    }

    public function testCurrentSummitMyAttendeeAddToSchedule($event_id = 18845, $summit_id = 22)
    {
        $params = array
        (
            'id'          => $summit_id,
            'attendee_id' => 'me',
            'event_id'    => $event_id
        );

        $headers = array("HTTP_Authorization" => " Bearer " . $this->access_token);
        $response = $this->action(
            "POST",
            "OAuth2SummitAttendeesApiController@addEventToAttendeeSchedule",
            $params,
            array(),
            array(),
            array(),
            $headers
        );
        $content = $response->getContent();
        $this->assertResponseStatus(201);
    }

    public function testCurrentSummitMyAttendeeScheduleUnset($event_id = 18845, $summit_id = 22)
    {
        //$this->testCurrentSummitMyAttendeeAddToSchedule($event_id, $summit_id);
        $params = array
        (
            'id' => $summit_id,
            'attendee_id' => 'me',
            'event_id' => $event_id
        );

        $headers = array("HTTP_Authorization" => " Bearer " . $this->access_token);
        $response = $this->action(
            "DELETE",
            "OAuth2SummitAttendeesApiController@removeEventFromAttendeeSchedule",
            $params,
            array(),
            array(),
            array(),
            $headers
        );
        $content = $response->getContent();
        $this->assertResponseStatus(204);
    }


    public function testGetMySpeakerFromCurrentSummit()
    {

        $params = array
        (
            'expand' => 'presentations',
            'id' => 6,
            'speaker_id' => 'me'
        );

        $headers = array("HTTP_Authorization" => " Bearer " . $this->access_token);
        $response = $this->action(
            "GET",
            "OAuth2SummitSpeakersApiController@getSpeaker",
            $params,
            array(),
            array(),
            array(),
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $speaker = json_decode($content);
        $this->assertTrue(!is_null($speaker));
    }

    public function testAllEventsByEventType()
    {
        $params = array
        (
            'id' => 'current',
            'expand' => 'feedback',
            'filter' => array
            (
                'event_type_id==4',
                'summit_id==6',
            ),
        );

        $headers = array
        (
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        );

        $response = $this->action
        (
            "GET",
            "OAuth2SummitApiController@getAllEvents",
            $params,
            array(),
            array(),
            array(),
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);

        $events = json_decode($content);
        $this->assertTrue(!is_null($events));
    }

    public function testGetEntityEventsFromCurrentSummit()
    {
        //$this->testGetCurrentSummit(22);

        $params = array
        (
            'id'        => '22',
            'from_date' => 1460148342,
            'limit'     => 100
        );

        $headers = array
        (
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        );

        $response = $this->action
        (
            "GET",
            "OAuth2SummitApiController@getSummitEntityEvents",
            $params,
            array(),
            array(),
            array(),
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);

        $events = json_decode($content);
        $this->assertTrue(!is_null($events));
    }

    public function testGetEntityEventsFromCurrentSummitFromGivenDate()
    {
        $params = array
        (
            'id'        => 7,
            'from_date' => 1471565531,
            'limit'     => 100
        );

        $headers = array
        (
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        );

        $response = $this->action
        (
            "GET",
            "OAuth2SummitApiController@getSummitEntityEvents",
            $params,
            array(),
            array(),
            array(),
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);

        $events = json_decode($content);
        $this->assertTrue(!is_null($events));
    }

    public function testGetEntityEventsFromCurrentSummitGreaterThanGivenID($summit_id = 7, $last_event_id = 702471)
    {
        $params = array
        (
            'id'            => $summit_id,
            'last_event_id' => $last_event_id,
            'limit'         => 100
        );

        $headers = array
        (
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        );

        $response = $this->action
        (
            "GET",
            "OAuth2SummitApiController@getSummitEntityEvents",
            $params,
            array(),
            array(),
            array(),
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);

        $events = json_decode($content);
        $this->assertTrue(!is_null($events));

        $params = array
        (
            'id'            => 6,
            'last_event_id' => 32795
        );

        $headers = array
        (
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        );

        $response = $this->action
        (
            "GET",
            "OAuth2SummitApiController@getSummitEntityEvents",
            $params,
            array(),
            array(),
            array(),
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);

        $events = json_decode($content);
        $this->assertTrue(!is_null($events));
    }

    public function testGetEntityEventsFromCurrentSummitGreaterThanGivenIDMax()
    {
        $params = array
        (
            'id' => 6,
            'last_event_id' => PHP_INT_MAX,
            'limit' => 250,
        );

        $headers = array
        (
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        );

        $response = $this->action
        (
            "GET",
            "OAuth2SummitApiController@getSummitEntityEvents",
            $params,
            array(),
            array(),
            array(),
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);

        $events = json_decode($content);
        $this->assertTrue(!is_null($events));

        $params = array
        (
            'id' => 6,
            'last_event_id' => 32795
        );

        $headers = array
        (
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        );

        $response = $this->action
        (
            "GET",
            "OAuth2SummitApiController@getSummitEntityEvents",
            $params,
            array(),
            array(),
            array(),
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);

        $events = json_decode($content);
        $this->assertTrue(!is_null($events));
    }

    public function testGetCurrentSummitExternalOrder()
    {
        $params = array
        (
            'id' => 6,
            'external_order_id' => 488240765
        );

        $headers = array
        (
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        );

        $response = $this->action
        (
            "GET",
            "OAuth2SummitApiController@getExternalOrder",
            $params,
            array(),
            array(),
            array(),
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);

        $order = json_decode($content);
        $this->assertTrue(!is_null($order));
    }

    public function testGetCurrentSummitExternalOrderNonExistent()
    {
        $params = array
        (
            'id' => 6,
            'external_order_id' => 'ADDDD'
        );

        $headers = array
        (
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        );

        $response = $this->action
        (
            "GET",
            "OAuth2SummitApiController@getExternalOrder",
            $params,
            array(),
            array(),
            array(),
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(404);

        $order = json_decode($content);
        $this->assertTrue(!is_null($order));
    }

    public function testCurrentSummitConfirmExternalOrder()
    {
        $params = array
        (
            'id' => 6,
            'external_order_id' => 488240765,
            'external_attendee_id' => 615935124
        );

        $headers = array
        (
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        );

        $response = $this->action
        (
            "POST",
            "OAuth2SummitApiController@confirmExternalOrderAttendee",
            $params,
            array(),
            array(),
            array(),
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);

        $attendee = json_decode($content);
        $this->assertTrue(!is_null($attendee));
    }

    public function testAddPresentationVideo($summit_id = 25)
    {
        $repo   =  EntityManager::getRepository(\models\summit\Summit::class);
        $summit = $repo->getById($summit_id);
        $presentation = $summit->getPublishedPresentations()[0];
        $params = array
        (
            'id' => $summit_id,
            'presentation_id' => $presentation->getId()
        );

        $headers = array
        (
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        );

        $video_data = array
        (
            'youtube_id' => 'cpHa7kSOur0',
            'name' => 'test video',
            'description' => 'test video',
            'display_on_site' => true,
        );

        $response = $this->action
        (
            "POST",
            "OAuth2PresentationApiController@addVideo",
            $params,
            array(),
            array(),
            array(),
            $headers,
            json_encode($video_data)
        );

        $video_id = $response->getContent();
        $this->assertResponseStatus(201);
        return intval($video_id);
    }

    public function testUpdatePresentationVideo()
    {
        $video_id = $this->testAddPresentationVideo($summit_id = 25);

        $params = array
        (
            'id' => 7,
            'presentation_id' => 15404,
            'video_id' => $video_id
        );

        $headers = array
        (
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        );

        $video_data = array
        (
            'youtube_id' => 'cpHa7kSOur0',
            'name' => 'test video update',
        );

        $response = $this->action
        (
            "PUT",
            "OAuth2PresentationApiController@updateVideo",
            $params,
            array(),
            array(),
            array(),
            $headers,
            json_encode($video_data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(204);

    }

    public function testGetPresentationVideos()
    {

        //$video_id = $this->testAddPresentationVideo(7, 15404);

        $params = array
        (
            'id' => 7,
            'presentation_id' => 15404,
        );

        $headers = array
        (
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        );

        $response = $this->action
        (
            "GET",
            "OAuth2PresentationApiController@getPresentationVideos",
            $params,
            array(),
            array(),
            array(),
            $headers

        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);

    }

    public function testDeletePresentationVideo()
    {
        $video_id = $this->testAddPresentationVideo($summit_id = 25);

        $params = array
        (
            'id' => 7,
            'presentation_id' => 15404,
            'video_id' => $video_id
        );

        $headers = array
        (
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        );

        $response = $this->action
        (
            "DELETE",
            "OAuth2PresentationApiController@deleteVideo",
            $params,
            array(),
            array(),
            array(),
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(204);

    }

    public function testAdd2Favorite($summit_id = 22, $event_id = 18719){
        $params = array
        (
            'id'          => $summit_id,
            'member_id'   => 'me',
            'event_id'    => $event_id
        );

        $headers = array("HTTP_Authorization" => " Bearer " . $this->access_token);
        $response = $this->action(
            "POST",
            "OAuth2SummitMembersApiController@addEventToMemberFavorites",
            $params,
            array(),
            array(),
            array(),
            $headers
        );
        $content = $response->getContent();
        $this->assertResponseStatus(201);
    }

    public function testRemoveFromFavorites($summit_id = 22, $event_id = 18719){

         $params = array
         (
             'id'          => $summit_id,
             'member_id'   => 'me',
             'event_id'    => $event_id
         );

        $headers = array("HTTP_Authorization" => " Bearer " . $this->access_token);
        $response = $this->action(
            "DELETE",
            "OAuth2SummitMembersApiController@removeEventFromMemberFavorites",
            $params,
            array(),
            array(),
            array(),
            $headers
        );
        $content = $response->getContent();
        $this->assertResponseStatus(204);
    }

    public function testGetMyFavorites(){

          $params = [

              'member_id' => 'me',
              'id'        => 7,
          ];

        $headers = array("HTTP_Authorization" => " Bearer " . $this->access_token);
        $response = $this->action(
            "GET",
            "OAuth2SummitMembersApiController@getMemberFavoritesSummitEvents",
            $params,
            array(),
            array(),
            array(),
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $favorites = json_decode($content);
        $this->assertTrue(!is_null($favorites));
    }

    public function testGetMyMemberFromCurrentSummit()
    {

        $params = [

            'expand'    => 'attendee,speaker,feedback,groups,presentations',
            'member_id' => 'me',
            'id'        => 22,
        ];

        $headers = array("HTTP_Authorization" => " Bearer " . $this->access_token);
        $response = $this->action(
            "GET",
            "OAuth2SummitMembersApiController@getMyMember",
            $params,
            array(),
            array(),
            array(),
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $member = json_decode($content);
        $this->assertTrue(!is_null($member));
    }

    /**
     * @param int $summit_id
     */
    public function testGetMembersBySummit($summit_id = 27)
    {

        $params = [

            'expand'    => 'attendee,speaker,feedback,groups,presentations',
            'id'        => $summit_id,
            'filter'   => 'schedule_event_id==23828'
        ];

        $headers = ["HTTP_Authorization" => " Bearer " . $this->access_token];
        $response = $this->action(
            "GET",
            "OAuth2SummitMembersApiController@getAllBySummit",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $members = json_decode($content);
        $this->assertTrue(!is_null($members));
    }

    /**
     * @param int $summit_id
     */
    public function testGetMembersBySummitCSV($summit_id = 27)
    {

        $params = [

            'expand'    => 'attendee,speaker,feedback,groups,presentations',
            'id'        => $summit_id,
            'filter'   => 'schedule_event_id==24015',
            'columns'  => 'id,first_name,last_name,email,affiliations',
        ];

        $headers = ["HTTP_Authorization" => " Bearer " . $this->access_token];
        $response = $this->action(
            "GET",
            "OAuth2SummitMembersApiController@getAllBySummitCSV",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $csv = $content;
        $this->assertTrue(!empty($csv));
    }


    public function testCurrentSummitMyMemberFavorites()
    {
        $params = array
        (
            'id' => 22,
            'member_id' => 'me',
            'expand' => 'speakers',
        );

        $headers = array("HTTP_Authorization" => " Bearer " . $this->access_token);
        $response = $this->action(
            "GET",
            "OAuth2SummitMembersApiController@getMemberFavoritesSummitEvents",
            $params,
            array(),
            array(),
            array(),
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $favorites = json_decode($content);
        $this->assertTrue(!is_null($favorites));
    }

    public function testCurrentSummitMemberAddToSchedule($event_id = 18845, $summit_id = 22)
    {
        $params = array
        (
            'id'        => $summit_id,
            'member_id' => 'me',
            'event_id'  => $event_id
        );

        $headers = array("HTTP_Authorization" => " Bearer " . $this->access_token);
        $response = $this->action(
            "POST",
            "OAuth2SummitMembersApiController@addEventToMemberSchedule",
            $params,
            array(),
            array(),
            array(),
            $headers
        );
        $content = $response->getContent();
        $this->assertResponseStatus(201);
    }

    public function testCurrentSummitMemberScheduleUnset($event_id = 18845, $summit_id = 22)
    {
        $this->testCurrentSummitMemberAddToSchedule($event_id, $summit_id);
        $params = array
        (
            'id'        => $summit_id,
            'member_id' => 'me',
            'event_id'  => $event_id
        );

        $headers = array("HTTP_Authorization" => " Bearer " . $this->access_token);
        $response = $this->action(
            "DELETE",
            "OAuth2SummitMembersApiController@removeEventFromMemberSchedule",
            $params,
            array(),
            array(),
            array(),
            $headers
        );
        $content = $response->getContent();
        $this->assertResponseStatus(204);
    }

    public function testAddPresentationSlide($summit_id=25){

        $repo   =  EntityManager::getRepository(\models\summit\Summit::class);
        $summit = $repo->getById($summit_id);
        $presentation = $summit->getPublishedPresentations()[0];
        $params = array
        (
            'id' => $summit_id,
            'presentation_id' => $presentation->getId(),
        );

        $headers = array
        (
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "multipart/form-data; boundary=----WebKitFormBoundaryBkSYnzBIiFtZu4pb"
        );

        $video_data = array
        (
            'name' => 'test slide',
            'description' => 'test slide',
            'display_on_site' => true,
        );

        $response = $this->action
        (
            "POST",
            "OAuth2PresentationApiController@addPresentationSlide",
            $params,
            array(),
            array(),
            [
                'file' => UploadedFile::fake()->image('slide.pdf')
            ],
            $headers,
            json_encode($video_data)
        );

        $video_id = $response->getContent();
        $this->assertResponseStatus(201);
        return intval($video_id);
    }


    public function testAddPresentationSlideInvalidName($summit_id=25){

        $repo   =  EntityManager::getRepository(\models\summit\Summit::class);
        $summit = $repo->getById($summit_id);
        $presentation = $summit->getPublishedPresentations()[0];
        $params = array
        (
            'id' => $summit_id,
            'presentation_id' => $presentation->getId(),
        );

        $headers = array
        (
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        );

        $video_data = array
        (
            'name' => 'test slide',
            'description' => 'test slide',
            'display_on_site' => true,
        );

        $response = $this->action
        (
            "POST",
            "OAuth2PresentationApiController@addPresentationSlide",
            $params,
            array(),
            array(),
            [
                'file' => UploadedFile::fake()->image('IMG 0008 副本 白底.jpg')
            ],
            $headers,
            json_encode($video_data)
        );

        $video_id = $response->getContent();
        $this->assertResponseStatus(201);
        return intval($video_id);
    }

    public function testImportEventData(){
/*        $csv_content = <<<CSV
title,abstract,type,track,social_summary,allow_feedback,to_record,tags,speakers_names,speakers,start_date,end_date,is_published,selection_plan,attendees_expected_learnt,problem_addressed,location
test1,test abstract1,TEST PRESENTATION TYPE,DEFAULT TRACK,social test1,1,1,tag1|tag2|tag3,Sebas Marcet|Sebas 1 Marcet|Sebas 2 Marcet,smarcet@gmail.com|smarcet+1@gmail.com,smarcet+2@gmail.com,2020-01-01 13:00:00,2020-01-01 13:45:00,1,TEST_SELECTION_PLAN,DEFAULT TRACK,big things,world issues,TEST VENUE
test2,test abstract2,TEST PRESENTATION TYPE,DEFAULT TRACK,social test2,1,1,tag1|tag2,Sebas  Marcet,smarcet@gmail.com,2020-01-01 13:45:00,2020-01-01 14:45:00,1,TEST_SELECTION_PLAN,big things,world issues,TEST VENUE
test3,test abstract3,TEST PRESENTATION TYPE,DEFAULT TRACK,social test3,1,1,tag4,Sebas 2 Marcet,smarcet+2@gmail.com,2020-01-01 14:45:00,2020-01-01 15:45:00,1,TEST_SELECTION_PLAN,big things,world issues,
CSV;*/
$csv_content = <<<CSV
track,start_date,end_date,type,title,abstract,attendees_expected_learnt,social_summary ,speakers_names,speakers,selection_plan
Security,2020-11-12 8:00:00,2020-11-12 9:00:00,Presentation,Security Projects Alignment,"OCP-Security scope / threat model
Compare Resiliency approaches
General role of RoT
Alignment on security requirements across OCP Server sub-groups.",Cross-orgs alignment/sync on scope and approaches ,,JP Mon,jp@tipit.net,Draft Presentations Submissions
CSV;

        $path = "/tmp/events.csv";

        file_put_contents($path, $csv_content);

        $file = new UploadedFile($path, "events.csv", 'text/csv', null, true);

        $params = [
            'summit_id' => self::$summit->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitEventsApiController@importEventData",
            $params,
            [
                'send_speaker_email' => true,
            ],
            [],
            [
                'file' => $file,
            ],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
    }

    public function testGetCurrentSummitCompanies()
    {
        $params = [
            'id'       => self::$summit->getId(),
            'page'     => 1,
            'per_page' => 15,
            'filter'   => 'company_name==Intel',
        ];

        $headers = array("HTTP_Authorization" => " Bearer " . $this->access_token);
        $response = $this->action(
            "GET",
            "OAuth2SummitRegistrationCompaniesApiController@getAllBySummit",
            $params,
            array(),
            array(),
            array(),
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $speakers = json_decode($content);
        $this->assertTrue(!is_null($speakers));
    }

    public function testAddCompanyToSummit()
    {
        $params = array(
            'id'            => self::$summit->getId(),
            'company_id'    => 1,
        );

        $headers = array("HTTP_Authorization" => " Bearer " . $this->access_token);

        $response = $this->action(
            "PUT",
            "OAuth2SummitRegistrationCompaniesApiController@add",
            $params,
            array(),
            array(),
            array(),
            $headers
        );
        $content = $response->getContent();
        $this->assertResponseStatus(201);
    }

    public function testRemoveCompanyFromSummit()
    {
        $params = array(
            'id'            => self::$summit->getId(),
            'company_id'    => 1,
        );

        $headers = array("HTTP_Authorization" => " Bearer " . $this->access_token);

        $response = $this->action(
            "DELETE",
            "OAuth2SummitRegistrationCompaniesApiController@delete",
            $params,
            array(),
            array(),
            array(),
            $headers
        );
        $content = $response->getContent();
        $this->assertResponseStatus(204);
    }

    public function testGenerateQREncKey()
    {
        $params = [
            'id' => self::$summit->getId(),
        ];

        $headers = ["HTTP_Authorization" => " Bearer " . $this->access_token];

        $response = $this->action(
            "PUT",
            "OAuth2SummitApiController@generateQREncKey",
            $params,
            array(),
            array(),
            array(),
            $headers
        );
        $content = $response->getContent();
        $this->assertResponseStatus(201);
    }

    public function testAddLeadReportSettings(){

        $params = [
            'id' => self::$summit->getId(),
        ];

        $allowed_columns = [
            'scan_date',
            'attendee_first_name',
            'attendee_company',
            SummitLeadReportSetting::AttendeeExtraQuestionsKey => [
                [
                    'id'   => 392,
                    'name' => 'QUESTION1'
                ],
            ],
            SummitLeadReportSetting::SponsorExtraQuestionsKey => ['*']
        ];

        $data = [
            'allowed_columns' => $allowed_columns
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitApiController@addLeadReportSettings",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $lead_report_settings = json_decode($content);
        $this->assertNotNull($lead_report_settings);
        $this->assertSameSize($allowed_columns[SummitLeadReportSetting::AttendeeExtraQuestionsKey], $lead_report_settings->columns->attendee_extra_questions);
        return $lead_report_settings;
    }

    public function testUpdateLeadReportSettings(){
        $this->testAddLeadReportSettings();

        $params = [
            'id' => self::$summit->getId(),
        ];

        $allowed_columns = [
            'scan_date',
            SummitLeadReportSetting::AttendeeExtraQuestionsKey => [
                [
                    'id'   => 393,
                    'name' => 'QUESTION2'
                ],
            ],
            SummitLeadReportSetting::SponsorExtraQuestionsKey => ['*']
        ];

        $data = [
            'allowed_columns' => $allowed_columns
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"       => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitApiController@updateLeadReportSettings",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $lead_report_settings = json_decode($content);
        $this->assertEquals($allowed_columns[SummitLeadReportSetting::AttendeeExtraQuestionsKey][0]['id'], $lead_report_settings->columns->attendee_extra_questions[0]->id);
        return $lead_report_settings;
    }

    public function testGetLeadReportSettingsMetadata(){

        $params = [
            'id' => self::$summit->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitApiController@getLeadReportSettingsMetadata",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $metadata = json_decode($content);
        self::assertEquals('*', $metadata->extra_questions[0]);
    }
}