<?php namespace Tests;
/*
 * Copyright 2023 OpenStack Foundation
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
use LaravelDoctrine\ORM\Facades\EntityManager;
use models\summit\Summit;
use models\summit\SummitAttendeeBadge;

final class OAuth2SummitAttendeeBadgePrintApiControllerTest extends ProtectedApiTestCase {
  use InsertSummitTestData;

  use InsertMemberTestData;

  protected function setUp(): void {
    parent::setUp();
    self::insertMemberTestData(IGroup::FoundationMembers);
    self::$defaultMember = self::$member;
    self::$defaultMember2 = self::$member2;
    self::insertSummitTestData();
  }

  protected function tearDown(): void {
    self::clearSummitTestData();
    parent::tearDown();
  }

  public function testDeletePrintsFromBadge() {
    $attendee = self::$summit->getAttendees()[0];
    $ticket = $attendee->getFirstTicket();
    $this->assertTrue(!is_null($ticket));
    $badge = $ticket->getBadge();
    $this->assertTrue(!is_null($badge));
    // create test print
    $badge->printIt(self::$member, self::$default_badge_view_type);
    self::$em->flush();

    $params = [
      "id" => self::$summit->getId(),
      "ticket_id" => $ticket->getId(),
    ];

    $this->action(
      "DELETE",
      "OAuth2SummitAttendeeBadgePrintApiController@deleteBadgePrints",
      $params,
      [],
      [],
      [],
      $this->getAuthHeaders(),
    );

    $this->assertResponseStatus(204);

    $badge_repository = EntityManager::getRepository(SummitAttendeeBadge::class);
    $badge_from_db = $badge_repository->find($badge->getId());
    $this->assertTrue(!is_null($badge_from_db));
    $this->assertFalse($badge_from_db->isPrinted());
  }
}
