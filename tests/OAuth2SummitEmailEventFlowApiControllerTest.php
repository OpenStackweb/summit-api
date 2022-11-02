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

/**
 * Class OAuth2SummitEmailEventFlowApiControllerTest
 */
class OAuth2SummitEmailEventFlowApiControllerTest extends ProtectedApiTest
{
    use InsertSummitTestData;

    protected function setUp():void
    {
        parent::setUp();
        self::insertSummitTestData();
        self::$summit->seedDefaultEmailFlowEvents();
    }

    protected function tearDown():void
    {
        self::clearSummitTestData();
        parent::tearDown();
    }

    /**
     * @param int $summit_id
     */
    public function testGetAllEmailEvents(){
        $params = [
            'id'       => self::$summit->getId(),
            'page'     => 1,
            'per_page' => 10,
            'order'    => '-id',
            'filter'   => 'flow_name==Schedule'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitEmailEventFlowApiController@getAllBySummit",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $email_events = json_decode($content);
        $this->assertTrue(!is_null($email_events));
        $this->assertTrue($email_events->total >= 1);
        return $email_events;
    }

    public function testUpdateEmailEvent(){
        $email_events = $this->testGetAllEmailEvents();

        $params = [
            'id'       => self::$summit->getId(),
            'event_id' => $email_events->data[0]->id
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $data = [
            'email_template_identifier' => "NEW_TEMPLATE",
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitEmailEventFlowApiController@update",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $email_event = json_decode($content);

    }
}