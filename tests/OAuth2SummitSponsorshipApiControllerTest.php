<?php
/*
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

use Tests\InsertSummitTestData;
use Tests\ProtectedApiTestCase;

/**
 * Class OAuth2SummitSponsorshipApiControllerTest
 * @package Tests
 */
final class OAuth2SummitSponsorshipApiControllerTest
    extends ProtectedApiTestCase
{
    use InsertSummitTestData;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();
        self::insertSummitTestData();
    }

    protected function tearDown(): void
    {
        self::clearSummitTestData();
        parent::tearDown();
    }

    public function testGetAllBySummitAndSponsor(){

        $params = [
            'id' => self::$summit->getId(),
            'sponsor_id' => self::$sponsors[0]->getId(),
            'filter' => ['name==Default'],
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSponsorshipsApiController@getAll",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $page = json_decode($content);
        $this->assertNotNull($page);
        $this->assertNotEmpty($page->data);
    }

    public function testGetById(){
        $params = [
            'id' => self::$summit->getId(),
            'sponsor_id' => self::$sponsors[0]->getId(),
            'sponsorship_id' => self::$sponsors[0]->getSponsorships()[0]->getId(),
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSponsorshipsApiController@getById",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $summit_sponsorship = json_decode($content);
        $this->assertNotNull($summit_sponsorship);
    }

    public function testAddFromSponsorshipTypes(){

        $params = [
            'id' => self::$summit->getId(),
            'sponsor_id' => self::$sponsors[0]->getId(),
            'expand' => 'type'
        ];

        $data = [
            'type_ids' => [self::$default_summit_sponsor_type2->getId()],
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitSponsorshipsApiController@addFromTypes",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $page = json_decode($content);
        $this->assertNotNull($page);
    }

    public function testDelete(){

        $params = [
            'id' => self::$summit->getId(),
            'sponsor_id' => self::$sponsors[0]->getId(),
            'sponsorship_id' => self::$sponsors[0]->getSponsorships()[0]->getId(),
        ];

        $this->action(
            "DELETE",
            "OAuth2SummitSponsorshipsApiController@remove",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $this->assertResponseStatus(204);
    }

    //Add-Ons

    public function testGetAllAddOns(){
        $params = [
            'id' => self::$summit->getId(),
            'sponsor_id' => self::$sponsors[0]->getId(),
            'sponsorship_id' => self::$sponsors[0]->getSponsorships()[0]->getId(),
            'filter' => ['type==Booth'],
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSponsorshipsApiController@getAllAddOns",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $page = json_decode($content);
        $this->assertNotNull($page);
        $this->assertNotEmpty($page->data);
    }

    public function testGetAddOnById(){
        $add_on = self::$sponsors[0]->getSponsorships()[0]->getAddOns()[0];
        $params = [
            'id' => self::$summit->getId(),
            'sponsor_id' => self::$sponsors[0]->getId(),
            'sponsorship_id' => self::$sponsors[0]->getSponsorships()[0]->getId(),
            'add_on_id' => $add_on->getId(),
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSponsorshipsApiController@getAddOnById",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $retrieved_add_on = json_decode($content);
        $this->assertNotNull($retrieved_add_on);
        $this->assertEquals($add_on->getId(), $retrieved_add_on->id);
    }

    public function testAddNewAddOn(){
        $params = [
            'id' => self::$summit->getId(),
            'sponsor_id' => self::$sponsors[0]->getId(),
            'sponsorship_id' => self::$sponsors[0]->getSponsorships()[0]->getId(),
        ];

        $data = [
            'name' => 'Added AddOn',
            'type' => 'Booth',
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitSponsorshipsApiController@addNewAddOn",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $add_on = json_decode($content);
        $this->assertNotNull($add_on);
    }

    public function testUpdateAddOn(){
        $params = [
            'id' => self::$summit->getId(),
            'sponsor_id' => self::$sponsors[0]->getId(),
            'sponsorship_id' => self::$sponsors[0]->getSponsorships()[0]->getId(),
            'add_on_id' => self::$sponsors[0]->getSponsorships()[0]->getAddOns()[0]->getId()
        ];

        $new_name = 'Updated AddOn';

        $data = [
            'name' => $new_name,
            'type' => 'Booth',
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitSponsorshipsApiController@updateAddOn",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $add_on = json_decode($content);
        $this->assertEquals($new_name, $add_on->name);
    }

    public function testUpdateAddOnInvalidType(){
        $params = [
            'id' => self::$summit->getId(),
            'sponsor_id' => self::$sponsors[0]->getId(),
            'sponsorship_id' => self::$sponsors[0]->getSponsorships()[0]->getId(),
            'add_on_id' => self::$sponsors[0]->getSponsorships()[0]->getAddOns()[0]->getId()
        ];

        $new_name = 'Updated AddOn';

        $data = [
            'name' => $new_name,
            'type' => 'TestInvalidType',
        ];

        $this->action(
            "PUT",
            "OAuth2SummitSponsorshipsApiController@updateAddOn",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $this->assertResponseStatus(412);
    }

    public function testRemoveAddOn(){
        $params = [
            'id' => self::$summit->getId(),
            'sponsor_id' => self::$sponsors[0]->getId(),
            'sponsorship_id' => self::$sponsors[0]->getSponsorships()[0]->getId(),
            'add_on_id' => self::$sponsors[0]->getSponsorships()[0]->getAddOns()[0]->getId()
        ];

        $this->action(
            "DELETE",
            "OAuth2SummitSponsorshipsApiController@removeAddOn",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $this->assertResponseStatus(204);
    }

    public function testGetAddsMetadata(){
        $params = [
            'id' => self::$summit->getId(),
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSponsorshipsApiController@getMetadata",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $metadata = json_decode($content);
        $this->assertNotNull($metadata);
    }
}