<?php namespace Tests;
use App\Models\Foundation\Main\IGroup;
use Illuminate\Support\Facades\App;
use models\utils\SilverstripeBaseModel;
use services\model\IPresentationService;

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

final class OAuth2SummitEventsApiTest extends ProtectedApiTest
{
    use InsertSummitTestData;

    use InsertMemberTestData;

    protected function setUp():void
    {
        parent::setUp();
        self::insertMemberTestData(IGroup::TrackChairs);
        self::$defaultMember = self::$member;
        self::$defaultMember2 = self::$member2;
        self::insertSummitTestData();
    }

    protected function tearDown():void
    {
        self::clearSummitTestData();
        self::clearMemberTestData();
        parent::tearDown();
    }

    public function testAddPublishableEvent($start_date = 1477645200, $end_date = 1477647600, $location_id = 0)
    {
        $params = [
            'id' => self::$summit->getId(),
        ];

        $headers = array
        (
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        );

        $data = array
        (
            'title'         => 'Neutron: tbd',
            'description'    => 'TBD',
            'allow_feedback' => true,
            'type_id'        => self::$defaultPresentationType->getId(),
            'tags'           => ['Neutron'],
            'track_id'       => self::$defaultTrack->getId()
        );

        if($start_date > 0){
            $data['start_date'] = $start_date;
        }

        if($end_date > 0){
            $data['end_date'] = $end_date;
        }

        if($location_id > 0){
            $data['location_id'] = $location_id;
        }

        $response = $this->action
        (
            "POST",
            "OAuth2SummitEventsApiController@addEvent",
            $params,
            array(),
            array(),
            array(),
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $event = json_decode($content);
        $this->assertTrue($event->id > 0);
        return $event;
    }

    public function testAddNonPublishableEventWithScheduledDates($start_date = 1477645200, $end_date = 1477647600, $location_id = 0)
    {
        $params = [
            'id' => self::$summit->getId(),
        ];

        $headers = array
        (
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        );

        $data = array
        (
            'title'         => 'Neutron: tbd',
            'description'    => 'TBD',
            'allow_feedback' => true,
            'type_id'        => self::$allow2VotePresentationType->getId(),
            'tags'           => ['Neutron'],
            'track_id'       => self::$defaultTrack->getId()
        );

        if($start_date > 0){
            $data['start_date'] = $start_date;
        }

        if($end_date > 0){
            $data['end_date'] = $end_date;
        }

        if($location_id > 0){
            $data['location_id'] = $location_id;
        }

        $response = $this->action
        (
            "POST",
            "OAuth2SummitEventsApiController@addEvent",
            $params,
            array(),
            array(),
            array(),
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(412);
        $error = json_decode($content);
    }

    public function testAddNonPublishableEventWithScheduledDatesAndLocation()
    {
        $params = [
            'id' => self::$summit->getId(),
        ];

        $headers = array
        (
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        );

        $start_date  = clone(self::$summit->getBeginDate());
        $end_date    = clone($start_date);
        $end_date =    $end_date->add(new \DateInterval("P1D"));
        $data = array
        (
            'title'         => 'Neutron: tbd',
            'description'    => 'TBD',
            'allow_feedback' => true,
            'type_id'        => self::$allow2VotePresentationType->getId(),
            'tags'           => ['Neutron'],
            'track_id'       => self::$defaultTrack->getId(),
            'start_date' => $start_date->getTimestamp(),
            'end_date' => $end_date->getTimestamp(),
            'location_id' => self::$mainVenue->getId()
        );

        $response = $this->action
        (
            "POST",
            "OAuth2SummitEventsApiController@addEvent",
            $params,
            array(),
            array(),
            array(),
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(412);
        $error = json_decode($content);
    }

    public function testAddNonPublishableEvent()
    {
        $params = [
            'id' => self::$summit->getId(),
        ];

        $headers = array
        (
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        );

        $start_date  = clone(self::$summit->getBeginDate());
        $end_date    = clone($start_date);
        $end_date =    $end_date->add(new \DateInterval("P1D"));
        $data = array
        (
            'title'         => 'Neutron: tbd',
            'description'    => 'TBD',
            'allow_feedback' => true,
            'type_id'        => self::$allow2VotePresentationType->getId(),
            'tags'           => ['Neutron'],
            'track_id'       => self::$defaultTrack->getId(),
        );

        $response = $this->action
        (
            "POST",
            "OAuth2SummitEventsApiController@addEvent",
            $params,
            array(),
            array(),
            array(),
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $event = json_decode($content);
        $this->assertTrue($event->id > 0);

        $params = array
        (
            'id'         => self::$summit->getId(),
            'event_id'   => $event->id,
        );

        $headers = array
        (
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        );

        $response = $this->action
        (
            "PUT",
            "OAuth2SummitEventsApiController@publishEvent",
            $params,
            array(),
            array(),
            array(),
            $headers
        );

        $this->assertResponseStatus(201);
        $content = $response->getContent();

        $event = json_decode($content);
        $this->assertTrue($event->id > 0);
        $this->assertTrue(is_null($event->start_date));
        $this->assertTrue($event->is_published == true);
    }

    public function testPostEventRSVPTemplateUnExistent()
    {
        $params = array
        (
            'id' => self::$summit->getId(),
        );

        $headers = array
        (
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        );

        $data = array
        (
            'title'            => 'Neutron: tbd',
            'description'      => 'TBD',
            'allow_feedback'   => true,
            'type_id'        => self::$allow2VotePresentationType->getId(),
            'tags'           => ['Neutron'],
            'track_id'       => self::$defaultTrack->getId(),
            'rsvp_template_id' => 1,
        );


        $response = $this->action
        (
            "POST",
            "OAuth2SummitEventsApiController@addEvent",
            $params,
            array(),
            array(),
            array(),
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(412);
    }

    public function testPostEventRSVPTemplate($summit_id = 23, $location_id = 0, $type_id = 124, $track_id = 208, $start_date = 0, $end_date = 0)
    {
        $params = array
        (
            'id' => $summit_id,
        );

        $headers = array
        (
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        );

        $data = array
        (
            'title'            => 'Neutron: tbd',
            'description'      => 'TBD',
            'allow_feedback'   => true,
            'type_id'          => $type_id,
            'tags'             => ['Neutron'],
            'track_id'         => $track_id,
            'rsvp_template_id' => 12,
        );

        if($start_date > 0){
            $data['start_date'] = $start_date;
        }

        if($end_date > 0){
            $data['end_date'] = $end_date;
        }

        if($location_id > 0){
            $data['location_id'] = $location_id;
        }

        $response = $this->action
        (
            "POST",
            "OAuth2SummitEventsApiController@addEvent",
            $params,
            array(),
            array(),
            array(),
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $event = json_decode($content);
        $this->assertTrue($event->id > 0);
        $this->assertTrue(!$event->rsvp_external);
        return $event;
    }

    public function testPostPresentationFail412($start_date = 1461510000, $end_date = 1461513600)
    {
        $params = array
        (
            'id' => 7,
        );

        $headers = array
        (
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        );

        $data = array
        (
            'title' => 'test presentation BCN',
            'description' => 'test presentation BCN',
            'allow_feedback' => true,
            'type_id' => 86,
            'tags' => ['tag#1', 'tag#2']
        );

        $response = $this->action
        (
            "POST",
            "OAuth2SummitEventsApiController@addEvent",
            $params,
            array(),
            array(),
            array(),
            $headers,
            json_encode($data)
        );

        $this->assertResponseStatus(412);
    }

    public function testPostPresentation($start_date = 1461510000, $end_date = 1461513600)
    {
        $params = array
        (
            'id' => 7,
        );

        $headers = array
        (
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        );

        $data = array
        (
            'title' => 'test presentation BCN',
            'description' => 'test presentation BCN',
            'allow_feedback' => true,
            'type_id' => 86,
            'tags' => ['tag#1', 'tag#2'],
            'speakers' => [1, 2, 3],
        );

        $response = $this->action
        (
            "POST",
            "OAuth2SummitEventsApiController@addEvent",
            $params,
            array(),
            array(),
            array(),
            $headers,
            json_encode($data)
        );

        $this->assertResponseStatus(201);

        $content = $response->getContent();
        $presentation = json_decode($content);

        $this->assertTrue($presentation->getId() > 0);
        return $presentation;
    }

    public function testUpdateEvent()
    {
        /*$event = $this->testPostEvent();
        unset($event->tags);*/
        $params = array
        (
            'id' => 3343,
            'event_id' => 107221,
        );

        $data = array
        (
            'title' => 'Using HTTPS to Secure OpenStack Services Update',
            'speakers' => [210, 9161, 202]
        );


        $headers = array
        (
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        );

        $response = $this->action
        (
            "PUT",
            "OAuth2SummitEventsApiController@updateEvent",
            $params,
            array(),
            array(),
            array(),
            $headers,
            json_encode($data)
        );

        $this->assertResponseStatus(200);
        $content = $response->getContent();
        $event = json_decode($content);
        $this->assertTrue($event->id > 0);
        return $event;

    }

    public function testPublishEvent($start_date = 1509789600, $end_date = 1509791400)
    {
        $event = $this->testPostEvent($summit_id = 23, $location_id = 0, $type_id = 124, $track_id = 206, $start_date, $end_date);
        unset($event->tags);

        $params = array
        (
            'id'         => $summit_id,
            'event_id'   => $event->id,
            'start_date' => $start_date,
            'end_date'   => $end_date
        );

        $headers = array
        (
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        );

        $response = $this->action
        (
            "PUT",
            "OAuth2SummitEventsApiController@publishEvent",
            $params,
            array(),
            array(),
            array(),
            $headers
        );

        $this->assertResponseStatus(204);

        return $event;
    }

    public function testUpdateEventOccupancy(){

        $params = array
        (
            'id' => 23,
            'event_id' => 20345,
        );

        $data = [
            'occupancy' => '25%'
        ];

        $headers = array
        (
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        );

        $response = $this->action
        (
            "PUT",
            "OAuth2SummitEventsApiController@updateEvent",
            $params,
            array(),
            array(),
            array(),
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $event = json_decode($content);
        $this->assertTrue($event->id > 0);
        return $event;
    }

    public function testUnPublishEvent()
    {
        $event = $this->testPublishEvent(1461529800, 1461533400);

        $params = array
        (
            'id' => 6,
            'event_id' => $event->getId(),
        );

        $headers = array
        (
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        );

        $response = $this->action
        (
            "DELETE",
            "OAuth2SummitEventsApiController@unPublishEvent",
            $params,
            array(),
            array(),
            array(),
            $headers
        );

        $this->assertResponseStatus(204);

        return $event;
    }

    public function testDeleteEvent($summit_id = 23, $event_id = 0)
    {
        if($event_id == 0) {
            $event = $this->testPostEvent($summit_id, $location_id = 0 , 117, 151, 0 , 0);
            $event_id = $event->id;
        }

        $params = [

            'id'       => $summit_id,
            'event_id' => $event_id,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action
        (
            "DELETE",
            "OAuth2SummitEventsApiController@deleteEvent",
            $params,
            [],
            [],
            [],
            $headers
        );

        $this->assertResponseStatus(204);
        //return $event;
    }

    public function testCurrentSummitEventsWithFilter($summit_id=27)
    {
        $params = [
            'id'       => $summit_id,
            "expand"   => "speakers,type",
            "page"     =>  1,
            "per_page" => 10,
            "filter"   =>  "title=@Project Leaders,abstract=@Project Leaders,tags=@Project Leaders,speaker=@Project Leaders,speaker_email=@Project Leaders,id==Project Leaders",
            "order"    => "+id"
        ];

        $headers = array
        (
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        );

        $response = $this->action
        (
            "GET",
            "OAuth2SummitEventsApiController@getEvents",
            $params,
            array(),
            array(),
            array(),
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);

        $events = json_decode($content);
        $this->assertTrue(!is_null($events));
    }

    public function testCurrentSummitEventsWithFilterCSV()
    {
        $params = array
        (
            'id' => 31,
            //'expand' => 'feedback',
            /*'filter' => [
                'published==1'
            ]*/
        );

        $headers = array
        (
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        );

        $response = $this->action
        (
            "GET",
            "OAuth2SummitEventsApiController@getEventsCSV",
            $params,
            array(),
            array(),
            array(),
            $headers
        );

        $csv = $response->getContent();
        $this->assertResponseStatus(200);


        $this->assertTrue(!empty($csv));
    }

    public function testCurrentSelectionMotiveSummitEvents()
    {
        $params = array
        (
            'id' => self::$summit->getId(),
            'filter'=> [
                'selection_status==selected||rejected||alternate'
            ]
        );

        $headers = array
        (
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        );


        $response = $this->action
        (
            "GET",
            "OAuth2SummitEventsApiController@getEvents",
            $params,
            array(),
            array(),
            array(),
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);

        $events = json_decode($content);
        $this->assertTrue(!is_null($events));
    }

    public function testCurrentSummitEventsBySummitType()
    {
        $params = array
        (
            'id' => 6,
            'expand' => 'feedback',
            'filter' => array
            (
                'summit_type_id==1',
            )
        );

        $headers = array
        (
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        );


        $response = $this->action
        (
            "GET",
            "OAuth2SummitEventsApiController@getEvents",
            $params,
            array(),
            array(),
            array(),
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);

        $events = json_decode($content);
        $this->assertTrue(!is_null($events));
    }

    public function testCurrentSummitPublishedEventsBySummitType()
    {
        $params = [

            'id' => 6,
            'expand' => 'feedback,location,location.venue,location.floor',
            'filter' => [
                'summit_type_id==2',
            ]
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action
        (
            "GET",
            "OAuth2SummitEventsApiController@getScheduledEvents",
            $params,
            array(),
            array(),
            array(),
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);

        $events = json_decode($content);
        $this->assertTrue(!is_null($events));
    }

    /**
     * @param int $summit_id
     * @param string $level
     */
    public function testGetScheduledEventsBySummitAndLevel($summit_id = 27, $level = 'N/A')
    {
        $params = [

            'id' => $summit_id,
            'expand' => 'feedback,location,location.venue,location.floor',
            'filter' => [
                "level=={$level}"
            ]
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action
        (
            "GET",
            "OAuth2SummitEventsApiController@getScheduledEvents",
            $params,
            array(),
            array(),
            array(),
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);

        $events = json_decode($content);
        $this->assertTrue(!is_null($events));
    }

    /**
     * @param int $summit_id
     */
    public function testGetScheduledEventsBySummit($summit_id = 27)
    {
        $params = [

            'id' => $summit_id,
            'expand' => 'type,track,location,location.venue,location.floor',
            'page' => 2,
            'per_page' => 100,
            'order'  => '+start_date'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action
        (
            "GET",
            "OAuth2SummitEventsApiController@getScheduledEvents",
            $params,
            array(),
            array(),
            array(),
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);

        $events = json_decode($content);
        $this->assertTrue(!is_null($events));
    }

    public function testGetScheduledEventsTags($summit_id = 27)
    {
        $params = [

            'id' => $summit_id,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action
        (
            "GET",
            "OAuth2SummitEventsApiController@getScheduledEventsTags",
            $params,
            array(),
            array(),
            array(),
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);

        $tags = json_decode($content);
        $this->assertTrue(!is_null($tags));
    }

    public function testGetORSpeakers($summit_id=24)
    {
        $params = array
        (
            'id' => $summit_id,
            'filter' => [
                'speaker_id==13987,speaker_id==12765'
            ]
        );

        $headers = array
        (
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        );


        $response = $this->action
        (
            "GET",
            "OAuth2SummitEventsApiController@getScheduledEvents",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);

        $events = json_decode($content);
        $this->assertTrue(!is_null($events));
    }

    public function testCurrentSummitPublishedEventsSummitTypeDesign()
    {
        $params = array
        (
            'id' => 6,
            'expand' => 'location',
            'filter' => array
            (
                "summit_type_id==2",
                "tags=@Magnum"
            )
        );

        $headers = array
        (
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        );


        $response = $this->action
        (
            "GET",
            "OAuth2SummitEventsApiController@getScheduledEvents",
            $params,
            array(),
            array(),
            array(),
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);

        $events = json_decode($content);
        $this->assertTrue(!is_null($events));
    }

    public function testCurrentSummitEventsBySummitTypeOR()
    {
        $params = array
        (
            'id' => 'current',
            'expand' => 'feedback',
            'filter' => array
            (
                'summit_type_id==2,tags=@Trove',
            )
        );

        $headers = array
        (
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        );


        $response = $this->action
        (
            "GET",
            "OAuth2SummitEventsApiController@getEvents",
            $params,
            array(),
            array(),
            array(),
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);

        $events = json_decode($content);
        $this->assertTrue(!is_null($events));
    }

    public function testCurrentSummitEventsBySummitTypeAND()
    {
        $params = array
        (
            'id' => 'current',
            'expand' => 'feedback',
            'filter' => array
            (
                'summit_type_id==2',
                'tags=@Trove',
            ),
        );

        $headers = array
        (
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        );


        $response = $this->action
        (
            "GET",
            "OAuth2SummitEventsApiController@getEvents",
            $params,
            array(),
            array(),
            array(),
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);

        $events = json_decode($content);
        $this->assertTrue(!is_null($events));
    }

    public function testCurrentSummitEventsByEventType()
    {
        $params = array
        (
            'id' => 'current',
            'expand' => 'feedback',
            'filter' => array
            (
                'event_type_id==4',
            )
        );

        $headers = array
        (
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        );


        $response = $this->action
        (
            "GET",
            "OAuth2SummitEventsApiController@getEvents",
            $params,
            array(),
            array(),
            array(),
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);

        $events = json_decode($content);
        $this->assertTrue(!is_null($events));
    }

    public function testGetScheduleEmptySpotsBySummit()
    {
        $summit_repository   = EntityManager::getRepository(\models\summit\Summit::class);
        $summit              = $summit_repository->getById(25);
        $summit_time_zone    = $summit->getTimeZone();
        $start_datetime      = new \DateTime( "2018-11-10 07:00:00", $summit_time_zone);
        $end_datetime        = new \DateTime("2018-11-10 22:00:00", $summit_time_zone);
        $start_datetime_unix = $start_datetime->getTimestamp();
        $end_datetime_unix   = $end_datetime->getTimestamp();

        $params = [

            'id' => 25,
            'filter' =>
                [
                    'location_id==391',
                    'start_date>='.$start_datetime_unix,
                    'end_date<='.$end_datetime_unix,
                    'gap>=30',
                ],
        ];

        $headers = [

            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action
        (
            "GET",
            "OAuth2SummitEventsApiController@getScheduleEmptySpots",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);

        $gaps = json_decode($content);
        $this->assertTrue(!is_null($gaps));
    }

    public function testGetUnpublishedEventBySummit()
    {
        $params = [

            'id' => 23,
            'filter' =>
                [
                    'selection_status==lightning-alternate',
                    'event_type_id==117',
                    'title=@test,abstract=@test,social_summary=@test,tags=@test,speaker=@test'
                ],
            'expand' => 'speakers',
        ];

        $headers = [

            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action
        (
            "GET",
            "OAuth2SummitEventsApiController@getUnpublishedEvents",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);

        $events = json_decode($content);
        $this->assertTrue(!is_null($events));
    }

    public function testGetUnpublishedEventBySummiOrderedByTrackSelChair($summit_id=27)
    {
        $params = [

            'id' => $summit_id,
            'order' => '+trackchairsel',
            'filter' =>
                [
                    'track_id==314',
                    'selection_status==accepted',
                ],
            'expand' => 'speakers',
        ];

        $headers = [

            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action
        (
            "GET",
            "OAuth2SummitEventsApiController@getUnpublishedEvents",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);

        $events = json_decode($content);
        $this->assertTrue(!is_null($events));
    }

    public function testGetUnpublishedEventBySummitOrderByTrack($summit_id=26)
    {
        $params = [

            'id' => $summit_id,
            'order' => '+track',
            'expand' => 'speakers',
        ];

        $headers = [

            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action
        (
            "GET",
            "OAuth2SummitEventsApiController@getUnpublishedEvents",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);

        $events = json_decode($content);
        $this->assertTrue(!is_null($events));
    }

    public function testGetAllEvents(){
        $params = array
        (
            'id' => self::$summit->getId(),
            'page' => 1,
            'per_page' => 5,
            'filter' => [
                'published==1',
                'type_allows_attendee_vote==1',
            ],
            'order' => 'random'
        );

        $headers = array
        (
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        );

        $response = $this->action
        (
            "GET",
            "OAuth2SummitEventsApiController@getEvents",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);

        $events = json_decode($content);
        $this->assertTrue(!is_null($events));
    }

    public function testGetAllPresentations(){
        $params = array
        (
            'id' => self::$summit->getId(),
            'page' => 1,
            'per_page' => 5,
            'order' => 'random'
        );

        $headers = array
        (
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        );

        $response = $this->action
        (
            "GET",
            "OAuth2SummitEventsApiController@getAllPresentations",
            $params,
            array(),
            array(),
            array(),
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);

        $events = json_decode($content);
        $this->assertTrue(!is_null($events));
    }

    public function testGetAllVoteablePresentations(){

        $service = App::make(IPresentationService::class);
        $summitPresentations = self::$summit->getPresentations();
        for ($i = 0; $i < count($summitPresentations); $i++) {
            $presentation = $summitPresentations[$i];
            $service->castAttendeeVote(self::$summit, self::$defaultMember, $presentation->getId());
            if (self::$defaultMember2 != null && $i % 3 == 0) {
                $service->castAttendeeVote(self::$summit, self::$defaultMember2, $presentation->getId());
            }
        }

        $start_datetime      = new \DateTime( 'now', new \DateTimeZone(SilverstripeBaseModel::DefaultTimeZone));
        $start_datetime->setTime(0,0,0);
        $end_datetime        = clone $start_datetime;
        $end_datetime->setTime(23,59,59);

        $params = array
        (
            'id' => self::$summit->getId(),
            'page' => 1,
            'per_page' => 5,
            'expand' => 'voters',
            'fields' => 'id,title,votes_count,voters.first_name,voters.last_name,voters.email',
            'relations'=> 'voters.none',
            'filter' => [
                'published==1',
                'presentation_attendee_vote_date>='.$start_datetime->getTimestamp(),
                'presentation_attendee_vote_date<='.$end_datetime->getTimestamp(),
            ],
            'order' => '-votes_count'
        );

        $headers = array
        (
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        );

        $response = $this->action
        (
            "GET",
            "OAuth2SummitEventsApiController@getAllVoteablePresentationsV2",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);

        $events = json_decode($content);
        $this->assertTrue(!is_null($events));
    }

    public function testGetAllScheduledEventsUsingOrder()
    {

        $params = array
        (
            'id' => self::$summit->getId(),
            'page' => 1,
            'per_page' => 5,
            'order' => 'random'
        );

        $headers = array
        (
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        );

        $response = $this->action
        (
            "GET",
            "OAuth2SummitEventsApiController@getScheduledEvents",
            $params,
            array(),
            array(),
            array(),
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);

        $events = json_decode($content);
        $this->assertTrue(!is_null($events));
    }

    public function testGetAllScheduledEvents()
    {

        $params = array
        (
            'id' => self::$summit->getId(),
            'page' => 1,
            'per_page' => 10,
        );

        $headers = array
        (
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        );

        $response = $this->action
        (
            "GET",
            "OAuth2SummitEventsApiController@getScheduledEvents",
            $params,
            array(),
            array(),
            array(),
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);

        $events = json_decode($content);
        $this->assertTrue(!is_null($events));
    }

    public function testCurrentSummitEventsByEventTypeExpandLocation($summit_id = 7)
    {
        $params = array
        (
            'id' => $summit_id,
            'expand' => 'feedback,location',
            'filter' => array
            (
                'event_type_id==91',
            )
        );

        $headers = array
        (
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        );

        $response = $this->action
        (
            "GET",
            "OAuth2SummitEventsApiController@getEvents",
            $params,
            array(),
            array(),
            array(),
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);

        $events = json_decode($content);
        $this->assertTrue(!is_null($events));
    }

    public function testGetSummitEventsExpandSpeaker($summit_id = 37)
    {
        $params = array
        (
            'id' => $summit_id,
            'expand' => 'speaker,type',
        );

        $headers = array
        (
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        );


        $response = $this->action
        (
            "GET",
            "OAuth2SummitEventsApiController@getEvents",
            $params,
            array(),
            array(),
            array(),
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);

        $events = json_decode($content);
        $this->assertTrue(!is_null($events));
    }

    public function testGetEvent()
    {
        $params = array
        (
            'id' => 7,
            'event_id' => 15303,
            'expand' => 'speakers',
        );

        $headers = array
        (
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        );


        $response = $this->action
        (
            "GET",
            "OAuth2SummitEventsApiController@getEvent",
            $params,
            array(),
            array(),
            array(),
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);

        $events = json_decode($content);
        $this->assertTrue(!is_null($events));
    }

    public function testGetPublishedEventFields()
    {

        $params = array
        (
            'id' => 7,
            'event_id' => 17300,
            'fields' => 'id, avg_feedback_rate, head_count',
            'relations' => 'metrics'
        );

        $headers = array
        (
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        );

        $response = $this->action
        (
            "GET",
            "OAuth2SummitEventsApiController@getScheduledEvent",
            $params,
            array(),
            array(),
            array(),
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);

        $events = json_decode($content);
        $this->assertTrue(!is_null($events));
    }

    public function testGetPublishedEventFieldsNotExists()
    {

        $params = array
        (
            'id' => 6,
            'event_id' => 8900,
            'fields' => 'id_test',
            'relations' => 'none'
        );

        $headers = array
        (
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        );


        $response = $this->action
        (
            "GET",
            "OAuth2SummitEventsApiController@getScheduledEvent",
            $params,
            array(),
            array(),
            array(),
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);

        $events = json_decode($content);
        $this->assertTrue(!is_null($events));
    }

    public function testGetPublishedEvent()
    {

        $params = array
        (
            'id' => 6,
            'event_id' => 8900,
        );

        $headers = array
        (
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        );


        $response = $this->action
        (
            "GET",
            "OAuth2SummitEventsApiController@getScheduledEvent",
            $params,
            array(),
            array(),
            array(),
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);

        $events = json_decode($content);
        $this->assertTrue(!is_null($events));
    }

    public function testGetMeEventFeedback()
    {
        $this->testAddFeedback2Event();

        $params = array
        (
            'id' => 6,
            'event_id' => 9454,
            'attendee_id' => 'me',
        );

        $headers = array
        (
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        );

        $response = $this->action
        (
            "GET",
            "OAuth2SummitEventsApiController@getEventFeedback",
            $params,
            array('expand' => 'owner'),
            array(),
            array(),
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);

        $feedback = json_decode($content);
        $this->assertTrue(!is_null($feedback));
    }

    public function testGetEventFeedback()
    {
        //$this->testAddFeedback2Event();

        $params = array
        (
            'id' => 7,
            'event_id' => 17300,
        );

        $headers = array
        (
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        );

        $response = $this->action
        (
            "GET",
            "OAuth2SummitEventsApiController@getEventFeedback",
            $params,
            array('expand' => 'owner'),
            array(),
            array(),
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);

        $feedback = json_decode($content);
        $this->assertTrue(!is_null($feedback));

        $response = $this->action
        (
            "GET",
            "OAuth2SummitEventsApiController@getEventFeedback",
            $params,
            array('expand' => 'owner'),
            array(),
            array(),
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);

        $feedback = json_decode($content);
        $this->assertTrue(!is_null($feedback));
    }

    public function testUpdateFeedback2EventByMember($summit_id = 27, $event_id = 24340)
    {
        //$this->testAddFeedback2EventByMember($summit_id, $event_id);
        $params = array
        (
            'id'       => $summit_id,
            'event_id' => $event_id,
        );

        $headers = array
        (
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        );

        $feedback_data = array
        (
            'rate' => 3,
            'note' => 'update',
        );

        $response = $this->action
        (
            "PUT",
            "OAuth2SummitEventsApiController@updateMyEventFeedbackReturnId",
            $params,
            array(),
            array(),
            array(),
            $headers,
            json_encode($feedback_data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);

    }

    public function testAddFeedback2Event()
    {
        $params = array
        (
            'id' => 7,
            'event_id' => 17300,
        );

        $headers = array
        (
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        );

        $feedback_data = array
        (
            'rate' => 10,
            'note' => 'nice presentation, wow!',
            'attendee_id' => 'me'
        );

        $response = $this->action
        (
            "POST",
            "OAuth2SummitEventsApiController@addEventFeedback",
            $params,
            array(),
            array(),
            array(),
            $headers,
            json_encode($feedback_data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);

    }

    public function testAddFeedback2EventByMember($summit_id = 27, $event_id = 24340)
    {
        $params = array
        (
            'id'       => $summit_id,
            'event_id' => $event_id,
        );

        $headers = array
        (
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        );

        $feedback_data = array
        (
            'rate' => 5,
        );

        $response = $this->action
        (
            "POST",
            "OAuth2SummitEventsApiController@addMyEventFeedbackReturnId",
            $params,
            array(),
            array(),
            array(),
            $headers,
            json_encode($feedback_data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(204);
    }

    public function testCloneEvent($summit_id = 25, $event_id= 22943)
    {
        $params = [

            'id' => $summit_id,
            'event_id' => $event_id,
        ];

        $headers = [

            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action
        (
            "POST",
            "OAuth2SummitEventsApiController@cloneEvent",
            $params,
            [], [], [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $event = json_decode($content);
        $this->assertTrue($event->id > 0);
        return $event;
    }


    /**
     * @param int $summit_id
     * @param int $event_id
     */
    public function testShareEvent($summit_id = 27, $event_id = 24344){
         $params = [
             'id' => $summit_id,
             'event_id' => $event_id,
         ];

        $headers = [

            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $payload = [
            'from' => 'smarcet@gmail.com',
            'to'   => 'smarcet@gmail.com',
        ];

        $response = $this->action
        (
            "POST",
            "OAuth2SummitEventsApiController@shareScheduledEventByEmail",
            $params,
            [], [], [],
            $headers,
            json_encode($payload)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
    }

    public function testCastVote(){
        $presentation = self::$presentations[count(self::$presentations) - 1];
        $params = [
            'id' => self::$summit->getId(),
            'presentation_id' => $presentation->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $payload = [
        ];

        $response = $this->action
        (
            "POST",
            "OAuth2PresentationApiController@castAttendeeVote",
            $params,
            [], [], [],
            $headers,
            json_encode($payload)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
    }

    public function testCurrentSummitEventsFilteredByDuration()
    {
        $params = array
        (
            'id' => self::$summit->getId(),
            'filter' => ['duration>0', 'duration<=60','speakers_count>1']
        );

        $headers = array
        (
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        );

        $response = $this->action
        (
            "GET",
            "OAuth2SummitEventsApiController@getEvents",
            $params,
            array(),
            array(),
            array(),
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);

        $events = json_decode($content);
        $this->assertTrue(!is_null($events));
    }
}