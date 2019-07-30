<?php namespace App\Jobs;
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

use App\Models\Foundation\Summit\Registration\IBuildDefaultPaymentGatewayProfileStrategy;
use App\Models\Foundation\Summit\Repositories\ISummitOrderRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use libs\utils\ITransactionService;
use models\summit\IPaymentConstants;
use models\summit\SummitOrder;
/**
 * Class ProcessOrderRefundRequest
 * @package App\Jobs
 */
class ProcessOrderRefundRequest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5;

    /**
     * @var int
     */
    private $order_id;

    /**
     * @var int
     */
    private $requested_n_days_before_summit;

    /**
     * ProcessOrderRefundRequest constructor.
     * @param int $order_id
     * @param int $requested_n_days_before_summit
     */
    public function __construct
    (
        int $order_id,
        int $requested_n_days_before_summit
    )
    {
        $this->order_id = $order_id;
        $this->requested_n_days_before_summit = $requested_n_days_before_summit;
    }


    /**
     * @param IBuildDefaultPaymentGatewayProfileStrategy $default_payment_gateway_strategy
     * @param ISummitOrderRepository $repository
     * @param ITransactionService $tx_service
     * @throws \Exception
     */
    public function handle(
        IBuildDefaultPaymentGatewayProfileStrategy $default_payment_gateway_strategy,
        ISummitOrderRepository $repository,
        ITransactionService $tx_service
    )
    {
        $tx_service->transaction(function() use($default_payment_gateway_strategy, $repository){

            Log::debug(sprintf("ProcessOrderRefundRequest::handle: processing for order id %s", $this->order_id));

            $order = $repository->getByIdExclusiveLock($this->order_id);

            if(is_null($order) || !$order instanceof SummitOrder || !$order->isRefundRequested()){
                Log::debug(sprintf("ProcessOrderRefundRequest::handle: order id %s not found", $this->order_id));
            }

            $summit = $order->getSummit();

            $payment_gateway = $summit->getPaymentGateWayPerApp
            (
                IPaymentConstants::ApplicationTypeRegistration,
                $default_payment_gateway_strategy
            );
            if(is_null($payment_gateway)){
                Log::warning(sprintf("Payment configuration is not set for summit %s", $summit->getId()));
                return;
            }

            $policy = $summit->getRefundPolicyForRefundRequest($this->requested_n_days_before_summit);

            if(is_null($policy)){

                Log::debug
                (
                    sprintf
                    (
                        "ProcessOrderRefundRequest::handle: policy not found for order id %s - requested_n_days_before_summit %s summit id %s",
                        $this->order_id,
                        $this->requested_n_days_before_summit,
                        $summit->getId())
                );
                return;
            }
            $rate = $policy->getRefundRate();
            if($rate <= 0){
                Log::debug
                (
                    sprintf
                    (
                        "ProcessOrderRefundRequest::handle: policy id %s has not a valid refund rate %s",
                        $policy->getId(),
                        $rate
                    )
                );
                return;
            }

            $amount_2_refund = ($rate/100.00) * $order->getFinalAmount();

            Log::debug
            (
                sprintf
                (
                    "ProcessOrderRefundRequest::handle: requesting refund to payment gateway with following data amount_2_refund %s - cart id %s - currency %s ",
                    $amount_2_refund,
                    $order->getPaymentGatewayCartId(),
                    $order->getCurrency())
            );

            if(!$order->hasPaymentInfo())
            {
                Log::warning(sprintf("order %s has not payment info ", $order->getId()));
                return;
            }

            try {
                $payment_gateway->refundPayment(
                    $order->getPaymentGatewayCartId(),
                    $amount_2_refund,
                    $order->getCurrency()
                );
            }
            catch(\Exception $ex){
                log::error($ex);
                return;
            }

            $order->refund($amount_2_refund);

        });
    }
}
