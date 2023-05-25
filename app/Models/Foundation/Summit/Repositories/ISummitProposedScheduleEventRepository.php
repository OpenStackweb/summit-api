<?php namespace models\summit;
/**
 * Copyright 2023 OpenStack Foundation
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

use App\Models\Foundation\Summit\ProposedSchedule\SummitProposedScheduleSummitEvent;
use models\utils\IBaseRepository;

/**
 * Interface ISummitProposedScheduleEventRepository
 * @package models\summit
 */
interface ISummitProposedScheduleEventRepository extends IBaseRepository
{
    /**
     * @param int $summit_id
     * @param string $source
     * @param int $event_id
     * @return SummitProposedScheduleSummitEvent|null
     */
    public function getBySummitSourceAndEventId(int $summit_id, string $source, int $event_id):?SummitProposedScheduleSummitEvent;
}