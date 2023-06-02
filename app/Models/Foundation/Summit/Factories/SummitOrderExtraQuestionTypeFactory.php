<?php namespace App\Models\Foundation\Summit\Factories;
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
use App\Models\Foundation\ExtraQuestions\ExtraQuestionType;
use App\Models\Foundation\ExtraQuestions\Factories\ExtraQuestionTypeFactory;
use models\summit\SummitOrderExtraQuestionType;
/**
 * Class SummitOrderExtraQuestionTypeFactory
 * @package App\Models\Foundation\Summit\Factories
 */
final class SummitOrderExtraQuestionTypeFactory extends ExtraQuestionTypeFactory
{
    /**
     * @param ExtraQuestionType $question
     * @param array $data
     * @return ExtraQuestionType
     * @throws \models\exceptions\ValidationException
     */
    public static function populate(ExtraQuestionType $question, array $data):ExtraQuestionType{

        $question = parent::populate($question, $data);

        if(isset($data['usage']))
            $question->setUsage(trim($data['usage']));

        if(isset($data['external_id']))
            $question->setExternalId(trim($data['external_id']));

        if(isset($data['printable']))
            $question->setPrintable(boolval($data['printable']));

        return $question;
    }

    protected static function getNewEntity(): ExtraQuestionType
    {
        return new SummitOrderExtraQuestionType;
    }
}