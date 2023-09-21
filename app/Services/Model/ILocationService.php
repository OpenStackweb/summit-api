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
use App\Models\Foundation\Summit\Locations\Banners\SummitLocationBanner;
use models\main\File;
use models\main\Member;
use models\summit\SummitBookableVenueRoom;
use models\summit\SummitLocationImage;
use models\summit\SummitRoomReservation;
use models\summit\SummitVenueRoom;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\summit\Summit;
use models\summit\SummitAbstractLocation;
use models\summit\SummitVenueFloor;
use Illuminate\Http\UploadedFile;
/**
 * Interface ILocationService
 * @package App\Services\Model
 */
interface ILocationService extends IProcessPaymentService
{
    /**
     * @param Summit $summit
     * @param array $data
     * @return SummitAbstractLocation
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function addLocation(Summit $summit, array $data);

    /**
     * @param Summit $summit
     * @param int $location_id
     * @param array $data
     * @return SummitAbstractLocation
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function updateLocation(Summit $summit, $location_id, array $data);

    /**
     * @param Summit $summit
     * @param int $location_id
     * @return void
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function deleteLocation(Summit $summit, $location_id);

    /**
     * @param Summit $summit
     * @param int $venue_id
     * @param array $data
     * @return SummitVenueFloor
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function addVenueFloor(Summit $summit, $venue_id, array $data);

    /**
     * @param Summit $summit
     * @param int $venue_id
     * @param int $floor_id
     * @param array $data
     * @return SummitVenueFloor
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function updateVenueFloor(Summit $summit, $venue_id, $floor_id, array $data);

    /**
     * @param Summit $summit
     * @param int $venue_id
     * @param int $floor_id
     * @return void
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function deleteVenueFloor(Summit $summit, $venue_id, $floor_id);

    /**
     * @param Summit $summit
     * @param int $venue_id
     * @param int $room_id
     * @return void
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function deleteVenueRoom(Summit $summit, $venue_id, $room_id);

    /**
     * @param Summit $summit
     * @param $venue_id
     * @param array $data
     * @return SummitVenueRoom
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function addVenueRoom(Summit $summit, $venue_id, array $data);

    /**
     * @param Summit $summit
     * @param int $venue_id
     * @param int $room_id
     * @param array $data
     * @return SummitVenueRoom
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function updateVenueRoom(Summit $summit, $venue_id, $room_id, array $data);

    /**
     * @param Summit $summit
     * @param int $location_id
     * @param array $data
     * @return SummitLocationBanner
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function addLocationBanner(Summit $summit, $location_id, array $data);

    /**
     * @param Summit $summit
     * @param int $location_id
     * @param int $banner_id
     * @param array $data
     * @return SummitLocationBanner
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function updateLocationBanner(Summit $summit, $location_id, $banner_id, array $data);

    /**
     * @param Summit $summit
     * @param int $location_id
     * @param int $banner_id
     * @return void
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function deleteLocationBanner(Summit $summit, $location_id, $banner_id);

    /**
     * @param Summit $summit
     * @param int $location_id
     * @param array $metadata
     * @param $file
     * @return SummitLocationImage
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function addLocationMap(Summit $summit, $location_id, array $metadata, UploadedFile $file);

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
    public function updateLocationMap(Summit $summit, $location_id, $map_id, array $metadata, UploadedFile $file = null);

    /**
     * @param Summit $summit
     * @param int $location_id
     * @param int $map_id
     * @return SummitAbstractLocation
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function deleteLocationMap(Summit $summit, $location_id, $map_id);

    /**
     * @param Summit $summit
     * @param int $location_id
     * @param array $metadata
     * @param $file
     * @return SummitLocationImage
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function addLocationImage(Summit $summit, $location_id, array $metadata, UploadedFile $file);

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
    public function updateLocationImage(Summit $summit, $location_id, $image_id, array $metadata, UploadedFile $file = null);

    /**
     * @param Summit $summit
     * @param int $location_id
     * @param int $image_id
     * @return SummitAbstractLocation
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function deleteLocationImage(Summit $summit, $location_id, $image_id);

    /**
     * @param Summit $summit
     * @param int $room_id
     * @param array $payload
     * @return SummitRoomReservation
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function addBookableRoomReservation(Summit $summit, int $room_id, array $payload):SummitRoomReservation;

    /**
     * @param Summit $sumit
     * @param Member $owner
     * @param int $reservation_id
     * @return SummitRoomReservation
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function cancelReservation(Summit $sumit, Member $owner, int $reservation_id):SummitRoomReservation;

    /**
     * @param SummitBookableVenueRoom $room
     * @param int $reservation_id
     * @param int $amount
     * @return SummitRoomReservation
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function refundReservation(SummitBookableVenueRoom $room, int $reservation_id, int $amount):SummitRoomReservation;


        /**
     * @param Summit $summit
     * @param int $venue_id
     * @param int $room_id
     * @return void
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function deleteVenueBookableRoom(Summit $summit, $venue_id, $room_id);

    /**
     * @param Summit $summit
     * @param $venue_id
     * @param array $data
     * @return SummitVenueRoom
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function addVenueBookableRoom(Summit $summit, $venue_id, array $data);

    /**
     * @param Summit $summit
     * @param int $venue_id
     * @param int $room_id
     * @param array $data
     * @return SummitVenueRoom
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function updateVenueBookableRoom(Summit $summit, $venue_id, $room_id, array $data);

    /**
     * @param Summit $summit
     * @param int $venue_id
     * @param int $room_id
     * @param int $attribute_id
     * @return SummitBookableVenueRoom
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function addVenueBookableRoomAttribute(Summit $summit, int $venue_id, int $room_id, int $attribute_id):SummitBookableVenueRoom;

    /**
     * @param Summit $summit
     * @param int $venue_id
     * @param int $room_id
     * @param int $attribute_id
     * @return SummitBookableVenueRoom
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function deleteVenueBookableRoomAttribute(Summit $summit, int $venue_id, int $room_id, int $attribute_id):SummitBookableVenueRoom;

    /**
     * @param Summit $summit
     * @param int $venue_id
     * @param int $room_id
     * @param UploadedFile $file
     * @param int $max_file_size
     * @return mixed|File
     * @throws \Exception
     */
    public function addRoomImage(Summit $summit, int $venue_id, $room_id, UploadedFile $file, $max_file_size = 10485760);

