<?php namespace Tests;
use App\Models\Foundation\Main\IGroup;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\App;
use models\utils\SilverstripeBaseModel;
use services\model\IPresentationService;
use models\summit\SummitEvent;
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

final class OAuth2SummitEventsApiTest extends ProtectedApiTestCase {
  use InsertSummitTestData;

  use InsertOrdersTestData;

  protected function setUp(): void {
    $this->current_group = IGroup::TrackChairs;
    parent::setUp();
    self::$defaultMember = self::$member;
    self::$defaultMember2 = self::$member2;
    self::insertSummitTestData();
    self::InsertOrdersTestData();
  }

  public function tearDown(): void {
    self::clearSummitTestData();
    parent::tearDown();
  }

  public function testAddPublishableEvent(
    $start_date = 1477645200,
    $end_date = 1477647600,
    $location_id = 0,
  ) {
    $this->markTestSkipped("Skipped test: needs review");

    $params = [
      "id" => self::$summit->getId(),
    ];

    $data = [
      "title" => "Neutron: tbd",
      "description" => "TBD",
      "allow_feedback" => true,
      "type_id" => self::$defaultPresentationType->getId(),
      "tags" => ["Neutron"],
      "track_id" => self::$defaultTrack->getId(),
      "speakers" => [self::$defaultMember->getSpeaker()->getId()],
    ];

    if ($start_date > 0) {
      $data["start_date"] = $start_date;
    }

    if ($end_date > 0) {
      $data["end_date"] = $end_date;
    }

    if ($location_id > 0) {
      $data["location_id"] = $location_id;
    }

    $response = $this->action(
      "POST",
      "OAuth2SummitEventsApiController@addEvent",
      $params,
      [],
      [],
      [],
      $this->getAuthHeaders(),
      json_encode($data),
    );

    $content = $response->getContent();
    $this->assertResponseStatus(201);
    $event = json_decode($content);
    $this->assertTrue($event->id > 0);
    return $event;
  }

