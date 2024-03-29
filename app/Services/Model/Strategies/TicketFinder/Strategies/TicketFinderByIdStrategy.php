<?php namespace App\Services\Model\Strategies\TicketFinder\Strategies;
/*
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
use App\Services\Model\Strategies\TicketFinder\ITicketFinderStrategy;
use Illuminate\Support\Facades\Log;
use models\summit\SummitAttendeeTicket;
/**
 * Class TicketFinderByIdStrategy
 * @package App\Services\Model\Strategies\TicketFinder\Strategies
 */
final class TicketFinderByIdStrategy
    extends AbstractTicketFinderStrategy
    implements ITicketFinderStrategy
{

    /**
     * @return SummitAttendeeTicket|null
     */
    public function find(): ?SummitAttendeeTicket
    {
        Log::debug(sprintf("TicketFinderByIdStrategy::find id %s", $this->ticket_criteria));
        $res = $this->repository->getById(intval($this->ticket_criteria));
        return $res instanceof SummitAttendeeTicket ? $res : null;
    }
}