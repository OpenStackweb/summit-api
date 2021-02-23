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

class OAuth2SummitMetricsApiControllerTest extends ProtectedApiTest
{
    /**
     * @param int $summit_id
     * @return mixed
     */
    public function testEnter($summit_id = 18){

        $params = [
            'id' => $summit_id,
        ];

        $data = [
            'type' => \models\summit\ISummitMetricType::General,
        ];

        $headers = [
            "HTTP_Authorization"  => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json",
            'REMOTE_ADDR'         => '10.1.0.1',
            'HTTP_REFERER'        => 'https://www.test.com'
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitMetricsApiController@enter",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $metric = json_decode($content);
        $this->assertTrue(!is_null($metric));
        return $metric;
    }

    /**
     * @param int $summit_id
     * @return mixed
     */
    public function testEnterEvent($summit_id = 18, $event_id = 706){

        $params = [
            'id' => $summit_id,
            'event_id' => $event_id,
            'member_id' => 'me'
        ];

        $headers = [
            "HTTP_Authorization"  => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json",
            'REMOTE_ADDR'         => '10.1.0.1',
            'HTTP_REFERER'        => 'https://www.test.com'
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitMetricsApiController@enterToEvent",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $metric = json_decode($content);
        $this->assertTrue(!is_null($metric));
        return $metric;
    }
}