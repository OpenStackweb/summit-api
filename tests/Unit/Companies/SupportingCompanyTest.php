<?php namespace Tests\Unit\Companies;

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

use models\main\Company;
use models\main\ProjectSponsorshipType;
use models\main\SupportingCompany;
use Tests\BrowserKitTestCase;
use Tests\SetupEntityMananger;

/**
 * Class SupportingCompanyTest
 * @package Tests\unit
 */
class SupportingCompanyTest extends BrowserKitTestCase
{
    use SetupEntityMananger;

    /**
     * @throws \Exception
     */
    protected function setUp():void
    {
        parent::setUp();
        self::setupEntityManager();
    }

    public function tearDown():void
    {
        self::tearDownEntityManager();
        parent::tearDown();
    }

    public function test()
    {
        $child_entity = new Company();
        $child_entity->setName('Test Company');
        self::$em->persist($child_entity);

        $child_entity_2 = new ProjectSponsorshipType();
        $child_entity_2->setName('Test Project Type');
        $child_entity_2->setOrder(1);
        self::$em->persist($child_entity_2);

        $entity = new SupportingCompany();
        $entity->setOrder(1);
        $entity->setCompany($child_entity);
        $entity->setSponsorshipType($child_entity_2);

        self::$em->persist($entity);
        self::$em->flush();

        $repo = self::$em->getRepository(SupportingCompany::class);
        $found = $repo->find($entity->getId());

        $this->assertInstanceOf(SupportingCompany::class, $found);
        $this->assertEquals($entity->getCompany(), $found->getCompany());
        $this->assertEquals($entity->getSponsorshipType(), $found->getSponsorshipType());

        self::$em->remove($found);
    }
}