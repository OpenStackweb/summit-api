<?php namespace Tests;
use App\ModelSerializers\IMemberSerializerTypes;
use LaravelDoctrine\ORM\Facades\EntityManager;
use models\main\Member;
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
 * Class SubmitterRepositoryTest
 */
class SubmitterRepositoryTest extends ProtectedApiTestCase
{
    use InsertSummitTestData;

    protected function setUp(): void
    {
        parent::setUp();
        self::$defaultMember = self::$member;
        self::insertSummitTestData();
    }

    protected function tearDown(): void
    {
        self::clearSummitTestData();
        parent::tearDown();
    }

    public function testGetSubmittersBySummit(){

        $submitter_repository = EntityManager::getRepository(Member::class);

        $filter = FilterParser::parse(
            ["filter" => "is_speaker==false"],
            ["is_speaker" => ['==']]
        );

        $order = new Order([
            OrderElement::buildDescFor("id"),
        ]);

        $page = $submitter_repository->getSubmittersBySummit(self::$summit, new PagingInfo(1, 5), $filter, $order);

        $params = [
            "summit" => self::$summit
        ];

        foreach ($page->getItems() as $submitter) {
            $sm = SerializerRegistry::getInstance()->getSerializer($submitter, IMemberSerializerTypes::Submitter)
                ->serialize('accepted_presentations,alternate_presentations,rejected_presentations', [], [], $params);
        }

        self::assertNotNull($page);
    }

    public function testGetSubmittersIdsBySummit(){
        $submitter_repository = EntityManager::getRepository(Member::class);

        $filter = FilterParser::parse(
            ["filter" => "has_rejected_presentations==false"],
            ["has_rejected_presentations" => ['==']]
        );

        $order = new Order([
            OrderElement::buildDescFor("id"),
        ]);

        $submitterIds = $submitter_repository->getSubmittersIdsBySummit(self::$summit, new PagingInfo(1, 5), $filter, $order);

        self::assertIsArray($submitterIds);
    }

    public function testGetUniqueActivitiesCountBySummit(){
        $submitter_repository = EntityManager::getRepository(Member::class);

        $totalCount = $submitter_repository->getUniqueActivitiesCountBySummit(self::$summit, null);
        self::assertIsInt($totalCount);
        self::assertGreaterThanOrEqual(0, $totalCount);

        $filter = FilterParser::parse(
            ["filter" => "is_speaker==false"],
            ["is_speaker" => ['==']]
        );

        $filteredCount = $submitter_repository->getUniqueActivitiesCountBySummit(self::$summit, $filter);

        self::assertIsInt($filteredCount);
        self::assertLessThanOrEqual($totalCount, $filteredCount);
    }
}
