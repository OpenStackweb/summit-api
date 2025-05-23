<?php namespace Tests;
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

use App\Models\Foundation\Summit\Factories\SummitLocationFactory;
use  LaravelDoctrine\ORM\Facades\EntityManager;
use models\summit\SummitBookableVenueRoom;
use Mockery;
/**
 * Class OAuth2SummitLocationsApiTest
 */
final class OAuth2SummitLocationsApiTest extends ProtectedApiTestCase
{
    public function testGetFolder(){
        $service = \Illuminate\Support\Facades\App::make(\App\Services\Model\IFolderService::class);
        $folder  =    $service->findOrMake('summits/1/locations/292/maps');
    }

    use InsertSummitTestData;

    private static $bookable_room;

    protected function setUp():void
    {
        parent::setUp();
        self::insertSummitTestData();

        $data = [
            'name'            => 'test bookable room',
            'capacity'       =>  10,
            'description'    => 'test bookable room',
            'time_slot_cost' => 200,
            'currency'       => 'USD',
        ];

        $data['class_name'] = SummitBookableVenueRoom::ClassName;
        self::$bookable_room = SummitLocationFactory::build($data);

        self::$summit->addLocation(self::$bookable_room);
        self::$mainVenue->addRoom(self::$bookable_room);

        self::$em->persist(self::$summit);
        self::$em->flush();
    }

    protected function tearDown():void
    {
        self::clearSummitTestData();
        parent::tearDown();
    }

    public function testGetCurrentSummitLocations()
    {
        $params = [
            'id'       => 3589, //self::$summit->getId(),
            'page'     => 1,
            'per_page' => 5,
            'order'    => '-order'
        ];

        $response = $this->action
        (
            "GET",
            "OAuth2SummitLocationsApiController@getLocations",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);

        $locations = json_decode($content);
        $this->assertTrue(!is_null($locations));
    }

    public function testGetSummitLocationsOrderByName($summit_id = 22)
    {
        $params = [
            'id'       => $summit_id,
            'page'     => 1,
            'per_page' => 5,
            'order'    => 'name-'
        ];

        $response = $this->action
        (
            "GET",
            "OAuth2SummitLocationsApiController@getLocations",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);

        $locations = json_decode($content);
        $this->assertTrue(!is_null($locations));
    }

    public function testGetCurrentSummitLocationsMetadata($summit_id = 23)
    {
        $params = [
            'id' => $summit_id,
        ];

        $response = $this->action
        (
            "GET",
            "OAuth2SummitLocationsApiController@getMetadata",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);

        $metadata = json_decode($content);
        $this->assertTrue(!is_null($metadata));
    }

    public function testGetCurrentSummitLocationsByClassNameVenueORAirport($summit_id = 24)
    {
        $params = [
            'id'         => $summit_id,
            'page'       => 1,
            'per_page'   => 5,
            'filter'     => [
                'class_name=='.\models\summit\SummitVenue::ClassName.',class_name=='.\models\summit\SummitAirport::ClassName,
            ]
        ];

        $response = $this->action
        (
            "GET",
            "OAuth2SummitLocationsApiController@getLocations",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);

        $locations = json_decode($content);
        $this->assertTrue(!is_null($locations));
    }

    public function testGetCurrentSummitLocationsByClassHotels($summit_id = 25)
    {
        $params = [
            'id'         => $summit_id,
            'page'       => 1,
            'per_page'   => 100,
            'filter'     => [
                'class_name=='.\models\summit\SummitHotel::ClassName,
            ]
        ];

        $response = $this->action
        (
            "GET",
            "OAuth2SummitLocationsApiController@getLocations",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);

        $locations = json_decode($content);
        $this->assertTrue(!is_null($locations));
    }

    public function testGetCurrentSummitVenues()
    {
        $params = [
            'id' => 'current',
        ];

        $response = $this->action
        (
            "GET",
            "OAuth2SummitLocationsApiController@getVenues",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);

        $locations = json_decode($content);
        $this->assertTrue(!is_null($locations));
    }

