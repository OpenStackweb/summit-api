<?php
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
 * Class OAuth2PersonalCalendarShareInfoApiTest
 */
class OAuth2PersonalCalendarShareInfoApiTest extends ProtectedApiTest
{
    public function testCreateShareableLink($summit_id = 27){

        $params = array
        (
            'id' => $summit_id,
            'member_id' => 'me',
        );

        $headers = array
        (
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"       => "application/json",
        );

        $response = $this->action
        (
            "POST",
            "OAuth2SummitMembersApiController@createScheduleShareableLink",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();

        $this->assertResponseStatus(201);

        $link = json_decode($content);

        return $link;
    }

    public function testGetICS($summit_id = 27){

        $link = $this->testCreateShareableLink($summit_id);
        $params = array
        (
            'id' => $summit_id,
            'cid' => $link->cid,
        );

        $headers = array
        (
            "CONTENT_TYPE"       => "application/json",
        );

        $response = $this->action
        (
            "GET",
            "OAuth2SummitMembersApiController@getCalendarFeedICS",
            $params,
            [],
            [],
            [],
            $headers
        );

        $ics = $response->getContent();

        $this->assertResponseStatus(200);

        return $ics;
    }
}