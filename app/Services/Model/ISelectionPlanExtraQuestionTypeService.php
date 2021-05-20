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
use App\Models\Foundation\ExtraQuestions\ExtraQuestionTypeValue;
use App\Models\Foundation\Summit\ExtraQuestions\SummitSelectionPlanExtraQuestionType;
use App\Models\Foundation\Summit\SelectionPlan;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\summit\SummitOrderExtraQuestionType;
/**
 * Interface ISelectionPlanExtraQuestionTypeService
 * @package App\Services\Model
 */
interface ISelectionPlanExtraQuestionTypeService
{
    /**
     * @param SelectionPlan $selectionPlan
     * @param array $payload
     * @return SummitOrderExtraQuestionType
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function addExtraQuestion(SelectionPlan $selectionPlan, array $payload):SummitSelectionPlanExtraQuestionType;

    /**
     * @param SelectionPlan $selectionPlan
     * @param int $question_id
     * @param array $payload
     * @return SummitOrderExtraQuestionType
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function updateExtraQuestion(SelectionPlan $selectionPlan, int $question_id, array $payload):SummitSelectionPlanExtraQuestionType;

    /**
     * @param SelectionPlan $selectionPlan
     * @param int $question_id
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function deleteExtraQuestion(SelectionPlan $selectionPlan, int $question_id):void;

    /**
     * @param SelectionPlan $selectionPlan
     * @param int $question_id
     * @param array $payload
     * @return ExtraQuestionTypeValue
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function addExtraQuestionValue(SelectionPlan $selectionPlan, int $question_id, array $payload):ExtraQuestionTypeValue;

    /**
     * @param SelectionPlan $selectionPlan
     * @param int $question_id
     * @param int $value_id
     * @param array $payload
     * @return ExtraQuestionTypeValue
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function updateExtraQuestionValue(SelectionPlan $selectionPlan, int $question_id, int $value_id, array $payload):ExtraQuestionTypeValue;

    /**
     * @param SelectionPlan $selectionPlan
     * @param int $question_id
     * @param int $value_id
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function deleteExtraQuestionValue(SelectionPlan $selectionPlan, int $question_id, int $value_id):void;
}