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

use App\Models\Exceptions\AuthzException;
use App\ModelSerializers\SerializerUtils;
use App\Services\Model\ISummitSelectedPresentationListService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;
use models\main\IMemberRepository;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitRepository;
use models\summit\SummitSelectedPresentation;
use ModelSerializers\SerializerRegistry;
use OpenApi\Attributes as OA;

/**
 * Class OAuth2SummitSelectedPresentationListApiController
 * @package App\Http\Controllers
 */
class OAuth2SummitSelectedPresentationListApiController
    extends OAuth2ProtectedController
{
    use RequestProcessor;
    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * @var IMemberRepository
     */
    private $member_repository;

    /**
     * @var ISummitSelectedPresentationListService
     */
    private $service;

    /**
     * OAuth2SummitSelectedPresentationListApiController constructor.
     * @param ISummitRepository $summit_repository
     * @param IMemberRepository $member_repository
     * @param ISummitSelectedPresentationListService $service
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        ISummitRepository $summit_repository,
        IMemberRepository $member_repository,
        ISummitSelectedPresentationListService $service,
        IResourceServerContext $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->summit_repository = $summit_repository;
        $this->member_repository = $member_repository;
        $this->service = $service;
    }

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @param $track_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    #[OA\Get(
        path: "/api/v1/summits/{id}/selection-plans/{selection_plan_id}/track-chairs/tracks/{track_id}/selection-lists/team",
        summary: "Get team selection list for a track",
        security: [["Bearer" => []]],
        tags: ["summit-selected-presentation-lists"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The summit id"
            ),
            new OA\Parameter(
                name: "selection_plan_id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The selection plan id"
            ),
            new OA\Parameter(
                name: "track_id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The track id"
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "OK",
                content: new OA\JsonContent(ref: "#/components/schemas/SummitSelectedPresentationList")
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function getTeamSelectionList($summit_id, $selection_plan_id, $track_id){
        return $this->processRequest(function () use($summit_id, $selection_plan_id, $track_id){

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $selection_list = $this->service->getTeamSelectionList
            (
                $summit,
                intval($selection_plan_id),
                intval($track_id)
            );

            return $this->ok(SerializerRegistry::getInstance()->getSerializer($selection_list)->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @param $track_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    #[OA\Post(
        path: "/api/v1/summits/{id}/selection-plans/{selection_plan_id}/track-chairs/tracks/{track_id}/selection-lists/team",
        summary: "Create team selection list for a track",
        security: [["Bearer" => []]],
        tags: ["summit-selected-presentation-lists"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The summit id"
            ),
            new OA\Parameter(
                name: "selection_plan_id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The selection plan id"
            ),
            new OA\Parameter(
                name: "track_id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The track id"
            )
        ],
        responses: [
            new OA\Response(
                response: 201,
                description: "Created",
                content: new OA\JsonContent(ref: "#/components/schemas/SummitSelectedPresentationList")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function createTeamSelectionList($summit_id, $selection_plan_id, $track_id){
        return $this->processRequest(function () use($summit_id, $selection_plan_id, $track_id){

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $selection_list = $this->service->createTeamSelectionList($summit, intval($selection_plan_id), intval($track_id));

            return $this->created(SerializerRegistry::getInstance()->getSerializer($selection_list)->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @param $track_id
     * @param $owner_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    #[OA\Get(
        path: "/api/v1/summits/{id}/selection-plans/{selection_plan_id}/track-chairs/tracks/{track_id}/selection-lists/individual/owner/{owner_id}",
        summary: "Get individual selection list for a specific owner",
        security: [["Bearer" => []]],
        tags: ["summit-selected-presentation-lists"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The summit id"
            ),
            new OA\Parameter(
                name: "selection_plan_id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The selection plan id"
            ),
            new OA\Parameter(
                name: "track_id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The track id"
            ),
            new OA\Parameter(
                name: "owner_id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The owner/member id"
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "OK",
                content: new OA\JsonContent(ref: "#/components/schemas/SummitSelectedPresentationList")
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function getIndividualSelectionList($summit_id, $selection_plan_id, $track_id, $owner_id){
        return $this->processRequest(function () use($summit_id, $selection_plan_id, $track_id, $owner_id){

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $selection_list = $this->service->getIndividualSelectionList($summit, intval($selection_plan_id), intval($track_id), intval($owner_id));

            return $this->ok(SerializerRegistry::getInstance()->getSerializer($selection_list)->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @param $track_id
     * @param $owner_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    #[OA\Post(
        path: "/api/v1/summits/{id}/selection-plans/{selection_plan_id}/track-chairs/tracks/{track_id}/selection-lists/individual/owner/me",
        summary: "Create individual selection list for current user",
        security: [["Bearer" => []]],
        tags: ["summit-selected-presentation-lists"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The summit id"
            ),
            new OA\Parameter(
                name: "selection_plan_id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The selection plan id"
            ),
            new OA\Parameter(
                name: "track_id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The track id"
            )
        ],
        responses: [
            new OA\Response(
                response: 201,
                description: "Created",
                content: new OA\JsonContent(ref: "#/components/schemas/SummitSelectedPresentationList")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function createIndividualSelectionList($summit_id, $selection_plan_id, $track_id){
        return $this->processRequest(function () use($summit_id, $selection_plan_id, $track_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $selection_list = $this->service->createIndividualSelectionList($summit, intval($selection_plan_id), intval($track_id), $this->resource_server_context->getCurrentUserId());

            return $this->created(SerializerRegistry::getInstance()->getSerializer($selection_list)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @param $track_id
     * @param $list_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    #[OA\Put(
        path: "/api/v1/summits/{id}/selection-plans/{selection_plan_id}/track-chairs/tracks/{track_id}/selection-lists/{list_id}/reorder",
        summary: "Reorder presentations in a selection list",
        security: [["Bearer" => []]],
        tags: ["summit-selected-presentation-lists"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The summit id"
            ),
            new OA\Parameter(
                name: "selection_plan_id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The selection plan id"
            ),
            new OA\Parameter(
                name: "track_id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The track id"
            ),
            new OA\Parameter(
                name: "list_id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The selection list id"
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(ref: "#/components/schemas/SummitSelectedPresentationListReorderRequest")
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "OK",
                content: new OA\JsonContent(ref: "#/components/schemas/SummitSelectedPresentationList")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function reorderSelectionList($summit_id, $selection_plan_id, $track_id, $list_id){
        return $this->processRequest(function () use($summit_id, $selection_plan_id, $track_id, $list_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $data = Request::json();
            $payload = $data->all();
            // Creates a Validator instance and validates the data.
            $validation = Validator::make($payload,[
                'hash' => 'sometimes|nullable|string',
                'collection' => sprintf('required|string|in:%s,%s', SummitSelectedPresentation::CollectionMaybe, SummitSelectedPresentation::CollectionSelected),
                'presentations' => 'nullable|sometimes|int_array',
            ]);

            if ($validation->fails()) {
                $messages = $validation->messages()->toArray();
                return $this->error412
                (
                    $messages
                );
            }

            $current_member = $this->resource_server_context->getCurrentUser();

            if(is_null($current_member))
                throw new AuthzException("Current Member not found.");

            $selection_list = $this->service->reorderList($current_member, $summit, intval($selection_plan_id), intval($track_id), intval($list_id), $payload);

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($selection_list)->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @param $track_id
     * @param $collection
     * @param $presentation_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    #[OA\Post(
        path: "/api/v1/summits/{id}/selection-plans/{selection_plan_id}/track-chairs/tracks/{track_id}/selection-lists/individual/presentation-selections/{collection}/presentations/{presentation_id}",
        summary: "Assign a presentation to current user's individual selection list",
        security: [["Bearer" => []]],
        tags: ["summit-selected-presentation-lists"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The summit id"
            ),
            new OA\Parameter(
                name: "selection_plan_id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The selection plan id"
            ),
            new OA\Parameter(
                name: "track_id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The track id"
            ),
            new OA\Parameter(
                name: "collection",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "string", enum: ["selected", "maybe"]),
                description: "The collection type (selected or maybe)"
            ),
            new OA\Parameter(
                name: "presentation_id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The presentation id"
            )
        ],
        responses: [
            new OA\Response(
                response: 201,
                description: "Created",
                content: new OA\JsonContent(ref: "#/components/schemas/SummitSelectedPresentationList")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function assignPresentationToMyIndividualList($summit_id, $selection_plan_id, $track_id, $collection, $presentation_id){
        return $this->processRequest(function () use($summit_id, $selection_plan_id, $track_id, $collection,$presentation_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $selection_list = $this->service->assignPresentationToMyIndividualList($summit, intval($selection_plan_id), intval($track_id), trim($collection), intval($presentation_id));

            return $this->created(SerializerRegistry::getInstance()->getSerializer($selection_list)->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @param $track_id
     * @param $collection
     * @param $presentation_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    #[OA\Delete(
        path: "/api/v1/summits/{id}/selection-plans/{selection_plan_id}/track-chairs/tracks/{track_id}/selection-lists/individual/presentation-selections/{collection}/presentations/{presentation_id}",
        summary: "Remove a presentation from current user's individual selection list",
        security: [["Bearer" => []]],
        tags: ["summit-selected-presentation-lists"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The summit id"
            ),
            new OA\Parameter(
                name: "selection_plan_id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The selection plan id"
            ),
            new OA\Parameter(
                name: "track_id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The track id"
            ),
            new OA\Parameter(
                name: "collection",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "string", enum: ["selected", "maybe"]),
                description: "The collection type (selected or maybe)"
            ),
            new OA\Parameter(
                name: "presentation_id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The presentation id"
            )
        ],
        responses: [
            new OA\Response(
                response: 201,
                description: "Created",
                content: new OA\JsonContent(ref: "#/components/schemas/SummitSelectedPresentationList")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function removePresentationFromMyIndividualList($summit_id, $selection_plan_id, $track_id, $collection, $presentation_id){
        return $this->processRequest(function () use($summit_id, $selection_plan_id, $track_id, $collection,$presentation_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $selection_list = $this->service->removePresentationFromMyIndividualList($summit, intval($selection_plan_id), intval($track_id), intval($presentation_id));

            return $this->created(SerializerRegistry::getInstance()->getSerializer($selection_list)->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }
}
