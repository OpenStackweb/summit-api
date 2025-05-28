<?php

namespace Tests\Unit\Entities;

/**
 * Copyright 2025 OpenStack Foundation
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

use models\summit\SummitEvent;
use Tests\InsertSummitTestData;
use Tests\TestCase;

class SummitEventTest extends TestCase
{
    use InsertSummitTestData;

    /**
     * @throws \Exception
     */
    protected function setUp():void
    {
        parent::setUp();
        self::insertSummitTestData();
    }

    public function tearDown():void
    {
        self::clearSummitTestData();
        parent::tearDown();
    }

    public function testAddEvent(){
        $summit = TestUtils::mockSummit();
        self::$em->persist($summit);

        $category = TestUtils::mockPresentationCategory($summit);
        self::$em->persist($category);

        $event_type = TestUtils::mockSummitEventType($summit);
        self::$em->persist($event_type);

        $event = TestUtils::mockSummitEvent($summit, $category, $event_type);

        $sponsor1 = self::$companies[0];
        $sponsor2 = self::$companies[1];

        $event->addSponsor($sponsor1);
        $event->addSponsor($sponsor2);
        self::$em->persist($sponsor1);
        self::$em->persist($sponsor2);

        $rsvp = TestUtils::mockRSVP($event);
        self::$em->persist($rsvp);

        self::$em->persist($event);
        self::$em->flush();
        self::$em->clear();

        $event_repository = self::$em->getRepository(SummitEvent::class);
        $found_event = $event_repository->find($event->getId());

        $this->assertInstanceOf(SummitEvent::class, $found_event);

        //Test ManyToOne relations
        $found_category = $found_event->getCategory();
        $this->assertEquals($category->getCode(), $found_category->getCode());

        //Test ManyToMany relations
        $this->assertCount(2, $found_event->getSponsors()->toArray());

        //Test OneToMany relations
        $this->assertCount(1, $found_event->getRsvp()->toArray());
    }

    public function testDeleteEventChildren(){
        $event = self::$summit->getEvents()[0];
        $this->assertNotEmpty($event->getTags()->toArray());

        $event->clearTags();

        self::$em->flush();
        self::$em->clear();

        $event_repository = self::$em->getRepository(SummitEvent::class);
        $found_event = $event_repository->find($event->getId());

        //Test ManyToMany relations
        $this->assertEmpty($found_event->getTags()->toArray());
    }
}