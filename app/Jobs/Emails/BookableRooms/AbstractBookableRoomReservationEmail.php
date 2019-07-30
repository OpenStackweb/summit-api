<?php namespace App\Jobs\Emails\BookableRooms;
/**
 * Copyright 2020 OpenStack Foundation
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

use App\Jobs\Emails\AbstractEmailJob;
use models\summit\SummitRoomReservation;

/**
 * Class AbstractBookableRoomReservationEmail
 * @package App\Jobs\Emails\BookableRooms
 */
abstract class AbstractBookableRoomReservationEmail extends AbstractEmailJob
{

    protected function getTo(SummitRoomReservation $reservation):string {
        return $reservation->getOwner()->getEmail();
    }

    /**
     * AbstractBookableRoomReservationEmail constructor.
     * @param string $to
     * @param SummitRoomReservation $reservation
     */
    public function __construct(SummitRoomReservation $reservation)
    {
        $payload = [];
        $room = $reservation->getRoom();
        $summit = $room->getSummit();
        $payload['owner_fullname'] = $reservation->getOwner()->getFullName();
        $payload['owner_email'] = $reservation->getOwner()->getEmail();
        $payload['room_complete_name'] = $room->getCompleteName();
        $payload['reservation_start_datetime'] = $reservation->getLocalStartDatetime()->format("Y-m-d H:i:s");
        $payload['reservation_end_datetime'] = $reservation->getLocalEndDatetime()->format("Y-m-d H:i:s");
        $payload['reservation_created_datetime'] = $reservation->getCreated()->format("Y-m-d H:i:s");
        $payload['reservation_amount'] = $reservation->getAmount();
        $payload['reservation_currency'] = $reservation->getCurrency();
        $payload['reservation_id'] = $reservation->getId();
        $payload['room_capacity'] = $room->getCapacity();
        $payload['summit_name'] = $summit->getName();
        $payload['summit_logo'] = $summit->getLogoUrl();
        $payload['reservation_refunded_amount'] = $reservation->getRefundedAmount();

        $template_identifier = $this->getEmailTemplateIdentifierFromEmailEvent($summit);
        parent::__construct($payload, $template_identifier, $this->getTo($reservation));
    }
}