    /**
     * @param Summit $summit
     * @param int $venue_id
     * @param int $room_id
     * @throws EntityNotFoundException
     * @throws ValidationException
     * @return SummitVenueRoom
     */
    public function removeRoomImage(Summit $summit, int $venue_id, int $room_id):SummitVenueRoom;

    /**
     * @param Summit $summit
     * @param int $venue_id
     * @param int $floor_id
     * @param UploadedFile $file
     * @param int $max_file_size
     * @return mixed|File
     * @throws \Exception
     */
    public function addFloorImage(Summit $summit, int $venue_id, int $floor_id, UploadedFile $file, $max_file_size = 10485760);

    /**
     * @param Summit $summit
     * @param int $venue_id
     * @param int $floor_id
     * @throws EntityNotFoundException
     * @throws ValidationException
     * @return SummitVenueFloor
     */
    public function removeFloorImage(Summit $summit, int $venue_id, int $floor_id):SummitVenueFloor;


    /**
     * @param int $minutes
     */
    public function revokeBookableRoomsReservedOlderThanNMinutes(int $minutes):void;

    /**
     * @param Summit $source_summit
     * @param Summit $target_summit
     * @return Summit
     * @throws \Exception
     */
    public function copySummitLocations(Summit $source_summit, Summit $target_summit): Summit;

    /**
     * @param Summit $summit
     * @param SummitBookableVenueRoom $room
     * @param int $reservation_id
     * @throws EntityNotFoundException
     * @return void
     */
    public function deleteReservation(Summit $summit, SummitBookableVenueRoom $room, int $reservation_id):void;
}