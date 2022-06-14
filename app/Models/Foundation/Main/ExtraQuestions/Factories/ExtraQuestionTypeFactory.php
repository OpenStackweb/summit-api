<?php namespace App\Models\Foundation\ExtraQuestions\Factories;
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
use App\Models\Foundation\ExtraQuestions\ExtraQuestionType;
/**
 * Class ExtraQuestionTypeFactory
 * @package App\Models\Foundation\ExtraQuestions\Factories
 */
abstract class ExtraQuestionTypeFactory
{
    abstract protected static function getNewEntity():ExtraQuestionType;

    /**
     * @param array $data
     * @return ExtraQuestionType
     */
    public static function build(array $data):ExtraQuestionType{
        return static::populate(static::getNewEntity(), $data);
    }

    /**
     * @param ExtraQuestionType $question
     * @param array $data
     * @return ExtraQuestionType
     */
    public static function populate(ExtraQuestionType $question, array $data):ExtraQuestionType{

        if(isset($data['name']))
            $question->setName(trim($data['name']));

        if(isset($data['label']))
            $question->setLabel(trim($data['label']));

        if(isset($data['type']))
            $question->setType(trim($data['type']));

        if(isset($data['placeholder']))
            $question->setPlaceholder(trim($data['placeholder']));

        if(isset($data['mandatory']))
            $question->setMandatory(boolval($data['mandatory']));

        if(isset($data['max_selected_values']))
            $question->setMaxSelectedValues(intval($data['max_selected_values']));

        return $question;
    }
}