<?php namespace Tests;
/*
 * Copyright 2022 OpenStack Foundation
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

use Illuminate\Support\Facades\App;
use models\summit\ISummitBadgeViewTypeRepository;

/**
 * Class OAuth2SummitBadgeViewTypeApiTest
 * @package Tests
 */
final class OAuth2SummitBadgeViewTypeApiTest extends ProtectedApiTestCase
{
    use InsertSummitTestData;

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

    public function testAdd()
    {
        $params = [
            'id' => self::$summit->getId(),
        ];

        $name = str_random(16) . '_badge_view_type';

        $data = [
            'name' => $name,
            'description' => "this is a description",
            'is_default' => true
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitBadgeViewTypeApiController@add",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $badge_view_type = json_decode($content);
        $this->assertTrue(!is_null($badge_view_type));
        $this->assertTrue($badge_view_type->name == $name);
        $this->assertTrue($badge_view_type->summit_id === self::$summit->getId());
        return $badge_view_type;
    }

    public function testAddTwiceDefaultFail()
    {
        $params = [
            'id' => self::$summit->getId(),
        ];

        $name = str_random(16) . '_badge_view_type';

        $data = [
            'name' => $name,
            'description' => "this is a description",
            'is_default' => true
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitBadgeViewTypeApiController@add",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $badge_view_type = json_decode($content);
        $this->assertTrue(!is_null($badge_view_type));
        $this->assertTrue($badge_view_type->name == $name);
        $this->assertTrue($badge_view_type->summit_id === self::$summit->getId());;

        $name = str_random(16) . '_badge_view_type_fail';

        $data = [
            'name' => $name,
            'description' => "this is a description",
            'is_default' => true
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitBadgeViewTypeApiController@add",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $this->assertResponseStatus(412);
        $content = $response->getContent();
        $error_response = json_decode($content);
        $this->assertTrue(count($error_response->errors) > 0);
        $this->assertTrue($error_response->errors[0] === 'There is a former default view.');
    }

    public function testAddDelete()
    {

        $repository = App::make(ISummitBadgeViewTypeRepository::class);

        $params = [
            'id' => self::$summit->getId(),
        ];

        $name = str_random(16) . '_badge_view_type';

        $data = [
            'name' => $name,
            'description' => "this is a description",
            'is_default' => true
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitBadgeViewTypeApiController@add",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $badge_view_type = json_decode($content);
        $this->assertTrue(!is_null($badge_view_type));
        $this->assertTrue($badge_view_type->name == $name);
        $this->assertTrue($badge_view_type->summit_id === self::$summit->getId());
        // check DB

        $entity = $repository->getById($badge_view_type->id);

        $this->assertTrue(!is_null($entity));

        $params['badge_view_type_id'] = $badge_view_type->id;

        $this->action("DELETE",
            "OAuth2SummitBadgeViewTypeApiController@delete",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $this->assertResponseStatus(204);

        // check DB

        $entity = $repository->getById($badge_view_type->id);

        $this->assertTrue(is_null($entity));
    }

    public function testAddUpdate()
    {
        $params = [
            'id' => self::$summit->getId(),
        ];

        $name = str_random(16) . '_badge_view_type';

        $data = [
            'name' => $name,
            'description' => "this is a description",
            'is_default' => true
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitBadgeViewTypeApiController@add",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $badge_view_type = json_decode($content);
        $this->assertTrue(!is_null($badge_view_type));
        $this->assertTrue($badge_view_type->name == $name);
        $this->assertTrue($badge_view_type->summit_id === self::$summit->getId());

        $data['is_default'] = false;
        $data['description'] = 'updated description';
        $params['badge_view_type_id'] = $badge_view_type->id;

        $response = $this->action(
            "PUT",
            "OAuth2SummitBadgeViewTypeApiController@update",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);

        $badge_view_type = json_decode($content);
        $this->assertTrue(!is_null($badge_view_type));
        $this->assertTrue($badge_view_type->is_default == false);
        $this->assertTrue($badge_view_type->description === 'updated description');
    }

    public function testAddGetAll(){
        $params = [
            'id' => self::$summit->getId(),
        ];

        $name = str_random(16) . '_badge_view_type';

        $data = [
            'name' => $name,
            'description' => "this is a description",
            'is_default' => true
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitBadgeViewTypeApiController@add",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $badge_view_type = json_decode($content);
        $this->assertTrue(!is_null($badge_view_type));
        $this->assertTrue($badge_view_type->name == $name);
        $this->assertTrue($badge_view_type->summit_id === self::$summit->getId());

        $name = str_random(16) . '_badge_view_type_2';

        $data = [
            'name' => $name,
            'description' => "this is a description",
            'is_default' => false
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitBadgeViewTypeApiController@add",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $badge_view_type = json_decode($content);
        $this->assertTrue(!is_null($badge_view_type));
        $this->assertTrue($badge_view_type->name == $name);
        $this->assertTrue($badge_view_type->summit_id === self::$summit->getId());

        $response = $this->action(
            "GET",
            "OAuth2SummitBadgeViewTypeApiController@getAllBySummit",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $this->assertResponseStatus(200);
        $content = $response->getContent();
        $page_response = json_decode($content);

        $this->assertTrue($page_response->total === 2);
    }
}