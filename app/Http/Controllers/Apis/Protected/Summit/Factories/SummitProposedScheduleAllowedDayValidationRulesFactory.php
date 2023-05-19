<?php namespace App\Http\Controllers;
/*
 * Copyright 2023 OpenStack Foundation
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
 * Class SummitProposedScheduleAllowedDayValidationRulesFactory
 * @package App\Http\Controllers
 */
final class SummitProposedScheduleAllowedDayValidationRulesFactory
    extends AbstractValidationRulesFactory
{

    public static function buildForAdd(array $payload = []): array
    {
        return [
            'day' => 'required|date_format:U',
            'opening_hour' => 'sometimes|int|min:0|max:2359',
            'closing_hour' => 'sometimes|int|min:0|max:2359|required_with:from|gt:from',
        ];
    }

    public static function buildForUpdate(array $payload = []): array
    {
        return [
            'day' => 'sometimes|date_format:U',
            'opening_hour' => 'sometimes|int|min:0|max:2359',
            'closing_hour' => 'sometimes|int|min:0|max:2359|required_with:from|gt:from',
        ];
    }
}