    public function testGetCurrentSummitHotels()
    {
        $params = array
        (
            'id' => 'current',
        );

        $response = $this->action
        (
            "GET",
            "OAuth2SummitLocationsApiController@getHotels",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);

        $locations = json_decode($content);
        $this->assertTrue(!is_null($locations));
    }

    public function testGetCurrentSummitAirports()
    {
        $params = [
            'id' => 'current',
        ];

        $response = $this->action
        (
            "GET",
            "OAuth2SummitLocationsApiController@getAirports",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);

        $locations = json_decode($content);
        $this->assertTrue(!is_null($locations));
    }

    public function testGetCurrentSummitExternalLocations()
    {
        $params = [
            'id' => 'current',
        ];

        $response = $this->action
        (
            "GET",
            "OAuth2SummitLocationsApiController@getExternalLocations",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);

        $locations = json_decode($content);
        $this->assertTrue(!is_null($locations));
    }

    public function testGetCurrentSummitLocation()
    {
        $params =
        [
            'id' => 'current',
            'location_id' => self::$mainVenue->getId()
        ];

        $response = $this->action
        (
            "GET",
            "OAuth2SummitLocationsApiController@getLocation",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);

        $locations = json_decode($content);
        $this->assertTrue(!is_null($locations));
    }

    public function testCurrentSummitLocationEventsWithFilter()
    {
        $params = [
            'id'          => self::$summit->getId(),
            'page'        => 1,
            'per_page'    => 50,
            'location_id' => self::$mainVenue->getId(),
        ];

        $response = $this->action
        (
            "GET",
            "OAuth2SummitLocationsApiController@getLocationEvents",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);

        $page = json_decode($content);
        $this->assertTrue(!is_null($page));
        $this->assertNotEmpty($page->data);
    }

    public function testCurrentSummitPublishedLocationEventsWithFilter()
    {
        $params = [
            'id'          => self::$summit->getId(),
            'page'        => 1,
            'per_page'    => 50,
            'location_id' => self::$mainVenue->getId(),
            'filter' => [
                'start_date>='.(time())
            ]
        ];

        $response = $this->action
        (
            "GET",
            "OAuth2SummitLocationsApiController@getLocationPublishedEvents",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);

        $page = json_decode($content);
        $this->assertTrue(!is_null($page));
        $this->assertNotEmpty($page->data);
    }

    public function testCurrentSummitPublishedLocationTBAEvents()
    {
        $params = [
            'id'          => self::$summit->getId(),
            'location_id' => "tba",
        ];

        $response = $this->action
        (
            "GET",
            "OAuth2SummitLocationsApiController@getLocationPublishedEvents",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);

        $page = json_decode($content);
        $this->assertTrue(!is_null($page));
    }

