<?php

namespace App\Http\Controllers;

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
use App\Models\Foundation\Main\IGroup;
use App\Models\Foundation\Summit\Repositories\ISummitTaxTypeRepository;
use App\Security\SummitScopes;
use App\Services\Model\ISummitTaxTypeService;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitRepository;
use models\summit\Summit;
use models\utils\IBaseRepository;
use models\utils\IEntity;
use ModelSerializers\SerializerRegistry;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;
use Exception;
/**
 * Class OAuth2SummitTaxTypeApiController
 * @package App\Http\Controllers
 */
final class OAuth2SummitTaxTypeApiController extends OAuth2ProtectedController
{
    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * @var ISummitTaxTypeService
     */
    private $service;

    use GetAllBySummit;

    use GetSummitChildElementById;

    use AddSummitChildElement;

    use UpdateSummitChildElement;

    use DeleteSummitChildElement;

    #[OA\Get(
        path: '/api/v1/summits/{id}/tax-types',
        summary: 'Get all tax types for a summit',
        security: [
            [
                'tax_types_oauth2' => [
                    SummitScopes::ReadSummitData,
                    SummitScopes::ReadAllSummitData,
                ]
            ]
        ],
        tags: ['Summits Tax Types'],
        parameters: [

            new OA\Parameter(
                name: 'page',
                in: 'query',
                required: false,
                description: 'Page number for pagination',
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                required: false,
                description: 'Items per page',
                schema: new OA\Schema(type: 'integer', example: 10, maximum: 100)
            ),
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'filter', in: 'query', description: 'Filter by name (name=@value, name==value)', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'order', in: 'query', description: 'Order by: +/-id, +/-name', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'relations', in: 'query', description: 'Relations to include: ticket_types', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Successful response',
                content: new OA\JsonContent(ref: '#/components/schemas/PaginatedSummitTaxTypesResponse')
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
        path: '/api/v1/summits/{id}/tax-types',
        summary: 'Create a new tax type',
        security: [
            [
                'tax_types_oauth2' => [
                    SummitScopes::WriteSummitData,
                ]
            ]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
                IGroup::SummitRegistrationAdmins,
            ]
        ],
        tags: ['Summits Tax Types'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID', schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/SummitTaxTypeCreateRequest')
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'Tax type created',
                content: new OA\JsonContent(ref: '#/components/schemas/SummitTaxType')
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
        path: '/api/v1/summits/{id}/tax-types/{tax_id}',
        summary: 'Get a tax type by ID',
        security: [
            [
                'tax_types_oauth2' => [
                    SummitScopes::ReadSummitData,
                    SummitScopes::ReadAllSummitData,
                ]
            ]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
                IGroup::SummitRegistrationAdmins,
            ]
        ],
        tags: ['Summits Tax Types'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'tax_id', in: 'path', required: true, description: 'Tax Type ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Successful response',
                content: new OA\JsonContent(ref: '#/components/schemas/SummitTaxType')
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function get($summit_id, $tax_id)
    {
        return parent::get($summit_id, $tax_id);
    }

    #[OA\Put(
        path: '/api/v1/summits/{id}/tax-types/{tax_id}',
        summary: 'Update a tax type',
        security: [
            [
                'tax_types_oauth2' => [
                    SummitScopes::WriteSummitData,
                ]
            ]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
                IGroup::SummitRegistrationAdmins,
            ]
        ],
        tags: ['Summits Tax Types'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'tax_id', in: 'path', required: true, description: 'Tax Type ID', schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/SummitTaxTypeUpdateRequest')
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Tax type updated',
                content: new OA\JsonContent(ref: '#/components/schemas/SummitTaxType')
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function update($summit_id, $tax_id)
    {
        return parent::update($summit_id, $tax_id);
    }

    #[OA\Delete(
        path: '/api/v1/summits/{id}/tax-types/{tax_id}',
        summary: 'Delete a tax type',
        security: [
            [
                'tax_types_oauth2' => [
                    SummitScopes::WriteSummitData,
                ]
            ]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
                IGroup::SummitRegistrationAdmins,
            ]
        ],
        tags: ['Summits Tax Types'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'tax_id', in: 'path', required: true, description: 'Tax Type ID', schema: new OA\Schema(type: 'integer')),
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
    public function delete($summit_id, $tax_id)
    {
        return parent::delete($summit_id, $tax_id);
    }

    /**
     * @return array
     */
    protected function getFilterRules(): array
    {
        return [
            'name' => ['=@', '=='],
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

    public function __construct
    (
        ISummitTaxTypeRepository $repository,
        ISummitRepository $summit_repository,
        ISummitTaxTypeService $service,
        IResourceServerContext $resource_server_context
    ) {
        parent::__construct($resource_server_context);
        $this->repository = $repository;
        $this->summit_repository = $summit_repository;
        $this->service = $service;
    }

    /**
     * @return ISummitRepository
     */
    protected function getSummitRepository(): ISummitRepository
    {
        return $this->summit_repository;
    }

    /**
     * @param array $payload
     * @return array
     */
    protected function getAddValidationRules(array $payload): array
    {
        return TaxTypeValidationRulesFactory::build($payload);
    }

    /**
     * @param Summit $summit
     * @param array $payload
     * @return IEntity
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    protected function addChild(Summit $summit, array $payload): IEntity
    {
        return $this->service->addTaxType($summit, $payload);
    }

    protected function getChildFromSummit(Summit $summit, $child_id): ?IEntity
    {
        return $summit->getTaxTypeById($child_id);
    }

    /**
     * @param array $payload
     * @return array
     */
    function getUpdateValidationRules(array $payload): array
    {
        return TaxTypeValidationRulesFactory::build($payload, true);
    }

    /**
     * @param Summit $summit
     * @param int $child_id
     * @param array $payload
     * @return IEntity
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    protected function updateChild(Summit $summit, int $child_id, array $payload): IEntity
    {
        return $this->service->updateTaxType($summit, $child_id, $payload);
    }

    /**
     * @param Summit $summit
     * @param $child_id
     * @throws EntityNotFoundException
     */
    protected function deleteChild(Summit $summit, $child_id): void
    {
        $this->service->deleteTaxType($summit, $child_id);
    }

    /**
     * @param $summit_id
     * @param $tax_id
     * @param $ticket_type_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    #[OA\Put(
        path: '/api/v1/summits/{id}/tax-types/{tax_id}/ticket-types/{ticket_type_id}',
        summary: 'Add a tax type to a ticket type',
        security: [
            [
                'tax_types_oauth2' => [
                    SummitScopes::WriteSummitData,
                ]
            ]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
                IGroup::SummitRegistrationAdmins,
            ]
        ],
        tags: ['Summits Tax Types'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'tax_id', in: 'path', required: true, description: 'Tax Type ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'ticket_type_id', in: 'path', required: true, description: 'Ticket Type ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Tax type added to ticket type successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/SummitTaxType')
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function addTaxToTicketType($summit_id, $tax_id, $ticket_type_id)
    {
        try {

            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit))
                return $this->error404();

            $child = $this->service->addTaxTypeToTicketType($summit, $tax_id, $ticket_type_id);
            return $this->updated(SerializerRegistry::getInstance()->getSerializer($child)->serialize());
        } catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412(array($ex1->getMessage()));
        } catch (EntityNotFoundException $ex2) {
            Log::warning($ex2);
            return $this->error404(array('message' => $ex2->getMessage()));
        } catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $summit_id
     * @param $tax_id
     * @param $ticket_type_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    #[OA\Delete(
        path: '/api/v1/summits/{id}/tax-types/{tax_id}/ticket-types/{ticket_type_id}',
        summary: 'Remove a tax type from a ticket type',
        security: [
            [
                'tax_types_oauth2' => [
                    SummitScopes::WriteSummitData,
                ]
            ]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
                IGroup::SummitRegistrationAdmins,
            ]
        ],
        tags: ['Summits Tax Types'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'tax_id', in: 'path', required: true, description: 'Tax Type ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'ticket_type_id', in: 'path', required: true, description: 'Ticket Type ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Tax type removed from ticket type successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/SummitTaxType')
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function removeTaxFromTicketType($summit_id, $tax_id, $ticket_type_id)
    {
        try {

            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit))
                return $this->error404();

            $child = $this->service->removeTaxTypeFromTicketType($summit, $tax_id, $ticket_type_id);
            return $this->updated(SerializerRegistry::getInstance()->getSerializer($child)->serialize());
        } catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412(array($ex1->getMessage()));
        } catch (EntityNotFoundException $ex2) {
            Log::warning($ex2);
            return $this->error404(array('message' => $ex2->getMessage()));
        } catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }
}