  public function testAddNonPublishableEventWithScheduledDates(
    $start_date = 1477645200,
    $end_date = 1477647600,
    $location_id = 0,
  ) {
    $this->markTestSkipped("Skipped test: needs review");

    $params = [
      "id" => self::$summit->getId(),
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $data = [
      "title" => "Neutron: tbd",
      "description" => "TBD",
      "allow_feedback" => true,
      "type_id" => self::$allow2VotePresentationType->getId(),
      "tags" => ["Neutron"],
      "track_id" => self::$defaultTrack->getId(),
    ];

    if ($start_date > 0) {
      $data["start_date"] = $start_date;
    }

    if ($end_date > 0) {
      $data["end_date"] = $end_date;
    }

    if ($location_id > 0) {
      $data["location_id"] = $location_id;
    }

    $response = $this->action(
      "POST",
      "OAuth2SummitEventsApiController@addEvent",
      $params,
      [],
      [],
      [],
      $headers,
      json_encode($data),
    );

    $content = $response->getContent();
    $this->assertResponseStatus(412);
    $error = json_decode($content);
  }

  public function testAddNonPublishableEventWithScheduledDatesAndLocation() {
    $this->markTestSkipped("Skipped test: needs review");

    $params = [
      "id" => self::$summit->getId(),
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $start_date = clone self::$summit->getBeginDate();
    $end_date = clone $start_date;
    $end_date = $end_date->add(new \DateInterval("P1D"));
    $data = [
      "title" => "Neutron: tbd",
      "description" => "TBD",
      "allow_feedback" => true,
      "type_id" => self::$allow2VotePresentationType->getId(),
      "tags" => ["Neutron"],
      "track_id" => self::$defaultTrack->getId(),
      "start_date" => $start_date->getTimestamp(),
      "end_date" => $end_date->getTimestamp(),
      "location_id" => self::$mainVenue->getId(),
    ];

    $response = $this->action(
      "POST",
      "OAuth2SummitEventsApiController@addEvent",
      $params,
      [],
      [],
      [],
      $headers,
      json_encode($data),
    );

    $content = $response->getContent();
    $this->assertResponseStatus(412);
    $error = json_decode($content);
  }

  public function testAddNonPublishableEvent() {
    $this->markTestSkipped("Skipped test: needs review");

    $params = [
      "id" => self::$summit->getId(),
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $start_date = clone self::$summit->getBeginDate();
    $end_date = clone $start_date;
    $end_date = $end_date->add(new \DateInterval("P1D"));
    $data = [
      "title" => "Neutron: tbd",
      "description" => "TBD",
      "allow_feedback" => true,
      "type_id" => self::$allow2VotePresentationType->getId(),
      "tags" => ["Neutron"],
      "track_id" => self::$defaultTrack->getId(),
    ];

    $response = $this->action(
      "POST",
      "OAuth2SummitEventsApiController@addEvent",
      $params,
      [],
      [],
      [],
      $headers,
      json_encode($data),
    );

    $content = $response->getContent();
    $this->assertResponseStatus(201);
    $event = json_decode($content);
    $this->assertTrue($event->id > 0);

    $params = [
      "id" => self::$summit->getId(),
      "event_id" => $event->id,
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $response = $this->action(
      "PUT",
      "OAuth2SummitEventsApiController@publishEvent",
      $params,
      [],
      [],
      [],
      $headers,
    );

    $this->assertResponseStatus(201);
    $content = $response->getContent();

    $event = json_decode($content);
    $this->assertTrue($event->id > 0);
    $this->assertTrue(is_null($event->start_date));
    $this->assertTrue($event->is_published == true);
  }

  public function testPostEventRSVPTemplateUnExistent() {
    $this->markTestSkipped("Skipped test: needs review");

    $params = [
      "id" => self::$summit->getId(),
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $data = [
      "title" => "Neutron: tbd",
      "description" => "TBD",
      "allow_feedback" => true,
      "type_id" => self::$allow2VotePresentationType->getId(),
      "tags" => ["Neutron"],
      "track_id" => self::$defaultTrack->getId(),
      "rsvp_template_id" => 1,
    ];

    $response = $this->action(
      "POST",
      "OAuth2SummitEventsApiController@addEvent",
      $params,
      [],
      [],
      [],
      $headers,
      json_encode($data),
    );

    $content = $response->getContent();
    $this->assertResponseStatus(412);
  }

  public function testPostEventRSVPTemplate(
    $summit_id = 23,
    $location_id = 0,
    $type_id = 124,
    $track_id = 208,
    $start_date = 0,
    $end_date = 0,
  ) {
    $this->markTestSkipped("Skipped test: needs review");

    $params = [
      "id" => $summit_id,
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $data = [
      "title" => "Neutron: tbd",
      "description" => "TBD",
      "allow_feedback" => true,
      "type_id" => $type_id,
      "tags" => ["Neutron"],
      "track_id" => $track_id,
      "rsvp_template_id" => 12,
    ];

    if ($start_date > 0) {
      $data["start_date"] = $start_date;
    }

    if ($end_date > 0) {
      $data["end_date"] = $end_date;
    }

    if ($location_id > 0) {
      $data["location_id"] = $location_id;
    }

    $response = $this->action(
      "POST",
      "OAuth2SummitEventsApiController@addEvent",
      $params,
      [],
      [],
      [],
      $headers,
      json_encode($data),
    );

    $content = $response->getContent();
    $this->assertResponseStatus(201);
    $event = json_decode($content);
    $this->assertTrue($event->id > 0);
    $this->assertTrue(!$event->rsvp_external);
    return $event;
  }

  public function testPostPresentationFail412($start_date = 1461510000, $end_date = 1461513600) {
    $this->markTestSkipped("Skipped test: needs review");

    $params = [
      "id" => self::$summit->getId(),
    ];

    $data = [
      "title" => "test presentation BCN",
      "description" => "test presentation BCN",
      "allow_feedback" => true,
      "type_id" => 86,
      "tags" => ["tag#1", "tag#2"],
    ];

    $response = $this->action(
      "POST",
      "OAuth2SummitEventsApiController@addEvent",
      $params,
      [],
      [],
      [],
      $this->getAuthHeaders(),
      json_encode($data),
    );

    $this->assertResponseStatus(412);
  }

  public function testPostPresentation() {
    $params = [
      "id" => self::$summit->getId(),
    ];

    $data = [
      "title" => "test presentation BCN",
      "description" => "test presentation BCN",
      "allow_feedback" => true,
      "type_id" => self::$defaultPresentationType->getId(),
      "track_id" => self::$defaultTrack->getId(),
      "tags" => ["tag#1", "tag#2"],
      "speakers" => [self::$defaultMember->getSpeaker()->getId()],
    ];

    $response = $this->action(
      "POST",
      "OAuth2SummitEventsApiController@addEvent",
      $params,
      [],
      [],
      [],
      $this->getAuthHeaders(),
      json_encode($data),
    );

    $this->assertResponseStatus(201);

    $content = $response->getContent();
    $presentation = json_decode($content);

    $this->assertTrue($presentation->id > 0);
    $this->assertEquals(SummitEvent::SOURCE_ADMIN, $presentation->submission_source);
    return $presentation;
  }

  public function testUpdateEvent() {
    $params = [
      "id" => self::$summit->getId(),
      "event_id" => self::$summit->getPresentations()[0]->getId(),
      "expand" => "allowed_ticket_types",
    ];

    $data = [
      "title" => "Using HTTPS to Secure OpenStack Services Update",
      "allowed_ticket_types" => [
        self::$summit->getTicketTypes()[0]->getId(),
        self::$summit->getTicketTypes()[1]->getId(),
      ],
    ];

    $response = $this->action(
      "PUT",
      "OAuth2SummitEventsApiController@updateEvent",
      $params,
      [],
      [],
      [],
      $this->getAuthHeaders(),
      json_encode($data),
    );

    $this->assertResponseStatus(200);
    $content = $response->getContent();
    $event = json_decode($content);
    $this->assertTrue($event->id > 0);
    $this->assertTrue(count($event->allowed_ticket_types) == 2);
    return $event;
  }

  public function testPublishEvent($start_date = 1509789600, $end_date = 1509791400) {
    $this->markTestSkipped("Skipped test: needs review");

    $event = $this->testPostEvent(
      $summit_id = 23,
      $location_id = 0,
      $type_id = 124,
      $track_id = 206,
      $start_date,
      $end_date,
    );
    unset($event->tags);

    $params = [
      "id" => $summit_id,
      "event_id" => $event->id,
      "start_date" => $start_date,
      "end_date" => $end_date,
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $response = $this->action(
      "PUT",
      "OAuth2SummitEventsApiController@publishEvent",
      $params,
      [],
      [],
      [],
      $headers,
    );

    $this->assertResponseStatus(204);

    return $event;
  }

  public function testPublishEventOnTimeRestrictedLocation(
    $start_date = 1677764037,
    $end_date = 1682861637,
  ) {
    $this->markTestSkipped("Skipped test: needs review");

    $params = [
      "id" => 3589,
      "event_id" => 116320,
      "start_date" => $start_date,
      "end_date" => $end_date,
      "opening_hour" => 1300,
      "closing_hour" => 1900,
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $response = $this->action(
      "PUT",
      "OAuth2SummitEventsApiController@publishEvent",
      $params,
      [],
      [],
      [],
      $headers,
    );

    $content = $response->getContent();
    $this->assertResponseStatus(204);
    $event = json_decode($content);
    $this->assertTrue($event->id > 0);
    return $event;
  }

  public function testUpdateEventOccupancy() {
    $this->markTestSkipped("Skipped test: needs review");

    $params = [
      "id" => 23,
      "event_id" => 20345,
    ];

    $data = [
      "occupancy" => "25%",
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $response = $this->action(
      "PUT",
      "OAuth2SummitEventsApiController@updateEvent",
      $params,
      [],
      [],
      [],
      $headers,
      json_encode($data),
    );

    $content = $response->getContent();
    $this->assertResponseStatus(200);
    $event = json_decode($content);
    $this->assertTrue($event->id > 0);
    return $event;
  }

  public function testUnPublishEvent() {
    $this->markTestSkipped("Skipped test: needs review");

    $event = $this->testPublishEvent(1461529800, 1461533400);

    $params = [
      "id" => 6,
      "event_id" => $event->getId(),
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $response = $this->action(
      "DELETE",
      "OAuth2SummitEventsApiController@unPublishEvent",
      $params,
      [],
      [],
      [],
      $headers,
    );

    $this->assertResponseStatus(204);

    return $event;
  }

  public function testDeleteEvent($summit_id = 23, $event_id = 0) {
    $this->markTestSkipped("Skipped test: needs review");

    if ($event_id == 0) {
      $event = $this->testPostEvent($summit_id, $location_id = 0, 117, 151, 0, 0);
      $event_id = $event->id;
    }

    $params = [
      "id" => $summit_id,
      "event_id" => $event_id,
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $response = $this->action(
      "DELETE",
      "OAuth2SummitEventsApiController@deleteEvent",
      $params,
      [],
      [],
      [],
      $headers,
    );

    $this->assertResponseStatus(204);
    //return $event;
  }

  public function testCurrentSummitEventsWithFilter($summit_id = 27) {
    $this->markTestSkipped("Skipped test: needs review");

    $params = [
      "id" => $summit_id,
      "expand" => "speakers,type",
      "page" => 1,
      "per_page" => 10,
      "filter" =>
        "title=@Project Leaders,abstract=@Project Leaders,tags=@Project Leaders,speaker=@Project Leaders,speaker_email=@Project Leaders,id==Project Leaders",
      "order" => "+id",
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $response = $this->action(
      "GET",
      "OAuth2SummitEventsApiController@getEvents",
      $params,
      [],
      [],
      [],
      $headers,
    );

    $content = $response->getContent();
    $this->assertResponseStatus(200);

    $events = json_decode($content);
    $this->assertTrue(!is_null($events));
  }

  public function testCurrentSummitEventsWithFilterCSV() {
    $this->markTestSkipped("Skipped test: needs review");

    $params = [
      "id" => 31,
      //'expand' => 'feedback',
      /*'filter' => [
                'published==1'
            ]*/
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $response = $this->action(
      "GET",
      "OAuth2SummitEventsApiController@getEventsCSV",
      $params,
      [],
      [],
      [],
      $headers,
    );

    $csv = $response->getContent();
    $this->assertResponseStatus(200);

    $this->assertTrue(!empty($csv));
  }

  public function testCurrentSelectionMotiveSummitEvents() {
    $this->markTestSkipped("Skipped test: needs review");

    $params = [
      "id" => self::$summit->getId(),
      "filter" => ["selection_status==selected||rejected||alternate"],
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $response = $this->action(
      "GET",
      "OAuth2SummitEventsApiController@getEvents",
      $params,
      [],
      [],
      [],
      $headers,
    );

    $content = $response->getContent();
    $this->assertResponseStatus(200);

    $events = json_decode($content);
    $this->assertTrue(!is_null($events));
  }

  public function testCurrentSummitEventsBySummitType() {
    $this->markTestSkipped("Skipped test: needs review");

    $params = [
      "id" => 6,
      "expand" => "feedback",
      "filter" => ["summit_type_id==1"],
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $response = $this->action(
      "GET",
      "OAuth2SummitEventsApiController@getEvents",
      $params,
      [],
      [],
      [],
      $headers,
    );

    $content = $response->getContent();
    $this->assertResponseStatus(200);

    $events = json_decode($content);
    $this->assertTrue(!is_null($events));
  }

  public function testCurrentSummitPublishedEventsBySummitType() {
    $this->markTestSkipped("Skipped test: needs review");

    $params = [
      "id" => 6,
      "expand" => "feedback,location,location.venue,location.floor",
      "filter" => ["summit_type_id==2"],
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $response = $this->action(
      "GET",
      "OAuth2SummitEventsApiController@getScheduledEvents",
      $params,
      [],
      [],
      [],
      $headers,
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
  public function testGetScheduledEventsBySummitAndLevel($summit_id = 27, $level = "N/A") {
    $this->markTestSkipped("Skipped test: needs review");

    $params = [
      "id" => $summit_id,
      "expand" => "feedback,location,location.venue,location.floor",
      "filter" => ["level=={$level}"],
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $response = $this->action(
      "GET",
      "OAuth2SummitEventsApiController@getScheduledEvents",
      $params,
      [],
      [],
      [],
      $headers,
    );

    $content = $response->getContent();
    $this->assertResponseStatus(200);

    $events = json_decode($content);
    $this->assertTrue(!is_null($events));
  }

  /**
   * @param int $summit_id
   */
  public function testGetScheduledEventsBySummit($summit_id = 27) {
    $this->markTestSkipped("Skipped test: needs review");

    $params = [
      "id" => $summit_id,
      "expand" => "type,track,location,location.venue,location.floor",
      "page" => 2,
      "per_page" => 100,
      "order" => "+start_date",
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $response = $this->action(
      "GET",
      "OAuth2SummitEventsApiController@getScheduledEvents",
      $params,
      [],
      [],
      [],
      $headers,
    );

    $content = $response->getContent();
    $this->assertResponseStatus(200);

    $events = json_decode($content);
    $this->assertTrue(!is_null($events));
  }

  public function testGetScheduledEventsTags($summit_id = 27) {
    $this->markTestSkipped("Skipped test: needs review");

    $params = [
      "id" => $summit_id,
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $response = $this->action(
      "GET",
      "OAuth2SummitEventsApiController@getScheduledEventsTags",
      $params,
      [],
      [],
      [],
      $headers,
    );

    $content = $response->getContent();
    $this->assertResponseStatus(200);

    $tags = json_decode($content);
    $this->assertTrue(!is_null($tags));
  }

  public function testGetORSpeakers($summit_id = 24) {
    $this->markTestSkipped("Skipped test: needs review");

    $params = [
      "id" => $summit_id,
      "filter" => ["speaker_id==13987,speaker_id==12765"],
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $response = $this->action(
      "GET",
      "OAuth2SummitEventsApiController@getScheduledEvents",
      $params,
      [],
      [],
      [],
      $headers,
    );

    $content = $response->getContent();
    $this->assertResponseStatus(200);

    $events = json_decode($content);
    $this->assertTrue(!is_null($events));
  }

  public function testCurrentSummitPublishedEventsSummitTypeDesign() {
    $this->markTestSkipped("Skipped test: needs review");

    $params = [
      "id" => 6,
      "expand" => "location",
      "filter" => ["summit_type_id==2", "tags=@Magnum"],
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $response = $this->action(
      "GET",
      "OAuth2SummitEventsApiController@getScheduledEvents",
      $params,
      [],
      [],
      [],
      $headers,
    );

    $content = $response->getContent();
    $this->assertResponseStatus(200);

    $events = json_decode($content);
    $this->assertTrue(!is_null($events));
  }

  public function testCurrentSummitEventsBySummitTypeOR() {
    $this->markTestSkipped("Skipped test: needs review");

    $params = [
      "id" => "current",
      "expand" => "feedback",
      "filter" => ["summit_type_id==2,tags=@Trove"],
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $response = $this->action(
      "GET",
      "OAuth2SummitEventsApiController@getEvents",
      $params,
      [],
      [],
      [],
      $headers,
    );

    $content = $response->getContent();
    $this->assertResponseStatus(200);

    $events = json_decode($content);
    $this->assertTrue(!is_null($events));
  }

  public function testCurrentSummitEventsBySummitTypeAND() {
    $this->markTestSkipped("Skipped test: needs review");

    $params = [
      "id" => "current",
      "expand" => "feedback",
      "filter" => ["summit_type_id==2", "tags=@Trove"],
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $response = $this->action(
      "GET",
      "OAuth2SummitEventsApiController@getEvents",
      $params,
      [],
      [],
      [],
      $headers,
    );

    $content = $response->getContent();
    $this->assertResponseStatus(200);

    $events = json_decode($content);
    $this->assertTrue(!is_null($events));
  }

  public function testCurrentSummitEventsByEventType() {
    $this->markTestSkipped("Skipped test: needs review");

    $params = [
      "id" => "current",
      "expand" => "feedback",
      "filter" => ["event_type_id==4"],
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $response = $this->action(
      "GET",
      "OAuth2SummitEventsApiController@getEvents",
      $params,
      [],
      [],
      [],
      $headers,
    );

    $content = $response->getContent();
    $this->assertResponseStatus(200);

    $events = json_decode($content);
    $this->assertTrue(!is_null($events));
  }

  public function testGetScheduleEmptySpotsBySummit() {
    $this->markTestSkipped("Skipped test: needs review");

    $summit_repository = EntityManager::getRepository(\models\summit\Summit::class);
    $summit = $summit_repository->getById(25);
    $summit_time_zone = $summit->getTimeZone();
    $start_datetime = new \DateTime("2018-11-10 07:00:00", $summit_time_zone);
    $end_datetime = new \DateTime("2018-11-10 22:00:00", $summit_time_zone);
    $start_datetime_unix = $start_datetime->getTimestamp();
    $end_datetime_unix = $end_datetime->getTimestamp();

    $params = [
      "id" => 25,
      "filter" => [
        "location_id==391",
        "start_date>=" . $start_datetime_unix,
        "end_date<=" . $end_datetime_unix,
        "gap>=30",
      ],
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $response = $this->action(
      "GET",
      "OAuth2SummitEventsApiController@getScheduleEmptySpots",
      $params,
      [],
      [],
      [],
      $headers,
    );

    $content = $response->getContent();
    $this->assertResponseStatus(200);

    $gaps = json_decode($content);
    $this->assertTrue(!is_null($gaps));
  }

  public function testGetUnpublishedEventBySummit() {
    $this->markTestSkipped("Skipped test: needs review");

    $params = [
      "id" => 23,
      "filter" => [
        "selection_status==lightning-alternate",
        "event_type_id==117",
        "title=@test,abstract=@test,social_summary=@test,tags=@test,speaker=@test",
      ],
      "expand" => "speakers",
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $response = $this->action(
      "GET",
      "OAuth2SummitEventsApiController@getUnpublishedEvents",
      $params,
      [],
      [],
      [],
      $headers,
    );

    $content = $response->getContent();
    $this->assertResponseStatus(200);

    $events = json_decode($content);
    $this->assertTrue(!is_null($events));
  }

  public function testGetUnpublishedEventBySummiOrderedByTrackSelChair($summit_id = 27) {
    $this->markTestSkipped("Skipped test: needs review");

    $params = [
      "id" => $summit_id,
      "order" => "+trackchairsel",
      "filter" => ["track_id==314", "selection_status==accepted"],
      "expand" => "speakers",
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $response = $this->action(
      "GET",
      "OAuth2SummitEventsApiController@getUnpublishedEvents",
      $params,
      [],
      [],
      [],
      $headers,
    );

    $content = $response->getContent();
    $this->assertResponseStatus(200);

    $events = json_decode($content);
    $this->assertTrue(!is_null($events));
  }

  public function testGetUnpublishedEventBySummitOrderByTrack($summit_id = 26) {
    $this->markTestSkipped("Skipped test: needs review");

    $params = [
      "id" => $summit_id,
      "order" => "+track",
      "expand" => "speakers",
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $response = $this->action(
      "GET",
      "OAuth2SummitEventsApiController@getUnpublishedEvents",
      $params,
      [],
      [],
      [],
      $headers,
    );

    $content = $response->getContent();
    $this->assertResponseStatus(200);

    $events = json_decode($content);
    $this->assertTrue(!is_null($events));
  }

  public function testGetAllEvents() {
    $this->markTestSkipped("Skipped test: needs review");

    $params = [
      "id" => self::$summit->getId(),
      "page" => 1,
      "per_page" => 5,
      "filter" => ["published==1", "type_allows_attendee_vote==1"],
      "order" => "random",
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $response = $this->action(
      "GET",
      "OAuth2SummitEventsApiController@getEvents",
      $params,
      [],
      [],
      [],
      $headers,
    );

    $content = $response->getContent();
    $this->assertResponseStatus(200);

    $events = json_decode($content);
    $this->assertTrue(!is_null($events));
  }

  public function testGetEventsByMediaUploadWithType() {
    $this->markTestSkipped("Skipped test: needs review");

    $params = [
      "id" => self::$summit->getId(),
      "page" => 1,
      "per_page" => 5,
      "filter" => ["has_media_upload_with_type==57"],
      "order" => "random",
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $response = $this->action(
      "GET",
      "OAuth2SummitEventsApiController@getEvents",
      $params,
      [],
      [],
      [],
      $headers,
    );

    $content = $response->getContent();
    $this->assertResponseStatus(200);
    $events = json_decode($content);
    $this->assertTrue(!is_null($events));
  }

  public function testGetAllPresentations() {
    $this->markTestSkipped("Skipped test: needs review");

    $params = [
      "id" => self::$summit->getId(),
      "page" => 1,
      "per_page" => 5,
      "order" => "random",
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $response = $this->action(
      "GET",
      "OAuth2SummitEventsApiController@getAllPresentations",
      $params,
      [],
      [],
      [],
      $headers,
    );

    $content = $response->getContent();
    $this->assertResponseStatus(200);
    $events = json_decode($content);
    $this->assertTrue(!is_null($events));
  }

  public function testGetAllVoteablePresentations() {
    $this->markTestSkipped("Skipped test: needs review");

    $service = App::make(IPresentationService::class);
    $summitPresentations = self::$summit->getPresentations();
    for ($i = 0; $i < count($summitPresentations); $i++) {
      $presentation = $summitPresentations[$i];
      $service->castAttendeeVote(self::$summit, self::$defaultMember, $presentation->getId());
      if (self::$defaultMember2 != null && $i % 3 == 0) {
        $service->castAttendeeVote(self::$summit, self::$defaultMember2, $presentation->getId());
      }
    }

    $start_datetime = new \DateTime(
      "now",
      new \DateTimeZone(SilverstripeBaseModel::DefaultTimeZone),
    );
    $start_datetime->setTime(0, 0, 0);
    $end_datetime = clone $start_datetime;
    $end_datetime->setTime(23, 59, 59);

    $params = [
      "id" => self::$summit->getId(),
      "page" => 1,
      "per_page" => 5,
      "expand" => "voters",
      "fields" => "id,title,votes_count,voters.first_name,voters.last_name,voters.email",
      "relations" => "voters.none",
      "filter" => [
        "published==1",
        "presentation_attendee_vote_date>=" . $start_datetime->getTimestamp(),
        "presentation_attendee_vote_date<=" . $end_datetime->getTimestamp(),
      ],
      "order" => "-votes_count",
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $response = $this->action(
      "GET",
      "OAuth2SummitEventsApiController@getAllVoteablePresentationsV2",
      $params,
      [],
      [],
      [],
      $headers,
    );

    $content = $response->getContent();
    $this->assertResponseStatus(200);

    $events = json_decode($content);
    $this->assertTrue(!is_null($events));
  }

  public function testGetAllScheduledEventsUsingOrder() {
    $this->markTestSkipped("Skipped test: needs review");

    $params = [
      "id" => self::$summit->getId(),
      "page" => 1,
      "per_page" => 5,
      "order" => "random",
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $response = $this->action(
      "GET",
      "OAuth2SummitEventsApiController@getScheduledEvents",
      $params,
      [],
      [],
      [],
      $headers,
    );

    $content = $response->getContent();
    $this->assertResponseStatus(200);

    $events = json_decode($content);
    $this->assertTrue(!is_null($events));
  }

  public function testGetAllScheduledEvents() {
    $this->markTestSkipped("Skipped test: needs review");

    $params = [
      "id" => self::$summit->getId(),
      "page" => 1,
      "per_page" => 10,
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $response = $this->action(
      "GET",
      "OAuth2SummitEventsApiController@getScheduledEvents",
      $params,
      [],
      [],
      [],
      $headers,
    );

    $content = $response->getContent();
    $this->assertResponseStatus(200);

    $events = json_decode($content);
    $this->assertTrue(!is_null($events));
  }

  public function testCurrentSummitEventsByEventTypeExpandLocation($summit_id = 7) {
    $this->markTestSkipped("Skipped test: needs review");

    $params = [
      "id" => $summit_id,
      "expand" => "feedback,location",
      "filter" => ["event_type_id==91"],
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $response = $this->action(
      "GET",
      "OAuth2SummitEventsApiController@getEvents",
      $params,
      [],
      [],
      [],
      $headers,
    );

    $content = $response->getContent();
    $this->assertResponseStatus(200);

    $events = json_decode($content);
    $this->assertTrue(!is_null($events));
  }

  public function testGetSummitEventsExpandSpeaker() {
    $this->markTestSkipped("Skipped test: needs review");

    $params = [
      "id" => self::$summit->getId(),
      "expand" => "speaker,type",
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $response = $this->action(
      "GET",
      "OAuth2SummitEventsApiController@getEvents",
      $params,
      [],
      [],
      [],
      $headers,
    );

    $content = $response->getContent();
    $this->assertResponseStatus(200);

    $events = json_decode($content);
    $this->assertTrue(!is_null($events));
  }

  public function testGetEvent() {
    $this->markTestSkipped("Skipped test: needs review");

    $params = [
      "id" => 2941,
      "event_id" => 91941,
      "expand" => "speakers",
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $response = $this->action(
      "GET",
      "OAuth2SummitEventsApiController@getEvent",
      $params,
      [],
      [],
      [],
      $headers,
    );

    $content = $response->getContent();
    $this->assertResponseStatus(200);

    $events = json_decode($content);
    $this->assertTrue(!is_null($events));
  }

  public function testGetPublishedEventFields() {
    $this->markTestSkipped("Skipped test: needs review");

    $params = [
      "id" => 7,
      "event_id" => 17300,
      "fields" => "id, avg_feedback_rate, head_count",
      "relations" => "metrics",
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $response = $this->action(
      "GET",
      "OAuth2SummitEventsApiController@getScheduledEvent",
      $params,
      [],
      [],
      [],
      $headers,
    );

    $content = $response->getContent();
    $this->assertResponseStatus(200);

    $events = json_decode($content);
    $this->assertTrue(!is_null($events));
  }

  public function testGetPublishedEventFieldsNotExists() {
    $this->markTestSkipped("Skipped test: needs review");

    $params = [
      "id" => 6,
      "event_id" => 8900,
      "fields" => "id_test",
      "relations" => "none",
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $response = $this->action(
      "GET",
      "OAuth2SummitEventsApiController@getScheduledEvent",
      $params,
      [],
      [],
      [],
      $headers,
    );

    $content = $response->getContent();
    $this->assertResponseStatus(200);

    $events = json_decode($content);
    $this->assertTrue(!is_null($events));
  }

  public function testGetPublishedEvent() {
    $this->markTestSkipped("Skipped test: needs review");

    $params = [
      "id" => 6,
      "event_id" => 8900,
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $response = $this->action(
      "GET",
      "OAuth2SummitEventsApiController@getScheduledEvent",
      $params,
      [],
      [],
      [],
      $headers,
    );

    $content = $response->getContent();
    $this->assertResponseStatus(200);

    $events = json_decode($content);
    $this->assertTrue(!is_null($events));
  }

  public function testGetMeEventFeedback() {
    $this->markTestSkipped("Skipped test: needs review");

    $this->testAddFeedback2Event();

    $params = [
      "id" => 6,
      "event_id" => 9454,
      "attendee_id" => "me",
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $response = $this->action(
      "GET",
      "OAuth2SummitEventsApiController@getEventFeedback",
      $params,
      ["expand" => "owner"],
      [],
      [],
      $headers,
    );

    $content = $response->getContent();
    $this->assertResponseStatus(200);

    $feedback = json_decode($content);
    $this->assertTrue(!is_null($feedback));
  }

  public function testGetEventFeedback() {
    $this->markTestSkipped("Skipped test: needs review");

    //$this->testAddFeedback2Event();

    $params = [
      "id" => 7,
      "event_id" => 17300,
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $response = $this->action(
      "GET",
      "OAuth2SummitEventsApiController@getEventFeedback",
      $params,
      ["expand" => "owner"],
      [],
      [],
      $headers,
    );

    $content = $response->getContent();
    $this->assertResponseStatus(200);

    $feedback = json_decode($content);
    $this->assertTrue(!is_null($feedback));

    $response = $this->action(
      "GET",
      "OAuth2SummitEventsApiController@getEventFeedback",
      $params,
      ["expand" => "owner"],
      [],
      [],
      $headers,
    );

    $content = $response->getContent();
    $this->assertResponseStatus(200);

    $feedback = json_decode($content);
    $this->assertTrue(!is_null($feedback));
  }

  public function testUpdateFeedback2EventByMember($summit_id = 27, $event_id = 24340) {
    $this->markTestSkipped("Skipped test: needs review");

    //$this->testAddFeedback2EventByMember($summit_id, $event_id);
    $params = [
      "id" => $summit_id,
      "event_id" => $event_id,
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $feedback_data = [
      "rate" => 3,
      "note" => "update",
    ];

    $response = $this->action(
      "PUT",
      "OAuth2SummitEventsApiController@updateMyEventFeedbackReturnId",
      $params,
      [],
      [],
      [],
      $headers,
      json_encode($feedback_data),
    );

    $content = $response->getContent();
    $this->assertResponseStatus(201);
  }

  public function testAddFeedback2Event() {
    $this->markTestSkipped("Skipped test: needs review");

    $params = [
      "id" => 7,
      "event_id" => 17300,
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $feedback_data = [
      "rate" => 10,
      "note" => "nice presentation, wow!",
      "attendee_id" => "me",
    ];

    $response = $this->action(
      "POST",
      "OAuth2SummitEventsApiController@addEventFeedback",
      $params,
      [],
      [],
      [],
      $headers,
      json_encode($feedback_data),
    );

    $content = $response->getContent();
    $this->assertResponseStatus(201);
  }

  public function testAddFeedback2EventByMember($summit_id = 27, $event_id = 24340) {
    $this->markTestSkipped("Skipped test: needs review");

    $params = [
      "id" => $summit_id,
      "event_id" => $event_id,
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $feedback_data = [
      "rate" => 5,
    ];

    $response = $this->action(
      "POST",
      "OAuth2SummitEventsApiController@addMyEventFeedbackReturnId",
      $params,
      [],
      [],
      [],
      $headers,
      json_encode($feedback_data),
    );

    $content = $response->getContent();
    $this->assertResponseStatus(204);
  }

  public function testCloneEvent($summit_id = 3693, $event_id = 119634) {
    $this->markTestSkipped("Skipped test: needs review");

    $params = [
      "id" => $summit_id,
      "event_id" => $event_id,
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $response = $this->action(
      "POST",
      "OAuth2SummitEventsApiController@cloneEvent",
      $params,
      [],
      [],
      [],
      $headers,
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
  public function testShareEvent($summit_id = 27, $event_id = 24344) {
    $this->markTestSkipped("Skipped test: needs review");

    $params = [
      "id" => $summit_id,
      "event_id" => $event_id,
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $payload = [
      "from" => "smarcet@gmail.com",
      "to" => "smarcet@gmail.com",
    ];

    $response = $this->action(
      "POST",
      "OAuth2SummitEventsApiController@shareScheduledEventByEmail",
      $params,
      [],
      [],
      [],
      $headers,
      json_encode($payload),
    );

    $content = $response->getContent();
    $this->assertResponseStatus(200);
  }

  public function testCastVote() {
    $this->markTestSkipped("Skipped test: needs review");

    $presentation = self::$presentations[count(self::$presentations) - 1];
    $params = [
      "id" => self::$summit->getId(),
      "presentation_id" => $presentation->getId(),
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $payload = [];

    $response = $this->action(
      "POST",
      "OAuth2PresentationApiController@castAttendeeVote",
      $params,
      [],
      [],
      [],
      $headers,
      json_encode($payload),
    );

    $content = $response->getContent();
    $this->assertResponseStatus(200);
  }

  public function testCurrentSummitEventsFilteredByDuration() {
    $this->markTestSkipped("Skipped test: needs review");

    $params = [
      "id" => self::$summit->getId(),
      "filter" => ["duration>0", "duration<=60", "speakers_count>1"],
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $response = $this->action(
      "GET",
      "OAuth2SummitEventsApiController@getEvents",
      $params,
      [],
      [],
      [],
      $headers,
    );

    $content = $response->getContent();
    $this->assertResponseStatus(200);

    $events = json_decode($content);
    $this->assertTrue(!is_null($events));
  }

  public function testImportEventData() {
    $this->markTestSkipped("Skipped test: needs review");

    /*        $csv_content = <<<CSV
        title,abstract,type,track,social_summary,allow_feedback,to_record,tags,speakers_names,speakers,start_date,end_date,is_published,selection_plan,attendees_expected_learnt,problem_addressed,location
        test1,test abstract1,TEST PRESENTATION TYPE,DEFAULT TRACK,social test1,1,1,tag1|tag2|tag3,Sebas Marcet|Sebas 1 Marcet|Sebas 2 Marcet,smarcet@gmail.com|smarcet+1@gmail.com,smarcet+2@gmail.com,2020-01-01 13:00:00,2020-01-01 13:45:00,1,TEST_SELECTION_PLAN,DEFAULT TRACK,big things,world issues,TEST VENUE
        test2,test abstract2,TEST PRESENTATION TYPE,DEFAULT TRACK,social test2,1,1,tag1|tag2,Sebas  Marcet,smarcet@gmail.com,2020-01-01 13:45:00,2020-01-01 14:45:00,1,TEST_SELECTION_PLAN,big things,world issues,TEST VENUE
        test3,test abstract3,TEST PRESENTATION TYPE,DEFAULT TRACK,social test3,1,1,tag4,Sebas 2 Marcet,smarcet+2@gmail.com,2020-01-01 14:45:00,2020-01-01 15:45:00,1,TEST_SELECTION_PLAN,big things,world issues,
        CSV;*/
    $csv_content = <<<CSV
    track,start_date,end_date,type,title,abstract,attendees_expected_learnt,social_summary ,speakers_names,speakers,selection_plan
    Security,2020-11-12 8:00:00,2020-11-12 9:00:00,Presentation,Security Projects Alignment,"OCP-Security scope / threat model
    Compare Resiliency approaches
    General role of RoT
    Alignment on security requirements across OCP Server sub-groups.",Cross-orgs alignment/sync on scope and approaches ,,JP Mon,jp@tipit.net,Draft Presentations Submissions
    CSV;

    $path = "/tmp/events.csv";

    file_put_contents($path, $csv_content);

    $file = new UploadedFile($path, "events.csv", "text/csv", null, true);

    $params = [
      "summit_id" => self::$summit->getId(),
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
    ];

    $response = $this->action(
      "POST",
      "OAuth2SummitEventsApiController@importEventData",
      $params,
      [
        "send_speaker_email" => true,
      ],
      [],
      [
        "file" => $file,
      ],
      $headers,
    );

    $this->assertResponseStatus(200);
  }
}
