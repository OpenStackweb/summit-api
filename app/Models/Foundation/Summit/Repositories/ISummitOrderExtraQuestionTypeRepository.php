<?php namespace App\Models\Foundation\Summit\Repositories;
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
use models\summit\Summit;
use models\summit\SummitOrder;
use models\summit\SummitOrderExtraQuestionType;
use models\utils\IBaseRepository;
/**
 * Interface ISummitOrderExtraQuestionTypeRepository
 * @package App\Models\Foundation\Summit\Repositories
 */
interface ISummitOrderExtraQuestionTypeRepository extends IBaseRepository
{
    /**
     * @param Summit $summit
     * @return array
     */
    public function getQuestionsMetadata(Summit $summit);

    /**
     * @param SummitOrderExtraQuestionType $questionType
     * @return bool
     */
    public function hasAnswers(SummitOrderExtraQuestionType $questionType):bool;

    /**
     * @param SummitOrderExtraQuestionType $questionType
     * @return void
     */
    public function deleteAnswersFrom(SummitOrderExtraQuestionType $questionType):void;

}