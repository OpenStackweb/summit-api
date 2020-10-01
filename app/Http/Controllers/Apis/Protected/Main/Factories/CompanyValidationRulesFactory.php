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
 * Class CompanyValidationRulesFactory
 * @package App\Http\Controllers
 */
final class CompanyValidationRulesFactory
{
    /**
     * @param array $data
     * @param bool $update
     * @return array
     */
    public static function build(array $data, $update = false)
    {
        if ($update) {

            return [
                'name' => 'sometimes|string',
                'url' => 'nullable|url',
                'display_on_site' => 'nullable|boolean',
                'featured' => 'nullable|boolean',
                'city' => 'nullable|string',
                'state' => 'nullable|string',
                'country' => 'nullable|string',
                'description' => 'nullable|string',
                'industry' => 'nullable|string',
                'products' => 'nullable|string',
                'contributions' => 'nullable|string',
                'contact_email' => 'nullable|email',
                'member_level' => 'nullable|string',
                'admin_email' => 'nullable|email',
                'color' => 'nullable|hex_color',
                'overview' => 'nullable|string',
                'commitment' => 'nullable|string',
                'commitment_author' => 'nullable|string',
            ];
        }

        return [
            'name' => 'required|string',
            'url' => 'nullable|url',
            'display_on_site' => 'nullable|boolean',
            'featured' => 'nullable|boolean',
            'city' => 'nullable|string',
            'state' => 'nullable|string',
            'country' => 'nullable|string',
            'description' => 'nullable|string',
            'industry' => 'nullable|string',
            'products' => 'nullable|string',
            'contributions' => 'nullable|string',
            'contact_email' => 'nullable|email',
            'member_level' => 'nullable|string',
            'admin_email' => 'nullable|email',
            'color' => 'nullable|hex_color',
            'overview' => 'nullable|string',
            'commitment' => 'nullable|string',
            'commitment_author' => 'nullable|string',
        ];
    }
}