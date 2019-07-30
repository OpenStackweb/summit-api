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
use App\Services\Model\ILocationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Exception;
/**
 * Class SummitRoomReservationRevocationCommand
 * @package App\Console\Commands
 */
final class SummitRoomReservationRevocationCommand extends Command {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'summit:room-reservation-revocation';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'summit:room-reservation-revocation';


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Revokes all reserved bookable room reservations after N minutes';


    /**
     * @var ILocationService
     */
    private $location_service;


    /**
     * SummitRoomReservationRevocationCommand constructor.
     * @param ILocationService $location_service
     */
    public function __construct
    (
        ILocationService $location_service
    )
    {
        parent::__construct();
        $this->location_service = $location_service;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $enabled = Config::get("bookable_rooms.enable_bookable_rooms_reservation_revocation", false);
        if(!$enabled){
            $this->info("task is not enabled!");
            return false;
        }

        try {

            $this->info("processing summit room reservations");
            $start   = time();
            $lifetime = intval(Config::get("bookable_rooms.reservation_lifetime", 30));
            Log::info(sprintf("SummitRoomReservationRevocationCommand: using lifetime of %s ", $lifetime));
            $this->location_service->revokeBookableRoomsReservedOlderThanNMinutes($lifetime);
            $end   = time();
            $delta = $end - $start;
            $this->info(sprintf("execution call %s seconds", $delta));
        }
        catch (Exception $ex) {
            Log::error($ex);
        }
    }
}