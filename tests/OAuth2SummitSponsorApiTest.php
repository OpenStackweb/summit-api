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
use Illuminate\Http\UploadedFile;
use Mockery;
use models\summit\SummitLeadReportSetting;
use App\Models\Foundation\Main\IGroup;
/**
 * Class OAuth2SummitSponsorApiTest
 */
final class OAuth2SummitSponsorApiTest extends ProtectedApiTestCase
{
    use InsertSummitTestData;


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
        $this->current_group = IGroup::Sponsors;
        parent::setUp();
        self::$defaultMember = self::$member;
        self::insertSummitTestData();
    }

    protected function tearDown(): void
    {
        self::clearSummitTestData();
        parent::tearDown();
    }

    public function testGetAllSponsorsBySummit(){
        $params = [
            'id' => self::$summit->getId(),
            'filter'=> 'company_name=@'.substr(self::$companies[0]->getName(),0,3),
            'expand' => 'summit,company,extra_questions,featured_event,lead_report_setting,sponsorservices_statistics',
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
        $this->assertNotNull($sponsor->sponsorship_id);
        return $page;
    }

     public function testGetAllSponsorsBySummitV2(){
        $params = [
            'id' => self::$summit->getId(),
            'filter'=> 'company_name=@'.substr(self::$companies[0]->getName(),0,3),
            'expand' => 'summit,company,sponsorships,sponsorships.type,sponsorships.add_ons,extra_questions,featured_event,lead_report_setting,sponsorservices_statistics',
            'order' => '-sponsorship_name'
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSponsorApiController@getAllBySummitV2",
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
        $this->assertNotNull($sponsor->sponsorservices_statistics);
        return $page;
    }

    public function testGetSponsor()
    {
        $params = [
            'id' => self::$summit->getId(),
            'sponsor_id'=> self::$sponsors[0]->getId(),
            'expand' => 'summit,company,extra_questions,featured_event,lead_report_setting',
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
        $this->assertNotNull($sponsor->sponsorship_id);
    }

    public function testGetSponsorV2()
    {
        $params = [
            'id' => self::$summit->getId(),
            'sponsor_id'=> self::$sponsors[0]->getId(),
            'expand' => 'summit,company,sponsorships,sponsorships.type,sponsorships.add_ons,extra_questions,featured_event,lead_report_setting',
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSponsorApiController@getV2",
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
            'sponsorship_id' => self::$default_summit_sponsor_type2->getId()
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
            'sponsorship_id' => self::$default_summit_sponsor_type2->getId(),
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
        $this->assertNotNull($sponsor->sponsorship_id);
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
        $this->assertCount(1, $sponsor->sponsorships);
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
        // create a fresh sponsor so it has no FK dependencies (e.g. promo codes)
        $sponsor = $this->testAddSponsor();

        $params = [
            'id' => self::$summit->getId(),
            'sponsor_id'=> $sponsor->id
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

        // remove the last extra question first (sponsors already have 5, the max)
        // using last() keeps order numbering compact so new question order == count
        $existingQuestion = self::$sponsors[0]->getExtraQuestions()->last();
        $this->action(
            "DELETE",
            "OAuth2SummitSponsorApiController@deleteExtraQuestion",
            [
                'id' => self::$summit->getId(),
                'sponsor_id' => self::$sponsors[0]->getId(),
                'extra_question_id' => $existingQuestion->getId()
            ],
            [], [], [],
            $this->getAuthHeaders()
        );
        $this->assertResponseStatus(204);

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
        $upd_type = ExtraQuestionTypeConstants::ComboBoxQuestionType;
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

        // create an order extra question since test data doesn't include one
        $orderExtraQuestion = new \models\summit\SummitOrderExtraQuestionType();
        $orderExtraQuestion->setType(ExtraQuestionTypeConstants::TextQuestionType);
        $orderExtraQuestion->setLabel('TEST_ORDER_EXTRA_QUESTION');
        $orderExtraQuestion->setName('TEST_ORDER_EXTRA_QUESTION');
        $orderExtraQuestion->setUsage(\models\summit\SummitOrderExtraQuestionTypeConstants::OrderQuestionUsage);
        self::$summit->addOrderExtraQuestion($orderExtraQuestion);
        self::$em->persist(self::$summit);
        self::$em->flush();

        $params = [
            'id' => self::$summit->getId(),
            'sponsor_id' => self::$sponsors[0]->getId(),
        ];

        $attendeeExtraQuestions = self::$summit->getOrderExtraQuestions();
        $sponsorExtraQuestions = self::$sponsors[0]->getExtraQuestions();

        $allowed_columns = [
            'scan_date',
            'attendee_first_name',
            'attendee_company',
            SummitLeadReportSetting::AttendeeExtraQuestionsKey => [
                [
                    'id'   => $attendeeExtraQuestions->first()->getId(),
                    'name' => $attendeeExtraQuestions->first()->getName()
                ],
            ],
            SummitLeadReportSetting::SponsorExtraQuestionsKey => [
                [
                    'id'   => $sponsorExtraQuestions->first()->getId(),
                    'name' => $sponsorExtraQuestions->first()->getName()
                ],
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

        $sponsorExtraQuestions = self::$sponsors[0]->getExtraQuestions();

        $allowed_columns = [
            'scan_date',
            'extra_questions' => [
                [
                    'id'   => $sponsorExtraQuestions->first()->getId(),
                    'name' => $sponsorExtraQuestions->first()->getName()
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

    public function testUpdateSponsorServicesStatistics(){
        $params = [
            'id' => self::$summit->getId(),
            'sponsor_id' => self::$sponsors[0]->getId(),
        ];

        $new_forms_qty = 10;

        $data = [
            'forms_qty' => $new_forms_qty,
            'purchases_qty' => 8,
            'pages_qty' => 7,
            'documents_qty' => 6
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitSponsorApiController@updateSponsorServicesStatistics",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $statistics = json_decode($content);
        $this->assertEquals($new_forms_qty, $statistics->forms_qty);
    }

    public function testUpdatePartiallySponsorServicesStatistics(){
        $params = [
            'id' => self::$summit->getId(),
            'sponsor_id' => self::$sponsors[0]->getId(),
        ];


        $new_forms_qty = 10;
        $pages_qty = self::$sponsors[0]->getSponsorServicesStatistics()->getPagesQty();

        $data = [
            'forms_qty' => $new_forms_qty
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitSponsorApiController@updateSponsorServicesStatistics",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $statistics = json_decode($content);
        $this->assertEquals($new_forms_qty, $statistics->forms_qty);
        $this->assertEquals($pages_qty, $statistics->pages_qty);
    }

    public function testInsertPartiallySponsorServicesStatistics(){
        $params = [
            'id' => self::$summit->getId(),
            'sponsor_id' => self::$sponsors[1]->getId(),
        ];

        $new_forms_qty = 10;

        $data = [
            'forms_qty' => $new_forms_qty
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitSponsorApiController@updateSponsorServicesStatistics",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $statistics = json_decode($content);
        $this->assertEquals($new_forms_qty, $statistics->forms_qty);
    }

    // ---- Sponsor User removal ----

    public function testRemoveSponsorUserMember(){
        // first add the user
        $this->testAddSponsorUserMember();

        $params = [
            'id'         => self::$summit->getId(),
            'sponsor_id' => self::$sponsors[0]->getId(),
            'member_id'  => self::$member->getId()
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitSponsorApiController@removeSponsorUser",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $sponsor = json_decode($content);
        $this->assertNotNull($sponsor);
    }

    // ---- Image upload / delete tests ----

    public function testDeleteSponsorSideImage(){
        // upload first
        $this->testUploadSponsorSideImage();

        $params = [
            'id' => self::$summit->getId(),
            'sponsor_id' => self::$sponsors[0]->getId(),
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitSponsorApiController@deleteSponsorSideImage",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $this->assertResponseStatus(204);
    }

    public function testUploadSponsorHeaderImage(){
        $params = [
            'id' => self::$summit->getId(),
            'sponsor_id' => self::$sponsors[0]->getId(),
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitSponsorApiController@addSponsorHeaderImage",
            $params,
            [],
            [],
            [
                'file' => UploadedFile::fake()->image('header.png'),
            ],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $file = json_decode($content);
        $this->assertNotNull($file);
    }

    public function testDeleteSponsorHeaderImage(){
        $this->testUploadSponsorHeaderImage();

        $params = [
            'id' => self::$summit->getId(),
            'sponsor_id' => self::$sponsors[0]->getId(),
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitSponsorApiController@deleteSponsorHeaderImage",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $this->assertResponseStatus(204);
    }

    public function testUploadSponsorHeaderImageMobile(){
        $params = [
            'id' => self::$summit->getId(),
            'sponsor_id' => self::$sponsors[0]->getId(),
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitSponsorApiController@addSponsorHeaderImageMobile",
            $params,
            [],
            [],
            [
                'file' => UploadedFile::fake()->image('header_mobile.png'),
            ],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $file = json_decode($content);
        $this->assertNotNull($file);
    }

    public function testDeleteSponsorHeaderImageMobile(){
        $this->testUploadSponsorHeaderImageMobile();

        $params = [
            'id' => self::$summit->getId(),
            'sponsor_id' => self::$sponsors[0]->getId(),
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitSponsorApiController@deleteSponsorHeaderImageMobile",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $this->assertResponseStatus(204);
    }

    public function testUploadSponsorCarouselAdvertiseImage(){
        $params = [
            'id' => self::$summit->getId(),
            'sponsor_id' => self::$sponsors[0]->getId(),
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitSponsorApiController@addSponsorCarouselAdvertiseImage",
            $params,
            [],
            [],
            [
                'file' => UploadedFile::fake()->image('carousel.png'),
            ],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $file = json_decode($content);
        $this->assertNotNull($file);
    }

    public function testDeleteSponsorCarouselAdvertiseImage(){
        $this->testUploadSponsorCarouselAdvertiseImage();

        $params = [
            'id' => self::$summit->getId(),
            'sponsor_id' => self::$sponsors[0]->getId(),
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitSponsorApiController@deleteSponsorCarouselAdvertiseImage",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $this->assertResponseStatus(204);
    }

    // ---- Sponsor Ads CRUD ----

    public function testAddAd(){
        $params = [
            'id' => self::$summit->getId(),
            'sponsor_id' => self::$sponsors[0]->getId(),
        ];

        $data = [
            'link' => 'https://ad.example.com',
            'text' => 'Test Ad Text',
            'alt'  => 'Test Ad Alt',
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitSponsorApiController@addAd",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $ad = json_decode($content);
        $this->assertNotNull($ad);
        $this->assertEquals('https://ad.example.com', $ad->link);
        $this->assertEquals('Test Ad Text', $ad->text);
        return $ad;
    }

    public function testGetAd(){
        $ad = self::$sponsors[0]->getAds()[0];

        $params = [
            'id' => self::$summit->getId(),
            'sponsor_id' => self::$sponsors[0]->getId(),
            'ad_id' => $ad->getId(),
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSponsorApiController@getAd",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $result = json_decode($content);
        $this->assertNotNull($result);
        $this->assertEquals($ad->getId(), $result->id);
    }

    public function testUpdateAd(){
        $ad = self::$sponsors[0]->getAds()[0];

        $params = [
            'id' => self::$summit->getId(),
            'sponsor_id' => self::$sponsors[0]->getId(),
            'ad_id' => $ad->getId(),
        ];

        $data = [
            'text' => 'Updated Ad Text',
            'link' => 'https://updated-ad.example.com',
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitSponsorApiController@updateAd",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $result = json_decode($content);
        $this->assertNotNull($result);
        $this->assertEquals('Updated Ad Text', $result->text);
        $this->assertEquals('https://updated-ad.example.com', $result->link);
    }

    public function testDeleteAd(){
        $ad = $this->testAddAd();

        $params = [
            'id' => self::$summit->getId(),
            'sponsor_id' => self::$sponsors[0]->getId(),
            'ad_id' => $ad->id,
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitSponsorApiController@deleteAd",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $this->assertResponseStatus(204);
    }

    public function testAddAdImage(){
        $ad = self::$sponsors[0]->getAds()[0];

        $params = [
            'id' => self::$summit->getId(),
            'sponsor_id' => self::$sponsors[0]->getId(),
            'ad_id' => $ad->getId(),
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitSponsorApiController@addAdImage",
            $params,
            [],
            [],
            [
                'file' => UploadedFile::fake()->image('ad_image.png'),
            ],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $file = json_decode($content);
        $this->assertNotNull($file);
    }

    public function testRemoveAdImage(){
        // add image first
        $this->testAddAdImage();

        $ad = self::$sponsors[0]->getAds()[0];

        $params = [
            'id' => self::$summit->getId(),
            'sponsor_id' => self::$sponsors[0]->getId(),
            'ad_id' => $ad->getId(),
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitSponsorApiController@removeAdImage",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $this->assertResponseStatus(204);
    }

    // ---- Sponsor Materials CRUD ----

    public function testAddMaterial(){
        $params = [
            'id' => self::$summit->getId(),
            'sponsor_id' => self::$sponsors[0]->getId(),
        ];

        $data = [
            'link' => 'https://material.example.com',
            'name' => 'Test Material',
            'type' => 'Video',
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitSponsorApiController@addMaterial",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $material = json_decode($content);
        $this->assertNotNull($material);
        $this->assertEquals('Test Material', $material->name);
        $this->assertEquals('Video', $material->type);
        return $material;
    }

    public function testGetMaterial(){
        $material = self::$sponsors[0]->getMaterials()[0];

        $params = [
            'id' => self::$summit->getId(),
            'sponsor_id' => self::$sponsors[0]->getId(),
            'material_id' => $material->getId(),
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSponsorApiController@getMaterial",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $result = json_decode($content);
        $this->assertNotNull($result);
        $this->assertEquals($material->getId(), $result->id);
    }

    public function testUpdateMaterial(){
        $material = self::$sponsors[0]->getMaterials()[0];

        $params = [
            'id' => self::$summit->getId(),
            'sponsor_id' => self::$sponsors[0]->getId(),
            'material_id' => $material->getId(),
        ];

        $data = [
            'name' => 'Updated Material Name',
            'link' => 'https://updated-material.example.com',
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitSponsorApiController@updateMaterial",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $result = json_decode($content);
        $this->assertNotNull($result);
        $this->assertEquals('Updated Material Name', $result->name);
    }

    // ---- Sponsor Social Networks CRUD ----

    public function testAddSocialNetwork(){
        $params = [
            'id' => self::$summit->getId(),
            'sponsor_id' => self::$sponsors[0]->getId(),
        ];

        $data = [
            'link' => 'https://twitter.com/test',
            'icon_css_class' => 'fa-twitter',
            'is_enabled' => true,
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitSponsorApiController@addSocialNetwork",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $sn = json_decode($content);
        $this->assertNotNull($sn);
        $this->assertEquals('https://twitter.com/test', $sn->link);
        $this->assertEquals('fa-twitter', $sn->icon_css_class);
        return $sn;
    }

    public function testGetSocialNetwork(){
        $sn = self::$sponsors[0]->getSocialNetworks()[0];

        $params = [
            'id' => self::$summit->getId(),
            'sponsor_id' => self::$sponsors[0]->getId(),
            'social_network_id' => $sn->getId(),
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSponsorApiController@getSocialNetwork",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $result = json_decode($content);
        $this->assertNotNull($result);
        $this->assertEquals($sn->getId(), $result->id);
    }

    public function testUpdateSocialNetwork(){
        $sn = self::$sponsors[0]->getSocialNetworks()[0];

        $params = [
            'id' => self::$summit->getId(),
            'sponsor_id' => self::$sponsors[0]->getId(),
            'social_network_id' => $sn->getId(),
        ];

        $data = [
            'link' => 'https://linkedin.com/test',
            'icon_css_class' => 'fa-linkedin',
            'is_enabled' => false,
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitSponsorApiController@updateSocialNetwork",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $result = json_decode($content);
        $this->assertNotNull($result);
        $this->assertEquals('https://linkedin.com/test', $result->link);
        $this->assertEquals('fa-linkedin', $result->icon_css_class);
    }

    public function testDeleteSocialNetwork(){
        $sn = $this->testAddSocialNetwork();

        $params = [
            'id' => self::$summit->getId(),
            'sponsor_id' => self::$sponsors[0]->getId(),
            'social_network_id' => $sn->id,
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitSponsorApiController@deleteSocialNetwork",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $this->assertResponseStatus(204);
    }

    // ---- Extra Question Values CRUD ----

    private function createComboBoxExtraQuestion(){
        // remove the last extra question first (sponsors already have 5, the max)
        $existingQuestion = self::$sponsors[0]->getExtraQuestions()->last();
        $this->action(
            "DELETE",
            "OAuth2SummitSponsorApiController@deleteExtraQuestion",
            [
                'id' => self::$summit->getId(),
                'sponsor_id' => self::$sponsors[0]->getId(),
                'extra_question_id' => $existingQuestion->getId()
            ],
            [], [], [],
            $this->getAuthHeaders()
        );
        $this->assertResponseStatus(204);

        $params = [
            'id' => self::$summit->getId(),
            'sponsor_id' => self::$sponsors[0]->getId(),
        ];

        $name = 'COMBO_EXTRA_QUESTION_' . str_random(5);

        $data = [
            'name'  => $name,
            'type' => ExtraQuestionTypeConstants::ComboBoxQuestionType,
            'label' => 'Combo extra question',
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
        return json_decode($content);
    }

    public function testAddExtraQuestionValue(){
        $q = $this->createComboBoxExtraQuestion();

        $params = [
            'id' => self::$summit->getId(),
            'sponsor_id' => self::$sponsors[0]->getId(),
            'extra_question_id' => $q->id,
        ];

        $data = [
            'value' => 'Option A',
            'label' => 'Option A Label',
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitSponsorApiController@addExtraQuestionValue",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $value = json_decode($content);
        $this->assertNotNull($value);
        $this->assertEquals('Option A', $value->value);
        return (object)['question_id' => $q->id, 'value' => $value];
    }

    public function testUpdateExtraQuestionValue(){
        $result = $this->testAddExtraQuestionValue();

        $params = [
            'id' => self::$summit->getId(),
            'sponsor_id' => self::$sponsors[0]->getId(),
            'extra_question_id' => $result->question_id,
            'value_id' => $result->value->id,
        ];

        $data = [
            'value' => 'Option A Updated',
            'label' => 'Option A Label Updated',
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitSponsorApiController@updateExtraQuestionValue",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $value = json_decode($content);
        $this->assertNotNull($value);
        $this->assertEquals('Option A Updated', $value->value);
    }

    public function testDeleteExtraQuestionValue(){
        $result = $this->testAddExtraQuestionValue();

        $params = [
            'id' => self::$summit->getId(),
            'sponsor_id' => self::$sponsors[0]->getId(),
            'extra_question_id' => $result->question_id,
            'value_id' => $result->value->id,
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitSponsorApiController@deleteExtraQuestionValue",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $this->assertResponseStatus(204);
    }

    // ---- Public API ----

    public function testGetAllSponsorsBySummitPublic(){
        $params = [
            'id' => self::$summit->getId(),
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSponsorApiController@getAllBySummitPublic",
            $params,
            [],
            [],
            [],
            []
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $page = json_decode($content);
        $this->assertNotNull($page);
        $this->assertGreaterThan(0, $page->total);
    }
}
