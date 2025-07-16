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

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSponsorshipsApiController@getAll",
            $params,
            [],
            [],
            [],
            $headers
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

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"       => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSponsorshipsApiController@getById",
            $params,
            [],
            [],
            [],
            $headers
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

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"       => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitSponsorshipsApiController@addFromTypes",
            $params,
            [],
            [],
            [],
            $headers,
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

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $this->action(
            "DELETE",
            "OAuth2SummitSponsorshipsApiController@remove",
            $params,
            [],
            [],
            [],
            $headers
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