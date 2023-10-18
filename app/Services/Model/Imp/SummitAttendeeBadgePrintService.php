<?php namespace services\model;
/**
 * Copyright 2023 OpenStack Foundation
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

use App\Services\Model\AbstractService;
use App\Services\Model\Imp\Traits\ParametrizedSendEmails;
use Illuminate\Support\Facades\Log;
use libs\utils\ITransactionService;
use models\exceptions\EntityNotFoundException;
use models\summit\ISummitAttendeeTicketRepository;
use models\summit\Summit;
use models\summit\SummitAttendeeTicket;

/**
 * Class SummitAttendeeBadgePrintService
 * @package services\model
 */
final class SummitAttendeeBadgePrintService
    extends AbstractService
    implements ISummitAttendeeBadgePrintService
{
    use ParametrizedSendEmails;

    /**
     * @var ISummitAttendeeTicketRepository
     */
    private $ticket_repository;

    /**
     * SpeakerService constructor.
     * @param ISummitAttendeeTicketRepository $ticket_repository
     * @param ITransactionService $tx_service
     */
    public function __construct
    (
        ISummitAttendeeTicketRepository  $ticket_repository,
        ITransactionService $tx_service
    )
    {
        parent::__construct($tx_service);
        $this->ticket_repository = $ticket_repository;
    }

    /**
     * @inheritDoc
     */
    public function deleteBadgePrintsByTicket(Summit $summit, int $ticket_id): void
    {
        $this->tx_service->transaction(function() use($summit, $ticket_id){

            Log::debug(sprintf("SummitAttendeeBadgePrintService::deleteBadgePrintsByTicket: summit id %s, ticket id %s", $summit->getId(), $ticket_id));

            $ticket = $this->ticket_repository->getById($ticket_id);

            if (!$ticket instanceof SummitAttendeeTicket)
                throw new EntityNotFoundException(sprintf("Ticket id %s not found.", $ticket_id));

            $badge = $ticket->getBadge();
            if (!is_null($badge)) $badge->clearPrints();
        });
    }
}