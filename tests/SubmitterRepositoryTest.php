<?php namespace Tests;
use App\ModelSerializers\IMemberSerializerTypes;
use LaravelDoctrine\ORM\Facades\EntityManager;
use models\main\Member;
use models\summit\Summit;
use ModelSerializers\SerializerRegistry;
use utils\FilterParser;
use utils\Order;
use utils\OrderElement;
use utils\PagingInfo;

/**
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

/**
 * Class AuditModelTest
 */
class SubmitterRepositoryTest extends ProtectedApiTestCase {
  public function testGetSubmittersBySummit() {
    $submitter_repository = EntityManager::getRepository(Member::class);
    $summit_repository = EntityManager::getRepository(Summit::class);

    $summit = $summit_repository->find(3401);

    $filter = FilterParser::parse(["filter" => "is_speaker==false"], ["is_speaker" => ["=="]]);

    $order = new Order([OrderElement::buildDescFor("id")]);

    $page = $submitter_repository->getSubmittersBySummit(
      $summit,
      new PagingInfo(1, 5),
      $filter,
      $order,
    );

    $params = [
      "summit" => $summit,
    ];

    foreach ($page->getItems() as $submitter) {
      $sm = SerializerRegistry::getInstance()
        ->getSerializer($submitter, IMemberSerializerTypes::Submitter)
        ->serialize(
          "accepted_presentations,alternate_presentations,rejected_presentations",
          [],
          [],
          $params,
        );
    }

    self::assertNotNull($page);
  }

  public function testGetSubmittersIdsBySummit() {
    $submitter_repository = EntityManager::getRepository(Member::class);
    $summit_repository = EntityManager::getRepository(Summit::class);

    $summit = $summit_repository->find(3363);

    $filter = FilterParser::parse(
      ["filter" => "has_rejected_presentations==false"],
      ["has_rejected_presentations" => ["=="]],
    );

    $order = new Order([OrderElement::buildDescFor("id")]);

    $submitterIds = $submitter_repository->getSubmittersIdsBySummit(
      $summit,
      new PagingInfo(1, 5),
      $filter,
      $order,
    );

    self::assertNotEmpty($submitterIds);
  }
}
