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
use Illuminate\Support\Facades\Config;
/**
 * Class BookableRoomReservationRefundRequestedAdminEmail
 * @package App\Mail
 */
final class BookableRoomReservationRefundRequestedAdminEmail extends AbstractBookableRoomReservationEmail
{

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $subject = sprintf("[%s] There is a new reservation refund request available!", $this->summit_name);
        $from = Config::get("mail.from");
        if(empty($from)){
            throw new \InvalidArgumentException("mail.from is not set");
        }
        return $this->from($from)
            ->to(Config::get("bookable_rooms.admin_email"))
            ->subject($subject)
            ->view('emails.bookable_rooms.reservation_refund_requested_admin');
    }
}
