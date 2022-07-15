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
use App\Http\ValidationRulesFactories\AbstractValidationRulesFactory;
/**
 * Class SponsoredProjectValidationRulesFactory
 * @package App\Http\Controllers
 */
final class SponsoredProjectValidationRulesFactory extends AbstractValidationRulesFactory
{

    /**
     * @param array $payload
     * @return array
     */
    public static function buildForAdd(array $payload = []): array
    {
        return [
            'name' => 'required|string',
            'description' => 'sometimes|string',
            'is_active' => 'sometimes|boolean',
            'should_show_on_nav_bar' => 'sometimes|boolean',
            'site_url' => 'sometimes|url',
        ];
    }

    /**
     * @param array $payload
     * @return array
     */
    public static function buildForUpdate(array $payload = []): array
    {
        return [
            'name' => 'sometimes|string',
            'description' => 'sometimes|string',
            'is_active' => 'sometimes|boolean',
            'should_show_on_nav_bar' => 'sometimes|boolean',
            'site_url' => 'sometimes|url',
        ];
    }
}