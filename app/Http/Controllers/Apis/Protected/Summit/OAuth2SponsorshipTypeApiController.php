<?php namespace App\Http\Controllers;
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
use App\Models\Foundation\Summit\Repositories\ISponsorshipTypeRepository;
use App\ModelSerializers\SerializerUtils;
use App\Security\SummitScopes;
use App\Services\Model\ISponsorshipTypeService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitRepository;
use ModelSerializers\SerializerRegistry;
use App\Models\Foundation\Main\IGroup;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class OAuth2SponsorshipTypeApiController
 * @package App\Http\Controllers
 */
final class OAuth2SponsorshipTypeApiController extends OAuth2ProtectedController
{

    use RequestProcessor;
    /**
     * @var ISponsorshipTypeService
     */
    private $service;

    /**
     * OAuth2SponsorshipTypeApiController constructor.
     * @param ISponsorshipTypeRepository $repository
     * @param ISummitRepository $summit_repository
     * @param ISponsorshipTypeService $service
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        ISponsorshipTypeRepository $repository,
        ISummitRepository $summit_repository,
        ISponsorshipTypeService $service,
        IResourceServerContext $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->repository = $repository;
        $this->summit_repository = $summit_repository;
        $this->service = $service;
    }

    use GetAll;

    /**
     * @return array
     */
    protected function getFilterRules(): array
    {
        return [
            'name' => ['==', '=@'],
            'label' => ['==', '=@'],
            'size' => ['==', '=@'],
        ];
    }

    /**
     * @return array
     */
    protected function getFilterValidatorRules(): array
    {
        return [
            'name' => 'sometimes|required|string',
            'label' => 'sometimes|required|string',
            'size' => 'sometimes|required|string',
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
            'order',
            'label',
            'size',
        ];
    }

    /**
     * @return ISummitRepository
     */
    protected function getSummitRepository(): ISummitRepository
    {
        return $this->summit_repository;
    }

    use GetAndValidateJsonPayload;

    #[OA\Get(
        path: "/api/v1/sponsorship-types",
        summary: "Get all sponsorship types",
        operationId: 'getSponsorshipTypes',
        x: [
            'required-groups' => [
                IGroup::SummitAdministrators,
                IGroup::SuperAdmins,
                IGroup::Administrators
            ]
        ],
        security: [['summit_sponsorship_type_oauth2' => [
            SummitScopes::ReadSummitData,
            SummitScopes::ReadAllSummitData,
        ]]],
        tags: ["Sponsorship Types"],
        parameters: [
            new OA\Parameter(name: "page", description: "Page number", in: "query", required: false, schema: new OA\Schema(type: "integer", default: 1)),
            new OA\Parameter(name: "per_page", description: "Items per page", in: "query", required: false, schema: new OA\Schema(type: "integer", default: 10)),
            new OA\Parameter(name: "filter", description: "Filter query (name==value, label=@value, size==value)", in: "query", required: false, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "order", description: "Order by (+id, -name, +order, +label, +size)", in: "query", required: false, schema: new OA\Schema(type: "string")),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "OK",
                content: new OA\JsonContent(ref: '#/components/schemas/PaginatedDataSponsorshipType'),
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]

    #[OA\Post(
        path: "/api/v1/sponsorship-types",
        summary: "Add a new sponsorship type",
        operationId: 'addSponsorshipType',
        x: [
            'required-groups' => [
                IGroup::SummitAdministrators,
                IGroup::SuperAdmins,
                IGroup::Administrators
            ]
        ],
        security: [['summit_sponsorship_type_oauth2' => [
            SummitScopes::WriteSummitData,
        ]]],
        tags: ["Sponsorship Types"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/SponsorshipTypeAddRequest")
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: "Created",
                content: new OA\JsonContent(ref: "#/components/schemas/SponsorshipType")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function add()
    {
        return $this->processRequest(function(){

            $payload = $this->getJsonPayload(
                SponsorshipTypeValidationRulesFactory::buildForAdd(),
                true
            );

            $sponsorship_type = $this->service->addSponsorShipType($payload);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($sponsorship_type)->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    #[OA\Get(
        path: "/api/v1/sponsorship-types/{id}",
        summary: "Get a sponsorship type by id",
        operationId: 'getSponsorshipType',
        security: [['summit_sponsorship_type_oauth2' => [
            SummitScopes::ReadSummitData,
            SummitScopes::ReadAllSummitData,
        ]]],
        tags: ["Sponsorship Types"],
        parameters: [
            new OA\Parameter(name: "id", description: "Sponsorship Type ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "OK",
                content: new OA\JsonContent(ref: "#/components/schemas/SponsorshipType")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function get($id)
    {
        return $this->processRequest(function() use($id){
            $sponsorship_type = $this->repository->getById($id);
            if(is_null($sponsorship_type))
                return $this->error404();

            return $this->ok(SerializerRegistry::getInstance()->getSerializer($sponsorship_type)->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    #[OA\Put(
        path: "/api/v1/sponsorship-types/{id}",
        summary: "Update a sponsorship type",
        operationId: 'updateSponsorshipType',
        x: [
            'required-groups' => [
                IGroup::SummitAdministrators,
                IGroup::SuperAdmins,
                IGroup::Administrators
            ]
        ],
        security: [['summit_sponsorship_type_oauth2' => [
            SummitScopes::WriteSummitData,
        ]]],
        tags: ["Sponsorship Types"],
        parameters: [
            new OA\Parameter(name: "id", description: "Sponsorship Type ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/SponsorshipTypeUpdateRequest")
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "OK",
                content: new OA\JsonContent(ref: "#/components/schemas/SponsorshipType")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function update($id)
    {
        return $this->processRequest(function() use($id){
            Log::debug(sprintf("OAuth2SponsorshipTypeApiController::update id %s", json_encode($id)));
            $payload = $this->getJsonPayload(
                SponsorshipTypeValidationRulesFactory::buildForUpdate(),
                true
            );

            $sponsorship_type = $this->service->updateSponsorShipType(intval($id), $payload);

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($sponsorship_type)->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    #[OA\Delete(
        path: "/api/v1/sponsorship-types/{id}",
        summary: "Delete a sponsorship type",
        operationId: 'deleteSponsorshipType',
        x: [
            'required-groups' => [
                IGroup::SummitAdministrators,
                IGroup::SuperAdmins,
                IGroup::Administrators
            ]
        ],
        security: [['summit_sponsorship_type_oauth2' => [
            SummitScopes::WriteSummitData,
        ]]],
        tags: ["Sponsorship Types"],
        parameters: [
            new OA\Parameter(name: "id", description: "Sponsorship Type ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_NO_CONTENT, description: "No Content"),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function delete($id)
    {
        return $this->processRequest(function() use($id){
            $this->service->deleteSponsorShipType($id);
            return $this->deleted();
        });
    }
}
