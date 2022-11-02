<?php namespace Tests;
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
class OAuth2SummitAdministratorPermissionGroupApiControllerTest
    extends ProtectedApiTest
{
    use InsertSummitTestData;

    protected function setUp():void
    {
        parent::setUp();
        self::insertSummitTestData();
    }

    public function tearDown():void
    {
        self::clearSummitTestData();
        Mockery::close();
    }

    public function testAddGroupOK(){
        $params = [
        ];

        $data = [
            'title' => 'TEST GROUP '.str_random(16),
            'members' => [self::$member2->getId()],
            'summits' => [self::$summit->getId()],
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitAdministratorPermissionGroupApiController@add",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $group = json_decode($content);
        $this->assertTrue(!is_null($group));
        $this->assertTrue(count($group->members) == 1);
        $this->assertTrue(count($group->summits) == 1);

        return $group;
    }

    public function testUpdateGroupOk(){

        $group = $this->testAddGroupOK();

        $params = [
            'group_id' => $group->id
        ];

        $data = [
            'members' => [self::$member2->getId()],
            'summits' => [],
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitAdministratorPermissionGroupApiController@update",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $group = json_decode($content);
        $this->assertTrue(!is_null($group));
        $this->assertTrue(count($group->members) == 1);
        $this->assertTrue(count($group->summits) == 0);
    }

    public function testGetAllOK(){
        $group = $this->testAddGroupOK();

        $params = [
            'expand' => 'summits,members'
        ];

        $data = [
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitAdministratorPermissionGroupApiController@getAll",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $groups = json_decode($content);
        $this->assertTrue(!is_null($groups));
    }

    public function testGetByIdOK(){
        $group = $this->testAddGroupOK();

        $params = [
            'id' => $group->id,
            //'expand' => 'summits,members',
        ];

        $data = [
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitAdministratorPermissionGroupApiController@get",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $group = json_decode($content);
        $this->assertTrue(!is_null($group));
    }
}