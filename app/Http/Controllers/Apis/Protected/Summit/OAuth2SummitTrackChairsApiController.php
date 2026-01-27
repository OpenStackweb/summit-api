<?php namespace App\Http\Controllers;
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

use App\Http\Exceptions\HTTP403ForbiddenException;
use App\Http\Utils\EpochCellFormatter;
use App\Models\Foundation\Main\IGroup;
use App\Models\Foundation\Summit\Repositories\ISummitTrackChairRepository;
use App\Security\SummitScopes;
use App\Services\Model\ITrackChairService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitRepository;
use models\summit\Summit;
use models\utils\IEntity;
use ModelSerializers\SerializerRegistry;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;
use utils\Filter;
use utils\FilterElement;
use Exception;

/**
 * Class OAuth2SummitTrackChairsApiController
 * @package App\Http\Controllers
 */
final class OAuth2SummitTrackChairsApiController
    extends OAuth2ProtectedController
{
    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * @var ITrackChairService
     */
    private $service;

    /**
     * OAuth2SummitTrackChairsApiController constructor.
     * @param ISummitRepository $summit_repository
     * @param ISummitTrackChairRepository $repository
     * @param ITrackChairService $service
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        ISummitRepository $summit_repository,
        ISummitTrackChairRepository $repository,
        ITrackChairService $service,
        IResourceServerContext $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->repository = $repository;
        $this->service = $service;
        $this->summit_repository = $summit_repository;
    }

    use ParametrizedGetAll;

    #[OA\Get(
        path: "/api/v1/summits/{id}/track-chairs",
        operationId: "getAllTrackChairs",
        summary: "Get all track chairs for a summit",
        description: "Returns different data based on user role: Public view for regular users, Admin view for admins/track chairs",
        tags: ["Track Chairs"],
        security: [
            [
                'summit_track_chairs_oauth2' => [
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
                IGroup::TrackChairsAdmins,
                IGroup::TrackChairs
            ]
        ],
        parameters: [
            new OA\Parameter(name: "id", description: "Summit ID", in: "path", required: true, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "page", description: "Page number", in: "query", required: false, schema: new OA\Schema(type: "integer", default: 1)),
            new OA\Parameter(name: "per_page", description: "Items per page", in: "query", required: false, schema: new OA\Schema(type: "integer", default: 10)),
            new OA\Parameter(name: "filter", description: "Filter query (member_first_name=@value, member_last_name=@value, member_email=@value, member_id==value, track_id==value)", in: "query", required: false, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "order", description: "Order by (+member_first_name, -member_last_name, +member_email, +id, +track_id)", in: "query", required: false, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "expand", description: "Expand relations (categories, member, summit)", in: "query", required: false, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "relations", description: "Include relations (categories)", in: "query", required: false, schema: new OA\Schema(type: "string")),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "OK",
                content: new OA\JsonContent(ref: "#/components/schemas/PaginatedTrackChairsResponse")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function getAllBySummit($summit_id){
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
        if (is_null($summit)) return $this->error404();

        return $this->_getAll(
            function () {
                return [
                    'member_first_name' => ['=@', '=='],
                    'member_last_name' => ['=@', '=='],
                    'member_full_name' => ['=@', '=='],
                    'member_email' => ['=@', '=='],
                    'member_id' => ['=='],
                    'track_id' => ['=='],
                    'summit_id' => ['=='],
                ];
            },
            function () {
                return [
                    'member_first_name' => 'sometimes|string',
                    'member_last_name' => 'sometimes|string',
                    'member_full_name' => 'sometimes|string',
                    'member_email' => 'sometimes|string',
                    'member_id' => 'sometimes|integer',
                    'track_id' => 'sometimes|integer',
                    'summit_id' => 'sometimes|integer',
                ];
            },
            function () {
                return [
                    'member_first_name',
                    'member_last_name',
                    'member_email',
                    'member_full_name',
                    'id',
                    'track_id',
                ];
            },
            function ($filter) use ($summit) {
                if ($filter instanceof Filter) {
                    $filter->addFilterCondition(FilterElement::makeEqual('summit_id', $summit->getId()));
                }
                return $filter;
            },
            function () {
                $current_user = $this->resource_server_context->getCurrentUser();
                if(!is_null($current_user)){
                    if(
                        $current_user->isOnGroup(IGroup::Administrators) ||
                        $current_user->isOnGroup(IGroup::SuperAdmins) ||
                        $current_user->isOnGroup(IGroup::TrackChairsAdmins) ||
                        $current_user->isOnGroup(IGroup::SummitAdministrators) ||
                        $current_user->isOnGroup(IGroup::TrackChairs)
                    )
                        return SerializerRegistry::SerializerType_Private;
                }
                return SerializerRegistry::SerializerType_Public;
            }
        );
    }

    #[OA\Get(
        path: "/api/v1/summits/{id}/track-chairs/csv",
        operationId: "getAllTrackChairsCSV",
        summary: "Get all track chairs for a summit in CSV format",
        tags: ["Track Chairs"],
        security: [
            [
                'summit_track_chairs_oauth2' => [
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
                IGroup::TrackChairsAdmins
            ]
        ],
        parameters: [
            new OA\Parameter(name: "id", description: "Summit ID", in: "path", required: true, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "filter", description: "Filter query", in: "query", required: false, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "order", description: "Order by", in: "query", required: false, schema: new OA\Schema(type: "string")),
            new OA\Parameter(
                name: "columns",
                description: "Comma-separated list of columns to export. Allowed: created,last_edited,member_first_name,member_last_name,member_email,member_id,categories,summit_id",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string"),
                example: "member_first_name,member_last_name,member_email,categories"
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "OK - CSV file download",
                content: new OA\MediaType(
                    mediaType: "text/csv",
                    schema: new OA\Schema(type: "string", format: "binary")
                )
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function getAllBySummitCSV($summit_id){

        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
        if (is_null($summit)) return $this->error404();

        $current_member = $this->resource_server_context->getCurrentUser();
        if (is_null($current_member)) return $this->error403();

        return $this->_getAllCSV(
            function () {
                return [
                    'member_first_name' => ['=@', '=='],
                    'member_last_name' => ['=@', '=='],
                    'member_full_name' => ['=@', '=='],
                    'member_email' => ['=@', '=='],
                    'member_id' => ['=='],
                    'track_id' => ['=='],
                    'summit_id' => ['=='],
                ];
            },
            function () {
                return [
                    'member_first_name' => 'sometimes|string',
                    'member_last_name' => 'sometimes|string',
                    'member_full_name' => 'sometimes|string',
                    'member_email' => 'sometimes|string',
                    'member_id' => 'sometimes|integer',
                    'track_id' => 'sometimes|integer',
                    'summit_id' => 'sometimes|integer',
                ];
            },
            function () {
                return [
                    'member_first_name',
                    'member_last_name',
                    'member_email',
                    'member_full_name',
                    'id',
                    'track_id',
                ];
            },
            function ($filter) use ($summit) {
                if ($filter instanceof Filter) {
                    $filter->addFilterCondition(FilterElement::makeEqual('summit_id', $summit->getId()));
                }
                return $filter;
            },
            function(){
                return SerializerRegistry::SerializerType_CSV;
            },
            function(){
                return [
                    'created' => new EpochCellFormatter(),
                    'last_edited' => new EpochCellFormatter(),
                ];
            },
            function(){

                $allowed_columns = [
                    'created',
                    'last_edited',
                    'member_first_name',
                    'member_last_name',
                    'member_email',
                    'member_id',
                    'categories',
                    'summit_id'
                ];

                $columns_param = Request::input("columns", "");
                $columns = [];
                if(!empty($columns_param))
                    $columns  = explode(',', $columns_param);
                $diff     = array_diff($columns, $allowed_columns);
                if(count($diff) > 0){
                    throw new ValidationException(sprintf("columns %s are not allowed!", implode(",", $diff)));
                }
                if(empty($columns))
                    $columns = $allowed_columns;
                return $columns;
            },
            'track-chairs-'
        );
    }

    use AddSummitChildElement;

    /**
     * @param array $payload
     * @return array
     */
    function getAddValidationRules(array $payload): array
    {
        return [
            'member_id'   => 'required|int',
            'categories' => 'required|int_array',
        ];
    }

    /**
     * @param Summit $summit
     * @param array $payload
     * @return IEntity
     */
    protected function addChild(Summit $summit, array $payload): IEntity
    {
        return $this->service->addTrackChair($summit, $payload);
    }

    /**
     * @inheritDoc
     */
    protected function getSummitRepository(): ISummitRepository
    {
        return $this->summit_repository;
    }

    use UpdateSummitChildElement;

    function getUpdateValidationRules(array $payload): array{
        return [
            'categories' => 'required|int_array',
        ];
    }

    /**
     * @param Summit $summit
     * @param int $child_id
     * @param array $payload
     * @return IEntity
     */
    protected function updateChild(Summit $summit,int $child_id, array $payload):IEntity{
        return $this->service->updateTrackChair($summit, $child_id, $payload);
    }

    use DeleteSummitChildElement;

    /**
     * @param Summit $summit
     * @param $child_id
     * @return void
     */
    protected function deleteChild(Summit $summit, $child_id):void{
        $this->service->deleteTrackChair($summit, $child_id);
    }

    use GetSummitChildElementById;

    /**
     * @param Summit $summit
     * @param $child_id
     * @return IEntity|null
     */
    protected function getChildFromSummit(Summit $summit, $child_id):?IEntity{
        return $summit->getTrackChair(intval($child_id));
    }

    #[OA\Post(
        path: "/api/v1/summits/{id}/track-chairs",
        operationId: "addTrackChair",
        summary: "Add a track chair to a summit",
        tags: ["Track Chairs"],
        security: [
            [
                'summit_track_chairs_oauth2' => [
                    SummitScopes::WriteSummitData,
                ]
            ]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
                IGroup::TrackChairsAdmins
            ]
        ],
        parameters: [
            new OA\Parameter(name: "id", description: "Summit ID", in: "path", required: true, schema: new OA\Schema(type: "string")),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/TrackChairAddRequest")
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: "Created",
                content: new OA\JsonContent(ref: "#/components/schemas/AdminSummitTrackChair")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function add($summit_id){
        return $this->processRequest(function() use($summit_id){
            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();
            return $this->_add($summit, $this->getJsonPayload($this->getAddValidationRules()));
        });
    }

    #[OA\Get(
        path: "/api/v1/summits/{id}/track-chairs/{track_chair_id}",
        operationId: "getTrackChair",
        summary: "Get a track chair by id",
        tags: ["Track Chairs"],
        security: [
            [
                'summit_track_chairs_oauth2' => [
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
                IGroup::TrackChairsAdmins,
            ]
        ],
        parameters: [
            new OA\Parameter(name: "id", description: "Summit ID", in: "path", required: true, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "track_chair_id", description: "Track chair ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "expand", description: "Expand relations (categories, member, summit)", in: "query", required: false, schema: new OA\Schema(type: "string")),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "OK",
                content: new OA\JsonContent(
                    oneOf: [
                        new OA\Schema(ref: "#/components/schemas/SummitTrackChair"),
                        new OA\Schema(ref: "#/components/schemas/AdminSummitTrackChair")
                    ]
                )
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function get($summit_id, $track_chair_id){
        return $this->processRequest(function() use($summit_id, $track_chair_id){
            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();
            return $this->_get($summit, $track_chair_id);
        });
    }

    #[OA\Put(
        path: "/api/v1/summits/{id}/track-chairs/{track_chair_id}",
        operationId: "updateTrackChair",
        summary: "Update a track chair's categories",
        tags: ["Track Chairs"],
        security: [
            [
                'summit_track_chairs_oauth2' => [
                    SummitScopes::WriteSummitData,
                ]
            ]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
                IGroup::TrackChairsAdmins
            ]
        ],
        parameters: [
            new OA\Parameter(name: "id", description: "Summit ID", in: "path", required: true, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "track_chair_id", description: "Track chair ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/TrackChairUpdateRequest")
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "OK",
                content: new OA\JsonContent(ref: "#/components/schemas/AdminSummitTrackChair")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function update($summit_id, $track_chair_id){
        return $this->processRequest(function() use($summit_id, $track_chair_id){
            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();
            return $this->_update($summit, $track_chair_id, $this->getJsonPayload($this->getUpdateValidationRules()));
        });
    }

    #[OA\Delete(
        path: "/api/v1/summits/{id}/track-chairs/{track_chair_id}",
        operationId: "deleteTrackChair",
        summary: "Delete a track chair",
        tags: ["Track Chairs"],
        security: [
            [
                'summit_track_chairs_oauth2' => [
                    SummitScopes::WriteSummitData,
                ]
            ]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
                IGroup::TrackChairsAdmins
            ]
        ],
        parameters: [
            new OA\Parameter(name: "id", description: "Summit ID", in: "path", required: true, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "track_chair_id", description: "Track chair ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_NO_CONTENT, description: "No Content"),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function delete($summit_id, $track_chair_id){
        return $this->processRequest(function() use($summit_id, $track_chair_id){
            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();
            return $this->_delete($summit, $track_chair_id);
        });
    }

    #[OA\Post(
        path: "/api/v1/summits/{id}/track-chairs/{track_chair_id}/categories/{track_id}",
        operationId: "addCategoryToTrackChair",
        summary: "Add a track/category to a track chair",
        tags: ["Track Chairs"],
        security: [
            [
                'summit_track_chairs_oauth2' => [
                    SummitScopes::WriteSummitData,
                ]
            ]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
                IGroup::TrackChairsAdmins
            ]
        ],
        parameters: [
            new OA\Parameter(name: "id", description: "Summit ID", in: "path", required: true, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "track_chair_id", description: "Track chair ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "track_id", description: "Track/Category ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "OK",
                content: new OA\JsonContent(ref: "#/components/schemas/AdminSummitTrackChair")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function addTrack2TrackChair($summit_id, $track_chair_id, $track_id){
        try{
            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();
            $track_chair = $this->service->addTrack2TrackChair($summit, intval($track_chair_id), intval($track_id));
            return $this->updated(SerializerRegistry::getInstance()->getSerializer($track_chair)->serialize(Request::input('expand', '')));
        }
        catch (ValidationException $ex) {
            Log::warning($ex);
            return $this->error412($ex->getMessages());
        }
        catch (EntityNotFoundException $ex) {
            Log::warning($ex);
            return $this->error404(array('message' => $ex->getMessage()));
        }
        catch (\HTTP401UnauthorizedException $ex) {
            Log::warning($ex);
            return $this->error401();
        }
        catch(HTTP403ForbiddenException $ex){
            Log::warning($ex);
            return $this->error403();
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    #[OA\Delete(
        path: "/api/v1/summits/{id}/track-chairs/{track_chair_id}/categories/{track_id}",
        operationId: "removeCategoryFromTrackChair",
        summary: "Remove a track/category from a track chair",
        tags: ["Track Chairs"],
        security: [
            [
                'summit_track_chairs_oauth2' => [
                    SummitScopes::WriteSummitData,
                ]
            ]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
                IGroup::TrackChairsAdmins
            ]
        ],
        parameters: [
            new OA\Parameter(name: "id", description: "Summit ID", in: "path", required: true, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "track_chair_id", description: "Track chair ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "track_id", description: "Track/Category ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "OK",
                content: new OA\JsonContent(ref: "#/components/schemas/AdminSummitTrackChair")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function removeFromTrackChair($summit_id, $track_chair_id, $track_id){
        try{
            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();
            $track_chair = $this->service->removeFromTrackChair($summit, intval($track_chair_id), intval($track_id));
            return $this->updated(SerializerRegistry::getInstance()->getSerializer($track_chair)->serialize(Request::input('expand', '')));
        }
        catch (ValidationException $ex) {
            Log::warning($ex);
            return $this->error412($ex->getMessages());
        }
        catch (EntityNotFoundException $ex) {
            Log::warning($ex);
            return $this->error404(array('message' => $ex->getMessage()));
        }
        catch (\HTTP401UnauthorizedException $ex) {
            Log::warning($ex);
            return $this->error401();
        }
        catch(HTTP403ForbiddenException $ex){
            Log::warning($ex);
            return $this->error403();
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

}
