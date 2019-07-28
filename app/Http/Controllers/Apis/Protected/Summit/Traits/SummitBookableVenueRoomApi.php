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

use App\Http\Utils\BooleanCellFormatter;
use App\Http\Utils\EpochCellFormatter;
use App\Http\Utils\PagingConstants;
use Exception;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\summit\SummitBookableVenueRoom;
use models\summit\SummitBookableVenueRoomAvailableSlot;
use models\summit\SummitRoomReservation;
use models\summit\SummitVenue;
use ModelSerializers\SerializerRegistry;
use utils\Filter;
use utils\FilterElement;
use utils\FilterParser;
use utils\OrderParser;
use utils\PagingInfo;
use utils\PagingResponse;
use Illuminate\Http\Request as LaravelRequest;
/**
 * Trait SummitBookableVenueRoomApi
 * @package App\Http\Controllers
 */
trait SummitBookableVenueRoomApi
{

    /**
     * @param $id
     * @return mixed
     */
    public function getReservationById($id){
        try {

            $expand    = Request::input('expand', '');
            $relations = Request::input('relations', '');
            $relations = !empty($relations) ? explode(',', $relations) : [];

            $reservation = $this->reservation_repository->getById($id);

            if (is_null($reservation)) {
                return $this->error404();
            }

            return $this->ok(SerializerRegistry::getInstance()->getSerializer($reservation)->serialize($expand,[], $relations));
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

    /**
     * @param $summit_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getBookableVenueRooms($summit_id){
        $values = Input::all();
        $rules  = [
            'page'     => 'integer|min:1',
            'per_page' => sprintf('required_with:page|integer|min:%s|max:%s', PagingConstants::MinPageSize, PagingConstants::MaxPageSize),
        ];

        try {

            $summit = $summit_id === 'current' ? $this->repository->getCurrent() : $this->repository->getById(intval($summit_id));
            if (is_null($summit)) return $this->error404();

            $validation = Validator::make($values, $rules);

            if ($validation->fails()) {
                $ex = new ValidationException();
                throw $ex->setMessages($validation->messages()->toArray());
            }

            // default values
            $page     = 1;
            $per_page = PagingConstants::DefaultPageSize;

            if (Input::has('page')) {
                $page     = intval(Input::get('page'));
                $per_page = intval(Input::get('per_page'));
            }

            $filter = null;

            if (Input::has('filter')) {
                $filter = FilterParser::parse(Input::get('filter'), [
                    'name'             => ['==', '=@'],
                    'description'      => ['=@'],
                    'capacity'         => ['>', '<', '<=', '>=', '=='],
                    'availability_day' => ['=='],
                    'attribute'         => ['=='],
                ]);
            }
            if(is_null($filter)) $filter = new Filter();

            $filter->validate([
                'name'             => 'sometimes|string',
                'description'      => 'sometimes|string',
                'capacity'         => 'sometimes|integer',
                'availability_day' => 'sometimes|date_format:U',
                'attribute'        => 'sometimes|string',
            ]);

            $order = null;

            if (Input::has('order'))
            {
                $order = OrderParser::parse(Input::get('order'), [
                    'id',
                    'name',
                    'capacity',
                ]);
            }

            $filter->addFilterCondition(FilterParser::buildFilter('class_name','==', SummitBookableVenueRoom::ClassName));

            $data = $this->location_repository->getBySummit($summit, new PagingInfo($page, $per_page), $filter, $order, false);

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
            return $this->error412([$ex1->getMessage()]);
        }
        catch (EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(['message' => $ex2->getMessage()]);
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

    /**
     * @param $summit_id
     * @return mixed
     */
    public function getAllReservationsBySummit($summit_id){
        $values = Input::all();
        $rules  = [

            'page'     => 'integer|min:1',
            'per_page' => sprintf('required_with:page|integer|min:%s|max:%s', PagingConstants::MinPageSize, PagingConstants::MaxPageSize),
        ];

        try {

            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $validation = Validator::make($values, $rules);

            if ($validation->fails()) {
                $ex = new ValidationException();
                throw $ex->setMessages($validation->messages()->toArray());
            }

            // default values
            $page     = 1;
            $per_page = PagingConstants::DefaultPageSize;

            if (Input::has('page')) {
                $page     = intval(Input::get('page'));
                $per_page = intval(Input::get('per_page'));
            }

            $filter = null;

            if (Input::has('filter')) {
                $filter = FilterParser::parse(Input::get('filter'), [
                    'summit_id'      => ['=='],
                    'room_name'      => ['==', '=@'],
                    'room_id'        => ['=='],
                    'owner_id'       => ['=='],
                    'owner_name'     => ['==', '=@'],
                    'owner_email'    => ['==', '=@'],
                    'status'         => ['=='],
                    'start_datetime' => ['>', '<', '<=', '>=', '=='],
                    'end_datetime'   => ['>', '<', '<=', '>=', '=='],
                ]);
            }
            if(is_null($filter)) $filter = new Filter();

            $filter->validate([
                'status'         => sprintf('sometimes|in:%s',implode(',', SummitRoomReservation::$valid_status)),
                'room_name'      => 'sometimes|string',
                'owner_name'     => 'sometimes|string',
                'owner_email'    => 'sometimes|string',
                'summit_id'      => 'sometimes|integer',
                'room_id'        => 'sometimes|integer',
                'owner_id'       => 'sometimes|string',
                'start_datetime' => 'sometimes|required|date_format:U',
                'end_datetime'   => 'sometimes|required_with:start_datetime|date_format:U|after:start_datetime',

            ], [
                'status.in' =>  sprintf
                (
                    ":attribute has an invalid value ( valid values are %s )",
                    implode(", ", SummitRoomReservation::$valid_status)
                )
            ]);

            $order = null;

            if (Input::has('order'))
            {
                $order = OrderParser::parse(Input::get('order'), [
                    'id',
                    'start_datetime',
                    'end_datetime',
                ]);
            }


            $data = $this->reservation_repository->getAllBySummitByPage($summit, new PagingInfo($page, $per_page), $filter, $order);

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

    /**
     * @param $summit_id
     * @return mixed
     */
    public function getAllReservationsBySummitCSV($summit_id){

        try {

            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            // default values
            $page     = 1;
            $per_page = PHP_INT_MAX;

            $filter = null;

            if (Input::has('filter')) {
                $filter = FilterParser::parse(Input::get('filter'), [
                    'summit_id'      => ['=='],
                    'room_name'      => ['==', '=@'],
                    'room_id'        => ['=='],
                    'owner_id'       => ['=='],
                    'owner_name'     => ['==', '=@'],
                    'owner_email'    => ['==', '=@'],
                    'status'         => ['=='],
                    'start_datetime' => ['>', '<', '<=', '>=', '=='],
                    'end_datetime'   => ['>', '<', '<=', '>=', '=='],
                ]);
            }
            if(is_null($filter)) $filter = new Filter();

            $filter->validate([
                'status'         => sprintf('sometimes|in:%s',implode(',', SummitRoomReservation::$valid_status)),
                'room_name'      => 'sometimes|string',
                'owner_name'     => 'sometimes|string',
                'owner_email'    => 'sometimes|string',
                'summit_id'      => 'sometimes|integer',
                'room_id'        => 'sometimes|integer',
                'owner_id'       => 'sometimes|string',
                'start_datetime' => 'sometimes|required|date_format:U',
                'end_datetime'   => 'sometimes|required_with:start_datetime|date_format:U|after:start_datetime',

            ], [
                'status.in' =>  sprintf
                (
                    ":attribute has an invalid value ( valid values are %s )",
                    implode(", ", SummitRoomReservation::$valid_status)
                )
            ]);

            $order = null;

            if (Input::has('order'))
            {
                $order = OrderParser::parse(Input::get('order'), [
                    'id',
                    'start_datetime',
                    'end_datetime',
                ]);
            }


            $data = $this->reservation_repository->getAllBySummitByPage($summit, new PagingInfo($page, $per_page), $filter, $order);

            $filename = "bookable-rooms-reservations-" . date('Ymd');
            $list     =  $data->toArray();
            return $this->export
            (
                'csv',
                $filename,
                $list['data'],
                [
                    'created'                    => new EpochCellFormatter,
                    'last_edited'                => new EpochCellFormatter,
                    'start_datetime'             => new EpochCellFormatter,
                    'end_datetime'               => new EpochCellFormatter,
                ]
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

    /**
     * @param $summit_id
     * @param $venue_id
     * @param $room_id
     * @return mixed
     */
    public function getBookableVenueRoom($summit_id, $venue_id, $room_id){
        try {

            $expand    = Request::input('expand', '');
            $relations = Request::input('relations', '');
            $relations = !empty($relations) ? explode(',', $relations) : [];

            $summit    = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $venue = $summit->getLocation($venue_id);

            if (is_null($venue)) {
                return $this->error404();
            }

            if (!$venue instanceof SummitVenue) {
                return $this->error404();
            }

            $room = $venue->getRoom($room_id);

            if (is_null($room) || !$room instanceof SummitBookableVenueRoom) {
                return $this->error404();
            }

            return $this->ok(SerializerRegistry::getInstance()->getSerializer($room)->serialize($expand,[], $relations));
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

    /**
     * @param $summit_id
     * @param $room_id
     * @param $day
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getBookableVenueRoomAvailability($summit_id, $room_id, $day){
        try {
            $day    = intval($day);
            $summit = $summit_id === 'current' ? $this->repository->getCurrent() : $this->repository->getById(intval($summit_id));
            if (is_null($summit)) return $this->error404();

            $room = $summit->getLocation($room_id);

            if(!$room instanceof SummitBookableVenueRoom)
                return $this->error404();

            $slots_definitions = $room->getAvailableSlots(new \DateTime("@$day"));
            $list = [];
            foreach($slots_definitions as $slot_label => $is_free){
                $dates = explode('|', $slot_label);
                $list[] =
                    SerializerRegistry::getInstance()->getSerializer( new SummitBookableVenueRoomAvailableSlot
                    (
                        $room,
                        $summit->convertDateFromTimeZone2UTC(new \DateTime($dates[0], $summit->getTimeZone())),
                        $summit->convertDateFromTimeZone2UTC(new \DateTime($dates[1], $summit->getTimeZone())),
                        $is_free
                    ))->serialize();
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
                $response->toArray()
            );
        }
        catch (ValidationException $ex1)
        {
            Log::warning($ex1);
            return $this->error412([$ex1->getMessage()]);
        }
        catch (EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(['message' => $ex2->getMessage()]);
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

    /**
     * @param $summit_id
     * @param $room_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function createBookableVenueRoomReservation($summit_id, $room_id){
        try {
            if(!Request::isJson()) return $this->error400();

            $current_member = $this->resource_server_context->getCurrentUser();

            if (is_null($current_member))
                return $this->error403();

            $summit = $summit_id === 'current' ? $this->repository->getCurrent() : $this->repository->getById(intval($summit_id));
            if (is_null($summit)) return $this->error404();

            $room = $summit->getLocation($room_id);

            if(!$room instanceof SummitBookableVenueRoom)
                return $this->error404();

            $payload = Input::json()->all();
            $payload['owner_id'] = $current_member->getId();
            $rules   = SummitRoomReservationValidationRulesFactory::build($payload);
            // Creates a Validator instance and validates the data.
            $validation = Validator::make($payload, $rules);

            if ($validation->fails()) {
                $messages = $validation->messages()->toArray();
                return $this->error412
                (
                    $messages
                );
            }

            $reservation = $this->location_service->addBookableRoomReservation($summit, $room_id, $payload);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($reservation)->serialize());

        }
        catch (ValidationException $ex1)
        {
            Log::warning($ex1);
            return $this->error412([$ex1->getMessage()]);
        }
        catch (EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(['message' => $ex2->getMessage()]);
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

    /**
     * @param $summit_id
     * @return mixed
     */
    public function getMyBookableVenueRoomReservations($summit_id){
        try{
            $current_member = $this->resource_server_context->getCurrentUser();

            if (is_null($current_member))
                return $this->error403();

            $summit = $summit_id === 'current' ? $this->repository->getCurrent() : $this->repository->getById(intval($summit_id));
            if (is_null($summit)) return $this->error404();


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
                    Request::input('expand', '')
                )
            );
        }
        catch (ValidationException $ex1)
        {
            Log::warning($ex1);
            return $this->error412([$ex1->getMessage()]);
        }
        catch (EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(['message' => $ex2->getMessage()]);
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

    /**
     * @param $summit_id
     * @param $reservation_id
     * @return mixed
     */
    public function cancelMyBookableVenueRoomReservation($summit_id, $reservation_id){
        try{
            $current_member = $this->resource_server_context->getCurrentUser();

            if (is_null($current_member))
                return $this->error403();

            $summit = $summit_id === 'current' ? $this->repository->getCurrent() : $this->repository->getById(intval($summit_id));
            if (is_null($summit)) return $this->error404();

            $reservation = $this->location_service->cancelReservation($summit, $current_member, $reservation_id);

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($reservation)->serialize());
        }
        catch (ValidationException $ex1)
        {
            Log::warning($ex1);
            return $this->error412([$ex1->getMessage()]);
        }
        catch (EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(['message' => $ex2->getMessage()]);
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

    /**
     * @param LaravelRequest $request
     * @return mixed
     */
    public function confirmBookableVenueRoomReservation(LaravelRequest $request){

        if(!Request::isJson())
            return $this->error400();

        try {
            $response = $this->payment_gateway->processCallback($request);
            $this->location_service->processBookableRoomPayment($response);
            return $this->ok();
        }
        catch(EntityNotFoundException $ex){
            Log::warning($ex);
            return $this->error400(["error" => 'payload error']);
        }
        catch (Exception $ex){
            Log::error($ex);
            return $this->error400(["error" => 'payload error']);
        }
        return $this->error400(["error" => 'invalid event type']);
    }

    /**
     * @param $summit_id
     * @param $venue_id
     * @param $room_id
     * @return mixed
     */
    public function updateVenueBookableRoom($summit_id, $venue_id, $room_id){
        try {
            if(!Request::isJson()) return $this->error400();
            $payload = Input::json()->all();
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

            return $this->created(SerializerRegistry::getInstance()->getSerializer($room)->serialize());
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

    public function getVenueFloorBookableRoom($summit_id, $venue_id, $floor_id, $room_id){
        try {

            $expand    = Request::input('expand', '');
            $relations = Request::input('relations', '');
            $relations = !empty($relations) ? explode(',', $relations) : [];

            $summit    = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
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

            if (is_null($room) || !$room instanceof SummitBookableVenueRoom) {
                return $this->error404();
            }

            return $this->ok(SerializerRegistry::getInstance()->getSerializer($room)->serialize($expand,[], $relations));
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

    /**
     * @param $summit_id
     * @param $venue_id
     * @param $floor_id
     * @param $room_id
     * @return mixed
     */
    public function updateVenueFloorBookableRoom($summit_id, $venue_id, $floor_id, $room_id){
        try {
            if(!Request::isJson()) return $this->error400();
            $payload = Input::json()->all();
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

            if(!isset($payload['floor_id']))
                $payload['floor_id'] = intval($floor_id);

            $room = $this->location_service->updateVenueBookableRoom($summit, $venue_id, $room_id, $payload);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($room)->serialize());
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

    public function deleteVenueBookableRoom($summit_id, $venue_id, $room_id){
        try {

            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $this->location_service->deleteVenueBookableRoom($summit, $venue_id, $room_id);

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

    /**
     * @param $summit_id
     * @param $venue_id
     * @return mixed
     */
    public function addVenueBookableRoom($summit_id, $venue_id){
        try {
            if(!Request::isJson()) return $this->error400();
            $payload = Input::json()->all();
            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();
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

            $room = $this->location_service->addVenueBookableRoom($summit, $venue_id, $payload);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($room)->serialize());
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

    /**
     * @param $summit_id
     * @param $venue_id
     * @return mixed
     */
    public function addVenueFloorBookableRoom($summit_id, $venue_id, $floor_id){
        try {
            if(!Request::isJson()) return $this->error400();
            $payload = Input::json()->all();
            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();
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

            $room = $this->location_service->addVenueBookableRoom($summit, $venue_id, $payload);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($room)->serialize());
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

    /**
     * @param $summit_id
     * @param $venue_id
     * @param $room_id
     * @param $attribute_id
     * @return mixed
     */
    public function addVenueBookableRoomAttribute($summit_id, $venue_id, $room_id, $attribute_id){
        try {

            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $room = $this->location_service->addVenueBookableRoomAttribute($summit, $venue_id, $room_id, $attribute_id);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($room)->serialize());
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

    /**
     * @param $summit_id
     * @param $venue_id
     * @param $room_id
     * @param $attribute_id
     * @return mixed
     */
    public function deleteVenueBookableRoomAttribute($summit_id, $venue_id, $room_id, $attribute_id){
        try {
            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $room = $this->location_service->deleteVenueBookableRoomAttribute($summit, $venue_id, $room_id, $attribute_id);

            return $this->deleted(SerializerRegistry::getInstance()->getSerializer($room)->serialize());
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

    public function refundBookableVenueRoomReservation($summit_id, $room_id, $reservation_id){
        try {
            if(!Request::isJson()) return $this->error400();
            $payload = Input::json()->all();
            $rules = [
                'amount' => 'required|integer|greater_than:0',
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

            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $room = $summit->getLocation($room_id);
            if (is_null($room)) return $this->error404();
            if (!$room instanceof SummitBookableVenueRoom) return $this->error404();

            $amount = intval($payload['amount']);
            $reservation = $this->location_service->refundReservation($room, $reservation_id, $amount);

            return $this->updateVenueBookableRoom(SerializerRegistry::getInstance()->getSerializer($reservation)->serialize());
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