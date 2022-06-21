<?php namespace Tests;
/*
 * Copyright 2022 OpenStack Foundation
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
use ChargeIO_InvalidRequestError;
use App\Services\Apis\PaymentGateways\LawPayApi;
/**
 * Class LawApiTest
 * @package Tests
 */
final class LawApiTest extends TestCase
{
    /**
     * @throws ChargeIO_InvalidRequestError
     */
    public function testPayment():void{

        $this->expectException(ChargeIO_InvalidRequestError::class);

        $api = new LawPayApi(
            [
                'secret_key' => env("LAW_API_SECRET_KEY"),
                'public_key' => env("LAW_API_PUBLIC_KEY"),
                'account_id' => env("LAW_API_ACCOUNT_ID"),
                'test_mode_enabled' => true,
            ]
        );

        $api->generatePayment(
            [
                'amount' => 500.00,
                'token_id' => 'INVALID'
            ]);

    }
}