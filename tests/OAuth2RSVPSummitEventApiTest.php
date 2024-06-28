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
 * Class OAuth2RSVPSummitEventApiTest
 */
final class OAuth2RSVPSummitEventApiTest extends ProtectedApiTestCase
{

    /**
     * @param int $summit_id
     * @param int $event_id
     * @return false|string
     */
    public function testAddRSVP($summit_id = 27, $event_id = 24344){

        $params = array
        (
            'id' => $summit_id,
            'member_id' => 'me',
            'event_id' => $event_id,
        );

        $headers = array
        (
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"       => "application/json",
            "HTTP_Referer"       => "https://www.openstack.org/summit/shanghai-2019/summit-schedule/events/".$event_id
        );

        $payload = [
            'answers' => [
                [
                    'question_id' => 209,
                    'value' => 'smarcet@gmail.com',
                ],
                [
                    'question_id' => 210,
                    'value' => 'Sebastian',
                ],
                [
                    'question_id' => 211,
                    'value' => 'Marcet',
                ],
                [
                    'question_id' => 212,
                    'value' => 'Dev',
                ],
                [
                    'question_id' => 213,
                    'value' => 'Tipit',
                ],
                [
                    'question_id' => 214,
                    'value' => '+5491133943659',
                ],
                [
                    'question_id' => 215,
                    'value' => [
                        '150', '151'
                    ],
                ],
                [
                    'question_id' => 216,
                    'value' => '155',
                ],
                [
                    'question_id' => 218,
                    'value' => '161',
                ],
                [
                    'question_id' => 219,
                    'value' => 'N/A',
                ],
            ]
        ];

        $response = $this->action
        (
            "POST",
            "OAuth2SummitMembersApiController@addEventRSVP",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($payload)
        );
        $rsvp = $response->getContent();

        $this->assertResponseStatus(201);

        return $rsvp;
    }

    public function testCurrentSummitMyMemberScheduleUnRSVP($summit_id = 27, $event_id = 24344)
    {
        $params = array
        (
            'id'          => $summit_id,
            'member_id' => 'me',
            'event_id'    => $event_id
        );

        $headers = array("HTTP_Authorization" => " Bearer " . $this->access_token);
        $response = $this->action(
            "DELETE",
            "OAuth2SummitMembersApiController@deleteEventRSVP",
            $params,
            array(),
            array(),
            array(),
            $headers
        );
        $content = $response->getContent();
        $this->assertResponseStatus(204);
    }

}