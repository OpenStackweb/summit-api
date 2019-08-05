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
use App\Models\Foundation\Summit\Repositories\ISummitRoomReservationRepository;
use Illuminate\Support\Facades\Log;
use libs\utils\ITransactionService;
use models\summit\SummitRoomReservation;
use utils\Filter;
use utils\FilterElement;
use utils\PagingInfo;
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
     * @var ISummitRoomReservationRepository
     */
    private $reservations_repository;

    /**
     * @var ITransactionService
     */
    private $tx_service;

    /**
     * SummitRoomReservationRevocationCommand constructor.
     * @param ISummitRoomReservationRepository $reservations_repository
     * @param ITransactionService $tx_service
     */
    public function __construct
    (
        ISummitRoomReservationRepository $reservations_repository,
        ITransactionService $tx_service
    )
    {
        parent::__construct();
        $this->reservations_repository = $reservations_repository;
        $this->tx_service              = $tx_service;
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

            $this->tx_service->transaction(function(){
                $filter = new Filter();
                $filter->addFilterCondition(FilterElement::makeEqual('status', SummitRoomReservation::ReservedStatus));
                $eol = new \DateTime('now', new \DateTimeZone('UTC'));
                $lifetime = intval(Config::get("bookable_rooms.reservation_lifetime", 5));
                $eol->sub(new \DateInterval('PT'.$lifetime.'M'));
                $filter->addFilterCondition(FilterElement::makeLowerOrEqual('created', $eol->getTimestamp() ));
                $page  = $this->reservations_repository->getAllByPage(new PagingInfo(1, 100), $filter);
                foreach($page->getItems() as $reservation){
                    $reservation->cancel();
                }
            });

            $end   = time();
            $delta = $end - $start;
            $this->info(sprintf("execution call %s seconds", $delta));
        }
        catch (Exception $ex) {
            Log::error($ex);
        }
    }
}