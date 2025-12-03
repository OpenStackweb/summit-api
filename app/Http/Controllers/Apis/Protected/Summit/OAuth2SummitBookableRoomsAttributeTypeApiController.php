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

use App\Models\Foundation\Summit\Repositories\ISummitBookableVenueRoomAttributeTypeRepository;
use App\Models\Foundation\Summit\Repositories\ISummitBookableVenueRoomAttributeValueRepository;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;
use libs\utils\PaginationValidationRules;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitRepository;
use ModelSerializers\SerializerRegistry;
use services\model\ISummitService;
use utils\Filter;
use utils\FilterElement;
use utils\FilterParser;
use utils\OrderParser;
use utils\PagingInfo;
use Exception;
use App\Security\SummitScopes;
use App\Models\Foundation\Main\IGroup;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;
/**
 * Class OAuth2SummitBookableRoomsAttributeTypeApiController
 * @package App\Http\Controllers
 */
final class OAuth2SummitBookableRoomsAttributeTypeApiController extends OAuth2ProtectedController
{
    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * @var ISummitBookableVenueRoomAttributeTypeRepository
     */
    private $attribute_type_repository;

    /**
     * @var ISummitBookableVenueRoomAttributeValueRepository
     */
    private $attribute_value_repository;

    /**
     * @var ISummitService
     */
    private $summit_service;

