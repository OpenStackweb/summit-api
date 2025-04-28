<?php namespace App\Services\Model;
/**
 * Copyright 2021 OpenStack Foundation
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
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\main\Member;
use models\summit\Summit;
use models\summit\SummitSelectedPresentationList;
/**
 * Interface ISummitSelectedPresentationListService
 * @package App\Services\Model
 */
interface ISummitSelectedPresentationListService
{
    /**
     * @param Summit $summit
     * @param int $selection_plan_id
     * @param int $track_id
     * @return SummitSelectedPresentationList
     * @throws EntityNotFoundException
     */
    public function getTeamSelectionList(Summit $summit, int $selection_plan_id, int $track_id):SummitSelectedPresentationList;

    /**
     * @param Summit $summit
     * @param int $selection_plan_id
     * @param int $track_id
     * @return SummitSelectedPresentationList
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function createTeamSelectionList(Summit $summit, int $selection_plan_id, int $track_id):SummitSelectedPresentationList;

    /**
     * @param Summit $summit
     * @param int $selection_plan_id
     * @param int $track_id
     * @param int $owner_id
     * @return SummitSelectedPresentationList
     * @throws EntityNotFoundException
     */
    public function getIndividualSelectionList(Summit $summit, int $selection_plan_id, int $track_id, int $owner_id):SummitSelectedPresentationList;

    /**
     * @param Summit $summit
     * @param int $selection_plan_id
     * @param int $track_id
     * @param int $member_id
     * @return SummitSelectedPresentationList
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function createIndividualSelectionList(Summit $summit, int $selection_plan_id, int $track_id, int $member_id):SummitSelectedPresentationList;

    /**
     * @param Member $current_member
     * @param Summit $summit
     * @param int $selection_plan_id
     * @param int $track_id
     * @param int $list_id
     * @param array $payload
     * @return SummitSelectedPresentationList
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function reorderList(Member $current_member, Summit $summit, int $selection_plan_id, int $track_id, int $list_id, array $payload):SummitSelectedPresentationList;

    /**
     * @param Summit $summit
     * @param int $selection_plan_id
     * @param int $track_id
     * @param string $collection
     * @param int $presentation_id
     * @return SummitSelectedPresentationList
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function assignPresentationToMyIndividualList(Summit $summit, int $selection_plan_id, int $track_id, string $collection, int $presentation_id):SummitSelectedPresentationList;

    /**
     * @param Summit $summit
     * @param int $track_id
     * @param int $presentation_id
     * @return SummitSelectedPresentationList
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function removePresentationFromMyIndividualList(Summit $summit, int $selection_plan_id, int $track_id, int $presentation_id):SummitSelectedPresentationList;
}