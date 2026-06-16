<?php
/*
 * Copyright 2026 OpenStack Foundation
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

use LaravelDoctrine\ORM\Facades\Registry;
use models\summit\SummitSponsorshipAddOnType;
use models\utils\SilverstripeBaseModel;
use Tests\InsertSummitTestData;
use Tests\ProtectedApiTestCase;

/**
 * Class OAuth2SummitSponsorshipAddOnTypesApiControllerTest
 */
final class OAuth2SummitSponsorshipAddOnTypesApiControllerTest extends ProtectedApiTestCase
{
    use InsertSummitTestData;

    private static ?SummitSponsorshipAddOnType $default_type = null;

    protected function setUp(): void
    {
        parent::setUp();
        self::insertSummitTestData();

        self::$default_type = new SummitSponsorshipAddOnType();
        self::$default_type->setName('Test_Type_' . str_random(6));
        self::$em->persist(self::$default_type);
        self::$em->flush();
    }

    protected function tearDown(): void
    {
        if (!is_null(self::$default_type)) {
            $em = self::$em->isOpen()
                ? self::$em
                : Registry::resetManager(SilverstripeBaseModel::EntityManager);

            $type = $em->find(SummitSponsorshipAddOnType::class, self::$default_type->getId());
            if (!is_null($type)) {
                $em->remove($type);
                $em->flush();
            }
            self::$default_type = null;
        }
        self::clearSummitTestData();
        parent::tearDown();
    }

    public function testGetAll(): void
    {
        $response = $this->action(
            "GET",
            "OAuth2SummitSponsorshipAddOnTypesApiController@getAll",
            [],
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

    public function testGetAllFilterByName(): void
    {
        $params = [
            'filter' => ['name==' . self::$default_type->getName()],
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSponsorshipAddOnTypesApiController@getAll",
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
        $this->assertCount(1, $page->data);
        $this->assertEquals(self::$default_type->getName(), $page->data[0]->name);
    }

    public function testGet(): void
    {
        $params = ['id' => self::$default_type->getId()];

        $response = $this->action(
            "GET",
            "OAuth2SummitSponsorshipAddOnTypesApiController@get",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $type = json_decode($content);
        $this->assertNotNull($type);
        $this->assertEquals(self::$default_type->getId(), $type->id);
        $this->assertEquals(self::$default_type->getName(), $type->name);
    }

    public function testGetNotFound(): void
    {
        $params = ['id' => PHP_INT_MAX];

        $this->action(
            "GET",
            "OAuth2SummitSponsorshipAddOnTypesApiController@get",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $this->assertResponseStatus(404);
    }

    public function testAdd(): void
    {
        $data = [
            'name' => 'New_Type_' . str_random(6),
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitSponsorshipAddOnTypesApiController@add",
            [],
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $type = json_decode($content);
        $this->assertNotNull($type);
        $this->assertEquals($data['name'], $type->name);
    }

    public function testAddDuplicateName(): void
    {
        $data = [
            'name' => self::$default_type->getName(),
        ];

        $this->action(
            "POST",
            "OAuth2SummitSponsorshipAddOnTypesApiController@add",
            [],
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $this->assertResponseStatus(412);
    }

    public function testAddMissingName(): void
    {
        $data = [];

        $this->action(
            "POST",
            "OAuth2SummitSponsorshipAddOnTypesApiController@add",
            [],
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $this->assertResponseStatus(412);
    }

    public function testUpdate(): void
    {
        $params   = ['id' => self::$default_type->getId()];
        $new_name = 'Updated_Type_' . str_random(4);

        $data = [
            'name' => $new_name,
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitSponsorshipAddOnTypesApiController@update",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $type = json_decode($content);
        $this->assertNotNull($type);
        $this->assertEquals($new_name, $type->name);
    }

    public function testUpdateNotFound(): void
    {
        $params = ['id' => PHP_INT_MAX];

        $this->action(
            "PUT",
            "OAuth2SummitSponsorshipAddOnTypesApiController@update",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode(['name' => 'irrelevant'])
        );

        $this->assertResponseStatus(404);
    }

    public function testDelete(): void
    {
        $params = ['id' => self::$default_type->getId()];

        $this->action(
            "DELETE",
            "OAuth2SummitSponsorshipAddOnTypesApiController@delete",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $this->assertResponseStatus(204);
        self::$default_type = null;
    }

    public function testDeleteNotFound(): void
    {
        $params = ['id' => PHP_INT_MAX];

        $this->action(
            "DELETE",
            "OAuth2SummitSponsorshipAddOnTypesApiController@delete",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $this->assertResponseStatus(404);
    }

    public function testDeleteInUse(): void
    {
        $params = ['id' => self::$default_add_on_type_booth->getId()];

        $this->action(
            "DELETE",
            "OAuth2SummitSponsorshipAddOnTypesApiController@delete",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $this->assertResponseStatus(412);
    }
}
