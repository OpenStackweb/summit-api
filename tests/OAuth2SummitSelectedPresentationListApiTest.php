<?php namespace Tests;
/**
 * Copyright 2021 OpenStack Foundation
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
use models\summit\SummitSelectedPresentation;
/**
 * Class OAuth2SummitSelectedPresentationListApiTest
 * @package Tests
 */
final class OAuth2SummitSelectedPresentationListApiTest extends ProtectedApiTestCase {
  use InsertSummitTestData;

  use InsertMemberTestData;

  protected function setUp(): void {
    $this->setCurrentGroup(IGroup::TrackChairs);
    parent::setUp();
    self::insertSummitTestData();
    self::$summit_permission_group->addMember(self::$member);
    self::$em->persist(self::$summit);
    self::$em->persist(self::$summit_permission_group);
    self::$em->flush();
    $track_chair = self::$summit->addTrackChair(self::$member, [self::$defaultTrack]);
    self::$em->persist(self::$summit);
    self::$em->flush();
  }

  protected function tearDown(): void {
    self::clearSummitTestData();
    parent::tearDown();
  }

  /**
   * @return mixed
   */
  public function testAddIndividualSelectionListAndAddSelection() {
    $params = [
      "id" => self::$summit->getId(),
      "track_id" => self::$defaultTrack->getId(),
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $response = $this->action(
      "POST",
      "OAuth2SummitSelectedPresentationListApiController@createIndividualSelectionList",
      $params,
      [],
      [],
      [],
      $headers,
      "",
    );

    $content = $response->getContent();
    $this->assertResponseStatus(201);
    $selection_list = json_decode($content);
    $this->assertTrue(!is_null($selection_list));

    $params = [
      "id" => self::$summit->getId(),
      "track_id" => self::$defaultTrack->getId(),
      "collection" => SummitSelectedPresentation::CollectionSelected,
      "presentation_id" => self::$presentations[0]->getId(),
      "expand" => "selected_presentations,interested_presentations,",
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $response = $this->action(
      "POST",
      "OAuth2SummitSelectedPresentationListApiController@assignPresentationToMyIndividualList",
      $params,
      [],
      [],
      [],
      $headers,
      "",
    );

    $content = $response->getContent();
    $this->assertResponseStatus(201);
    $selection_list = json_decode($content);
    $this->assertTrue(!is_null($selection_list));
    $this->assertTrue(count($selection_list->selected_presentations) > 0);
    return $selection_list;
  }

  /**
   * @return mixed
   */
  public function testAddIndividualSelectionListAndReorder() {
    $params = [
      "id" => self::$summit->getId(),
      "track_id" => self::$defaultTrack->getId(),
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $response = $this->action(
      "POST",
      "OAuth2SummitSelectedPresentationListApiController@createIndividualSelectionList",
      $params,
      [],
      [],
      [],
      $headers,
      "",
    );

    $content = $response->getContent();
    $this->assertResponseStatus(201);
    $selection_list = json_decode($content);
    $this->assertTrue(!is_null($selection_list));

    $params = [
      "id" => self::$summit->getId(),
      "track_id" => self::$defaultTrack->getId(),
      "collection" => SummitSelectedPresentation::CollectionSelected,
      "presentation_id" => self::$presentations[0]->getId(),
      "expand" => "selected_presentations,interested_presentations,",
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $response = $this->action(
      "POST",
      "OAuth2SummitSelectedPresentationListApiController@assignPresentationToMyIndividualList",
      $params,
      [],
      [],
      [],
      $headers,
      "",
    );

    $content = $response->getContent();
    $this->assertResponseStatus(201);

    $params = [
      "id" => self::$summit->getId(),
      "track_id" => self::$defaultTrack->getId(),
      "list_id" => $selection_list->id,
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $data = [
      "collection" => SummitSelectedPresentation::CollectionSelected,
      "presentations" => [
        self::$presentations[1]->getId(),
        self::$presentations[0]->getId(),
        self::$presentations[2]->getId(),
      ],
    ];

    $response = $this->action(
      "PUT",
      "OAuth2SummitSelectedPresentationListApiController@reorderSelectionList",
      $params,
      [],
      [],
      [],
      $headers,
      json_encode($data),
    );

    $content = $response->getContent();
    $this->assertResponseStatus(201);
    $selection_list = json_decode($content);
    $this->assertTrue(!is_null($selection_list));
    $this->assertTrue(count($selection_list->selected_presentations) == 3);

    return $selection_list;
  }

  /**
   * @return mixed
   */
  public function testAddGroupSelectionListAndReorder() {
    $params = [
      "id" => self::$summit->getId(),
      "track_id" => self::$defaultTrack->getId(),
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $response = $this->action(
      "POST",
      "OAuth2SummitSelectedPresentationListApiController@createTeamSelectionList",
      $params,
      [],
      [],
      [],
      $headers,
      "",
    );

    $content = $response->getContent();
    $this->assertResponseStatus(201);
    $selection_list = json_decode($content);
    $this->assertTrue(!is_null($selection_list));

    $params = [
      "id" => self::$summit->getId(),
      "track_id" => self::$defaultTrack->getId(),
      "list_id" => $selection_list->id,
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $data = [
      "collection" => SummitSelectedPresentation::CollectionSelected,
      "presentations" => [
        self::$presentations[1]->getId(),
        self::$presentations[0]->getId(),
        self::$presentations[2]->getId(),
      ],
      "hash" => "",
    ];

    $response = $this->action(
      "PUT",
      "OAuth2SummitSelectedPresentationListApiController@reorderSelectionList",
      $params,
      [],
      [],
      [],
      $headers,
      json_encode($data),
    );

    $content = $response->getContent();
    $this->assertResponseStatus(201);
    $selection_list = json_decode($content);
    $this->assertTrue(!is_null($selection_list));
    $this->assertTrue(count($selection_list->selected_presentations) == 3);
    $this->assertTrue(property_exists($selection_list, "hash"));
    $this->assertTrue(!empty($selection_list->hash));
    return $selection_list;
  }

  /**
   * @return mixed
   */
  public function testAddIndividualSelectionListAndReorderRemove() {
    $params = [
      "id" => self::$summit->getId(),
      "track_id" => self::$defaultTrack->getId(),
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $response = $this->action(
      "POST",
      "OAuth2SummitSelectedPresentationListApiController@createIndividualSelectionList",
      $params,
      [],
      [],
      [],
      $headers,
      "",
    );

    $content = $response->getContent();
    $this->assertResponseStatus(201);
    $selection_list = json_decode($content);
    $this->assertTrue(!is_null($selection_list));

    $params = [
      "id" => self::$summit->getId(),
      "track_id" => self::$defaultTrack->getId(),
      "collection" => SummitSelectedPresentation::CollectionSelected,
      "presentation_id" => self::$presentations[0]->getId(),
      "expand" => "selected_presentations,interested_presentations,",
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $response = $this->action(
      "POST",
      "OAuth2SummitSelectedPresentationListApiController@assignPresentationToMyIndividualList",
      $params,
      [],
      [],
      [],
      $headers,
      "",
    );

    $content = $response->getContent();
    $this->assertResponseStatus(201);

    $params = [
      "id" => self::$summit->getId(),
      "track_id" => self::$defaultTrack->getId(),
      "list_id" => $selection_list->id,
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $data = [
      "collection" => SummitSelectedPresentation::CollectionSelected,
      "presentations" => [self::$presentations[1]->getId(), self::$presentations[2]->getId()],
    ];

    $response = $this->action(
      "PUT",
      "OAuth2SummitSelectedPresentationListApiController@reorderSelectionList",
      $params,
      [],
      [],
      [],
      $headers,
      json_encode($data),
    );

    $content = $response->getContent();
    $this->assertResponseStatus(201);
    $selection_list = json_decode($content);
    $this->assertTrue(!is_null($selection_list));
    $this->assertTrue(count($selection_list->selected_presentations) == 2);

    return $selection_list;
  }

  /**
   * @return mixed
   */
  public function testAddIndividualSelectionListAndReorderRemoveAll() {
    $params = [
      "id" => self::$summit->getId(),
      "track_id" => self::$defaultTrack->getId(),
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $response = $this->action(
      "POST",
      "OAuth2SummitSelectedPresentationListApiController@createIndividualSelectionList",
      $params,
      [],
      [],
      [],
      $headers,
      "",
    );

    $content = $response->getContent();
    $this->assertResponseStatus(201);
    $selection_list = json_decode($content);
    $this->assertTrue(!is_null($selection_list));

    $params = [
      "id" => self::$summit->getId(),
      "track_id" => self::$defaultTrack->getId(),
      "collection" => SummitSelectedPresentation::CollectionSelected,
      "presentation_id" => self::$presentations[0]->getId(),
      "expand" => "selected_presentations,interested_presentations,",
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $response = $this->action(
      "POST",
      "OAuth2SummitSelectedPresentationListApiController@assignPresentationToMyIndividualList",
      $params,
      [],
      [],
      [],
      $headers,
      "",
    );

    $content = $response->getContent();
    $this->assertResponseStatus(201);

    $params = [
      "id" => self::$summit->getId(),
      "track_id" => self::$defaultTrack->getId(),
      "list_id" => $selection_list->id,
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    // add
    $data = [
      "collection" => SummitSelectedPresentation::CollectionSelected,
      "presentations" => [self::$presentations[1]->getId(), self::$presentations[2]->getId()],
    ];

    $response = $this->action(
      "PUT",
      "OAuth2SummitSelectedPresentationListApiController@reorderSelectionList",
      $params,
      [],
      [],
      [],
      $headers,
      json_encode($data),
    );

    $content = $response->getContent();
    $this->assertResponseStatus(201);
    $selection_list = json_decode($content);
    $this->assertTrue(!is_null($selection_list));
    $this->assertTrue(count($selection_list->selected_presentations) == 2);

    $data = [
      "collection" => SummitSelectedPresentation::CollectionSelected,
      "presentations" => [],
    ];

    // remove all
    $response = $this->action(
      "PUT",
      "OAuth2SummitSelectedPresentationListApiController@reorderSelectionList",
      $params,
      [],
      [],
      [],
      $headers,
      json_encode($data),
    );

    $content = $response->getContent();
    $this->assertResponseStatus(201);
    $selection_list = json_decode($content);
    $this->assertTrue(!is_null($selection_list));
    $this->assertTrue(count($selection_list->selected_presentations) == 0);

    return $selection_list;
  }
}
