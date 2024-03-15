<?php namespace Tests;
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
use App\Jobs\Emails\Registration\PromoCodes\SponsorPromoCodeEmail;
use App\Models\Foundation\Summit\PromoCodes\PromoCodesConstants;
use models\summit\PrePaidSummitRegistrationDiscountCode;
use models\summit\PrePaidSummitRegistrationPromoCode;
use models\summit\SpeakersRegistrationDiscountCode;
use models\summit\SpeakersSummitRegistrationPromoCode;
use models\summit\SummitTicketType;
/**
 * Class OAuth2SummitPromoCodesApiTest
 */
final class OAuth2SummitPromoCodesApiTest
    extends ProtectedApiTest
{
    use InsertSummitTestData;

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
            'expand' => 'owners',
            'order'  => '-redeemed',
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

    public function testGetSponsorPromoCodesAllBySummit()
    {
        $params = [
            'id' => self::$summit->getId(),
            'expand' => 'sponsor,sponsor.company,sponsor.sponsorship,sponsor.sponsorship.type',
        ];

        $headers = ["HTTP_Authorization" => " Bearer " . $this->access_token];

        $response = $this->action(
            "GET",
            "OAuth2SummitPromoCodesApiController@getAllSponsorPromoCodesBySummit",
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

        $discount_amount = 8;
        $quantity_available = 10;

        $data = [
            'type'          => PromoCodesConstants::SpeakerSummitRegistrationPromoCodeTypeAlternate,
            'class_name'    => PrePaidSummitRegistrationDiscountCode::ClassName,
            'code'          => 'TEST_PPDC_' . rand(),
            'description'   => 'TEST PRE PAID DISCOUNT CODE',
            'amount'           => $discount_amount,
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
        $this->assertEquals($discount_amount, $promo_code->amount);
        $this->assertEquals($quantity_available, $promo_code->quantity_available);
        return $promo_code;
    }

    public function testRemovePrePaidDiscountCodeBySummit()
    {
        $discount_code = $this->testAddPrePaidDiscountCodeBySummit();

        $params = [
            'id' => self::$summit->getId(),
            'promo_code_id' => $discount_code->id
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

    public function testPreValidatePromoCodeSuccess()
    {
        $type_id = self::$default_ticket_type->getId();

        $params = [
            'id' => self::$summit->getId(),
            'promo_code_val' => self::$default_prepaid_discount_code->getCode(),
            'filter' => [
                'ticket_type_id==' . $type_id,
                'ticket_type_qty==1',
                'ticket_type_subtype==' . SummitTicketType::Subtype_PrePaid
            ]
        ];

        $headers = ["HTTP_Authorization" => " Bearer " . $this->access_token];

        $this->action(
            "GET",
            "OAuth2SummitPromoCodesApiController@preValidatePromoCode",
            $params,
            [],
            [],
            [],
            $headers
        );

        $this->assertResponseStatus(200);
    }

    public function testPreValidatePromoCodeInvalid()
    {
        $type_id = self::$default_ticket_type->getId();

        $params = [
            'id' => self::$summit->getId(),
            'promo_code_val' => self::$default_prepaid_discount_code->getCode(),
            'filter' => [
                'ticket_type_id==' . $type_id,
                'ticket_type_qty==2',
                'ticket_type_subtype=='  . SummitTicketType::Subtype_Regular
            ]
        ];

        $headers = ["HTTP_Authorization" => " Bearer " . $this->access_token];

        $this->action(
            "GET",
            "OAuth2SummitPromoCodesApiController@preValidatePromoCode",
            $params,
            [],
            [],
            [],
            $headers
        );

        $this->assertResponseStatus(412);
    }

    public function testPreValidatePromoCodeAppliedTooManyTimes()
    {
        $type_id = self::$default_ticket_type->getId();

        $params = [
            'id' => self::$summit->getId(),
            'promo_code_val' => self::$default_discount_code->getCode(),
            'filter' => [
                'ticket_type_id==' . $type_id,
                'ticket_type_qty==20',
                'ticket_type_subtype=='  . SummitTicketType::Subtype_Regular
            ]
        ];

        $headers = ["HTTP_Authorization" => " Bearer " . $this->access_token];

        $this->action(
            "GET",
            "OAuth2SummitPromoCodesApiController@preValidatePromoCode",
            $params,
            [],
            [],
            [],
            $headers
        );

        $this->assertResponseStatus(412);
    }

    public function testGetAllBySummitFilterByTerm(string $term = 'intel')
    {
        $params = [
            'id' => self::$summit->getId(),
            'filter' => "code=@{$term},creator=@{$term},creator_email=@{$term},owner=@{$term},owner_email=@{$term},speaker=@{$term},speaker_email=@{$term},sponsor=@{$term}",
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

    private function getPromoCodeBySummitAndFilter(int $summit_id, string $filter, string $expand = '')
    {
        $params = [
            'id' => $summit_id,
            'filter' => $filter,
            'expand' => $expand
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
        return json_decode($content);
    }

    public function testGetByClassName()
    {
        $summit_id = self::$summit->getId();
        $class_name = PrePaidSummitRegistrationDiscountCode::ClassName;
        $promo_codes = $this->getPromoCodeBySummitAndFilter($summit_id, "class_name=={$class_name}");
        $this->assertNotNull($promo_codes);
        $this->assertTrue($promo_codes->total > 0);
        $this->assertEquals($class_name, $promo_codes->data[0]->class_name);
    }

    public function testGetByCode()
    {
        $summit_id = self::$summit->getId();
        $code = 'TEST_';
        $promo_codes = $this->getPromoCodeBySummitAndFilter($summit_id, "code=@{$code}");
        $this->assertNotNull($promo_codes);
        $this->assertTrue($promo_codes->total > 0);
        $this->assertStringStartsWith($code, $promo_codes->data[0]->code);
    }

    public function testGetByDescription()
    {
        $summit_id = self::$summit->getId();
        $description = 'TEST';
        $promo_codes = $this->getPromoCodeBySummitAndFilter($summit_id, "description=@{$description}");
        $this->assertNotNull($promo_codes);
        $this->assertTrue($promo_codes->total > 0);
        $this->assertStringStartsWith($description, $promo_codes->data[0]->description);
    }

    public function testGetByTag()
    {
        $summit_id = self::$summit->getId();
        $tag = 'TEST';
        $promo_codes = $this->getPromoCodeBySummitAndFilter($summit_id, "tag=@{$tag}", "tags");
        $this->assertNotNull($promo_codes);
        $this->assertTrue($promo_codes->total > 0);
        $this->assertNotEmpty($promo_codes->data[0]->tags);
        $this->assertStringStartsWith($tag, $promo_codes->data[0]->tags[0]->tag);
    }

    public function testSendSponsorPromoCodes()
    {
        $params = [
            'id' => self::$summit->getId(),
            'filter' => [
                'id=='.implode('||',[
                        self::$default_sponsors_promo_codes[count(self::$default_sponsors_promo_codes)-1]->getId(),
                        self::$default_sponsors_promo_codes[count(self::$default_sponsors_promo_codes)-3]->getId(),
                    ]
                ),
                'email_sent==0',
            ]
        ];

        $data = [
            'email_flow_event'      => SponsorPromoCodeEmail::EVENT_SLUG,
            'test_email_recipient'  => 'test_recip@nomail.com',
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitPromoCodesApiController@sendSponsorPromoCodes",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $this->assertResponseStatus(200);
    }
}