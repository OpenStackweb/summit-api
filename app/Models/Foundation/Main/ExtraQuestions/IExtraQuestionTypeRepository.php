<?php namespace App\Models\Foundation\ExtraQuestions;
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
use models\utils\IBaseRepository;
/**
 * Interface IExtraQuestionTypeRepository
 * @package App\Models\Foundation\ExtraQuestions
 */
interface IExtraQuestionTypeRepository extends IBaseRepository
{
    /**
     * @return array
     */
    public function getQuestionsMetadata();

    /**
     * @param ExtraQuestionType $questionType
     * @return bool
     */
    public function hasAnswers(ExtraQuestionType $questionType):bool;

    /**
     * @param ExtraQuestionType $questionType
     * @return void
     */
    public function deleteAnswersFrom(ExtraQuestionType $questionType):void;
}