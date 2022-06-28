<?php namespace App\Services\Model;
/**
 * Copyright 2022 OpenStack Foundation
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

use App\Models\Foundation\Summit\SelectionPlan;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\summit\SelectionPlanActionType;
/**
 * Interface ISummitSelectionPlanActionTypeService
 * @package App\Services\Model
 */
interface ISummitSelectionPlanActionTypeService
{
    /**
     * @param SelectionPlan $selection_plan
     * @param array $payload
     * @return SelectionPlanActionType
     * @throws ValidationException
     */
    public function add(SelectionPlan $selection_plan, array $payload):SelectionPlanActionType;

    /**
     * @param SelectionPlan $selection_plan
     * @param int $action_type_id
     * @param array $payload
     * @return SelectionPlanActionType|null
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function update(SelectionPlan $selection_plan, int $action_type_id, array $payload):?SelectionPlanActionType;

    /**
     * @param SelectionPlan $selection_plan
     * @param int $action_type_id
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function delete(SelectionPlan $selection_plan, int $action_type_id):void;
}