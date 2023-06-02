<?php namespace App\Services\Model;
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

use App\Services\Apis\ExternalRegistrationFeeds\IExternalRegistrationFeed;
use models\summit\Summit;
use models\summit\SummitAttendee;

/**
 * Interface IRegistrationIngestionService
 * @package App\Services\Model
 */
interface IRegistrationIngestionService
{

    public function ingestAllSummits():void;

    /**
     * @param Summit $summit
     * @return void
     */
    public function ingestSummit(Summit $summit):void;

    /**
     * @param $summit_id
     * @param $index
     * @param $external_attendee
     * @param IExternalRegistrationFeed $feed
     * @return SummitAttendee|null
     */
    public function ingestExternalAttendee($summit_id, $index, $external_attendee, IExternalRegistrationFeed $feed):?SummitAttendee;
}