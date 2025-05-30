<?php namespace models\summit;
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
use models\summit\CalendarSync\WorkQueue\AbstractCalendarSyncWorkRequest;
use models\utils\IBaseRepository;
use utils\PagingInfo;
use utils\PagingResponse;

/**
 * Interface IAbstractCalendarSyncWorkRequestRepository
 * @package models\summit
 */
interface IAbstractCalendarSyncWorkRequestRepository extends IBaseRepository
{
    /**
     * @param Member $member
     * @param SummitEvent $event
     * @param CalendarSyncInfo $calendar_sync_info
     * @param null|string $type
     * @return AbstractCalendarSyncWorkRequest
     */
    public function getUnprocessedMemberScheduleWorkRequest($member, $event, $calendar_sync_info, $type = null);

    /**
    public function getUnprocessedMemberScheduleWorkRequestActionByPage(PagingInfo $paging_info, $provider = 'ALL');

    /**
     * @param PagingInfo $paging_info
     * @return PagingResponse
     */
    public function getUnprocessedAdminScheduleWorkRequestActionByPage(PagingInfo $paging_info);
}