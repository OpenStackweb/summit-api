<?php namespace Tests;
/**
 * Copyright 2021 OpenStack Foundation
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
use App\Models\Foundation\Elections\Election;
use App\Models\Foundation\Main\IGroup;
/**
 * Class OAuth2ElectionApiTest
 * @package Tests
 */
class OAuth2ElectionApiTest extends ProtectedApiTestCase
{
    /**
     * @var Election|null
     */
    private static $election = null;

    protected $current_group = IGroup::FoundationMembers;

    protected function setUp():void
    {
        parent::setUp();
        self::$member->signIndividualMembership();
        self::$election = new Election();
        self::$election->setName("TEST ELECTION");
        $now = new \DateTime("now", new \DateTimeZone("UTC"));
        self::$election->setNominationOpens($now);
        self::$election->setNominationCloses((clone $now)->add(new \DateInterval("P10D")));
        self::$election->setNominationDeadline((clone $now)->add(new \DateInterval("P2D")));
        self::$election->setOpens((clone $now)->add(new \DateInterval("P20D")));
        self::$election->setCloses((clone $now)->add(new \DateInterval("P22D")));
        self::$em->persist(self::$election);
        self::$em->flush();
    }

    protected function tearDown():void
    {
        parent::tearDown();
    }

    public function testGetCurrentElection(){
        $params = [
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2ElectionsApiController@getCurrent",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $election = json_decode($content);
        $this->assertTrue(!is_null($election));
        $this->assertTrue($election->name == "TEST ELECTION");
    }

    public function testSaveMyCandidate(){
        $params = [
            'expand' => 'candidate_profile'
        ];

        $data = [
            'bio' => "lorep ip sum",
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2ElectionsApiController@updateMyCandidateProfile",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $member = json_decode($content);
        $this->assertTrue(!is_null($member));
        $this->assertTrue($member->candidate_profile->bio == "lorep ip sum");
    }

    public function testNominateMySelf(){

        $params = [
            'candidate_id' => self::$member->getId(),
            'expand' => 'candidate_profile'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2ElectionsApiController@nominateCandidate",
            $params,
            [],
            [],
            [],
            $headers,
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $nomination = json_decode($content);
        $this->assertTrue(!is_null($nomination));
    }

    public function testGetAllElections(){
        $params = [
            'page'     => 1,
            'per_page' => 10,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2ElectionsApiController@getAll",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $elections = json_decode($content);
        $this->assertNotNull($elections);
        $this->assertGreaterThan(0, $elections->total);
    }

    public function testGetElectionById(){
        $params = [
            'election_id' => self::$election->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2ElectionsApiController@getById",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $election = json_decode($content);
        $this->assertNotNull($election);
        $this->assertEquals("TEST ELECTION", $election->name);
    }

    public function testGetCurrentCandidates(){
        // nominate first so there's at least one candidate
        $this->testNominateMySelf();

        $params = [
            'page'     => 1,
            'per_page' => 10,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2ElectionsApiController@getCurrentCandidates",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $candidates = json_decode($content);
        $this->assertNotNull($candidates);
    }

    public function testGetElectionCandidates(){
        // nominate first so there's at least one candidate
        $this->testNominateMySelf();

        $params = [
            'election_id' => self::$election->getId(),
            'page'     => 1,
            'per_page' => 10,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2ElectionsApiController@getElectionCandidates",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $candidates = json_decode($content);
        $this->assertNotNull($candidates);
    }

    public function testGetCurrentGoldCandidates(){
        $params = [
            'page'     => 1,
            'per_page' => 10,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2ElectionsApiController@getCurrentGoldCandidates",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $candidates = json_decode($content);
        $this->assertNotNull($candidates);
    }

    public function testGetElectionGoldCandidates(){
        $params = [
            'election_id' => self::$election->getId(),
            'page'     => 1,
            'per_page' => 10,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2ElectionsApiController@getElectionGoldCandidates",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $candidates = json_decode($content);
        $this->assertNotNull($candidates);
    }

}