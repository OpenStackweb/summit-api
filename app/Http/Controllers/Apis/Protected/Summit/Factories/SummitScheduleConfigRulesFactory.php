<?php namespace App\Http\Controllers;
/*
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
use models\summit\SummitScheduleConfig;
/**
 * Class SummitScheduleSettingValidationRulesFactory
 * @package App\Http\Controllers
 */
final class SummitScheduleConfigRulesFactory
{
    /**
     * @param array $data
     * @param bool $update
     * @return array
     */
    public static function build(array $data, $update = false){

        return [
            'key' => 'required|string|max:50',
            'is_enabled' => 'required|boolean',
            'is_default' => 'required|boolean',
            'is_my_schedule' => 'required|boolean',
            'only_events_with_attendee_access' => 'required|boolean',
            'hide_past_events_with_show_always_on_schedule' => 'required|boolean',
            'color_source' => 'required|string|in:'.implode(',', SummitScheduleConfig::AllowedColorSource),
            'filters' => 'sometimes|summit_schedule_config_filter_dto_array',
            'pre_filters' => 'sometimes|summit_schedule_config_pre_filter_dto_array',
        ];
    }
}