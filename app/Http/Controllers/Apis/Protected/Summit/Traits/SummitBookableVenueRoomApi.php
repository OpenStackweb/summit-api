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
use App\ModelSerializers\SerializerUtils;
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
    public function getBookableVenueRoom($summit_id, $room_id)
    {
        return $this->processRequest(function () use ($summit_id, $room_id) {

            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

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
     * @return mixed
     */
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