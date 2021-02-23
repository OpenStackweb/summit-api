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
use App\Jobs\ProcessSummitOrderPaymentConfirmation;
use App\Models\Foundation\Summit\Repositories\ISummitOrderRepository;
use App\Services\Apis\IExternalUserApi;
use App\Services\Model\ISummitOrderService;
use Illuminate\Support\Facades\App;
use models\main\IMemberRepository;
use models\summit\SummitOrder;
/**
 * Class SummitOrderJobsTest
 * @package Tests
 */
final class SummitOrderJobsTest extends TestCase
{
    use InsertSummitTestData;

    protected function tearDown():void
    {
        self::clearTestData();
        parent::tearDown();
        \Mockery::close();
    }


    protected function setUp():void
    {
        parent::setUp();
        self::insertTestData();
    }

    public function testDispatchPaymentConfirmationJobWithNonExistentUser(){
        try {

            $order = \Mockery::mock(SummitOrder::class);
            $order->shouldReceive('getId')->andReturn(1);
            $order->shouldReceive('getNumber')->andReturn('ABC_1234');
            $order->shouldReceive('generateQRCode');
            $order->shouldReceive('hasOwner')->andReturnFalse();
            $order->shouldReceive('getOwnerEmail')->andReturn('test@test.com');
            $order->shouldReceive('getOwnerFirstName')->andReturn('test');
            $order->shouldReceive('getOwnerSurname')->andReturn('test');
            $order->shouldReceive('getOwnerFullName')->andReturn('test test');
            $order->shouldReceive('getRawAmount')->andReturn(1000);
            $order->shouldReceive('getFinalAmount')->andReturn(1000);
            $order->shouldReceive('getTaxesAmount')->andReturn(0);
            $order->shouldReceive('getDiscountAmount')->andReturn(0);
            $order->shouldReceive('getCurrency')->andReturn('USD');
            $order->shouldReceive('getQRCode')->andReturn('QR_CODE');
            $order->shouldReceive('getSummit')->andReturn(self::$summit);
            $order->shouldReceive('getTickets')->andReturn([]);

            $externalUserApi = \Mockery::mock(IExternalUserApi::class)
                ->shouldIgnoreMissing();

            $externalUserApi->shouldReceive('getUserByEmail')
                ->with('test@test.com')->andReturn([]);

            $externalUserApi->shouldReceive('registerUser')->andReturn(
                [
                    'set_password_link' => 'https://test.com'
                ]
            );

            $this->app->instance(IExternalUserApi::class, $externalUserApi);

            $orderRepository = \Mockery::mock(ISummitOrderRepository::class)->shouldIgnoreMissing();
            $orderRepository->shouldReceive('getById')->with(1)->andReturn($order);

            $this->app->instance(ISummitOrderRepository::class, $orderRepository);

            $job = new ProcessSummitOrderPaymentConfirmation($order->getId());
            $job->handle(App::make(ISummitOrderService::class));
        }
        catch (\Exception $ex){
            $this->fail($ex->getMessage());
        }
       $this->assertTrue(true);
    }

    public function testDispatchPaymentConfirmationJobWithExistentUser(){
        try {


            $order = \Mockery::mock(SummitOrder::class);
            $order->shouldReceive('getId')->andReturn(1);
            $order->shouldReceive('getNumber')->andReturn('ABC_1234');
            $order->shouldReceive('generateQRCode');
            $order->shouldReceive('hasOwner')->andReturnFalse();
            $order->shouldReceive('getOwnerEmail')->andReturn('test@test.com');
            $order->shouldReceive('getOwnerFirstName')->andReturn('test');
            $order->shouldReceive('getOwnerSurname')->andReturn('test');
            $order->shouldReceive('getOwnerFullName')->andReturn('test test');
            $order->shouldReceive('getRawAmount')->andReturn(1000);
            $order->shouldReceive('getFinalAmount')->andReturn(1000);
            $order->shouldReceive('getTaxesAmount')->andReturn(0);
            $order->shouldReceive('getDiscountAmount')->andReturn(0);
            $order->shouldReceive('getCurrency')->andReturn('USD');
            $order->shouldReceive('getQRCode')->andReturn('QR_CODE');
            $order->shouldReceive('getSummit')->andReturn(self::$summit);
            $order->shouldReceive('getTickets')->andReturn([]);
            $order->shouldReceive('setOwner');

            $externalUserApi = \Mockery::mock(IExternalUserApi::class)
                ->shouldIgnoreMissing();

            $externalUserApi->shouldReceive('getUserByEmail')
                ->with('test@test.com')->andReturn([
                    'email'      => 'test@test.com',
                    'id'         => '1',
                    'first_name' => 'test',
                    'last_name'  => 'test',
                ]);

            $externalUserApi->shouldReceive('registerUser')->andReturn(
                [
                    'set_password_link' => 'https://test.com'
                ]
            );

            $this->app->instance(IExternalUserApi::class, $externalUserApi);

            $orderRepository = \Mockery::mock(ISummitOrderRepository::class)->shouldIgnoreMissing();
            $orderRepository->shouldReceive('getById')->with(1)->andReturn($order);

            $this->app->instance(ISummitOrderRepository::class, $orderRepository);

            $memberRepository = \Mockery::mock(IMemberRepository::class)->shouldIgnoreMissing();

            $this->app->instance(IMemberRepository::class, $memberRepository);

            $job = new ProcessSummitOrderPaymentConfirmation($order->getId());
            $job->handle(App::make(ISummitOrderService::class));
        }
        catch (\Exception $ex){
            $this->fail($ex->getMessage());
        }
        $this->assertTrue(true);
    }

    public function testDispatchRefund(){

        \App\Jobs\ProcessOrderRefundRequest::dispatch(
            23,
            10
        );
    }
}