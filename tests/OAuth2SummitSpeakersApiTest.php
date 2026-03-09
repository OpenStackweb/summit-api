<?php namespace Tests;
/**
 * Copyright 2017 OpenStack Foundation
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
use App\Models\Foundation\Summit\Speakers\SpeakerEditPermissionRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Date;
use models\summit\SpeakersSummitRegistrationPromoCode;

final class OAuth2SummitSpeakersApiTest extends ProtectedApiTestCase
{
    use InsertSummitTestData;


    protected function setUp(): void
    {
        parent::setUp();
        self::$defaultMember = self::$member;
        self::$defaultMember2 = self::$member2;
        self::insertSummitTestData();
        // Clean up stale edit permission requests from previous test runs/methods
        self::$em->getConnection()->executeStatement('DELETE FROM SpeakerEditPermissionRequest');
    }

    protected function tearDown(): void
    {
        self::clearSummitTestData();
        parent::tearDown();
    }

    public function testPostSpeakerBySummit()
    {
        $params = [
            'id' => self::$summit->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $suffix = str_random(16);

        $data = [
            'title' => 'Developer!',
            'first_name' => 'Sebastian',
            'last_name' => 'Marcet',
            'email' => "smarcet.{$suffix}@gmail.com",
            'other_presentation_links' => [
                [
                    'title' => 'OpenStack',
                    'link' => 'https://www.openstack.org',
                ]
            ],
        ];

        $response = $this->action
        (
            "POST",
            "OAuth2SummitSpeakersApiController@addSpeakerBySummit",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $speaker = json_decode($content);
        $this->assertTrue($speaker->id > 0);
        return $speaker;
    }

    public function testPostSpeaker()
    {
        $email_rand = 'smarcet' . str_random(16);

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $data = [
            'title' => 'Developer!',
            'first_name' => 'Sebastian',
            'last_name' => 'Marcet',
            'bio' => 'Test speaker bio',
            'irc' => 'smarcet_irc',
            'twitter' => 'smarcet_twitter',
            'email' => $email_rand . '@gmail.com',
            'notes' => 'test',
            'willing_to_present_video' => true,
        ];

        $response = $this->action
        (
            "POST",
            "OAuth2SummitSpeakersApiController@addSpeaker",
            [],
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $this->assertResponseStatus(201);
        $content = $response->getContent();
        $speaker = json_decode($content);
        $this->assertTrue($speaker->id > 0);
        $this->assertTrue($speaker->notes == "test");
        $this->assertTrue($speaker->willing_to_present_video == true);
        return $speaker;
    }

    public function testPostSpeakerWithDetails()
    {

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $suffix = str_random(16);

        $data = [
            "areas_of_expertise" => ["tester"],
            "available_for_bureau" => false,
            "bio" => "<p>nad</p>",
            "country" => "AS",
            "email" => "santi_{$suffix}@test.com",
            "first_name" => "Test",
            "funded_travel" => false,
            "other_presentation_links" => [],
            "title" => "Mr",
            "last_name" => "Tester",
            "travel_preferences" => [],
            "twitter" => "",
            "willing_to_present_video" => false,
            "willing_to_travel" => false,
        ];

        $response = $this->action
        (
            "POST",
            "OAuth2SummitSpeakersApiController@addSpeaker",
            [],
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $this->assertResponseStatus(201);
        $content = $response->getContent();
        $speaker = json_decode($content);
        $this->assertTrue($speaker->id > 0);
        $this->assertEquals(false, $speaker->willing_to_present_video);
        return $speaker;
    }

    public function testUpdateSpeaker()
    {
        $speaker = $this->testPostSpeaker();

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $data = [
            'title' => 'Developer update!',
            'first_name' => 'Sebastian update',
            'last_name' => 'Marcet update',
            'notes' => 'test update',
            'willing_to_present_video' => false,
        ];

        $response = $this->action
        (
            "PUT",
            "OAuth2SummitSpeakersApiController@updateSpeaker",
            [
                'speaker_id' => $speaker->id
            ],
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $this->assertResponseStatus(201);
        $content = $response->getContent();
        $speaker = json_decode($content);
        $this->assertTrue($speaker->id > 0);
        $this->assertTrue($speaker->notes == "test update");
        $this->assertTrue($speaker->willing_to_present_video == false);
        return $speaker;
    }

    public function testDeleteSpeaker()
    {
        $speaker = $this->testPostSpeaker();

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];


        $response = $this->action
        (
            "DELETE",
            "OAuth2SummitSpeakersApiController@deleteSpeaker",
            [
                'speaker_id' => $speaker->id
            ],
            [],
            [],
            [],
            $headers
        );

        $this->assertResponseStatus(204);
        $content = $response->getContent();
    }

    public function testPostSpeakerRegCodeBySummit()
    {
        $params = [
            'id' => self::$summit->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $suffix = str_random(16);

        $data = [
            'title' => 'Developer!',
            'first_name' => 'Sebastian',
            'last_name' => 'Marcet',
            'email' => "speaker.reg.{$suffix}@gmail.com",
            'registration_code' => 'REG_CODE_' . str_random(8),
        ];

        $response = $this->action
        (
            "POST",
            "OAuth2SummitSpeakersApiController@addSpeakerBySummit",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $this->assertResponseStatus(201);
        $content = $response->getContent();
        $speaker = json_decode($content);
        $this->assertTrue($speaker->id > 0);
        return $speaker;
    }

    public function testPostSpeakerExistentBySummit()
    {
        $params = [
            'id' => self::$summit->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $data = [
            'title' => 'Developer!',
            'first_name' => 'Sebastian',
            'last_name' => 'Marcet',
            'email' => self::$member2->getEmail(),
        ];

        $response = $this->action
        (
            "POST",
            "OAuth2SummitSpeakersApiController@addSpeakerBySummit",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $this->assertResponseStatus(201);
        $content = $response->getContent();
        $speaker = json_decode($content);
        $this->assertTrue($speaker->id > 0);
        return $speaker;
    }

    public function testUpdateSpeakerBySummit()
    {
        $created_speaker = $this->testPostSpeakerBySummit();

        $params = [
            'id' => self::$summit->getId(),
            'speaker_id' => $created_speaker->id,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $data = [
            'title' => 'Legend!!!',
        ];

        $response = $this->action
        (
            "PUT",
            "OAuth2SummitSpeakersApiController@updateSpeakerBySummit",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $this->assertResponseStatus(201);
        $content = $response->getContent();
        $speaker = json_decode($content);
        $this->assertTrue($speaker->id > 0);
        return $speaker;
    }

    public function testGetSpeakerById()
    {
        $created_speaker = $this->testPostSpeaker();

        $params = [
            'speaker_id' => $created_speaker->id,
            'expand' => 'member,presentations',
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSpeakersApiController@getSpeaker",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $speaker = json_decode($content);
        $this->assertTrue(!is_null($speaker));
        return $speaker;
    }

    public function testGetCurrentSummitSpeakersOrderByIDAndFilteredBySelPlanOR()
    {
        $params = [

            'id' => self::$summit->getId(),
            'page' => 1,
            'per_page' => 10,
            'order' => '+id',
            'filter' => [
                sprintf('presentations_selection_plan_id==%s||%s',
                    self::$default_selection_plan->getId(),
                    self::$default_selection_plan2->getId()),
                sprintf('presentations_track_id==%s', self::$defaultTrack->getId())]
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSpeakersApiController@getSpeakers",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $speakers_response = json_decode($content);
        $this->assertTrue(!is_null($speakers_response));
        $speakers = $speakers_response->data;
        $this->assertTrue(count($speakers) >= 1);
        $this->assertTrue(count($speakers[0]->accepted_presentations) == 40);
    }

    public function testGetCurrentSummitSpeakersOrderByIDAndFilteredBySelPlan()
    {
        $params = [

            'id' => self::$summit->getId(),
            'page' => 1,
            'per_page' => 10,
            'order' => '+id',
            'filter' => [
                /*  'has_accepted_presentations==true',
                  'has_alternate_presentations==false',
                  'has_rejected_presentations==false',*/
                sprintf('presentations_selection_plan_id==%s',
                    self::$default_selection_plan2->getId()),
                sprintf('presentations_track_id==%s',
                    self::$defaultTrack->getId()),
            ]
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSpeakersApiController@getSpeakers",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $speakers_response = json_decode($content);
        $this->assertTrue(!is_null($speakers_response));
        $speakers = $speakers_response->data;
        $this->assertTrue(count($speakers) >= 1);
        $this->assertTrue(count($speakers[0]->accepted_presentations) == 20);
    }

    public function testGetCurrentSummitSpeakersOrderByIDAndFilteredByMediaUploadType()
    {
        $media_upload_ids =array_map(function($v){
            return $v->getId();
        }, self::$media_uploads_types);
        $params = [

            'id' => self::$summit->getId(),
            'page' => 1,
            'per_page' => 10,
            'order' => '+id',
            'filter' => [
                sprintf('has_not_media_upload_with_type==%s', join("&&", $media_upload_ids))
            ]
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSpeakersApiController@getSpeakers",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $speakers_response = json_decode($content);
        $this->assertTrue(!is_null($speakers_response));
        $speakers = $speakers_response->data;
        $this->assertTrue(count($speakers) == 0);
    }

    /**
     * @param int $summit_id
     */
    public function testGetCurrentSummitSpeakersByName()
    {
        $params = [
            'id' => self::$summit->getId(),
            'page' => 1,
            'per_page' => 10,
            'filter' => [
                'first_name=@b||a,last_name=@b,email=@b'
            ],
            'order' => '+id'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSpeakersApiController@getSpeakers",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $speakers = json_decode($content);
        $this->assertTrue(!is_null($speakers));
    }

    public function testGetCurrentSummitSpeakersWithAcceptedPresentations()
    {
        $params = [
            'id'        => self::$summit->getId(),
            'page'      => 1,
            'per_page'  => 10,
            'filter'    => [
                'has_accepted_presentations==true',
            ],
            'expand' => 'presentations,accepted_presentations',
            'order'     => '+id'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSpeakersApiController@getSpeakers",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $speakers = json_decode($content);
        $this->assertTrue(!is_null($speakers));
    }

    public function testGetCurrentSummitSpeakersWithRejectedPresentations()
    {
        $params = [
            'id'        => self::$summit->getId(),
            'page'      => 1,
            'per_page'  => 10,
            'filter'    => [
                'has_rejected_presentations==true',
            ],
            'expand' => 'rejected_presentations',
            'order'     => '+id'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSpeakersApiController@getSpeakers",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $speakers = json_decode($content);
        $this->assertTrue(!is_null($speakers));
    }

    public function testGetCurrentSummitSpeakersFilteredByMemberExternalUserID()
    {
        $params = [
            'id' => self::$summit->getId(),
            'page' => 1,
            'per_page' => 10,
            'filter' => [
                'member_user_external_id==' . self::$member->getUserExternalId()
            ],
            'order' => '+id'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSpeakersApiController@getSpeakers",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $speakers = json_decode($content);
        $this->assertTrue(!is_null($speakers));
    }

    public function testGetCurrentSummitFeaturedSpeakersFilteredByMemberID()
    {
        $params = [
            'id' => self::$summit->getId(),
            'page' => 1,
            'per_page' => 10,
            'filter' => [
                'member_user_external_id==' . self::$member->getUserExternalId()
            ],
            'order' => '+id'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitApiController@getAllFeatureSpeaker",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $speakers = json_decode($content);
        $this->assertTrue(!is_null($speakers));
    }

    public function testExportCurrentSummitSpeakersFilteredByMemberExternalUserID()
    {
        $params = [
            'id' => self::$summit->getId(),
            'page' => 1,
            'per_page' => 10,
            'filter' => [
                'member_user_external_id==' . self::$member->getUserExternalId()
            ],
            'order' => '+id'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSpeakersApiController@getSpeakersCSV",
            $params,
            [],
            [],
            [],
            $headers
        );

        $csv = $response->getContent();
        $this->assertResponseStatus(200);
        $this->assertNotEmpty($csv);
    }

    public function testGetSpeakersFilteredByMemberExternalUserID()
    {
        $params = [
            'filter' => [
                'full_name=@smarcet,email=@hei@やる.ca',
                'first_name=@hei@やる.ca',
                'last_name=@hei@やる.ca',
            ]
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSpeakersApiController@getAll",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $speakers = json_decode($content);
        $this->assertTrue(!is_null($speakers));
    }

    public function testExportCurrentSummitSpeakersWithAcceptedPresentations()
    {
        $params = [
            'id'        => self::$summit->getId(),
            'page'      => 1,
            'per_page'  => 10,
            'filter'    => [
                'has_accepted_presentations==true',
            ],
            'expand'    => 'presentations,accepted_presentations',
            'order'     => '+id'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSpeakersApiController@getSpeakersCSV",
            $params,
            [],
            [],
            [],
            $headers
        );

        $csv = $response->getContent();
        $this->assertResponseStatus(200);
        $this->assertTrue(!empty($csv));
    }

    public function testSendSpeakersBulkEmail() {
        $params = [
            'id' => self::$summit->getId(),
            'filter'    => [
                'first_name=@b||a,last_name=@b,email=@b',
            ],
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $promo_code_spec = [
            "class_name"            => SpeakersSummitRegistrationPromoCode::ClassName,
            "allowed_ticket_types"  => [self::$default_ticket_type->getId()],
            "badge_features"        => [],
            "description"           => "Test multi speakers promo code",
            "discount_rate"         => 0.0,
            "amount"                => 10.0,
            "quantity_available"    => 10,
            "tags"                  => [],
            "valid_since_date"      => Date::now()->getTimestamp(),
            "valid_until_date"      => Date::now()->addDays(10)->getTimestamp(),
        ];

        $data = [
            'email_flow_event'  => 'SUMMIT_SUBMISSIONS_PRESENTATION_SPEAKER_ACCEPTED_ALTERNATE',
            'speaker_ids'       => [
                self::$speaker->getId()
            ],
            'test_email_recipient'      => 'test_recip@nomail.com',
            'outcome_email_recipient'   => 'outcome_recip@nomail.com',
            'promo_code_spec'           => $promo_code_spec,
        ];

        $response = $this->action
        (
            "PUT",
            "OAuth2SummitSpeakersApiController@send",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
    }

    public function testGetCurrentSummitSpeakersOrderByEmail()
    {
        $params = [
            'id' => self::$summit->getId(),
            'page' => 1,
            'per_page' => 10,
            'order' => '+email'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSpeakersApiController@getSpeakers",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $speakers = json_decode($content);
        $this->assertTrue(!is_null($speakers));
    }

    public function testGetCurrentSummitSpeakersByIDMultiple()
    {
        $params = [
            'id' => self::$summit->getId(),
            'page' => 1,
            'per_page' => 10,
            'filter[]' => sprintf('id==%s', self::$speaker->getId()),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSpeakersApiController@getSpeakersOnSchedule",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $speakers = json_decode($content);
        $this->assertTrue(!is_null($speakers));
    }

    public function testGetSpeakersOnSchedule()
    {
        $params = [
            'id' => self::$summit->getId(),
            'page' => 1,
            'per_page' => 10,
            'filter' => [
                'event_start_date>=1604304000',
                'event_end_date<=1604390399'
            ]
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSpeakersApiController@getSpeakersOnSchedule",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $speakers = json_decode($content);
        $this->assertTrue(!is_null($speakers));
    }

    public function testGetCurrentSummitSpeakersByID()
    {
        $created_speaker = $this->testPostSpeakerBySummit();

        $params = [
            'id' => self::$summit->getId(),
            'speaker_id' => $created_speaker->id,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSpeakersApiController@getSummitSpeaker",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $speaker = json_decode($content);
        $this->assertTrue(!is_null($speaker));
    }

    public function testGetSpeaker()
    {
        $created_speaker = $this->testPostSpeaker();

        $params = [
            'speaker_id' => $created_speaker->id,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSpeakersApiController@getSpeaker",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $speaker = json_decode($content);
        $this->assertTrue(!is_null($speaker));
    }

    public function testGetMySpeaker()
    {

        $params = [
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSpeakersApiController@getMySpeaker",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $speaker = json_decode($content);
        $this->assertTrue(!is_null($speaker));
    }

    public function testCreateMySpeaker()
    {

        $params = [
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitSpeakersApiController@createMySpeaker",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(412);
        $error = json_decode($content);
        $this->assertTrue(!is_null($error));
    }

    public function testMergeSpeakers()
    {
        // Create speakers via ORM to avoid BrowserKit state leaking from prior API calls
        $speaker_from = new \models\summit\PresentationSpeaker();
        $speaker_from->setFirstName('MergeFrom');
        $speaker_from->setLastName('Speaker');
        $speaker_from->setBio('Bio from');
        $speaker_from->setIrcHandle('irc_from');
        $speaker_from->setTwitterName('twitter_from');
        self::$em->persist($speaker_from);

        $speaker_to = new \models\summit\PresentationSpeaker();
        $speaker_to->setFirstName('MergeTo');
        $speaker_to->setLastName('Speaker');
        $speaker_to->setBio('Bio to');
        $speaker_to->setIrcHandle('irc_to');
        $speaker_to->setTwitterName('twitter_to');
        self::$em->persist($speaker_to);
        self::$em->flush();

        $params = [
            'speaker_from_id' => $speaker_from->getId(),
            'speaker_to_id'   => $speaker_to->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $data = [
            'title'                => $speaker_to->getId(),
            'bio'                  => $speaker_to->getId(),
            'first_name'           => $speaker_to->getId(),
            'last_name'            => $speaker_to->getId(),
            'irc'                  => $speaker_to->getId(),
            'twitter'              => $speaker_to->getId(),
            'pic'                  => $speaker_to->getId(),
            'registration_request' => $speaker_to->getId(),
            'member'               => $speaker_to->getId(),
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitSpeakersApiController@merge",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
    }

    public function testMergeSpeakersSame()
    {
        $created_speaker = $this->testPostSpeaker();

        $params = [
            'speaker_from_id' => $created_speaker->id,
            'speaker_to_id'   => $created_speaker->id,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $data = [
            'title' => $created_speaker->id,
            'bio' => $created_speaker->id,
            'first_name' => $created_speaker->id,
            'last_name' => $created_speaker->id,
            'irc' => $created_speaker->id,
            'twitter' => $created_speaker->id,
            'pic' => $created_speaker->id,
            'registration_request' => $created_speaker->id,
            'member' => $created_speaker->id,
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitSpeakersApiController@merge",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(412);
        $error = json_decode($content);
        $this->assertTrue(!is_null($error));
    }

    public function testGetMySpeakerPresentationsByRoleAndSelectionPlan()
    {
        $params = [
            'role' => 'speaker',
            'selection_plan_id' => self::$default_selection_plan->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSpeakersApiController@getMySpeakerPresentationsByRoleAndBySelectionPlan",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $presentations = json_decode($content);
        $this->assertTrue(!is_null($presentations));
    }

    public function testGetMySpeakerPresentationsByRoleAndBySummit()
    {
        $params = [
            'role' => 'speaker',
            'summit_id' => self::$summit->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSpeakersApiController@getMySpeakerPresentationsByRoleAndBySummit",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $presentations = json_decode($content);
        $this->assertTrue(!is_null($presentations));
    }

    public function testRequestSpeakerEditPermission()
    {
        // Create speaker via ORM with member2 so it has an email for notifications
        $other_speaker = new \models\summit\PresentationSpeaker();
        $other_speaker->setFirstName('EditPerm');
        $other_speaker->setLastName('Speaker');
        $other_speaker->setBio('Test bio');
        $other_speaker->setMember(self::$member2);
        self::$em->persist($other_speaker);
        self::$em->flush();
        self::$em->clear();

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $params = [
            'speaker_id' => $other_speaker->getId(),
        ];

        $response = $this->action
        (
            "PUT",
            "OAuth2SummitSpeakersApiController@requestSpeakerEditPermission",
            $params,
            [],
            [],
            [],
            $headers
        );

        $this->assertResponseStatus(201);
        $content = $response->getContent();
        $request = json_decode($content);
        $this->assertTrue($request->id > 0);
        return $request;
    }

    public function testGetRequestSpeakerEditPermission()
    {
        // First create the edit permission request
        $edit_request = $this->testRequestSpeakerEditPermission();

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $params = [
            'speaker_id' => $edit_request->speaker_id,
        ];

        $response = $this->action
        (
            "GET",
            "OAuth2SummitSpeakersApiController@getSpeakerEditPermission",
            $params,
            [],
            [],
            [],
            $headers
        );

        $this->assertResponseStatus(200);
        $content = $response->getContent();
        $request = json_decode($content);
        $this->assertTrue($request->id > 0);
        return $request;
    }

    public function testGetSpeakersWithSubmittedMediaUploadsWithType()
    {
        $media_upload_ids =array_map(function($v){
            return $v->getId();
        }, self::$media_uploads_types);

        $params = [
            'id'        => self::$summit->getId(),
            'page'      => 1,
            'per_page'  => 10,
            'filter'    => [
                'has_accepted_presentations==true',
                //'has_alternate_presentations==true',
                //'has_rejected_presentations==true',
                sprintf('has_media_upload_with_type==%s', implode("||", $media_upload_ids) ),
            ],
            'expand' => 'presentations,accepted_presentations,accepted_presentations.media_uploads',
            'order'     => '+id'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSpeakersApiController@getSpeakers",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $speakers = json_decode($content);
        $this->assertTrue(!is_null($speakers));
        $this->assertTrue(count($speakers->data) > 0);
        $speaker = $speakers->data[0];
        $this->assertTrue(count($speaker->accepted_presentations) > 0);
        $accepted_presentation = $speaker->accepted_presentations[0];
        $this->assertTrue(count($accepted_presentation->media_uploads) > 0);
        $media_upload = $accepted_presentation->media_uploads[0];
        $this->assertTrue(in_array($media_upload->media_upload_type_id, $media_upload_ids));
    }

    public function testGetCurrentSummitSpeakers()
    {
        $params = [
            'id'       => self::$summit->getId(),
            'page'     => 1,
            'per_page' => 50,
            'order'    => '+first_name,-last_name'
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSpeakersApiController@getSpeakers",
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

    public function testAllSpeakersFilterByFullName()
    {
        $params = [
            'page'     => 1,
            'per_page' => 15,
            'filter'   => 'full_name=@Bryce',
            'order'    => '+first_name,-last_name'
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSpeakersApiController@getAll",
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


    public function testGetMySpeakerFromCurrentSummit()
    {
        $params = array
        (
            'expand' => 'presentations',
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

    public function testGetSpeakersCompanies(){
        $params = [

        ];

        $headers = ["HTTP_Authorization" => " Bearer " . $this->access_token];
        $response = $this->action(
            "GET",
            "OAuth2SummitSpeakersApiController@getAllCompanies",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $companies = json_decode($content);
        $this->assertNotNull($companies);
        $this->assertEquals(2, $companies->total);
        $this->assertResponseStatus(200);
    }

    public function testGetSpeakersCompaniesWithFilterAndOrdering(){
        $params = [
            'filter' => ['company=@LLC'],
            'order'  => '+company',
        ];

        $headers = ["HTTP_Authorization" => " Bearer " . $this->access_token];
        $response = $this->action(
            "GET",
            "OAuth2SummitSpeakersApiController@getAllCompanies",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $companies = json_decode($content);
        $this->assertNotNull($companies);
        $this->assertEquals(1, $companies->total);
        $this->assertResponseStatus(200);
    }

    // --- getMySummitSpeaker ---

    public function testGetMySummitSpeaker()
    {
        $params = [
            'id' => self::$summit->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSpeakersApiController@getMySummitSpeaker",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $speaker = json_decode($content);
        $this->assertNotNull($speaker);
        $this->assertTrue($speaker->id > 0);
    }

    // --- updateMySpeaker ---

    public function testUpdateMySpeaker()
    {
        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $data = [
            'title' => 'Updated Title!',
            'first_name' => 'UpdatedFirst',
            'last_name' => 'UpdatedLast',
            'bio' => 'Updated bio text',
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitSpeakersApiController@updateMySpeaker",
            [],
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $speaker = json_decode($content);
        $this->assertNotNull($speaker);
        $this->assertEquals('Updated Title!', $speaker->title);
    }

    // --- Speaker Photo Upload/Delete (validation path) ---

    public function testAddSpeakerPhotoNoFile()
    {
        $speaker = $this->testPostSpeaker();

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
        ];

        // No file parameter provided - should return 412
        $response = $this->action(
            "POST",
            "OAuth2SummitSpeakersApiController@addSpeakerPhoto",
            ['speaker_id' => $speaker->id],
            [],
            [],
            [],
            $headers
        );

        $this->assertResponseStatus(412);
        $content = json_decode($response->getContent());
        $this->assertNotNull($content);
    }

    public function testAddSpeakerPhotoNotFound()
    {
        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
        ];

        // Non-existent speaker ID - should return 404
        $response = $this->action(
            "POST",
            "OAuth2SummitSpeakersApiController@addSpeakerPhoto",
            ['speaker_id' => 0],
            [],
            [],
            ['file' => UploadedFile::fake()->image('photo.jpg')],
            $headers
        );

        $this->assertResponseStatus(404);
    }

    public function testDeleteSpeakerPhotoNotFound()
    {
        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
        ];

        // Non-existent speaker ID - should return 404
        $response = $this->action(
            "DELETE",
            "OAuth2SummitSpeakersApiController@deleteSpeakerPhoto",
            ['speaker_id' => 0],
            [],
            [],
            [],
            $headers
        );

        $this->assertResponseStatus(404);
    }

    public function testAddSpeakerBigPhotoNoFile()
    {
        $speaker = $this->testPostSpeaker();

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
        ];

        // No file parameter provided - should return 412
        $response = $this->action(
            "POST",
            "OAuth2SummitSpeakersApiController@addSpeakerBigPhoto",
            ['speaker_id' => $speaker->id],
            [],
            [],
            [],
            $headers
        );

        $this->assertResponseStatus(412);
    }

    public function testDeleteSpeakerBigPhotoNotFound()
    {
        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
        ];

        // Non-existent speaker ID - should return 404
        $response = $this->action(
            "DELETE",
            "OAuth2SummitSpeakersApiController@deleteSpeakerBigPhoto",
            ['speaker_id' => 0],
            [],
            [],
            [],
            $headers
        );

        $this->assertResponseStatus(404);
    }

    public function testAddMySpeakerPhotoNoFile()
    {
        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
        ];

        // No file parameter provided - should return 412
        $response = $this->action(
            "POST",
            "OAuth2SummitSpeakersApiController@addMySpeakerPhoto",
            [],
            [],
            [],
            [],
            $headers
        );

        $this->assertResponseStatus(412);
    }

    public function testAddMySpeakerBigPhotoNoFile()
    {
        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
        ];

        // No file parameter provided - should return 412
        $response = $this->action(
            "POST",
            "OAuth2SummitSpeakersApiController@addMySpeakerBigPhoto",
            [],
            [],
            [],
            [],
            $headers
        );

        $this->assertResponseStatus(412);
    }

    // --- Presentation Speaker/Moderator Management ---

    public function testAddSpeakerToMyPresentation()
    {
        // Create a new speaker to add to the presentation
        $new_speaker = new \models\summit\PresentationSpeaker();
        $new_speaker->setFirstName('NewSpeaker');
        $new_speaker->setLastName('ForPresentation');
        $new_speaker->setBio('New speaker bio');
        self::$em->persist($new_speaker);
        self::$em->flush();
        self::$em->clear();

        // Use the first presentation which has the current user's speaker
        $presentation_id = self::$presentations[0]->getId();
        $speaker_id = $new_speaker->getId();

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $params = [
            'presentation_id' => $presentation_id,
            'speaker_id' => $speaker_id,
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitSpeakersApiController@addSpeakerToMyPresentation",
            $params,
            [],
            [],
            [],
            $headers
        );

        $this->assertResponseStatus(201);
        return [
            'presentation_id' => $presentation_id,
            'speaker_id' => $speaker_id,
        ];
    }

    public function testRemoveSpeakerFromMyPresentation()
    {
        $ids = $this->testAddSpeakerToMyPresentation();

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $params = [
            'presentation_id' => $ids['presentation_id'],
            'speaker_id' => $ids['speaker_id'],
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitSpeakersApiController@removeSpeakerFromMyPresentation",
            $params,
            [],
            [],
            [],
            $headers
        );

        $this->assertResponseStatus(204);
    }

    public function testAddModeratorToMyPresentation()
    {
        // Create a new speaker to be moderator
        $moderator = new \models\summit\PresentationSpeaker();
        $moderator->setFirstName('Moderator');
        $moderator->setLastName('Speaker');
        $moderator->setBio('Moderator bio');
        self::$em->persist($moderator);
        self::$em->flush();

        $presentation_id = self::$presentations[0]->getId();
        $moderator_id = $moderator->getId();
        self::$em->clear();

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $params = [
            'presentation_id' => $presentation_id,
            'speaker_id' => $moderator_id,
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitSpeakersApiController@addModeratorToMyPresentation",
            $params,
            [],
            [],
            [],
            $headers
        );

        $this->assertResponseStatus(201);
        return [
            'presentation_id' => $presentation_id,
            'speaker_id' => $moderator_id,
        ];
    }

    public function testRemoveModeratorFromMyPresentation()
    {
        $ids = $this->testAddModeratorToMyPresentation();

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $params = [
            'presentation_id' => $ids['presentation_id'],
            'speaker_id' => $ids['speaker_id'],
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitSpeakersApiController@removeModeratorFromMyPresentation",
            $params,
            [],
            [],
            [],
            $headers
        );

        $this->assertResponseStatus(204);
    }

    // --- Approve/Decline Speaker Edit Permission ---

    public function testApproveSpeakerEditPermission()
    {
        // Set config value needed by SpeakerEditPermissionApprovedEmail
        Config::set('cfp.base_url', 'http://localhost');

        // Create speaker via ORM with member2 so it has an email
        $other_speaker = new \models\summit\PresentationSpeaker();
        $other_speaker->setFirstName('ApproveEditPerm');
        $other_speaker->setLastName('Speaker');
        $other_speaker->setBio('Test bio');
        $other_speaker->setMember(self::$member2);
        self::$em->persist($other_speaker);
        self::$em->flush();

        // Create a SpeakerEditPermissionRequest with a known token
        $request = new SpeakerEditPermissionRequest();
        $request->setSpeaker($other_speaker);
        $request->setRequestedBy(self::$member);
        $token = $request->generateConfirmationToken();
        self::$em->persist($request);
        self::$em->flush();
        self::$em->clear();

        // Call the public approve endpoint
        $response = $this->call(
            "GET",
            "/api/public/v1/speakers/{$other_speaker->getId()}/edit-permission/{$token}/approve"
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testDeclineSpeakerEditPermission()
    {
        // Set config value needed by SpeakerEditPermissionRejectedEmail
        Config::set('cfp.base_url', 'http://localhost');

        // Create speaker via ORM with member2 so it has an email
        $other_speaker = new \models\summit\PresentationSpeaker();
        $other_speaker->setFirstName('DeclineEditPerm');
        $other_speaker->setLastName('Speaker');
        $other_speaker->setBio('Test bio');
        $other_speaker->setMember(self::$member2);
        self::$em->persist($other_speaker);
        self::$em->flush();

        // Create a SpeakerEditPermissionRequest with a known token
        $request = new SpeakerEditPermissionRequest();
        $request->setSpeaker($other_speaker);
        $request->setRequestedBy(self::$member);
        $token = $request->generateConfirmationToken();
        self::$em->persist($request);
        self::$em->flush();
        self::$em->clear();

        // Call the public decline endpoint
        $response = $this->call(
            "GET",
            "/api/public/v1/speakers/{$other_speaker->getId()}/edit-permission/{$token}/decline"
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

}