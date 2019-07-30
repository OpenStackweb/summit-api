<?php namespace App\Http\Controllers;
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

use App\Http\Exceptions\HTTP403ForbiddenException;
use App\Models\Foundation\Main\Repositories\ISummitAdministratorPermissionGroupRepository;
use App\ModelSerializers\ISummitAttendeeTicketSerializerTypes;
use App\Services\Model\ISummitAdministratorPermissionGroupService;
use Illuminate\Support\Facades\Log;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\oauth2\IResourceServerContext;
use models\summit\IOrderConstants;
use models\utils\IEntity;
use Exception;
use ModelSerializers\SerializerRegistry;
use utils\Filter;
use utils\FilterElement;

/**
 * Class OAuth2SummitAdministratorPermissionGroupApiController
 * @package App\Http\Controllers
 */
class OAuth2SummitAdministratorPermissionGroupApiController
    extends OAuth2ProtectedController
{
    /**
     * @var ISummitAdministratorPermissionGroupService
     */
    private $service;

    /**
     * OAuth2SummitAdministratorPermissionGroupApiController constructor.
     * @param ISummitAdministratorPermissionGroupService $service
     * @param ISummitAdministratorPermissionGroupRepository $repository
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        ISummitAdministratorPermissionGroupService $service,
        ISummitAdministratorPermissionGroupRepository $repository,
        IResourceServerContext $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->service = $service;
        $this->repository = $repository;
    }

    use ParametrizedGetAll;

    use AddEntity;

    use DeleteEntity;

    use UpdateEntity;

    use GetEntity;

    function getAll()
    {
        return $this->_getAll(
            function () {
                return [
                    'title' => ['=@', '=='],
                    'member_first_name' => ['=@', '=='],
                    'member_last_name' => ['=@', '=='],
                    'member_full_name' => ['=@', '=='],
                    'member_email' => ['=@', '=='],
                    'summit_id' => ['=='],
                    'member_id' => ['=='],
                ];
            },
            function () {
                return [
                   'title' => 'sometimes|string',
                    'member_first_name' => 'sometimes|string',
                    'member_last_name' => 'sometimes|string',
                    'member_full_name' => 'sometimes|string',
                    'member_email' => 'sometimes|string',
                    'summit_id' => 'sometimes|integer',
                    'member_id' => 'sometimes|integer',
                ];
            },
            function () {
                return [
                    'id',
                    'title',
                ];
            },
            function ($filter) {
                return $filter;
            },
            function () {
                return SerializerRegistry::SerializerType_Public;
            }
        );
    }

    /**
     * @inheritDoc
     */
    function getAddValidationRules(array $payload): array
    {
        return [
            'title' => 'required|string',
            'summits' => 'required|int_array',
            'members' => 'required|int_array',
        ];
    }

    /**
     * @inheritDoc
     */
    protected function addEntity(array $payload): IEntity
    {
        return $this->service->create($payload);
    }

    /**
     * @inheritDoc
     */
    protected function deleteEntity(int $id): void
    {
        $this->service->delete($id);
    }

    /**
     * @inheritDoc
     */
    protected function getEntity(int $id): IEntity
    {
        return $this->repository->getById($id);
    }

    /**
     * @inheritDoc
     */
    function getUpdateValidationRules(array $payload): array
    {
        return [
            'title' => 'sometimes|string',
            'summits' => 'sometimes|int_array',
            'members' => 'sometimes|int_array',
        ];
    }

    /**
     * @inheritDoc
     */
    protected function updateEntity($id, array $payload): IEntity
    {
        return $this->service->update($id, $payload);
    }

    public function addMember($id, $member_id)
    {
        try {
            $group = $this->repository->getById($id);
            if (is_null($group))
                throw new EntityNotFoundException();
            $group = $this->service->addMemberTo($group, $member_id);
            return $this->updated(SerializerRegistry::getInstance()->getSerializer($group)->serialize());
        } catch (ValidationException $ex) {
            Log::warning($ex);
            return $this->error412(array($ex->getMessage()));
        } catch (EntityNotFoundException $ex) {
            Log::warning($ex);
            return $this->error404(array('message' => $ex->getMessage()));
        } catch (\HTTP401UnauthorizedException $ex) {
            Log::warning($ex);
            return $this->error401();
        } catch (HTTP403ForbiddenException $ex) {
            Log::warning($ex);
            return $this->error403();
        } catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    public function removeMember($id, $member_id)
    {
        try {
            $group = $this->repository->getById($id);
            if (is_null($group))
                throw new EntityNotFoundException();
            $group = $this->service->removeMemberFrom($group, $member_id);
            return $this->updated(SerializerRegistry::getInstance()->getSerializer($group)->serialize());
        } catch (ValidationException $ex) {
            Log::warning($ex);
            return $this->error412(array($ex->getMessage()));
        } catch (EntityNotFoundException $ex) {
            Log::warning($ex);
            return $this->error404(array('message' => $ex->getMessage()));
        } catch (\HTTP401UnauthorizedException $ex) {
            Log::warning($ex);
            return $this->error401();
        } catch (HTTP403ForbiddenException $ex) {
            Log::warning($ex);
            return $this->error403();
        } catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    public function addSummit($id, $summit_id)
    {
        try {
            $group = $this->repository->getById($id);
            if (is_null($group))
                throw new EntityNotFoundException();
            $group = $this->service->addSummitTo($group, $summit_id);
            return $this->updated(SerializerRegistry::getInstance()->getSerializer($group)->serialize());
        } catch (ValidationException $ex) {
            Log::warning($ex);
            return $this->error412(array($ex->getMessage()));
        } catch (EntityNotFoundException $ex) {
            Log::warning($ex);
            return $this->error404(array('message' => $ex->getMessage()));
        } catch (\HTTP401UnauthorizedException $ex) {
            Log::warning($ex);
            return $this->error401();
        } catch (HTTP403ForbiddenException $ex) {
            Log::warning($ex);
            return $this->error403();
        } catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    public function removeSummit($id, $summit_id)
    {
        try {
            $group = $this->repository->getById($id);
            if (is_null($group))
                throw new EntityNotFoundException();
            $group = $this->service->removeSummitFrom($group, $summit_id);
            return $this->updated(SerializerRegistry::getInstance()->getSerializer($group)->serialize());
        } catch (ValidationException $ex) {
            Log::warning($ex);
            return $this->error412(array($ex->getMessage()));
        } catch (EntityNotFoundException $ex) {
            Log::warning($ex);
            return $this->error404(array('message' => $ex->getMessage()));
        } catch (\HTTP401UnauthorizedException $ex) {
            Log::warning($ex);
            return $this->error401();
        } catch (HTTP403ForbiddenException $ex) {
            Log::warning($ex);
            return $this->error403();
        } catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }
}