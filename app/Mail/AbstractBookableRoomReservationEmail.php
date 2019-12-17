<?php namespace App\Mail;
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
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use models\summit\SummitRoomReservation;
/**
 * Class AbstractBookableRoomReservationEmailextends
 * @package App\Mail
 */
abstract class AbstractBookableRoomReservationEmail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @var string
     */
    public $owner_fullname;

    /**
     * @var string
     */
    public $owner_email;

    /**
     * @var string
     */
    public $room_complete_name;

    /**
     * @var int
     */
    public $room_capacity;

    /**
     * @var string
     */
    public $reservation_start_datetime;

    /**
     * @var string
     */
    public $reservation_end_datetime;

    /**
     * @var string
     */
    public $reservation_created_datetime;

    /**
     * @var int
     */
    public $reservation_amount;

    /**
     * @var int
     */
    public $reservation_id;

    /**
     * @var int
     */
    public $reservation_refunded_amount;

    /**
     * @var string
     */
    public $reservation_currency;

    /**
     * @var string
     */
    public $summit_name;

    /**
     * AbstractBookableRoomReservationEmail constructor.
     * @param SummitRoomReservation $reservation
     */
    public function __construct(SummitRoomReservation $reservation)
    {
        $this->owner_fullname = $reservation->getOwner()->getFullName();
        $this->owner_email = $reservation->getOwner()->getEmail();
        $this->room_complete_name = $reservation->getRoom()->getCompleteName();
        $this->reservation_start_datetime = $reservation->getLocalStartDatetime()->format("Y-m-d H:i:s");
        $this->reservation_end_datetime = $reservation->getLocalEndDatetime()->format("Y-m-d H:i:s");
        $this->reservation_created_datetime = $reservation->getCreated()->format("Y-m-d H:i:s");
        $this->reservation_amount = $reservation->getAmount();
        $this->reservation_currency = $reservation->getCurrency();
        $this->reservation_id = $reservation->getId();
        $this->room_capacity = $reservation->getRoom()->getCapacity();
        $this->summit_name = $reservation->getRoom()->getSummit()->getName();
        $this->reservation_refunded_amount = $reservation->getRefundedAmount();
    }

}
