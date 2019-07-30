<?php namespace App\Services\Model;
/**
 * Copyright 2020 OpenStack Foundation
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
use models\main\SummitAdministratorPermissionGroup;
/**
 * Interface ISummitAdministratorPermissionGroupService
 * @package App\Services\Model
 */
interface ISummitAdministratorPermissionGroupService
{
    /**
     * @param array $payload
     * @return SummitAdministratorPermissionGroup
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function create(array $payload):SummitAdministratorPermissionGroup;

    /**
     * @param int $id
     * @param array $payload
     * @return SummitAdministratorPermissionGroup
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function update(int $id, array $payload):SummitAdministratorPermissionGroup;

    /**
     * @param int $id
     * @throws EntityNotFoundException
     */
    public function delete(int $id):void;

    /**
     * @param SummitAdministratorPermissionGroup $group
     * @param int $member_id
     * @return SummitAdministratorPermissionGroup
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function addMemberTo(SummitAdministratorPermissionGroup $group, int $member_id):SummitAdministratorPermissionGroup;

    /**
     * @param SummitAdministratorPermissionGroup $group
     * @param int $member_id
     * @return SummitAdministratorPermissionGroup
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function removeMemberFrom(SummitAdministratorPermissionGroup $group, int $member_id):SummitAdministratorPermissionGroup;

    /**
     * @param SummitAdministratorPermissionGroup $group
     * @param int $summit_id
     * @return SummitAdministratorPermissionGroup
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function addSummitTo(SummitAdministratorPermissionGroup $group, int $summit_id):SummitAdministratorPermissionGroup;

    /**
     * @param SummitAdministratorPermissionGroup $group
     * @param int $summit_id
     * @return SummitAdministratorPermissionGroup
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function removeSummitFrom(SummitAdministratorPermissionGroup $group, int $summit_id):SummitAdministratorPermissionGroup;
}