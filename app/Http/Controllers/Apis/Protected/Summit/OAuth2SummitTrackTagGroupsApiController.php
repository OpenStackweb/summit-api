<?php namespace App\Http\Controllers;
/**
 * Copyright 2018 OpenStack Foundation
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
use App\Models\Foundation\Summit\Repositories\ITrackTagGroupAllowedTagsRepository;
use App\Security\SummitScopes;
use App\Services\Model\ISummitTrackTagGroupService;
use libs\utils\PaginationValidationRules;
use models\oauth2\IResourceServerContext;
use Illuminate\Support\Facades\Validator;
use ModelSerializers\SerializerRegistry;
use utils\Filter;
use utils\FilterParser;
use utils\FilterParserException;
use utils\OrderParser;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Log;
use utils\PagingInfo;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\summit\ISummitRepository;
use utils\PagingResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class OAuth2SummitTrackTagGroupsApiController
 * @package App\Http\Controllers
 */
final class OAuth2SummitTrackTagGroupsApiController extends OAuth2ProtectedController
{
    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * @var ISummitTrackTagGroupService
     */
    private $track_tag_group_service;

    /**
     * OAuth2SummitTrackTagGroupsApiController constructor.
     * @param ISummitRepository $summit_repository
     * @param ITrackTagGroupAllowedTagsRepository $repository
     * @param ISummitTrackTagGroupService $track_tag_group_service
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        ISummitRepository $summit_repository,
        ITrackTagGroupAllowedTagsRepository $repository,
        ISummitTrackTagGroupService $track_tag_group_service,
        IResourceServerContext $resource_server_context
    )
    {
        parent::__construct($resource_server_context);

        $this->summit_repository  = $summit_repository;
        $this->track_tag_group_service = $track_tag_group_service;
        $this->repository = $repository;
    }

    #[OA\Get(
        path: "/api/v1/summits/{id}/track-tag-groups",
        operationId: "getTrackTagGroupsBySummit",
        description: "Get all track tag groups for a summit",
        tags: ["Track Tag Groups"],
        security: [['summit_track_tag_groups_oauth2' => [
            SummitScopes::ReadAllSummitData,
        ]]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "string"),
                description: "Summit ID or slug"
            ),
            new OA\Parameter(
                name: "expand",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string"),
                description: "Expand relationships: allowed_tags"
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Track tag groups retrieved successfully",
                content: new OA\JsonContent(ref: "#/components/schemas/TrackTagGroupsList")
            ),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Summit not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    /**
     * @param $summit_id
     * @return mixed
     */
    public function getTrackTagGroupsBySummit($summit_id){
        try {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $track_tag_groups = $summit->getTrackTagGroups()->toArray();

            $response    = new PagingResponse
            (
                count($track_tag_groups),
                count($track_tag_groups),
                1,
                1,
                $track_tag_groups
            );

            return $this->ok($response->toArray($expand = Request::input('expand','')));
        }

        catch (EntityNotFoundException $ex1) {
            Log::warning($ex1);
            return $this->error404();
        }
        catch (ValidationException $ex2) {
            Log::warning($ex2);
            return $this->error412($ex2->getMessages());
        }
        catch(FilterParserException $ex3){
            Log::warning($ex3);
            return $this->error412($ex3->getMessages());
        }
        catch (\Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    #[OA\Get(
        path: "/api/v1/summits/{id}/track-tag-groups/all/allowed-tags",
        operationId: "getAllowedTags",
        description: "Get all allowed tags for track tag groups in a summit. required-groups " . IGroup::SuperAdmins . ", " . IGroup::Administrators . ", " . IGroup::SummitAdministrators,
        tags: ["Track Tag Groups"],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        security: [['summit_track_tag_groups_oauth2' => [
            SummitScopes::WriteSummitData,
        ]]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "string"),
                description: "Summit ID or slug"
            ),
            new OA\Parameter(
                name: "page",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "integer", default: 1),
                description: "Page number"
            ),
            new OA\Parameter(
                name: "per_page",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "integer", default: 5),
                description: "Items per page"
            ),
            new OA\Parameter(
                name: "filter",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string"),
                description: "Filter by tag name"
            ),
            new OA\Parameter(
                name: "order",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string"),
                description: "Order by: tag, id"
            ),
            new OA\Parameter(
                name: "expand",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string"),
                description: "Expand relationships"
            ),
            new OA\Parameter(
                name: "fields",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string"),
                description: "Specific fields to return"
            ),
            new OA\Parameter(
                name: "relations",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string"),
                description: "Relations to include"
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Allowed tags retrieved successfully",
                content: new OA\JsonContent(ref: "#/components/schemas/PaginatedTrackTagGroupAllowedTagsResponse")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Summit not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    /**
     * @param $summit_id
     * @return mixed
     */
    public function getAllowedTags($summit_id){

        $values = Request::all();

        $rules = PaginationValidationRules::get();

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
            $per_page = 5;

            if (Request::has('page')) {
                $page     = intval(Request::input('page'));
                $per_page = intval(Request::input('per_page'));
            }

            $filter = null;

            if (Request::has('filter')) {
                $filter = FilterParser::parse(Request::input('filter'),  array
                (
                    'tag' => ['=@', '==', '@@'],
                ));
            }

            $order = null;

            if (Request::has('order'))
            {
                $order = OrderParser::parse(Request::input('order'), array
                (
                    'tag',
                    'id',
                ));
            }

            if(is_null($filter)) $filter = new Filter();

            $data      = $this->repository->getBySummit($summit, new PagingInfo($page, $per_page), $filter, $order);
            $fields    = Request::input('fields', '');
            $fields    = !empty($fields) ? explode(',', $fields) : [];
            $relations = Request::input('relations', '');
            $relations = !empty($relations) ? explode(',', $relations) : [];

            return $this->ok
            (
                $data->toArray
                (
                    Request::input('expand', ''),
                    $fields,
                    $relations
                )
            );
        }

        catch (EntityNotFoundException $ex1) {
            Log::warning($ex1);
            return $this->error404();
        }
        catch (ValidationException $ex2) {
            Log::warning($ex2);
            return $this->error412($ex2->getMessages());
        }
        catch(FilterParserException $ex3){
            Log::warning($ex3);
            return $this->error412($ex3->getMessages());
        }
        catch (\Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    #[OA\Post(
        path: "/api/v1/summits/{id}/track-tag-groups",
        operationId: "addTrackTagGroup",
        description: "Create a new track tag group. required-groups " . IGroup::SuperAdmins . ", " . IGroup::Administrators . ", " . IGroup::SummitAdministrators,
        tags: ["Track Tag Groups"],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        security: [['summit_track_tag_groups_oauth2' => [
            SummitScopes::WriteSummitData,
            SummitScopes::WriteTrackTagGroupsData,
        ]]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "string"),
                description: "Summit ID or slug"
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/CreateTrackTagGroupRequest")
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: "Track tag group created successfully",
                content: new OA\JsonContent(ref: "#/components/schemas/TrackTagGroup")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Summit not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    /**
     * @param $summit_id
     * @return mixed
     */
    public function addTrackTagGroup($summit_id){
        try {
            if(!Request::isJson()) return $this->error400();
            $data = Request::json();

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $rules = [
                'name'          => 'required|string|max:50',
                'label'         => 'required|string|max:50',
                'is_mandatory'  => 'required|boolean',
                'allowed_tags'  => 'sometimes|string_array',
            ];

            // Creates a Validator instance and validates the data.
            $validation = Validator::make($data->all(), $rules);

            if ($validation->fails()) {
                $messages = $validation->messages()->toArray();

                return $this->error412
                (
                    $messages
                );
            }

            $track_tag_group = $this->track_tag_group_service->addTrackTagGroup($summit, $data->all());

            return $this->created(SerializerRegistry::getInstance()->getSerializer($track_tag_group)->serialize(Request::input('expand', '')));
        }
        catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412([$ex1->getMessage()]);
        }
        catch(EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(['message'=> $ex2->getMessage()]);
        }
        catch (\Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    #[OA\Get(
        path: "/api/v1/summits/{id}/track-tag-groups/{track_tag_group_id}",
        operationId: "getTrackTagGroup",
        description: "Get a specific track tag group. required-groups " . IGroup::SuperAdmins . ", " . IGroup::Administrators . ", " . IGroup::SummitAdministrators,
        tags: ["Track Tag Groups"],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        security: [['summit_track_tag_groups_oauth2' => [
            SummitScopes::ReadAllSummitData,
        ]]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "string"),
                description: "Summit ID or slug"
            ),
            new OA\Parameter(
                name: "track_tag_group_id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", format: "int64"),
                description: "Track tag group ID"
            ),
            new OA\Parameter(
                name: "expand",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string"),
                description: "Expand relationships: allowed_tags"
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Track tag group retrieved successfully",
                content: new OA\JsonContent(ref: "#/components/schemas/TrackTagGroup")
            ),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Track tag group or summit not found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    /**
     * @param $summit_id
     * @param $track_tag_group_id
     * @return mixed
     */
    public function getTrackTagGroup($summit_id, $track_tag_group_id){
        try{
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();
            $track_tag_group = $summit->getTrackTagGroup(intval($track_tag_group_id));
            if(is_null($track_tag_group))
                return $this->error404();

            return $this->ok(SerializerRegistry::getInstance()->getSerializer($track_tag_group)->serialize(Request::input('expand', '')));
        }
        catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412([$ex1->getMessage()]);
        }
        catch(EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(['message'=> $ex2->getMessage()]);
        }
        catch (\Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    #[OA\Put(
        path: "/api/v1/summits/{id}/track-tag-groups/{track_tag_group_id}",
        operationId: "updateTrackTagGroup",
        description: "Update an existing track tag group. required-groups " . IGroup::SuperAdmins . ", " . IGroup::Administrators . ", " . IGroup::SummitAdministrators,
        tags: ["Track Tag Groups"],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        security: [['summit_track_tag_groups_oauth2' => [
            SummitScopes::WriteSummitData,
            SummitScopes::WriteTrackTagGroupsData,
        ]]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "string"),
                description: "Summit ID or slug"
            ),
            new OA\Parameter(
                name: "track_tag_group_id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", format: "int64"),
                description: "Track tag group ID"
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/UpdateTrackTagGroupRequest")
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Track tag group updated successfully",
                content: new OA\JsonContent(ref: "#/components/schemas/TrackTagGroup")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Track tag group or summit not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    /**
     * @param $summit_id
     * @param $track_tag_group_id
     * @return mixed
     */
    public function updateTrackTagGroup($summit_id, $track_tag_group_id){
        try {
            if(!Request::isJson()) return $this->error400();
            $data = Request::json();

            $summit = SummitFinderStrategyFactory::build
            (
                $this->summit_repository,
                $this->resource_server_context
            )->find($summit_id);

            if (is_null($summit)) return $this->error404();

            $rules = [
                'name'          => 'sometimes|string|max:50',
                'label'         => 'sometimes|string|max:50',
                'is_mandatory'  => 'sometimes|boolean',
                'order'         => 'sometimes|integer|min:1',
                'allowed_tags'  => 'sometimes|string_array',
            ];

            // Creates a Validator instance and validates the data.
            $validation = Validator::make($data->all(), $rules);

            if ($validation->fails()) {
                $messages = $validation->messages()->toArray();

                return $this->error412
                (
                    $messages
                );
            }

            $track_tag_group = $this->track_tag_group_service->updateTrackTagGroup($summit, $track_tag_group_id, $data->all());

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($track_tag_group)->serialize(Request::input('expand', '')));
        }
        catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412([$ex1->getMessage()]);
        }
        catch(EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(['message'=> $ex2->getMessage()]);
        }
        catch (\Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    #[OA\Delete(
        path: "/api/v1/summits/{id}/track-tag-groups/{track_tag_group_id}",
        operationId: "deleteTrackTagGroup",
        description: "Delete a track tag group. required-groups " . IGroup::SuperAdmins . ", " . IGroup::Administrators . ", " . IGroup::SummitAdministrators,
        tags: ["Track Tag Groups"],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        security: [['summit_track_tag_groups_oauth2' => [
            SummitScopes::WriteSummitData,
            SummitScopes::WriteTrackTagGroupsData,
        ]]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "string"),
                description: "Summit ID or slug"
            ),
            new OA\Parameter(
                name: "track_tag_group_id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", format: "int64"),
                description: "Track tag group ID"
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_NO_CONTENT,
                description: "Track tag group deleted successfully"
            ),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Track tag group or summit not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    /**
     * @param $summit_id
     * @param $track_tag_group_id
     * @return mixed
     */
    public function deleteTrackTagGroup($summit_id, $track_tag_group_id){
        try {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $this->track_tag_group_service->deleteTrackTagGroup($summit, $track_tag_group_id);

            return $this->deleted();
        }
        catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412([$ex1->getMessage()]);
        }
        catch(EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(['message'=> $ex2->getMessage()]);
        }
        catch (\Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    #[OA\Post(
        path: "/api/v1/summits/{id}/track-tag-groups/seed-defaults",
        operationId: "seedDefaultTrackTagGroups",
        description: "Seed default track tag groups for a summit. required-groups " . IGroup::SuperAdmins . ", " . IGroup::Administrators . ", " . IGroup::SummitAdministrators,
        tags: ["Track Tag Groups"],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        security: [['summit_track_tag_groups_oauth2' => [
            SummitScopes::WriteSummitData,
            SummitScopes::WriteTrackTagGroupsData,
        ]]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "string"),
                description: "Summit ID or slug"
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: "Default track tag groups seeded successfully",
                content: new OA\JsonContent(ref: "#/components/schemas/TrackTagGroupsList")
            ),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Summit not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    /**
     * @param $summit_id
     * @return mixed
     */
    public function seedDefaultTrackTagGroups($summit_id){
        try{
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();
            $track_tag_groups = $this->track_tag_group_service->seedDefaultTrackTagGroups($summit);

            $response = new PagingResponse
            (
                count($track_tag_groups),
                count($track_tag_groups),
                1,
                1,
                $track_tag_groups
            );
            return $this->created($response->toArray());

        }
        catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412([$ex1->getMessage()]);
        }
        catch(EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(['message'=> $ex2->getMessage()]);
        }
        catch (\Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    #[OA\Post(
        path: "/api/v1/summits/{id}/track-tag-groups/all/allowed-tags/{tag_id}/seed-on-tracks",
        operationId: "seedTagOnAllTracks",
        description: "Seed a tag on all tracks in a summit. required-groups " . IGroup::SuperAdmins . ", " . IGroup::Administrators . ", " . IGroup::SummitAdministrators,
        tags: ["Track Tag Groups"],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        security: [['summit_track_tag_groups_oauth2' => [
            SummitScopes::WriteSummitData,
            SummitScopes::WriteTracksData,
        ]]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "string"),
                description: "Summit ID or slug"
            ),
            new OA\Parameter(
                name: "tag_id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", format: "int64"),
                description: "Tag ID"
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Tag seeded successfully on all tracks"
            ),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Summit or tag not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    /**
     * @param $summit_id
     * @param $tag_id
     * @return mixed
     */
    public function seedTagOnAllTracks($summit_id, $tag_id){
        try{
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();
            $this->track_tag_group_service->seedTagOnAllTrack($summit, $tag_id);
            return $this->updated();
        }
        catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412([$ex1->getMessage()]);
        }
        catch(EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(['message'=> $ex2->getMessage()]);
        }
        catch (\Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    #[OA\Post(
        path: "/api/v1/summits/{id}/track-tag-groups/{track_tag_group_id}/allowed-tags/all/copy/tracks/{track_id}",
        operationId: "seedTagTrackGroupOnTrack",
        description: "Seed a track tag group on a specific track. required-groups " . IGroup::SuperAdmins . ", " . IGroup::Administrators . ", " . IGroup::SummitAdministrators,
        tags: ["Track Tag Groups"],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        security: [['summit_track_tag_groups_oauth2' => [
            SummitScopes::WriteSummitData,
            SummitScopes::WriteTracksData,
        ]]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "string"),
                description: "Summit ID or slug"
            ),
            new OA\Parameter(
                name: "track_tag_group_id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", format: "int64"),
                description: "Track tag group ID"
            ),
            new OA\Parameter(
                name: "track_id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", format: "int64"),
                description: "Track ID"
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Track tag group seeded successfully on track"
            ),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Summit, track or track tag group not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    /**
     * @param $summit_id
     * @param $track_tag_group_id
     * @param $track_id
     * @return mixed
     */
    public function seedTagTrackGroupOnTrack($summit_id, $track_tag_group_id, $track_id){
        try{
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();
            $this->track_tag_group_service->seedTagTrackGroupTagsOnTrack($summit, $track_tag_group_id, $track_id);
            return $this->updated();
        }
        catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412([$ex1->getMessage()]);
        }
        catch(EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(['message'=> $ex2->getMessage()]);
        }
        catch (\Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }
}