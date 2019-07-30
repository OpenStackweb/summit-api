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
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use libs\utils\ITransactionService;
use models\exceptions\ValidationException;
use models\summit\ISummitTicketTypeRepository;
use models\summit\SummitTicketType;
/**
 * Class CompensateTickets
 * @package App\Jobs
 */
class CompensateTickets implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 10;
    /**
     * @var int
     */
    private $ticket_type_id;

    /**
     * @var int
     */
    private $qty_to_return;


    /**
     * CompensateTickets constructor.
     * @param int $ticket_type_id
     * @param int $qty_to_return
     */
    public function __construct(int $ticket_type_id, int $qty_to_return)
    {
        $this->ticket_type_id = $ticket_type_id;
        $this->qty_to_return = $qty_to_return;
    }


    /**
     * @param ISummitTicketTypeRepository $repository
     * @param ITransactionService $tx_service
     * @throws \Exception
     */
    public function handle(ISummitTicketTypeRepository $repository, ITransactionService $tx_service)
    {
        $tx_service->transaction(function () use ($repository) {

            $ticket_type = $repository->getByIdExclusiveLock($this->ticket_type_id);
            if(is_null($ticket_type) || !$ticket_type instanceof SummitTicketType) return;
            Log::debug(sprintf("CompensateTickets::handle: compensating ticket type %s on %s usages", $this->ticket_type_id, $this->qty_to_return));
            try {
                $ticket_type->restore($this->qty_to_return);
            }
            catch(ValidationException $ex){
                Log::error($ex);
            }
        });
    }
}
