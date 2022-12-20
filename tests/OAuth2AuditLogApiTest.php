<?php namespace Tests;
/**
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

class OAuth2AuditLogApiTest extends ProtectedApiTest
{
    public function testGetSummitAuditLog()
    {
        $params = [
            'filter' => ['class_name==SummitEventAuditLog', 'summit_id==3343'],
            'order'  => '+event_id',
            'expand' => 'user'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2AuditLogController@getAll",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $audit_log = json_decode($content);
        $this->assertTrue(!is_null($audit_log));
        $this->assertResponseStatus(200);
    }

    public function testGetSummitEventAuditLog()
    {
        $params = [
            'filter' => ['class_name==SummitEventAuditLog', 'summit_id==3343', 'event_id==107223'],
            'order'  => '-created',
            'expand' => 'user'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2AuditLogController@getAll",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $audit_log = json_decode($content);
        $this->assertTrue(!is_null($audit_log));
        $this->assertResponseStatus(200);
    }
}