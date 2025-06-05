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

use models\main\File;
use models\summit\Summit;
use Tests\InsertSummitTestData;
use Tests\TestCase;

class SummitTest extends TestCase
{
    use InsertSummitTestData;

    /**
     * @throws \Exception
     */
    protected function setUp():void
    {
        parent::setUp();
        self::insertSummitTestData();

        self::$summit->addRegistrationCompany(self::$companies[0]);
        self::$em->flush();
        self::$em->clear();
    }

    public function tearDown():void
    {
        self::clearSummitTestData();
        parent::tearDown();
    }

    public function testUpdateSummit(){
        $summit_id = self::$summit->getId();
        $repository = self::$em->getRepository(Summit::class);
        $summit = $repository->find($summit_id);

        $logo = new File();
        $logo->setFilename("test_logo.png");
        self::$em->persist($logo);

        $summit->setLogo($logo);

        $previous_categories_count = count($summit->getPresentationCategories()->toArray());
        $category = TestUtils::mockPresentationCategory($summit);
        self::$em->persist($category);
        $summit->addPresentationCategory($category);

        self::$em->flush();
        self::$em->clear();

        $found_summit = $repository->find($summit->getId());

        //Test ManyToOne relations
        $found_logo = $found_summit->getLogo();
        $this->assertEquals($logo->getId(), $found_logo->getId());

        //Test OneToMany relations
        $this->assertCount($previous_categories_count + 1, $found_summit->getPresentationCategories()->toArray());
    }

    public function testDeleteSummitChildren(){
        $summit_id = self::$summit->getId();
        $repository = self::$em->getRepository(Summit::class);
        $summit = $repository->find($summit_id);

        $event_types = $summit->getEventTypes()->toArray();
        $previous_event_types_count = count($event_types);
        $summit->removeEventType($event_types[0]);

        $registration_companies = $summit->getRegistrationCompanies()->toArray();
        $previous_registration_companies_count = count($registration_companies);
        $summit->removeRegistrationCompany($registration_companies[0]);

        self::$em->flush();
        self::$em->clear();

        $found_summit = $repository->find($summit->getId());

        //Test ManyToMany relations
        $this->assertCount($previous_registration_companies_count - 1, $found_summit->getRegistrationCompanies()->toArray());

        //Test OneToMany relations
        $this->assertCount($previous_event_types_count - 1, $found_summit->getEventTypes()->toArray());

    }
}