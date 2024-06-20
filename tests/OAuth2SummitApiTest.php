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
use App\Models\Foundation\Summit\ISummitExternalScheduleFeedType;
use App\Models\ResourceServer\IAccessTokenService;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\App;
use App\Models\Foundation\Main\IGroup;
use models\summit\SummitLeadReportSetting;
use services\apis\IEventbriteAPI;

/**
 * Class OAuth2SummitApiTest
 */
final class OAuth2SummitApiTest extends ProtectedApiTest
{

    use InsertSummitTestData;

    use InsertOrdersTestData;

    protected function setUp():void
    {
        parent::setUp();
        self::$defaultMember = self::$member;
        self::$defaultMember2 = self::$member2;
        self::insertSummitTestData();
        self::InsertOrdersTestData();
    }

    public function tearDown():void
    {
        self::clearSummitTestData();
        parent::tearDown();
    }

    public function createApplication()
    {
        $app = parent::createApplication();

        $fileUploaderMock = \Mockery::mock(\App\Http\Utils\IFileUploader::class)
            ->shouldIgnoreMissing();

        $fileUploaderMock->shouldReceive('build')->andReturn(new \models\main\File());

        $app->instance(\App\Http\Utils\IFileUploader::class, $fileUploaderMock);

        $eventBriteMock = \Mockery::mock(IEventbriteAPI::class);

        $eventBriteMock->shouldReceive('getOrder')->withArgs(['123456'])->andReturn(
            [
                'attendees' => [
                    [
                        'ticket_class_id' => '123456',
                        'id' => '123456',
                        'profile' => [
                            'first_name' => 'John',
                            'last_name' => 'Doe',
                            'email' => 'test@test.com',
                            'company' => 'test',
                            'job_title' => 'test',
                        ],
                        'status' => 'placed',

                    ]
                ],
                'status' => 'placed',
                'event_id' => '123456'
            ]
        );

        $eventBriteMock->shouldReceive('getOrder')->withArgs(['12345678'])
            ->andThrow(new ClientException('Not Found',
                \Mockery::mock(\GuzzleHttp\Psr7\Request::class),
                \Mockery::mock(\GuzzleHttp\Psr7\Response::class,
                    ['getStatusCode' => 400])));

        $this->app->instance(IEventbriteAPI::class,  $eventBriteMock);

        return $app;
    }

