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

use App\Services\Apis\PaymentGateways\StripeApi;

use Illuminate\Support\Facades\Config;
/**
 * Class StripeTest
 * @package Tests
 */
final class StripeTest extends TestCase
{
    public function testRefund(){
        $api = new StripeApi(
            Config::get("stripe.private_key", null),
            Config::get("stripe.endpoint_secret", null)
        );

        $api->refundPayment("pi_1Epa7kL4yik3a08Jlf8SN6YS", 30);
    }
}