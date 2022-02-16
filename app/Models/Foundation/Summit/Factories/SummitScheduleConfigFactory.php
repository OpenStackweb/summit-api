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

use App\Models\Foundation\Summit\Repositories\ISummitScheduleConfigRepository;
use models\summit\SummitScheduleConfig;
use models\summit\SummitScheduleFilterElementConfig;
use \models\exceptions\ValidationException;
use models\summit\SummitSchedulePreFilterElementConfig;

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
     * @param ISummitScheduleConfigRepository|null $repository
     * @return SummitScheduleConfig
     * @throws ValidationException
     */
    public static function populate(SummitScheduleConfig $config, array $payload, ISummitScheduleConfigRepository $repository = null):SummitScheduleConfig{
        if(isset($payload['key']))
            $config->setKey(trim($payload['key']));
        if(isset($payload['is_default']))
            $config->setIsDefault(boolval($payload['is_default']));
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
            if(!is_null($repository) && !$config->isNew()){
                $config->clearFilters();
                $repository->add($config, true);
            }
            foreach ($filters_dto as $dto){
                $filter = new SummitScheduleFilterElementConfig();
                if(isset($dto['label']))
                    $filter->setLabel(trim($dto['label']));
                if(isset($dto['type']))
                    $filter->setType(trim($dto['type']));
                if(isset($dto['is_enabled']))
                    $filter->setIsEnabled(boolval($dto['is_enabled']));
                $config->addFilter($filter);
            }
        }
        if(isset($payload['pre_filters'])){
            $pre_filters_dto = $payload['pre_filters'];
            if(!is_null($repository) && !$config->isNew()){
                $config->clearPreFilters();
                $repository->add($config, true);
            }
            foreach ($pre_filters_dto as $dto){
                $filter = new SummitSchedulePreFilterElementConfig();
                if(isset($dto['type']))
                    $filter->setType(trim($dto['type']));
                if(isset($dto['values']))
                    $filter->setValues($dto['values']);
                $config->addPreFilter($filter);
            }
        }
        return $config;
    }
}