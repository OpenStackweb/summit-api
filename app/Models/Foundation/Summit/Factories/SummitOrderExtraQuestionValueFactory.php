<?php namespace App\Models\Foundation\Factories;
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
use App\Models\Foundation\ExtraQuestions\ExtraQuestionTypeValue;
/**
 * Class ExtraQuestionTypeValueFactory
 * @package App\Models\Foundation\Factories
 */
final class ExtraQuestionTypeValueFactory
{
    /**
     * @param array $data
     * @return ExtraQuestionTypeValue
     */
    public static function build(array $data):ExtraQuestionTypeValue{
        return self::populate(new ExtraQuestionTypeValue, $data);
    }

    /**
     * @param ExtraQuestionTypeValue $value
     * @param array $data
     * @return ExtraQuestionTypeValue
     */
    public static function populate(ExtraQuestionTypeValue $value, array $data):ExtraQuestionTypeValue{

        if(isset($data['label']))
            $value->setLabel(trim($data['label']));

        if(isset($data['value']))
            $value->setValue(trim($data['value']));

        return $value;
    }
}