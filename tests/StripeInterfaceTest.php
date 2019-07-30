<?php
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
use models\summit\Summit;
use Tests\TestCase;
use models\summit\StripePaymentProfile;
/**
 * Class StripeInterfaceTest
 */
final class StripeInterfaceTest extends TestCase
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

    public static function setUpBeforeClass(): void
    {
    }

    public static function tearDownAfterClass(): void
    {
    }

    protected function setUp()
    {
        parent::setUp();
        self::$test_secret_key = env('TEST_STRIPE_SECRET_KEY');
        self::$test_public_key = env('TEST_STRIPE_PUBLISHABLE_KEY');
        self::$live_secret_key = env('LIVE_STRIPE_SECRET_KEY');
        self::$live_public_key = env('LIVE_STRIPE_PUBLISHABLE_KEY');
        self::insertTestData();
    }

    protected function tearDown()
    {
        self::clearTestData();
        parent::tearDown();
    }

    /**
     * @throws \models\exceptions\ValidationException
     */
    public function testAddPaymentGatewayConfig2Summit(){

        // build payment profile and attach to summit
        $profile = PaymentGatewayProfileFactory::build(IPaymentConstants::ProviderStripe, [
            'application_type'     => IPaymentConstants::ApplicationTypeRegistration,
            'is_test_mode'         => true,
            'test_publishable_key' => self::$test_public_key,
            'test_secret_key'      => self::$test_secret_key,
            'is_active'            => false,
        ]);

        self::$summit->addPaymentProfile($profile);
        self::$em->persist(self::$summit);
        self::$em->flush();

        $summit2 = self::$summit_repository->findOneBy(['id' => self::$summit->getId()]);

        $this->assertTrue(!is_null($summit2));
        $this->assertTrue($summit2 instanceof Summit);
        $profile->activate();
        $profile = $summit2->getPaymentGateWayProfilePerApp(IPaymentConstants::ApplicationTypeRegistration);
        $this->assertTrue(!is_null($profile));
        $this->assertTrue($profile instanceof StripePaymentProfile);
        if($profile instanceof StripePaymentProfile){
            $profile->buildWebHook();
        }

        self::$em->persist($summit2);
        self::$em->flush();
    }

    /**
     * @throws \models\exceptions\ValidationException
     */
    public function testAddPaymentGatewayConfig2SummitAndChangeToLive(){

        // build payment profile and attach to summit
        $profile = PaymentGatewayProfileFactory::build(IPaymentConstants::ProviderStripe, [
            'application_type'     => IPaymentConstants::ApplicationTypeRegistration,
            'is_test_mode'         => true,
            'test_publishable_key' => self::$test_public_key,
            'test_secret_key'      => self::$test_secret_key,
            'live_publishable_key' => self::$live_public_key,
            'live_secret_key'      => self::$live_secret_key,
            'is_active'            => true,
        ]);

        self::$summit->addPaymentProfile($profile);
        self::$em->persist(self::$summit);
        self::$em->flush();

        $summit2 = self::$summit_repository->findOneBy(['id' => self::$summit->getId()]);

        $this->assertTrue(!is_null($summit2));
        $this->assertTrue($summit2 instanceof Summit);

        $profile = $summit2->getPaymentGateWayProfilePerApp(IPaymentConstants::ApplicationTypeRegistration);
        $this->assertTrue(!is_null($profile));
        $this->assertTrue($profile instanceof StripePaymentProfile);
        if($profile instanceof StripePaymentProfile){
            // build hook on test
            $profile->setTestMode();
            $profile->buildWebHook();
            // build hook on live
            $profile->setLiveMode();
            $profile->buildWebHook();
        }

        self::$em->persist($summit2);
        self::$em->flush();
    }


}