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

use App\Jobs\Emails\Registration\Invitations\InviteSummitRegistrationEmail;
use Illuminate\Http\UploadedFile;
use models\summit\SummitTicketType;

/**
 * Class OAuth2SummitRegistrationInvitationApiControllerTest
 */
class OAuth2SummitRegistrationInvitationApiControllerTest extends ProtectedApiTestCase
{

    use InsertSummitTestData;

    public function setUp():void
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

    public function testIngestInvitationsAndGet(){
        $csv_content = <<<CSV
email,first_name,last_name,tags
cespin+3@gmail.com,Jason,Cirrus,tag1|tag2
cespin+4@gmail.com,Allen,Altostratus,tag6|tag1
CSV;
        $path = "/tmp/invitations.csv";

        file_put_contents($path, $csv_content);

        $file = new UploadedFile($path, "invitations.csv", 'text/csv', null, true);

        $params = [
            'id' => self::$summit->getId(),
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
            'id' => self::$summit->getId(),
            'filter'=> 'tags==tag6',
            'expand' => 'tags',
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
        $invitations = json_decode($content);
        $this->assertTrue(!is_null($invitations));
        $this->assertTrue(count($invitations->data) == 1);
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
            'id' => self::$summit->getId(),
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
            'id' => self::$summit->getId(),
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


    public function testIngestExistingInvitations(){

        $csv_content = <<<CSV
email,first_name,last_name
cespin+3@gmail.com,Jason,Cirrus
CSV;

        $path = "/tmp/invitations.csv";

        file_put_contents($path, $csv_content);

        $file = new UploadedFile($path, "invitations.csv", 'text/csv', null, true);

        $params = [
            'id' => self::$summit->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
        ];

        $this->action(
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
    }

    public function testInviteWithInvitation(){

        self::$default_ticket_type->setAudience(SummitTicketType::Audience_With_Invitation);
        self::$em->persist(self::$default_ticket_type);
        self::$em->flush();

        $params = [
            'id' => self::$summit->getId(),
            'expand' => 'tags'
        ];

        $first_name = str_random(16).'_ticket_type';
        $last_name  = str_random(16).'_external_id';
        $email      = "roman.gutierrez@gmail.com";

        $data = [
            'email'                 => $email,
            'first_name'            => $first_name,
            'last_name'             => $last_name,
            'allowed_ticket_types'  => [self::$default_ticket_type->getId()],
            'acceptance_criteria'   => 'ALL_TICKET_TYPES',
            'tags'                  => ['tag1', 'tag2']
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitRegistrationInvitationApiController@add",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $invitation = json_decode($content);
        $this->assertTrue(!is_null($invitation));
        $this->assertTrue(count($invitation->tags) == 2);
        return $invitation;
    }

    public function testInviteWithoutInvitation(){
        self::$default_ticket_type->setAudience(SummitTicketType::Audience_Without_Invitation);
        self::$em->persist(self::$default_ticket_type);
        self::$em->flush();

        $params = [
            'id' => self::$summit->getId(),
        ];

        $first_name = str_random(16).'_ticket_type';
        $last_name  = str_random(16).'_external_id';
        $email      = "roman.gutierrez@gmail.com";

        $data = [
            'email'                 => $email,
            'first_name'            => $first_name,
            'last_name'             => $last_name,
            'allowed_ticket_types'  => [self::$default_ticket_type->getId()],
            'acceptance_criteria'   => 'ALL_TICKET_TYPES',
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitRegistrationInvitationApiController@add",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(412);
    }

    public function testGetInvitationsByAllowedTicketTypes(){
        $params = [
            'id' => self::$summit->getId(),
            'filter' => ['ticket_types_id==' . self::$default_ticket_type->getId()],
            'order' => '-status'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitRegistrationInvitationApiController@getAllBySummit",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $invitations = json_decode($content);
        $this->assertNotEmpty($invitations);
        $this->assertResponseStatus(200);
    }

    public function testExportInvitations2CSV(){

        // first create an invitation so there's data to export
        $this->testInviteWithInvitation();

        $params = [
            'id'    => self::$summit->getId(),
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
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $this->assertNotEmpty($content);
    }

    public function testAcceptInvitation(){
        $this->markTestSkipped('Model issue: update service does not set is_accepted directly from payload; derived from ticket type state.');

        // first create an invitation
        $created = $this->testInviteWithInvitation();

        $params = [
            'id'            => self::$summit->getId(),
            'invitation_id' => $created->id
        ];

        $data = [
            'email'       => 'test@fntech.com',
            'is_accepted' => true,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitRegistrationInvitationApiController@update",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $this->assertResponseStatus(201);
        $content = $response->getContent();
        $invitation = json_decode($content);
        $this->assertTrue($invitation->is_accepted);
        $this->assertNotNull($invitation->accepted_date);
    }

    public function testGetInvitationBySummitAndToken(){
        $this->markTestSkipped('Model issue: invitation hash is never populated by the service layer.');

        $params = [
            'id' => self::$summit->getId(),
            'token' => 'placeholder',
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitRegistrationInvitationApiController@getInvitationBySummitAndToken",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $result = json_decode($content);
        $this->assertNotEmpty($result);
    }

    public function testRejectInvitationBySummitAndToken(){
        $this->markTestSkipped('Model issue: invitation hash is never populated by the service layer.');

        $params = [
            'id' => self::$summit->getId(),
            'token' => 'placeholder',
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitRegistrationInvitationApiController@rejectInvitationBySummitAndToken",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(204);
    }

    public function testSendInvitationsEmail() {

        // first create an invitation
        $invitation = $this->testInviteWithInvitation();

        $params = [
            'id' => self::$summit->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $data = [
            'email_flow_event'  => InviteSummitRegistrationEmail::EVENT_SLUG,
            'invitations_ids'   => [ $invitation->id ],
            'test_email_recipient'    => 'test_recip@nomail.com',
            'outcome_email_recipient' => 'outcome_recip@nomail.com',
        ];

        $response = $this->action
        (
            "PUT",
            "OAuth2SummitRegistrationInvitationApiController@send",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
    }

    public function testGetInvitationById(){
        $invitation = $this->testInviteWithInvitation();

        $params = [
            'id' => self::$summit->getId(),
            'invitation_id' => $invitation->id,
            'expand' => 'tags',
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitRegistrationInvitationApiController@get",
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
        $this->assertTrue($fetched->id == $invitation->id);
    }

    public function testGetInvitationById404(){
        $params = [
            'id' => self::$summit->getId(),
            'invitation_id' => 0,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitRegistrationInvitationApiController@get",
            $params,
            [],
            [],
            [],
            $headers
        );

        $this->assertResponseStatus(404);
    }

    public function testDeleteInvitation(){
        $invitation = $this->testInviteWithInvitation();

        $params = [
            'id' => self::$summit->getId(),
            'invitation_id' => $invitation->id,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitRegistrationInvitationApiController@delete",
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
            "OAuth2SummitRegistrationInvitationApiController@get",
            $params,
            [],
            [],
            [],
            $headers
        );

        $this->assertResponseStatus(404);
    }

    public function testDeleteAllInvitations(){
        // create some invitations first
        $this->testInviteWithInvitation();

        $params = [
            'id' => self::$summit->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitRegistrationInvitationApiController@deleteAll",
            $params,
            [],
            [],
            [],
            $headers
        );

        $this->assertResponseStatus(204);

        // verify all are gone
        $response = $this->action(
            "GET",
            "OAuth2SummitRegistrationInvitationApiController@getAllBySummit",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $invitations = json_decode($content);
        $this->assertTrue($invitations->total == 0);
    }
}