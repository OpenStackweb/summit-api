<?php

namespace App\Http\Controllers;

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

use App\Models\Foundation\Main\IGroup;
use App\Models\Foundation\Summit\Repositories\ISummitSponsorshipTypeRepository;
use App\ModelSerializers\SerializerUtils;
use App\Security\SummitScopes;
use App\Services\Model\ISummitSponsorshipTypeService;
use Illuminate\Http\Request as LaravelRequest;
use Illuminate\Http\Response;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitRepository;
use models\summit\Summit;
use models\utils\IEntity;
use ModelSerializers\SerializerRegistry;
use OpenApi\Attributes as OA;

/**
 * Class OAuth2SummitSponsorshipTypeApiController
 * @package App\Http\Controllers
 */
final class OAuth2SummitSponsorshipTypeApiController extends OAuth2ProtectedController
{
    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * @var ISummitSponsorshipTypeService
     */
    private $service;

    /**
     * @param ISummitRepository $summit_repository
     * @param ISummitSponsorshipTypeRepository $repository
     * @param ISummitSponsorshipTypeService $service
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        ISummitRepository $summit_repository,
        ISummitSponsorshipTypeRepository $repository,
        ISummitSponsorshipTypeService $service,
        IResourceServerContext $resource_server_context
    ) {
        $this->service = $service;
        $this->repository = $repository;
        $this->summit_repository = $summit_repository;
        parent::__construct($resource_server_context);
    }

    use GetAllBySummit;

    use GetSummitChildElementById;

    use AddSummitChildElement;

    use UpdateSummitChildElement;

    use DeleteSummitChildElement;

    #[OA\Get(
        path: '/api/v1/summits/{id}/sponsorships-types',
        summary: 'Get all sponsorship types for a summit',
        operationId: 'getSummitAllSponsorshipTypes',
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        security: [
            [
                'sponsorship_types_oauth2' => [
                    SummitScopes::ReadSummitData,
                    SummitScopes::ReadAllSummitData,
                ]
            ]
        ],
        tags: ['Summits Sponsorship Types'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, description: 'Page number', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'Items per page', schema: new OA\Schema(type: 'integer', default: 10, maximum: 100)),
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'filter', in: 'query', description: 'Filter by name, label, or size (name=@value, label==value, size=@value)', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'order', in: 'query', description: 'Order by: +/-id, +/-name, +/-order, +/-label, +/-size', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Successful response',
                content: new OA\JsonContent(ref: '#/components/schemas/PaginatedSummitSponsorshipTypesResponse')
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function getAllBySummit($summit_id)
    {
        return parent::getAllBySummit($summit_id);
    }

    #[OA\Post(
        path: '/api/v1/summits/{id}/sponsorships-types',
        summary: 'Create a new sponsorship type',
        operationId: 'createSummitSponsorshipType',
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        security: [
            [
                'sponsorship_types_oauth2' => [
                    SummitScopes::WriteSummitData,
                ]
            ]
        ],
        tags: ['Summits Sponsorship Types'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID', schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/SummitSponsorshipTypeCreateRequest')
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'Sponsorship type created',
                content: new OA\JsonContent(ref: '#/components/schemas/SummitSponsorshipType')
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function add($summit_id)
    {
        return parent::add($summit_id);
    }

    #[OA\Get(
        path: '/api/v1/summits/{id}/sponsorships-types/{type_id}',
        summary: 'Get a sponsorship type by ID',
        operationId: 'getSummitSponsorshipType',
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        security: [
            [
                'sponsorship_types_oauth2' => [
                    SummitScopes::ReadSummitData,
                    SummitScopes::ReadAllSummitData,
                ]
            ]
        ],
        tags: ['Summits Sponsorship Types'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'type_id', in: 'path', required: true, description: 'Sponsorship Type ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Successful response',
                content: new OA\JsonContent(ref: '#/components/schemas/SummitSponsorshipType')
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function get($summit_id, $type_id)
    {
        return parent::get($summit_id, $type_id);
    }

    #[OA\Put(
        path: '/api/v1/summits/{id}/sponsorships-types/{type_id}',
        summary: 'Update a sponsorship type',
        operationId: 'updateSummitSponsorshipType',
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        security: [
            [
                'sponsorship_types_oauth2' => [
                    SummitScopes::WriteSummitData,
                ]
            ]
        ],
        tags: ['Summits Sponsorship Types'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'type_id', in: 'path', required: true, description: 'Sponsorship Type ID', schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/SummitSponsorshipTypeUpdateRequest')
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Sponsorship type updated',
                content: new OA\JsonContent(ref: '#/components/schemas/SummitSponsorshipType')
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function update($summit_id, $type_id)
    {
        return parent::update($summit_id, $type_id);
    }

    #[OA\Delete(
        path: '/api/v1/summits/{id}/sponsorships-types/{type_id}',
        summary: 'Delete a sponsorship type',
        operationId: 'deleteSummitSponsorshipType',
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        security: [
            [
                'sponsorship_types_oauth2' => [
                    SummitScopes::WriteSummitData,
                ]
            ]
        ],
        tags: ['Summits Sponsorship Types'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'type_id', in: 'path', required: true, description: 'Sponsorship Type ID', schema: new OA\Schema(type: 'integer')),
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
    public function delete($summit_id, $type_id)
    {
        return parent::delete($summit_id, $type_id);
    }

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
     * @param Summit $summit
     * @param array $payload
     * @return IEntity
     */
    protected function addChild(Summit $summit, array $payload): IEntity
    {
        return $this->service->add($summit, $payload);
    }

