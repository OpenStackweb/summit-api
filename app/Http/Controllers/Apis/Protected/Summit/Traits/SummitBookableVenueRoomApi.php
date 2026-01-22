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

use App\Http\Utils\EpochCellFormatter;
use App\Models\Foundation\Main\IGroup;
use App\ModelSerializers\SerializerUtils;
use App\Security\SummitScopes;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;
use models\exceptions\ValidationException;
use models\main\Member;
use models\summit\Summit;
use models\summit\SummitBookableVenueRoom;
use models\summit\SummitBookableVenueRoomAvailableSlot;
use models\summit\SummitRoomReservation;
use models\summit\SummitVenue;
use ModelSerializers\SerializerRegistry;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;
use utils\Filter;
use utils\FilterParser;
use utils\PagingInfo;
use utils\PagingResponse;

/**
 * Trait SummitBookableVenueRoomApi
 * @package App\Http\Controllers
 */
trait SummitBookableVenueRoomApi
{

    use RequestProcessor;

    use GetAndValidateJsonPayload;

    use ParametrizedGetAll;

    /**
     * @param $id
     * @return mixed
     */
    #[OA\Get(
        path: '/api/v1/summits/all/locations/bookable-rooms/all/reservations/{id}',
        operationId: 'getReservationById',
        summary: 'Get a reservation by ID',
        tags: ['Summit Bookable Rooms'],
        security: [
            [
                'locations_oauth2' => [
                    SummitScopes::ReadBookableRoomsData,
                    SummitScopes::ReadAllSummitData,
                ]
            ]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
                IGroup::SummitRoomAdministrators,
            ]
        ],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Reservation ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'expand', in: 'query', required: false, description: 'Expand related entities', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'fields', in: 'query', required: false, description: 'Fields to return', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'relations', in: 'query', required: false, description: 'Relations to include', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/SummitRoomReservation')),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Reservation not found'),
        ]
    )]
    public function getReservationById($id)
    {
        return $this->processRequest(function () use ($id) {
            $reservation = $this->reservation_repository->getById(intval($id));

            if (is_null($reservation)) {
                return $this->error404();
            }

            return $this->ok(SerializerRegistry::getInstance()->getSerializer($reservation)->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    /**
     * @param $summit_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    #[OA\Get(
        path: '/api/v1/summits/{id}/locations/bookable-rooms',
        operationId: 'getBookableVenueRooms',
        summary: 'Get all bookable venue rooms for a summit',
        tags: ['Summit Bookable Rooms'],
        security: [
            [
                'locations_oauth2' => [
                    SummitScopes::ReadBookableRoomsData,
                    SummitScopes::ReadAllSummitData,
                ]
            ]
        ],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'filter', in: 'query', required: false, description: 'Filter by: name, description, capacity, availability_day, attribute', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'order', in: 'query', required: false, description: 'Order by: id, name, capacity', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'page', in: 'query', required: false, description: 'Page number', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'Items per page', schema: new OA\Schema(type: 'integer', default: 10)),
            new OA\Parameter(name: 'expand', in: 'query', required: false, description: 'Expand related entities', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'fields', in: 'query', required: false, description: 'Fields to return', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'relations', in: 'query', required: false, description: 'Relations to include', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/SummitBookableVenueRoomPaginatedResponse')),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit not found'),
        ]
    )]
    #[OA\Get(
        path: '/api/v1/summits/{id}/locations/venues/all/bookable-rooms',
        operationId: 'getBookableVenueAllRooms',
        summary: 'Get all bookable venue rooms for a summit',
        tags: ['Summit Bookable Rooms'],
        security: [
            [
                'locations_oauth2' => [
                    SummitScopes::ReadSummitData,
                    SummitScopes::ReadAllSummitData,
                ]
            ]
        ],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'filter', in: 'query', required: false, description: 'Filter by: name, description, capacity, availability_day, attribute', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'order', in: 'query', required: false, description: 'Order by: id, name, capacity', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'page', in: 'query', required: false, description: 'Page number', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'Items per page', schema: new OA\Schema(type: 'integer', default: 10)),
            new OA\Parameter(name: 'expand', in: 'query', required: false, description: 'Expand related entities', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'fields', in: 'query', required: false, description: 'Fields to return', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'relations', in: 'query', required: false, description: 'Relations to include', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/SummitBookableVenueRoomPaginatedResponse')),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit not found'),
        ]
    )]
    public function getBookableVenueRooms($summit_id)
    {

        $summit = SummitFinderStrategyFactory::build($this->repository, $this->getResourceServerContext())->find($summit_id);
        if (is_null($summit)) return $this->error404();

        return $this->_getAll(
            function () {
                return [
                    'name' => ['==', '=@'],
                    'description' => ['=@'],
                    'capacity' => ['>', '<', '<=', '>=', '=='],
                    'availability_day' => ['=='],
                    'attribute' => ['=='],
                ];
            },
            function () {
                return [
                    'name' => 'sometimes|string',
                    'description' => 'sometimes|string',
                    'capacity' => 'sometimes|integer',
                    'availability_day' => 'sometimes|date_format:U|epoch_seconds',
                    'attribute' => 'sometimes|string',
                ];
            },
            function () {
                return [
                    'id',
                    'name',
                    'capacity',
                ];
            },
            function ($filter) use ($summit) {
                if ($filter instanceof Filter) {
                    $filter->addFilterCondition(FilterParser::buildFilter('class_name', '==', SummitBookableVenueRoom::ClassName));
                }
                return $filter;
            },
            function () {
                return SerializerRegistry::SerializerType_Public;
            },
            null,
            null,
            function ($page, $per_page, $filter, $order, $applyExtraFilters) use ($summit) {

                return $this->location_repository->getBySummit
                (
                    $summit,
                    new PagingInfo($page, $per_page),
                    call_user_func($applyExtraFilters, $filter),
                    $order,
                    false
                );
            }
        );
    }

    /**
     * @param $summit_id
     * @return mixed
     */
    #[OA\Get(
        path: '/api/v1/summits/{id}/locations/bookable-rooms/all/reservations',
        operationId: 'getAllReservationsBySummit',
        summary: 'Get all reservations for a summit',
        tags: ['Summit Bookable Rooms'],
        security: [
            [
                'locations_oauth2' => [
                    SummitScopes::ReadAllSummitData,
                    SummitScopes::ReadSummitData,
                ]
            ]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'filter', in: 'query', required: false, description: 'Filter by: summit_id, room_name, room_id, owner_id, owner_name, owner_email, not_owner_email, status, start_datetime, end_datetime', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'order', in: 'query', required: false, description: 'Order by: id, start_datetime, end_datetime, room_name, room_id, status, created, owner_name, owner_email', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'page', in: 'query', required: false, description: 'Page number', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'Items per page', schema: new OA\Schema(type: 'integer', default: 10)),
            new OA\Parameter(name: 'expand', in: 'query', required: false, description: 'Expand related entities', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'fields', in: 'query', required: false, description: 'Fields to return', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'relations', in: 'query', required: false, description: 'Relations to include', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/SummitRoomReservationPaginatedResponse')),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit not found'),
        ]
    )]
    public function getAllReservationsBySummit($summit_id)
    {

        $summit = SummitFinderStrategyFactory::build($this->repository, $this->getResourceServerContext())->find($summit_id);
        if (is_null($summit)) return $this->error404();

        return $this->_getAll(
            function () {
                return [
                    'summit_id' => ['=='],
                    'room_name' => ['==', '=@'],
                    'room_id' => ['=='],
                    'owner_id' => ['=='],
                    'owner_name' => ['==', '=@'],
                    'owner_email' => ['==', '=@'],
                    'not_owner_email' => ['=@'],
                    'status' => ['=='],
                    'start_datetime' => ['>', '<', '<=', '>=', '=='],
                    'end_datetime' => ['>', '<', '<=', '>=', '=='],
                ];
            },
            function () {
                return [
                    'status' => sprintf('sometimes|in:%s', implode(',', SummitRoomReservation::$valid_status)),
                    'room_name' => 'sometimes|string',
                    'owner_name' => 'sometimes|string',
                    'owner_email' => 'sometimes|string',
                    'not_owner_email' => 'sometimes|string',
                    'summit_id' => 'sometimes|integer',
                    'room_id' => 'sometimes|integer',
                    'owner_id' => 'sometimes|string',
                    'start_datetime' => 'sometimes|required|date_format:U|epoch_seconds',
                    'end_datetime' => 'sometimes|required_with:start_datetime|date_format:U|epoch_seconds|after:start_datetime',
                ];
            },
            function () {
                return [
                    'id',
                    'start_datetime',
                    'end_datetime',
                    'room_name',
                    'room_id',
                    'status',
                    'created',
                    'owner_name',
                    'owner_email',
                ];
            },
            function ($filter) use ($summit) {
                return $filter;
            },
            function () {
                return SerializerRegistry::SerializerType_Public;
            },
            null,
            null,
            function ($page, $per_page, $filter, $order, $applyExtraFilters) use ($summit) {
                return $this->reservation_repository->getAllBySummitByPage
                (
                    $summit,
                    new PagingInfo($page, $per_page),
                    call_user_func($applyExtraFilters, $filter),
                    $order
                );
            }
        );
    }

    /**
     * @param $summit_id
     * @return mixed
     */
    #[OA\Get(
        path: '/api/v1/summits/{id}/locations/bookable-rooms/all/reservations/csv',
        operationId: 'getAllReservationsBySummitCSV',
        summary: 'Export reservations for a summit as CSV',
        tags: ['Summit Bookable Rooms'],
        security: [
            [
                'locations_oauth2' => [
                    SummitScopes::ReadAllSummitData,
                    SummitScopes::ReadSummitData,
                ]
            ]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'filter', in: 'query', required: false, description: 'Filter by: summit_id, room_name, room_id, owner_id, owner_name, owner_email, not_owner_email, status, start_datetime, end_datetime', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'order', in: 'query', required: false, description: 'Order by: id, start_datetime, end_datetime, room_name, room_id, status, created, owner_name, owner_email', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'CSV file', content: new OA\MediaType(mediaType: 'text/csv')),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit not found'),
        ]
    )]
    public function getAllReservationsBySummitCSV($summit_id)
    {

        $summit = SummitFinderStrategyFactory::build($this->repository, $this->getResourceServerContext())->find($summit_id);
        if (is_null($summit)) return $this->error404();

        return $this->_getAllCSV(
            function () {
                return [
                    'summit_id' => ['=='],
                    'room_name' => ['==', '=@'],
                    'room_id' => ['=='],
                    'owner_id' => ['=='],
                    'owner_name' => ['==', '=@'],
                    'owner_email' => ['==', '=@'],
                    'not_owner_email' => [ '=@'],
                    'status' => ['=='],
                    'start_datetime' => ['>', '<', '<=', '>=', '=='],
                    'end_datetime' => ['>', '<', '<=', '>=', '=='],
                ];
            },
            function () {
                return [
                    'status' => sprintf('sometimes|in:%s', implode(',', SummitRoomReservation::$valid_status)),
                    'room_name' => 'sometimes|string',
                    'owner_name' => 'sometimes|string',
                    'owner_email' => 'sometimes|string',
                    'not_owner_email' => 'sometimes|string',
                    'summit_id' => 'sometimes|integer',
                    'room_id' => 'sometimes|integer',
                    'owner_id' => 'sometimes|string',
                    'start_datetime' => 'sometimes|required|date_format:U|epoch_seconds',
                    'end_datetime' => 'sometimes|required_with:start_datetime|date_format:U|epoch_seconds|after:start_datetime',
                ];
            },
            function () {
                return [
                    'id',
                    'start_datetime',
                    'end_datetime',
                    'room_name',
                    'room_id',
                    'status',
                    'created',
                    'owner_name',
                    'owner_email',
                ];
            },
            function ($filter) {
                return $filter;
            },
            function () {
                return SerializerRegistry::SerializerType_CSV;
            },
            function ()  use($summit){
                return [
                    'created' => new EpochCellFormatter(EpochCellFormatter::DefaultFormat, $summit->getTimeZone()),
                    'last_edited' => new EpochCellFormatter(EpochCellFormatter::DefaultFormat, $summit->getTimeZone()),
                    'start_datetime' => new EpochCellFormatter(EpochCellFormatter::DefaultFormat, $summit->getTimeZone()),
                    'end_datetime' => new EpochCellFormatter(EpochCellFormatter::DefaultFormat, $summit->getTimeZone()),
                    'approved_payment_date' => new EpochCellFormatter(EpochCellFormatter::DefaultFormat, $summit->getTimeZone()),
                ];
            },
            function () {
                return [];
            },
            'bookable-rooms-reservations-',
            [],
            function ($page, $per_page, $filter, $order, $applyExtraFilters) use ($summit) {
                return $this->reservation_repository->getAllBySummitByPage
                (
                    $summit,
                    new PagingInfo($page, $per_page),
                    call_user_func($applyExtraFilters, $filter),
                    $order
                );
            }
        );
    }

    /**
     * @param $summit_id
     * @param $venue_id
     * @param $room_id
     * @return mixed
     */
    #[OA\Get(
        path: '/api/v1/summits/{id}/locations/venues/{venue_id}/bookable-rooms/{room_id}',
        operationId: 'getBookableVenueRoomByVenue',
        summary: 'Get a bookable venue room by venue and room ID',
        tags: ['Summit Bookable Rooms'],
        security: [
            [
                'locations_oauth2' => [
                    SummitScopes::ReadBookableRoomsData,
                    SummitScopes::ReadAllSummitData,
                ]
            ]
        ],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'venue_id', in: 'path', required: true, description: 'Venue ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'room_id', in: 'path', required: true, description: 'Room ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'expand', in: 'query', required: false, description: 'Expand related entities', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'fields', in: 'query', required: false, description: 'Fields to return', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'relations', in: 'query', required: false, description: 'Relations to include', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/SummitBookableVenueRoom')),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit, venue, or room not found'),
        ]
    )]
    public function getBookableVenueRoomByVenue($summit_id, $venue_id, $room_id)
    {
        return $this->processRequest(function () use ($summit_id, $venue_id, $room_id) {

            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $venue = $summit->getLocation(intval($venue_id));

            if (!$venue instanceof SummitVenue) {
                return $this->error404();
            }

            $room = $venue->getRoom(intval($room_id));

            if (!$room instanceof SummitBookableVenueRoom) {
                return $this->error404();
            }

            return $this->ok(SerializerRegistry::getInstance()->getSerializer($room)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    /**
     * @param $summit_id
     * @param $venue_id
     * @param $room_id
     * @return mixed
     */
    #[OA\Get(
        path: '/api/v1/summits/{id}/locations/bookable-rooms/{room_id}',
        operationId: 'getBookableVenueRoom',
        summary: 'Get a bookable venue room by ID',
        tags: ['Summit Bookable Rooms'],
        security: [
            [
                'locations_oauth2' => [
                    SummitScopes::ReadBookableRoomsData,
                    SummitScopes::ReadAllSummitData,
                ]
            ]
        ],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'room_id', in: 'path', required: true, description: 'Room ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'expand', in: 'query', required: false, description: 'Expand related entities', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'fields', in: 'query', required: false, description: 'Fields to return', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'relations', in: 'query', required: false, description: 'Relations to include', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/SummitBookableVenueRoom')),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit or room not found'),
        ]
    )]
    public function getBookableVenueRoom($summit_id, $room_id)
    {
        return $this->processRequest(function () use ($summit_id, $room_id) {

            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit))
                return $this->error404();

            $room = $summit->getLocation(intval($room_id));

            if (!$room instanceof SummitBookableVenueRoom) {
                return $this->error404();
            }

            return $this->ok(SerializerRegistry::getInstance()->getSerializer($room)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    /**
     * @param $summit_id
     * @param $room_id
     * @param $day
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    #[OA\Get(
        path: '/api/v1/summits/{id}/locations/bookable-rooms/{room_id}/availability/{day}',
        operationId: 'getBookableVenueRoomAvailability',
        summary: 'Get availability slots for a bookable room on a specific day',
        tags: ['Summit Bookable Rooms'],
        security: [
            [
                'locations_oauth2' => [
                    SummitScopes::ReadBookableRoomsData,
                    SummitScopes::ReadAllSummitData,
                ]
            ]
        ],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'room_id', in: 'path', required: true, description: 'Room ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'day', in: 'path', required: true, description: 'Day (epoch timestamp or Y-m-d format)', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'expand', in: 'query', required: false, description: 'Expand related entities', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'fields', in: 'query', required: false, description: 'Fields to return', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'relations', in: 'query', required: false, description: 'Relations to include', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'OK', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit or room not found'),
        ]
    )]
    public function getBookableVenueRoomAvailability($summit_id, $room_id, $day)
    {
        return $this->processRequest(function () use ($summit_id, $room_id, $day) {

            Log::debug
            (
                sprintf
                (
                    "SummitBookableVenueRoomApi::getBookableVenueRoomAvailability summit_id %s room_id %s day %s",
                    $summit_id,
                    $room_id,
                    $day
                )
            );

            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (!$summit instanceof Summit) return $this->error404();

            $room = $summit->getLocation($room_id);

            if (!$room instanceof SummitBookableVenueRoom)
                return $this->error404();

            try{
                // day could be epoch or YMD format
                $from_day = is_numeric($day) ? new \DateTime("@$day") :
                            // if its a string then we need to be aware of time zone
                             \DateTime::createFromFormat("Y-m-d", $day, $summit->getTimeZone())
                                 ->setTime(0,0,0);
            }
            catch (\Exception $ex){
                throw new ValidationException(sprintf("day %s is not valid", $day));
            }

            $slots_definitions = $room->getAvailableSlots($from_day);

            $list = [];
            foreach ($slots_definitions as $slot_label => $is_free) {
                $dates = explode('|', $slot_label);
                $list[] =
                    SerializerRegistry::getInstance()->getSerializer
                    (
                        new SummitBookableVenueRoomAvailableSlot
                        (
                            $room,
                            $summit->convertDateFromTimeZone2UTC(new \DateTime($dates[0], $summit->getTimeZone())),
                            $summit->convertDateFromTimeZone2UTC(new \DateTime($dates[1], $summit->getTimeZone())),
                            $is_free
                        )
                    )->serialize();
            }

            $response = new PagingResponse
            (
                count($list),
                count($list),
                1,
                1,
                $list
            );

            return $this->ok(
                $response->toArray(
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                )
            );
        });
    }

    /**
     * @param $summit_id
     * @param $room_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    #[OA\Post(
        path: '/api/v1/summits/{id}/locations/bookable-rooms/{room_id}/reservations',
        operationId: 'createBookableVenueRoomReservation',
        summary: 'Create a reservation for a bookable room',
        tags: ['Summit Bookable Rooms'],
        security: [
            [
                'locations_oauth2' => [
                    SummitScopes::WriteMyBookableRoomsReservationData,
                ]
            ]
        ],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'room_id', in: 'path', required: true, description: 'Room ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'expand', in: 'query', required: false, description: 'Expand related entities', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'fields', in: 'query', required: false, description: 'Fields to return', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'relations', in: 'query', required: false, description: 'Relations to include', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_CREATED, description: 'Reservation created', content: new OA\JsonContent(ref: '#/components/schemas/SummitRoomReservation')),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit or room not found'),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Validation error'),
        ]
    )]
    public function createBookableVenueRoomReservation($summit_id, $room_id)
    {
        return $this->processRequest(function () use ($summit_id, $room_id) {

            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (!$summit instanceof Summit) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (!$current_member instanceof Member)
                return $this->error403();

            $payload = $this->getJsonPayload(
                SummitRoomReservationValidationRulesFactory::buildForAdd(),
                true
            );

            $payload['owner_id'] = $current_member->getId();

            $reservation = $this->location_service->addBookableRoomReservation($summit, intval($room_id), $payload);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($reservation)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));

        });
    }

    /**
     * @param $summit_id
     * @param $room_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    #[OA\Post(
        path: '/api/v1/summits/{id}/locations/bookable-rooms/{room_id}/reservations/offline',
        operationId: 'createOfflineBookableVenueRoomReservation',
        summary: 'Create an offline reservation for a bookable room',
        tags: ['Summit Bookable Rooms'],
        security: [
            [
                'locations_oauth2' => [
                    SummitScopes::WriteSummitData,
                    SummitScopes::WriteBookableRoomsData,
                ]
            ]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'room_id', in: 'path', required: true, description: 'Room ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'expand', in: 'query', required: false, description: 'Expand related entities', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'fields', in: 'query', required: false, description: 'Fields to return', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'relations', in: 'query', required: false, description: 'Relations to include', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_CREATED, description: 'Offline reservation created', content: new OA\JsonContent(ref: '#/components/schemas/SummitRoomReservation')),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit or room not found'),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Validation error'),
        ]
    )]
    public function createOfflineBookableVenueRoomReservation($summit_id, $room_id)
    {
        return $this->processRequest(function () use ($summit_id, $room_id) {

            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (!$summit instanceof Summit) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (!$current_member instanceof Member)
                return $this->error403();

            $payload = $this->getJsonPayload(
                SummitRoomReservationValidationRulesFactory::buildForAddOffline(),
                true
            );

            $reservation = $this->location_service->addOfflineBookableRoomReservation($summit, intval($room_id), $payload);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($reservation)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    /**
     * @param $summit_id
     * @param $room_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    #[OA\Put(
        path: '/api/v1/summits/{id}/locations/bookable-rooms/{room_id}/reservations/{reservation_id}',
        operationId: 'updateBookableVenueRoomReservation',
        summary: 'Update a bookable room reservation',
        tags: ['Summit Bookable Rooms'],
        security: [
            [
                'locations_oauth2' => [
                    SummitScopes::WriteSummitData,
                    SummitScopes::WriteBookableRoomsData,
                ]
            ]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'room_id', in: 'path', required: true, description: 'Room ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'reservation_id', in: 'path', required: true, description: 'Reservation ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'expand', in: 'query', required: false, description: 'Expand related entities', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'fields', in: 'query', required: false, description: 'Fields to return', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'relations', in: 'query', required: false, description: 'Relations to include', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Reservation updated', content: new OA\JsonContent(ref: '#/components/schemas/SummitRoomReservation')),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit, room or reservation not found'),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Validation error'),
        ]
    )]
    public function updateBookableVenueRoomReservation($summit_id, $room_id, $reservation_id)
    {
        return $this->processRequest(function () use ($summit_id, $room_id, $reservation_id) {

            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (!$summit instanceof Summit) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (!$current_member instanceof Member)
                return $this->error403();

            $payload = $this->getJsonPayload(
                SummitRoomReservationValidationRulesFactory::buildForUpdate(),
                true
            );

            $reservation = $this->location_service->updateBookableRoomReservation($summit, intval($room_id), intval($reservation_id), $payload);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($reservation)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    /**
     * @param $summit_id
     * @param $room_id
     * @param $reservation_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    #[OA\Get(
        path: '/api/v1/summits/{id}/locations/bookable-rooms/{room_id}/reservations/{reservation_id}',
        operationId: 'getBookableVenueRoomReservation',
        summary: 'Get a bookable room reservation by ID',
        tags: ['Summit Bookable Rooms'],
        security: [
            [
                'locations_oauth2' => [
                    SummitScopes::ReadAllSummitData,
                    SummitScopes::ReadSummitData,
                ]
            ]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'room_id', in: 'path', required: true, description: 'Room ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'reservation_id', in: 'path', required: true, description: 'Reservation ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'expand', in: 'query', required: false, description: 'Expand related entities', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'fields', in: 'query', required: false, description: 'Fields to return', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'relations', in: 'query', required: false, description: 'Relations to include', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/SummitRoomReservation')),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit, room or reservation not found'),
        ]
    )]
    public function getBookableVenueRoomReservation($summit_id, $room_id, $reservation_id)
    {
        return $this->processRequest(function () use ($summit_id, $room_id, $reservation_id) {

            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (!$summit instanceof Summit)
                return $this->error404();

            $room = $summit->getLocation(intval($room_id));
            if (!$room instanceof SummitBookableVenueRoom)
                return $this->error404();

            $reservation = $room->getReservationById(intval($reservation_id));
            if (!$reservation instanceof SummitRoomReservation)
                return $this->error404();

            return $this->ok(SerializerRegistry::getInstance()->getSerializer($reservation)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    /**
     * @param $summit_id
     * @return mixed
     */
    #[OA\Get(
        path: '/api/v1/summits/{id}/locations/bookable-rooms/all/reservations/me',
        operationId: 'getMyBookableVenueRoomReservations',
        summary: 'Get my bookable room reservations for a summit',
        tags: ['Summit Bookable Rooms'],
        security: [
            [
                'locations_oauth2' => [
                    SummitScopes::ReadMyBookableRoomsReservationData,
                ]
            ]
        ],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'expand', in: 'query', required: false, description: 'Expand related entities', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'fields', in: 'query', required: false, description: 'Fields to return', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'relations', in: 'query', required: false, description: 'Relations to include', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/SummitRoomReservationPaginatedResponse')),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit not found'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Not authenticated'),
        ]
    )]
    public function getMyBookableVenueRoomReservations($summit_id)
    {
        return $this->processRequest(function () use ($summit_id) {

            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (!$summit instanceof Summit) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();

            if (is_null($current_member))
                return $this->error403();

            $reservations = $current_member->getReservationsBySummit($summit);

            $response = new PagingResponse
            (
                count($reservations),
                count($reservations),
                1,
                1,
                $reservations
            );

            return $this->ok(
                $response->toArray(
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                )
            );
        });
    }

    /**
     * @param $summit_id
     * @param $reservation_id
     * @return mixed
     */
    #[OA\Delete(
        path: '/api/v1/summits/{id}/locations/bookable-rooms/all/reservations/{reservation_id}',
        operationId: 'cancelMyBookableVenueRoomReservation',
        summary: 'Cancel my bookable room reservation',
        tags: ['Summit Bookable Rooms'],
        security: [
            [
                'locations_oauth2' => [
                    SummitScopes::WriteMyBookableRoomsReservationData,
                ]
            ]
        ],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'reservation_id', in: 'path', required: true, description: 'Reservation ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Reservation cancelled', content: new OA\JsonContent(ref: '#/components/schemas/SummitRoomReservation')),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit or reservation not found'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Not authenticated'),
        ]
    )]
    public function cancelMyBookableVenueRoomReservation($summit_id, $reservation_id)
    {
        return $this->processRequest(function () use ($summit_id, $reservation_id) {

            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (!$summit instanceof Summit)
                return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();

            if (is_null($current_member))
                return $this->error403();

            $reservation = $this->location_service->cancelReservation($summit, $current_member, $reservation_id);

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($reservation)->serialize());
        });
    }

    /**
     * @param $summit_id
     * @param $venue_id
     * @param $room_id
     * @return mixed
     */
    #[OA\Put(
        path: '/api/v1/summits/{id}/locations/venues/{venue_id}/bookable-rooms/{room_id}',
        operationId: 'updateVenueBookableRoom',
        summary: 'Update a bookable venue room',
        tags: ['Summit Bookable Rooms'],
        security: [
            [
                'locations_oauth2' => [
                    SummitScopes::WriteSummitData,
                    SummitScopes::WriteBookableRoomsData,
                ]
            ]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'venue_id', in: 'path', required: true, description: 'Venue ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'room_id', in: 'path', required: true, description: 'Room ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'expand', in: 'query', required: false, description: 'Expand related entities', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'fields', in: 'query', required: false, description: 'Fields to return', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'relations', in: 'query', required: false, description: 'Relations to include', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Room updated', content: new OA\JsonContent(ref: '#/components/schemas/SummitBookableVenueRoom')),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit, venue or room not found'),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Validation error'),
        ]
    )]
    public function updateVenueBookableRoom($summit_id, $venue_id, $room_id)
    {
        return $this->processRequest(function () use ($summit_id, $venue_id, $room_id) {
            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (!$summit instanceof Summit)
                return $this->error404();

            if (!Request::isJson()) return $this->error400();
            $payload = Request::json()->all();
            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();
            $payload['class_name'] = SummitBookableVenueRoom::ClassName;
            $rules = SummitLocationValidationRulesFactory::build($payload, true);
            // Creates a Validator instance and validates the data.
            $validation = Validator::make($payload, $rules);

            if ($validation->fails()) {
                $messages = $validation->messages()->toArray();

                return $this->error412
                (
                    $messages
                );
            }

            $room = $this->location_service->updateVenueBookableRoom($summit, $venue_id, $room_id, $payload);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($room)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    #[OA\Get(
        path: '/api/v1/summits/{id}/locations/venues/{venue_id}/floors/{floor_id}/bookable-rooms/{room_id}',
        operationId: 'getVenueFloorBookableRoom',
        summary: 'Get a bookable venue room by floor',
        tags: ['Summit Bookable Rooms'],
        security: [
            [
                'locations_oauth2' => [
                    SummitScopes::ReadBookableRoomsData,
                    SummitScopes::ReadAllSummitData,
                ]
            ]
        ],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'venue_id', in: 'path', required: true, description: 'Venue ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'floor_id', in: 'path', required: true, description: 'Floor ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'room_id', in: 'path', required: true, description: 'Room ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'expand', in: 'query', required: false, description: 'Expand related entities', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'fields', in: 'query', required: false, description: 'Fields to return', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'relations', in: 'query', required: false, description: 'Relations to include', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/SummitBookableVenueRoom')),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit, venue, floor or room not found'),
        ]
    )]
    public function getVenueFloorBookableRoom($summit_id, $venue_id, $floor_id, $room_id)
    {
        return $this->processRequest(function () use ($summit_id, $venue_id, $floor_id, $room_id) {

            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $venue = $summit->getLocation($venue_id);

            if (is_null($venue)) {
                return $this->error404();
            }

            if (!$venue instanceof SummitVenue) {
                return $this->error404();
            }

            $floor = $venue->getFloor($floor_id);

            if (is_null($floor)) {
                return $this->error404();
            }

            $room = $floor->getRoom($room_id);

            if (!$room instanceof SummitBookableVenueRoom) {
                return $this->error404();
            }

            return $this->ok(SerializerRegistry::getInstance()->getSerializer($room)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    /**
     * @param $summit_id
     * @param $venue_id
     * @param $floor_id
     * @param $room_id
     * @return mixed
     */
    #[OA\Put(
        path: '/api/v1/summits/{id}/locations/venues/{venue_id}/floors/{floor_id}/bookable-rooms/{room_id}',
        operationId: 'updateVenueFloorBookableRoom',
        summary: 'Update a bookable venue room by floor',
        tags: ['Summit Bookable Rooms'],
        security: [
            [
                'locations_oauth2' => [
                    SummitScopes::WriteSummitData,
                    SummitScopes::WriteBookableRoomsData,
                ]
            ]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'venue_id', in: 'path', required: true, description: 'Venue ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'floor_id', in: 'path', required: true, description: 'Floor ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'room_id', in: 'path', required: true, description: 'Room ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'expand', in: 'query', required: false, description: 'Expand related entities', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'fields', in: 'query', required: false, description: 'Fields to return', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'relations', in: 'query', required: false, description: 'Relations to include', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Room updated', content: new OA\JsonContent(ref: '#/components/schemas/SummitBookableVenueRoom')),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit, venue, floor or room not found'),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Validation error'),
        ]
    )]
    public function updateVenueFloorBookableRoom($summit_id, $venue_id, $floor_id, $room_id)
    {
        return $this->processRequest(function () use ($summit_id, $venue_id, $floor_id, $room_id) {

            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            if (!Request::isJson()) return $this->error400();
            $payload = Request::json()->all();

            $payload['class_name'] = SummitBookableVenueRoom::ClassName;
            $rules = SummitLocationValidationRulesFactory::build($payload, true);
            // Creates a Validator instance and validates the data.
            $validation = Validator::make($payload, $rules);

            if ($validation->fails()) {
                $messages = $validation->messages()->toArray();

                return $this->error412
                (
                    $messages
                );
            }

            if (!isset($payload['floor_id']))
                $payload['floor_id'] = intval($floor_id);

            $room = $this->location_service->updateVenueBookableRoom($summit, intval($venue_id), intval($room_id), $payload);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($room)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    #[OA\Delete(
        path: '/api/v1/summits/{id}/locations/venues/{venue_id}/bookable-rooms/{room_id}',
        operationId: 'deleteVenueBookableRoom',
        summary: 'Delete a bookable venue room',
        tags: ['Summit Bookable Rooms'],
        security: [
            [
                'locations_oauth2' => [
                    SummitScopes::WriteSummitData,
                    SummitScopes::WriteBookableRoomsData,
                ]
            ]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'venue_id', in: 'path', required: true, description: 'Venue ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'room_id', in: 'path', required: true, description: 'Room ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_NO_CONTENT, description: 'Room deleted'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit, venue or room not found'),
        ]
    )]
    public function deleteVenueBookableRoom($summit_id, $venue_id, $room_id)
    {
        return $this->processRequest(function () use ($summit_id, $venue_id, $room_id) {

            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $this->location_service->deleteVenueBookableRoom($summit, intval($venue_id), intval($room_id));

            return $this->deleted();
        });
    }

    /**
     * @param $summit_id
     * @param $venue_id
     * @return mixed
     */
    #[OA\Post(
        path: '/api/v1/summits/{id}/locations/venues/{venue_id}/bookable-rooms',
        operationId: 'addVenueBookableRoom',
        summary: 'Add a bookable room to a venue',
        tags: ['Summit Bookable Rooms'],
        security: [
            [
                'locations_oauth2' => [
                    SummitScopes::WriteSummitData,
                    SummitScopes::WriteBookableRoomsData,
                ]
            ]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'venue_id', in: 'path', required: true, description: 'Venue ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'expand', in: 'query', required: false, description: 'Expand related entities', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'fields', in: 'query', required: false, description: 'Fields to return', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'relations', in: 'query', required: false, description: 'Relations to include', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_CREATED, description: 'Room created', content: new OA\JsonContent(ref: '#/components/schemas/SummitBookableVenueRoom')),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit or venue not found'),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Validation error'),
        ]
    )]
    public function addVenueBookableRoom($summit_id, $venue_id)
    {
        return $this->processRequest(function () use ($summit_id, $venue_id) {

            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            if (!Request::isJson()) return $this->error400();
            $payload = Request::json()->all();
            $payload['class_name'] = SummitBookableVenueRoom::ClassName;
            $rules = SummitLocationValidationRulesFactory::build($payload);
            // Creates a Validator instance and validates the data.
            $validation = Validator::make($payload, $rules);

            if ($validation->fails()) {
                $messages = $validation->messages()->toArray();

                return $this->error412
                (
                    $messages
                );
            }

            $room = $this->location_service->addVenueBookableRoom($summit, intval($venue_id), $payload);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($room)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    /**
     * @param $summit_id
     * @param $venue_id
     * @return mixed
     */
    #[OA\Post(
        path: '/api/v1/summits/{id}/locations/venues/{venue_id}/floors/{floor_id}/bookable-rooms',
        operationId: 'addVenueFloorBookableRoom',
        summary: 'Add a bookable room to a venue floor',
        tags: ['Summit Bookable Rooms'],
        security: [
            [
                'locations_oauth2' => [
                    SummitScopes::WriteSummitData,
                    SummitScopes::WriteBookableRoomsData,
                ]
            ]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'venue_id', in: 'path', required: true, description: 'Venue ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'floor_id', in: 'path', required: true, description: 'Floor ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'expand', in: 'query', required: false, description: 'Expand related entities', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'fields', in: 'query', required: false, description: 'Fields to return', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'relations', in: 'query', required: false, description: 'Relations to include', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_CREATED, description: 'Room created', content: new OA\JsonContent(ref: '#/components/schemas/SummitBookableVenueRoom')),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit, venue or floor not found'),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Validation error'),
        ]
    )]
    public function addVenueFloorBookableRoom($summit_id, $venue_id, $floor_id)
    {
        return $this->processRequest(function () use ($summit_id, $venue_id, $floor_id) {

            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            if (!Request::isJson()) return $this->error400();
            $payload = Request::json()->all();
            $payload['class_name'] = SummitBookableVenueRoom::ClassName;
            $rules = SummitLocationValidationRulesFactory::build($payload);
            // Creates a Validator instance and validates the data.
            $validation = Validator::make($payload, $rules);

            if ($validation->fails()) {
                $messages = $validation->messages()->toArray();

                return $this->error412
                (
                    $messages
                );
            }

            $payload['floor_id'] = intval($floor_id);

            $room = $this->location_service->addVenueBookableRoom($summit, intval($venue_id), $payload);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($room)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    /**
     * @param $summit_id
     * @param $venue_id
     * @param $room_id
     * @param $attribute_id
     * @return mixed
     */
    #[OA\Put(
        path: '/api/v1/summits/{id}/locations/venues/{venue_id}/bookable-rooms/{room_id}/attributes/{attribute_id}',
        operationId: 'addVenueBookableRoomAttribute',
        summary: 'Add an attribute to a bookable venue room',
        tags: ['Summit Bookable Rooms'],
        security: [
            [
                'locations_oauth2' => [
                    SummitScopes::WriteSummitData,
                    SummitScopes::WriteBookableRoomsData,
                ]
            ]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'venue_id', in: 'path', required: true, description: 'Venue ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'room_id', in: 'path', required: true, description: 'Room ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'attribute_id', in: 'path', required: true, description: 'Attribute ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'expand', in: 'query', required: false, description: 'Expand related entities', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'fields', in: 'query', required: false, description: 'Fields to return', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'relations', in: 'query', required: false, description: 'Relations to include', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_CREATED, description: 'Attribute added', content: new OA\JsonContent(ref: '#/components/schemas/SummitBookableVenueRoom')),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit, venue, room or attribute not found'),
        ]
    )]
    public function addVenueBookableRoomAttribute($summit_id, $venue_id, $room_id, $attribute_id)
    {
        return $this->processRequest(function () use ($summit_id, $venue_id, $room_id, $attribute_id) {

            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $room = $this->location_service->addVenueBookableRoomAttribute
            (
                $summit,
                intval($venue_id),
                intval($room_id),
                intval($attribute_id)
            );

            return $this->created(SerializerRegistry::getInstance()->getSerializer($room)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    /**
     * @param $summit_id
     * @param $venue_id
     * @param $room_id
     * @param $attribute_id
     * @return mixed
     */
    #[OA\Delete(
        path: '/api/v1/summits/{id}/locations/venues/{venue_id}/bookable-rooms/{room_id}/attributes/{attribute_id}',
        operationId: 'deleteVenueBookableRoomAttribute',
        summary: 'Remove an attribute from a bookable venue room',
        tags: ['Summit Bookable Rooms'],
        security: [
            [
                'locations_oauth2' => [
                    SummitScopes::WriteSummitData,
                    SummitScopes::WriteBookableRoomsData,
                ]
            ]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'venue_id', in: 'path', required: true, description: 'Venue ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'room_id', in: 'path', required: true, description: 'Room ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'attribute_id', in: 'path', required: true, description: 'Attribute ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Attribute removed', content: new OA\JsonContent(ref: '#/components/schemas/SummitBookableVenueRoom')),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit, venue, room or attribute not found'),
        ]
    )]
    public function deleteVenueBookableRoomAttribute($summit_id, $venue_id, $room_id, $attribute_id)
    {
        return $this->processRequest(function () use ($summit_id, $venue_id, $room_id, $attribute_id) {
            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $room = $this->location_service->deleteVenueBookableRoomAttribute($summit,
                intval($venue_id),
                intval($room_id),
                intval($attribute_id)
            );

            return $this->deleted(SerializerRegistry::getInstance()->getSerializer($room)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    #[OA\Delete(
        path: '/api/v1/summits/{id}/locations/bookable-rooms/{room_id}/reservations/{reservation_id}/refund',
        operationId: 'refundBookableVenueRoomReservation',
        summary: 'Refund a bookable room reservation',
        tags: ['Summit Bookable Rooms'],
        security: [
            [
                'locations_oauth2' => [
                    SummitScopes::WriteSummitData,
                    SummitScopes::WriteBookableRoomsData,
                ]
            ]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'room_id', in: 'path', required: true, description: 'Room ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'reservation_id', in: 'path', required: true, description: 'Reservation ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Reservation refunded', content: new OA\JsonContent(ref: '#/components/schemas/SummitRoomReservation')),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit, room or reservation not found'),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Validation error'),
        ]
    )]
    public function refundBookableVenueRoomReservation($summit_id, $room_id, $reservation_id)
    {
        return $this->processRequest(function () use ($summit_id, $room_id, $reservation_id) {

            $payload = $this->getJsonPayload(
                [
                    'amount' => 'required|integer|greater_than:0',
                ],
                true
            );

            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit))
                return $this->error404();

            $room = $summit->getLocation($room_id);
            if (!$room instanceof SummitBookableVenueRoom)
                return $this->error404();

            $amount = intval($payload['amount']);

            $reservation = $this->location_service->refundReservation($room, intval($reservation_id), $amount);

            return SerializerRegistry::getInstance()->getSerializer($reservation)->serialize();
        });
    }

    #[OA\Delete(
        path: '/api/v1/summits/{id}/locations/bookable-rooms/{room_id}/reservations/{reservation_id}/cancel',
        operationId: 'cancelBookableVenueRoomReservation',
        summary: 'Cancel a bookable room reservation',
        tags: ['Summit Bookable Rooms'],
        security: [
            [
                'locations_oauth2' => [
                    SummitScopes::WriteSummitData,
                    SummitScopes::WriteBookableRoomsData,
                ]
            ]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'room_id', in: 'path', required: true, description: 'Room ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'reservation_id', in: 'path', required: true, description: 'Reservation ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_NO_CONTENT, description: 'Reservation cancelled'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit, room or reservation not found'),
        ]
    )]
    public function cancelBookableVenueRoomReservation($summit_id, $room_id, $reservation_id)
    {
        return $this->processRequest(function () use ($summit_id, $room_id, $reservation_id) {

            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit))
                return $this->error404();

            $room = $summit->getLocation($room_id);
            if (!$room instanceof SummitBookableVenueRoom)
                return $this->error404();

            $this->location_service->deleteReservation($summit, $room, intval($reservation_id));

            return $this->deleted();
        });
    }
}
