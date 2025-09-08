<?php namespace App\Services\Model;
/**
 * Copyright 2025 OpenStack Foundation
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
use models\main\Member;
use models\summit\RSVP;
use models\summit\Summit;
use models\summit\SummitEvent;

interface ISummitRSVPService
{
    /**
     * @param Summit $summit
     * @param Member $member
     * @param int $event_id
     * @param array $payload
     * @return RSVP
     * @throws \Exception
     */
    public function rsvpEvent(Summit $summit, Member $member, int $event_id, array $payload = []): RSVP;

    /**
     * @param Summit $summit
     * @param Member $member
     * @param int $event_id
     * @throws \Exception
     */
    public function unRSVPEvent(Summit $summit, Member $member, int $event_id):void;

    /**
     * @param SummitEvent $event
     * @param int $rsvp_id
     * @param array $payload
     * @return RSVP
     */
    public function update(SummitEvent $event, int $rsvp_id, array $payload): RSVP;

    /**
     * @param SummitEvent $event
     * @param int $rsvp_id
     * @return void
     */
    public function delete(SummitEvent $event, int $rsvp_id): void;

    /**
     * @param Summit $summit
     * @param int $event_id
     * @param array $payload
     * @return RSVP
     */
    public function createRSVPFromPayload(Summit $summit, int $event_id, array $payload):RSVP;
}