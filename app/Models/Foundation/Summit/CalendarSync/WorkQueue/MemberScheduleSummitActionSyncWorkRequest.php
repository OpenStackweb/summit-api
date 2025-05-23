<?php namespace models\summit\CalendarSync\WorkQueue;
/**
 * Copyright 2017 OpenStack Foundation
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
use models\summit\CalendarSync\CalendarSyncInfo;
use Doctrine\ORM\Mapping AS ORM;

/**
 * Class MemberScheduleSummitActionSyncWorkRequest
 * @package models\summit\CalendarSync\WorkQueue
 */
#[ORM\Table(name: 'MemberScheduleSummitActionSyncWorkRequest')]
#[ORM\Entity]
class MemberScheduleSummitActionSyncWorkRequest
    extends AbstractCalendarSyncWorkRequest
{
    /**
     * @var Member
     */
    #[ORM\JoinColumn(name: 'OwnerID', referencedColumnName: 'ID')]
    #[ORM\ManyToOne(targetEntity: \models\main\Member::class, cascade: ['persist'])]
    protected $owner;

    /**
     * @var CalendarSyncInfo
     */
    #[ORM\JoinColumn(name: 'CalendarSyncInfoID', referencedColumnName: 'ID')]
    #[ORM\ManyToOne(targetEntity: \models\summit\CalendarSync\CalendarSyncInfo::class, cascade: ['persist'])]
    protected $calendar_sync_info;

    /**
     * @return Member
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @param Member $owner
     */
    public function setOwner($owner)
    {
        $this->owner = $owner;
    }

    /**
     * @return CalendarSyncInfo
     */
    public function getCalendarSyncInfo()
    {
        return $this->calendar_sync_info;
    }

    /**
     * @param CalendarSyncInfo $calendar_sync_info
     */
    public function setCalendarSyncInfo($calendar_sync_info)
    {
        $this->calendar_sync_info = $calendar_sync_info;
    }

    public function getSubType(){
        return null;
    }
}