<?php namespace App\Http\Controllers;
/*
 * Copyright 2026 OpenStack Foundation
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

use App\Models\Foundation\Main\IGroup;
use App\Models\Foundation\Summit\Repositories\ISummitSponsorshipAddOnTypeRepository;
use App\ModelSerializers\SerializerUtils;
use App\Security\SummitScopes;
use App\Services\Model\ISummitSponsorshipAddOnTypeService;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitRepository;
use ModelSerializers\SerializerRegistry;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class OAuth2SummitSponsorshipAddOnTypesApiController
 * @package App\Http\Controllers
 */
final class OAuth2SummitSponsorshipAddOnTypesApiController extends OAuth2ProtectedController
{
    use RequestProcessor;
    use GetAll;
    use GetAndValidateJsonPayload;

    /**
     * @var ISummitSponsorshipAddOnTypeService
     */
    private $service;

    /**
     * @param ISummitSponsorshipAddOnTypeRepository $repository
     * @param ISummitRepository $summit_repository
     * @param ISummitSponsorshipAddOnTypeService $service
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct(
        ISummitSponsorshipAddOnTypeRepository $repository,
        ISummitRepository                     $summit_repository,
        ISummitSponsorshipAddOnTypeService    $service,
        IResourceServerContext                $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->repository = $repository;
        $this->summit_repository = $summit_repository;
        $this->service = $service;
    }

    /**
     * @return array
     */
    protected function getFilterRules(): array
    {
        return [
            'name' => ['==', '=@'],
        ];
    }

    /**
     * @return array
     */
    protected function getFilterValidatorRules(): array
    {
        return [
            'name' => 'sometimes|required|string',
        ];
    }

    /**
     * @return array
     */
    protected function getOrderRules(): array
    {
        return [
            'id',
            'name',
        ];
    }


    /**
     * @return ISummitRepository
     */
    protected function getSummitRepository(): ISummitRepository
    {
        return $this->summit_repository;
    }

    #[OA\Get(
        path: "/api/v1/summits/all/add-on-types",
        summary: "Get all sponsorship add-on types",
        operationId: 'getAddOnTypes',
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        security: [['summit_sponsorship_oauth2' => [
            SummitScopes::ReadSummitData,
            SummitScopes::ReadAllSummitData,
        ]]],
        tags: ["Sponsorship Add-On Types"],
        parameters: [
            new OA\Parameter(name: "page", description: "Page number", in: "query", required: false, schema: new OA\Schema(type: "integer", default: 1)),
            new OA\Parameter(name: "per_page", description: "Items per page", in: "query", required: false, schema: new OA\Schema(type: "integer", default: 10)),
            new OA\Parameter(name: "filter", description: "Filter query (name==value, name=@value)", in: "query", required: false, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "order", description: "Order by (+id, -name)", in: "query", required: false, schema: new OA\Schema(type: "string")),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "OK",
                content: new OA\JsonContent(ref: '#/components/schemas/PaginatedSummitSponsorshipAddOnTypeResponse')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]

    #[OA\Post(
        path: "/api/v1/summits/all/add-on-types",
        summary: "Add a new sponsorship add-on type",
        operationId: 'addAddOnType',
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        security: [['summit_sponsorship_oauth2' => [
            SummitScopes::WriteSummitData,
        ]]],
        tags: ["Sponsorship Add-On Types"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name"],
                properties: [
                    new OA\Property(property: "name", type: "string"),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: "Created",
                content: new OA\JsonContent(ref: '#/components/schemas/SummitSponsorshipAddOnType')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function add()
    {
        return $this->processRequest(function () {
            $payload = $this->getJsonPayload(
                SummitSponsorshipAddOnTypeValidationRulesFactory::buildForAdd(),
                true
            );

            $type = $this->service->add($payload);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($type)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    #[OA\Get(
        path: "/api/v1/summits/all/add-on-types/{id}",
        summary: "Get a sponsorship add-on type by id",
        operationId: 'getAddOnType',
        security: [['summit_sponsorship_oauth2' => [
            SummitScopes::ReadSummitData,
            SummitScopes::ReadAllSummitData,
        ]]],
        tags: ["Sponsorship Add-On Types"],
        parameters: [
            new OA\Parameter(name: "id", description: "Add-on Type ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "OK",
                content: new OA\JsonContent(ref: '#/components/schemas/SummitSponsorshipAddOnType')
            ),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function get($id)
    {
        return $this->processRequest(function () use ($id) {
            $type = $this->repository->getById(intval($id));
            if (is_null($type))
                return $this->error404();

            return $this->ok(SerializerRegistry::getInstance()->getSerializer($type)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    #[OA\Put(
        path: "/api/v1/summits/all/add-on-types/{id}",
        summary: "Update a sponsorship add-on type",
        operationId: 'updateAddOnType',
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        security: [['summit_sponsorship_oauth2' => [
            SummitScopes::WriteSummitData,
        ]]],
        tags: ["Sponsorship Add-On Types"],
        parameters: [
            new OA\Parameter(name: "id", description: "Add-on Type ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "name", type: "string"),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "OK",
                content: new OA\JsonContent(ref: '#/components/schemas/SummitSponsorshipAddOnType')
            ),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function update($id)
    {
        return $this->processRequest(function () use ($id) {
            $payload = $this->getJsonPayload(
                SummitSponsorshipAddOnTypeValidationRulesFactory::buildForUpdate(),
                true
            );

            $type = $this->service->update(intval($id), $payload);

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($type)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    #[OA\Delete(
        path: "/api/v1/summits/all/add-on-types/{id}",
        summary: "Delete a sponsorship add-on type",
        operationId: 'deleteAddOnType',
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        security: [['summit_sponsorship_oauth2' => [
            SummitScopes::WriteSummitData,
        ]]],
        tags: ["Sponsorship Add-On Types"],
        parameters: [
            new OA\Parameter(name: "id", description: "Add-on Type ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_NO_CONTENT, description: "No Content"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function delete($id)
    {
        return $this->processRequest(function () use ($id) {
            $this->service->delete(intval($id));
            return $this->deleted();
        });
    }
}
