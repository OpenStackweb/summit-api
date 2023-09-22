<?php namespace App\Services\Model;
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
use App\Events\CreatedBookableRoomReservation;
use App\Http\Utils\IFileUploader;
use App\Models\Foundation\Summit\Factories\SummitLocationBannerFactory;
use App\Models\Foundation\Summit\Factories\SummitLocationFactory;
use App\Models\Foundation\Summit\Factories\SummitLocationImageFactory;
use App\Models\Foundation\Summit\Factories\SummitRoomReservationFactory;
use App\Models\Foundation\Summit\Factories\SummitVenueFloorFactory;
use App\Models\Foundation\Summit\Locations\Banners\SummitLocationBanner;
use App\Models\Foundation\Summit\Registration\IBuildDefaultPaymentGatewayProfileStrategy;
use App\Models\Foundation\Summit\Repositories\ISummitLocationRepository;
use App\Models\Foundation\Summit\Repositories\ISummitRoomReservationRepository;
use App\Services\Apis\GeoCodingApiException;
use App\Services\Apis\IGeoCodingAPI;
use App\Services\Model\Strategies\GeoLocation\GeoLocationStrategyFactory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use libs\utils\ITransactionService;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\main\File;
use models\main\IMemberRepository;
use models\main\Member;
use models\summit\IPaymentConstants;
use models\summit\Summit;
use models\summit\SummitAbstractLocation;
use models\summit\SummitBookableVenueRoom;
use models\summit\SummitGeoLocatedLocation;
use models\summit\SummitLocationImage;
use models\summit\SummitRoomReservation;
use models\summit\SummitVenue;
use models\summit\SummitVenueFloor;
use models\summit\SummitVenueRoom;
/**
 * Class SummitLocationService
 * @package App\Services\Model
 */
