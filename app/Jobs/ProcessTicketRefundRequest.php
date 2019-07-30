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
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use libs\utils\ITransactionService;
use models\summit\IPaymentConstants;
use models\summit\ISummitAttendeeTicketRepository;
use models\summit\SummitAttendeeTicket;

/**
 * Class ProcessTicketRefundRequest
 * @package App\Jobs
 */
class ProcessTicketRefundRequest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5;

    /**
     * @var int
     */
    private $ticket_id;

    /**
     * @var int
     */
    private $requested_n_days_before_summit;

    /**
     * ProcessTicketRefundRequest constructor.
     * @param int $ticket_id
     * @param int $requested_n_days_before_summit
     */
    public function __construct(int $ticket_id, int $requested_n_days_before_summit)
    {
        $this->ticket_id = $ticket_id;
        $this->requested_n_days_before_summit = $requested_n_days_before_summit;
    }

    /**
     * @param IBuildDefaultPaymentGatewayProfileStrategy $default_payment_gateway_strategy
     * @param ISummitAttendeeTicketRepository $repository
     * @param ITransactionService $tx_service
     * @throws \Exception
     */
    public function handle(
        IBuildDefaultPaymentGatewayProfileStrategy $default_payment_gateway_strategy,
        ISummitAttendeeTicketRepository $repository,
        ITransactionService $tx_service
    )
    {
        $tx_service->transaction(function () use ($default_payment_gateway_strategy, $repository) {

            $ticket = $repository->getByIdExclusiveLock($this->ticket_id);

            if (is_null($ticket) || !$ticket instanceof SummitAttendeeTicket || !$ticket->isRefundRequested()) return;


            $order = $ticket->getOrder();
            $summit = $order->getSummit();
            $payment_gateway = $summit->getPaymentGateWayPerApp
            (
                IPaymentConstants::ApplicationTypeRegistration,
                $default_payment_gateway_strategy
            );

            if (is_null($payment_gateway)) {
                Log::warning(sprintf("Payment configuration is not set for summit %s", $summit->getId()));
                return;
            }

            $policy = $summit->getRefundPolicyForRefundRequest($this->requested_n_days_before_summit);
            if (is_null($policy)) return;
            $rate = $policy->getRefundRate();
            if ($rate <= 0) return;
            $amount_2_refund = ($rate / 100.00) * $ticket->getFinalAmount();

            if (!$order->hasPaymentInfo()) {
                Log::warning(sprintf("order %s has not payment info ", $order->getId()));
                return;
            }

            try {
                $payment_gateway->refundPayment(
                    $order->getPaymentGatewayCartId(),
                    $amount_2_refund,
                    $ticket->getCurrency()
                );
            } catch (\Exception $ex) {
                Log::warning($ex);
                return;
            }

            $ticket->refund($amount_2_refund);

        });
    }
}
