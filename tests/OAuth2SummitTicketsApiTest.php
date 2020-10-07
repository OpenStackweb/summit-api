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
use Illuminate\Http\UploadedFile;

/**
 * Class OAuth2SummitTicketsApiTest
 */
final class OAuth2SummitTicketsApiTest extends ProtectedApiTest
{

    public function setUp()
    {
        parent::setUp();
    }
  /**
      * @return mixed
     */
    public function testGetAllTickets($summit_id = 8){
        $params = [
            'summit_id' => $summit_id,
            'page'     => 1,
            'per_page' => 10,
            'order'    => '+id',
            'expand'   => 'owner,order,ticket_type,badge,promo_code'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitTicketApiController@getAllBySummit",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $tickets = json_decode($content);
        $this->assertTrue(!is_null($tickets));
        return $tickets;
    }

    /**
     * @param int $summit_id
     * @return mixed
     */
    public function testGetAllTicketsCSV($summit_id = 4){

        $params = [
            'summit_id' => $summit_id,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitTicketApiController@getAllBySummitCSV",
            $params,
            [],
            [],
            [],
            $headers
        );

        $csv = $response->getContent();
        $this->assertResponseStatus(200);
        $this->assertTrue(!empty($csv));
        return $csv;
    }

    public function testGetTicketImportTemplate($summit_id = 3){

        $params = [
            'summit_id' => $summit_id,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitTicketApiController@getImportTicketDataTemplate",
            $params,
            [],
            [],
            [],
            $headers
        );

        $csv = $response->getContent();
        $this->assertResponseStatus(200);
        $this->assertTrue(!empty($csv));
        return $csv;
    }

    public function testIngestTicketData($summit_id = 21){
        $csv_content = <<<CSV
attendee_email,attendee_first_name,attendee_last_name,ticket_type_name,badge_type_name,Bloomreach Connect Summit - Day 1,User Group - Day 2,Tech Track - Day 3,Partner Summit - Day 4
smarcet+json12@gmail.com,Jason12,Marcet,General Admission,General Admission,1,1,1,1
CSV;
        $path = "/tmp/tickets.csv";

        file_put_contents($path, $csv_content);

        $file = new UploadedFile($path, "tickets.csv", 'text/csv', null, true);

        $params = [
            'summit_id' => $summit_id,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitTicketApiController@importTicketData",
            $params,
            [],
            [],
            [
                'file' => $file
            ],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
    }

    public function testIngestTicketData2($summit_id = 1){
        $csv_content = <<<CSV
id,number,attendee_email,attendee_first_name,attendee_last_name,attendee_company,ticket_type_name,ticket_type_id,badge_type_id,badge_type_name,Commander,VIP Access
,REGISTRATIONDEVSUMMIT2019_TICKET_5D7BD99A36008622282877,xmarcet+4@gmail.com,,,,,,,,1,1
CSV;
        $path = "/tmp/tickets.csv";

        file_put_contents($path, $csv_content);

        $file = new UploadedFile($path, "tickets.csv", 'text/csv', null, true);

        $params = [
            'summit_id' => $summit_id,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitTicketApiController@importTicketData",
            $params,
            [],
            [],
            [
                'file' => $file
            ],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
    }

    /**
     * @param int $summit_id
     * @param string $number
     * @return mixed
     */
    public function testGetTicketByNumber($summit_id = 27, $number = 'SHANGHAI2019_TICKET_5D658DE87978A699249976'){
        $params = [
            'summit_id' => $summit_id,
            'ticket_id' => $number,
            'expand'   => 'order'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitTicketApiController@get",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $ticket = json_decode($content);
        $this->assertTrue(!is_null($ticket));
        return $ticket;
    }

    // badges endpoints

    /**
     * @param int $summit_id
     * @param string $number
     * @return mixed
     */
    public function testCreateAttendeeBadge($summit_id = 27, $number = 'SHANGHAI2019_TICKET_5D658F4EF058F555164878'){
        $params = [
            'id' => $summit_id,
            'ticket_id' => $number
        ];
        $data = [
            'badge_type_id' => 2,
            'features' => [1, 2]
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitTicketApiController@createAttendeeBadge",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $badge = json_decode($content);
        $this->assertTrue(!is_null($badge));
        return $badge;
    }

    public function testRemoveAttendeeBadgeFeature($summit_id = 27, $number = 'SHANGHAI2019_TICKET_5D658F4EF058F555164878', $feature_id = 1){
        $params = [
            'id' => $summit_id,
            'ticket_id' => $number,
            'feature_id' => $feature_id,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitTicketApiController@removeAttendeeBadgeFeature",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $badge = json_decode($content);
        $this->assertTrue(!is_null($badge));
        return $badge;
    }

    public function testAddAttendeeBadgeFeature($summit_id = 27, $number = 'SHANGHAI2019_TICKET_5D658F4EF058F555164878', $feature_id = 1){
        $params = [
            'id' => $summit_id,
            'ticket_id' => $number,
            'feature_id' => $feature_id,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitTicketApiController@addAttendeeBadgeFeature",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $badge = json_decode($content);
        $this->assertTrue(!is_null($badge));
        return $badge;
    }

    /**
     * @param int $summit_id
     * @param string $number
     * @param int $type
     * @return mixed
     */
    function testUpdateAttendeeBadgeType($summit_id = 27, $number = 'SHANGHAI2019_TICKET_5D658F4EF058F555164878', $type = 1){
        $params = [
            'id' => $summit_id,
            'ticket_id' => $number,
            'type_id' => $type,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitTicketApiController@updateAttendeeBadgeType",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $badge = json_decode($content);
        $this->assertTrue(!is_null($badge));
        return $badge;
    }
    /**
     * @param int $summit_id
     * @param string $number
     * @return mixed
     */
    public function testDeleteAttendeeBadge($summit_id = 27, $number = 'SHANGHAI2019_TICKET_5D658F4EF058F555164878'){
        $params = [
            'id' => $summit_id,
            'ticket_id' => $number
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitTicketApiController@deleteAttendeeBadge",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(204);
    }


    /**
     * @param int $summit_id
     * @param string $number
     * @return mixed
     */
    function testPrintAttendeeBadge($summit_id = 1, $number = 'REGISTRATIONDEVSUMMIT2019_TICKET_5D7BE0E518E8C161661586'){
        $params = [
            'id' => $summit_id,
            'ticket_id' => $number,
            'expand' => 'features',
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitTicketApiController@printAttendeeBadge",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $badge = json_decode($content);
        $this->assertTrue(!is_null($badge));
        return $badge;
    }
}