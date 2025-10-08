<?php namespace App\Jobs;
/*
 * Copyright 2025 OpenStack Foundation
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
use App\Services\Model\ISummitOrderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use models\summit\SummitAttendeeTicketRefundRequest;
use models\summit\SummitOrder;

final class ProcessPaymentGatewayRefundJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    // no timeout
    public int $timeout = 0;
    /*
    /**
     * @var int
     */
    private int $order_id;

    /**
     * @var int
     */
    private int $request_id;

    /**
     * @param SummitOrder $order
     * @param SummitAttendeeTicketRefundRequest $request
     */
    public function __construct(SummitOrder $order, SummitAttendeeTicketRefundRequest $request)
    {
        $this->request_id = $request->getId();
        $this->order_id = $order->getId();
    }

    /**
     * @param ISummitOrderService $service
     * @return void
     */
    public function handle(ISummitOrderService $service):void
    {
        Log::debug
        (
            sprintf
            (
                "ProcessPaymentGatewayRefundJob::handle order id %s request id %s",
                $this->order_id,
                $this->request_id
            )
        );

        $service->processPaymentGatewayRefundRequest($this->order_id, $this->request_id);
    }

    public function failed(\Throwable $exception)
    {
        Log::error($exception);
    }

}