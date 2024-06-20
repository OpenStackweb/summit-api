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
use Illuminate\Support\Facades\Date;
use models\summit\SpeakersSummitRegistrationPromoCode;

final class OAuth2SummitSpeakersApiTest extends ProtectedApiTestCase
{
    use InsertSummitTestData;

    use InsertMemberTestData;

    protected function setUp(): void
    {
        parent::setUp();
        self::insertMemberTestData(IGroup::TrackChairs);
        self::$defaultMember = self::$member;
        self::$defaultMember2 = self::$member2;
        self::insertSummitTestData();
    }

    protected function tearDown(): void
    {
        self::clearSummitTestData();
        parent::tearDown();
    }

    public function testPostSpeakerBySummit($summit_id = 23)
    {
        $params = [
            'id' => $summit_id,
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
            'languages' => [1, 2, 3],
            'other_presentation_links' => [
                [
                    'link' => 'https://www.openstack.org',
                ]
            ],
            'travel_preferences' => ["AF"],
            "areas_of_expertise" => ["testing"],
            "active_involvements" => [],
            "organizational_roles" => [],
            "other_organizational_rol" => "no se",
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

    public function testPostSpeaker1()
    {

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $data = [
            "areas_of_expertise" => ["tester"],
            "available_for_bureau" => false,
            "bio" => "<p>nad</p>",
            "country" => "AS",
            "email" => "santi2288@test.com",
            "first_name" => "Test",
            "funded_travel" => false,
            "id" => 0,
            "irc" => "",
            "languages" => [27],
            "last_name" => "Tester",
            "org_has_cloud" => false,
            "organizational_roles" => [4, 8, 10],
            "other_presentation_links" => [],
            "title" => "Mr",
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
        $this->assertTrue($speaker->notes == "test");
        $this->assertTrue($speaker->willing_to_present_video == true);
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

    public function testPostSpeakerRegCodeBySummit($summit_id = 23)
    {
        $params = [

            'id' => $summit_id,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $data = [
            'title' => 'Developer!',
            'first_name' => 'Sebastian',
            'last_name' => 'Marcet',
            'email' => 'sebastian.ge7.marcet@gmail.com',
            'registration_code' => 'SPEAKER_00001'
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

    public function testPostSpeakerExistentBySummit($summit_id = 23)
    {
        $params = [

            'id' => $summit_id,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $data = [

            'title' => 'Developer!',
            'first_name' => 'Sebastian',
            'last_name' => 'Marcet',
            'email' => 'sebastian@tipit.net',
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

    public function testUpdateSpeakerBySummit($summit_id = 23)
    {
        $params = [

            'id' => $summit_id,
            'speaker_id' => 9161
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

    public function testGetSpeakerById($speaker_id=219)
    {
        $params = [

            'speaker_id' => $speaker_id,
            'expand' => 'member,presentations'
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
    public function testGetCurrentSummitSpeakersByName($summit_id = 27)
    {
        $params = [

            'id' => $summit_id,
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

    public function testGetCurrentSummitSpeakersWithAcceptedPresentations($summit_id = 1723)
    {
        $params = [
            'id'        => $summit_id,
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

    public function testGetCurrentSummitSpeakersWithRejectedPresentations($summit_id = 1587)
    {
        $params = [
            'id'        => $summit_id,
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

    public function testExportCurrentSummitSpeakersWithAcceptedPresentations($summit_id = 1723)
    {
        $params = [
            'id'        => $summit_id,
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
            "allowed_ticket_types"  => [2446,2447],
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
                9161
            ],
            'test_email_recipient'      => 'test_recip@nomail.com',
            'outcome_email_recipient'   => 'outcome_recip@nomail.com',
            'promo_code_spec'           => $promo_code_spec,
            //'promo_code'                => 'TEST_SSRPC'
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

            'id' => 23,
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

            'id' => 23,
            'page' => 1,
            'per_page' => 10,
            'filter[]' => 'id==13869||id==19'
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

    public function testGetSpeakersOnSchedule($summit_id = 31)
    {
        $params = [

            'id' => $summit_id,
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
        $params = [
            'id' => 23,
            'speaker_id' => 13869
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

        $params = [
            'speaker_id' => 12927
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

        $params = [
            'speaker_from_id' => 3643,
            'speaker_to_id' => 1
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $data = [
            'title' => 1,
            'bio' => 1,
            'first_name' => 1,
            'last_name' => 1,
            'irc' => 1,
            'twitter' => 1,
            'pic' => 1,
            'registration_request' => 1,
            'member' => 1,
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
        $this->assertResponseStatus(204);
        $speaker = json_decode($content);
        $this->assertTrue(!is_null($speaker));
    }

    public function testMergeSpeakersSame()
    {

        $params = [
            'speaker_from_id' => 1,
            'speaker_to_id' => 1
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $data = [
            'title' => 1,
            'bio' => 1,
            'first_name' => 1,
            'last_name' => 1,
            'irc' => 1,
            'twitter' => 1,
            'pic' => 1,
            'registration_request' => 1,
            'member' => 1,
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
        $speaker = json_decode($content);
        $this->assertTrue(!is_null($speaker));
    }

    public function testGetMySpeakerPresentationsByRoleAndSelectionPlan()
    {
        $params = [
            'role' => 'speaker',
            'selection_plan_id' => 8,
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

    public function testGetMySpeakerPresentationsByRoleAndBySummit($summit_id = 7)
    {
        $params = [
            'role' => 'speaker',
            'summit_id' => $summit_id,
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

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $params = [
            'speaker_id' => 9
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

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $params = [
            'speaker_id' => 9
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
}