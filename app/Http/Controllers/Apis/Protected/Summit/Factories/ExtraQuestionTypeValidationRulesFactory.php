<?php namespace App\Http\Controllers;
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

use App\Models\Foundation\ExtraQuestions\ExtraQuestionTypeConstants;

/**
 * Class ExtraQuestionTypeValidationRulesFactory
 * @package App\Http\Controllers
 */
abstract class ExtraQuestionTypeValidationRulesFactory
{
    /**
     * @param array $data
     * @param bool $update
     * @return array
     */
    public static function build(array $data, $update = false){

        if($update){
            return [
                'name'        => 'sometimes|string',
                'type'        => 'sometimes|string|in:'.implode(",", ExtraQuestionTypeConstants::ValidQuestionTypes),
                'label'       => 'sometimes|string',
                'mandatory'   => 'sometimes|boolean',
                'placeholder' => 'sometimes|nullable|string',
                'order'       => 'sometimes|integer|min:1',
                'max_selected_values' => 'sometimes|integer|min:0',
            ];
        }

        return [
            'name'        => 'required|string',
            'type'        => 'required|string|in:'.implode(",", ExtraQuestionTypeConstants::ValidQuestionTypes),
            'label'       => 'required|string',
            'mandatory'   => 'required|boolean',
            'placeholder' => 'sometimes|nullable|string',
            'max_selected_values' => 'sometimes|integer|min:0',
        ];
    }
}