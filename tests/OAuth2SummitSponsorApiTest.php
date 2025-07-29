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

use App\Models\Foundation\ExtraQuestions\ExtraQuestionTypeConstants;
use App\Models\Foundation\Main\IGroup;
use Illuminate\Http\UploadedFile;
use Mockery;
use models\summit\SummitLeadReportSetting;

/**
 * Class OAuth2SummitSponsorApiTest
 */
final class OAuth2SummitSponsorApiTest extends ProtectedApiTestCase
{
    use InsertSummitTestData;

    use InsertMemberTestData;

    public function createApplication()
    {
        $app = parent::createApplication();

        $fileUploaderMock = Mockery::mock(\App\Http\Utils\IFileUploader::class)
            ->shouldIgnoreMissing();

        $fileUploaderMock->shouldReceive('build')->andReturn(new \models\main\File());

        $app->instance(\App\Http\Utils\IFileUploader::class, $fileUploaderMock);

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        self::insertMemberTestData(IGroup::TrackChairs);
        self::$defaultMember = self::$member;
        self::insertSummitTestData();
    }

    protected function tearDown(): void
    {
        self::clearSummitTestData();
        self::clearMemberTestData();
        parent::tearDown();
    }

    public function testGetAllSponsorsBySummit(){
        $params = [
            'id' => self::$summit->getId(),
            'filter'=> 'company_name=@'.substr(self::$companies[0]->getName(),0,3),
            'expand' => 'summit,company,sponsorships,sponsorships.type,sponsorships.add_ons,extra_questions,featured_event,lead_report_setting',
            'order' => '-sponsorship_name'
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSponsorApiController@getAllBySummit",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $page = json_decode($content);
        $this->assertNotNull($page);
        $this->assertGreaterThan(0, $page->total);
        $sponsor = $page->data[0];
        $this->assertNotNull($sponsor);
        $this->assertNotEmpty($sponsor->sponsorships);
        $this->assertNotEmpty($sponsor->sponsorships[0]->add_ons);
        $this->assertNotNull($sponsor->sponsorships[0]->add_ons[0]->name);
        $this->assertNotNull($sponsor->sponsorships[0]->type);
        $this->assertNotNull($sponsor->sponsorships[0]->type->type_id);
        return $page;
    }

    public function testGetSponsor()
    {
        $params = [
            'id' => self::$summit->getId(),
            'sponsor_id'=> self::$sponsors[0]->getId(),
            'expand' => 'summit,company,sponsorships,sponsorships.type,sponsorships.add_ons,extra_questions,featured_event,lead_report_setting',
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSponsorApiController@get",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );
        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $sponsor = json_decode($content);
        $this->assertNotNull($sponsor);
        $this->assertNotEmpty($sponsor->sponsorships);
        $this->assertNotEmpty($sponsor->sponsorships[0]->add_ons);
        $this->assertNotNull($sponsor->sponsorships[0]->add_ons[0]->name);
        $this->assertNotNull($sponsor->sponsorships[0]->type);
        $this->assertNotNull($sponsor->sponsorships[0]->type->type_id);
    }

    public function testAddSponsor(){

        $params = [
            'id' => self::$summit->getId(),
            'expand' => 'sponsorships,sponsorships.type',
        ];

        $data = [
            'company_id'  => self::$companies_without_sponsor[0]->getId(),
            'marquee' => 'this is a marquee',
            'intro' => 'this is an intro',
            'is_published' => false,
            'external_link' => 'https://external.com',
            'chat_link' => 'https://chat.com',
            'video_link' => 'https://video.com',
            'sponsorships' => [
                self::$default_summit_sponsor_type->getId(),
                self::$default_summit_sponsor_type2->getId(),
            ]
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitSponsorApiController@add",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $sponsor = json_decode($content);
        $this->assertNotNull($sponsor);
        $this->assertTrue($sponsor->marquee === 'this is a marquee');
        $this->assertTrue($sponsor->external_link === 'https://external.com');
        return $sponsor;
    }

     public function testAddSponsorV2(){

        $params = [
            'id' => self::$summit->getId(),
            'expand' => 'sponsorships,sponsorships.type',
        ];

        $data = [
            'company_id'  => self::$companies_without_sponsor[0]->getId(),
            'marquee' => 'this is a marquee',
            'intro' => 'this is an intro',
            'is_published' => false,
            'external_link' => 'https://external.com',
            'chat_link' => 'https://chat.com',
            'video_link' => 'https://video.com',
            'sponsorships' => [
                ['type_id' => self::$default_summit_sponsor_type->getId()],
                ['type_id' => self::$default_summit_sponsor_type2->getId()],
            ]
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitSponsorApiController@addV2",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $sponsor = json_decode($content);
        $this->assertNotNull($sponsor);
        $this->assertTrue($sponsor->marquee === 'this is a marquee');
        $this->assertTrue($sponsor->external_link === 'https://external.com');
        return $sponsor;
    }

     public function testUpdateSponsor(){

        $params = [
            'id' => self::$summit->getId(),
            'sponsor_id'=> self::$sponsors[0]->getId(),
            'expand' => 'sponsorships,sponsorships.type',
        ];

        $data = [
            'company_id'  => self::$companies_without_sponsor[0]->getId(),
            'marquee' => 'this is a marquee',
            'intro' => 'this is an intro',
            'is_published' => false,
            'external_link' => 'https://external.com',
            'chat_link' => 'https://chat.com',
            'video_link' => 'https://video.com',
            'sponsorships' => [
                self::$default_summit_sponsor_type->getId(),
                self::$default_summit_sponsor_type2->getId(),
            ]
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitSponsorApiController@update",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $sponsor = json_decode($content);
        $this->assertNotNull($sponsor);
        $this->assertTrue($sponsor->marquee === 'this is a marquee');
        $this->assertTrue($sponsor->external_link === 'https://external.com');
        $this->assertCount(2, $sponsor->sponsorships);
        return $sponsor;
    }

    public function testUpdateSponsorV2(){

        $params = [
            'id' => self::$summit->getId(),
            'sponsor_id'=> self::$sponsors[0]->getId(),
            'expand' => 'sponsorships,sponsorships.type',
        ];

        $data = [
            'company_id'  => self::$companies_without_sponsor[0]->getId(),
            'marquee' => 'this is a marquee',
            'intro' => 'this is an intro',
            'is_published' => false,
            'external_link' => 'https://external.com',
            'chat_link' => 'https://chat.com',
            'video_link' => 'https://video.com',
            'sponsorships' => [
                ['type_id' => self::$default_summit_sponsor_type->getId()],
                ['type_id' => self::$default_summit_sponsor_type2->getId()],
            ]
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitSponsorApiController@updateV2",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $sponsor = json_decode($content);
        $this->assertNotNull($sponsor);
        $this->assertTrue($sponsor->marquee === 'this is a marquee');
        $this->assertTrue($sponsor->external_link === 'https://external.com');
        $this->assertCount(2, $sponsor->sponsorships);
        return $sponsor;
    }

    public function testUploadSponsorSideImage(){
        $params = [
            'id' => self::$summit->getId(),
            "sponsor_id" => self::$sponsors[0]->getId()
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitSponsorApiController@addSponsorSideImage",
            $params,
            [],
            [],
            [
                'file' => UploadedFile::fake()->image('image.svg'),
            ],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $file = json_decode($content);
        $this->assertTrue(!is_null($file));
    }

    public function testGetAllSponsorsAdsBySponsor(){
        $params = [
            'id' => self::$summit->getId(),
            'sponsor_id' => self::$sponsors[0]->getId(),
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSponsorApiController@getAds",
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
        $this->assertNotEmpty($page->data);
        return $page;
    }

    public function testGetAllSponsorsMaterialsBySponsor(){
        $params = [
            'id' => self::$summit->getId(),
            'sponsor_id' => self::$sponsors[0]->getId(),
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSponsorApiController@getMaterials",
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
        $this->assertNotEmpty($page->data);
        return $page;
    }

    public function testGetAllSponsorsMaterialsBySponsorAndType(){

        $params = [
            'id' => self::$summit->getId(),
            'sponsor_id' => self::$sponsors[0]->getId(),
            'filter'=> 'type==Video',
            'order' => '-order',
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSponsorApiController@getMaterials",
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
        $this->assertNotEmpty($page->data);
        return $page;
    }

    public function testDeleteMaterial(){
        $params = [
            'id' => self::$summit->getId(),
            'sponsor_id'=> self::$sponsors[0]->getId(),
            'material_id' => self::$sponsors[0]->getMaterials()[0]->getId()
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitSponsorApiController@deleteMaterial",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(204);
        $this->assertTrue(empty($content));
    }

    public function testGetAllSponsorsSocialNetworksBySponsor(){
        $params = [
            'id' => self::$summit->getId(),
            'sponsor_id' => self::$sponsors[0]->getId(),
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSponsorApiController@getSocialNetworks",
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
        $this->assertNotEmpty($page->data);
        return $page;
    }

    public function testDeleteSponsor(){

        $params = [
            'id' => self::$summit->getId(),
            'sponsor_id'=> self::$sponsors[0]->getId()
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitSponsorApiController@delete",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(204);
        $this->assertTrue(empty($content));
    }

    public function testAddSponsorUserMember(){
        $params = [
            'id'         => self::$summit->getId(),
            'sponsor_id' => self::$sponsors[0]->getId(),
            'member_id'  => self::$member->getId()
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitSponsorApiController@addSponsorUser",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $sponsor = json_decode($content);
        $this->assertTrue(!is_null($sponsor));
        return $sponsor;
    }

    public function testAddSponsorExtraQuestions(){

        $params = [
            'id' => self::$summit->getId(),
            'sponsor_id' => self::$sponsors[0]->getId(),
            'expand' => 'sponsor'
        ];

        $name = 'ADDED_EXTRA_QUESTION_TYPE_' . str_random(5);

        $data = [
            'name'  => $name,
            'type' => ExtraQuestionTypeConstants::CheckBoxQuestionType,
            'label' => 'Added extra question type',
            'mandatory' => true,
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitSponsorApiController@addExtraQuestion",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $question = json_decode($content);
        $this->assertTrue(!is_null($question));
        $this->assertTrue($question->name === $name);
        $this->assertEquals(count($question->sponsor->extra_questions), $question->order);
        return $question;
    }

    public function testGetAllSponsorExtraQuestionsMetadata(){

        $params = [
            'id' => self::$summit->getId(),
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSponsorApiController@getMetadata",
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

    public function testGetAllSponsorExtraQuestionsBySponsor(){
        $question = $this->testAddSponsorExtraQuestions();

        $params = [
            'id' => self::$summit->getId(),
            'sponsor_id' => self::$sponsors[0]->getId(),
            'filter'=> 'label=='.$question->label,
            'order' => '+order',
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSponsorApiController@getExtraQuestions",
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
        $this->assertNotEmpty($page->data);
        return $page;
    }

    public function testGetSponsorExtraQuestionsBySponsor(){
        $q = $this->testAddSponsorExtraQuestions();

        $params = [
            'id' => self::$summit->getId(),
            'sponsor_id' => self::$sponsors[0]->getId(),
            'extra_question_id' => $q->id
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSponsorApiController@getExtraQuestion",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $question = json_decode($content);
        $this->assertEquals($q->id, $question->id);
        return $question;
    }

    public function testUpdateSponsorExtraQuestionsBySponsor(){
        $q = $this->testAddSponsorExtraQuestions();

        $params = [
            'id' => self::$summit->getId(),
            'sponsor_id' => self::$sponsors[0]->getId(),
            'extra_question_id' => $q->id

        ];

        $upd_label = 'Updated label';
        $upd_type = ExtraQuestionTypeConstants::RadioButtonQuestionType;
        $upd_order = 2;

        $data = [
            'label' => $upd_label,
            'type'  => $upd_type,
            'order' => $upd_order
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitSponsorApiController@updateExtraQuestion",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $question = json_decode($content);
        $this->assertEquals($upd_label, $question->label);
        $this->assertEquals($upd_type, $question->type);
        $this->assertEquals($upd_order, $question->order);
        return $question;
    }

    public function testDeleteSponsorExtraQuestionsBySponsor(){
        $q = $this->testAddSponsorExtraQuestions();

        $params = [
            'id' => self::$summit->getId(),
            'sponsor_id' => self::$sponsors[0]->getId(),
            'extra_question_id' => $q->id
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitSponsorApiController@deleteExtraQuestion",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $this->assertResponseStatus(204);
    }

    public function testAddLeadReportSettings(){

        $params = [
            'id' => self::$summit->getId(),
            'sponsor_id' => self::$sponsors[0]->getId(),
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
            SummitLeadReportSetting::SponsorExtraQuestionsKey => [
                [
                    'id'   => 519,
                    'name' => 'ADDED_EXTRA_QUESTION_TYPE'
                ],
                [
                    'id'   => 520,
                    'name' => 'ADDED_EXTRA_QUESTION_TYPE_RRRJc'
                ]
            ]
        ];

        $data = [
            'allowed_columns' => $allowed_columns
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitSponsorApiController@addLeadReportSettings",
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
        $this->assertNotNull($lead_report_settings);
        $this->assertSameSize($allowed_columns[SummitLeadReportSetting::SponsorExtraQuestionsKey], $lead_report_settings->columns->extra_questions);
        $this->assertSameSize($allowed_columns[SummitLeadReportSetting::AttendeeExtraQuestionsKey], $lead_report_settings->columns->attendee_extra_questions);
        return $lead_report_settings;
    }

    public function testUpdateLeadReportSettings(){
        $this->testAddLeadReportSettings();

        $params = [
            'id' => self::$summit->getId(),
            'sponsor_id' => self::$sponsors[0]->getId(),
        ];

        $allowed_columns = [
            'scan_date',
            'extra_questions' => [
                [
                    'id'   => 519,
                    'name' => 'ADDED_EXTRA_QUESTION_TYPE'
                ]
            ]
        ];

        $data = [
            'allowed_columns' => $allowed_columns
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitSponsorApiController@updateLeadReportSettings",
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
        $this->assertEquals($allowed_columns['extra_questions'][0]['id'], $lead_report_settings->columns->extra_questions[0]->id);
        return $lead_report_settings;
    }

    public function testGetLeadReportSettingsMetadata(){

        $params = [
            'id' => self::$summit->getId(),
            'sponsor_id' => self::$sponsors[0]->getId(),
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSponsorApiController@getLeadReportSettingsMetadata",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $metadata = json_decode($content);
        $this->assertNotNull($metadata);
    }
}