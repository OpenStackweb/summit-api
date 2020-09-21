<?php namespace App\Services\Model;
/**
 * Copyright 2019 OpenStack Foundation
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
use models\summit\SummitAccessLevelType;
/**
 * Interface ISummitAccessLevelTypeService
 * @package App\Services\Model
 */
interface ISummitAccessLevelTypeService
{
    /**
     * @param Summit $summit
     * @param array $data
     * @return SummitAccessLevelType
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function addAccessLevelType(Summit $summit, array $data):SummitAccessLevelType;

    /**
     * @param Summit $summit
     * @param int $level_id
     * @param array $data
     * @return SummitAccessLevelType
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function updateAccessLevelType(Summit $summit, int $level_id, array $data):SummitAccessLevelType;

    /**
     * @param Summit $summit
     * @param int $level_id
     * @throws EntityNotFoundException
     */
    public function deleteAccessLevelType(Summit $summit, int $level_id):void;
}