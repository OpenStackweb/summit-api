<?php namespace App\Console\Commands;
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
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Class IngestSummitOrderPaymentInfoCommand
 * @package App\Console\Commands
 */
final class IngestSummitOrderPaymentInfoCommand extends Command {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'summit:registration-orders-payment-info-ingest';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'summit:registration-orders-payment-info-ingest';


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ingest missing payment info from Summit registration Orders';


    /**
     * @var ISummitOrderService
     */
    private $order_service;

    /**
     * RegistrationSummitOrderRevocationCommand constructor.
     * @param ISummitOrderService $order_service
     */
    public function __construct(ISummitOrderService $order_service)
    {
        parent::__construct();
        $this->order_service = $order_service;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {


        try {
            $this->info("processing summit orders with missing payment info");
            $start = time();

            $this->order_service->ingestPaymentInfoForRegistrationOrders();
            $end = time();
            $delta = $end - $start;
            $this->info(sprintf("execution call %s seconds", $delta));


        } catch (Exception $ex) {
            Log::error($ex);
        }
    }



}