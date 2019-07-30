<?php namespace App\Events;
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

/**
 * Class RequestedSummitAttendeeTicketRefund
 * @package App\Events
 */
class RequestedSummitAttendeeTicketRefund extends SummitAttendeeTicketAction
{
    /**
     * @var int
     */
    private $days_before_event_starts;

    /**
     * RequestedSummitAttendeeTicketRefund constructor.
     * @param int $order_id
     * @param int $days_before_event_starts
     */
    public function __construct(int $order_id, int $days_before_event_starts)
    {
        parent::__construct($order_id);
        $this->days_before_event_starts = $days_before_event_starts;
    }

    /**
     * @return int
     */
    public function getDaysBeforeEventStarts(): int
    {
        return $this->days_before_event_starts;
    }

}