<?php namespace App\Services\Model;
/**
 * Copyright 2019 OpenStack Foundation
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
use models\summit\Summit;
use models\summit\SummitOrderExtraQuestionType;
use models\summit\SummitOrderExtraQuestionValue;
/**
 * Interface ISummitOrderExtraQuestionTypeService
 * @package App\Services\Model
 */
interface ISummitOrderExtraQuestionTypeService
{
    /**
     * @param Summit $summit
     * @param array $payload
     * @return SummitOrderExtraQuestionType
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function addOrderExtraQuestion(Summit $summit, array $payload):SummitOrderExtraQuestionType;


    /**
     * @param Summit $summit
     * @param int $question_id
     * @param array $payload
     * @return SummitOrderExtraQuestionType
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function updateOrderExtraQuestion(Summit $summit, int $question_id, array $payload):SummitOrderExtraQuestionType;


    /**
     * @param Summit $summit
     * @param int $question_id
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function deleteOrderExtraQuestion(Summit $summit, int $question_id):void;

    /**
     * @param Summit $summit
     * @param int $question_id
     * @param array $payload
     * @return SummitOrderExtraQuestionValue
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function addOrderExtraQuestionValue(Summit $summit, int $question_id, array $payload):SummitOrderExtraQuestionValue;

    /**
     * @param Summit $summit
     * @param int $question_id
     * @param int $value_id
     * @param array $payload
     * @return SummitOrderExtraQuestionValue
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function updateOrderExtraQuestionValue(Summit $summit, int $question_id, int $value_id, array $payload):SummitOrderExtraQuestionValue;

    /**
     * @param Summit $summit
     * @param int $question_id
     * @param int $value_id
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function deleteOrderExtraQuestionValue(Summit $summit, int $question_id, int $value_id):void;

}