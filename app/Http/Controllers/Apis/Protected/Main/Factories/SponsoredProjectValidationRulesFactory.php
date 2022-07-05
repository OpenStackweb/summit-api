<?php namespace App\Http\Controllers;
/**
 * Copyright 2020 OpenStack Foundation
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
 * Class SponsoredProjectValidationRulesFactory
 * @package App\Http\Controllers
 */
class SponsoredProjectValidationRulesFactory
{
    /**
     * @param array $data
     * @param bool $update
     * @return array
     */
    public static function build(array $data, $update = false){
        if($update){
            return [
                'name' => 'sometimes|string',
                'description' => 'sometimes|string',
                'is_active' => 'sometimes|boolean',
                'nav_bar_title' => 'sometimes|string',
                'should_show_on_nav_bar' => 'sometimes|boolean',
                'learn_more_link' => 'sometimes|url',
                'learn_more_text' => 'sometimes|string',
                'site_url' => 'sometimes|url',
            ];
        }
        return [
            'name' => 'required|string',
            'description' => 'sometimes|string',
            'is_active' => 'sometimes|boolean',
            'nav_bar_title' => 'sometimes|string',
            'should_show_on_nav_bar' => 'sometimes|boolean',
            'learn_more_link' => 'sometimes|url',
            'learn_more_text' => 'sometimes|string',
            'site_url' => 'sometimes|url',
        ];
    }
}