<?php namespace App\Services\Utils;
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
use models\utils\IEntity;
/**
 * Interface IBaseService
 * @package App\Services\Utils
 */
interface IBaseService
{
    /**
     * @param array $payload
     * @return IEntity
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function create(array $payload):IEntity;

    /**
     * @param int $id
     * @param array $payload
     * @return IEntity
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function update(int $id, array $payload):IEntity;

    /**
     * @param int $id
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function delete(int $id):void;

}