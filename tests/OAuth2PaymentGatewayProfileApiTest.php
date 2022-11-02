<?php namespace Tests;
/**
 * Copyright 2020 OpenStack Foundation
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
use models\summit\PaymentGatewayProfileFactory;
use models\summit\IPaymentConstants;
use App\Models\Foundation\Summit\Factories\SummitBadgeTypeFactory;
use App\Models\Foundation\Summit\Factories\SummitTicketTypeFactory;
/**
 * Class OAuth2PaymentGatewayProfileApiTest
 */
final class OAuth2PaymentGatewayProfileApiTest extends ProtectedApiTest
{
    /**
    * @var string
     */
    protected static $test_secret_key;

    /**
     * @var string
     */
    protected static $test_public_key;

    /**
     * @var string
     */
    protected static $live_secret_key;

    /**
     * @var string
     */
    protected static $live_public_key;

    use InsertSummitTestData;

    protected function setUp()
    {
        parent::setUp();
        self::$test_secret_key = env('TEST_STRIPE_SECRET_KEY');
        self::$test_public_key = env('TEST_STRIPE_PUBLISHABLE_KEY');
        self::$live_secret_key = env('LIVE_STRIPE_SECRET_KEY');
        self::$live_public_key = env('LIVE_STRIPE_PUBLISHABLE_KEY');
        self::insertSummitTestData();
        // build payment profile and attach to summit
        $profile = PaymentGatewayProfileFactory::build(IPaymentConstants::ProviderStripe, [
            'application_type'     => IPaymentConstants::ApplicationTypeRegistration,
            'is_test_mode'         => true,
            'test_publishable_key' => self::$test_public_key,
            'test_secret_key'      => self::$test_secret_key,
            'is_active'            => false,
        ]);

        // build default badge type

        $defaultBadge = SummitBadgeTypeFactory::build([
            'name' => 'DEFAULT',
            'is_default' => true,
        ]);

        // build ticket type

        $ticketType = SummitTicketTypeFactory::build(self::$summit, [
            'name'            => 'TICKET_1',
            'cost'            => 100,
            'quantity_2_sell' => 1000,
        ]);

        self::$summit->addPaymentProfile($profile);
        self::$summit->addBadgeType($defaultBadge);
        self::$summit->addTicketType($ticketType);
        self::$em->persist(self::$summit);
        self::$em->flush();
    }

    protected function tearDown():void
    {
        self::clearSummitTestData();
        parent::tearDown();
    }

    /**
     * @param int $summit_id
     */
    public function testGetPaymentProfiles(){
        $params = [

            'id'       => self::$summit->getId(),
            'page'     => 1,
            'per_page' => 10,
            //'filter'   => 'code=@DISCOUNT_',
            'order'    => '-id'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2PaymentGatewayProfileApiController@getAllBySummit",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $profiles = json_decode($content);
        $this->assertTrue(!is_null($profiles));
        $this->assertTrue($profiles->total >= 1);
        $aProfile = $profiles->data[0];
        $this->assertTrue(property_exists($aProfile, 'live_secret_key'));
        $this->assertTrue(property_exists($aProfile, 'test_secret_key'));
        return $profiles;
    }

    public function testAddProfileFail(){
        $params = [
            'id' => self::$summit->getId(),
        ];

        $data = [
            'active' => false,
            'application_type' => 'test',
            'provider' => 'test',
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2PaymentGatewayProfileApiController@add",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(412);
        $errors = json_decode($content);
    }


    public function testAddProfileOK(){
        $params = [
            'id' => self::$summit->getId(),
        ];

        $data = [
            'active'               => false,
            'application_type'     =>  IPaymentConstants::ApplicationTypeRegistration,
            'provider'             => IPaymentConstants::ProviderStripe,
            'test_mode_enabled'    => true,
            'test_secret_key'      => self::$test_secret_key,
            'test_publishable_key' => self::$test_public_key,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2PaymentGatewayProfileApiController@add",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $profile = json_decode($content);
        $this->assertTrue(!is_null($profile));
        $this->assertTrue($profile->test_secret_key ==  self::$test_secret_key);
        $this->assertTrue($profile->test_publishable_key ==  self::$test_public_key);
        return $profile;
    }

    public function testUpdateOK(){
        $params = [
            'id' => self::$summit->getId(),
        ];

        $data = [
            'active'               => false,
            'application_type'     =>  IPaymentConstants::ApplicationTypeRegistration,
            'provider'             => IPaymentConstants::ProviderStripe,
            'test_mode_enabled'    => true,
            'test_secret_key'      => self::$test_secret_key,
            'test_publishable_key' => self::$test_public_key,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2PaymentGatewayProfileApiController@add",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $profile = json_decode($content);

        $params = [
            'id' => self::$summit->getId(),
            'payment_profile_id' => $profile->id,
        ];

        $data = [
            'active'               => true,
            'provider'             => IPaymentConstants::ProviderStripe,
            'test_mode_enabled'    => true,
            'live_secret_key'      => self::$live_secret_key,
            'live_publishable_key' => self::$live_public_key,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2PaymentGatewayProfileApiController@update",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $profile = json_decode($content);
        $this->assertTrue(!is_null($profile));
        $this->assertTrue($profile->live_publishable_key ==  self::$live_public_key);
        return $profile;
    }

    public function testDelete(){

        $params = [
            'id' => self::$summit->getId(),
        ];

        $data = [
            'active'               => false,
            'application_type'     => IPaymentConstants::ApplicationTypeRegistration,
            'provider'             => IPaymentConstants::ProviderStripe,
            'test_mode_enabled'    => true,
            'test_secret_key'      => self::$test_secret_key,
            'test_publishable_key' => self::$test_public_key,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2PaymentGatewayProfileApiController@add",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $profile = json_decode($content);

        $params = [
            'id' => self::$summit->getId(),
            'payment_profile_id' => $profile->id,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2PaymentGatewayProfileApiController@delete",
            $params,
            [],
            [],
            [],
            $headers

        );

        $content = $response->getContent();
        $this->assertResponseStatus(204);
    }

}