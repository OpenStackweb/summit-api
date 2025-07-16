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

use models\summit\SummitSponsorship;
use models\summit\SummitSponsorshipAddOn;
use models\summit\SummitSponsorshipType;
use Tests\InsertSummitTestData;
use Tests\TestCase;

class SummitSponsorshipTest extends TestCase
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

    public function testAddSummitSponsorship(){
        // Create a new sponsorship
        $sponsorship = new SummitSponsorship();
        self::$em->persist($sponsorship);
        
        // Get an existing sponsor from the test data
        $sponsor = self::$sponsors[0];
        
        // Set the sponsor (ManyToOne relationship)
        $sponsorship->setSponsor($sponsor);

        $summit_sponsorship_type = new SummitSponsorshipType();
        $summit_sponsorship_type->setType(self::$default_sponsor_ship_type);
        self::$em->persist($summit_sponsorship_type);
        self::$summit->addSponsorshipType(self::$default_summit_sponsor_type);
        
        // Set the type (ManyToOne relationship)
        $sponsorship->setType($summit_sponsorship_type);
        
        // Create a new add-on
        $add_on = new SummitSponsorshipAddOn();
        $add_on->setName("Test Add-On " . str_random(5));
        $add_on->setType(SummitSponsorshipAddOn::Booth_Type);
        self::$em->persist($add_on);
        
        // Add the add-on (OneToMany relationship)
        $sponsorship->addAddOn($add_on);
        
        self::$em->flush();
        self::$em->clear();
        
        // Retrieve the sponsorship from the database
        $repository = self::$em->getRepository(SummitSponsorship::class);
        $found_sponsorship = $repository->find($sponsorship->getId());
        
        // Test ManyToOne relationship with sponsor
        $found_sponsor = $found_sponsorship->getSponsor();
        $this->assertEquals($sponsor->getId(), $found_sponsor->getId());
        
        // Test ManyToOne relationship with type
        $found_type = $found_sponsorship->getType();
        $this->assertEquals($summit_sponsorship_type->getId(), $found_type->getId());
        
        // Test OneToMany relationship with add-ons
        $found_add_ons = $found_sponsorship->getAddOns()->toArray();
        $this->assertNotEmpty($found_add_ons);
        $found_add_on = null;
        foreach ($found_add_ons as $ao) {
            if ($ao->getName() === $add_on->getName()) {
                $found_add_on = $ao;
                break;
            }
        }
        $this->assertNotNull($found_add_on);
    }

    public function testDeleteSummitSponsorshipChildren(){
        // Create a new sponsorship
        $sponsorship = new SummitSponsorship();
        self::$em->persist($sponsorship);
        
        // Get an existing sponsor from the test data
        $sponsor = self::$sponsors[0];
        
        // Set the sponsor (ManyToOne relationship)
        $sponsorship->setSponsor($sponsor);
        
        $summit_sponsorship_type = new SummitSponsorshipType();
        $summit_sponsorship_type->setType(self::$default_sponsor_ship_type);
        self::$em->persist($summit_sponsorship_type);
        self::$summit->addSponsorshipType(self::$default_summit_sponsor_type);
        
        // Set the type (ManyToOne relationship)
        $sponsorship->setType($summit_sponsorship_type);
        
        // Create a new add-on
        $add_on = new SummitSponsorshipAddOn();
        $add_on->setName("Test Add-On " . str_random(5));
        $add_on->setType(SummitSponsorshipAddOn::Booth_Type);
        self::$em->persist($add_on);
        
        // Add the add-on (OneToMany relationship)
        $sponsorship->addAddOn($add_on);
        
        self::$em->flush();
        self::$em->clear();
        
        // Retrieve the sponsorship from the database
        $repository = self::$em->getRepository(SummitSponsorship::class);
        $found_sponsorship = $repository->find($sponsorship->getId());
        
        // Clear add-ons
        $found_sponsorship->clearAddOns();
        
        self::$em->flush();
        self::$em->clear();
        
        // Retrieve the sponsorship from the database again
        $updated_sponsorship = $repository->find($sponsorship->getId());
        
        // Test OneToMany relationship with add-ons
        $this->assertEmpty($updated_sponsorship->getAddOns()->toArray());
    }
}