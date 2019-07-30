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
use models\summit\SummitOrderExtraQuestionType;
/**
 * Class SummitOrderExtraQuestionTypeFactory
 * @package App\Models\Foundation\Summit\Factories
 */
final class SummitOrderExtraQuestionTypeFactory
{
    /**
     * @param array $data
     * @return SummitOrderExtraQuestionType
     */
    public static function build(array $data):SummitOrderExtraQuestionType{
        return self::populate(new SummitOrderExtraQuestionType, $data);
    }

    /**
     * @param SummitOrderExtraQuestionType $question
     * @param array $data
     * @return SummitOrderExtraQuestionType
     */
    public static function populate(SummitOrderExtraQuestionType $question, array $data):SummitOrderExtraQuestionType{

        if(isset($data['name']))
            $question->setName(trim($data['name']));

        if(isset($data['label']))
            $question->setLabel(trim($data['label']));

        if(isset($data['type']))
            $question->setType(trim($data['type']));

        if(isset($data['usage']))
            $question->setUsage(trim($data['usage']));

        if(isset($data['printable']))
            $question->setPrintable(boolval($data['printable']));

        if(isset($data['placeholder']))
            $question->setPlaceholder(trim($data['placeholder']));

        if(isset($data['mandatory']))
            $question->setMandatory(boolval($data['mandatory']));


        return $question;
    }
}