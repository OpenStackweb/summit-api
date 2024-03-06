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
use models\exceptions\ValidationException;
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
    public function handle
    (
        IBuildDefaultPaymentGatewayProfileStrategy $default_payment_gateway_strategy,
        ISummitAttendeeTicketRepository $repository,
        ITransactionService $tx_service
    )
    {
        // todo : move to service layer
        try {
            $tx_service->transaction(function () use ($default_payment_gateway_strategy, $repository) {

                Log::debug(sprintf("ProcessTicketRefundRequest::handle processing ticket %s", $this->ticket_id));
                $ticket = $repository->getByIdExclusiveLock($this->ticket_id);

                if (!$ticket instanceof SummitAttendeeTicket || !$ticket->isRefundRequested()) return;

                $order = $ticket->getOrder();
                $summit = $order->getSummit();
                Log::debug(sprintf("ProcessTicketRefundRequest::handle got ticket %s", $ticket->getNumber()));

                $policy = $summit->getRefundPolicyForRefundRequest($this->requested_n_days_before_summit);
                if (is_null($policy)) return;

                $rate = $policy->getRefundRate();

                Log::debug
                (
                    sprintf
                    (
                        "ProcessTicketRefundRequest::handle got ticket %s and policy %s rate %s",
                        $ticket->getNumber(),
                        $policy->getId(),
                        $rate
                    )
                );

                if ($rate <= 0) return;
                $amount_2_refund = ($rate / 100.00) * $ticket->getNetSellingPrice();

                Log::debug
                (
                    sprintf
                    (
                        "ProcessTicketRefundRequest::handle trying to refund ticket %s amount %s",
                        $ticket->getNumber(),
                        $amount_2_refund
                    )
                );

                if (!$ticket->canRefund($amount_2_refund)) {
                    throw new ValidationException
                    (
                        sprintf
                        (
                            "ProcessTicketRefundRequest::handle Can not request a refund on Ticket %s.",
                            $ticket->getNumber()
                        )
                    );
                }

                $paymentGatewayRes = null;
                $request = $ticket->refund
                (
                    null,
                    $amount_2_refund,
                    $paymentGatewayRes,
                    sprintf
                    (
                        "* AUTOMATIC REFUND FROM POLICY %s.", $policy->getId()
                    )
                );

                if ($order->hasPaymentInfo()){
                    try {

                        $payment_gateway = $summit->getPaymentGateWayPerApp
                        (
                            IPaymentConstants::ApplicationTypeRegistration,
                            $default_payment_gateway_strategy
                        );

                        if (is_null($payment_gateway)) {
                            Log::warning(sprintf("Payment configuration is not set for summit %s", $summit->getId()));
                            return;
                        }


                        Log::debug
                        (
                            sprintf
                            (
                                "ProcessTicketRefundRequest::handle trying to refund on payment gateway cart id %s final amount %s",
                                $order->getPaymentGatewayCartId(),
                                $request->getTotalRefundedAmount()
                            )
                        );
                        $paymentGatewayRes = $payment_gateway->refundPayment
                        (
                            $order->getPaymentGatewayCartId(),
                            $request->getTotalRefundedAmount(),
                            $ticket->getCurrency()
                        );

                        Log::debug
                        (
                            sprintf
                            (
                                "SummitOrderService::refundTicket refunded payment gateway cart id %s payment gateway response %s",
                                $order->getPaymentGatewayCartId(),
                                $paymentGatewayRes
                            )
                        );

                        $request->setPaymentGatewayResult($paymentGatewayRes);

                    } catch (\Exception $ex) {
                        Log::warning($ex);
                        throw new ValidationException($ex->getMessage());
                    }
                }

            });
        }
        catch (\Exception $ex){
            Log::error($ex);
        }
    }
}
