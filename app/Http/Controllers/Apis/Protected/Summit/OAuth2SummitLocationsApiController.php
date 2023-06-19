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

use App\Models\Foundation\Summit\Locations\Banners\SummitLocationBannerConstants;
use App\Models\Foundation\Summit\Locations\SummitLocationConstants;
use App\Models\Foundation\Summit\Repositories\ISummitLocationBannerRepository;
use App\Models\Foundation\Summit\Repositories\ISummitLocationRepository;
use App\Models\Foundation\Summit\Repositories\ISummitRoomReservationRepository;
use App\ModelSerializers\SerializerUtils;
use App\Rules\Boolean;
use App\Services\Model\ILocationService;
use Illuminate\Http\Request as LaravelRequest;
use Illuminate\Support\Facades\Validator;
use libs\utils\HTMLCleaner;
use models\exceptions\ValidationException;
use models\main\IMemberRepository;
use models\oauth2\IResourceServerContext;
use models\summit\IEventFeedbackRepository;
use models\summit\ISpeakerRepository;
use models\summit\ISummitEventRepository;
use models\summit\ISummitRepository;
use models\summit\Summit;
use models\summit\SummitAirport;
use models\summit\SummitExternalLocation;
use models\summit\SummitGeoLocatedLocation;
use models\summit\SummitHotel;
use models\summit\SummitVenue;
use models\summit\SummitVenueRoom;
use ModelSerializers\SerializerRegistry;
use services\model\ISummitService;
use utils\FilterParser;
use utils\PagingInfo;
use utils\PagingResponse;

/**
 * Class OAuth2SummitLocationsApiController
 * @package App\Http\Controllers
 */
final class OAuth2SummitLocationsApiController extends OAuth2ProtectedController
{
    /**
     * @var ISummitService
     */
    private $summit_service;

    /**
     * @var ISpeakerRepository
     */
    private $speaker_repository;

    /**
     * @var ISummitEventRepository
     */
    private $event_repository;

    /**
     * @var IEventFeedbackRepository
     */
    private $event_feedback_repository;

    /**
     * @var ISummitLocationRepository
     */
    private $location_repository;

    /**
     * @var IMemberRepository
     */
    private $member_repository;

    /**
     * @var ILocationService
     */
    private $location_service;

    /**
     * @var ISummitLocationBannerRepository
     */
    private $location_banners_repository;

    /**
     * @var ISummitRoomReservationRepository
     */
    private $reservation_repository;