    public function testAddLocationWithoutClassName(){

        $params = [
            'id' => self::$summit->getId(),
        ];

        $name       = str_random(16).'_location';
        $data = [
            'name'       => $name,
            'description' => 'test location',
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitLocationsApiController@addLocation",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(412);
    }

    public function testAddLocationVenue(){

        $params = [
            self::$summit->getId(),
        ];

        $name = str_random(16).'_location';

        $data = [
            'name'        => $name,
            'address_1'    => 'Nazar 612',
            'city'        => 'Lanus',
            'state'       => 'Buenos Aires',
            'country'     => 'AR',
            'class_name'  => \models\summit\SummitVenue::ClassName,
            'description' => 'test location'
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitLocationsApiController@addLocation",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);

        $location = json_decode($content);
        $this->assertTrue(!is_null($location));
        return $location;
    }

    /**
     * @param int $summit_id
     * @return mixed
     */
    public function testAddLocationVenueLatLng(){

        $params = [
            'id' => self::$summit->getId(),
        ];

        $name = str_random(16).'_location';

        $data = [
            'name'        => $name,
            'lat'         => '-34.6994795',
            'lng'         => '-58.3920795',
            'class_name'  => \models\summit\SummitVenue::ClassName,
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitLocationsApiController@addLocation",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);

        $location = json_decode($content);
        $this->assertTrue(!is_null($location));
        return $location;
    }

    /**
     * @param int $summit_id
     * @return mixed
     */
    public function testAddLocationVenueLatLngInvalid(){

        $params = [
            'id' => self::$summit->getId(),
        ];

        $name       = str_random(16).'_location';

        $data = [
            'name'        => $name,
            'lat'         => '-134.6994795',
            'lng'         => '-658.3920795',
            'class_name'  => \models\summit\SummitVenue::ClassName,
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitLocationsApiController@addLocation",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(412);
    }

    public function testAddLocationWithTimeRange(){

        $params = [
            'id' => self::$summit->getId(),
        ];

        $name = str_random(16).'_location';

        $data = [
            'name'        => $name,
            'address_1'    => 'Nazar 612',
            'city'        => 'Lanus',
            'state'       => 'Buenos Aires',
            'country'     => 'AR',
            'class_name'  => \models\summit\SummitVenue::ClassName,
            'description' => 'test location',
            'opening_hour' => 1300,
            'closing_hour' => 1900
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitLocationsApiController@addLocation",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);

        $location = json_decode($content);
        $this->assertTrue(!is_null($location));
        return $location;
    }

    /**
     * @param int $summit_id
     * @return mixed
     */
    public function testAddLocationHotelAddress(){

        $params = [
            'id' => self::$summit->getId(),
        ];

        $name       = str_random(16).'_hotel';

        $data = [
            'name'        => $name,
            'address_1'   => 'H. de Malvinas 1724',
            'city'        => 'Lanus Este',
            'state'       => 'Buenos Aires',
            'country'     => 'AR',
            'zip_code'    => '1824',
            'class_name'  => \models\summit\SummitHotel::ClassName,
            'hotel_type'  => \models\summit\SummitHotel::HotelTypePrimary,
            'capacity'    => 200
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitLocationsApiController@addLocation",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $location = json_decode($content);
        $this->assertTrue(!is_null($location));
        return $location;
    }

    public function testUpdateLocationHotelOrder($summit_id = 24){

        $hotel = $this->testAddLocationHotelAddress($summit_id);
        $new_order = 9;
        $params = [
            'id'          => $summit_id,
            'location_id' => $hotel->id
        ];

        $data = [
            'order' => $new_order,
            'class_name'  => \models\summit\SummitHotel::ClassName,
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitLocationsApiController@updateLocation",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $location = json_decode($content);
        $this->assertTrue(!is_null($location));
        $this->assertTrue($location->order == $new_order);
        return $location;
    }

    /**
     * @param int $summit_id
     * @return mixed
     */
    public function testUpdateExistingLocation(){

        $params = [
            'id'          => self::$summit->getId(),
            'location_id' => self::$mainVenue->getId()
        ];

        $data = [
            'class_name'  => \models\summit\SummitVenue::ClassName,
            'name' => 'Sydney Convention and Exhibition Centre Update!',
            'opening_hour' => 1200,
            'closing_hour' => 2100
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitLocationsApiController@updateLocation",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $location = json_decode($content);
        $this->assertTrue(!is_null($location));
        return $location;
    }

    /**
     * @param int $summit_id
     */
    public function testDeleteNewlyCreatedHotel($summit_id = 24){

        $hotel = $this->testAddLocationHotelAddress($summit_id);
        $params = [
            'id'          => $summit_id,
            'location_id' => $hotel->id
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitLocationsApiController@deleteLocation",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(204);
    }

    /**
     * @param int $summit_id
     * @param int $venue_id
     * @param int $number
     * @return mixed
     */
    public function testAddVenueFloor($summit_id = 23, $venue_id = 292, $number = null){

        $params = [
            'id'       => $summit_id,
            'venue_id' => $venue_id
        ];

        if(is_null($number))
            $number = rand(0,1000);

        $name       = str_random(16).'_floor';
        $data = [
           'name'        => $name,
           'description' => 'test floor',
           'number'      => $number
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitLocationsApiController@addVenueFloor",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $floor = json_decode($content);
        $this->assertTrue(!is_null($floor));
        return $floor;
    }

    /**
     * @param int $summit_id
     * @param int $venue_id
     * @return mixed
     */
    public function testUpdateVenueFloor($summit_id = 23, $venue_id = 292){

        $floor = $this->testAddVenueFloor($summit_id, $venue_id, rand(0,1000));
        $params = [
            'id'       => $summit_id,
            'venue_id' => $venue_id,
            'floor_id' => $floor->id
        ];

        $data = [
            'name' => 'test floor update',
            'description' => 'test floor update',
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitLocationsApiController@updateVenueFloor",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $floor = json_decode($content);
        $this->assertTrue(!is_null($floor));
        return $floor;
    }

    /**
     * @param int $summit_id
     * @param int $venue_id
     */
    public function testDeleteVenueFloor($summit_id = 23, $venue_id = 292){

        $floor = $this->testAddVenueFloor($summit_id, $venue_id, rand(0,1000));

        $params = [
            'id'       => $summit_id,
            'venue_id' => $venue_id,
            'floor_id' => $floor->id
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitLocationsApiController@deleteVenueFloor",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(204);
    }

    /**
     * @param int $summit_id
     * @param int $venue_id
     * @return mixed
     */
    public function testAddVenueRoom($summit_id = 23, $venue_id = 292){

        $params = [
            'id'       => $summit_id,
            'venue_id' => $venue_id,
        ];

        $name       = str_random(16).'_room';

        $data = [
            'name'        => $name,
            'description' => 'test room',
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitLocationsApiController@addVenueRoom",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $room = json_decode($content);
        $this->assertTrue(!is_null($room));
        return $room;
    }


    /**
     * @param int $summit_id
     * @param int $venue_id
     * @return mixed
     */
    public function testAddVenueRoomWithFloor($summit_id = 23, $venue_id = 292){

        $floor = $this->testAddVenueFloor($summit_id, $venue_id, rand(0,1000));

        $params = [
            'id'       => $summit_id,
            'venue_id' => $venue_id,
            'floor_id' => $floor->id
        ];

        $name       = str_random(16).'_room';

        $data = [
            'name'        => $name,
            'description' => 'test room',
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitLocationsApiController@addVenueFloorRoom",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $room = json_decode($content);
        $this->assertTrue(!is_null($room));
        return $room;
    }

    /**
     * @param int $summit_id
     * @param int $venue_id
     * @return mixed
     */
    public function testUpdateVenueRoomWithFloor($summit_id = 23, $venue_id = 292){

        $params = [
            'id'       => $summit_id,
            'venue_id' => $venue_id,
            'floor_id' => 22,
            'room_id'  => 307
        ];

        $data = [
            'description' => 'Pyrmont Theatre Update',
            'order'       => 2,
            'capacity'    => 1000,
            'floor_id'    => 23
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitLocationsApiController@updateVenueFloorRoom",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $room = json_decode($content);
        $this->assertTrue(!is_null($room));
        return $room;
    }

    /**
     * @param int $summit_id
     * @param int $venue_id
     * @return mixed
     */
    public function testDeleteExistentRoom($summit_id = 23, $venue_id = 292, $room_id = 307){

        $params = [
            'id'       => $summit_id,
            'venue_id' => $venue_id,
            'room_id'  => 333
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitLocationsApiController@deleteVenueRoom",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(204);
    }

    public function testGetFloorById($summit_id = 23, $venue_id = 292, $floor_id = 23){

        $params = [
            'id'       => $summit_id,
            'venue_id' => $venue_id,
            'floor_id' => $floor_id,
            'expand'   => 'rooms'
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitLocationsApiController@getVenueFloor",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $floor = json_decode($content);
        $this->assertTrue(!is_null($floor));
        return $floor;
    }

    public function testGetVenueFloorRoomById($summit_id = 23, $venue_id = 292, $floor_id = 23, $room_id = 309){

        $params = [
            'id'       => $summit_id,
            'venue_id' => $venue_id,
            'floor_id' => $floor_id,
            'room_id'  => $room_id,
            'expand'   => 'floor,venue'
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitLocationsApiController@getVenueFloorRoom",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $room = json_decode($content);
        $this->assertTrue(!is_null($room));
        return $room;
    }

    public function testAddLocationBanner($summit_id = 23, $location_id = 315){
        $params = [
            'id'          => $summit_id,
            'location_id' => $location_id
        ];

        $data = [
            'title'      => str_random(16).'_banner_title',
            'content'    => '<span>title</span>',
            'type'       => \App\Models\Foundation\Summit\Locations\Banners\SummitLocationBanner::TypePrimary,
            'enabled'    => true,
            'class_name' => \App\Models\Foundation\Summit\Locations\Banners\SummitLocationBanner::ClassName,
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitLocationsApiController@addLocationBanner",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $banner = json_decode($content);
        $this->assertTrue(!is_null($banner));
        return $banner;
    }


    public function testAddLocationScheduleBanner($summit_id = 23, $location_id = 315){
        $params = [
            'id'          => $summit_id,
            'location_id' => $location_id
        ];

        $data = [
            'title'      => str_random(16).'_banner_title',
            'content'    => '<span>title</span>',
            'type'       => \App\Models\Foundation\Summit\Locations\Banners\SummitLocationBanner::TypePrimary,
            'enabled'    => true,
            'class_name' => \App\Models\Foundation\Summit\Locations\Banners\ScheduledSummitLocationBanner::ClassName,
            'start_date' => 1509876000,
            'end_date'   => (1509876000+1000),
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitLocationsApiController@addLocationBanner",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $banner = json_decode($content);
        $this->assertTrue(!is_null($banner));
        return $banner;
    }

    public function testGetLocationBanners($summit_id = 23, $location_id = 315)
    {
        $params = [
            'id'          => $summit_id,
            'location_id' => $location_id,
            'page'        => 1,
            'per_page'    => 5,
            'order'       => '-id'
        ];

        $response = $this->action
        (
            "GET",
            "OAuth2SummitLocationsApiController@getLocationBanners",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);

        $banners = json_decode($content);
        $this->assertTrue(!is_null($banners));

        return $banners;
    }

    public function testGetLocationBannersFilterByClassName($summit_id = 23, $location_id = 315)
    {
        $params = [
            'id'          => $summit_id,
            'location_id' => $location_id,
            'page'        => 1,
            'per_page'    => 5,
            'order'       => '-id',
            'filter'      => 'class_name=='.\App\Models\Foundation\Summit\Locations\Banners\ScheduledSummitLocationBanner::ClassName
        ];

        $response = $this->action
        (
            "GET",
            "OAuth2SummitLocationsApiController@getLocationBanners",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);

        $banners = json_decode($content);
        $this->assertTrue(!is_null($banners));
    }

    public function testGetLocationBannersFilterByInvalidClassName($summit_id = 23, $location_id = 315)
    {
        $params = [
            'id'          => $summit_id,
            'location_id' => $location_id,
            'page'        => 1,
            'per_page'    => 5,
            'order'       => '-id',
            'filter'      => 'class_name==test,class_name==test2'
        ];

        $response = $this->action
        (
            "GET",
            "OAuth2SummitLocationsApiController@getLocationBanners",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);

        $banners = json_decode($content);
        $this->assertTrue(!is_null($banners));
    }

    public function testDeleteLocationBanner($summit_id = 23, $location_id = 315){
        $banners = $this->testGetLocationBanners($summit_id, $location_id);

        $params = [
            'id'          => $summit_id,
            'location_id' => $location_id,
            'banner_id'   => $banners->data[0]->id
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitLocationsApiController@deleteLocationBanner",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
        );

        $content = $response->getContent();
        $this->assertResponseStatus(204);
    }

    public function testDeleteLocationMap($summit_id = 22, $location_id = 214, $map_id=30){

        $params = [
            'id'          => $summit_id,
            'location_id' => $location_id,
            'map_id'      => $map_id
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitLocationsApiController@deleteLocationMap",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
        );

        $content = $response->getContent();
        $this->assertResponseStatus(204);
    }

    // bookable rooms tests

    public function testSummitGetBookableRoomsFilterDiffValuesSameColumn($summit_id = 6)
    {
        $params = [
            'id'       => $summit_id,
            'page'     => 1,
            'per_page' => 10,
            'order'    => '-id',
            'expand'   => 'venue,attribute_type',
            'filter'   => [
                "availability_day==1579086000",
                "attribute==",
                "capacity>=1"
            ],
        ];

        $response = $this->action
        (
            "GET",
            "OAuth2SummitLocationsApiController@getBookableVenueRooms",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);

        $rooms = json_decode($content);
        $this->assertTrue(!is_null($rooms));
    }

    public function testSummitGetBookableRoomAvailability($summit_id = 6, $room_id = 20, $day = 1579172400)
    {
        $params = [
            'id'       => $summit_id,
            'room_id'  => $room_id,
            'day'      => $day,
        ];

        $response = $this->action
        (
            "GET",
            "OAuth2SummitLocationsApiController@getBookableVenueRoomAvailability",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);

        $slots = json_decode($content);
        $this->assertTrue(!is_null($slots));
    }

    /**
     * @param int $summit_id
     * @param int $room_id
     * @param int $start_date
     * @return mixed
     */
    public function testBookableRoomReservation(){
        $params = [
            'id'       => self::$summit->getId(),
            'room_id'  => self::$bookable_room->getId(),
        ];

        $data = [
            'currency'   => 'USD',
            'amount'     => 200,
            'start_datetime' => 1572919200,
            'end_datetime'   => 1572922800,
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitLocationsApiController@createBookableVenueRoomReservation",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $reservation = json_decode($content);
        $this->assertTrue(!is_null($reservation));
        return $reservation;
    }

    public function testGetMyReservations($summit_id = 27)
    {
        $params = [
            'id' => $summit_id,
            'expand' => 'room'
        ];

        $response = $this->action
        (
            "GET",
            "OAuth2SummitLocationsApiController@getMyBookableVenueRoomReservations",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);

        $reservations = json_decode($content);
        $this->assertTrue(!is_null($reservations));
    }

    public function testCancelMyReservations($summit_id = 27, $reservation_id = 4)
    {
        $params = [
            'id' => $summit_id,
            'reservation_id' => $reservation_id
        ];


        $response = $this->action
        (
            "DELETE",
            "OAuth2SummitLocationsApiController@cancelMyBookableVenueRoomReservation",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);

        $reservations = json_decode($content);
        $this->assertTrue(!is_null($reservations));
    }

    /**
     * @param int $summit_id
     */
    public function testAddBookableRoom($summit_id = 27){
        $summit_repository = EntityManager::getRepository(\models\summit\Summit::class);
        $summit = $summit_repository->getById($summit_id);
        $this->assertTrue(!is_null($summit));
        if(!$summit instanceof \models\summit\Summit) return;
        $venues = $summit->getVenues();
        $this->assertTrue($venues->count() > 0 );
        $venue  = $venues->first();

        $params = [
            'id' => $summit_id,
            'venue_id' => $venue->getId()
        ];

        $name       = str_random(16).'_bookable_room';

        $data = [
            'name'            => $name,
            'capacity'       =>  10,
            'description'    => 'test bookable room',
            'time_slot_cost' => 200,
            'currency'       => 'USD',
         ];

        $response = $this->action
        (
            "POST",
            "OAuth2SummitLocationsApiController@addVenueBookableRoom",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);

        $bookable_room = json_decode($content);
        $this->assertTrue(!is_null($bookable_room));
        $this->assertTrue($bookable_room->name == $name);

        return $bookable_room;
    }

    /**
     * @param int $summit_id
     * @param int $floor_id
     * @return mixed|null
     */
    public function testAddBookableRoomOnFloor($summit_id = 27){

        $summit_repository = EntityManager::getRepository(\models\summit\Summit::class);
        $summit = $summit_repository->getById($summit_id);
        $this->assertTrue(!is_null($summit));
        if(!$summit instanceof \models\summit\Summit) return null;
        $venues = $summit->getVenues();
        $this->assertTrue($venues->count() > 0 );
        $venue  = $venues->first();
        if(!$venue instanceof \models\summit\SummitVenue) return null;

        $floors = $venue->getFloors();

        $this->assertTrue($floors->count() > 0);

        $params = [
            'id' => $summit_id,
            'venue_id' => $venue->getId(),
            'floor_id' => $floors->first()->getId()
        ];

        $name       = str_random(16).'_bookable_room';

        $data = [
            'name'            => $name,
            'capacity'       =>  10,
            'description'    => 'test bookable room',
            'time_slot_cost' => 200,
            'currency'       => 'USD',
        ];

        $response = $this->action
        (
            "POST",
            "OAuth2SummitLocationsApiController@addVenueFloorBookableRoom",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);

        $bookable_room = json_decode($content);
        $this->assertTrue(!is_null($bookable_room));
        $this->assertTrue($bookable_room->name == $name);

        return $bookable_room;
    }

    public function testUpdateBookableRooms($summit_id = 27){
        $bookable_room = $this->testAddBookableRoom($summit_id);
        $this->assertTrue(!is_null($bookable_room));

        $params = [
            'id' => $summit_id,
            'venue_id' => $bookable_room->venue_id,
            'room_id' => $bookable_room->id,
        ];

        $name       = str_random(16).'_bookable_room_update';

        $data = [
            'name'            => $name,
            'capacity'       =>  14,
            'time_slot_cost' => 250,
        ];

        $response = $this->action
        (
            "PUT",
            "OAuth2SummitLocationsApiController@updateVenueBookableRoom",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);

        $bookable_room = json_decode($content);
        $this->assertTrue(!is_null($bookable_room));
        $this->assertTrue($bookable_room->name == $name);

        return $bookable_room;

    }

    /**
     * @param int $summit_id
     */
    public function testAddBookableRoomAttributeValue($summit_id = 27){
        $summit_repository = EntityManager::getRepository(\models\summit\Summit::class);
        $summit = $summit_repository->getById($summit_id);
        $this->assertTrue(!is_null($summit));
        if(!$summit instanceof \models\summit\Summit) return;

        $rooms = $summit->getBookableRooms();
        $room = $rooms->first();
        $attributes = $summit->getMeetingBookingRoomAllowedAttributes();
        $attribute = $attributes->last();
        $values = $attribute->getValues();
        $value = $values->first();

        $params = [
            'id' => $summit_id,
            'venue_id' => $room->getVenueId(),
            'room_id' => $room->getId(),
            'attribute_id' => $value->getId()
        ];


        $response = $this->action
        (
            "PUT",
            "OAuth2SummitLocationsApiController@addVenueBookableRoomAttribute",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);

        $bookable_room = json_decode($content);
        $this->assertTrue(!is_null($bookable_room));

    }


    public function testGetAllReservationsBySummit($summit_id =27){
        $params = [
            'id' => $summit_id,
            'filter' => 'status==Reserved,room_id==1',
            'order'  => '+owner_name'
        ];

        $response = $this->action
        (
            "GET",
            "OAuth2SummitLocationsApiController@getAllReservationsBySummit",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);

        $reservations = json_decode($content);
        $this->assertTrue(!is_null($reservations));
    }

    public function testGetAllReservationsBySummitAndOwnerName($summit_id =27){
        $params = [
            'id' => $summit_id,
            'filter' => 'status==Canceled,owner_name=@Sebastian'
        ];

        $response = $this->action
        (
            "GET",
            "OAuth2SummitLocationsApiController@getAllReservationsBySummit",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);

        $reservations = json_decode($content);
        $this->assertTrue(!is_null($reservations));
    }

    public function testGetAllReservationsBySummitAndOwnerNameCSV($summit_id =27){
        $params = [
            'id' => $summit_id,
            'filter' => 'status==Canceled,owner_name=@Sebastian'
        ];

        $response = $this->action
        (
            "GET",
            "OAuth2SummitLocationsApiController@getAllReservationsBySummitCSV",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
        );

        $csv = $response->getContent();
        $this->assertResponseStatus(200);
        $this->assertTrue(!empty($csv));
    }

    public function testGetReservationById($id = 2){
        $params = [
            'id' => $id,
            'filter' => 'status==Canceled,owner_name=@Sebastian'
        ];

        $response = $this->action
        (
            "GET",
            "OAuth2SummitLocationsApiController@getReservationById",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);

        $reservation = json_decode($content);
        $this->assertTrue(!is_null($reservation));
    }

    public function testCopyLocation(){

        $params = [
            'id' => self::$summit->getId(),
            'target_summit_id' => self::$summit2->getId(),
        ];

        $data = [
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitLocationsApiController@copy",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(412);
    }

}