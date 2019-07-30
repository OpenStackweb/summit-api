<?php
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

final class OAuth2SummitRefundPolicyTypeApiTest extends ProtectedApiTest
{
    /**
     * @param int $summit_id
     * @param int $until_x_days_before_event_starts
     */
    public function testAddPolicy($summit_id = 27, $until_x_days_before_event_starts = 20)
    {
        $params = [
            'id' => $summit_id,
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

    public function testGetAllPoliciesBySummit($summit_id=27){
        $params = [
            'id' => $summit_id,
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

    public function testDeletePolicy($summit_id=27){
        $policy = $this->testAddPolicy($summit_id, 2);

        $params = [
            'id' => $summit_id,
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
}