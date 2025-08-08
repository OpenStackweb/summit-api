<?php namespace App\Events\RSVP;
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

use App\Events\SummitEventAction;
use models\summit\RSVP;

/**
 * Class RSVPAction
 * @package App\Events
 */
class RSVPAction extends SummitEventAction
{
    /**
     * @var int
     */
    protected int $rsvp_id;

    protected int $member_id;

    public function __construct(RSVP $rsvp){

        $this->rsvp_id = $rsvp->getId();
        $this->member_id = $rsvp->getOwnerId();
        parent::__construct($rsvp->getEventId());
    }

    /**
     * @return int
     */
    public function getRsvpId(): int
    {
        return $this->rsvp_id;
    }

    /**
     * @return int
     */
    public function getMemberId(): int{
        return $this->member_id;
    }

}