final class SummitLocationService
    extends AbstractService
    implements ILocationService
{
    /**
     * @var ISummitLocationRepository
     */
    private $location_repository;

    /**
     * @var IGeoCodingAPI
     */
    private $geo_coding_api;

    /**
     * @var IFolderService
     */
    private $folder_service;

    /**
     * @var IMemberRepository
     */
    private $member_repository;

    /**
     * @var ISummitRoomReservationRepository
     */
    private $reservation_repository;

    /**
     * @var IFileUploader
     */
    private $file_uploader;

    /**
     * @var IBuildDefaultPaymentGatewayProfileStrategy
     */
    private $default_payment_gateway_strategy;

    /**
     * SummitLocationService constructor.
     * @param ISummitLocationRepository $location_repository
     * @param IMemberRepository $member_repository
     * @param ISummitRoomReservationRepository $reservation_repository
     * @param IGeoCodingAPI $geo_coding_api
     * @param IFolderService $folder_service
     * @param IFileUploader $file_uploader
     * @param IBuildDefaultPaymentGatewayProfileStrategy $default_payment_gateway_strategy
     * @param ITransactionService $tx_service
     */
    public function __construct
    (
        ISummitLocationRepository $location_repository,
        IMemberRepository $member_repository,
        ISummitRoomReservationRepository $reservation_repository,
        IGeoCodingAPI $geo_coding_api,
        IFolderService $folder_service,
        IFileUploader $file_uploader,
        IBuildDefaultPaymentGatewayProfileStrategy $default_payment_gateway_strategy,
        ITransactionService $tx_service
    )
    {
        parent::__construct($tx_service);

        $this->location_repository = $location_repository;
        $this->member_repository = $member_repository;
        $this->reservation_repository = $reservation_repository;
        $this->geo_coding_api = $geo_coding_api;
        $this->file_uploader = $file_uploader;
        $this->folder_service = $folder_service;
        $this->default_payment_gateway_strategy = $default_payment_gateway_strategy;
    }

    /**
     * @param Summit $summit
     * @param array $data
     * @return SummitAbstractLocation
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function addLocation(Summit $summit, array $data)
    {
        $location = $this->tx_service->transaction(function () use ($summit, $data) {

            $old_location = $summit->getLocationByName(trim($data['name']));

            if (!is_null($old_location)) {
                throw new ValidationException
                (
                    trans
                    (
                        'validation_errors.LocationService.addLocation.LocationNameAlreadyExists',
                        [
                            'summit_id' => $summit->getId()
                        ]
                    )
                );
            }

            $location = SummitLocationFactory::build($data);

            if (is_null($location)) {
                throw new ValidationException
                (
                    trans
                    (
                        'validation_errors.LocationService.addLocation.InvalidClassName'
                    )
                );
            }

            if ($location instanceof SummitGeoLocatedLocation) {
                try {
                    $geo_location_strategy = GeoLocationStrategyFactory::build($location);
                    $geo_location_strategy->doGeoLocation($location, $this->geo_coding_api);
                } catch (GeoCodingApiException $ex1) {
                    Log::warning($ex1->getMessage());
                    $validation_msg = trans('validation_errors.LocationService.addLocation.geoCodingGenericError');
                    switch ($ex1->getStatus()) {
                        case IGeoCodingAPI::ResponseStatusZeroResults:
                            {
                                $validation_msg = trans('validation_errors.LocationService.addLocation.InvalidAddressOrCoordinates');
                            }
                            break;
                        case IGeoCodingAPI::ResponseStatusOverQueryLimit:
                            {
                                $validation_msg = trans('validation_errors.LocationService.addLocation.OverQuotaLimit');
                            }
                            break;
                    }
                    throw new ValidationException($validation_msg);
                } catch (\Exception $ex) {
                    Log::warning($ex->getMessage());
                    throw $ex;
                }
            }

            $summit->addLocation($location);

            return $location;
        });

        return $location;
    }

    /**
     * @param Summit $summit
     * @param int $location_id
     * @param array $data
     * @return SummitAbstractLocation
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function updateLocation(Summit $summit, $location_id, array $data)
    {
        return $this->tx_service->transaction(function () use ($summit, $location_id, $data) {

            if (isset($data['name'])) {
                $old_location = $summit->getLocationByName(trim($data['name']));

                if (!is_null($old_location) && $old_location->getId() != $location_id) {
                    throw new ValidationException
                    (
                        trans
                        (
                            'validation_errors.LocationService.updateLocation.LocationNameAlreadyExists',
                            [
                                'summit_id' => $summit->getId()
                            ]
                        )
                    );
                }
            }

            $location = $summit->getLocation($location_id);

            if (is_null($location)) {
                throw new EntityNotFoundException(
                    trans
                    (
                        'validation_errors.LocationService.updateLocation.LocationNotFoundOnSummit',
                        [
                            'summit_id' => $summit->getId(),
                            'location_id' => $location_id,
                        ]
                    )
                );
            }

            if (!Summit::isPrimaryLocation($location)) {
                throw new EntityNotFoundException(
                    trans
                    (
                        'validation_errors.LocationService.updateLocation.LocationNotFoundOnSummit',
                        [
                            'summit_id' => $summit->getId(),
                            'location_id' => $location_id,
                        ]
                    )
                );
            }

            if ($location->getClassName() != $data['class_name']) {
                throw new ValidationException(
                    trans
                    (
                        'validation_errors.LocationService.updateLocation.ClassNameMissMatch',
                        [
                            'summit_id' => $summit->getId(),
                            'location_id' => $location_id,
                            'class_name' => $data['class_name']
                        ]
                    )
                );
            }

            $location = SummitLocationFactory::populate($location, $data);

            if ($location instanceof SummitGeoLocatedLocation && $this->hasGeoLocationData2Update($data)) {
                try {
                    $geo_location_strategy = GeoLocationStrategyFactory::build($location);
                    $geo_location_strategy->doGeoLocation($location, $this->geo_coding_api);
                } catch (GeoCodingApiException $ex1) {
                    Log::warning($ex1->getMessage());
                    $validation_msg = trans('validation_errors.LocationService.addLocation.geoCodingGenericError');
                    switch ($ex1->getStatus()) {
                        case IGeoCodingAPI::ResponseStatusZeroResults:
                            {
                                $validation_msg = trans('validation_errors.LocationService.addLocation.InvalidAddressOrCoordinates');
                            }
                            break;
                        case IGeoCodingAPI::ResponseStatusOverQueryLimit:
                            {
                                $validation_msg = trans('validation_errors.LocationService.addLocation.OverQuotaLimit');
                            }
                            break;
                    }
                    throw new ValidationException($validation_msg);
                } catch (\Exception $ex) {
                    Log::warning($ex->getMessage());
                    throw $ex;
                }
            }

            if (isset($data['order']) && intval($data['order']) != $location->getOrder()) {
                // request to update order
                $summit->recalculateLocationOrder($location, intval($data['order']));
            }

            return $location;
        });
    }

    /**
     * @param array $data
     * @return bool
     */
    private function hasGeoLocationData2Update(array $data)
    {
        return isset($data['address_1']) || isset($data['lat']);
    }

    /**
     * @param Summit $summit
     * @param int $location_id
     * @return SummitAbstractLocation
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function deleteLocation(Summit $summit, $location_id)
    {
        return $this->tx_service->transaction(function () use ($summit, $location_id) {

            $location = $summit->getLocation($location_id);

            if (is_null($location)) {
                throw new EntityNotFoundException(
                    trans
                    (
                        'validation_errors.LocationService.deleteLocation.LocationNotFoundOnSummit',
                        [
                            'summit_id' => $summit->getId(),
                            'location_id' => $location_id,
                        ]
                    )
                );
            }
            if (!Summit::isPrimaryLocation($location)) {
                throw new EntityNotFoundException(
                    trans
                    (
                        'validation_errors.LocationService.deleteLocation.LocationNotFoundOnSummit',
                        [
                            'summit_id' => $summit->getId(),
                            'location_id' => $location_id,
                        ]
                    )
                );
            }

            $summit->removeLocation($location);
        });
    }

    /**
     * @param Summit $summit
     * @param int $venue_id
     * @param array $data
     * @return SummitVenueFloor
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function addVenueFloor(Summit $summit, $venue_id, array $data)
    {
        return $this->tx_service->transaction(function () use ($summit, $venue_id, $data) {

            $venue = $summit->getLocation($venue_id);

            if (is_null($venue)) {
                throw new EntityNotFoundException
                (
                    trans
                    (
                        'not_found_errors.LocationService.addVenueFloor.VenueNotFound',
                        [
                            'summit_id' => $summit->getId(),
                            'venue_id' => $venue_id,
                        ]
                    )
                );
            }

            if (!$venue instanceof SummitVenue) {
                throw new EntityNotFoundException
                (
                    trans
                    (
                        'not_found_errors.LocationService.addVenueFloor.VenueNotFound',
                        [
                            'summit_id' => $summit->getId(),
                            'venue_id' => $venue_id,
                        ]
                    )
                );
            }

            $former_floor = $venue->getFloorByName(trim($data['name']));

            if (!is_null($former_floor)) {
                throw new ValidationException(
                    trans
                    (
                        'validation_errors.LocationService.addVenueFloor.FloorNameAlreadyExists',
                        [
                            'venue_id' => $venue_id,
                            'floor_name' => trim($data['name'])
                        ]
                    )
                );
            }

            $former_floor = $venue->getFloorByNumber(intval($data['number']));

            if (!is_null($former_floor)) {
                throw new ValidationException
                (
                    trans
                    (
                        'validation_errors.LocationService.addVenueFloor.FloorNumberAlreadyExists',
                        [
                            'venue_id' => $venue_id,
                            'floor_number' => intval($data['number'])
                        ]
                    )
                );
            }

            $floor = SummitVenueFloorFactory::build($data);

            $venue->addFloor($floor);

            return $floor;
        });
    }

    /**
     * @param Summit $summit
     * @param int $venue_id
     * @param int $floor_id
     * @param array $data
     * @return SummitVenueFloor
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function updateVenueFloor(Summit $summit, $venue_id, $floor_id, array $data)
    {
        return $this->tx_service->transaction(function () use ($summit, $venue_id, $floor_id, $data) {

            $venue = $summit->getLocation($venue_id);

            if (is_null($venue)) {
                throw new EntityNotFoundException
                (
                    trans
                    (
                        'not_found_errors.LocationService.updateVenueFloor.VenueNotFound',
                        [
                            'summit_id' => $summit->getId(),
                            'venue_id' => $venue_id,
                        ]
                    )
                );
            }

            if (!$venue instanceof SummitVenue) {
                throw new EntityNotFoundException
                (
                    trans
                    (
                        'not_found_errors.LocationService.updateVenueFloor.VenueNotFound',
                        [
                            'summit_id' => $summit->getId(),
                            'venue_id' => $venue_id,
                        ]
                    )
                );
            }

            if (isset($data['name'])) {
                $former_floor = $venue->getFloorByName(trim($data['name']));

                if (!is_null($former_floor) && $former_floor->getId() != $floor_id) {
                    throw new ValidationException(
                        trans
                        (
                            'validation_errors.LocationService.addVenueFloor.FloorNameAlreadyExists',
                            [
                                'venue_id' => $venue_id,
                                'floor_name' => trim($data['name'])
                            ]
                        )
                    );
                }
            }

            if (isset($data['number'])) {
                $former_floor = $venue->getFloorByNumber(intval($data['number']));

                if (!is_null($former_floor) && $former_floor->getId() != $floor_id) {
                    throw new ValidationException
                    (
                        trans
                        (
                            'validation_errors.LocationService.addVenueFloor.FloorNumberAlreadyExists',
                            [
                                'venue_id' => $venue_id,
                                'floor_number' => intval($data['number'])
                            ]
                        )
                    );
                }

            }

            $floor = $venue->getFloor($floor_id);
            if (is_null($floor)) {
                throw new EntityNotFoundException
                (
                    trans
                    (
                        'not_found_errors.LocationService.updateVenueFloor.FloorNotFound',
                        [
                            'floor_id' => $floor_id,
                            'venue_id' => $venue_id
                        ]
                    )
                );
            }

            return SummitVenueFloorFactory::populate($floor, $data);

        });
    }

    /**
     * @param Summit $summit
     * @param int $venue_id
     * @param int $floor_id
     * @return void
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function deleteVenueFloor(Summit $summit, $venue_id, $floor_id)
    {
        return $this->tx_service->transaction(function () use ($summit, $venue_id, $floor_id) {

            $venue = $summit->getLocation($venue_id);

            if (is_null($venue)) {
                throw new EntityNotFoundException
                (
                    trans
                    (
                        'not_found_errors.LocationService.deleteVenueFloor.VenueNotFound',
                        [
                            'summit_id' => $summit->getId(),
                            'venue_id' => $venue_id,
                        ]
                    )
                );
            }

            if (!$venue instanceof SummitVenue) {
                throw new EntityNotFoundException
                (
                    trans
                    (
                        'not_found_errors.LocationService.deleteVenueFloor.VenueNotFound',
                        [
                            'summit_id' => $summit->getId(),
                            'venue_id' => $venue_id,
                        ]
                    )
                );
            }

            $floor = $venue->getFloor($floor_id);

            if (is_null($floor)) {
                throw new EntityNotFoundException
                (
                    trans
                    (
                        'not_found_errors.LocationService.deleteVenueFloor.FloorNotFound',
                        [
                            'floor_id' => $floor_id,
                            'venue_id' => $venue_id
                        ]
                    )
                );
            }

            $venue->removeFloor($floor);
        });
    }

    /**
     * @param Summit $summit
     * @param $venue_id
     * @param array $data
     * @return SummitVenueRoom
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function addVenueRoom(Summit $summit, $venue_id, array $data)
    {
        $room = $this->tx_service->transaction(function () use ($summit, $venue_id, $data) {

            if (isset($data['name'])) {
                $old_location = $summit->getLocationByName(trim($data['name']));

                if (!is_null($old_location)) {
                    throw new ValidationException
                    (
                        trans
                        (
                            'validation_errors.LocationService.addVenueRoom.LocationNameAlreadyExists',
                            [
                                'summit_id' => $summit->getId()
                            ]
                        )
                    );
                }
            }

            $venue = $summit->getLocation($venue_id);

            if (is_null($venue)) {
                throw new EntityNotFoundException
                (
                    trans
                    (
                        'not_found_errors.LocationService.addVenueRoom.VenueNotFound',
                        [
                            'summit_id' => $summit->getId(),
                            'venue_id' => $venue_id,
                        ]
                    )
                );
            }

            if (!$venue instanceof SummitVenue) {
                throw new EntityNotFoundException
                (
                    trans
                    (
                        'not_found_errors.LocationService.addVenueRoom.VenueNotFound',
                        [
                            'summit_id' => $summit->getId(),
                            'venue_id' => $venue_id,
                        ]
                    )
                );
            }

            $data['class_name'] = SummitVenueRoom::ClassName;
            $room = SummitLocationFactory::build($data);

            if (is_null($room)) {
                throw new ValidationException
                (
                    trans
                    (
                        'validation_errors.LocationService.addVenueRoom.InvalidClassName'
                    )
                );
            }

            if (isset($data['floor_id'])) {
                $floor_id = intval($data['floor_id']);
                $floor = $venue->getFloor($floor_id);

                if (is_null($floor)) {
                    throw new EntityNotFoundException
                    (
                        trans
                        (
                            'not_found_errors.LocationService.addVenueRoom.FloorNotFound',
                            [
                                'floor_id' => $floor_id,
                                'venue_id' => $venue_id
                            ]
                        )
                    );
                }

                $floor->addRoom($room);
            }

            $summit->addLocation($room);
            $venue->addRoom($room);

            return $room;
        });

        return $room;
    }

    /**
     * @param Summit $summit
     * @param int $venue_id
     * @param int $room_id
     * @param array $data
     * @return SummitVenueRoom
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function updateVenueRoom(Summit $summit, $venue_id, $room_id, array $data)
    {
        return $this->tx_service->transaction(function () use ($summit, $venue_id, $room_id, $data) {

            if (isset($data['name'])) {
                $old_location = $summit->getLocationByName(trim($data['name']));

                if (!is_null($old_location) && $old_location->getId() != $room_id) {
                    throw new ValidationException
                    (
                        trans
                        (
                            'validation_errors.LocationService.updateVenueRoom.LocationNameAlreadyExists',
                            [
                                'summit_id' => $summit->getId()
                            ]
                        )
                    );
                }
            }

            $venue = $summit->getLocation($venue_id);

            if (is_null($venue)) {
                throw new EntityNotFoundException
                (
                    trans
                    (
                        'not_found_errors.LocationService.updateVenueRoom.VenueNotFound',
                        [
                            'summit_id' => $summit->getId(),
                            'venue_id' => $venue_id,
                        ]
                    )
                );
            }

            if (!$venue instanceof SummitVenue) {
                throw new EntityNotFoundException
                (
                    trans
                    (
                        'not_found_errors.LocationService.updateVenueRoom.VenueNotFound',
                        [
                            'summit_id' => $summit->getId(),
                            'venue_id' => $venue_id,
                        ]
                    )
                );
            }

            $room = $summit->getLocation($room_id);
            if (is_null($room)) {
                throw new EntityNotFoundException
                (
                    trans
                    (
                        'not_found_errors.LocationService.updateVenueRoom.RoomNotFound',
                        [
                            'summit_id' => $summit->getId(),
                            'venue_id' => $venue_id,
                            'room_id' => $room_id,
                        ]
                    )
                );
            }

            if (!$room instanceof SummitVenueRoom) {
                throw new EntityNotFoundException
                (
                    trans
                    (
                        'not_found_errors.LocationService.updateVenueRoom.RoomNotFound',
                        [
                            'summit_id' => $summit->getId(),
                            'venue_id' => $venue_id,
                            'room_id' => $room_id,
                        ]
                    )
                );
            }

            $old_floor_id = $room->getFloorId();
            $new_floor_id = $room->getFloorId();
            SummitLocationFactory::populate($room, $data);
            $floor = null;

            if (isset($data['floor_id'])) {
                $old_floor_id = intval($room->getFloorId());
                $new_floor_id = intval($data['floor_id']);
                if ($old_floor_id != $new_floor_id) {
                    $floor = $venue->getFloor($new_floor_id);
                    if (is_null($floor)) {
                        throw new EntityNotFoundException
                        (
                            trans
                            (
                                'not_found_errors.LocationService.updateVenueRoom.FloorNotFound',
                                [
                                    'floor_id' => $new_floor_id,
                                    'venue_id' => $venue_id
                                ]
                            )
                        );
                    }
                    if ($old_floor_id > 0) {
                        // remove from old floor
                        $room->getFloor()->removeRoom($room);

                    }
                    $floor->addRoom($room);
                }
            }

            // request to update order
            if (isset($data['order']) && intval($data['order']) != $room->getOrder()) {

                if (!is_null($floor)) {
                    $floor->recalculateRoomsOrder($room, intval($data['order']));
                } else {
                    $venue->recalculateRoomsOrder($room, intval($data['order']));
                }
            }

            return $room;
        });

    }

    /**
     * @param Summit $summit
     * @param int $venue_id
     * @param int $room_id
     * @return void
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function deleteVenueRoom(Summit $summit, $venue_id, $room_id)
    {
        return $this->tx_service->transaction(function () use ($summit, $venue_id, $room_id) {

            $venue = $summit->getLocation($venue_id);

            if (is_null($venue)) {
                throw new EntityNotFoundException
                (
                    trans
                    (
                        'not_found_errors.LocationService.deleteVenueRoom.VenueNotFound',
                        [
                            'summit_id' => $summit->getId(),
                            'venue_id' => $venue_id,
                        ]
                    )
                );
            }

            if (!$venue instanceof SummitVenue) {
                throw new EntityNotFoundException
                (
                    trans
                    (
                        'not_found_errors.LocationService.deleteVenueRoom.VenueNotFound',
                        [
                            'summit_id' => $summit->getId(),
                            'venue_id' => $venue_id,
                        ]
                    )
                );
            }

            $room = $venue->getRoom($room_id);

            if (is_null($room)) {
                throw new EntityNotFoundException
                (
                    trans
                    (
                        'not_found_errors.LocationService.deleteVenueRoom.RoomNotFound',
                        [
                            'room_id' => $room_id,
                            'venue_id' => $venue_id
                        ]
                    )
                );
            }

            $venue->removeRoom($room);

            if ($room->hasFloor()) {
                $floor = $room->getFloor();
                $floor->removeRoom($room);
            }

        });
    }

    /**
     * @param Summit $summit
     * @param int $location_id
     * @param array $data
     * @return SummitLocationBanner
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function addLocationBanner(Summit $summit, $location_id, array $data)
    {
        return $this->tx_service->transaction(function () use ($summit, $location_id, $data) {

            $location = $summit->getLocation($location_id);

            if (is_null($location)) {
                throw new EntityNotFoundException
                (
                    trans
                    (
                        'not_found_errors.LocationService.addLocationBanner.LocationNotFound',
                        [
                            'summit_id' => $summit->getId(),
                            'location_id' => $location_id,
                        ]
                    )
                );
            }

            $banner = SummitLocationBannerFactory::build($summit, $location, $data);

            if (is_null($banner)) {
                throw new ValidationException
                (
                    trans
                    (
                        'validation_errors.LocationService.addLocationBanner.InvalidClassName'
                    )
                );
            }

            $location->addBanner($banner);

            return $banner;
        });
    }

    /**
     * @param Summit $summit
     * @param int $location_id
     * @param int $banner_id
     * @param array $data
     * @return SummitLocationBanner
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function updateLocationBanner(Summit $summit, $location_id, $banner_id, array $data)
    {
        return $this->tx_service->transaction(function () use ($summit, $location_id, $banner_id, $data) {

            $location = $summit->getLocation($location_id);

            if (is_null($location)) {
                throw new EntityNotFoundException
                (
                    trans
                    (
                        'not_found_errors.LocationService.updateLocationBanner.LocationNotFound',
                        [
                            'summit_id' => $summit->getId(),
                            'location_id' => $location_id,
                        ]
                    )
                );
            }

            $banner = $location->getBannerById($banner_id);

            if (is_null($banner)) {
                throw new EntityNotFoundException
                (
                    trans
                    (
                        'not_found_errors.LocationService.updateLocationBanner.BannerNotFound',
                        [
                            'location_id' => $location_id,
                            'banner_id' => $banner_id,
                        ]
                    )
                );
            }

            $banner = SummitLocationBannerFactory::populate($summit, $location, $banner, $data);
            $location->validateBanner($banner);
            return $banner;
        });
    }

    /**
     * @param Summit $summit
     * @param int $location_id
     * @param int $banner_id
     * @return void
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function deleteLocationBanner(Summit $summit, $location_id, $banner_id)
    {
        return $this->tx_service->transaction(function () use ($summit, $location_id, $banner_id) {
            $location = $summit->getLocation($location_id);

            if (is_null($location)) {
                throw new EntityNotFoundException
                (
                    trans
                    (
                        'not_found_errors.LocationService.deleteLocationBanner.LocationNotFound',
                        [
                            'summit_id' => $summit->getId(),
                            'location_id' => $location_id,
                        ]
                    )
                );
            }

            $banner = $location->getBannerById($banner_id);

            if (is_null($banner)) {
                throw new EntityNotFoundException
                (
                    trans
                    (
                        'not_found_errors.LocationService.deleteLocationBanner.BannerNotFound',
                        [
                            'location_id' => $location_id,
                            'banner_id' => $banner_id,
                        ]
                    )
                );
            }

            $location->removeBanner($banner);
        });
    }

    /**
     * @param Summit $summit
     * @param int $location_id
     * @param array $metadata
     * @param $file
     * @return SummitLocationImage
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function addLocationMap(Summit $summit, $location_id, array $metadata, UploadedFile $file)
    {
        return $this->tx_service->transaction(function () use ($summit, $location_id, $metadata, $file) {
            $max_file_size = config('file_upload.max_file_upload_size');
            $allowed_extensions = ['png', 'jpg', 'jpeg', 'gif', 'pdf'];
            $location = $summit->getLocation($location_id);

            if (is_null($location)) {
                throw new EntityNotFoundException
                (
                    trans(
                        'not_found_errors.LocationService.addLocationMap.LocationNotFound',
                        [
                            'location_id' => $location_id,
                        ]
                    )
                );
            }

            if (!$location instanceof SummitGeoLocatedLocation) {
                throw new EntityNotFoundException
                (
                    trans(
                        'not_found_errors.LocationService.addLocationMap.LocationNotFound',
                        [
                            'location_id' => $location_id,
                        ]
                    )
                );
            }

            if (!in_array($file->extension(), $allowed_extensions)) {
                throw new ValidationException
                (
                    trans(
                        'validation_errors.LocationService.addLocationMap.FileNotAllowedExtension',
                        [
                            'allowed_extensions' => implode(", ", $allowed_extensions),
                        ]
                    )
                );
            }

            if ($file->getSize() > $max_file_size) {
                throw new ValidationException
                (
                    trans
                    (
                        'validation_errors.LocationService.addLocationMap.FileMaxSize',
                        [
                            'max_file_size' => (($max_file_size / 1024) / 1024)
                        ]
                    )
                );
            }

            $pic = $this->file_uploader->build($file, sprintf('summits/%s/locations/%s/maps', $location->getSummitId(), $location->getId()), true);
            $map = SummitLocationImageFactory::buildMap($metadata);
            $map->setPicture($pic);
            $location->addMap($map);
            return $map;
        });
    }

    /**
     * @param Summit $summit
     * @param int $location_id
     * @param int $map_id
     * @param array $metadata
     * @param UploadedFile|null $file
     * @return SummitLocationImage
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function updateLocationMap(Summit $summit, $location_id, $map_id, array $metadata, UploadedFile $file = null)
    {
        return $this->tx_service->transaction(function () use ($summit, $location_id, $map_id, $metadata, $file) {
            $max_file_size = config('file_upload.max_file_upload_size');
            $allowed_extensions = ['png', 'jpg', 'jpeg', 'gif', 'pdf'];
            $location = $summit->getLocation($location_id);

            if (is_null($location)) {
                throw new EntityNotFoundException
                (
                    trans(
                        'not_found_errors.LocationService.updateLocationMap.LocationNotFound',
                        [
                            'location_id' => $location_id,
                        ]
                    )
                );
            }

            if (!$location instanceof SummitGeoLocatedLocation) {
                throw new EntityNotFoundException
                (
                    trans(
                        'not_found_errors.LocationService.updateLocationMap.LocationNotFound',
                        [
                            'location_id' => $location_id,
                        ]
                    )
                );
            }

            $map = $location->getMap($map_id);

            if (is_null($map)) {
                throw new EntityNotFoundException
                (
                    trans(
                        'not_found_errors.LocationService.updateLocationMap.MapNotFound',
                        [
                            'map_id' => $map_id,
                            'location_id' => $location_id,
                        ]
                    )
                );
            }

            if (!is_null($file)) {
                if (!in_array($file->extension(), $allowed_extensions)) {
                    throw new ValidationException
                    (
                        trans(
                            'validation_errors.LocationService.updateLocationMap.FileNotAllowedExtension',
                            [
                                'allowed_extensions' => implode(", ", $allowed_extensions),
                            ]
                        )
                    );
                }

                if ($file->getSize() > $max_file_size) {
                    throw new ValidationException
                    (
                        trans
                        (
                            'validation_errors.LocationService.updateLocationMap.FileMaxSize',
                            [
                                'max_file_size' => (($max_file_size / 1024) / 1024)
                            ]
                        )
                    );
                }

                $pic = $this->file_uploader->build($file, sprintf('summits/%s/locations/%s/maps', $location->getSummitId(), $location->getId()), true);
                $map->setPicture($pic);
            }

            $map = SummitLocationImageFactory::populate($map, $metadata);

            if (isset($metadata['order']) && intval($metadata['order']) != $map->getOrder()) {
                // request to update order
                $location->recalculateMapOrder($map, intval($metadata['order']));
            }

            return $map;
        });
    }

    /**
     * @param Summit $summit
     * @param int $location_id
     * @param int $map_id
     * @return SummitAbstractLocation
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function deleteLocationMap(Summit $summit, $location_id, $map_id)
    {
        return $this->tx_service->transaction(function () use ($summit, $location_id, $map_id) {

            $location = $summit->getLocation($location_id);

            if (is_null($location)) {
                throw new EntityNotFoundException
                (
                    trans
                    (
                        'not_found_errors.LocationService.deleteLocationMap.LocationNotFound',
                        [
                            'summit_id' => $summit->getId(),
                            'location_id' => $location_id,
                        ]
                    )
                );
            }

            if (!$location instanceof SummitGeoLocatedLocation) {
                throw new EntityNotFoundException
                (
                    trans
                    (
                        'not_found_errors.LocationService.deleteLocationMap.LocationNotFound',
                        [
                            'summit_id' => $summit->getId(),
                            'location_id' => $location_id,
                        ]
                    )
                );
            }

            $map = $location->getMap($map_id);

            if (is_null($map)) {
                throw new EntityNotFoundException
                (
                    trans
                    (
                        'not_found_errors.LocationService.deleteLocationMap.MapNotFound',
                        [
                            'location_id' => $location_id,
                            'map_id' => $map_id,
                        ]
                    )
                );
            }

            $location->removeMap($map);

        });
    }

    /**
     * @param Summit $summit
     * @param int $location_id
     * @param array $metadata
     * @param $file
     * @return SummitLocationImage
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function addLocationImage(Summit $summit, $location_id, array $metadata, UploadedFile $file)
    {
        $image = $this->tx_service->transaction(function () use ($summit, $location_id, $metadata, $file) {
            $max_file_size = config('file_upload.max_file_upload_size');
            $allowed_extensions = ['png', 'jpg', 'jpeg', 'gif', 'pdf'];
            $location = $summit->getLocation($location_id);

            if (is_null($location)) {
                throw new EntityNotFoundException
                (
                    trans(
                        'not_found_errors.LocationService.addLocationImage.LocationNotFound',
                        [
                            'location_id' => $location_id,
                        ]
                    )
                );
            }

            if (!$location instanceof SummitGeoLocatedLocation) {
                throw new EntityNotFoundException
                (
                    trans(
                        'not_found_errors.LocationService.addLocationImage.LocationNotFound',
                        [
                            'location_id' => $location_id,
                        ]
                    )
                );
            }

            if (!in_array($file->extension(), $allowed_extensions)) {
                throw new ValidationException
                (
                    trans(
                        'validation_errors.LocationService.addLocationImage.FileNotAllowedExtension',
                        [
                            'allowed_extensions' => implode(", ", $allowed_extensions),
                        ]
                    )
                );
            }

            if ($file->getSize() > $max_file_size) {
                throw new ValidationException
                (
                    trans
                    (
                        'validation_errors.LocationService.addLocationImage.FileMaxSize',
                        [
                            'max_file_size' => (($max_file_size / 1024) / 1024)
                        ]
                    )
                );
            }

            $pic = $this->file_uploader->build($file, sprintf('summits/%s/locations/%s/images', $location->getSummitId(), $location->getId()), true);
            $image = SummitLocationImageFactory::buildImage($metadata);
            $image->setPicture($pic);
            $location->addImage($image);
            return $image;
        });

        return $image;
    }

    /**
     * @param Summit $summit
     * @param int $location_id
     * @param int $image_id
     * @param array $metadata
     * @param UploadedFile|null $file
     * @return SummitLocationImage
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function updateLocationImage(Summit $summit, $location_id, $image_id, array $metadata, UploadedFile $file = null)
    {
        return $this->tx_service->transaction(function () use ($summit, $location_id, $image_id, $metadata, $file) {
            $max_file_size = config('file_upload.max_file_upload_size');
            $allowed_extensions = ['png', 'jpg', 'jpeg', 'gif', 'pdf'];
            $location = $summit->getLocation($location_id);

            if (is_null($location)) {
                throw new EntityNotFoundException
                (
                    trans(
                        'not_found_errors.LocationService.updateLocationImage.LocationNotFound',
                        [
                            'location_id' => $location_id,
                        ]
                    )
                );
            }

            if (!$location instanceof SummitGeoLocatedLocation) {
                throw new EntityNotFoundException
                (
                    trans(
                        'not_found_errors.LocationService.updateLocationImage.LocationNotFound',
                        [
                            'location_id' => $location_id,
                        ]
                    )
                );
            }

            $image = $location->getImage($image_id);

            if (is_null($image)) {
                throw new EntityNotFoundException
                (
                    trans(
                        'not_found_errors.LocationService.updateLocationImage.ImageNotFound',
                        [
                            'image_id' => $image,
                            'location_id' => $location_id,
                        ]
                    )
                );
            }

            if (!is_null($file)) {
                if (!in_array($file->extension(), $allowed_extensions)) {
                    throw new ValidationException
                    (
                        trans(
                            'validation_errors.LocationService.updateLocationImage.FileNotAllowedExtension',
                            [
                                'allowed_extensions' => implode(", ", $allowed_extensions),
                            ]
                        )
                    );
                }

                if ($file->getSize() > $max_file_size) {
                    throw new ValidationException
                    (
                        trans
                        (
                            'validation_errors.LocationService.updateLocationImage.FileMaxSize',
                            [
                                'max_file_size' => (($max_file_size / 1024) / 1024)
                            ]
                        )
                    );
                }

                $pic = $this->file_uploader->build($file, sprintf('summits/%s/locations/%s/images', $location->getSummitId(), $location->getId()), true);
                $image->setPicture($pic);
            }

            $image = SummitLocationImageFactory::populate($image, $metadata);

            if (isset($metadata['order']) && intval($metadata['order']) != $image->getOrder()) {
                // request to update order
                $location->recalculateImageOrder($image, intval($metadata['order']));
            }

            return $image;
        });
    }

    /**
     * @param Summit $summit
     * @param int $location_id
     * @param int $image_id
     * @return SummitAbstractLocation
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function deleteLocationImage(Summit $summit, $location_id, $image_id)
    {
        return $this->tx_service->transaction(function () use ($summit, $location_id, $image_id) {

            $location = $summit->getLocation($location_id);

            if (is_null($location)) {
                throw new EntityNotFoundException
                (
                    trans
                    (
                        'not_found_errors.LocationService.deleteLocationImage.LocationNotFound',
                        [
                            'summit_id' => $summit->getId(),
                            'location_id' => $location_id,
                        ]
                    )
                );
            }

            if (!$location instanceof SummitGeoLocatedLocation) {
                throw new EntityNotFoundException
                (
                    trans
                    (
                        'not_found_errors.LocationService.deleteLocationImage.LocationNotFound',
                        [
                            'summit_id' => $summit->getId(),
                            'location_id' => $location_id,
                        ]
                    )
                );
            }

            $image = $location->getImage($image_id);

            if (is_null($image)) {
                throw new EntityNotFoundException
                (
                    trans
                    (
                        'not_found_errors.LocationService.deleteLocationImage.ImageNotFound',
                        [
                            'location_id' => $location_id,
                            'image_id' => $image_id,
                        ]
                    )
                );
            }

            $location->removeImage($image);

        });
    }

    /**
     * @param Summit $summit
     * @param int $room_id
     * @param array $payload
     * @return SummitRoomReservation
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function addBookableRoomReservation(Summit $summit, int $room_id, array $payload): SummitRoomReservation
    {
        $reservation = $this->tx_service->transaction(function () use ($summit, $room_id, $payload) {

            Log::debug
            (
                sprintf
                (
                    "SummitLocationService::addBookableRoomReservation summit %s room_id %s payload %s",
                    $summit->getId(),
                    $room_id,
                    json_encode($payload)
                )
            );

            $payment_gateway = $summit->getPaymentGateWayPerApp
            (
                IPaymentConstants::ApplicationTypeBookableRooms,
                $this->default_payment_gateway_strategy
            );

            if(is_null($payment_gateway))
                throw new ValidationException
                (
                    sprintf
                    (
                        "Payment configuration is not set for summit %s.",
                        $summit->getId()
                    )
                );

            $room = $this->location_repository->getByIdExclusiveLock($room_id);

            if (!$room instanceof SummitBookableVenueRoom) {
                throw new EntityNotFoundException("Room not found.");
            }

            $owner_id = $payload["owner_id"];

            $owner = $this->member_repository->getById($owner_id);

            if (!$owner instanceof Member){
                throw new EntityNotFoundException('Member not found.');
            }

            if ($owner->getReservationsCountBySummit($summit) >= $summit->getMeetingRoomBookingMaxAllowed())
                throw new ValidationException
                (
                    sprintf
                    (
                        "Member %s already reached max. quantity of reservations (%s).",
                        $owner->getId(),
                        $summit->getMeetingRoomBookingMaxAllowed()
                    )
                );

            $payload['owner'] = $owner;

            $currency = trim($payload['currency']);

            if ($room->getCurrency() != $currency) {
                throw new ValidationException
                (
                    sprintf
                    (
                        "Currency set %s is not allowed for room %s.",
                        $currency,
                        $room->getId()
                    )
                );
            }

            if(!$room->isFree()) {
                $amount = intval($payload['amount']);

                if ($room->getTimeSlotCost() != $amount) {
                    throw new ValidationException
                    (
                        sprintf
                        (
                            "Amount set %s does not match with time slot cost %s for room %s",
                            $amount,
                            $room->getTimeSlotCost(),
                            $room->getId()
                        )
                    );
                }
            }

            $reservation = SummitRoomReservationFactory::build($summit, $payload);

            $room->addReservation($reservation);

            if($room->isFree()) {
                Log::debug
                (
                    sprintf
                    (
                        "SummitLocationService::addBookableRoomReservation room %s is free, marked as paid...",
                        $room->getId()
                    )
                );

                $reservation->setPaid();
                return $reservation;
            }

            $result = $payment_gateway->generatePayment
                (
                    [
                        "amount" => $reservation->getAmount(),
                        "currency" => $reservation->getCurrency(),
                        "receipt_email" => $reservation->getOwner()->getEmail(),
                        "metadata" => [
                            "type" => IPaymentConstants::ApplicationTypeBookableRooms,
                            "room_id" => $room->getId(),
                            "summit_id" => $summit->getId(),
                        ]
                    ]
                );

            if (!isset($result['cart_id']))
                throw new ValidationException("Payment gateway error.");

            if (!isset($result['client_token']))
                throw new ValidationException("Payment gateway error.");

            $reservation->setPaymentGatewayCartId($result['cart_id']);
            $reservation->setPaymentGatewayClientToken($result['client_token']);

            return $reservation;
        });

        Event::dispatch(new CreatedBookableRoomReservation($reservation->getId()));

        return $reservation;
    }

    /**
     * @param Summit $summit
     * @param int $room_id
     * @param array $payload
     * @return SummitRoomReservation
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function addOfflineBookableRoomReservation(Summit $summit, int $room_id, array $payload): SummitRoomReservation
    {
        $reservation = $this->tx_service->transaction(function () use ($summit, $room_id, $payload) {

            Log::debug
            (
                sprintf
                (
                    "SummitLocationService::addOfflineBookableRoomReservation summit %s room_id %s payload %s",
                    $summit->getId(),
                    $room_id,
                    json_encode($payload)
                )
            );

            $room = $this->location_repository->getByIdExclusiveLock($room_id);


            if (!$room instanceof SummitBookableVenueRoom) {
                throw new EntityNotFoundException("Room not found.");
            }

            $owner_id = $payload["owner_id"];

            $owner = $this->member_repository->getById($owner_id);

            if (!$owner instanceof Member) {
                throw new EntityNotFoundException('Member not found.');
            }

            if ($owner->getReservationsCountBySummit($summit) >= $summit->getMeetingRoomBookingMaxAllowed())
                throw new ValidationException
                (
                    sprintf
                    (
                        "Member %s already reached max. quantity of reservations (%s).",
                        $owner->getId(),
                        $summit->getMeetingRoomBookingMaxAllowed()
                    )
                );

            $payload['owner'] = $owner;

            $currency = trim($payload['currency']);

            if ($room->getCurrency() != $currency) {
                throw new ValidationException
                (
                    sprintf
                    (
                        "Currency set %s is not allowed for room %s.",
                        $currency,
                        $room->getId()
                    )
                );
            }

            if(!$room->isFree()) {
                $amount = intval($payload['amount']);

                if ($room->getTimeSlotCost() != $amount) {
                    throw new ValidationException
                    (
                        sprintf
                        (
                            "Amount set %s does not match with time slot cost %s for room %s",
                            $amount,
                            $room->getTimeSlotCost(),
                            $room->getId()
                        )
                    );
                }
            }

            $reservation = SummitRoomReservationFactory::build($summit, $payload);
            $reservation->markAsOffline();
            $room->addReservation($reservation);
            $reservation->setPaid();
            return $reservation;
        });

        Event::dispatch(new CreatedBookableRoomReservation($reservation->getId()));

        return $reservation;
    }

    /**
     * @param array $payload
     * @param Summit|null $summit
     * @throws \Exception
     */
    public function processPayment(array $payload, ?Summit $summit = null): void
    {
        $this->tx_service->transaction(function () use ($summit, $payload) {

            Log::debug(sprintf("SummitLocationService::processPayment cart_id %s", $payload['cart_id']));

            $reservation = $this->reservation_repository->getByPaymentGatewayCartIdExclusiveLock($payload['cart_id']);

            if (is_null($reservation)) {
                throw new EntityNotFoundException(sprintf("There is no reservation with cart_id %s.", $payload['cart_id']));
            }

            if(!is_null($summit) && $reservation->getRoom()->getSummitId() != $summit->getId()){
                throw new EntityNotFoundException(sprintf("There is no reservation with cart_id %s.", $payload['cart_id']));
            }

            $summit = $reservation->getRoom()->getSummit();

            $payment_gateway = $summit->getPaymentGateWayPerApp
            (
                IPaymentConstants::ApplicationTypeBookableRooms,
                $this->default_payment_gateway_strategy
            );

            if(is_null($payment_gateway)){
                throw new ValidationException(sprintf("Payment configuration is not set for summit %s", $summit->getId()));
            }

            if ($payment_gateway->isSuccessFullPayment($payload)) {
                Log::debug("SummitLocationService::processPayment: payment is successful");
                $reservation->setPaid();
                return;
            }

            $reservation->setPaymentError($payment_gateway->getPaymentError($payload));
        });
    }

    /**
     * @param Summit $summit
     * @param Member $owner
     * @param int $reservation_id
     * @return SummitRoomReservation
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function cancelReservation(Summit $summit, Member $owner, int $reservation_id): SummitRoomReservation
    {
        return $this->tx_service->transaction(function () use ($summit, $owner, $reservation_id) {

            $reservation = $owner->getReservationById($reservation_id);

            if (is_null($reservation)) {
                throw new EntityNotFoundException();
            }

            if ($reservation->getRoom()->getSummitId() != $summit->getId()) {
                throw new EntityNotFoundException();
            }

            if($reservation->isFree()){
                // just cancel it
                $reservation->cancel();
                return $reservation;
            }

            if ($reservation->getStatus() == SummitRoomReservation::ReservedStatus)
                throw new ValidationException("Can not request a refund on a reserved booking.");

            if ($reservation->getStatus() == SummitRoomReservation::RequestedRefundStatus ||
                $reservation->getStatus() == SummitRoomReservation::RefundedStatus
            )
                throw new ValidationException("Can not request a refund on an already refunded booking.");

            $reservation->requestRefund();

            return $reservation;
        });
    }

    /**
     * @param SummitBookableVenueRoom $room
     * @param int $reservation_id
     * @param int $amount
     * @return SummitRoomReservation
     * @throws \Exception
     */
    public function refundReservation(SummitBookableVenueRoom $room, int $reservation_id, int $amount): SummitRoomReservation
    {
        return $this->tx_service->transaction(function () use ($room, $reservation_id, $amount) {

            Log::debug
            (
                sprintf
                (
                    "SummitLocationService::refundReservation room %s reservation_id %s amount %s",
                    $room->getId(),
                    $reservation_id,
                    $amount
                )
            );

            $reservation = $room->getReservationById($reservation_id);

            if (!$reservation instanceof SummitRoomReservation) {
                throw new EntityNotFoundException(sprintf("Reservation %s not found.", $reservation_id));
            }

            if($reservation->isFree()){
                throw new ValidationException("Can not refund a free booking.");
            }

            $summit = $room->getSummit();
            $payment_gateway = $summit->getPaymentGateWayPerApp
            (
                IPaymentConstants::ApplicationTypeBookableRooms,
                $this->default_payment_gateway_strategy
            );

            if(is_null($payment_gateway))
                throw new ValidationException(sprintf("Payment configuration is not set for summit %s.", $summit->getId()));

            $status = $reservation->getStatus();
            $validStatuses = [SummitRoomReservation::RequestedRefundStatus, SummitRoomReservation::PaidStatus];
            if (!in_array($status, $validStatuses))
                throw new ValidationException
                (
                    sprintf
                    (
                        "Can not request a refund on a %s booking.",
                        $status
                    )
                );

            if ($amount <= 0) {
                throw new ValidationException("Can not refund an amount lower than zero.");
            }

            $reservation->refund($amount);

            try {
                $payment_gateway->refundPayment($reservation->getPaymentGatewayCartId(), $amount, $reservation->getCurrency());
            } catch (\Exception $ex) {
                throw new ValidationException($ex->getMessage());
            }

            return $reservation;
        });
    }

    /**
     * @param Summit $summit
     * @param $venue_id
     * @param array $data
     * @return SummitBookableVenueRoom
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function addVenueBookableRoom(Summit $summit, $venue_id, array $data)
    {
        return $this->tx_service->transaction(function () use ($summit, $venue_id, $data) {

            if (isset($data['name'])) {
                $old_location = $summit->getLocationByName(trim($data['name']));

                if (!is_null($old_location)) {
                    throw new ValidationException
                    (
                        trans
                        (
                            'validation_errors.LocationService.addVenueRoom.LocationNameAlreadyExists',
                            [
                                'summit_id' => $summit->getId()
                            ]
                        )
                    );
                }
            }

            $venue = $summit->getLocation($venue_id);

            if (is_null($venue)) {
                throw new EntityNotFoundException
                (
                    trans
                    (
                        'not_found_errors.LocationService.addVenueRoom.VenueNotFound',
                        [
                            'summit_id' => $summit->getId(),
                            'venue_id' => $venue_id,
                        ]
                    )
                );
            }

            if (!$venue instanceof SummitVenue) {
                throw new EntityNotFoundException
                (
                    trans
                    (
                        'not_found_errors.LocationService.addVenueRoom.VenueNotFound',
                        [
                            'summit_id' => $summit->getId(),
                            'venue_id' => $venue_id,
                        ]
                    )
                );
            }

            $data['class_name'] = SummitBookableVenueRoom::ClassName;
            $room = SummitLocationFactory::build($data);

            if (is_null($room)) {
                throw new ValidationException
                (
                    trans
                    (
                        'validation_errors.LocationService.addVenueRoom.InvalidClassName'
                    )
                );
            }

            if (isset($data['floor_id'])) {
                $floor_id = intval($data['floor_id']);
                $floor = $venue->getFloor($floor_id);

                if (is_null($floor)) {
                    throw new EntityNotFoundException
                    (
                        trans
                        (
                            'not_found_errors.LocationService.addVenueRoom.FloorNotFound',
                            [
                                'floor_id' => $floor_id,
                                'venue_id' => $venue_id
                            ]
                        )
                    );
                }

                $floor->addRoom($room);
            }

            $summit->addLocation($room);
            $venue->addRoom($room);

            return $room;
        });
    }

    /**
     * @param Summit $summit
     * @param int $venue_id
     * @param int $room_id
     * @param array $data
     * @return SummitBookableVenueRoom
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function updateVenueBookableRoom(Summit $summit, $venue_id, $room_id, array $data)
    {
        return $this->tx_service->transaction(function () use ($summit, $venue_id, $room_id, $data) {

            if (isset($data['name'])) {
                $old_location = $summit->getLocationByName(trim($data['name']));

                if (!is_null($old_location) && $old_location->getId() != $room_id) {
                    throw new ValidationException
                    (
                        trans
                        (
                            'validation_errors.LocationService.updateVenueRoom.LocationNameAlreadyExists',
                            [
                                'summit_id' => $summit->getId()
                            ]
                        )
                    );
                }
            }

            $venue = $summit->getLocation($venue_id);

            if (is_null($venue)) {
                throw new EntityNotFoundException
                (
                    trans
                    (
                        'not_found_errors.LocationService.updateVenueRoom.VenueNotFound',
                        [
                            'summit_id' => $summit->getId(),
                            'venue_id' => $venue_id,
                        ]
                    )
                );
            }

            if (!$venue instanceof SummitVenue) {
                throw new EntityNotFoundException
                (
                    trans
                    (
                        'not_found_errors.LocationService.updateVenueRoom.VenueNotFound',
                        [
                            'summit_id' => $summit->getId(),
                            'venue_id' => $venue_id,
                        ]
                    )
                );
            }

            $room = $summit->getLocation($room_id);
            if (is_null($room)) {
                throw new EntityNotFoundException
                (
                    trans
                    (
                        'not_found_errors.LocationService.updateVenueRoom.RoomNotFound',
                        [
                            'summit_id' => $summit->getId(),
                            'venue_id' => $venue_id,
                            'room_id' => $room_id,
                        ]
                    )
                );
            }

            if (!$room instanceof SummitBookableVenueRoom) {
                throw new EntityNotFoundException
                (
                    trans
                    (
                        'not_found_errors.LocationService.updateVenueRoom.RoomNotFound',
                        [
                            'summit_id' => $summit->getId(),
                            'venue_id' => $venue_id,
                            'room_id' => $room_id,
                        ]
                    )
                );
            }

            SummitLocationFactory::populate($room, $data);
            $floor = null;

            if (isset($data['floor_id'])) {
                $old_floor_id = intval($room->getFloorId());
                $new_floor_id = intval($data['floor_id']);
                if ($old_floor_id != $new_floor_id) {
                    $floor = $venue->getFloor($new_floor_id);
                    if (is_null($floor)) {
                        throw new EntityNotFoundException
                        (
                            trans
                            (
                                'not_found_errors.LocationService.updateVenueRoom.FloorNotFound',
                                [
                                    'floor_id' => $new_floor_id,
                                    'venue_id' => $venue_id
                                ]
                            )
                        );
                    }
                    if ($old_floor_id > 0) {
                        // remove from old floor
                        $room->getFloor()->removeRoom($room);

                    }
                    $floor->addRoom($room);
                }
            }

            // request to update order
            if (isset($data['order']) && intval($data['order']) != $room->getOrder()) {

                if (!is_null($floor)) {
                    $floor->recalculateRoomsOrder($room, intval($data['order']));
                } else {
                    $venue->recalculateRoomsOrder($room, intval($data['order']));
                }
            }

            return $room;
        });

    }

    /**
     * @param Summit $summit
     * @param int $venue_id
     * @param int $room_id
     * @return void
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function deleteVenueBookableRoom(Summit $summit, $venue_id, $room_id)
    {
        return $this->tx_service->transaction(function () use ($summit, $venue_id, $room_id) {

            $venue = $summit->getLocation($venue_id);

            if (is_null($venue)) {
                throw new EntityNotFoundException
                (
                    "venue not found"
                );
            }

            if (!$venue instanceof SummitVenue) {
                throw new EntityNotFoundException
                (
                    "venue not found"
                );
            }

            $room = $venue->getRoom($room_id);

            if (is_null($room)) {
                throw new EntityNotFoundException("room not found");
            }

            $venue->removeRoom($room);

            if ($room->hasFloor()) {
                $floor = $room->getFloor();
                $floor->removeRoom($room);
            }

        });
    }

    /**
     * @param Summit $summit
     * @param int $venue_id
     * @param int $room_id
     * @param int $attribute_id
     * @return SummitBookableVenueRoom
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function addVenueBookableRoomAttribute(Summit $summit, int $venue_id, int $room_id, int $attribute_id): SummitBookableVenueRoom
    {
        return $this->tx_service->transaction(function () use ($summit, $venue_id, $room_id, $attribute_id) {
            $venue = $summit->getLocation($venue_id);

            if (is_null($venue)) {
                throw new EntityNotFoundException("venue not found");
            }

            if (!$venue instanceof SummitVenue) {
                throw new EntityNotFoundException("venue not found");
            }

            $room = $venue->getRoom($room_id);

            if (is_null($room) || !$room instanceof SummitBookableVenueRoom) {
                throw new EntityNotFoundException("room not found");
            }

            $attribute = $summit->getMeetingBookingRoomAllowedAttributeValueById($attribute_id);

            if (is_null($attribute)) {
                throw new EntityNotFoundException("attribute not found");
            }

            $room->addAttribute($attribute);

            return $room;

        });
    }

    /**
     * @param Summit $summit
     * @param int $venue_id
     * @param int $room_id
     * @param int $attribute_id
     * @return SummitBookableVenueRoom
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function deleteVenueBookableRoomAttribute(Summit $summit, int $venue_id, int $room_id, int $attribute_id): SummitBookableVenueRoom
    {
        return $this->tx_service->transaction(function () use ($summit, $venue_id, $room_id, $attribute_id) {
            $venue = $summit->getLocation($venue_id);

            if (is_null($venue)) {
                throw new EntityNotFoundException
                ("venue not found");
            }

            if (!$venue instanceof SummitVenue) {
                throw new EntityNotFoundException
                ("venue not found");
            }

            $room = $venue->getRoom($room_id);

            if (is_null($room) || !$room instanceof SummitBookableVenueRoom) {
                throw new EntityNotFoundException
                ("room not found");
            }

            $attribute = $summit->getMeetingBookingRoomAllowedAttributeValueById($attribute_id);

            if (is_null($attribute)) {
                throw new EntityNotFoundException
                ("attribute not found");
            }

            $room->removeAttribute($attribute);

            return $room;
        });
    }

    /**
     * @param Summit $summit
     * @param int $venue_id
     * @param int $room_id
     * @param UploadedFile $file
     * @param int $max_file_size
     * @return mixed|File
     * @throws \Exception
     */
    public function addRoomImage(Summit $summit, int $venue_id, $room_id, UploadedFile $file, $max_file_size = 10485760)
    {
        return $this->tx_service->transaction(function () use ($summit, $venue_id, $room_id, $file, $max_file_size) {

            $allowed_extensions = ['png', 'jpg', 'jpeg', 'gif', 'pdf'];

            $room = $summit->getLocation($room_id);
            if (is_null($room)) {
                throw new EntityNotFoundException
                (
                    'room not found'
                );
            }

            if (!$room instanceof SummitVenueRoom) {
                throw new EntityNotFoundException
                (
                    'room not found'
                );
            }

            if (!in_array($file->extension(), $allowed_extensions)) {
                throw new ValidationException("file does not has a valid extension ('png','jpg','jpeg','gif','pdf').");
            }

            if ($file->getSize() > $max_file_size) {
                throw new ValidationException(sprintf("file exceeds max_file_size (%s MB).", ($max_file_size / 1024) / 1024));
            }

            $image = $this->file_uploader->build($file, sprintf('summits/%s/locations/%s/rooms', $summit->getId(), $venue_id), true);
            $room->setImage($image);

            return $image;
        });
    }

    /**
     * @param Summit $summit
     * @param int $venue_id
     * @param int $room_id
     * @throws EntityNotFoundException
     * @throws ValidationException
     * @return SummitVenueRoom
     */
    public function removeRoomImage(Summit $summit, int $venue_id, int $room_id): SummitVenueRoom
    {
        return $this->tx_service->transaction(function () use ($summit, $venue_id, $room_id) {


            $room = $summit->getLocation($room_id);
            if (is_null($room)) {
                throw new EntityNotFoundException
                (
                    'room not found'
                );
            }

            if (!$room instanceof SummitVenueRoom) {
                throw new EntityNotFoundException
                (
                    'room not found'
                );
            }

            if (!$room->hasImage())
                throw new ValidationException("room has no image set");

            $room->clearImage();

            return $room;
        });
    }

    /**
     * @param int $minutes
     */
    public function revokeBookableRoomsReservedOlderThanNMinutes(int $minutes): void
    {
        // this is done in this way to avoid db lock contentions
        $reservations = $this->tx_service->transaction(function () use ($minutes) {
            return $this->reservation_repository->getAllReservedOlderThanXMinutes($minutes);
        });

        foreach ($reservations as $reservation) {

            $this->tx_service->transaction(function () use ($reservation) {

                try {
                    $reservation = $this->reservation_repository->getByIdExclusiveLock($reservation->getId());
                    if (!$reservation instanceof SummitRoomReservation) return;

                    $summit = $reservation->getRoom()->getSummit();
                    $payment_gateway = $summit->getPaymentGateWayPerApp
                    (
                        IPaymentConstants::ApplicationTypeBookableRooms,
                        $this->default_payment_gateway_strategy
                    );

                    if(is_null($payment_gateway)){
                        Log::warning(sprintf("Payment configuration is not set for summit %s", $summit->getId()));
                        return;
                    }

                    Log::debug(sprintf("[SummitLocationService::revokeBookableRoomsReservedOlderThanNMinutes] cancelling reservation %s created at %s", $reservation->getId(), $reservation->getCreated()->format("Y-m-d h:i:sa")));
                    $status = $payment_gateway->getCartStatus($reservation->getPaymentGatewayCartId());
                    if(!is_null($status)) {
                        Log::debug(sprintf("[SummitLocationService::revokeBookableRoomsReservedOlderThanNMinutes] got status %s for reservation %s", $status, $reservation->getId()));
                        if (!$payment_gateway->canAbandon($status)) {
                            Log::warning(sprintf("[SummitLocationService::revokeBookableRoomsReservedOlderThanNMinutes] reservation %s created at %s can not be cancelled external status %s", $reservation->getId(), $reservation->getCreated()->format("Y-m-d h:i:sa"), $status));
                            if ($payment_gateway->isSucceeded($status)) {
                                Log::debug(sprintf("[SummitLocationService::revokeBookableRoomsReservedOlderThanNMinutes] set reservation %s as paid", $status, $reservation->getId()));
                                $reservation->setPaid();
                            }
                            return;
                        }

                        $payment_gateway->abandonCart($reservation->getPaymentGatewayCartId());
                    }

                    $reservation->cancel();

                    Log::debug(sprintf("[SummitLocationService::revokeBookableRoomsReservedOlderThanNMinutes] reservation %s created at %s canceled", $reservation->getId(), $reservation->getCreated()->format("Y-m-d h:i:sa")));
                } catch (\Exception $ex) {
                    Log::warning($ex);
                }

            });
        }
    }

    /**
     * @param Summit $summit
     * @param int $venue_id
     * @param int $floor_id
     * @param UploadedFile $file
     * @param int $max_file_size
     * @return mixed|File
     * @throws \Exception
     */
    public function addFloorImage(Summit $summit, int $venue_id, int $floor_id, UploadedFile $file, $max_file_size = 10485760)
    {
        return $this->tx_service->transaction(function () use ($summit, $venue_id, $floor_id, $file, $max_file_size) {

            $allowed_extensions = ['png', 'jpg', 'jpeg', 'gif', 'pdf'];

            $venue = $summit->getLocation($venue_id);

            if (is_null($venue)) {
                throw new EntityNotFoundException
                (
                    'room not found'
                );
            }

            if (!$venue instanceof SummitVenue) {
                throw new EntityNotFoundException
                (
                    'room not found'
                );
            }

            $floor = $venue->getFloor($floor_id);

            if (is_null($floor))
                throw new EntityNotFoundException
                (
                    'floor not found'
                );

            if (!in_array($file->extension(), $allowed_extensions)) {
                throw new ValidationException("file does not has a valid extension ('png','jpg','jpeg','gif','pdf').");
            }

            if ($file->getSize() > $max_file_size) {
                throw new ValidationException(sprintf("file exceeds max_file_size (%s MB).", ($max_file_size / 1024) / 1024));
            }

            $image = $this->file_uploader->build($file, sprintf('summits/%s/locations/%s/floors', $summit->getId(), $venue_id), true);
            $floor->setImage($image);

            return $image;
        });
    }

    /**
     * @param Summit $summit
     * @param int $venue_id
     * @param int $floor_id
     * @throws EntityNotFoundException
     * @throws ValidationException
     * @return SummitVenueFloor
     */
    public function removeFloorImage(Summit $summit, int $venue_id, int $floor_id): SummitVenueFloor
    {
        return $this->tx_service->transaction(function () use ($summit, $venue_id, $floor_id) {

            $venue = $summit->getLocation($venue_id);

            if (is_null($venue)) {
                throw new EntityNotFoundException
                (
                    'room not found'
                );
            }

            if (!$venue instanceof SummitVenue) {
                throw new EntityNotFoundException
                (
                    'room not found'
                );
            }

            $floor = $venue->getFloor($floor_id);

            if (is_null($floor))
                throw new EntityNotFoundException
                (
                    'floor not found'
                );

            if (!$floor->hasImage())
                throw new ValidationException("floor has no image set");

            $floor->clearImage();

            return $floor;
        });
    }

    /**
     * @param Summit $source_summit
     * @param Summit $target_summit
     * @return Summit
     * @throws \Exception
     */
    public function copySummitLocations(Summit $source_summit, Summit $target_summit): Summit
    {
        return $this->tx_service->transaction(function () use ($source_summit, $target_summit) {

            $target_summit->copyLocationsFrom($source_summit);

            return $target_summit;
        });
    }


    /**
     * @param Summit $summit
     * @param SummitBookableVenueRoom $room
     * @param int $reservation_id
     * @return void
     * @throws \Exception
     */
    public function deleteReservation(Summit $summit, SummitBookableVenueRoom $room, int $reservation_id):void
    {
         $this->tx_service->transaction(function () use ($summit, $room, $reservation_id) {

            $reservation = $room->getReservationById($reservation_id);

            if (is_null($reservation)) {
                throw new EntityNotFoundException("Reservation not found");
            }

            if(!$reservation->isFree() && $reservation->isPaid()){
                throw new ValidationException("You must emit a refund before to cancel the reservation.");
            }

             $reservation->cancel();

        });
    }
}