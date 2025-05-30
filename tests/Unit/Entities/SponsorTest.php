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

use App\Models\Foundation\Main\IGroup;
use models\summit\Sponsor;
use models\summit\SponsorAd;
use models\summit\SponsorMaterial;
use models\summit\SponsorSocialNetwork;
use Tests\InsertMemberTestData;
use Tests\InsertSummitTestData;
use Tests\TestCase;

class SponsorTest extends TestCase
{
    use InsertSummitTestData;

    use InsertMemberTestData;

    /**
     * @throws \Exception
     */
    protected function setUp():void
    {
        parent::setUp();
        self::insertMemberTestData(IGroup::Sponsors);
        self::insertSummitTestData();

        self::$sponsors[0]->addUser(self::$member);

        self::$em->flush();
        self::$em->clear();
    }

    public function tearDown():void
    {
        self::clearSummitTestData();
        self::clearMemberTestData();
        parent::tearDown();
    }

    public function testAddSponsor(){
        // Get an existing sponsor from the test data
        $sponsor_id = self::$sponsors[0]->getId();
        $repository = self::$em->getRepository(Sponsor::class);
        $sponsor = $repository->find($sponsor_id);

        // Create a new material
        $material = new SponsorMaterial();
        $material->setName("Test Material " . str_random(5));
        $material->setLink("https://test-material-" . str_random(5) . ".com");
        $material->setType(SponsorMaterial::ValidTypes[array_rand(SponsorMaterial::ValidTypes)]);
        
        // Add the material (OneToMany relationship)
        $sponsor->addMaterial($material);

        // Create a new social network
        $social_network = new SponsorSocialNetwork();
        $social_network->setLink("https://test-social-" . str_random(5) . ".com");
        $social_network->setIsEnabled(true);
        $social_network->setIconCssClass("icon-test");
        
        // Add the social network (OneToMany relationship)
        $sponsor->addSocialNetwork($social_network);

        // Create a new ad
        $ad = new SponsorAd();
        $ad->setText("Test Ad Text " . str_random(5));
        $ad->setAlt("Test Ad Alt " . str_random(5));
        $ad->setLink("https://test-ad-" . str_random(5) . ".com");
        
        // Add the ad (OneToMany relationship)
        $sponsor->addAd($ad);

        self::$em->flush();
        self::$em->clear();

        // Retrieve the sponsor from the database
        $found_sponsor = $repository->find($sponsor->getId());

        // Test OneToMany relationship with materials
        $found_materials = $found_sponsor->getMaterials()->toArray();
        $found_material = null;
        foreach ($found_materials as $m) {
            if ($m->getName() === $material->getName()) {
                $found_material = $m;
                break;
            }
        }
        $this->assertNotNull($found_material);

        // Test OneToMany relationship with social networks
        $found_social_networks = $found_sponsor->getSocialNetworks()->toArray();
        $found_social_network = null;
        foreach ($found_social_networks as $sn) {
            if ($sn->getLink() === $social_network->getLink()) {
                $found_social_network = $sn;
                break;
            }
        }
        $this->assertNotNull($found_social_network);

        // Test OneToMany relationship with ads
        $found_ads = $found_sponsor->getAds()->toArray();
        $found_ad = null;
        foreach ($found_ads as $a) {
            if ($a->getText() === $ad->getText()) {
                $found_ad = $a;
                break;
            }
        }
        $this->assertNotNull($found_ad);
    }

    public function testDeleteSponsorChildren(){
        // Get an existing sponsor from the test data
        $sponsor_id = self::$sponsors[0]->getId();
        $repository = self::$em->getRepository(Sponsor::class);
        $sponsor = $repository->find($sponsor_id);

        // Get the current counts
        $previous_materials_count = count($sponsor->getMaterials()->toArray());
        $previous_social_networks_count = count($sponsor->getSocialNetworks()->toArray());
        $previous_ads_count = count($sponsor->getAds()->toArray());
        $previous_members_count = count($sponsor->getMembers()->toArray());

        // Remove a material if there are any
        if ($previous_materials_count > 0) {
            $materials = $sponsor->getMaterials()->toArray();
            $sponsor->removeMaterial($materials[0]);
        }

        // Remove a social network if there are any
        if ($previous_social_networks_count > 0) {
            $social_networks = $sponsor->getSocialNetworks()->toArray();
            $sponsor->removeSocialNetwork($social_networks[0]);
        }

        // Remove an ad if there are any
        if ($previous_ads_count > 0) {
            $ads = $sponsor->getAds()->toArray();
            $sponsor->removeAd($ads[0]);
        }

        // Remove a member if there are any
        if ($previous_members_count > 0) {
            $members = $sponsor->getMembers()->toArray();
            $sponsor->removeUser($members[0]);
        }

        // Clear side image
        if ($sponsor->hasSideImage()) {
            $sponsor->clearSideImage();
        }

        // Clear featured event
        if ($sponsor->hasFeaturedEvent()) {
            $sponsor->clearFeaturedEvent();
        }

        self::$em->flush();
        self::$em->clear();

        // Retrieve the sponsor from the database
        $found_sponsor = $repository->find($sponsor->getId());

        // Test ManyToOne relationship with side image
        $this->assertFalse($found_sponsor->hasSideImage());

        // Test ManyToOne relationship with featured event
        $this->assertFalse($found_sponsor->hasFeaturedEvent());

        // Test OneToMany relationship with materials
        if ($previous_materials_count > 0) {
            $this->assertCount($previous_materials_count - 1, $found_sponsor->getMaterials()->toArray());
        }

        // Test OneToMany relationship with social networks
        if ($previous_social_networks_count > 0) {
            $this->assertCount($previous_social_networks_count - 1, $found_sponsor->getSocialNetworks()->toArray());
        }

        // Test OneToMany relationship with ads
        if ($previous_ads_count > 0) {
            $this->assertCount($previous_ads_count - 1, $found_sponsor->getAds()->toArray());
        }

        // Test ManyToMany relationship with members
        if ($previous_members_count > 0) {
            $this->assertCount($previous_members_count - 1, $found_sponsor->getMembers()->toArray());
        }

    }
}