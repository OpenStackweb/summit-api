<?php namespace Tests;
/**
 * Copyright 2019 OpenStack Foundation
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

final class OAuth2SummitRefundPolicyTypeApiTest extends ProtectedApiTestCase
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

    public function testAddPolicy($until_x_days_before_event_starts = 20)
    {
        $params = [
            'id' => self::$summit->getId(),
        ];

        $name = str_random(16) . '_policy';
        $data = [
            'name'                             => $name,
            'until_x_days_before_event_starts' => $until_x_days_before_event_starts,
            'refund_rate'                      => 90.50
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitRefundPolicyTypeApiController@add",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $policy = json_decode($content);
        $this->assertTrue(!is_null($policy));
        $this->assertTrue($policy->name == $name);
        return $policy;
    }

    public function testGetAllPoliciesBySummit(){
        $params = [
            'id' => self::$summit->getId(),
            'filter' => 'until_x_days_before_event_starts<=15'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitRefundPolicyTypeApiController@getAllBySummit",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $data = json_decode($content);
        $this->assertTrue(!is_null($data));
    }

    public function testGetPolicyById(){
        $policy = $this->testAddPolicy();

        $params = [
            'id' => self::$summit->getId(),
            'policy_id' => $policy->id,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitRefundPolicyTypeApiController@get",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $fetched = json_decode($content);
        $this->assertTrue(!is_null($fetched));
        $this->assertTrue($fetched->id == $policy->id);
    }

    public function testGetPolicyById404(){
        $params = [
            'id' => self::$summit->getId(),
            'policy_id' => 0,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitRefundPolicyTypeApiController@get",
            $params,
            [],
            [],
            [],
            $headers
        );

        $this->assertResponseStatus(404);
    }

    public function testUpdatePolicy(){
        $policy = $this->testAddPolicy();

        $params = [
            'id' => self::$summit->getId(),
            'policy_id' => $policy->id,
        ];

        $data = [
            'name'                             => 'updated_policy_name',
            'until_x_days_before_event_starts' => 10,
            'refund_rate'                      => 50.00,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitRefundPolicyTypeApiController@update",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $updated = json_decode($content);
        $this->assertTrue(!is_null($updated));
        $this->assertTrue($updated->name == 'updated_policy_name');
        $this->assertTrue($updated->refund_rate == 50.00);
    }

    public function testDeletePolicy(){
        $policy = $this->testAddPolicy(2);

        $params = [
            'id' => self::$summit->getId(),
            'policy_id' => $policy->id
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitRefundPolicyTypeApiController@delete",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(204);
    }

    public function testDeletePolicy404(){
        $params = [
            'id' => self::$summit->getId(),
            'policy_id' => 0,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitRefundPolicyTypeApiController@delete",
            $params,
            [],
            [],
            [],
            $headers
        );

        $this->assertResponseStatus(404);
    }

    public function testDeleteAndVerifyRemoved(){
        $policy = $this->testAddPolicy(5);

        $params = [
            'id' => self::$summit->getId(),
            'policy_id' => $policy->id,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitRefundPolicyTypeApiController@delete",
            $params,
            [],
            [],
            [],
            $headers
        );

        $this->assertResponseStatus(204);

        // verify it's gone
        $response = $this->action(
            "GET",
            "OAuth2SummitRefundPolicyTypeApiController@get",
            $params,
            [],
            [],
            [],
            $headers
        );

        $this->assertResponseStatus(404);
    }
}