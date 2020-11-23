<?php namespace Tests;

use LaravelDoctrine\ORM\Facades\EntityManager;
use models\main\Company;
use models\main\ProjectSponsorshipType;
use models\main\SponsoredProject;
use models\summit\SponsorshipType;

/**
 * Copyright 2020 OpenStack Foundation
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
 * Class SponsoredProjectModelTest
 * @package Tests
 */
class SponsoredProjectModelTest  extends BrowserKitTestCase
{
    use \InsertSummitTestData;

    protected function setUp()
    {
        parent::setUp();

        self::insertTestData();
        self::$em->persist(self::$summit);
        self::$em->flush();
    }

    protected function tearDown()
    {
        self::clearTestData();
        parent::tearDown();
    }

    public function testCreateSponsoredProject(){

        $company_repository = EntityManager::getRepository(Company::class);

        $p1 = new SponsoredProject();
        $p1->setName("Kata Containers");
        $p1->setIsActive(true);

        $sponsorship1 = new ProjectSponsorshipType();
        $sponsorship1->setName("PLATINUM MEMBERS");
        $description1 = <<<HTML
Open Infrastructure Foundation Platinum Members provide a significant portion of the funding to achieve the Foundation's mission of protecting, empowering and promoting the Open Infrastructure community and open source software projects. Each Platinum Member's company strategy aligns with the OIF mission and is responsible for committing full-time resources toward the project. There are eight Platinum Members at any given time, each of which holds a seat on the Board of Directors. Thank you to the following Platinum Members who are committed to the Open Infrastructure community's success.
HTML;

        $sponsorship1->setDescription($description1);
        $sponsorship1->setIsActive(true);

        $p1->addSponsorshipType($sponsorship1);

        $companies = $company_repository->findAll();

        $sponsorship1->addSupportingCompany($companies[0]);
        $sponsorship1->addSupportingCompany($companies[1]);

        self::$em->persist($p1);
        self::$em->flush();
    }
}