    /**
     * @param array $payload
     * @return array
     */
    function getAddValidationRules(array $payload): array
    {
        return SummitSponsorshipTypeValidationRules::buildForAdd($payload);
    }

    /**
     * @return ISummitRepository
     */
    protected function getSummitRepository(): ISummitRepository
    {
        return $this->summit_repository;
    }

    /**
     * @param Summit $summit
     * @param $child_id
     * @return void
     */
    protected function deleteChild(Summit $summit, $child_id): void
    {
        $this->service->delete($summit, intval($child_id));
    }

    /**
     * @param Summit $summit
     * @param $child_id
     * @return IEntity|null
     */
    protected function getChildFromSummit(Summit $summit, $child_id): ?IEntity
    {
        return $summit->getSummitSponsorshipTypeById(intval($child_id));
    }

    /**
     * @param array $payload
     * @return array
     */
    function getUpdateValidationRules(array $payload): array
    {
        return SummitSponsorshipTypeValidationRules::buildForUpdate($payload);
    }

    /**
     * @param Summit $summit
     * @param int $child_id
     * @param array $payload
     * @return IEntity
     */
    protected function updateChild(Summit $summit, int $child_id, array $payload): IEntity
    {
        return $this->service->update($summit, $child_id, $payload);
    }

    use RequestProcessor;

    /**
     * @param LaravelRequest $request
     * @param $summit_id
     * @param $type_id
     * @return mixed
     * @throws \models\exceptions\EntityNotFoundException
     * @throws \models\exceptions\ValidationException
     */
    #[OA\Post(
        path: '/api/v1/summits/{id}/sponsorships-types/{type_id}/badge-image',
        summary: 'Upload a badge image for a sponsorship type',
        operationId: 'addSummitSponsorshipTypeBadgeImage',
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        security: [
            [
                'sponsorship_types_oauth2' => [
                    SummitScopes::WriteSummitData,
                ]
            ]
        ],
        tags: ['Summits Sponsorship Types'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'type_id', in: 'path', required: true, description: 'Sponsorship Type ID', schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['file'],
                    properties: [
                        new OA\Property(property: 'file', type: 'string', format: 'binary', description: 'Image file to upload')
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'Badge image uploaded successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 123),
                        new OA\Property(property: 'url', type: 'string', example: 'https://example.com/badge.png'),
                        new OA\Property(property: 'filename', type: 'string', example: 'badge.png'),
                    ]
                )
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function addBadgeImage(LaravelRequest $request, $summit_id, $type_id)
    {
        return $this->processRequest(function () use ($request, $summit_id, $type_id) {

            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->resource_server_context)->find($summit_id);
            if (is_null($summit))
                return $this->error404();

            $file = $request->file('file');
            if (is_null($file)) {
                return $this->error412(array('file param not set!'));
            }

            $image = $this->service->addBadgeImage($summit, $type_id, $file);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($image)->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));

        });
    }

    /**
     * @param $summit_id
     * @param $type_id
     * @return mixed
     * @throws \models\exceptions\EntityNotFoundException
     * @throws \models\exceptions\ValidationException
     */
    #[OA\Delete(
        path: '/api/v1/summits/{id}/sponsorships-types/{type_id}/badge-image',
        summary: 'Remove the badge image from a sponsorship type',
        operationId: 'deleteSummitSponsorshipTypeBadgeImage',
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        security: [
            [
                'sponsorship_types_oauth2' => [
                    SummitScopes::WriteSummitData,
                ]
            ]
        ],
        tags: ['Summits Sponsorship Types'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'type_id', in: 'path', required: true, description: 'Sponsorship Type ID', schema: new OA\Schema(type: 'integer')),
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
    public function removeBadgeImage($summit_id, $type_id)
    {
        return $this->processRequest(function () use ($summit_id, $type_id) {

            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->resource_server_context)->find($summit_id);
            if (is_null($summit))
                return $this->error404();

            $this->service->deleteBadgeImage($summit, $type_id);

            return $this->deleted();

        });
    }
}
