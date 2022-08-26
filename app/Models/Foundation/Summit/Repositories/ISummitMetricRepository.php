<?php namespace App\Models\Foundation\Summit\Repositories;
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
use models\main\Member;
use models\summit\SummitAttendee;
use models\summit\SummitEvent;
use models\summit\SummitMetric;
use models\summit\SummitVenueRoom;
use models\utils\IBaseRepository;
/**
 * Interface ISummitMetricRepository
 * @package App\Models\Foundation\Summit\Repositories
 */
interface ISummitMetricRepository extends IBaseRepository
{
    /**
     * @param Member $member
     * @param string $type
     * @param int|null $source_id
     * @return SummitMetric|null
     */
    public function getNonAbandoned(Member $member, string $type, ?int $source_id = null):?SummitMetric;

    /**
     * @param SummitAttendee $attendee
     * @param SummitVenueRoom|null $room
     * @param SummitEvent|null $event
     * @return SummitMetric|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getNonAbandonedOnSiteMetric(SummitAttendee $attendee, ?SummitVenueRoom $room , ?SummitEvent $event): ?SummitMetric;

}