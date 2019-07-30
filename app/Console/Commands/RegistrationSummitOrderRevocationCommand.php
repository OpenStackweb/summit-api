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
use Illuminate\Support\Facades\Config;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Class RegistrationSummitOrderRevocationCommand
 * @package App\Console\Commands
 */
class RegistrationSummitOrderRevocationCommand extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'summit:order-reservation-revocation';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'summit:order-reservation-revocation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Revokes all reserved orders after N minutes without action';

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

        $enabled = Config::get("registration.enable_orders_reservation_revocation", true);
        if (!$enabled) {
            $this->info("task is not enabled!");
            return false;
        }

        try {
            $this->info("processing summit orders reservations");
            $start = time();
            $lifetime = intval(Config::get("registration.reservation_lifetime", 30));
            Log::info(sprintf("RegistrationSummitOrderRevocationCommand: using lifetime of %s ", $lifetime));

            Log::info("RegistrationSummitOrderRevocationCommand: invoking revokeReservedOrdersOlderThanNMinutes");
            $this->order_service->revokeReservedOrdersOlderThanNMinutes($lifetime);

            $end = time();
            $delta = $end - $start;
            $this->info(sprintf("execution call %s seconds", $delta));

            $start = time();
            Log::info("RegistrationSummitOrderRevocationCommand: invoking confirmOrdersOlderThanNMinutes");
            $this->order_service->confirmOrdersOlderThanNMinutes($lifetime);

            $end = time();
            $delta = $end - $start;
            $this->info(sprintf("execution call %s seconds", $delta));
        } catch (Exception $ex) {
            Log::error($ex);
        }
    }
}
