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
use models\main\SponsoredProject;
use Tests\BrowserKitTestCase;
use Tests\SetupEntityMananger;

/**
 * Class SponsoredProjectTest
 * @package Tests\unit
 */
class SponsoredProjectTest extends BrowserKitTestCase
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

        $entity = new SponsoredProject();
        $entity->setName('Test Sponsorship Type');
        $entity->setDescription('Test Sponsorship Type desc');
        $entity->setIsActive(true);

        self::$em->persist($entity);
        self::$em->flush();

        $repo = self::$em->getRepository(SponsoredProject::class);
        $found = $repo->find($entity->getId());

        $this->assertInstanceOf(SponsoredProject::class, $found);
        $this->assertEquals($entity->getName(), $found->getName());
        $this->assertEquals($entity->getDescription(), $found->getDescription());

        self::$em->remove($found);
    }
}