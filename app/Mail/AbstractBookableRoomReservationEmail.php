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
use Illuminate\Contracts\Queue\ShouldQueue;
use models\summit\SummitRoomReservation;
/**
 * Class AbstractBookableRoomReservationEmailextends
 * @package App\Mail
 */
abstract class AbstractBookableRoomReservationEmail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @var SummitRoomReservation
     */
    public $reservation;

    /**
     * AbstractBookableRoomReservationEmailextends constructor.
     * @param SummitRoomReservation $reservation
     */
    public function __construct(SummitRoomReservation $reservation)
    {
        $this->reservation = $reservation;
    }


}
