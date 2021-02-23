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
use Illuminate\Http\UploadedFile;

/**
 * Class OAuth2SummitRegistrationInvitationApiControllerTest
 */
class OAuth2SummitRegistrationInvitationApiControllerTest extends ProtectedApiTest
{

    use InsertSummitTestData;

    public function setUp():void
    {
        parent::setUp();

        self::insertTestData();
        self::$summit->seedDefaultEmailFlowEvents();
    }

    protected function tearDown():void
    {
        self::clearTestData();
        parent::tearDown();
    }

    public function testIngestInvitationsAndGet(){
        $csv_content = <<<CSV
email,first_name,last_name
cespin+3@gmail.com,Jason,Cirrus
cespin+4@gmail.com,Allen,Altostratus
CSV;
        $path = "/tmp/invitations.csv";

        file_put_contents($path, $csv_content);

        $file = new UploadedFile($path, "invitations.csv", 'text/csv', null, true);

        $params = [
            'summit_id' => self::$summit->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitRegistrationInvitationApiController@ingestInvitations",
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


        $params = [
            'summit_id' => self::$summit->getId(),
            'filter'=> 'is_accepted==true'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitRegistrationInvitationApiController@getAllBySummit",
            $params,
            [],
            [],
            [
            ],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
    }

    public function testIngestInvitationsAndGetCSV(){
        $csv_content = <<<CSV
email,first_name,last_name
smarcet@gmail.com,Sebastian,Marcet
smarcet+pepe@gmail.com,Pepe,Marcet
CSV;
        $path = "/tmp/invitations.csv";

        file_put_contents($path, $csv_content);

        $file = new UploadedFile($path, "invitations.csv", 'text/csv', null, true);

        $params = [
            'summit_id' => self::$summit->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitRegistrationInvitationApiController@ingestInvitations",
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


        $params = [
            'summit_id' => self::$summit->getId(),
            'filter'=> 'is_accepted==false'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitRegistrationInvitationApiController@getAllBySummitCSV",
            $params,
            [],
            [],
            [
            ],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $this->assertTrue(!empty($content));
    }


    public function testIngestInvitationsAndResend(){
        $csv_content = <<<CSV
email,first_name,last_name
smarcet@gmail.com,Sebastian,Marcet
smarcet+pepe@gmail.com,Pepe,Marcet
CSV;
        $path = "/tmp/invitations.csv";

        file_put_contents($path, $csv_content);

        $file = new UploadedFile($path, "invitations.csv", 'text/csv', null, true);

        $params = [
            'summit_id' => self::$summit->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitRegistrationInvitationApiController@ingestInvitations",
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


        $response = $this->action(
            "PUT",
            "OAuth2SummitRegistrationInvitationApiController@resendNonAccepted",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
    }



}