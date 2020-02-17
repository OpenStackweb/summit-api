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
use models\summit\SummitEvent;
/**
 * Class ShareEventEmail
 * @package App\Mail\Schedule
 */
class ShareEventEmail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @var string
     */
    public $summit_name;

    /**
     * @var string
     */
    public $event_title;

    /**
     * @var string
     */
    public $from_email;

    /**
     * @var string
     */
    public $to_email;

    /**
     * @var string
     */
    public $event_description;

    /**
     * @var string
     */
    public $event_url;

    public function __construct(string $from_email, string $to_email, string $event_url, SummitEvent $event)
    {
        $this->from_email       = $from_email;
        $this->to_email          = $to_email;
        $this->summit_name       = $event->getSummit()->getName();
        $this->event_title       = $event->getTitle();
        $this->event_description = $event->getAbstract();
        $this->event_url         = $event_url;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $subject = sprintf("[%s] Fwd %s", $this->summit_name, $this->event_title);
        return $this->from($this->from_email)
            ->to($this->to_email)
            ->subject($subject)
            ->view('emails.schedule.share_event');
    }
}