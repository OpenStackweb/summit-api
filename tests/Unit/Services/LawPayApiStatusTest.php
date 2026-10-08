<?php namespace Tests\Unit\Services;
/**
 * Copyright 2026 OpenStack Foundation
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

use App\Services\Apis\PaymentGateways\ILawPayApiChargeStatus;
use App\Services\Apis\PaymentGateways\LawPayApi;
use PHPUnit\Framework\TestCase;

/**
 * SummitOrderService releases a reservation only when the gateway reports the
 * charge as succeeded, abandonable or declined. LawPayApi::isDeclined() always
 * returned false, so a FAILED or VOIDED LawPay charge could never release its
 * slot through the gateway-aware path.
 *
 * @package Tests\Unit\Services
 */
class LawPayApiStatusTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Facade::clearResolvedInstances();
        $container = new \Illuminate\Container\Container();
        $container->instance('app', $container);
        $container->instance('log', new class {
            public function __call($name, $args) { /* swallow */ }
        });
        \Illuminate\Support\Facades\Facade::setFacadeApplication($container);
    }

    protected function tearDown(): void
    {
        \Illuminate\Support\Facades\Facade::clearResolvedInstances();
        \Illuminate\Support\Facades\Facade::setFacadeApplication(null);
        parent::tearDown();
    }

    private function buildApi(): LawPayApi
    {
        return new LawPayApi([
            'secret_key' => 'test_secret',
            'public_key' => 'test_public',
            'account_id' => 'test_account',
            'test_mode_enabled' => true,
        ]);
    }

    public function test_is_declined_voided_and_failed_return_true(): void
    {
        $api = $this->buildApi();

        $this->assertTrue($api->isDeclined(ILawPayApiChargeStatus::Voided));
        $this->assertTrue($api->isDeclined(ILawPayApiChargeStatus::Failed));
    }

    public function test_is_declined_other_statuses_return_false(): void
    {
        $api = $this->buildApi();

        $this->assertFalse($api->isDeclined(ILawPayApiChargeStatus::Pending));
        $this->assertFalse($api->isDeclined(ILawPayApiChargeStatus::Authorized));
        $this->assertFalse($api->isDeclined(ILawPayApiChargeStatus::Completed));
    }
}