    /**
     * OAuth2SummitBookableRoomsAttributeTypeApiController constructor.
     * @param ISummitBookableVenueRoomAttributeTypeRepository $attribute_type_repository
     * @param ISummitBookableVenueRoomAttributeValueRepository $attribute_value_repository
     * @param ISummitRepository $summit_repository
     * @param ISummitService $summit_service
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        ISummitBookableVenueRoomAttributeTypeRepository $attribute_type_repository,
        ISummitBookableVenueRoomAttributeValueRepository $attribute_value_repository,
        ISummitRepository $summit_repository,
        ISummitService $summit_service,
        IResourceServerContext $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->attribute_type_repository  = $attribute_type_repository;
        $this->attribute_value_repository = $attribute_value_repository;
        $this->summit_repository          = $summit_repository;
        $this->summit_service             = $summit_service;
    }

    #[OA\Get(
        path: "/api/v1/summits/{id}/bookable-room-attribute-types",
        description: "Get all bookable room attribute types for a summit",
        summary: 'Get all bookable room attribute types',
        operationId: 'getAllBookableRoomAttributeTypes',
        tags: ['Bookable Room Attributes'],
        security: [['summit_bookable_rooms_attribute_oauth2' => [
            SummitScopes::ReadSummitData,
            SummitScopes::ReadAllSummitData,
        ]]],
        parameters: [
            new OA\Parameter(
                name: 'access_token',
                in: 'query',
                required: false,
                description: 'OAuth2 access token (alternative to Authorization: Bearer)',
                schema: new OA\Schema(type: 'string', example: 'eyJhbGciOi...'),
            ),
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The summit id'
            ),
            new OA\Parameter(
                name: 'page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 1),
                description: 'Page number'
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 10),
                description: 'Items per page'
            ),
            new OA\Parameter(
                name: 'filter[]',
                in: 'query',
                required: false,
                description: 'Filter expressions in the format field<op>value. Operators: @@, ==, =@.',
                style: 'form',
                explode: true,
                schema: new OA\Schema(
                    type: 'array',
                    items: new OA\Items(type: 'string', example: 'type@@Room')
                )
            ),
            new OA\Parameter(
                name: 'order',
                in: 'query',
                required: false,
                description: 'Order by field(s)',
                schema: new OA\Schema(type: 'string', example: 'id,-type')
            ),
            new OA\Parameter(
                name: 'expand',
                in: 'query',
                required: false,
                description: 'Comma-separated list of related resources to include',
                schema: new OA\Schema(type: 'string', example: 'values')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Get all paginated bookable room attribute types',
                content:  new OA\JsonContent(ref: '#/components/schemas/PaginatedBookableRoomAttributeTypesResponse')
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    /**
     * @param $summit_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getAllBookableRoomAttributeTypes($summit_id){
        $values = Request::all();
        $rules  = PaginationValidationRules::get();

        try {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $validation = Validator::make($values, $rules);

            if ($validation->fails()) {
                $ex = new ValidationException();
                throw $ex->setMessages($validation->messages()->toArray());
            }

            // default values
            $page     = 1;
            $per_page = PaginationValidationRules::PerPageMin;

            if (Request::has('page')) {
                $page     = intval(Request::input('page'));
                $per_page = intval(Request::input('per_page'));
            }

            $filter = null;

            if (Request::has('filter')) {
                $filter = FilterParser::parse(Request::input('filter'), [
                    'type'      => ['==', '=@'],
                    'summit_id' => ['=='],
                ]);
            }
            if(is_null($filter)) $filter = new Filter();

            $filter->validate([
                'type'      => 'sometimes|string',
                'summit_id' => 'sometimes|integer',
            ]);

            $order = null;

            if (Request::has('order'))
            {
                $order = OrderParser::parse(Request::input('order'), [
                    'id',
                    'type',
                ]);
            }

            $filter->addFilterCondition(FilterElement::makeEqual("summit_id", $summit->getId()));

            $data = $this->attribute_type_repository->getAllByPage(new PagingInfo($page, $per_page), $filter, $order);

            return $this->ok
            (
                $data->toArray
                (
                    Request::input('expand', ''),
                    [],
                    [],
                    []
                )
            );
        }
        catch (ValidationException $ex1)
        {
            Log::warning($ex1);
            return $this->error412(array( $ex1->getMessage()));
        }
        catch (EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(array('message' => $ex2->getMessage()));
        }
        catch(\HTTP401UnauthorizedException $ex3)
        {
            Log::warning($ex3);
            return $this->error401();
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    #[OA\Post(
        path: "/api/v1/summits/{id}/bookable-room-attribute-types",
        description: "Create a new bookable room attribute type",
        summary: 'Create bookable room attribute type',
        operationId: 'addBookableRoomAttributeType',
        tags: ['Bookable Room Attributes'],
        security: [['summit_bookable_rooms_attribute_oauth2' => [
            SummitScopes::WriteSummitData,
        ]]],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'access_token',
                in: 'query',
                required: false,
                description: 'OAuth2 access token (alternative to Authorization: Bearer)',
                schema: new OA\Schema(type: 'string', example: 'eyJhbGciOi...'),
            ),
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The summit id'
            ),
            new OA\Parameter(
                name: 'expand',
                in: 'query',
                required: false,
                description: 'Comma-separated list of related resources to include',
                schema: new OA\Schema(type: 'string', example: 'values')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/BookableRoomAttributeTypeCreateRequest')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Bookable room attribute type created',
                content: new OA\JsonContent(ref: '#/components/schemas/SummitBookableVenueRoomAttributeType')
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    /**
     * @param $summit_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function addBookableRoomAttributeType($summit_id){
        try {
            if(!Request::isJson()) return $this->error400();
            $payload = Request::json()->all();

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $rules = [
                'type' => 'required|string',
            ];

            // Creates a Validator instance and validates the data.
            $validation = Validator::make($payload, $rules);

            if ($validation->fails()) {
                $messages = $validation->messages()->toArray();

                return $this->error412
                (
                    $messages
                );
            }

            $attr = $this->summit_service->addBookableRoomAttribute($summit, $payload);
            return $this->created(SerializerRegistry::getInstance()->getSerializer($attr)->serialize(Request::input('expand', '')));
        }
        catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412(array($ex1->getMessage()));
        }
        catch(EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(array('message'=> $ex2->getMessage()));
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    #[OA\Get(
        path: "/api/v1/summits/{id}/bookable-room-attribute-types/{type_id}",
        description: "Get a specific bookable room attribute type",
        summary: 'Get bookable room attribute type',
        operationId: 'getBookableRoomAttributeType',
        tags: ['Bookable Room Attributes'],
        security: [['summit_bookable_rooms_attribute_oauth2' => [
            SummitScopes::ReadSummitData,
            SummitScopes::ReadAllSummitData,
        ]]],
        parameters: [
            new OA\Parameter(
                name: 'access_token',
                in: 'query',
                required: false,
                description: 'OAuth2 access token (alternative to Authorization: Bearer)',
                schema: new OA\Schema(type: 'string', example: 'eyJhbGciOi...'),
            ),
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The summit id'
            ),
            new OA\Parameter(
                name: 'type_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The attribute type id'
            ),
            new OA\Parameter(
                name: 'expand',
                in: 'query',
                required: false,
                description: 'Comma-separated list of related resources to include',
                schema: new OA\Schema(type: 'string', example: 'values')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Bookable room attribute type',
                content: new OA\JsonContent(ref: '#/components/schemas/SummitBookableVenueRoomAttributeType')
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    /**
     * @param $summit_id
     * @param $type_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getBookableRoomAttributeType($summit_id, $type_id){
        try {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $attr = $summit->getBookableAttributeTypeById($type_id);
            if (is_null($attr)) return $this->error404();

            return $this->ok(SerializerRegistry::getInstance()->getSerializer($attr)->serialize(Request::input('expand', '')));
        }
        catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412(array($ex1->getMessage()));
        }
        catch(EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(array('message'=> $ex2->getMessage()));
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }
    #[OA\Put(
        path: "/api/v1/summits/{id}/bookable-room-attribute-types/{type_id}",
        description: "Update a bookable room attribute type",
        summary: 'Update bookable room attribute type',
        operationId: 'updateBookableRoomAttributeType',
        tags: ['Bookable Room Attributes'],
        security: [['summit_bookable_rooms_attribute_oauth2' => [
            SummitScopes::WriteSummitData,
        ]]],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'access_token',
                in: 'query',
                required: false,
                description: 'OAuth2 access token (alternative to Authorization: Bearer)',
                schema: new OA\Schema(type: 'string', example: 'eyJhbGciOi...'),
            ),
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The summit id'
            ),
            new OA\Parameter(
                name: 'type_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The attribute type id'
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/BookableRoomAttributeTypeCreateRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Bookable room attribute type updated',
                content: new OA\JsonContent(ref: '#/components/schemas/SummitBookableVenueRoomAttributeType')
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    /**
     * @param $summit_id
     * @param $type_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function updateBookableRoomAttributeType($summit_id, $type_id){
        try {
            if(!Request::isJson()) return $this->error400();
            $payload = Request::json()->all();

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $rules = [
                'type' => 'required|string',
            ];

            // Creates a Validator instance and validates the data.
            $validation = Validator::make($payload, $rules);

            if ($validation->fails()) {
                $messages = $validation->messages()->toArray();

                return $this->error412
                (
                    $messages
                );
            }

            $attr = $this->summit_service->updateBookableRoomAttribute($summit, $type_id, $payload);
            return $this->updated(SerializerRegistry::getInstance()->getSerializer($attr)->serialize());
        }
        catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412(array($ex1->getMessage()));
        }
        catch(EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(array('message'=> $ex2->getMessage()));
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    #[OA\Delete(
        path: "/api/v1/summits/{id}/bookable-room-attribute-types/{type_id}",
        description: "Delete a bookable room attribute type",
        summary: 'Delete bookable room attribute type',
        operationId: 'deleteBookableRoomAttributeType',
        tags: ['Bookable Room Attributes'],
        security: [['summit_bookable_rooms_attribute_oauth2' => [
            SummitScopes::WriteSummitData,
        ]]],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'access_token',
                in: 'query',
                required: false,
                description: 'OAuth2 access token (alternative to Authorization: Bearer)',
                schema: new OA\Schema(type: 'string', example: 'eyJhbGciOi...'),
            ),
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The summit id'
            ),
            new OA\Parameter(
                name: 'type_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The attribute type id'
            ),
        ],
        responses: [
            new OA\Response(
                response: 204,
                description: "Deleted"
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    /**
     * @param $summit_id
     * @param $type_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function deleteBookableRoomAttributeType($summit_id, $type_id){
        try {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $this->summit_service->deleteBookableRoomAttribute($summit, $type_id);
            return $this->deleted();
        }
        catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412(array($ex1->getMessage()));
        }
        catch(EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(array('message'=> $ex2->getMessage()));
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    // values

    #[OA\Get(
        path: "/api/v1/summits/{id}/bookable-room-attribute-types/{type_id}/values",
        description: "Get all values for a bookable room attribute type",
        summary: 'Get all bookable room attribute values',
        operationId: 'getAllBookableRoomAttributeValues',
        tags: ['Bookable Room Attributes'],
        security: [['summit_bookable_rooms_attribute_oauth2' => [
            SummitScopes::ReadSummitData,
            SummitScopes::ReadAllSummitData,
        ]]],
        parameters: [
            new OA\Parameter(
                name: 'access_token',
                in: 'query',
                required: false,
                description: 'OAuth2 access token (alternative to Authorization: Bearer)',
                schema: new OA\Schema(type: 'string', example: 'eyJhbGciOi...'),
            ),
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The summit id'
            ),
            new OA\Parameter(
                name: 'type_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The attribute type id'
            ),
            new OA\Parameter(
                name: 'page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 1),
                description: 'Page number'
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 10),
                description: 'Items per page'
            ),
            new OA\Parameter(
                name: 'filter[]',
                in: 'query',
                required: false,
                description: 'Filter expressions in the format field<op>value. Operators: @@, ==, =@.',
                style: 'form',
                explode: true,
                schema: new OA\Schema(
                    type: 'array',
                    items: new OA\Items(type: 'string', example: 'value@@Large')
                )
            ),
            new OA\Parameter(
                name: 'order',
                in: 'query',
                required: false,
                description: 'Order by field(s)',
                schema: new OA\Schema(type: 'string', example: 'id,-value')
            ),
            new OA\Parameter(
                name: 'expand',
                in: 'query',
                required: false,
                description: 'Comma-separated list of related resources to include',
                schema: new OA\Schema(type: 'string', example: 'type')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Get all paginated bookable room attribute values',
                content: new OA\JsonContent(ref: '#/components/schemas/PaginatedBookableRoomAttributeValuesResponse')
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    /**
     * @param $summit_id
     * @param $type_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getAllBookableRoomAttributeValues($summit_id, $type_id){
        $values = Request::all();
        $rules  = PaginationValidationRules::get();

        try {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $attr = $summit->getBookableAttributeTypeById($type_id);
            if (is_null($attr)) return $this->error404();

            $validation = Validator::make($values, $rules);

            if ($validation->fails()) {
                $ex = new ValidationException();
                throw $ex->setMessages($validation->messages()->toArray());
            }

            // default values
            $page     = 1;
            $per_page = PaginationValidationRules::PerPageMin;

            if (Request::has('page')) {
                $page     = intval(Request::input('page'));
                $per_page = intval(Request::input('per_page'));
            }

            $filter = null;

            if (Request::has('filter')) {
                $filter = FilterParser::parse(Request::input('filter'), [
                    'value'      => ['==', '=@'],
                    'summit_id' => ['=='],
                    'type_id' => ['=='],
                ]);
            }
            if(is_null($filter)) $filter = new Filter();

            $filter->validate([
                'value'      => 'sometimes|string',
                'summit_id' => 'sometimes|integer',
                'type_id' => 'sometimes|integer',
            ]);

            $order = null;

            if (Request::has('order'))
            {
                $order = OrderParser::parse(Request::input('order'), [
                    'id',
                    'value',
                ]);
            }

            $filter->addFilterCondition(FilterElement::makeEqual("summit_id", $summit->getId()));
            $filter->addFilterCondition(FilterElement::makeEqual("type_id", $attr->getId()));

            $data = $this->attribute_value_repository->getAllByPage(new PagingInfo($page, $per_page), $filter, $order);

            return $this->ok
            (
                $data->toArray
                (
                    Request::input('expand', ''),
                    [],
                    [],
                    []
                )
            );
        }
        catch (ValidationException $ex1)
        {
            Log::warning($ex1);
            return $this->error412(array( $ex1->getMessage()));
        }
        catch (EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(array('message' => $ex2->getMessage()));
        }
        catch(\HTTP401UnauthorizedException $ex3)
        {
            Log::warning($ex3);
            return $this->error401();
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    #[OA\Get(
        path: "/api/v1/summits/{id}/bookable-room-attribute-types/{type_id}/values/{value_id}",
        description: "Get a specific bookable room attribute value",
        summary: 'Get bookable room attribute value',
        operationId: 'getBookableRoomAttributeValue',
        tags: ['Bookable Room Attributes'],
        security: [['summit_bookable_rooms_attribute_oauth2' => [
            SummitScopes::ReadSummitData,
            SummitScopes::ReadAllSummitData,
        ]]],
        parameters: [
            new OA\Parameter(
                name: 'access_token',
                in: 'query',
                required: false,
                description: 'OAuth2 access token (alternative to Authorization: Bearer)',
                schema: new OA\Schema(type: 'string', example: 'eyJhbGciOi...'),
            ),
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The summit id'
            ),
            new OA\Parameter(
                name: 'type_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The attribute type id'
            ),
            new OA\Parameter(
                name: 'value_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The attribute value id'
            ),
            new OA\Parameter(
                name: 'expand',
                in: 'query',
                required: false,
                description: 'Comma-separated list of related resources to include',
                schema: new OA\Schema(type: 'string', example: 'type')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Bookable room attribute value',
                content: new OA\JsonContent(ref: '#/components/schemas/SummitBookableVenueRoomAttributeValue')
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    /**
     * @param $summit_id
     * @param $type_id
     * @param $value_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getBookableRoomAttributeValue($summit_id, $type_id, $value_id){
        try {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $attr = $summit->getBookableAttributeTypeById($type_id);
            if (is_null($attr)) return $this->error404();

            $value = $attr->getValueById($value_id);
            if (is_null($value)) return $this->error404();

            return $this->ok(SerializerRegistry::getInstance()->getSerializer($value)->serialize());
        }
        catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412(array($ex1->getMessage()));
        }
        catch(EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(array('message'=> $ex2->getMessage()));
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    #[OA\Post(
        path: "/api/v1/summits/{id}/bookable-room-attribute-types/{type_id}/values",
        description: "Create a new bookable room attribute value",
        summary: 'Create bookable room attribute value',
        operationId: 'addBookableRoomAttributeValue',
        tags: ['Bookable Room Attributes'],
        security: [['summit_bookable_rooms_attribute_oauth2' => [
            SummitScopes::WriteSummitData,
        ]]],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'access_token',
                in: 'query',
                required: false,
                description: 'OAuth2 access token (alternative to Authorization: Bearer)',
                schema: new OA\Schema(type: 'string', example: 'eyJhbGciOi...'),
            ),
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The summit id'
            ),
            new OA\Parameter(
                name: 'type_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The attribute type id'
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/BookableRoomAttributeValueCreateRequest')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Bookable room attribute value created',
                content: new OA\JsonContent(ref: '#/components/schemas/SummitBookableVenueRoomAttributeValue')
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    /**
     * @param $summit_id
     * @param $type_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function addBookableRoomAttributeValue($summit_id, $type_id){
        try {
            if(!Request::isJson()) return $this->error400();
            $payload = Request::json()->all();

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $rules = [
                'value' => 'required|string',
            ];

            // Creates a Validator instance and validates the data.
            $validation = Validator::make($payload, $rules);

            if ($validation->fails()) {
                $messages = $validation->messages()->toArray();

                return $this->error412
                (
                    $messages
                );
            }

            $value = $this->summit_service->addBookableRoomAttributeValue($summit, $type_id, $payload);
            return $this->created(SerializerRegistry::getInstance()->getSerializer($value)->serialize());
        }
        catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412(array($ex1->getMessage()));
        }
        catch(EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(array('message'=> $ex2->getMessage()));
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    #[OA\Put(
        path: "/api/v1/summits/{id}/bookable-room-attribute-types/{type_id}/values/{value_id}",
        description: "Update a bookable room attribute value",
        summary: 'Update bookable room attribute value',
        operationId: 'updateBookableRoomAttributeValue',
        tags: ['Bookable Room Attributes'],
        security: [['summit_bookable_rooms_attribute_oauth2' => [
            SummitScopes::WriteSummitData,
        ]]],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'access_token',
                in: 'query',
                required: false,
                description: 'OAuth2 access token (alternative to Authorization: Bearer)',
                schema: new OA\Schema(type: 'string', example: 'eyJhbGciOi...'),
            ),
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The summit id'
            ),
            new OA\Parameter(
                name: 'type_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The attribute type id'
            ),
            new OA\Parameter(
                name: 'value_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The attribute value id'
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/BookableRoomAttributeValueCreateRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Bookable room attribute value updated',
                content: new OA\JsonContent(ref: '#/components/schemas/SummitBookableVenueRoomAttributeValue')
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    /**
     * @param $summit_id
     * @param $type_id
     * @param $value_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function updateBookableRoomAttributeValue($summit_id, $type_id, $value_id){
        try {
            if(!Request::isJson()) return $this->error400();
            $payload = Request::json()->all();

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $rules = [
                'value' => 'required|string',
            ];

            // Creates a Validator instance and validates the data.
            $validation = Validator::make($payload, $rules);

            if ($validation->fails()) {
                $messages = $validation->messages()->toArray();

                return $this->error412
                (
                    $messages
                );
            }

            $value = $this->summit_service->updateBookableRoomAttributeValue($summit, $type_id, $value_id, $payload);
            return $this->updated(SerializerRegistry::getInstance()->getSerializer($value)->serialize());
        }
        catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412(array($ex1->getMessage()));
        }
        catch(EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(array('message'=> $ex2->getMessage()));
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    #[OA\Delete(
        path: "/api/v1/summits/{id}/bookable-room-attribute-types/{type_id}/values/{value_id}",
        description: "Delete a bookable room attribute value",
        summary: 'Delete bookable room attribute value',
        operationId: 'deleteBookableRoomAttributeValue',
        tags: ['Bookable Room Attributes'],
        security: [['summit_bookable_rooms_attribute_oauth2' => [
            SummitScopes::WriteSummitData,
        ]]],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'access_token',
                in: 'query',
                required: false,
                description: 'OAuth2 access token (alternative to Authorization: Bearer)',
                schema: new OA\Schema(type: 'string', example: 'eyJhbGciOi...'),
            ),
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The summit id'
            ),
            new OA\Parameter(
                name: 'type_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The attribute type id'
            ),
            new OA\Parameter(
                name: 'value_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The attribute value id'
            ),
        ],
        responses: [
            new OA\Response(
                response: 204,
                description: "Deleted"
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    /**
     * @param $summit_id
     * @param $type_id
     * @param $value_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function deleteBookableRoomAttributeValue($summit_id, $type_id, $value_id){
        try {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $this->summit_service->deleteBookableRoomAttributeValue($summit, $type_id, $value_id);
            return $this->deleted();
        }
        catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412(array($ex1->getMessage()));
        }
        catch(EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(array('message'=> $ex2->getMessage()));
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }
}