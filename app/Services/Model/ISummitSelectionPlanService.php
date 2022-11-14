<?php namespace App\Services\Model;
/**
 * Copyright 2018 OpenStack Foundation
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

use App\Models\Exceptions\AuthzException;
use App\Models\Foundation\Summit\SelectionPlan;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\summit\Presentation;
use models\summit\PresentationActionType;
use models\summit\Summit;
use models\summit\SummitCategoryChange;
use models\summit\SummitPresentationComment;

/**
 * Interface ISummitSelectionPlanService
 * @package App\Services\Model
 */
interface ISummitSelectionPlanService
{
    /**
     * @param Summit $summit
     * @param array $payload
     * @return SelectionPlan
     * @throws ValidationException
     */
    public function addSelectionPlan(Summit $summit, array $payload);

    /**
     * @param Summit $summit
     * @param int $selection_plan_id
     * @param array $payload
     * @return SelectionPlan
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function updateSelectionPlan(Summit $summit, $selection_plan_id, array $payload);

    /**
     * @param Summit $summit
     * @param int $selection_plan_id
     * @throws EntityNotFoundException
     * @return void
     */
    public function deleteSelectionPlan(Summit $summit, $selection_plan_id);

    /**
     * @param Summit $summit
     * @param int $selection_plan_id
     * @param int $track_group_id
     * @throws EntityNotFoundException
     * @throws ValidationException
     * @return void
     */
    public function addTrackGroupToSelectionPlan(Summit $summit, $selection_plan_id, $track_group_id);

    /**
     * @param Summit $summit
     * @param int $selection_plan_id
     * @param int $track_group_id
     * @throws EntityNotFoundException
     * @throws ValidationException
     * @return void
     */
    public function deleteTrackGroupToSelectionPlan(Summit $summit, $selection_plan_id, $track_group_id);


    /**
     * @param Summit $summit
     * @param string $status
     * @return SelectionPlan|null
     */
    public function getCurrentSelectionPlanByStatus(Summit $summit, $status);

    /**
     * @param Summit $summit
     * @param int $selection_plan_id
     * @param int $presentation_id
     * @return Presentation
     * @throws EntityNotFoundException
     * @throws ValidationException
     * @throws AuthzException
     */
    public function markPresentationAsViewed(Summit $summit, int $selection_plan_id, int $presentation_id):Presentation;

    /**
     * @param Summit $summit
     * @param int $selection_plan_id
     * @param int $presentation_id
     * @param array $payload
     * @return SummitPresentationComment
     * @throws EntityNotFoundException
     * @throws ValidationException
     * @throws AuthzException
     */
    public function addPresentationComment(Summit $summit, int $selection_plan_id, int $presentation_id, array $payload):SummitPresentationComment;

    /**
     * @param Summit $summit
     * @param int $selection_plan_id
     * @param int $presentation_id
     * @param int $new_category_id
     * @return SummitCategoryChange|null
     * @throws EntityNotFoundException
     * @throws ValidationException
     * @throws AuthzException
     */
    public function createPresentationCategoryChangeRequest(Summit $summit, int $selection_plan_id, int $presentation_id, int $new_category_id):?SummitCategoryChange;

    /**
     * @param Summit $summit
     * @param int $selection_plan_id
     * @param int $presentation_id
     * @param int $category_change_request_id
     * @param array $payload
     * @return SummitCategoryChange|null
     * @throws EntityNotFoundException
     * @throws ValidationException
     * @throws AuthzException
     */
    public function resolvePresentationCategoryChangeRequest(Summit $summit, int $selection_plan_id, int $presentation_id, int $category_change_request_id, array $payload):?SummitCategoryChange;


    /**
     * @param Summit $summit
     * @param int $selection_plan_id
     * @param int $event_type_id
     * @throws EntityNotFoundException
     * @throws ValidationException
     * @return void
     */
    public function attachEventTypeToSelectionPlan(Summit $summit, int $selection_plan_id, int $event_type_id);

    /**
     * @param Summit $summit
     * @param int $selection_plan_id
     * @param int $event_type_id
     * @throws EntityNotFoundException
     * @throws ValidationException
     * @return void
     */
    public function detachEventTypeFromSelectionPlan(Summit $summit, int $selection_plan_id, int $event_type_id);

    /**
     * @param Summit $summit
     * @param int $selection_plan_id
     * @param int $type_id
     * @param array $payload
     * @return PresentationActionType
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function upsertAllowedPresentationActionType(
        Summit $summit, int $selection_plan_id, int $type_id, array $payload): PresentationActionType;

    /**
     * @param Summit $summit
     * @param int $selection_plan_id
     * @param int $type_id
     * @return void
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function removeAllowedPresentationActionType(Summit $summit, int $selection_plan_id, int $type_id);
}