<?php namespace App\Services\Model;
/**
 * Copyright 2021 OpenStack Foundation
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
use models\summit\PresentationActionType;
use models\summit\Summit;
/**
 * Interface ISummitPresentationActionTypeService
 * @package App\Services\Model
 */
interface ISummitPresentationActionTypeService
{
    /**
     * @param Summit $summit
     * @param array $payload
     * @return PresentationActionType
     * @throws ValidationException
     */
    public function add(Summit $summit, array $payload):PresentationActionType;

    /**
     * @param Summit $summit
     * @param int $action_type_id
     * @param array $payload
     * @return PresentationActionType|null
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function update(Summit $summit, int $action_type_id, array $payload):?PresentationActionType;

    /**
     * @param Summit $summit
     * @param int $action_type_id
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function delete(Summit $summit, int $action_type_id):void;
}