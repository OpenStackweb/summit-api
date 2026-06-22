<?php namespace Tests;

use LaravelDoctrine\ORM\Facades\EntityManager;
use models\summit\PresentationSpeaker;
use utils\FilterParser;
use utils\Order;
use utils\OrderElement;
use utils\PagingInfo;

/**
 * Copyright 2026 OpenStack Foundation
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
 * Class SpeakerRepositoryTest
 * Regression tests for DoctrineSpeakerRepository::getAllByPage (two-phase refactor).
 */
class SpeakerRepositoryTest extends ProtectedApiTestCase
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

    private function repo()
    {
        return EntityManager::getRepository(PresentationSpeaker::class);
    }

    // -----------------------------------------------------------------
    // getAllByPage - basic pagination
    // -----------------------------------------------------------------

    public function testGetAllByPageReturnsPagingResponse(): void
    {
        $page = $this->repo()->getAllByPage(new PagingInfo(1, 10));

        $this->assertNotNull($page);
        $this->assertGreaterThan(0, $page->getTotal());
        foreach ($page->getItems() as $speaker) {
            $this->assertInstanceOf(PresentationSpeaker::class, $speaker);
            // Entity must be hydrated enough for the serializer to call these.
            $this->assertNotNull($speaker->getId());
        }
    }

    // -----------------------------------------------------------------
    // getAllByPage - filter by first_name
    // -----------------------------------------------------------------

    public function testGetAllByPageFilterByFirstName(): void
    {
        // InsertSummitTestData seeds a speaker with first_name = "Sebastian".
        $filter = FilterParser::parse(
            ['filter' => 'first_name==Sebastian'],
            ['first_name' => ['==']]
        );

        $page = $this->repo()->getAllByPage(new PagingInfo(1, 10), $filter);

        $this->assertGreaterThan(0, $page->getTotal());
        foreach ($page->getItems() as $speaker) {
            $this->assertEquals('Sebastian', $speaker->getFirstName());
        }
    }

    // -----------------------------------------------------------------
    // getAllByPage - filter by id
    // -----------------------------------------------------------------

    public function testGetAllByPageFilterById(): void
    {
        // Get any speaker to obtain a known ID.
        $all  = $this->repo()->getAllByPage(new PagingInfo(1, 1));
        $this->assertGreaterThan(0, $all->getTotal());
        $target = $all->getItems()[0];

        $filter = FilterParser::parse(
            ['filter' => 'id==' . $target->getId()],
            ['id' => ['==']]
        );

        $page = $this->repo()->getAllByPage(new PagingInfo(1, 10), $filter);

        $this->assertEquals(1, $page->getTotal());
        $this->assertEquals($target->getId(), $page->getItems()[0]->getId());
    }

    // -----------------------------------------------------------------
    // getAllByPage - not_id filter (was silently ignored in raw-SQL version)
    // -----------------------------------------------------------------

    public function testGetAllByPageNotIdFilterExcludesSpeaker(): void
    {
        $all = $this->repo()->getAllByPage(new PagingInfo(1, 100));
        $this->assertGreaterThan(1, $all->getTotal(), 'Need at least 2 speakers for not_id test');

        $excluded = $all->getItems()[0]->getId();

        $filter = FilterParser::parse(
            ['filter' => 'not_id==' . $excluded],
            ['not_id' => ['==']]
        );

        $page = $this->repo()->getAllByPage(new PagingInfo(1, 100), $filter);

        $ids = array_map(fn($s) => $s->getId(), $page->getItems());
        $this->assertNotContains($excluded, $ids);
        $this->assertEquals($all->getTotal() - 1, $page->getTotal());
    }

    // -----------------------------------------------------------------
    // getAllByPage - order
    // -----------------------------------------------------------------

    public function testGetAllByPageOrderByFirstNameAsc(): void
    {
        $order = new Order([OrderElement::buildAscFor('first_name')]);
        $page  = $this->repo()->getAllByPage(new PagingInfo(1, 50), null, $order);

        $names = array_map(fn($s) => strtolower((string) $s->getFirstName()), $page->getItems());
        $sorted = $names;
        sort($sorted);
        $this->assertEquals($sorted, $names);
    }
}
