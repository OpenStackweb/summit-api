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
use App\Services\Model\ISummitOrderService;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
/**
 * Class ProcessSummitOrderPaymentConfirmation
 * @package App\Jobs
 */
class ProcessSummitOrderPaymentConfirmation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var int
     */
    public $tries = 5;

    /**
     * @var int
     */
    private $order_id;

    /**
     * ProcessSummitOrderPaymentConfirmation constructor.
     * @param int $order_id
     */
    public function __construct(int $order_id)
    {
        $this->order_id = $order_id;
    }

    /**
     * @param ISummitOrderService $orderService
     * @throws \Exception
     */
    public function handle
    (
        ISummitOrderService $orderService
    )
    {
        try{
            Log::debug(sprintf("ProcessSummitOrderPaymentConfirmation::handle order %s", $this->order_id));
            $orderService->processOrderPaymentConfirmation($this->order_id);
        }
        catch (\Exception $ex){
            Log::error($ex);
            throw $ex;
        }
    }
}
