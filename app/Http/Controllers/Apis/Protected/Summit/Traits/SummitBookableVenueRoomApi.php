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
use ModelSerializers\SerializerRegistry;
use utils\Filter;
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

            $data = $this->location_repository->getBySummit($summit, new PagingInfo($page, $per_page), $filter, $order);

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

    public function getBookableVenueRoom($summit_id, $room_id){

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
                $list[] = new SummitBookableVenueRoomAvailableSlot
                (
                    $room,
                    $summit->convertDateFromTimeZone2UTC(new \DateTime($dates[0], $summit->getTimeZone())),
                    $summit->convertDateFromTimeZone2UTC(new \DateTime($dates[1], $summit->getTimeZone())),
                    $is_free
                );
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

            $current_member_id = $this->resource_server_context->getCurrentUserExternalId();
            if (is_null($current_member_id))
                return $this->error403();

            $summit = $summit_id === 'current' ? $this->repository->getCurrent() : $this->repository->getById(intval($summit_id));
            if (is_null($summit)) return $this->error404();

            $room = $summit->getLocation($room_id);

            if(!$room instanceof SummitBookableVenueRoom)
                return $this->error404();

            $payload = Input::json()->all();
            $payload['owner_id'] = $current_member_id;
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
            $current_member_id = $this->resource_server_context->getCurrentUserExternalId();
            if (is_null($current_member_id))
                return $this->error403();

            $summit = $summit_id === 'current' ? $this->repository->getCurrent() : $this->repository->getById(intval($summit_id));
            if (is_null($summit)) return $this->error404();

            $member = $this->member_repository->getById($current_member_id);
            if(is_null($member))
                return $this->error403();

            $reservations = $member->getReservations()->toArray();

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
            $current_member_id = $this->resource_server_context->getCurrentUserExternalId();
            if (is_null($current_member_id))
                return $this->error403();

            $summit = $summit_id === 'current' ? $this->repository->getCurrent() : $this->repository->getById(intval($summit_id));
            if (is_null($summit)) return $this->error404();

            $member = $this->member_repository->getById($current_member_id);
            if(is_null($member))
                return $this->error403();

            $this->location_service->cancelReservation($summit, $member, $reservation_id);

            return $this->deleted();
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
        catch (Exception $ex){
            Log::error($ex);
            return $this->error400(["error" => 'payload error']);
        }
        return $this->error400(["error" => 'invalid event type']);
    }
}