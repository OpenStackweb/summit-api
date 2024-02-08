<?php namespace App\Http\Controllers;
/*
 * Copyright 2024 OpenStack Foundation
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

use models\summit\Sponsor;

/**
 * Class SponsorExtraQuestionValidationRulesFactory
 * @package App\Http\Controllers
 */
final class SponsorExtraQuestionValidationRulesFactory
    extends ExtraQuestionTypeValidationRulesFactory
{

    /**
     * @param array $payload
     * @return array
     */
    public static function buildForAdd(array $payload = []): array
    {
        return [
            'name'        => 'required|string',
            'type'        => 'required|string|in:'.implode(",", Sponsor::getAllowedQuestionTypes()),
            'label'       => 'required|string',
            'mandatory'   => 'required|boolean',
            'placeholder' => 'sometimes|nullable|string',
            'max_selected_values' => 'sometimes|integer|min:0',
        ];
    }

    /**
     * @param array $payload
     * @return array
     */
    public static function buildForUpdate(array $payload = []): array
    {
        return [
            'name'        => 'sometimes|string',
            'type'        => 'sometimes|string|in:'.implode(",", Sponsor::getAllowedQuestionTypes()),
            'label'       => 'sometimes|string',
            'mandatory'   => 'sometimes|boolean',
            'placeholder' => 'sometimes|nullable|string',
            'order'       => 'sometimes|integer|min:1',
            'max_selected_values' => 'sometimes|integer|min:0',
        ];
    }
}