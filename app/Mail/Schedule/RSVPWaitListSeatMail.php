<?php namespace App\Mail\Schedule;
use Illuminate\Support\Facades\Config;

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

/**
 * Class RSVPWaitListSeatMail
 * @package App\Mail\Schedule
 */
class RSVPWaitListSeatMail extends RSVPMail
{
    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $subject = sprintf("[%s] Waitlist for Event", $this->summit_name);
        $from = Config::get("mail.from");
        if(empty($from)){
            throw new \InvalidArgumentException("mail.from is not set");
        }
        return $this->from($from)
            ->to($this->owner_email)
            ->subject($subject)
            ->view('emails.schedule.rsvp_wait_seat');
    }
}