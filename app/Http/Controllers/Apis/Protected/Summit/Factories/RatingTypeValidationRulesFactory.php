<?php namespace App\Http\Controllers;
/**
 * Copyright 2022 OpenStack Foundation
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
 * Class RatingTypeValidationRulesFactory
 * @package App\Http\Controllers
 */
final class RatingTypeValidationRulesFactory extends AbstractValidationRulesFactory
{
    /**
     * @param array $payload
     * @return array
     */
    public static function buildForAdd(array $payload = []): array
    {
        return [
            'weight'  => 'required|numeric|min:0',
            'name'    => 'required|string',
            'order'   => 'sometimes|integer|min:1',
        ];
    }

    /**
     * @param array $payload
     * @return array
     */
    public static function buildForUpdate(array $payload = []): array
    {
        return [
            'weight'  => 'sometimes|numeric|min:0',
            'name'    => 'sometimes|string',
            'order'   => 'sometimes|required|integer|min:1',
        ];
    }
}