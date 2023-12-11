<?php namespace Tests;
use App\Models\Foundation\Summit\PromoCodes\PromoCodesConstants;
use models\summit\PrePaidSummitRegistrationDiscountCode;
use models\summit\PrePaidSummitRegistrationPromoCode;
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

    /**
     * @throws \Exception
     */
    protected function setUp(): void
    {
        parent::setUp();
        self::insertSummitTestData();
    }

    protected function tearDown(): void
    {
        self::clearSummitTestData();
        parent::tearDown();
    }

    public function testGetAllBySummit()
    {
        $params = [
            'id' => self::$summit->getId(),
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
            'id' => self::$summit->getId(),
        ];

        $data = [
            'type'          => PromoCodesConstants::SpeakerSummitRegistrationPromoCodeTypeAlternate,
            'class_name'    => SpeakersSummitRegistrationPromoCode::ClassName,
            'code'          => 'TEST_PC_5',
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
            'id'            => self::$summit->getId(),
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
            'id' => self::$summit->getId(),
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
            'id' => self::$summit->getId(),
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
            'id' => self::$summit->getId(),
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
            'id' => self::$summit->getId(),
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
            'id' => self::$summit->getId(),
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

    public function testGetAllPrepaidPromoCodesBySummit()
    {
        $this->testAddPrePaidPromoCodeBySummit();

        $params = [
            'id' => self::$summit->getId(),
            'filter' => 'class_name==' . PrePaidSummitRegistrationPromoCode::ClassName,
            'expand' => 'tickets'
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

    public function testAddPrePaidPromoCodeBySummit()
    {
        $params = [
            'id' => self::$summit->getId(),
        ];

        $data = [
            'type'          => PromoCodesConstants::SpeakerSummitRegistrationPromoCodeTypeAlternate,
            'class_name'    => PrePaidSummitRegistrationPromoCode::ClassName,
            'code'          => 'TEST_PPPC_' . rand(),
            'description'   => 'TEST PRE PAID PROMO CODE',
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
        return $promo_code;
    }

    public function testRemovePrePaidPromoCodeBySummit()
    {
        $promo_code = $this->testAddPrePaidPromoCodeBySummit();

        $params = [
            'id' => self::$summit->getId(),
            'promo_code_id' => $promo_code->id
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitPromoCodesApiController@deletePromoCodeBySummit",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(204);
    }

    public function testGetAllPrepaidDiscountCodesBySummit()
    {
        $this->testAddPrePaidDiscountCodeBySummit();

        $params = [
            'id' => self::$summit->getId(),
            'filter' => 'class_name==' . PrePaidSummitRegistrationDiscountCode::ClassName
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
        $discount_codes = json_decode($content);
        $this->assertTrue(!is_null($discount_codes));
        $this->assertResponseStatus(200);
    }

    public function testAddPrePaidDiscountCodeBySummit()
    {
        $params = [
            'id' => self::$summit->getId(),
        ];

        $data = [
            'type'          => PromoCodesConstants::SpeakerSummitRegistrationPromoCodeTypeAlternate,
            'class_name'    => PrePaidSummitRegistrationDiscountCode::ClassName,
            'code'          => 'TEST_PPDC_' . rand(),
            'description'   => 'TEST PRE PAID DISCOUNT CODE',
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
        return $promo_code;
    }
}