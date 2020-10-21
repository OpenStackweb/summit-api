<?php namespace App\Http\Controllers;
/**
 * Copyright 2018 OpenStack Foundation
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
/**
 * Class SummitSelectionPlanValidationRulesFactory
 * @package App\Http\Controllers
 */
final class SummitSelectionPlanValidationRulesFactory
{
    /**
     * @param array $data
     * @param bool $update
     * @return array
     */
    public static function build(array $data, $update = false){
        if($update){
            return [
                'name'                  => 'sometimes|string|max:255',
                'is_enabled'            => 'sometimes|boolean',
                'allow_new_presentations' => 'sometimes|boolean',
                'max_submission_allowed_per_user' => 'sometimes|integer|min:1',
                'submission_begin_date' => 'nullable|date_format:U',
                'submission_end_date'   => 'nullable|required_with:submission_begin_date|date_format:U|after_or_equal:submission_begin_date',
                'voting_begin_date'     => 'nullable|date_format:U',
                'voting_end_date'       => 'nullable|required_with:voting_begin_date|date_format:U|after_or_equal:voting_begin_date',
                'selection_begin_date'  => 'nullable|date_format:U',
                'selection_end_date'    => 'nullable|required_with:selection_begin_date|date_format:U|after_or_equal:selection_begin_date',
            ];
        }
        return [
            'name'                  => 'required|string|max:255',
            'is_enabled'            => 'required|boolean',
            'allow_new_presentations' => 'required|boolean',
            'max_submission_allowed_per_user' => 'sometimes|integer|min:1',
            'submission_begin_date' => 'nullable|date_format:U',
            'submission_end_date'   => 'nullable|required_with:submission_begin_date|date_format:U|after_or_equal:submission_begin_date',
            'voting_begin_date'     => 'nullable|date_format:U',
            'voting_end_date'       => 'nullable|required_with:voting_begin_date|date_format:U|after_or_equal:voting_begin_date',
            'selection_begin_date'  => 'nullable|date_format:U',
            'selection_end_date'    => 'nullable|required_with:selection_begin_date|date_format:U|after_or_equal:selection_begin_date',
        ];
    }
}