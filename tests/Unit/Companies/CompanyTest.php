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
use Tests\BrowserKitTestCase;
use Tests\SetupEntityMananger;

/**
 * Class CompanyTest
 * @package Tests\unit
 */
class CompanyTest extends BrowserKitTestCase
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
        $company = new Company();
        $company->setName('Test Company');
        $company->setDescription('Test Company desc');
        $company->setCity('Test City');
        $company->setCountry('Test Country');
        $company->setUrl('www.google.com');

        self::$em->persist($company);
        self::$em->flush();

        $repo = self::$em->getRepository(Company::class);
        $found = $repo->find($company->getId());

        $this->assertInstanceOf(Company::class, $found);
        $this->assertEquals($company->getName(), $found->getName());
        $this->assertEquals($company->getDescription(), $found->getDescription());
        $this->assertEquals($company->getCity(), $found->getCity());
        $this->assertEquals($company->getCountry(), $found->getCountry());
        $this->assertEquals($company->getUrl(), $found->getUrl());

        self::$em->remove($found);
    }
}