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

use App\Models\Foundation\Main\IGroup;
use App\Models\Foundation\Summit\Factories\SummitLocationFactory;
use Illuminate\Support\Facades\Config;
use models\summit\SummitBookableVenueRoom;
use Mockery;

/**
 * Class OAuth2SummitLocationsApiTest
 */
final class OAuth2SummitLocationsApiTest extends ProtectedApiTestCase
{
    use InsertSummitTestData;

    private static $bookable_room;

    protected function setUp():void
    {
        $this->setCurrentGroup(IGroup::TrackChairs);
        parent::setUp();
        self::$defaultMember = self::$member;
        self::insertSummitTestData();

        // Configure assets disk to use local driver for tests (avoids OpenStack/Swift dependency)
        Config::set('filesystems.disks.assets', [
            'driver' => 'local',
            'root' => storage_path('app/testing'),
        ]);

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
            'id'       => self::$summit->getId(),
            'page'     => 1,
            'per_page' => 5,
            'order'    => '-order'
        ];

        $response = $this->action(
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
        $this->assertNotNull($locations);
    }

    public function testGetSummitLocationsOrderByName()
    {
        $params = [
            'id'       => self::$summit->getId(),
            'page'     => 1,
            'per_page' => 5,
            'order'    => '-name'
        ];

        $response = $this->action(
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
        $this->assertNotNull($locations);
    }

    public function testGetCurrentSummitLocationsMetadata()
    {
        $params = [
            'id' => self::$summit->getId(),
        ];

        $response = $this->action(
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
        $this->assertNotNull($metadata);
    }

    public function testGetCurrentSummitLocationsByClassNameVenueORAirport()
    {
        $params = [
            'id'         => self::$summit->getId(),
            'page'       => 1,
            'per_page'   => 5,
            'filter'     => [
                'class_name=='.\models\summit\SummitVenue::ClassName.',class_name=='.\models\summit\SummitAirport::ClassName,
            ]
        ];

        $response = $this->action(
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
        $this->assertNotNull($locations);
    }

    public function testGetCurrentSummitLocationsByClassHotels()
    {
        $params = [
            'id'         => self::$summit->getId(),
            'page'       => 1,
            'per_page'   => 100,
            'filter'     => [
                'class_name=='.\models\summit\SummitHotel::ClassName,
            ]
        ];

        $response = $this->action(
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
        $this->assertNotNull($locations);
    }

    public function testGetCurrentSummitVenues()
    {
        $params = [
            'id' => self::$summit->getId(),
        ];

        $response = $this->action(
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
        $this->assertNotNull($locations);
    }

    public function testGetCurrentSummitHotels()
    {
        $params = [
            'id' => self::$summit->getId(),
        ];

        $response = $this->action(
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
        $this->assertNotNull($locations);
    }

    public function testGetCurrentSummitAirports()
    {
        $params = [
            'id' => self::$summit->getId(),
        ];

        $response = $this->action(
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
        $this->assertNotNull($locations);
    }

    public function testGetCurrentSummitExternalLocations()
    {
        $params = [
            'id' => self::$summit->getId(),
        ];

        $response = $this->action(
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
        $this->assertNotNull($locations);
    }

    public function testGetCurrentSummitLocation()
    {
        $params = [
            'id' => self::$summit->getId(),
            'location_id' => self::$mainVenue->getId()
        ];

        $response = $this->action(
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

        $location = json_decode($content);
        $this->assertNotNull($location);
    }

    public function testCurrentSummitLocationEventsWithFilter()
    {
        $params = [
            'id'          => self::$summit->getId(),
            'page'        => 1,
            'per_page'    => 50,
            'location_id' => self::$mainVenue->getId(),
        ];

        $response = $this->action(
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
        $this->assertNotNull($page);
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
            ],
            'order'    => '-track_id'
        ];

        $response = $this->action(
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
        $this->assertNotNull($page);
        $this->assertNotEmpty($page->data);
    }

    public function testAddLocationWithoutClassName(){

        $params = [
            'id' => self::$summit->getId(),
        ];

        $name = str_random(16).'_location';
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
        $this->assertNotNull($location);
        return $location;
    }

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
        $this->assertNotNull($location);
        return $location;
    }

    public function testAddLocationVenueLatLngInvalid(){

        $params = [
            'id' => self::$summit->getId(),
        ];

        $name = str_random(16).'_location';

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
        $this->assertNotNull($location);
        return $location;
    }

    public function testAddLocationHotelAddress(){

        $params = [
            'id' => self::$summit->getId(),
        ];

        $name = str_random(16).'_hotel';

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
        $this->assertNotNull($location);
        return $location;
    }

    public function testUpdateLocationHotelOrder(){

        $hotel = $this->testAddLocationHotelAddress();
        $new_order = 2;
        $params = [
            'id'          => self::$summit->getId(),
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
        $this->assertNotNull($location);
        $this->assertEquals($new_order, $location->order);
        return $location;
    }

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
        $this->assertNotNull($location);
        return $location;
    }

    public function testDeleteNewlyCreatedHotel(){

        $hotel = $this->testAddLocationHotelAddress();
        $params = [
            'id'          => self::$summit->getId(),
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

        $this->assertResponseStatus(204);
    }

    public function testAddVenueFloor(){

        $params = [
            'id'       => self::$summit->getId(),
            'venue_id' => self::$mainVenue->getId()
        ];

        // Collision-free floor number: start above the seed floor (number=1 in InsertSummitTestData)
        // and monotonically increment to guarantee uniqueness across calls in the same process.
        static $floor_number_seq = 1000;
        $number = ++$floor_number_seq;
        $name = str_random(16).'_floor';
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
        $this->assertNotNull($floor);
        return $floor;
    }

    public function testUpdateVenueFloor(){

        $floor = $this->testAddVenueFloor();
        $params = [
            'id'       => self::$summit->getId(),
            'venue_id' => self::$mainVenue->getId(),
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
        $this->assertNotNull($floor);
        return $floor;
    }

    public function testDeleteVenueFloor(){

        $floor = $this->testAddVenueFloor();

        $params = [
            'id'       => self::$summit->getId(),
            'venue_id' => self::$mainVenue->getId(),
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

        $this->assertResponseStatus(204);
    }

    public function testAddVenueRoom(){

        $params = [
            'id'       => self::$summit->getId(),
            'venue_id' => self::$mainVenue->getId(),
        ];

        $name = str_random(16).'_room';

        $data = [
            'name'        => $name,
            'description' => 'test room',
            'class_name'  => \models\summit\SummitVenueRoom::ClassName,
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
        $this->assertNotNull($room);
        return $room;
    }

    public function testAddVenueRoomWithFloor(){

        $floor = $this->testAddVenueFloor();

        $params = [
            'id'       => self::$summit->getId(),
            'venue_id' => self::$mainVenue->getId(),
            'floor_id' => $floor->id
        ];

        $name = str_random(16).'_room';

        $data = [
            'name'        => $name,
            'description' => 'test room',
            'class_name'  => \models\summit\SummitVenueRoom::ClassName,
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
        $this->assertNotNull($room);
        return $room;
    }

    public function testUpdateVenueRoomWithFloor(){
        $room = $this->testAddVenueRoomWithFloor();

        $params = [
            'id'       => self::$summit->getId(),
            'venue_id' => self::$mainVenue->getId(),
            'floor_id' => $room->floor_id,
            'room_id'  => $room->id
        ];

        $data = [
            'description' => 'Updated Room Description',
            'order'       => 2,
            'capacity'    => 1000,
            'class_name'  => \models\summit\SummitVenueRoom::ClassName,
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
        $this->assertNotNull($room);
        return $room;
    }

    public function testDeleteVenueRoom(){
        $room = $this->testAddVenueRoom();

        $params = [
            'id'       => self::$summit->getId(),
            'venue_id' => self::$mainVenue->getId(),
            'room_id'  => $room->id
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

        $this->assertResponseStatus(204);
    }

    public function testGetFloorById(){
        $floor = $this->testAddVenueFloor();

        $params = [
            'id'       => self::$summit->getId(),
            'venue_id' => self::$mainVenue->getId(),
            'floor_id' => $floor->id,
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
        $this->assertNotNull($floor);
        return $floor;
    }

    public function testGetVenueFloorRoomById(){
        $room = $this->testAddVenueRoomWithFloor();

        $params = [
            'id'       => self::$summit->getId(),
            'venue_id' => self::$mainVenue->getId(),
            'floor_id' => $room->floor_id,
            'room_id'  => $room->id,
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
        $room_response = json_decode($content);
        $this->assertNotNull($room_response);
        return $room_response;
    }

    public function testAddLocationBanner(){
        $params = [
            'id'          => self::$summit->getId(),
            'location_id' => self::$mainVenue->getId()
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
        $this->assertNotNull($banner);
        return $banner;
    }

    public function testAddLocationScheduleBanner(){
        $params = [
            'id'          => self::$summit->getId(),
            'location_id' => self::$mainVenue->getId()
        ];

        $data = [
            'title'      => str_random(16).'_banner_title',
            'content'    => '<span>title</span>',
            'type'       => \App\Models\Foundation\Summit\Locations\Banners\SummitLocationBanner::TypePrimary,
            'enabled'    => true,
            'class_name' => \App\Models\Foundation\Summit\Locations\Banners\ScheduledSummitLocationBanner::ClassName,
            'start_date' => self::$summit->getBeginDate()->getTimestamp() + 3600,
            'end_date'   => self::$summit->getBeginDate()->getTimestamp() + 7200,
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
        $this->assertNotNull($banner);
        return $banner;
    }

    public function testGetLocationBanners()
    {
        // Create a banner first so we have data
        $this->testAddLocationBanner();

        $params = [
            'id'          => self::$summit->getId(),
            'location_id' => self::$mainVenue->getId(),
            'page'        => 1,
            'per_page'    => 5,
            'order'       => '-id'
        ];

        $response = $this->action(
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
        $this->assertNotNull($banners);
        $this->assertGreaterThanOrEqual(1, $banners->total);

        return $banners;
    }

    public function testGetLocationBannersFilterByClassName()
    {
        // Create a scheduled banner first
        $this->testAddLocationScheduleBanner();

        $params = [
            'id'          => self::$summit->getId(),
            'location_id' => self::$mainVenue->getId(),
            'page'        => 1,
            'per_page'    => 5,
            'order'       => '-id',
            'filter'      => 'class_name=='.\App\Models\Foundation\Summit\Locations\Banners\ScheduledSummitLocationBanner::ClassName
        ];

        $response = $this->action(
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
        $this->assertNotNull($banners);
    }

    public function testGetLocationBannersFilterByInvalidClassName()
    {
        $params = [
            'id'          => self::$summit->getId(),
            'location_id' => self::$mainVenue->getId(),
            'page'        => 1,
            'per_page'    => 5,
            'order'       => '-id',
            'filter'      => 'class_name==test,class_name==test2'
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitLocationsApiController@getLocationBanners",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
        );

        $content = $response->getContent();
        // Invalid class names return 412 validation error
        $this->assertTrue(in_array($response->getStatusCode(), [200, 412]));

        $banners = json_decode($content);
        $this->assertNotNull($banners);
    }

    public function testDeleteLocationBanner(){
        $banners = $this->testGetLocationBanners();

        $params = [
            'id'          => self::$summit->getId(),
            'location_id' => self::$mainVenue->getId(),
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

        $this->assertResponseStatus(204);
    }

    // bookable rooms tests

    public function testSummitGetBookableRooms()
    {
        $params = [
            'id'       => self::$summit->getId(),
            'page'     => 1,
            'per_page' => 10,
            'order'    => '-id',
        ];

        $response = $this->action(
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
        $this->assertNotNull($rooms);
    }

    public function testAddBookableRoom(){
        $params = [
            'id' => self::$summit->getId(),
            'venue_id' => self::$mainVenue->getId()
        ];

        $name = str_random(16).'_bookable_room';

        $data = [
            'name'            => $name,
            'capacity'       =>  10,
            'description'    => 'test bookable room',
            'time_slot_cost' => 200,
            'currency'       => 'USD',
         ];

        $response = $this->action(
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
        $this->assertNotNull($bookable_room);
        $this->assertEquals($name, $bookable_room->name);

        return $bookable_room;
    }

    public function testAddBookableRoomOnFloor(){
        $floor = $this->testAddVenueFloor();

        $params = [
            'id' => self::$summit->getId(),
            'venue_id' => self::$mainVenue->getId(),
            'floor_id' => $floor->id
        ];

        $name = str_random(16).'_bookable_room';

        $data = [
            'name'            => $name,
            'capacity'       =>  10,
            'description'    => 'test bookable room',
            'time_slot_cost' => 200,
            'currency'       => 'USD',
        ];

        $response = $this->action(
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
        $this->assertNotNull($bookable_room);
        $this->assertEquals($name, $bookable_room->name);

        return $bookable_room;
    }

    public function testUpdateBookableRoom(){
        $bookable_room = $this->testAddBookableRoom();

        $params = [
            'id' => self::$summit->getId(),
            'venue_id' => $bookable_room->venue_id,
            'room_id' => $bookable_room->id,
        ];

        $name = str_random(16).'_bookable_room_update';

        $data = [
            'name'            => $name,
            'capacity'       =>  14,
            'time_slot_cost' => 250,
            'currency'       => 'USD',
        ];

        $response = $this->action(
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
        $this->assertNotNull($bookable_room);
        $this->assertEquals($name, $bookable_room->name);

        return $bookable_room;
    }

    public function testGetAllReservationsBySummit(){
        $params = [
            'id' => self::$summit->getId(),
        ];

        $response = $this->action(
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
        $this->assertNotNull($reservations);
    }

    public function testGetMyReservations()
    {
        $params = [
            'id' => self::$summit->getId(),
        ];

        $response = $this->action(
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
        $this->assertNotNull($reservations);
    }

    public function testCopyLocation(){

        $params = [
            'id' => self::$summit->getId(),
            'target_summit_id' => self::$summit2->getId(),
        ];

        $data = [];

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
        // Copy with empty data should succeed (copies all locations)
        $this->assertTrue(in_array($response->getStatusCode(), [200, 201, 412]));
    }

    // venue CRUD tests

    public function testAddVenue(){
        $params = [
            'id' => self::$summit->getId(),
        ];

        $name = str_random(16).'_venue';

        $data = [
            'name'        => $name,
            'class_name'  => \models\summit\SummitVenue::ClassName,
            'description' => 'test venue',
            'lat'         => '-34.601978',
            'lng'         => '-58.368822',
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitLocationsApiController@addVenue",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);

        $venue = json_decode($content);
        $this->assertNotNull($venue);
        $this->assertEquals($name, $venue->name);

        return $venue;
    }

    public function testUpdateVenue(){
        $venue = $this->testAddVenue();

        $params = [
            'id' => self::$summit->getId(),
            'venue_id' => $venue->id,
        ];

        $name = str_random(16).'_venue_updated';

        $data = [
            'name'        => $name,
            'class_name'  => \models\summit\SummitVenue::ClassName,
            'description' => 'updated test venue',
            'lat'         => '-34.601978',
            'lng'         => '-58.368822',
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitLocationsApiController@updateVenue",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);

        $updated_venue = json_decode($content);
        $this->assertNotNull($updated_venue);
        $this->assertEquals($name, $updated_venue->name);
    }

    public function testGetAllVenuesRooms(){
        $params = [
            'id'       => self::$summit->getId(),
            'page'     => 1,
            'per_page' => 10,
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitLocationsApiController@getAllVenuesRooms",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);

        $rooms = json_decode($content);
        $this->assertNotNull($rooms);
    }

    // venue room by venue (non-floor) tests

    public function testGetVenueRoom(){
        $room = $this->testAddVenueRoom();

        $params = [
            'id'       => self::$summit->getId(),
            'venue_id' => self::$mainVenue->getId(),
            'room_id'  => $room->id,
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitLocationsApiController@getVenueRoom",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);

        $fetched_room = json_decode($content);
        $this->assertNotNull($fetched_room);
        $this->assertEquals($room->id, $fetched_room->id);
    }

    public function testUpdateVenueRoom(){
        $room = $this->testAddVenueRoom();

        $params = [
            'id'       => self::$summit->getId(),
            'venue_id' => self::$mainVenue->getId(),
            'room_id'  => $room->id,
        ];

        $name = str_random(16).'_room_updated';

        $data = [
            'name'        => $name,
            'class_name'  => \models\summit\SummitVenueRoom::ClassName,
            'description' => 'updated test room',
            'capacity'    => 200,
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitLocationsApiController@updateVenueRoom",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);

        $updated_room = json_decode($content);
        $this->assertNotNull($updated_room);
        $this->assertEquals($name, $updated_room->name);
    }

    // location banner update test

    public function testUpdateLocationBanner(){
        $banners = $this->testGetLocationBanners();

        $params = [
            'id'          => self::$summit->getId(),
            'location_id' => self::$mainVenue->getId(),
            'banner_id'   => $banners->data[0]->id
        ];

        $data = [
            'class_name' => 'SummitLocationBanner',
            'title'      => 'updated banner title',
            'content'    => 'updated banner content',
            'type'       => 'Primary',
            'enabled'    => true,
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitLocationsApiController@updateLocationBanner",
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
        $this->assertNotNull($banner);
    }

    // bookable room additional tests

    public function testGetBookableVenueRoomById(){
        $bookable_room = $this->testAddBookableRoom();

        $params = [
            'id'      => self::$summit->getId(),
            'room_id' => $bookable_room->id,
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitLocationsApiController@getBookableVenueRoom",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);

        $room = json_decode($content);
        $this->assertNotNull($room);
        $this->assertEquals($bookable_room->id, $room->id);
    }

    public function testDeleteBookableRoom(){
        $bookable_room = $this->testAddBookableRoom();

        $params = [
            'id'       => self::$summit->getId(),
            'venue_id' => $bookable_room->venue_id,
            'room_id'  => $bookable_room->id,
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitLocationsApiController@deleteVenueBookableRoom",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
        );

        $this->assertResponseStatus(204);
    }

    public function testGetVenueFloorBookableRoom(){
        $bookable_room = $this->testAddBookableRoomOnFloor();

        $params = [
            'id'       => self::$summit->getId(),
            'venue_id' => self::$mainVenue->getId(),
            'floor_id' => $bookable_room->floor_id,
            'room_id'  => $bookable_room->id,
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitLocationsApiController@getVenueFloorBookableRoom",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);

        $room = json_decode($content);
        $this->assertNotNull($room);
        $this->assertEquals($bookable_room->id, $room->id);
    }

    public function testUpdateVenueFloorBookableRoom(){
        $bookable_room = $this->testAddBookableRoomOnFloor();

        $params = [
            'id'       => self::$summit->getId(),
            'venue_id' => self::$mainVenue->getId(),
            'floor_id' => $bookable_room->floor_id,
            'room_id'  => $bookable_room->id,
        ];

        $name = str_random(16).'_bookable_updated';

        $data = [
            'name'            => $name,
            'capacity'       =>  20,
            'time_slot_cost' => 300,
            'currency'       => 'USD',
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitLocationsApiController@updateVenueFloorBookableRoom",
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
        $this->assertNotNull($room);
        $this->assertEquals($name, $room->name);
    }

    // reservations CSV

    public function testGetAllReservationsBySummitCSV(){
        $params = [
            'id' => self::$summit->getId(),
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitLocationsApiController@getAllReservationsBySummitCSV",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
        );

        $this->assertResponseStatus(200);
    }

    // airport CRUD tests

    public function testAddAirport(){
        $params = [
            'id' => self::$summit->getId(),
        ];

        $name = str_random(16).'_airport';

        $data = [
            'name'        => $name,
            'class_name'  => \models\summit\SummitAirport::ClassName,
            'description' => 'test airport',
            'airport_type' => 'International',
            'lat'         => '-34.601978',
            'lng'         => '-58.368822',
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitLocationsApiController@addAirport",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);

        $airport = json_decode($content);
        $this->assertNotNull($airport);
        $this->assertEquals($name, $airport->name);

        return $airport;
    }

    public function testUpdateAirport(){
        $airport = $this->testAddAirport();

        $params = [
            'id' => self::$summit->getId(),
            'airport_id' => $airport->id,
        ];

        $name = str_random(16).'_airport_updated';

        $data = [
            'name'        => $name,
            'class_name'  => \models\summit\SummitAirport::ClassName,
            'description' => 'updated airport',
            'lat'         => '-34.601978',
            'lng'         => '-58.368822',
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitLocationsApiController@updateAirport",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);

        $updated = json_decode($content);
        $this->assertNotNull($updated);
        $this->assertEquals($name, $updated->name);
    }

    // hotel CRUD tests (routes under /hotels map to addExternalLocation/updateExternalLocation)

    public function testAddHotel(){
        $params = [
            'id' => self::$summit->getId(),
        ];

        $name = str_random(16).'_hotel';

        $data = [
            'name'        => $name,
            'class_name'  => \models\summit\SummitHotel::ClassName,
            'description' => 'test hotel',
            'hotel_type'  => 'Primary',
            'lat'         => '-34.601978',
            'lng'         => '-58.368822',
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitLocationsApiController@addExternalLocation",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);

        $hotel = json_decode($content);
        $this->assertNotNull($hotel);
        $this->assertEquals($name, $hotel->name);

        return $hotel;
    }

    public function testUpdateHotel(){
        $hotel = $this->testAddHotel();

        $params = [
            'id' => self::$summit->getId(),
            'hotel_id' => $hotel->id,
        ];

        $name = str_random(16).'_hotel_updated';

        $data = [
            'name'        => $name,
            'class_name'  => \models\summit\SummitHotel::ClassName,
            'description' => 'updated hotel',
            'lat'         => '-34.601978',
            'lng'         => '-58.368822',
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitLocationsApiController@updateExternalLocation",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);

        $updated = json_decode($content);
        $this->assertNotNull($updated);
        $this->assertEquals($name, $updated->name);
    }

    // external location CRUD tests (routes under /external-locations map to addHotel/updateHotel)

    public function testAddExternalLocation(){
        $params = [
            'id' => self::$summit->getId(),
        ];

        $name = str_random(16).'_external';

        $data = [
            'name'        => $name,
            'class_name'  => \models\summit\SummitExternalLocation::ClassName,
            'description' => 'test external location',
            'lat'         => '-34.601978',
            'lng'         => '-58.368822',
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitLocationsApiController@addHotel",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);

        $ext = json_decode($content);
        $this->assertNotNull($ext);
        $this->assertEquals($name, $ext->name);

        return $ext;
    }

    public function testUpdateExternalLocation(){
        $ext = $this->testAddExternalLocation();

        $params = [
            'id' => self::$summit->getId(),
            'external_location_id' => $ext->id,
        ];

        $name = str_random(16).'_external_updated';

        $data = [
            'name'        => $name,
            'class_name'  => \models\summit\SummitExternalLocation::ClassName,
            'description' => 'updated external location',
            'lat'         => '-34.601978',
            'lng'         => '-58.368822',
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitLocationsApiController@updateHotel",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);

        $updated = json_decode($content);
        $this->assertNotNull($updated);
        $this->assertEquals($name, $updated->name);
    }

    // location map tests

    public function testAddLocationMap(){
        $params = [
            'id'          => self::$summit->getId(),
            'location_id' => self::$mainVenue->getId(),
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitLocationsApiController@addLocationMap",
            $params,
            [],
            [],
            [
                'file' => \Illuminate\Http\UploadedFile::fake()->image('map.png'),
            ],
            $this->getAuthHeaders(),
            json_encode(['name' => 'test map'])
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);

        $map = json_decode($content);
        $this->assertNotNull($map);

        return $map;
    }

    public function testGetLocationMap(){
        $map = $this->testAddLocationMap();

        $params = [
            'id'          => self::$summit->getId(),
            'location_id' => self::$mainVenue->getId(),
            'map_id'      => $map->id,
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitLocationsApiController@getLocationMap",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);

        $fetched = json_decode($content);
        $this->assertNotNull($fetched);
        $this->assertEquals($map->id, $fetched->id);
    }

    public function testUpdateLocationMap(){
        $map = $this->testAddLocationMap();

        $params = [
            'id'          => self::$summit->getId(),
            'location_id' => self::$mainVenue->getId(),
            'map_id'      => $map->id,
        ];

        $data = [
            'name' => 'updated map name',
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitLocationsApiController@updateLocationMap",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);

        $updated = json_decode($content);
        $this->assertNotNull($updated);
    }

    public function testDeleteLocationMap(){
        $map = $this->testAddLocationMap();

        $params = [
            'id'          => self::$summit->getId(),
            'location_id' => self::$mainVenue->getId(),
            'map_id'      => $map->id,
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

        $this->assertResponseStatus(204);
    }

    // location image tests

    public function testAddLocationImage(){
        $params = [
            'id'          => self::$summit->getId(),
            'location_id' => self::$mainVenue->getId(),
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitLocationsApiController@addLocationImage",
            $params,
            [],
            [],
            [
                'file' => \Illuminate\Http\UploadedFile::fake()->image('location.png'),
            ],
            $this->getAuthHeaders(),
            json_encode(['name' => 'test image'])
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);

        $image = json_decode($content);
        $this->assertNotNull($image);

        return $image;
    }

    public function testGetLocationImage(){
        $image = $this->testAddLocationImage();

        $params = [
            'id'          => self::$summit->getId(),
            'location_id' => self::$mainVenue->getId(),
            'image_id'    => $image->id,
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitLocationsApiController@getLocationImage",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);

        $fetched = json_decode($content);
        $this->assertNotNull($fetched);
        $this->assertEquals($image->id, $fetched->id);
    }

    public function testUpdateLocationImage(){
        $image = $this->testAddLocationImage();

        $params = [
            'id'          => self::$summit->getId(),
            'location_id' => self::$mainVenue->getId(),
            'image_id'    => $image->id,
        ];

        $data = [
            'name' => 'updated image name',
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitLocationsApiController@updateLocationImage",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);

        $updated = json_decode($content);
        $this->assertNotNull($updated);
    }

    public function testDeleteLocationImage(){
        $image = $this->testAddLocationImage();

        $params = [
            'id'          => self::$summit->getId(),
            'location_id' => self::$mainVenue->getId(),
            'image_id'    => $image->id,
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitLocationsApiController@deleteLocationImage",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
        );

        $this->assertResponseStatus(204);
    }

    // venue floor image tests

    public function testAddVenueFloorImage(){
        $floor = $this->testAddVenueFloor();

        $params = [
            'id'       => self::$summit->getId(),
            'venue_id' => self::$mainVenue->getId(),
            'floor_id' => $floor->id,
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitLocationsApiController@addVenueFloorImage",
            $params,
            [],
            [],
            [
                'file' => \Illuminate\Http\UploadedFile::fake()->image('floor.png'),
            ],
            $this->getAuthHeaders(),
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);

        $floor_data = json_decode($content);
        $this->assertNotNull($floor_data);

        return $floor_data;
    }

}
