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
use App\Models\Foundation\Main\IGroup;
use App\Models\Foundation\Main\Repositories\ISummitAdministratorPermissionGroupRepository;
use App\ModelSerializers\ISummitAttendeeTicketSerializerTypes;
use App\Services\Model\ISummitAdministratorPermissionGroupService;
use App\Security\SummitScopes;
use Illuminate\Support\Facades\Log;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\oauth2\IResourceServerContext;
use models\summit\IOrderConstants;
use models\utils\IEntity;
use Exception;
use ModelSerializers\SerializerRegistry;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;
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

    #[OA\Get(
        path: "/api/v1/summit-administrator-groups",
        description: "required-groups " . IGroup::SuperAdmins . ", " . IGroup::Administrators,
        summary: "Get all summit administrator permission groups",
        security: [['summit_admin_groups_oauth2' => [
            SummitScopes::ReadSummitAdminGroups,
        ]]],
        tags: ["SummitAdministratorPermissionGroups"],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: "page",
                description: "Page number",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "integer", default: 1)
            ),
            new OA\Parameter(
                name: "per_page",
                description: "Items per page",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "integer", default: 10)
            ),
            new OA\Parameter(name: "filter", description: "Filter", in: "query", required: false, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "order", description: "Order", in: "query", required: false, schema: new OA\Schema(type: "string")),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "OK",
                content: new OA\JsonContent(ref: "#/components/schemas/SummitAdministratorPermissionGroupList")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
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

    #[OA\Post(
        path: "/api/v1/summit-administrator-groups",
        summary: "Create a new summit administrator permission group",
        security: [['summit_admin_groups_oauth2' => [
            SummitScopes::WriteSummitAdminGroups,
        ]]],
        tags: ["SummitAdministratorPermissionGroups"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                ref: "#/components/schemas/CreateSummitAdministratorPermissionGroup"
            )
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: "Created",
                content: new OA\JsonContent(ref: "#/components/schemas/SummitAdministratorPermissionGroup")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
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

    #[OA\Get(
        path: "/api/v1/summit-administrator-groups/{id}",
        summary: "Get a summit administrator permission group by ID",
        security: [['summit_admin_groups_oauth2' => [
            SummitScopes::ReadSummitAdminGroups,
        ]]],
        tags: ["SummitAdministratorPermissionGroups"],
        parameters: [
            new OA\Parameter(name: "id", description: "Permission Group ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "OK",
                content: new OA\JsonContent(ref: "#/components/schemas/SummitAdministratorPermissionGroup")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    protected function getEntity(int $id): IEntity
    {
        return $this->repository->getById($id);
    }

    #[OA\Put(
        path: "/api/v1/summit-administrator-groups/{id}",
        summary: "Update a summit administrator permission group",
        security: [['summit_admin_groups_oauth2' => [
            SummitScopes::WriteSummitAdminGroups,
        ]]],
        tags: ["SummitAdministratorPermissionGroups"],
        parameters: [
            new OA\Parameter(name: "id", description: "Permission Group ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                ref: "#/components/schemas/UpdateSummitAdministratorPermissionGroup"
            )
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "OK",
                content: new OA\JsonContent(ref: "#/components/schemas/SummitAdministratorPermissionGroup")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
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

    #[OA\Delete(
        path: "/api/v1/summit-administrator-groups/{id}",
        summary: "Delete a summit administrator permission group",
        security: [['summit_admin_groups_oauth2' => [
            SummitScopes::WriteSummitAdminGroups,
        ]]],
        tags: ["SummitAdministratorPermissionGroups"],
        parameters: [
            new OA\Parameter(name: "id", description: "Permission Group ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_NO_CONTENT, description: "No Content"),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    protected function deleteEntity(int $id): void
    {
        $this->service->delete($id);
    }

    #[OA\Put(
        path: "/api/v1/summit-administrator-groups/{id}/members/{member_id}",
        description: "required-groups " . IGroup::SuperAdmins . ", " . IGroup::Administrators,
        summary: "Add member to permission group",
        security: [['summit_admin_groups_oauth2' => [
            SummitScopes::WriteSummitAdminGroups,
        ]]],
        tags: ["SummitAdministratorPermissionGroups"],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
            ]
        ],
        parameters: [
            new OA\Parameter(name: "id", description: "Permission Group ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "member_id", description: "Member ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "OK",
                content: new OA\JsonContent(ref: "#/components/schemas/SummitAdministratorPermissionGroup")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
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

    #[OA\Delete(
        path: "/api/v1/summit-administrator-groups/{id}/members/{member_id}",
        description: "required-groups " . IGroup::SuperAdmins . ", " . IGroup::Administrators,
        summary: "Remove member from permission group",
        security: [['summit_admin_groups_oauth2' => [
            SummitScopes::WriteSummitAdminGroups,
        ]]],
        tags: ["SummitAdministratorPermissionGroups"],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
            ]
        ],
        parameters: [
            new OA\Parameter(name: "id", description: "Permission Group ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "member_id", description: "Member ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "OK",
                content: new OA\JsonContent(ref: "#/components/schemas/SummitAdministratorPermissionGroup")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
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

    #[OA\Put(
        path: "/api/v1/summit-administrator-groups/{id}/summits/{summit_id}",
        description: "required-groups " . IGroup::SuperAdmins . ", " . IGroup::Administrators,
        summary: "Add summit to permission group",
        security: [['summit_admin_groups_oauth2' => [
            SummitScopes::WriteSummitAdminGroups,
        ]]],
        tags: ["SummitAdministratorPermissionGroups"],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
            ]
        ],
        parameters: [
            new OA\Parameter(name: "id", description: "Permission Group ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "summit_id", description: "Summit ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "OK",
                content: new OA\JsonContent(ref: "#/components/schemas/SummitAdministratorPermissionGroup")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
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

    #[OA\Delete(
        path: "/api/v1/summit-administrator-groups/{id}/summits/{summit_id}",
        description: "required-groups " . IGroup::SuperAdmins . ", " . IGroup::Administrators,
        summary: "Remove summit from permission group",
        security: [['summit_admin_groups_oauth2' => [
            SummitScopes::WriteSummitAdminGroups,
        ]]],
        tags: ["SummitAdministratorPermissionGroups"],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
            ]
        ],
        parameters: [
            new OA\Parameter(name: "id", description: "Permission Group ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "summit_id", description: "Summit ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "OK",
                content: new OA\JsonContent(ref: "#/components/schemas/SummitAdministratorPermissionGroup")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
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