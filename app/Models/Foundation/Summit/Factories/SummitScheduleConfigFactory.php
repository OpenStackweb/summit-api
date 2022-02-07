<?php namespace App\Models\Foundation\Summit\Factories;
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
use models\summit\SummitScheduleFilterElementConfig;
use \models\exceptions\ValidationException;
/**
 * Class SummitScheduleConfigFactory
 * @package App\Models\Foundation\Summit\Factories
 */
final class SummitScheduleConfigFactory
{
    /**
     * @param array $data
     * @return SummitScheduleConfig
     * @throws ValidationException
     */
    public static function build(array $data): SummitScheduleConfig
    {
        return self::populate(new SummitScheduleConfig, $data);
    }

    /**
     * @param SummitScheduleConfig $config
     * @param array $payload
     * @return SummitScheduleConfig
     * @throws ValidationException
     */
    public static function populate(SummitScheduleConfig $config, array $payload):SummitScheduleConfig{
        if(isset($payload['key']))
            $config->setKey(trim($payload['key']));
        if(isset($payload['is_enabled']))
            $config->setIsEnabled(boolval($payload['is_enabled']));
        if(isset($payload['is_my_schedule']))
            $config->setIsMySchedule(boolval($payload['is_my_schedule']));
        if(isset($payload['only_events_with_attendee_access']))
            $config->setOnlyEventsWithAttendeeAccess(boolval($payload['only_events_with_attendee_access']));
        if(isset($payload['color_source']))
            $config->setColorSource(trim($payload['color_source']));
        if(isset($payload['filters'])){
            $filters_dto = $payload['filters'];
            $config->clearFilters();;
            foreach ($filters_dto as $dto){
                $filter = new SummitScheduleFilterElementConfig();
                if(isset($dto['label']))
                    $filter->setLabel(trim($dto['label']));
                if(isset($dto['type']))
                    $filter->setType(trim($dto['type']));
                if(isset($dto['is_enabled']))
                    $filter->setIsEnabled(boolval($dto['is_enabled']));
                if(isset($dto['is_enabled']))
                    $filter->setIsEnabled(boolval($dto['is_enabled']));
                if(isset($dto['prefilter_values']))
                    $filter->setPrefilterValues($dto['prefilter_values']);
                $config->addFilter($filter);
            }
        }
        return $config;
    }
}