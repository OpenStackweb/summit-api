<?php namespace App\Mail\Schedule;
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
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use models\summit\RSVP;
/**
 * Class RSVPMail
 * @package App\Mail\Schedule
 */
class RSVPMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @var string
     */
    public $owner_fullname;

    /**
     * @var string
     */
    public $event_title;

    /**
     * @var string
     */
    public $event_date;

    /**
     * @var string
     */
    public $event_uri;

    /**
     * @var string
     */
    public $confirmation_number;

    /**
     * @var string
     */
    public $owner_email;

    /**
     * @var string
     */
    public $summit_name;

    /**
     * @var string
     */
    public $summit_schedule_default_event_detail_url;

    /**
     * RSVPMail constructor.
     * @param RSVP $rsvp
     */
    public function __construct(RSVP $rsvp)
    {
        $event                                          = $rsvp->getEvent();
        $summit                                         = $event->getSummit();
        $owner                                          = $rsvp->getOwner();
        $this->owner_fullname                           = $owner->getFullName();
        $this->owner_email                              = $owner->getEmail();
        $this->event_title                              = $event->getTitle();
        $this->event_date                               = $event->getDateNice();
        $this->confirmation_number                      = $rsvp->getConfirmationNumber();
        $this->summit_name                              = $summit->getName();
        $this->summit_schedule_default_event_detail_url = $summit->getScheduleDefaultEventDetailUrl();
        $event_uri                                      = $rsvp->getEventUri();

        if(!empty($event_uri)){
            // we got a valid origin
            $this->event_uri = $event_uri;
        }
        // if we dont have a custom event uri, try to get default one
        if(empty($this->event_uri) && !empty($this->summit_schedule_default_event_detail_url)){
            $this->event_uri  = str_replace(":event_id", $event->getId(), $this->summit_schedule_default_event_detail_url);
        }
    }
}