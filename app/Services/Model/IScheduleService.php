<?php namespace App\Services\Model;
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

use App\Models\Foundation\Summit\ProposedSchedule\SummitProposedSchedule;
use App\Models\Foundation\Summit\ProposedSchedule\SummitProposedScheduleSummitEvent;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use utils\Filter;

/**
 * Interface IScheduleService
 * @package App\Services\Model
 */
interface IScheduleService
{
    /**
     * @param string $source
     * @param int $presentation_id
     * @param array $payload
     * @return SummitProposedScheduleSummitEvent
     */
    public function publishProposedActivityToSource(
        string $source, int $presentation_id, array $payload):SummitProposedScheduleSummitEvent;

    /**
     * @param int $schedule_id
     * @param int $presentation_id
     * @param array $payload
     * @return SummitProposedScheduleSummitEvent
     */
    public function publishProposedActivity(
        int $schedule_id, int $presentation_id, array $payload):SummitProposedScheduleSummitEvent;

    /**
     * @param string $source
     * @param int $presentation_id
     * @return void
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function unPublishProposedActivity(string $source, int $presentation_id):void;

    /**
     * @param string $source
     * @param int $summit_id
     * @param array $payload
     * @param Filter|null $filter;
     * @return SummitProposedSchedule
     * @throws EntityNotFoundException
     * @throws \Exception
     */
    public function publishAll(string $source, int $summit_id, array $payload, ?Filter $filter = null):SummitProposedSchedule;
}