<?php namespace App\Http\Controllers;
/*
 * Copyright 2023 OpenStack Foundation
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

use Illuminate\Http\Response;
use OpenApi\Attributes as OA;
use App\Models\Foundation\Summit\Repositories\ISummitProposedScheduleAllowedDayRepository;
use App\Models\Foundation\Summit\Repositories\ISummitProposedScheduleAllowedLocationRepository;
use App\ModelSerializers\SerializerUtils;
use App\Services\Model\ISummitProposedScheduleAllowedLocationService;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitRepository;
use models\summit\PresentationCategory;
use models\summit\Summit;
use ModelSerializers\SerializerRegistry;
use utils\Filter;
use utils\FilterElement;
use utils\PagingInfo;

/**
 * Class OAuth2SummitProposedScheduleAllowedLocationApiController
 * @package App\Http\Controllers
 */
final class OAuth2SummitProposedScheduleAllowedLocationApiController
    extends OAuth2ProtectedController
{

    use GetAndValidateJsonPayload;

    use RequestProcessor;

    use ParametrizedGetAll;

    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * @var ISummitProposedScheduleAllowedDayRepository
     */
    private $allowed_time_frames_repository;

    /**
     * @var ISummitProposedScheduleAllowedLocationService
     */
    private $service;

    /**
     * @param ISummitRepository $summit_repository
     * @param ISummitProposedScheduleAllowedDayRepository $allowed_time_frames_repository
     * @param ISummitProposedScheduleAllowedLocationRepository $repository
     * @param ISummitProposedScheduleAllowedLocationService $service
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        ISummitRepository $summit_repository,
        ISummitProposedScheduleAllowedDayRepository $allowed_time_frames_repository,
        ISummitProposedScheduleAllowedLocationRepository $repository,
        ISummitProposedScheduleAllowedLocationService $service,
        IResourceServerContext $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->allowed_time_frames_repository = $allowed_time_frames_repository;
        $this->summit_repository = $summit_repository;
        $this->service = $service;
        $this->repository = $repository;
    }

    /**
     * @param Summit $summit
     * @param PresentationCategory $track
     * @return bool
     */
    private function isCurrentUserAuth(Summit $summit, PresentationCategory $track):bool{
        $current_member = $this->resource_server_context->getCurrentUser();
        if(is_null($current_member)) return false;
        if($current_member->isAdmin()) return true;
        if($summit->isSummitAdmin($current_member)) return true;
        if($summit->isTrackChair($current_member, $track)) return true;
        return false;
    }

    /**
     * @param $summit_id
     * @param $track_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    #[OA\Get(
        path: "/api/v1/summits/{id}/tracks/{track_id}/proposed-schedule-allowed-locations",
        summary: "Get all allowed locations for a track's proposed schedule",
        security: [["Bearer" => []]],
        tags: ["Summit Proposed Schedule"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The summit id"
            ),
            new OA\Parameter(
                name: "track_id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The track id"
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
                schema: new OA\Schema(type: "integer", default: 10),
                description: "Items per page"
            ),
            new OA\Parameter(
                name: "filter",
                in: "query",
                required: false,
                explode: false,
                schema: new OA\Schema(type: "string"),
                description: "Filter operators: location_id==, track_id=="
            ),
            new OA\Parameter(
                name: "order",
                in: "query",
                required: false,
                explode: false,
                schema: new OA\Schema(type: "string"),
                description: "Order by fields: location_id, track_id"
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "OK",
                content: new OA\JsonContent(ref: "#/components/schemas/PaginateDataSchemaResponse")
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function getAllAllowedLocationByTrack($summit_id, $track_id){

        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find(intval($summit_id));
        if (is_null($summit)) return $this->error404();

        $track = $summit->getPresentationCategory(intval($track_id));
        if(is_null($track) || !$track->isChairVisible()) return $this->error404();

        if(!$this->isCurrentUserAuth($summit, $track))
            return $this->error403();

        return $this->_getAll(
            function () {
                return [
                    'location_id' => ['=='],
                    'track_id' => ['=='],
                ];
            },
            function () {
                return [
                    'location_id' => 'sometimes|integer',
                    'track_id' => 'sometimes|integer',
                ];
            },
            function () {
                return [
                    'location_id',
                    'track_id'
                ];
            },
            function ($filter) use ($track_id) {
                if ($filter instanceof Filter) {
                    $filter->addFilterCondition(FilterElement::makeEqual('track_id', intval($track_id)));
                }
                return $filter;
            },
            function () {
                return SerializerRegistry::SerializerType_Public;
            }
        );
    }

    /**
     * @param $summit_id
     * @param $track_id
     * @return mixed
     */
    #[OA\Post(
        path: "/api/v1/summits/{id}/tracks/{track_id}/proposed-schedule-allowed-locations",
        summary: "Add an allowed location to a track's proposed schedule",
        security: [["Bearer" => []]],
        tags: ["Summit Proposed Schedule"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The summit id"
            ),
            new OA\Parameter(
                name: "track_id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The track id"
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(ref: "#/components/schemas/SummitProposedScheduleAllowedLocationRequest")
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Created",
                content: new OA\JsonContent(ref: "#/components/schemas/SummitProposedScheduleAllowedLocation")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function addAllowedLocationToTrack($summit_id, $track_id){

        return $this->processRequest(function () use ($summit_id, $track_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find(intval($summit_id));
            if (is_null($summit)) return $this->error404();

            $track = $summit->getPresentationCategory(intval($track_id));
            if(is_null($track) || !$track->isChairVisible()) return $this->error404();

            if(!$this->isCurrentUserAuth($summit, $track))
                return $this->error403();

            $payload = $this->getJsonPayload(SummitProposedScheduleAllowedLocationValidationRulesFactory::buildForAdd());

            return $this->created(SerializerRegistry::getInstance()->getSerializer($this->service->addProposedLocationToTrack($track, $payload))
                ->serialize
                (
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                ));
        });
    }

    /**
     * @param $summit_id
     * @param $track_id
     * @param $location_id
     * @return mixed
     */
    #[OA\Get(
        path: "/api/v1/summits/{id}/tracks/{track_id}/proposed-schedule-allowed-locations/{location_id}",
        summary: "Get a specific allowed location from a track's proposed schedule",
        security: [["Bearer" => []]],
        tags: ["Summit Proposed Schedule"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The summit id"
            ),
            new OA\Parameter(
                name: "track_id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The track id"
            ),
            new OA\Parameter(
                name: "location_id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The allowed location id"
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "OK",
                content: new OA\JsonContent(ref: "#/components/schemas/SummitProposedScheduleAllowedLocation")
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function getAllowedLocationFromTrack($summit_id, $track_id, $location_id){
        return $this->processRequest(function () use ($summit_id, $track_id, $location_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find(intval($summit_id));
            if (is_null($summit)) return $this->error404();

            $track = $summit->getPresentationCategory(intval($track_id));
            if(is_null($track) || !$track->isChairVisible()) return $this->error404();

            if(!$this->isCurrentUserAuth($summit, $track))
                return $this->error403();

            $allowed_location = $track->getAllowedLocationById(intval($location_id));

            if(is_null($allowed_location)) return $this->error404();

            return $this->ok(SerializerRegistry::getInstance()->getSerializer($allowed_location)
                ->serialize
                (
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                ));
        });
    }

    #[OA\Delete(
        path: "/api/v1/summits/{id}/tracks/{track_id}/proposed-schedule-allowed-locations/{location_id}",
        summary: "Remove an allowed location from a track's proposed schedule",
        security: [["Bearer" => []]],
        tags: ["Summit Proposed Schedule"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The summit id"
            ),
            new OA\Parameter(
                name: "track_id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The track id"
            ),
            new OA\Parameter(
                name: "location_id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The allowed location id"
            )
        ],
        responses: [
            new OA\Response(response: 204, description: "No Content"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function removeAllowedLocationFromTrack($summit_id, $track_id, $location_id){
        return $this->processRequest(function () use ($summit_id, $track_id, $location_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find(intval($summit_id));
            if (is_null($summit)) return $this->error404();

            $track = $summit->getPresentationCategory(intval($track_id));
            if(is_null($track) || !$track->isChairVisible()) return $this->error404();

            if(!$this->isCurrentUserAuth($summit, $track))
                return $this->error403();

            $this->service->deleteProposedLocationFromTrack($track, intval($location_id));

            return $this->deleted();
        });
    }

    /**
     * @param $summit_id
     * @param $track_id
     * @return mixed
     */
    #[OA\Delete(
        path: "/api/v1/summits/{id}/tracks/{track_id}/proposed-schedule-allowed-locations/all",
        summary: "Remove all allowed locations from a track's proposed schedule",
        security: [["Bearer" => []]],
        tags: ["Summit Proposed Schedule"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The summit id"
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
            new OA\Response(response: 204, description: "No Content"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function removeAllAllowedLocationFromTrack($summit_id, $track_id){
        return $this->processRequest(function () use ($summit_id, $track_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find(intval($summit_id));
            if (is_null($summit)) return $this->error404();

            $track = $summit->getPresentationCategory(intval($track_id));
            if(is_null($track) || !$track->isChairVisible()) return $this->error404();

            if(!$this->isCurrentUserAuth($summit, $track))
                return $this->error403();

            $this->service->deleteAllProposedLocationFromTrack($track);

            return $this->deleted();
        });
    }

    /**
     * @param $summit_id
     * @param $track_id
     * @param $location_id
     * @return mixed
     */
    #[OA\Post(
        path: "/api/v1/summits/{id}/tracks/{track_id}/proposed-schedule-allowed-locations/{location_id}/allowed-time-frames",
        summary: "Add a time frame to an allowed location",
        security: [["Bearer" => []]],
        tags: ["Summit Proposed Schedule"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The summit id"
            ),
            new OA\Parameter(
                name: "track_id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The track id"
            ),
            new OA\Parameter(
                name: "location_id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The allowed location id"
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(ref: "#/components/schemas/SummitProposedScheduleAllowedDayAddRequest")
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Created",
                content: new OA\JsonContent(ref: "#/components/schemas/SummitProposedScheduleAllowedDay")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function addTimeFrame2AllowedLocation($summit_id, $track_id, $location_id){
        return $this->processRequest(function () use ($summit_id, $track_id, $location_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find(intval($summit_id));
            if (is_null($summit)) return $this->error404();

            $track = $summit->getPresentationCategory(intval($track_id));
            if(is_null($track) || !$track->isChairVisible()) return $this->error404();

            if(!$this->isCurrentUserAuth($summit, $track))
                return $this->error403();

            $payload = $this->getJsonPayload(SummitProposedScheduleAllowedDayValidationRulesFactory::buildForAdd());

            return $this->created(SerializerRegistry::getInstance()->getSerializer($this->service->addAllowedDayToProposedLocation($track, intval($location_id), $payload))
                ->serialize
                (
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                ));
        });
    }

    /**
     * @param $summit_id
     * @param $track_id
     * @param $location_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    #[OA\Get(
        path: "/api/v1/summits/{id}/tracks/{track_id}/proposed-schedule-allowed-locations/{location_id}/allowed-time-frames",
        summary: "Get all time frames for an allowed location",
        security: [["Bearer" => []]],
        tags: ["Summit Proposed Schedule"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The summit id"
            ),
            new OA\Parameter(
                name: "track_id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The track id"
            ),
            new OA\Parameter(
                name: "location_id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The allowed location id"
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
                schema: new OA\Schema(type: "integer", default: 10),
                description: "Items per page"
            ),
            new OA\Parameter(
                name: "filter",
                in: "query",
                required: false,
                explode: false,
                schema: new OA\Schema(type: "string"),
                description: "Filter operators: allowed_location_id==, track_id==, location_id==, day</>/<=/>=/ ==, opening_hour</>/<=/>=/ ==, closing_hour</>/<=/>=/=="
            ),
            new OA\Parameter(
                name: "order",
                in: "query",
                required: false,
                explode: false,
                schema: new OA\Schema(type: "string"),
                description: "Order by fields: id, day, opening_hour, closing_hour, location_id, allowed_location_id, track_id"
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "OK",
                content: new OA\JsonContent(ref: "#/components/schemas/PaginateDataSchemaResponse")
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function getAllTimeFrameFromAllowedLocation($summit_id, $track_id, $location_id){

        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find(intval($summit_id));
        if (is_null($summit)) return $this->error404();

        $track = $summit->getPresentationCategory(intval($track_id));
        if(is_null($track) || !$track->isChairVisible()) return $this->error404();

        if(!$this->isCurrentUserAuth($summit, $track))
            return $this->error403();

        $allowed_location = $track->getAllowedLocationById(intval($location_id));
        if(is_null($allowed_location)) return $this->error404();

        return $this->_getAll(
            function () {
                return [
                    'allowed_location_id' => ['=='],
                    'track_id' => ['=='],
                    'location_id' =>  ['=='],
                    'day' => ['<','>','==','>=','<='],
                    'opening_hour' => ['<','>','==','>=','<='],
                    'closing_hour' => ['<','>','==','>=','<='],
                ];
            },
            function () {
                return [
                    'allowed_location_id' => 'sometimes|integer',
                    'track_id' => 'sometimes|integer',
                    'location_id' =>  'sometimes|integer',
                    'day' => 'sometimes|integer',
                    'opening_hour' => 'sometimes|integer',
                    'closing_hour' => 'sometimes|integer',
                ];
            },
            function () {
                return [
                    'id',
                    'day',
                    'opening_hour',
                    'closing_hour',
                    'location_id',
                    'allowed_location_id',
                    'track_id',
                ];
            },
            function ($filter) use ($allowed_location) {
                if ($filter instanceof Filter) {
                    $filter->addFilterCondition
                    (
                        FilterElement::makeEqual('allowed_location_id', $allowed_location->getId())
                    );
                }
                return $filter;
            },
            function () {
                return SerializerRegistry::SerializerType_Public;
            },
            null,
            null,
            function ($page, $per_page, $filter, $order, $applyExtraFilters) {
                return $this->allowed_time_frames_repository->getAllByPage
                (
                    new PagingInfo($page, $per_page),
                    call_user_func($applyExtraFilters, $filter),
                    $order
                );
            }
        );
    }

    /**
     * @param $summit_id
     * @param $track_id
     * @param $location_id
     * @param $time_frame_id
     * @return mixed
     */
    #[OA\Get(
        path: "/api/v1/summits/{id}/tracks/{track_id}/proposed-schedule-allowed-locations/{location_id}/allowed-time-frames/{time_frame_id}",
        summary: "Get a specific time frame from an allowed location",
        security: [["Bearer" => []]],
        tags: ["Summit Proposed Schedule"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The summit id"
            ),
            new OA\Parameter(
                name: "track_id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The track id"
            ),
            new OA\Parameter(
                name: "location_id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The allowed location id"
            ),
            new OA\Parameter(
                name: "time_frame_id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The time frame id"
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "OK",
                content: new OA\JsonContent(ref: "#/components/schemas/SummitProposedScheduleAllowedDay")
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function getTimeFrameFromAllowedLocation($summit_id, $track_id, $location_id, $time_frame_id){
        return $this->processRequest(function() use($summit_id, $track_id, $location_id, $time_frame_id){
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find(intval($summit_id));
            if (is_null($summit)) return $this->error404();

            $track = $summit->getPresentationCategory(intval($track_id));
            if(is_null($track) || !$track->isChairVisible()) return $this->error404();

            if(!$this->isCurrentUserAuth($summit, $track))
                return $this->error403();

            $allowed_location = $track->getAllowedLocationById(intval($location_id));

            if(is_null($allowed_location)) return $this->error404();

            $time_frame = $allowed_location->getAllowedTimeFrameById(intval($time_frame_id));
            if(is_null($time_frame)) return $this->error404();

            return $this->ok(SerializerRegistry::getInstance()->getSerializer($time_frame)
                ->serialize
                (
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                ));
        });
    }

    /**
     * @param $summit_id
     * @param $track_id
     * @param $location_id
     * @param $time_frame_id
     * @return mixed
     */
    #[OA\Delete(
        path: "/api/v1/summits/{id}/tracks/{track_id}/proposed-schedule-allowed-locations/{location_id}/allowed-time-frames/{time_frame_id}",
        summary: "Remove a time frame from an allowed location",
        security: [["Bearer" => []]],
        tags: ["Summit Proposed Schedule"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The summit id"
            ),
            new OA\Parameter(
                name: "track_id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The track id"
            ),
            new OA\Parameter(
                name: "location_id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The allowed location id"
            ),
            new OA\Parameter(
                name: "time_frame_id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The time frame id"
            )
        ],
        responses: [
            new OA\Response(response: 204, description: "No Content"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function removeTimeFrameFromAllowedLocation($summit_id, $track_id, $location_id, $time_frame_id){
        return $this->processRequest(function() use($summit_id, $track_id, $location_id, $time_frame_id){
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find(intval($summit_id));
            if (is_null($summit)) return $this->error404();

            $track = $summit->getPresentationCategory(intval($track_id));
            if(is_null($track) || !$track->isChairVisible()) return $this->error404();

            if(!$this->isCurrentUserAuth($summit, $track))
                return $this->error403();

            $this->service->deleteAllowedDayToProposedLocation($track, intval($location_id), intval($time_frame_id));

            return $this->deleted();
        });
    }

    /**
     * @param $summit_id
     * @param $track_id
     * @param $location_id
     * @return mixed
     */
    #[OA\Delete(
        path: "/api/v1/summits/{id}/tracks/{track_id}/proposed-schedule-allowed-locations/{location_id}/allowed-time-frames/all",
        summary: "Remove all time frames from an allowed location",
        security: [["Bearer" => []]],
        tags: ["Summit Proposed Schedule"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The summit id"
            ),
            new OA\Parameter(
                name: "track_id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The track id"
            ),
            new OA\Parameter(
                name: "location_id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The allowed location id"
            )
        ],
        responses: [
            new OA\Response(response: 204, description: "No Content"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function removeAllTimeFrameFromAllowedLocation($summit_id, $track_id, $location_id){
        return $this->processRequest(function() use($summit_id, $track_id, $location_id){
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find(intval($summit_id));
            if (is_null($summit)) return $this->error404();

            $track = $summit->getPresentationCategory(intval($track_id));
            if(is_null($track) || !$track->isChairVisible()) return $this->error404();

            if(!$this->isCurrentUserAuth($summit, $track))
                return $this->error403();

            $this->service->deleteAllAllowedDayToProposedLocation($track, intval($location_id));

            return $this->deleted();
        });
    }

    /**
     * @param $summit_id
     * @param $track_id
     * @param $location_id
     * @param $time_frame_id
     * @return mixed
     */
    #[OA\Put(
        path: "/api/v1/summits/{id}/tracks/{track_id}/proposed-schedule-allowed-locations/{location_id}/allowed-time-frames/{time_frame_id}",
        summary: "Update a time frame for an allowed location",
        security: [["Bearer" => []]],
        tags: ["Summit Proposed Schedule"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The summit id"
            ),
            new OA\Parameter(
                name: "track_id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The track id"
            ),
            new OA\Parameter(
                name: "location_id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The allowed location id"
            ),
            new OA\Parameter(
                name: "time_frame_id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The time frame id"
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(ref: "#/components/schemas/SummitProposedScheduleAllowedDayUpdateRequest")
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "OK",
                content: new OA\JsonContent(ref: "#/components/schemas/SummitProposedScheduleAllowedDay")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function updateTimeFrameFromAllowedLocation($summit_id, $track_id, $location_id, $time_frame_id){
        return $this->processRequest(function () use ($summit_id, $track_id, $location_id, $time_frame_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find(intval($summit_id));
            if (is_null($summit)) return $this->error404();

            $track = $summit->getPresentationCategory(intval($track_id));
            if(is_null($track) || !$track->isChairVisible()) return $this->error404();

            if(!$this->isCurrentUserAuth($summit, $track))
                return $this->error403();

            $payload = $this->getJsonPayload(SummitProposedScheduleAllowedDayValidationRulesFactory::buildForUpdate());

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($this->service->updateAllowedDayToProposedLocation($track, intval($location_id), intval($time_frame_id), $payload))
                ->serialize
                (
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                ));
        });
    }
}