    public function testGetSummits()
    {

        $start = time();
        $params = ['relations'=>'none'];

        $response = $this->action(
            "GET",
            "OAuth2SummitApiController@getSummits",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $data = json_decode($content);
        $end   = time();
        $delta = $end - $start;
        $this->assertTrue($delta <= 1);
        $this->assertTrue(!is_null($data));
        $this->assertResponseStatus(200);
    }

    public function testGenerateQREncKey()
    {
        $params = [
            'id' => self::$summit->getId(),
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitApiController@generateQREncKey",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );
        $this->assertResponseStatus(201);
        $content = $response->getContent();
        $data = json_decode($content);
        $this->assertTrue(!is_null($data));
    }

    public function testGetAllSummits()
    {

        $start = time();
        $params = [
            'relations' => 'none',
            'expand'    => 'none',
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitApiController@getAllSummits",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $data = json_decode($content);
        $end   = time();
        $delta = $end - $start;
        $this>self::assertTrue($delta <= 1);
        $this->assertTrue(!is_null($data));
        $this->assertTrue($data->total == 2);
        $this->assertResponseStatus(200);
    }

    public function testGetAllSummitsNoPermissions()
    {
        // override member idp default groups to empty
        App::singleton(IAccessTokenService::class, function () {
            $service =  new AccessTokenServiceStub([]);
            $service->setUserId(self::$member->getUserExternalId());
            $service->setUserExternalId(self::$member->getUserExternalId());
            return $service;
        });

        self::setMemberDefaultGroup(IGroup::SummitAdministrators);

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

        $this->assertResponseStatus(403);
    }

    public function testGetAllSummitsAndPaymentProfiles()
    {
        $start = time();
        $params = [
            'relations' => 'payment_profiles',
            'expand'    => 'none',
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitApiController@getAllSummits",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $data = json_decode($content);
        $end   = time();
        $delta = $end - $start;
        $this->assertTrue($delta <= 1);
        $this->assertTrue(!is_null($data));
        $this->assertResponseStatus(200);
    }

    public function testGetSummit()
    {

        $params = [
            'id'     => self::$summit->getId()
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitApiController@getSummit",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );
        $content = $response->getContent();
        $summit = json_decode($content);
        $this->assertTrue(!is_null($summit));
        $this->assertResponseStatus(200);

        $response = $this->action(
            "GET",
            "OAuth2SummitApiController@getSummit",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $summit  = json_decode($content);
        $this->assertTrue(!is_null($summit));
        $this->assertResponseStatus(200);
    }

    public function testGetSummit2()
    {

        $params = [

            'expand' => 'event_types,tracks',
            'id'     => self::$summit->getId()
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitApiController@getSummit",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );
        $content = $response->getContent();
        $summit = json_decode($content);
        $this->assertTrue(!is_null($summit));
        $this->assertResponseStatus(200);

        $response = $this->action(
            "GET",
            "OAuth2SummitApiController@getSummit",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $summit  = json_decode($content);
        $this->assertTrue(!is_null($summit));
        $this->assertTrue(count($summit->event_types) > 0);
        $this->assertTrue(count($summit->tracks) > 0);
        $this->assertResponseStatus(200);
    }


    public function testAddSummitAlreadyExistsName(){
        $params = [
        ];

        $data = [
            'name'         => self::$summit->getName(),
            'start_date'   => 1522853212,
            'end_date'     => 1542853212,
            'time_zone_id' => 'America/Argentina/Buenos_Aires',
        ];

        $this->action(
            "POST",
            "OAuth2SummitApiController@addSummit",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $this->assertResponseStatus(412);
    }

    public function testAddSummitFeedNull(){
        $params = [
        ];

        $name        = str_random(16).'_summit';
        $data = [
            'name'         => $name,
            'slug' => $name,
            'start_date'   => 1522853212,
            'end_date'     => 1562853212,
            'time_zone_id' => 'America/Argentina/Buenos_Aires',
            'submission_begin_date' => null,
            'submission_end_date' => null,
            'api_feed_type' => null,
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitApiController@addSummit",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
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
            'slug' => $name,
            'start_date'   => 1522853212,
            'end_date'     => 1542853212,
            'time_zone_id' => 'America/Argentina/Buenos_Aires',
            'submission_begin_date' => null,
            'submission_end_date' => null,
            'api_feed_type' => '',
            'api_feed_url'  =>  '',
            'api_feed_key'  => ''
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitApiController@addSummit",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
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
            'slug'        => $name,
            'start_date'   => 1522853212,
            'end_date'     => 1542853212,
            'time_zone_id' => 'America/Argentina/Buenos_Aires',
            'submission_begin_date' => null,
            'submission_end_date' => null,
            'api_feed_type' => ISummitExternalScheduleFeedType::SchedType,
            'api_feed_url'  =>  'https://localhost.com',
            'api_feed_key'  => 'secret'
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitApiController@addSummit",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
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
            'slug' => $name,
            'start_date'   => 1522853212,
            'end_date'     => 1542853212,
            'time_zone_id' => 'America/Argentina/Buenos_Aires',
            'submission_begin_date' => null,
            'submission_end_date' => null,
            'api_feed_type' =>  ISummitExternalScheduleFeedType::SchedType,
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitApiController@addSummit",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
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

        $response = $this->action(
            "PUT",
            "OAuth2SummitApiController@updateSummit",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
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
            'name' => $summit->name.' update!',
            'slug' => $summit->slug.' update!',
        ];


        $response = $this->action(
            "PUT",
            "OAuth2SummitApiController@updateSummit",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
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
            'id' => $summit->id
        ];

        $this->action(
            "DELETE",
            "OAuth2SummitApiController@deleteSummit",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $this->assertResponseStatus(204);
    }

    public function testGetCurrentSummit()
    {

        $params = [
            'id'     => self::$summit->getId()
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitApiController@getSummit",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $summit = json_decode($content);
        $this->assertTrue(!is_null($summit));
    }

    public function testGetCurrentSummitRegStats()
    {

        $params = [
            'id' => self::$summit->getId(),
            'filter' => [
                'start_date>='.self::$summit->getBeginDate()->getTimestamp(),
            ]
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitApiController@getAllSummitByIdOrSlugRegistrationStats",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
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

        $response = $this->action(
            "GET",
            "OAuth2SummitApiController@getAttendeesCheckinsOverTimeStats",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $stats = json_decode($content);
        $this->assertTrue(!is_null($stats));
    }

    public function testGetCurrentSummitExternalOrder()
    {
        $params = [
            'id' => self::$summit->getId(),
            'external_order_id' => "123456"
        ];

        $response = $this->action
        (
            "GET",
            "OAuth2SummitApiController@getExternalOrder",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);

        $order = json_decode($content);
        $this->assertTrue(!is_null($order));
    }


    public function testGetCurrentSummitExternalOrderNonExistent()
    {
        $params = [

            'id' => self::$summit->getId(),
            'external_order_id' => '12345678'
        ];

        $response = $this->action
        (
            "GET",
            "OAuth2SummitApiController@getExternalOrder",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(404);

        $order = json_decode($content);
        $this->assertTrue(!is_null($order));
    }

    public function testCurrentSummitConfirmExternalOrder()
    {
        $params = [

            'id' => self::$summit->getId(),
            'external_order_id' => '123456',
            'external_attendee_id' => '123456'
        ];


        $response = $this->action
        (
            "POST",
            "OAuth2SummitApiController@confirmExternalOrderAttendee",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $this->assertResponseStatus(412);

    }

    public function testAdd2Favorite(){
        $params = [
            'id'          => self::$summit->getId(),
            'member_id'   => 'me',
            'event_id'    => self::$presentations[0]->getId()
        ];

        $this->action(
            "POST",
            "OAuth2SummitMembersApiController@addEventToMemberFavorites",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $this->assertResponseStatus(201);
    }

    public function testRemoveFromFavorites(){

        $params = [
            'id'          => self::$summit->getId(),
            'member_id'   => 'me',
            'event_id'    => self::$presentations[0]->getId()
        ];

        $this->action(
            "POST",
            "OAuth2SummitMembersApiController@addEventToMemberFavorites",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $this->assertResponseStatus(201);

        $response = $this->action(
            "DELETE",
            "OAuth2SummitMembersApiController@removeEventFromMemberFavorites",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );
        $this->assertResponseStatus(204);
    }

    public function testGetMyFavorites(){

        $params = [
            'id'          => self::$summit->getId(),
            'member_id'   => 'me',
            'event_id'    => self::$presentations[0]->getId()
        ];

        $this->action(
            "POST",
            "OAuth2SummitMembersApiController@addEventToMemberFavorites",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $this->assertResponseStatus(201);

        $params = [

              'member_id' => 'me',
              'id'        => self::$summit->getId(),
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitMembersApiController@getMemberFavoritesSummitEvents",
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
        $this->assertTrue($data->total > 0);
    }

    public function testGetMyMemberFromCurrentSummit()
    {

        $params = [

            'expand'    => 'attendee,speaker,feedback,groups,presentations',
            'member_id' => 'me',
            'id'        => self::$summit->getId(),
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitMembersApiController@getMyMember",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $member = json_decode($content);
        $this->assertTrue(!is_null($member));
    }

    public function testGetMembersBySummit()
    {

        $params = [

            'expand'    => 'attendee,speaker,feedback,groups,presentations',
            'id'        => self::$summit->getId(),
            'filter'   => 'schedule_event_id==23828'
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitMembersApiController@getAllBySummit",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $members = json_decode($content);
        $this->assertTrue(!is_null($members));
    }

    public function testGetMembersBySummitCSV()
    {

        $params = [

            'expand'    => 'attendee,speaker,feedback,groups,presentations',
            'id'        => self::$summit->getId(),
            'columns'  => 'id,first_name,last_name,email,affiliations',
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitMembersApiController@getAllBySummitCSV",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $csv = $content;
        $this->assertTrue(!empty($csv));
    }

    public function testCurrentSummitMyMemberFavorites()
    {
        $params = [
            'id' => self::$summit->getId(),
            'member_id' => 'me',
            'expand' => 'speakers',
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitMembersApiController@getMemberFavoritesSummitEvents",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $favorites = json_decode($content);
        $this->assertTrue(!is_null($favorites));
    }

    public function testCurrentSummitMemberAddToSchedule()
    {
        $params = [
            'id'        => self::$summit->getId(),
            'member_id' => 'me',
            'event_id'  => self::$presentations[0]->getId()
        ];

         $this->action(
            "POST",
            "OAuth2SummitMembersApiController@addEventToMemberSchedule",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );
        $this->assertResponseStatus(201);
    }

    public function testCurrentSummitMemberScheduleUnset()
    {
        $params = [
            'id'        => self::$summit->getId(),
            'member_id' => 'me',
            'event_id'  => self::$presentations[0]->getId()
        ];

        $this->action(
            "POST",
            "OAuth2SummitMembersApiController@addEventToMemberSchedule",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );
        $this->assertResponseStatus(201);


        $this->action(
            "DELETE",
            "OAuth2SummitMembersApiController@removeEventFromMemberSchedule",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );
        $this->assertResponseStatus(204);
    }

    public function testGetCurrentSummitCompanies()
    {
        $params = [
            'id'            => self::$summit->getId(),
            'company_id'    => self::$companies[0]->getId(),
        ];

        $this->action(
            "PUT",
            "OAuth2SummitRegistrationCompaniesApiController@add",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $this->assertResponseStatus(201);
        $params = [
            'id'       => self::$summit->getId(),
            'page'     => 1,
            'per_page' => 15,
            'filter'   => 'name=='. self::$companies[0]->getName(),
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitRegistrationCompaniesApiController@getAllBySummit",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $speakers = json_decode($content);
        $this->assertTrue(!is_null($speakers));
    }

    public function testAddCompanyToSummit()
    {
        $params = [
            'id'            => self::$summit->getId(),
            'company_id'    => self::$companies[0]->getId(),
        ];

        $this->action(
            "PUT",
            "OAuth2SummitRegistrationCompaniesApiController@add",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $this->assertResponseStatus(201);
    }

    public function testRemoveCompanyFromSummit()
    {
        $params = [
            'id'            => self::$summit->getId(),
            'company_id'    => self::$companies[0]->getId(),
        ];

        $this->action(
            "PUT",
            "OAuth2SummitRegistrationCompaniesApiController@add",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $this->assertResponseStatus(201);

        $response = $this->action(
            "DELETE",
            "OAuth2SummitRegistrationCompaniesApiController@delete",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $this->assertResponseStatus(204);
    }

    public function testUpdateLeadReportSettings(){

        $params = [
            'id' => self::$summit->getId()
        ];

        $allowed_columns = [
            'scan_date',
            SummitLeadReportSetting::AttendeeExtraQuestionsKey => ['*'],
            SummitLeadReportSetting::SponsorExtraQuestionsKey => ['*']
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
        $this->assertResponseStatus(201);
        $lead_report_settings = json_decode($content);
        $this->assertEquals($allowed_columns[SummitLeadReportSetting::AttendeeExtraQuestionsKey][0], $lead_report_settings->columns->attendee_extra_questions[0]);
        return $lead_report_settings;
    }

    public function testGetLeadReportSettingsMetadata(){

        $params = [
            'id' => self::$summit->getId(),
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitApiController@getLeadReportSettingsMetadata",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $metadata = json_decode($content);
        self::assertEquals('*', $metadata->extra_questions[0]);
    }
}