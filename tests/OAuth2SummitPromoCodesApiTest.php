<?php namespace Tests;
use App\Models\Foundation\Summit\PromoCodes\PromoCodesConstants;
use models\summit\SpeakersRegistrationDiscountCode;
use models\summit\SpeakersSummitRegistrationPromoCode;

/**
 * Copyright 2023 OpenStack Foundation
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
 * Class OAuth2SummitPromoCodesApiTest
 */
final class OAuth2SummitPromoCodesApiTest
    extends ProtectedApiTest
{
    use InsertSummitTestData;

//    /**
//     * @throws \Exception
//     */
//    protected function setUp(): void
//    {
//        parent::setUp();
//        self::insertSummitTestData();
//    }
//
//    protected function tearDown(): void
//    {
//        self::clearSummitTestData();
//        parent::tearDown();
//    }

    public function testGetAllBySummit()
    {
        $params = [
            'id' => 3603,
            //'filter' => 'owner_email==smarcet+kbxkyjnkyx@gmail.com',
            'expand' => 'owners'
        ];

        $headers = ["HTTP_Authorization" => " Bearer " . $this->access_token];

        $response = $this->action(
            "GET",
            "OAuth2SummitPromoCodesApiController@getAllBySummit",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $promo_codes = json_decode($content);
        $this->assertTrue(!is_null($promo_codes));
        $this->assertResponseStatus(200);
    }

    public function testAddPromoCodeBySummit()
    {
        $params = [
            'id' => 3603,
        ];

        $data = [
            'type'          => PromoCodesConstants::SpeakerSummitRegistrationPromoCodeTypeAlternate,
            'class_name'    => SpeakersSummitRegistrationPromoCode::ClassName,
            'code'          => 'TEST_PC_4',
            'description'   => 'TEST PROMO CODE',
            'quantity_available'   => 10,
            'allowed_ticket_types' => [],
            'badge_features' => [],
            'valid_since_date' => 1649108093,
            'valid_until_date' => 1649109093,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitPromoCodesApiController@addPromoCodeBySummit",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $promo_code = json_decode($content);
        $this->assertResponseStatus(201);
        $this->assertTrue(!is_null($promo_code));
    }

    public function testUpdatePromoCodeBySummit()
    {
        $params = [
            'id'            => 3603,
            'promo_code_id' => 439
        ];

        $data = [
            'class_name'    => SpeakersSummitRegistrationPromoCode::ClassName,
            'type'          => PromoCodesConstants::SpeakerSummitRegistrationPromoCodeTypeAccepted,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitPromoCodesApiController@updatePromoCodeBySummit",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $promo_code = json_decode($content);
        $this->assertResponseStatus(201);
        $this->assertTrue(!is_null($promo_code));
    }

    public function testAddDiscountCodeBySummit()
    {
        $params = [
            'id' => 3603,
        ];

        $data = [
            'type'          => PromoCodesConstants::SpeakerSummitRegistrationPromoCodeTypeAlternate,
            'class_name'    => SpeakersRegistrationDiscountCode::ClassName,
            'code'          => 'TEST_DC_3',
            'description'   => 'TEST DISCOUNT CODE',
            'quantity_available'   => 10,
            'allowed_ticket_types' => [],
            'badge_features'   => [],
            'valid_since_date' => 1649108093,
            'valid_until_date' => 1649109093,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitPromoCodesApiController@addPromoCodeBySummit",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $promo_code = json_decode($content);
        $this->assertResponseStatus(201);
        $this->assertTrue(!is_null($promo_code));
    }

    public function testGetPromoCodeSpeakers()
    {
        $params = [
            'id' => 3603,
            'promo_code_id' => 432,
            'filter' => 'email==smarcet+kbxkyjnkyx@gmail.com',
            'expand' => 'speaker'
        ];

        $headers = ["HTTP_Authorization" => " Bearer " . $this->access_token];

        $response = $this->action(
            "GET",
            "OAuth2SummitPromoCodesApiController@getPromoCodeSpeakers",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $speakers = json_decode($content);
        $this->assertTrue(!is_null($speakers));
        $this->assertResponseStatus(200);
    }

    public function testGetDiscountCodeSpeakers()
    {
        $params = [
            'id' => 3603,
            'discount_code_id' => 443,
            'filter' => 'email==jpmaxman@tipit.net',
            'expand' => 'speaker'
        ];

        $headers = ["HTTP_Authorization" => " Bearer " . $this->access_token];

        $response = $this->action(
            "GET",
            "OAuth2SummitPromoCodesApiController@getDiscountCodeSpeakers",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $speakers = json_decode($content);
        $this->assertTrue(!is_null($speakers));
        $this->assertResponseStatus(200);
    }

    public function testAddPromoCodeSpeaker()
    {
        $params = [
            'id' => 3603,
            'promo_code_id' => 442,
            'speaker_id' => 27086,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitPromoCodesApiController@addSpeaker",
            $params,
            [],
            [],
            [],
            $headers
        );

        $this->assertResponseStatus(201);
    }

    public function testRemovePromoCodeSpeaker()
    {
        $params = [
            'id' => 3603,
            'promo_code_id' => 432,
            'speaker_id' => 27086,
        ];


        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitPromoCodesApiController@removeSpeaker",
            $params,
            [],
            [],
            [],
            $headers
        );

        $this->assertResponseStatus(204);
    }
}