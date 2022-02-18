<?php namespace App\Services\Model;
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
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\summit\Summit;
use models\summit\SummitScheduleConfig;
use models\summit\SummitScheduleFilterElementConfig;

/**
 *
 */
interface ISummitScheduleSettingsService
{
    /**
     * @param Summit $summit
     * @param array $payload
     * @return SummitScheduleConfig|null
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function add(Summit $summit, array $payload):?SummitScheduleConfig;

    /**
     * @param Summit $summit
     * @param int $config_id
     * @param array $payload
     * @return SummitScheduleConfig|null
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function update(Summit $summit, int $config_id, array $payload):?SummitScheduleConfig;

    /**
     * @param Summit $summit
     * @param int $config_id
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function delete(Summit $summit, int $config_id):void;

    /**
     * @param Summit $summit
     * @param int $config_id
     * @param array $payload
     * @return SummitScheduleFilterElementConfig|null
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function addFilter(Summit $summit, int $config_id, array $payload):?SummitScheduleFilterElementConfig;

    /**
     * @param Summit $summit
     * @param int $config_id
     * @param int $filter_id
     * @param array $payload
     * @return SummitScheduleFilterElementConfig|null
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function updateFilter(Summit $summit, int $config_id, int $filter_id, array $payload):?SummitScheduleFilterElementConfig;

    /**
     * @param Summit $summit
     * @return array|SummitScheduleConfig[]
     * @throws \Exception
     */
    public function seedDefaults(Summit $summit):array;
}