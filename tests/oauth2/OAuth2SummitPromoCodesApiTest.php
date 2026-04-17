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
use models\summit\DomainAuthorizedSummitRegistrationPromoCode;
use models\summit\MemberSummitRegistrationPromoCode;
use models\summit\PrePaidSummitRegistrationDiscountCode;
use models\summit\PrePaidSummitRegistrationPromoCode;
use models\summit\SpeakersRegistrationDiscountCode;
use models\summit\SpeakersSummitRegistrationPromoCode;
use models\summit\SummitTicketType;
/**
 * Class OAuth2SummitPromoCodesApiTest
 */
final class OAuth2SummitPromoCodesApiTest
    extends ProtectedApiTestCase
{
    use InsertSummitTestData;

    protected function setUp(): void
    {
        parent::setUp();
        self::insertSummitTestData();
        self::$summit_permission_group->addMember(self::$member);
        self::$em->persist(self::$summit);
        self::$em->persist(self::$summit_permission_group);
        self::$em->flush();
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
            'order' => 'tier_name,sponsor_company_name',
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
            'code'          => 'TEST_PC_' . str_random(8),
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
        return $promo_code;
    }

    public function testUpdatePromoCodeBySummit()
    {
        $created = $this->testAddPromoCodeBySummit();

        $params = [
            'id'            => self::$summit->getId(),
            'promo_code_id' => $created->id
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
            'code'          => 'TEST_DC_' . str_random(8),
            'description'   => 'TEST DISCOUNT CODE',
            'quantity_available'   => 10,
            'amount'        => 10.00,
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
        return $promo_code;
    }

    public function testGetPromoCodeSpeakers()
    {
        $created = $this->testAddPromoCodeSpeaker();

        $params = [
            'id' => self::$summit->getId(),
            'promo_code_id' => $created->id,
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
        $discount_code = $this->testAddDiscountCodeBySummit();

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        // add speaker to the discount code
        $url = sprintf('/api/v1/summits/%s/speakers-discount-codes/%s/speakers/%s',
            self::$summit->getId(), $discount_code->id, self::$speaker->getId());

        $this->call("POST", $url, [], [], [], $headers);
        $this->assertResponseStatus(201);

        // now get speakers
        $params = [
            'id' => self::$summit->getId(),
            'discount_code_id' => $discount_code->id,
            'expand' => 'speaker'
        ];

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
        $created = $this->testAddPromoCodeBySummit();

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $url = sprintf('/api/v1/summits/%s/speakers-promo-codes/%s/speakers/%s',
            self::$summit->getId(), $created->id, self::$speaker->getId());

        $this->call("POST", $url, [], [], [], $headers);

        $this->assertResponseStatus(201);
        return $created;
    }

    public function testRemovePromoCodeSpeaker()
    {
        $created = $this->testAddPromoCodeSpeaker();

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $url = sprintf('/api/v1/summits/%s/speakers-promo-codes/%s/speakers/%s',
            self::$summit->getId(), $created->id, self::$speaker->getId());

        $this->call("DELETE", $url, [], [], [], $headers);

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

        $response = $this->action(
            "GET",
            "OAuth2SummitPromoCodesApiController@preValidatePromoCode",
            $params,
            [],
            [],
            [],
            $headers
        );

        $this->assertResponseStatus(200);
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('allows_to_reassign', $content);
        $this->assertTrue($content['allows_to_reassign']);
    }

    public function testPreValidatePromoCodeReturnsAllowsToReassignFalse()
    {
        self::$default_prepaid_discount_code->setAllowsToReassign(false);

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

        $response = $this->action(
            "GET",
            "OAuth2SummitPromoCodesApiController@preValidatePromoCode",
            $params,
            [],
            [],
            [],
            $headers
        );

        $this->assertResponseStatus(200);
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('allows_to_reassign', $content);
        $this->assertFalse($content['allows_to_reassign']);
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
            'filter' => "code=@{$term},creator=@{$term},creator_email=@{$term},owner=@{$term},owner_email=@{$term},speaker=@{$term},speaker_email=@{$term}",
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

    // -----------------------------------------------------------------------
    // Discovery endpoint — Task 12 follow-up #5
    // -----------------------------------------------------------------------

    /**
     * Domain-authorized code with matching email domain appears in discovery.
     */
    public function testDiscoverReturnsDomainAuthorizedCodeForMatchingEmail()
    {
        // Create a domain-authorized promo code matching the test member's email domain
        $memberEmail = self::$member->getEmail();
        $domain = '@' . substr($memberEmail, strpos($memberEmail, '@') + 1);

        $code = new DomainAuthorizedSummitRegistrationPromoCode();
        $code->setCode('DISC_DA_' . str_random(8));
        $code->setAllowedEmailDomains([$domain]);
        $code->setQuantityAvailable(10);
        $code->setAutoApply(true);
        // null valid dates = lives forever
        self::$summit->addPromoCode($code);
        self::$em->persist(self::$summit);
        self::$em->flush();

        $params = [
            'id' => self::$summit->getId(),
        ];

        $headers = ["HTTP_Authorization" => " Bearer " . $this->access_token];

        $response = $this->action(
            "GET",
            "OAuth2SummitPromoCodesApiController@discover",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $result = json_decode($content, true);
        $this->assertNotNull($result);
        $this->assertArrayHasKey('data', $result);

        $codes = array_column($result['data'], 'code');
        $this->assertContains($code->getCode(), $codes,
            'Domain-authorized code matching member email domain should appear in discovery');
    }

    /**
     * MemberSummitRegistrationPromoCode appears in discovery regardless of auto_apply value.
     */
    public function testDiscoverReturnsMemberPromoCodeRegardlessOfAutoApply()
    {
        $code = new MemberSummitRegistrationPromoCode();
        $code->setCode('DISC_MEMBER_' . str_random(8));
        $code->setQuantityAvailable(10);
        $code->setAutoApply(false);
        $code->setOwner(self::$member);
        $code->setFirstName(self::$member->getFirstName());
        $code->setLastName(self::$member->getLastName());
        $code->setEmail(self::$member->getEmail());
        self::$summit->addPromoCode($code);
        self::$em->persist(self::$summit);
        self::$em->flush();

        $params = [
            'id' => self::$summit->getId(),
        ];

        $headers = ["HTTP_Authorization" => " Bearer " . $this->access_token];

        $response = $this->action(
            "GET",
            "OAuth2SummitPromoCodesApiController@discover",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $result = json_decode($content, true);

        $codes = array_column($result['data'], 'code');
        $this->assertContains($code->getCode(), $codes,
            'Member promo code should appear in discovery regardless of auto_apply');
    }

    /**
     * Discovery returns correct auto_apply flag for each code (true vs false).
     */
    public function testDiscoverReturnsCorrectAutoApplyFlag()
    {
        $memberEmail = self::$member->getEmail();
        $domain = '@' . substr($memberEmail, strpos($memberEmail, '@') + 1);

        $codeTrue = new DomainAuthorizedSummitRegistrationPromoCode();
        $codeTrue->setCode('DISC_AUTO_T_' . str_random(8));
        $codeTrue->setAllowedEmailDomains([$domain]);
        $codeTrue->setQuantityAvailable(10);
        $codeTrue->setAutoApply(true);
        self::$summit->addPromoCode($codeTrue);

        $codeFalse = new DomainAuthorizedSummitRegistrationPromoCode();
        $codeFalse->setCode('DISC_AUTO_F_' . str_random(8));
        $codeFalse->setAllowedEmailDomains([$domain]);
        $codeFalse->setQuantityAvailable(10);
        $codeFalse->setAutoApply(false);
        self::$summit->addPromoCode($codeFalse);

        self::$em->persist(self::$summit);
        self::$em->flush();

        $params = [
            'id' => self::$summit->getId(),
        ];

        $headers = ["HTTP_Authorization" => " Bearer " . $this->access_token];

        $response = $this->action(
            "GET",
            "OAuth2SummitPromoCodesApiController@discover",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $result = json_decode($content, true);

        $byCode = [];
        foreach ($result['data'] as $item) {
            $byCode[$item['code']] = $item;
        }

        $this->assertArrayHasKey($codeTrue->getCode(), $byCode);
        $this->assertTrue($byCode[$codeTrue->getCode()]['auto_apply'],
            'auto_apply=true code should serialize as true');

        $this->assertArrayHasKey($codeFalse->getCode(), $byCode);
        $this->assertFalse($byCode[$codeFalse->getCode()]['auto_apply'],
            'auto_apply=false code should serialize as false');
    }

    /**
     * Discovery ignores ?email= query parameter — uses authenticated member's email only (Truth #14).
     */
    public function testDiscoverIgnoresEmailQueryParameter()
    {
        $memberEmail = self::$member->getEmail();
        $domain = '@' . substr($memberEmail, strpos($memberEmail, '@') + 1);

        $code = new DomainAuthorizedSummitRegistrationPromoCode();
        $code->setCode('DISC_NOENUM_' . str_random(8));
        $code->setAllowedEmailDomains([$domain]);
        $code->setQuantityAvailable(10);
        self::$summit->addPromoCode($code);
        self::$em->persist(self::$summit);
        self::$em->flush();

        $params = [
            'id'    => self::$summit->getId(),
            'email' => 'other@different.com', // should be ignored
        ];

        $headers = ["HTTP_Authorization" => " Bearer " . $this->access_token];

        $response = $this->action(
            "GET",
            "OAuth2SummitPromoCodesApiController@discover",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $result = json_decode($content, true);

        // The code should appear because it matches the AUTHENTICATED user's email domain,
        // not the ?email= parameter.
        $codes = array_column($result['data'], 'code');
        $this->assertContains($code->getCode(), $codes,
            'Discovery must use authenticated member email, ignoring ?email= query parameter');
    }

    /**
     * Discovery excludes codes where QuantityPerAccount is exhausted (Truth #9).
     */
    public function testDiscoverExcludesExhaustedCodes()
    {
        $memberEmail = self::$member->getEmail();
        $domain = '@' . substr($memberEmail, strpos($memberEmail, '@') + 1);

        $code = new DomainAuthorizedSummitRegistrationPromoCode();
        $code->setCode('DISC_EXHAUST_' . str_random(8));
        $code->setAllowedEmailDomains([$domain]);
        $code->setQuantityAvailable(10);
        $code->setQuantityPerAccount(1);
        self::$summit->addPromoCode($code);
        self::$em->persist(self::$summit);
        self::$em->flush();

        // Create an order + ticket attributed to this member and code
        // to simulate a prior purchase (count query checks o.OwnerID + t.PromoCodeID).
        $order = new \models\summit\SummitOrder();
        $order->setOwner(self::$member);
        $order->setPaidStatus();
        $order->setSummit(self::$summit);
        self::$em->persist($order);

        $ticket = new \models\summit\SummitAttendeeTicket();
        $ticket->setOrder($order);
        $ticket->setTicketType(self::$default_ticket_type);
        $ticket->setPromoCode($code);
        $ticket->setNumber('TKT_EXHAUST_' . str_random(8));
        self::$em->persist($ticket);
        self::$em->flush();

        $params = [
            'id' => self::$summit->getId(),
        ];

        $headers = ["HTTP_Authorization" => " Bearer " . $this->access_token];

        $response = $this->action(
            "GET",
            "OAuth2SummitPromoCodesApiController@discover",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $result = json_decode($content, true);

        $codes = array_column($result['data'], 'code');
        $this->assertNotContains($code->getCode(), $codes,
            'Exhausted domain-authorized code (quantity_per_account reached) should not appear in discovery');
    }

    /**
     * Discovery excludes codes where global quantity_available is exhausted
     * (quantity_used >= quantity_available), independent of quantity_per_account.
     * Regression: isLive() is dates-only, so the repository filter does not
     * catch globally-exhausted-but-still-in-date codes.
     */
    public function testDiscoverExcludesGloballyExhaustedCodes()
    {
        $memberEmail = self::$member->getEmail();
        $domain = '@' . substr($memberEmail, strpos($memberEmail, '@') + 1);

        $code = new DomainAuthorizedSummitRegistrationPromoCode();
        $code->setCode('DISC_GLOBAL_EXHAUST_' . str_random(8));
        $code->setAllowedEmailDomains([$domain]);
        $code->setQuantityAvailable(1);
        // quantity_per_account = 0 (unlimited) isolates the global exhaustion path
        $code->setQuantityPerAccount(0);
        self::$summit->addPromoCode($code);
        self::$em->persist(self::$summit);
        self::$em->flush();

        // Globally exhaust: quantity_used becomes 1, matches quantity_available=1.
        // isLive() is dates-only and will still return true, so without the
        // service-layer guard this code would leak into the discovery results.
        $code->addUsage('someone-else@example.com', 1);
        self::$em->persist($code);
        self::$em->flush();

        $params = [
            'id' => self::$summit->getId(),
        ];

        $headers = ["HTTP_Authorization" => " Bearer " . $this->access_token];

        $response = $this->action(
            "GET",
            "OAuth2SummitPromoCodesApiController@discover",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $result = json_decode($content, true);

        $codes = array_column($result['data'], 'code');
        $this->assertNotContains($code->getCode(), $codes,
            'Globally exhausted domain-authorized code (quantity_used >= quantity_available) should not appear in discovery');
    }

    // -----------------------------------------------------------------------
    // Checkout enforcement — Task 12 follow-up #6
    // -----------------------------------------------------------------------

    /**
     * Checkout rejects order when member has reached quantity_per_account limit.
     */
    public function testCheckoutRejectsOverLimitQuantityPerAccount()
    {
        $this->markTestSkipped(
            'Checkout enforcement requires the full order pipeline (SagaFactory + payment mocks). ' .
            'The ApplyPromoCodeTask enforcement is at SummitOrderService.php:791-808. ' .
            'This test requires a companion SDS for the order creation pipeline test harness.'
        );
    }

    /**
     * Checkout succeeds when member is under quantity_per_account limit.
     */
    public function testCheckoutSucceedsUnderLimitQuantityPerAccount()
    {
        $this->markTestSkipped(
            'Checkout enforcement requires the full order pipeline (SagaFactory + payment mocks). ' .
            'The ApplyPromoCodeTask enforcement is at SummitOrderService.php:791-808. ' .
            'This test requires a companion SDS for the order creation pipeline test harness.'
        );
    }

    /**
     * Concurrent checkout enforcement — requires full saga pipeline test harness.
     */
    public function testCheckoutConcurrentEnforcement()
    {
        $this->markTestSkipped(
            'D4 fix applied (ApplyPromoCodeTask now runs after ReserveOrderTask with pessimistic lock ' .
            'and count query includes Reserved orders). Concurrency test requires a full saga pipeline ' .
            'test harness with concurrent request simulation — out of scope for this SDS.'
        );
    }
}