    /**
     * OAuth2SummitLocationsApiController constructor.
     * @param ISummitRepository $summit_repository
     * @param ISummitEventRepository $event_repository
     * @param ISpeakerRepository $speaker_repository
     * @param IEventFeedbackRepository $event_feedback_repository
     * @param ISummitLocationRepository $location_repository
     * @param ISummitLocationBannerRepository $location_banners_repository
     * @param IMemberRepository $member_repository
     * @param ISummitRoomReservationRepository $reservation_repository
     * @param ISummitService $summit_service
     * @param ILocationService $location_service
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        ISummitRepository                $summit_repository,
        ISummitEventRepository           $event_repository,
        ISpeakerRepository               $speaker_repository,
        IEventFeedbackRepository         $event_feedback_repository,
        ISummitLocationRepository        $location_repository,
        ISummitLocationBannerRepository  $location_banners_repository,
        IMemberRepository                $member_repository,
        ISummitRoomReservationRepository $reservation_repository,
        ISummitService                   $summit_service,
        ILocationService                 $location_service,
        IResourceServerContext           $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->repository = $summit_repository;
        $this->speaker_repository = $speaker_repository;
        $this->event_repository = $event_repository;
        $this->member_repository = $member_repository;
        $this->event_feedback_repository = $event_feedback_repository;
        $this->location_repository = $location_repository;
        $this->location_banners_repository = $location_banners_repository;
        $this->location_service = $location_service;
        $this->summit_service = $summit_service;
        $this->reservation_repository = $reservation_repository;
    }

    use RequestProcessor;

    use ParametrizedGetAll;

    /**
     * @param $summit_id
     * @return mixed
     */
    public function getLocations($summit_id)
    {

        $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
        if (is_null($summit)) return $this->error404();

        return $this->_getAll(
            function () {
                return [
                    'class_name' => ['=='],
                    'name' => ['==', '=@'],
                    'description' => ['=@'],
                    'address_1' => ['=@'],
                    'address_2' => ['=@'],
                    'zip_code' => ['==', '=@'],
                    'city' => ['==', '=@'],
                    'state' => ['==', '=@'],
                    'country' => ['==', '=@'],
                    'sold_out' => ['=='],
                    'is_main' => ['=='],
                ];
            },
            function () {
                return [
                    'class_name' => sprintf('sometimes|in:%s', implode(',', SummitLocationConstants::$valid_first_level_class_names)),
                    'name' => 'sometimes|string',
                    'description' => 'sometimes|string',
                    'address_1' => 'sometimes|string',
                    'address_2' => 'sometimes|string',
                    'zip_code' => 'sometimes|string',
                    'city' => 'sometimes|string',
                    'state' => 'sometimes|string',
                    'country' => 'sometimes|string',
                    'sold_out' => 'sometimes|boolean',
                    'is_main' => 'sometimes|boolean',
                ];
            },
            function () {
                return [
                    'id',
                    'name',
                    'order'
                ];
            },
            function ($filter) {
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
                    $order
                );
            }
        );
    }

    /**
     * @param $summit_id
     * @return mixed
     */
    public function getVenues($summit_id)
    {
        return $this->processRequest(function () use ($summit_id) {

            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            //locations
            $locations = [];

            foreach ($summit->getVenues() as $location) {
                $locations[] = SerializerRegistry::getInstance()->getSerializer($location)->serialize();
            }

            $response = new PagingResponse
            (
                count($locations),
                count($locations),
                1,
                1,
                $locations
            );

            return $this->ok($response->toArray(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations(),
            ));
        });
    }

    /**
     * @param $summit_id
     * @return mixed
     */
    public function getExternalLocations($summit_id)
    {
        return $this->processRequest(function () use ($summit_id) {

            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            //locations
            $locations = [];
            foreach ($summit->getExternalLocations() as $location) {
                $locations[] = SerializerRegistry::getInstance()->getSerializer($location)->serialize();
            }

            $response = new PagingResponse
            (
                count($locations),
                count($locations),
                1,
                1,
                $locations
            );

            return $this->ok($response->toArray(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations(),
            ));

        });
    }

    /**
     * @param $summit_id
     * @return mixed
     */
    public function getHotels($summit_id)
    {
        return $this->processRequest(function () use ($summit_id) {
            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            //locations
            $locations = [];
            foreach ($summit->getHotels() as $location) {
                $locations[] = SerializerRegistry::getInstance()->getSerializer($location)->serialize();
            }

            $response = new PagingResponse
            (
                count($locations),
                count($locations),
                1,
                1,
                $locations
            );

            return $this->ok($response->toArray(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations(),
            ));

        });
    }

    /**
     * @param $summit_id
     * @return mixed
     */
    public function getAirports($summit_id)
    {
        return $this->processRequest(function () use ($summit_id) {

            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            //locations
            $locations = [];
            foreach ($summit->getAirports() as $location) {
                $locations[] = SerializerRegistry::getInstance()->getSerializer($location)->serialize();
            }

            $response = new PagingResponse
            (
                count($locations),
                count($locations),
                1,
                1,
                $locations
            );

            return $this->ok($response->toArray(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations(),
            ));

        });
    }

    /**
     * @param $summit_id
     * @param $location_id
     * @return mixed
     */
    public function getLocation($summit_id, $location_id)
    {
        return $this->processRequest(function () use ($summit_id, $location_id) {

            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $location = $summit->getLocation($location_id);
            if (is_null($location)) {
                return $this->error404();
            }
            return $this->ok(SerializerRegistry::getInstance()->getSerializer($location)->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    /**
     * @param $summit_id
     * @param $location_id
     * @param bool $published
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    private function _getLocationEvents($summit_id, $location_id, $published = true)
    {
        $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
        if (is_null($summit))
            if (is_null($summit)) return $this->error404();

        if (strtolower($location_id) !== "tbd") {
            $location = $summit->getLocation(intval($location_id));
            if (is_null($location)) return $this->error404();
        }

        return $this->_getAll(
            function () {
                return [
                    'title' => ['=@', '=='],
                    'start_date' => ['>', '<', '<=', '>=', '=='],
                    'end_date' => ['>', '<', '<=', '>=', '=='],
                    'speaker' => ['=@', '=='],
                    'tags' => ['=@', '=='],
                    'event_type_id' => ['=='],
                    'track_id' => ['=='],
                    'type_show_always_on_schedule' => ['=='],
                ];
            },
            function () {
                return [
                    'title' => 'sometimes|string',
                    'start_date' => 'sometimes|date_format:U',
                    'end_date' => 'sometimes|date_format:U',
                    'speaker' => 'sometimes|string',
                    'tags' =>'sometimes|string',
                    'event_type_id' =>  'sometimes|integer',
                    'track_id' => 'sometimes|integer',
                    'type_show_always_on_schedule' => ['sometimes', new Boolean],
                ];
            },
            function () {
                return [
                    'title',
                    'start_date',
                    'end_date',
                    'id',
                    'created',
                ];
            },
            function ($filter) use($summit_id, $location_id, $published){
                $filter->addFilterCondition(FilterParser::buildFilter('summit_id', '==', $summit_id));

                if (intval($location_id) > 0)
                    $filter->addFilterCondition(FilterParser::buildFilter('location_id', '==', $location_id));

                if ($published) {
                    $filter->addFilterCondition(FilterParser::buildFilter('published', '==', 1));
                }

                return $filter;
            },
            function () {
                return SerializerRegistry::SerializerType_Public;
            },
            null,
            null,
            function ($page, $per_page, $filter, $order, $applyExtraFilters) use ($location_id) {

                return strtolower($location_id) === "tbd" || intval($location_id) === 0 ?
                    $this->event_repository->getAllByPageLocationTBD
                    (
                        new PagingInfo($page, $per_page), call_user_func($applyExtraFilters, $filter), $order
                    )
                    :
                    $this->event_repository->getAllByPage
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
     * @param $location_id
     * @return mixed
     */
    public function getLocationEvents($summit_id, $location_id)
    {
        return $this->_getLocationEvents($summit_id, $location_id, false);
    }

    /**
     * @param $summit_id
     * @param $location_id
     * @return mixed
     */
    public function getLocationPublishedEvents($summit_id, $location_id)
    {
        return $this->_getLocationEvents($summit_id, $location_id, true);
    }

    /**
     * @param $summit_id
     * @return mixed
     */
    public function getMetadata($summit_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
        if (is_null($summit)) return $this->error404();

        return $this->ok
        (
            $this->location_repository->getMetadata($summit)
        );
    }

    /**
     * @param $summit_id
     * @param $venue_id
     * @param $floor_id
     * @return mixed
     */
    public function getVenueFloor($summit_id, $venue_id, $floor_id)
    {
       return $this->processRequest(function() use($summit_id, $venue_id, $floor_id){

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

            return $this->ok(SerializerRegistry::getInstance()->getSerializer($floor)->serialize
            (
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
    public function getVenueRoom($summit_id, $venue_id, $room_id)
    {
        return $this->processRequest(function() use($summit_id, $venue_id, $room_id){

            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $venue = $summit->getLocation($venue_id);

            if (is_null($venue)) {
                return $this->error404();
            }

            if (!$venue instanceof SummitVenue) {
                return $this->error404();
            }

            $room = $venue->getRoom($room_id);

            if (is_null($room)) {
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
    public function getVenueFloorRoom($summit_id, $venue_id, $floor_id, $room_id)
    {
        return $this->processRequest(function() use($summit_id, $venue_id, $floor_id , $room_id){

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

            if (is_null($room)) {
                return $this->error404();
            }

            return $this->ok(SerializerRegistry::getInstance()->getSerializer($room)->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    use GetAndValidateJsonPayload;
    /***
     * Add Locations Endpoints
     */

    /**
     * @param $summit_id
     * @return mixed
     */
    public function addLocation($summit_id)
    {
        return $this->processRequest(function() use($summit_id){

            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonPayload( SummitLocationValidationRulesFactory::build($this->getJsonData()), true);

            $location = $this->location_service->addLocation($summit, $payload);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($location)->serialize(
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
    public function addVenue($summit_id)
    {
        return $this->processRequest(function() use($summit_id){

            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonData();
            $payload['class_name'] = SummitVenue::ClassName;
            $payload = $this->getJsonPayload( SummitLocationValidationRulesFactory::build($payload), true);

            $location = $this->location_service->addLocation($summit, $payload);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($location)->serialize(
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
    public function addExternalLocation($summit_id)
    {
        return $this->processRequest(function() use($summit_id){

            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonData();
            $payload['class_name'] = SummitExternalLocation::ClassName;
            $payload = $this->getJsonPayload( SummitLocationValidationRulesFactory::build($payload), true);

            $location = $this->location_service->addLocation($summit, $payload);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($location)->serialize(
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
    public function addHotel($summit_id)
    {
        return $this->processRequest(function() use($summit_id){

            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonData();
            $payload['class_name'] = SummitHotel::ClassName;
            $payload = $this->getJsonPayload( SummitLocationValidationRulesFactory::build($payload), true);

            $location = $this->location_service->addLocation($summit, $payload);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($location)->serialize(
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
    public function addAirport($summit_id)
    {
        return $this->processRequest(function() use($summit_id){

            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonData();
            $payload['class_name'] = SummitAirport::ClassName;
            $payload = $this->getJsonPayload( SummitLocationValidationRulesFactory::build($payload), true);

            $location = $this->location_service->addLocation($summit, $payload);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($location)->serialize(
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
    public function addVenueFloor($summit_id, $venue_id)
    {
        return $this->processRequest(function() use($summit_id, $venue_id){

            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();
            $payload = $this->getJsonPayload([
                'name' => 'required|string|max:50',
                'number' => 'required|integer',
                'description' => 'sometimes|string',
            ], true);

            $floor = $this->location_service->addVenueFloor($summit, $venue_id, $payload);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($floor)->serialize(
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
    public function addVenueRoom($summit_id, $venue_id)
    {
        return $this->processRequest(function() use($summit_id, $venue_id){
            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonData();
            $payload['class_name'] = SummitVenueRoom::ClassName;
            $payload = $this->getJsonPayload( SummitLocationValidationRulesFactory::build($payload), true);

            $room = $this->location_service->addVenueRoom($summit, $venue_id, $payload);

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
    public function addVenueFloorRoom($summit_id, $venue_id, $floor_id)
    {
        return $this->processRequest(function() use($summit_id, $venue_id, $floor_id){

            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonData();
            $payload['class_name'] = SummitVenueRoom::ClassName;
            $payload = $this->getJsonPayload( SummitLocationValidationRulesFactory::build($payload), true);

            $payload['floor_id'] = intval($floor_id);

            $room = $this->location_service->addVenueRoom($summit, $venue_id, $payload);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($room)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    /**
     *  Update Location Endpoints
     */

    /**
     * @param $summit_id
     * @param $location_id
     * @return mixed
     */
    public function updateLocation($summit_id, $location_id)
    {
        return $this->processRequest(function() use($summit_id, $location_id){

            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();
            $payload = $this->getJsonData();
            $payload = $this->getJsonPayload( SummitLocationValidationRulesFactory::build($payload, true), true);


            $location = $this->location_service->updateLocation($summit, $location_id, $payload);

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($location)->serialize(
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
    public function updateVenue($summit_id, $venue_id)
    {
        return $this->processRequest(function() use($summit_id, $venue_id){

            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonData();
            $payload['class_name'] = SummitVenue::ClassName;
            $payload = $this->getJsonPayload(SummitLocationValidationRulesFactory::build($payload, true), true);

            $location = $this->location_service->updateLocation($summit, $venue_id, $payload);

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($location)->serialize(
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
     * @return mixed
     */
    public function updateVenueFloor($summit_id, $venue_id, $floor_id)
    {
        return $this->processRequest(function() use($summit_id, $venue_id, $floor_id){

            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonPayload([
                'name' => 'sometimes|string|max:50',
                'number' => 'sometimes|integer',
                'description' => 'sometimes|string',
            ], true);

            $floor = $this->location_service->updateVenueFloor($summit, $venue_id, $floor_id, $payload);

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($floor)->serialize(
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
    public function updateVenueRoom($summit_id, $venue_id, $room_id)
    {
        return $this->processRequest(function() use($summit_id, $venue_id, $room_id){

            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonData();
            $payload['class_name'] = SummitVenueRoom::ClassName;
            $payload = $this->getJsonPayload(SummitLocationValidationRulesFactory::build($payload, true), true);

            $room = $this->location_service->updateVenueRoom($summit, $venue_id, $room_id, $payload);

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
     * @param $floor_id
     * @param $room_id
     * @return mixed
     */
    public function updateVenueFloorRoom($summit_id, $venue_id, $floor_id, $room_id)
    {
        return $this->processRequest(function() use($summit_id, $venue_id, $floor_id, $room_id){
            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonData();
            $payload['class_name'] = SummitVenueRoom::ClassName;
            $payload =  $this->getJsonPayload(SummitLocationValidationRulesFactory::build($payload, true), true);

            if (!isset($payload['floor_id']))
                $payload['floor_id'] = intval($floor_id);

            $room = $this->location_service->updateVenueRoom($summit, $venue_id, $room_id, $payload);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($room)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }


    /**
     * @param $summit_id
     * @param $hotel_id
     * @return mixed
     */
    public function updateHotel($summit_id, $hotel_id)
    {
        return $this->processRequest(function() use($summit_id, $hotel_id){

            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonData();
            $payload['class_name'] = SummitHotel::ClassName;
            $payload = $this->getJsonPayload(SummitLocationValidationRulesFactory::build($payload, true), true);

            $location = $this->location_service->updateLocation($summit, $hotel_id, $payload);

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($location)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    /**
     * @param $summit_id
     * @param $airport_id
     * @return mixed
     */
    public function updateAirport($summit_id, $airport_id)
    {
        return $this->processRequest(function() use($summit_id, $airport_id){

            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonData();
            $payload['class_name'] = SummitAirport::ClassName;
            $payload = $this->getJsonPayload(SummitLocationValidationRulesFactory::build($payload, true), true);

            $location = $this->location_service->updateLocation($summit, $airport_id, $payload);

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($location)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    /**
     * @param $summit_id
     * @param $external_location_id
     * @return mixed
     */
    public function updateExternalLocation($summit_id, $external_location_id)
    {
        return $this->processRequest(function() use($summit_id, $external_location_id){

            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();
            $payload = $this->getJsonData();
            $payload['class_name'] = SummitExternalLocation::ClassName;
            $payload = $this->getJsonPayload(SummitLocationValidationRulesFactory::build($payload, true), true);

            $location = $this->location_service->updateLocation($summit, $external_location_id, $payload);

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($location)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));

        });
    }

    /**
     * Delete Location Endpoints
     */

    /**
     * @param $summit_id
     * @param $location_id
     * @return mixed
     */
    public function deleteLocation($summit_id, $location_id)
    {
        return $this->processRequest(function() use($summit_id, $location_id){

            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $this->location_service->deleteLocation($summit, $location_id);

            return $this->deleted();
        });

    }

    /**
     * @param $summit_id
     * @param $venue_id
     * @param $floor_id
     * @return mixed
     */
    public function deleteVenueFloor($summit_id, $venue_id, $floor_id)
    {
        return $this->processRequest(function() use($summit_id, $venue_id, $floor_id){

            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $this->location_service->deleteVenueFloor($summit, $venue_id, $floor_id);

            return $this->deleted();

        });
    }

    /**
     * @param $summit_id
     * @param $venue_id
     * @param $room_id
     * @return mixed
     */
    public function deleteVenueRoom($summit_id, $venue_id, $room_id)
    {
        return $this->processRequest(function() use($summit_id, $venue_id, $room_id){

            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $this->location_service->deleteVenueRoom($summit, $venue_id, $room_id);

            return $this->deleted();

        });
    }

    /**
     *  Location Banners Endpoints
     */

    /**
     * @param $summit_id
     * @param $location_id
     * @return mixed
     */
    public function getLocationBanners($summit_id, $location_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
        if (is_null($summit)) return $this->error404();

        $location = $summit->getLocation($location_id);
        if (is_null($location)) return $this->error404();

        return $this->_getAll(
            function(){
                return [
                    'class_name' => ['=='],
                    'title' => ['==', '=@'],
                    'content' => ['=@'],
                    'type' => ['=='],
                    'enabled' => ['=='],
                    'start_date' => ['>', '<', '<=', '>=', '=='],
                    'end_date' => ['>', '<', '<=', '>=', '=='],
                ];
            },
            function(){
                return [
                    'class_name' => sprintf('sometimes|in:%s', implode(',', SummitLocationBannerConstants::$valid_class_names)),
                    'title' => 'sometimes|string',
                    'content' => 'sometimes|string',
                    'type' => sprintf('sometimes|in:%s', implode(',', SummitLocationBannerConstants::$valid_types)),
                    'enabled' => 'sometimes|boolean',
                    'start_date' => 'sometimes|date_format:U',
                    'end_date' => 'sometimes|date_format:U',
                ];
            },
            function(){
                return  [
                    'id',
                    'title',
                    'start_date',
                    'end_date'
                ];
            },
            function ($filter) {
                return $filter;
            },
            function () {
                return SerializerRegistry::SerializerType_Public;
            },
            null,
            null,
            function ($page, $per_page, $filter, $order, $applyExtraFilters) use ($location) {
                return $this->location_banners_repository->getBySummitLocation($location, new PagingInfo($page, $per_page), call_user_func($applyExtraFilters, $filter), $order);
            }
        );
    }

    /**
     * @param $summit_id
     * @param $location_id
     * @return mixed
     */
    public function addLocationBanner($summit_id, $location_id)
    {
        return $this->processRequest(function() use($summit_id, $location_id){

            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonData();
            $payload = $this->getJsonPayload(SummitLocationBannerValidationRulesFactory::build($payload), true);

            $banner = $this->location_service->addLocationBanner
            (
                $summit,
                $location_id,
                HTMLCleaner::cleanData
                (
                    $payload, ['title', 'content']
                )
            );

            return $this->created(SerializerRegistry::getInstance()->getSerializer($banner)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    /**
     * @param $summit_id
     * @param $location_id
     * @param $banner_id
     * @return mixed
     */
    public function deleteLocationBanner($summit_id, $location_id, $banner_id)
    {

        return $this->processRequest(function() use($summit_id, $location_id, $banner_id){

            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $this->location_service->deleteLocationBanner($summit, $location_id, $banner_id);

            return $this->deleted();

        });
    }

    /**
     * @param $summit_id
     * @param $location_id
     * @param $banner_id
     * @return mixed
     */
    public function updateLocationBanner($summit_id, $location_id, $banner_id)
    {
        return $this->processRequest(function() use($summit_id, $location_id, $banner_id){

            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonData();
            $payload = $this->getJsonPayload(SummitLocationBannerValidationRulesFactory::build($payload, true), true);

            $banner = $this->location_service->updateLocationBanner
            (
                $summit,
                $location_id,
                $banner_id,
                HTMLCleaner::cleanData
                (
                    $payload, ['title', 'content']
                )
            );

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($banner)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));

        });
    }

    /**
     *  Location Maps endpoints
     */


    /**
     * @param $summit_id
     * @param $location_id
     * @param $map_id
     * @return mixed
     */
    public function getLocationMap($summit_id, $location_id, $map_id)
    {
        return $this->processRequest(function() use($summit_id, $location_id, $map_id){

            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $location = $summit->getLocation($location_id);
            if (is_null($location)) {
                return $this->error404();
            }

            if (!Summit::isPrimaryLocation($location)) {
                return $this->error404();
            }

            $map = $location->getMap($map_id);
            if (is_null($map)) {
                return $this->error404();
            }

            return $this->ok(SerializerRegistry::getInstance()->getSerializer($map)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));

        });
    }

    /**
     * @param LaravelRequest $request
     * @param $summit_id
     * @param $location_id
     * @return mixed
     */
    public function addLocationMap(LaravelRequest $request, $summit_id, $location_id)
    {

        return $this->processRequest(function() use($request, $summit_id, $location_id){
            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $file = $request->file('file');

            if (is_null($file))
                throw new ValidationException('file is required.');

            $metadata = $request->all();

            $rules = SummitLocationImageValidationRulesFactory::build();
            // Creates a Validator instance and validates the data.
            $validation = Validator::make($metadata, $rules);

            if ($validation->fails()) {
                $messages = $validation->messages()->toArray();

                return $this->error412
                (
                    $messages
                );
            }

            $map = $this->location_service->addLocationMap
            (
                $summit,
                $location_id,
                HTMLCleaner::cleanData
                (
                    $metadata, ['description']
                ),
                $file
            );

            return $this->created(SerializerRegistry::getInstance()->getSerializer($map)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    /**
     * @param LaravelRequest $request
     * @param $summit_id
     * @param $location_id
     * @param $map_id
     * @return mixed
     */
    public function updateLocationMap(LaravelRequest $request, $summit_id, $location_id, $map_id)
    {
        return $this->processRequest(function() use($request, $summit_id, $location_id, $map_id){

            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();
            $data = $request->all();
            $rules = SummitLocationImageValidationRulesFactory::build(true);
            // Creates a Validator instance and validates the data.
            $validation = Validator::make($data, $rules);

            if ($validation->fails()) {
                $messages = $validation->messages()->toArray();

                return $this->error412
                (
                    $messages
                );
            }

            $map = $this->location_service->updateLocationMap
            (
                $summit,
                $location_id,
                $map_id,
                HTMLCleaner::cleanData
                (
                    $data, ['description']
                ),
                $request->hasFile('file') ? $request->file('file') : null
            );

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($map)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));

        });
    }

    /**
     * @param $summit_id
     * @param $location_id
     * @param $map_id
     * @return mixed
     */
    public function deleteLocationMap($summit_id, $location_id, $map_id)
    {
        return $this->processRequest(function() use($summit_id, $location_id, $map_id){
            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();
            $this->location_service->deleteLocationMap($summit, $location_id, $map_id);
            return $this->deleted();
        });
    }

    /**
     *  Location Images endpoints
     */

    /**
     * @param $summit_id
     * @param $location_id
     * @param $image_id
     * @return mixed
     */
    public function getLocationImage($summit_id, $location_id, $image_id)
    {
        return $this->processRequest(function() use($summit_id, $location_id, $image_id){

            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $location = $summit->getLocation($location_id);
            if (is_null($location)) {
                return $this->error404();
            }

            if (!$location instanceof SummitGeoLocatedLocation) {
                return $this->error404();
            }

            $image = $location->getImage($image_id);
            if (is_null($image)) {
                return $this->error404();
            }

            return $this->ok(SerializerRegistry::getInstance()->getSerializer($image)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));

        });
    }

    /**
     * @param LaravelRequest $request
     * @param $summit_id
     * @param $location_id
     * @return mixed
     */
    public function addLocationImage(LaravelRequest $request, $summit_id, $location_id)
    {
        return $this->processRequest(function() use($request, $summit_id, $location_id){

            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $file = $request->file('file');
            if (is_null($file))
                throw new ValidationException('file is required.');

            $metadata = $request->all();

            $rules = SummitLocationImageValidationRulesFactory::build();
            // Creates a Validator instance and validates the data.
            $validation = Validator::make($metadata, $rules);

            if ($validation->fails()) {
                $messages = $validation->messages()->toArray();

                return $this->error412
                (
                    $messages
                );
            }

            $image = $this->location_service->addLocationImage
            (
                $summit,
                $location_id,
                HTMLCleaner::cleanData
                (
                    $metadata, ['description']
                ),
                $file
            );

            return $this->created(SerializerRegistry::getInstance()->getSerializer($image)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    /**
     * @param LaravelRequest $request
     * @param $summit_id
     * @param $location_id
     * @param $image_id
     * @return mixed
     */
    public function updateLocationImage(LaravelRequest $request, $summit_id, $location_id, $image_id)
    {
        return $this->processRequest(function() use($request, $summit_id, $location_id, $image_id){

            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonPayload(SummitLocationImageValidationRulesFactory::build(true));

            $image = $this->location_service->updateLocationImage
            (
                $summit,
                $location_id,
                $image_id,
                HTMLCleaner::cleanData
                (
                    $payload, ['description']
                ),
                $request->hasFile('file') ? $request->file('file') : null
            );

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($image)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    /**
     * @param $summit_id
     * @param $location_id
     * @param $image_id
     * @return mixed
     */
    public function deleteLocationImage($summit_id, $location_id, $image_id)
    {
        return $this->processRequest(function() use($summit_id, $location_id, $image_id){
            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();
            $this->location_service->deleteLocationImage($summit, $location_id, $image_id);
            return $this->deleted();
        });
    }

    /**
     * @param LaravelRequest $request
     * @param $summit_id
     * @param $venue_id
     * @param $room_id
     * @return mixed
     */
    public function addVenueRoomImage(LaravelRequest $request, $summit_id, $venue_id, $room_id)
    {
        return $this->processRequest(function() use($request, $summit_id, $venue_id, $room_id){

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();
            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $venue = $summit->getLocation($venue_id);

            if (is_null($venue)) {
                return $this->error404();
            }

            if (!$venue instanceof SummitVenue) {
                return $this->error404();
            }

            $room = $venue->getRoom($room_id);

            if (is_null($room)) {
                return $this->error404();
            }

            $file = $request->file('file');
            if (is_null($file)) {
                return $this->error412(array('file param not set!'));
            }

            $photo = $this->location_service->addRoomImage($summit, intval($venue_id), intval($room_id), $file);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($photo)->serialize(
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
    public function removeVenueRoomImage($summit_id, $venue_id, $room_id)
    {
        return $this->processRequest(function() use($summit_id, $venue_id, $room_id){

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();
            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $venue = $summit->getLocation($venue_id);

            if (is_null($venue)) {
                return $this->error404();
            }

            if (!$venue instanceof SummitVenue) {
                return $this->error404();
            }

            $room = $venue->getRoom($room_id);

            if (is_null($room)) {
                return $this->error404();
            }

            $room = $this->location_service->removeRoomImage($summit, intval($venue_id), intval($room_id));

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($room)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));

        });
    }

    /**
     * @param LaravelRequest $request
     * @param $summit_id
     * @param $venue_id
     * @param $floor_id
     * @return mixed
     */
    public function addVenueFloorImage(LaravelRequest $request, $summit_id, $venue_id, $floor_id)
    {
        return $this->processRequest(function() use($request, $summit_id, $venue_id, $floor_id) {

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();
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

            $file = $request->file('file');
            if (is_null($file)) {
                return $this->error412(array('file param not set!'));
            }

            $photo = $this->location_service->addFloorImage($summit, intval($venue_id), intval($floor_id), $file);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($photo)->serialize
            (
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
     * @return mixed
     */
    public function removeVenueFloorImage($summit_id, $venue_id, $floor_id)
    {
        return $this->processRequest(function () use($summit_id, $venue_id, $floor_id) {

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();
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

            $floor = $this->location_service->removeFloorImage($summit, intval($venue_id), intval($floor_id));

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($floor)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));

        });
    }

    // bookable rooms

    use SummitBookableVenueRoomApi;
}