<?php namespace Tests;
/**
 * Copyright 2016 OpenStack Foundation
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
use App\Models\Foundation\Main\IGroup;
use models\main\Group;
use models\main\LegalAgreement;
use Mockery;
use DateTime;
/**
 * Class OAuth2MembersApiTest
 * @package Tests
 */
final class OAuth2MembersApiTest extends ProtectedApiTestCase
{
    use InsertSummitTestData;

    protected function setUp():void
    {
        $this->setCurrentGroup(IGroup::TrackChairs);
        parent::setUp();
        self::insertSummitTestData();
        self::$summit_permission_group->addMember(self::$member);
        self::$summit_permission_group->addMember(self::$member2);
        self::$em->persist(self::$summit);
        self::$em->persist(self::$summit_permission_group);
        self::$em->flush();
        self::$summit->addTrackChair(self::$member, [ self::$defaultTrack ] );
        self::$em->persist(self::$summit);
        self::$em->flush();
    }

    public function tearDown():void
    {
        try {
            self::$em->getConnection()->executeStatement("DROP TABLE IF EXISTS SiteTree");
        } catch (\Exception $e) {}
        self::clearSummitTestData();
        Mockery::close();
        parent::tearDown();
    }

    public function testGetMembers()
    {

        $params = [
            //AND FILTER
            'filter' => ['first_name=@Seba', 'last_name=@Marcet'],
            'order'  => '+first_name,-last_name'
        ];

        $headers = array("HTTP_Authorization" => " Bearer " . $this->access_token);
        $response = $this->action(
            "GET",
            "OAuth2MembersApiController@getAll",
            $params,
            array(),
            array(),
            array(),
            $headers
        );

        $content = $response->getContent();
        $members = json_decode($content);
        $this->assertTrue(!is_null($members));
        $this->assertResponseStatus(200);
    }

    public function testGetMemberByFullName()
    {

        $params = [
            //AND FILTER
            'filter' => ['full_name=@Seba'],
            'order'  => '+first_name,-last_name'
        ];

        $headers = array("HTTP_Authorization" => " Bearer " . $this->access_token);
        $response = $this->action(
            "GET",
            "OAuth2MembersApiController@getAll",
            $params,
            array(),
            array(),
            array(),
            $headers
        );

        $content = $response->getContent();
        $members = json_decode($content);
        $this->assertTrue(!is_null($members));
        $this->assertResponseStatus(200);
    }

    public function testGetMembersEmpty()
    {

        $params = [
            'filter' => ['first_name=@', 'last_name=@'],
            //AND FILTER
            'order'  => '+first_name,-last_name'
        ];

        $headers = ["HTTP_Authorization" => " Bearer " . $this->access_token];
        $response = $this->action(
            "GET",
            "OAuth2MembersApiController@getAll",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(412);
    }

    public function testGetMembersByEmail()
    {
        $params = [
            'filter' => 'email=@sebastian@tipit.net',
            'order'  => '+first_name,-last_name',
            'expand' => 'groups'
        ];

        $headers  = array("HTTP_Authorization" => " Bearer " . $this->access_token);
        $response = $this->action(
            "GET",
            "OAuth2MembersApiController@getAll",
            $params,
            array(),
            array(),
            array(),
            $headers
        );

        $content = $response->getContent();
        $members = json_decode($content);

        $this->assertTrue(!is_null($members));
        $this->assertResponseStatus(200);
    }

    public function testGetMembersByEmail2()
    {
        $params = [
            'filter' => ['email==sean.mcginnis@gmail.com', "email_verified==0"],
        ];

        $headers  = array("HTTP_Authorization" => " Bearer " . $this->access_token);
        $response = $this->action(
            "GET",
            "OAuth2MembersApiController@getAll",
            $params,
            array(),
            array(),
            array(),
            $headers
        );

        $content = $response->getContent();
        $members = json_decode($content);
        $this->assertTrue(!is_null($members));
        $this->assertResponseStatus(200);
    }

    public function testGetMyMember()
    {
        $params = [
            'expand' => 'groups,track_chairs,sponsor_memberships.extra_questions'
        ];

        $headers  = array("HTTP_Authorization" => " Bearer " . $this->access_token);
        $response = $this->action(
            "GET",
            "OAuth2MembersApiController@getMyMember",
            $params,
            array(),
            array(),
            array(),
            $headers
        );

        $content = $response->getContent();
        $member = json_decode($content);
        $this->assertTrue(!is_null($member));
        $this->assertResponseStatus(200);
    }

    public function testGetMembersByGitHubUser()
    {
        $params = [
            'filter' => 'github_user=@smarcet',
            'order'  => '+first_name,-last_name',
            'expand' => 'groups, ccla_teams'
        ];

        $headers  = ["HTTP_Authorization" => " Bearer " . $this->access_token];
        $response = $this->action(
            "GET",
            "OAuth2MembersApiController@getAll",
            $params,
            array(),
            array(),
            array(),
            $headers
        );

        $content = $response->getContent();
        $members = json_decode($content);
        $this->assertTrue(!is_null($members));
        $this->assertResponseStatus(200);
    }

    public function testAddMemberAffiliation(){
        $member_id = self::$member->getId();
        $params = [
            'member_id'      => $member_id,
        ];

        $start_datetime      = new DateTime( "2018-11-10 00:00:00");
        $start_datetime_unix = $start_datetime->getTimestamp();

        $data = [
            'is_current' => true,
            'start_date' => $start_datetime_unix,
            'job_title'  => 'test affiliation',
            'end_date'   => null,
            'organization_name' => 'test organization'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"       => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2MembersApiController@addAffiliation",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $affiliation = json_decode($content);
        $this->assertTrue(!is_null($affiliation));
        return $affiliation;
    }

    public function testAddMyMemberAffiliation(){
        $params = [

        ];

        $start_datetime      = new DateTime( "2018-11-10 00:00:00");
        $start_datetime_unix = $start_datetime->getTimestamp();

        $data = [
            'is_current' => true,
            'start_date' => $start_datetime_unix,
            'job_title'  => 'test affiliation',
            'end_date'   => null,
            'organization_name' => 'test new organization'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"       => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2MembersApiController@addMyAffiliation",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $affiliation = json_decode($content);
        $this->assertTrue(!is_null($affiliation));
        return $affiliation;
    }

    public function testUpdateMemberAffiliation(){
        $member_id = self::$member->getId();
        $new_affiliation = $this->testAddMemberAffiliation();
        $params = [
            'member_id'      => $member_id,
            'affiliation_id' => $new_affiliation->id,
        ];

        $data = [
            'job_title'  => 'job title update'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"       => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2MembersApiController@updateAffiliation",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $affiliation = json_decode($content);
        $this->assertTrue(!is_null($affiliation));
        return $affiliation;
    }

    public function testDeleteMemberAffiliation(){
        $member_id = self::$member->getId();
        $new_affiliation = $this->testAddMemberAffiliation();
        $params = [
            'member_id'      => $member_id,
            'affiliation_id' => $new_affiliation->id,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"       => "application/json"
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2MembersApiController@deleteAffiliation",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(204);
    }

    public function testGetMemberAffiliation()
    {
        $member_id = self::$member->getId();
        $params = [
            'member_id' => $member_id
        ];

        $headers = ["HTTP_Authorization" => " Bearer " . $this->access_token];
        $response = $this->action(
            "GET",
            "OAuth2MembersApiController@getMemberAffiliations",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $affiliations = json_decode($content);
        $this->assertTrue(!is_null($affiliations));
        $this->assertResponseStatus(200);
    }

    private function createFoundationMembershipPrerequisites(): void
    {
        // Create the FoundationMembers group needed by signFoundationMembership service
        $foundationGroup = new Group();
        $foundationGroup->setCode(IGroup::FoundationMembers);
        $foundationGroup->setTitle(IGroup::FoundationMembers);
        self::$em->persist($foundationGroup);
        self::$em->flush();

        // Create the SiteTree legal document record needed by DoctrineLegalDocumentRepository
        $conn = self::$em->getConnection();
        $conn->executeStatement("CREATE TABLE IF NOT EXISTS SiteTree (
            ID INT AUTO_INCREMENT PRIMARY KEY,
            Title VARCHAR(255),
            URLSegment VARCHAR(255),
            Content TEXT,
            ClassName VARCHAR(255)
        )");
        $conn->executeStatement(
            "INSERT INTO SiteTree (Title, URLSegment, Content, ClassName) VALUES (?, ?, ?, ?)",
            [
                'The OpenStack Foundation Individual Member Agreement',
                LegalAgreement::Slug,
                'Test legal content',
                'LegalDocumentPage'
            ]
        );
    }

    public function testSignFoundationMembership(){
        $this->createFoundationMembershipPrerequisites();

        $params = [
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"       => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2MembersApiController@signFoundationMembership",
            $params,
            [],
            [],
            [],
            $headers,
            ""
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $member = json_decode($content);
        $this->assertTrue(!is_null($member));
        return $member;
    }

    public function testSignResignFoundationMembership(){
        $this->createFoundationMembershipPrerequisites();

        $params = [
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"       => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2MembersApiController@signFoundationMembership",
            $params,
            [],
            [],
            [],
            $headers,
            ""
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $member = json_decode($content);
        $this->assertTrue(!is_null($member));

        $response = $this->action(
            "DELETE",
            "OAuth2MembersApiController@resignMembership",
            $params,
            [],
            [],
            [],
            $headers,
            ""
        );

        $content = $response->getContent();
        $this->assertResponseStatus(204);
        return $member;
    }

    public function testGetMemberCompanies(){
        $params = [

        ];

        $headers = ["HTTP_Authorization" => " Bearer " . $this->access_token];
        $response = $this->action(
            "GET",
            "OAuth2MembersApiController@getAllCompanies",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $companies = json_decode($content);
        $this->assertNotNull($companies);
        $this->assertEquals(2, $companies->total);
        $this->assertResponseStatus(200);
    }

    public function testGetMemberCompaniesFilterByName(){
        $params = [
            'filter' => ['company@@FN'],
            'order'  => '+company',
        ];

        $headers = ["HTTP_Authorization" => " Bearer " . $this->access_token];
        $response = $this->action(
            "GET",
            "OAuth2MembersApiController@getAllCompanies",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $companies = json_decode($content);
        $this->assertTrue(!is_null($companies));
        $this->assertTrue($companies->total == 1 );
        $this->assertResponseStatus(200);
    }

    public function testGetMemberById(){
        $params = [
            'member_id' => self::$member->getId(),
        ];

        $headers = ["HTTP_Authorization" => " Bearer " . $this->access_token];
        $response = $this->action(
            "GET",
            "OAuth2MembersApiController@getById",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $member = json_decode($content);
        $this->assertNotNull($member);
        $this->assertEquals(self::$member->getId(), $member->id);
    }

    public function testUpdateMyMember(){
        $params = [];

        $data = [
            'shirt_size' => 'Large',
            'display_on_site' => true,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"       => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2MembersApiController@updateMyMember",
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
        $this->assertNotNull($member);
    }

    public function testGetMyMemberAffiliations(){
        // add an affiliation first
        $this->testAddMyMemberAffiliation();

        $params = [];

        $headers = ["HTTP_Authorization" => " Bearer " . $this->access_token];
        $response = $this->action(
            "GET",
            "OAuth2MembersApiController@getMyMemberAffiliations",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $affiliations = json_decode($content);
        $this->assertNotNull($affiliations);
        $this->assertGreaterThan(0, $affiliations->total);
    }

    public function testUpdateMyAffiliation(){
        $affiliation = $this->testAddMyMemberAffiliation();

        $params = [
            'affiliation_id' => $affiliation->id,
        ];

        $data = [
            'job_title' => 'updated job title',
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"       => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2MembersApiController@updateMyAffiliation",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $affiliation = json_decode($content);
        $this->assertNotNull($affiliation);
    }

    public function testDeleteMyAffiliation(){
        $affiliation = $this->testAddMyMemberAffiliation();

        $params = [
            'affiliation_id' => $affiliation->id,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"       => "application/json"
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2MembersApiController@deleteMyAffiliation",
            $params,
            [],
            [],
            [],
            $headers
        );

        $this->assertResponseStatus(204);
    }

    public function testDeleteRSVP(){
        // RSVP data is not part of test fixtures; create member context and expect 404 for non-existent RSVP
        $params = [
            'member_id' => self::$member->getId(),
            'rsvp_id'   => 0,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"       => "application/json"
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2MembersApiController@deleteRSVP",
            $params,
            [],
            [],
            [],
            $headers
        );

        $this->assertResponseStatus(404);
    }

    public function testSignCommunityMembership(){
        // Create the CommunityMembers group needed by the service
        $communityGroup = new Group();
        $communityGroup->setCode(IGroup::CommunityMembers);
        $communityGroup->setTitle(IGroup::CommunityMembers);
        self::$em->persist($communityGroup);
        self::$em->flush();

        $params = [];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"       => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2MembersApiController@signCommunityMembership",
            $params,
            [],
            [],
            [],
            $headers,
            ""
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $member = json_decode($content);
        $this->assertNotNull($member);
    }

    public function testSignIndividualMembership(){
        $params = [];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"       => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2MembersApiController@signIndividualMembership",
            $params,
            [],
            [],
            [],
            $headers,
            ""
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $member = json_decode($content);
        $this->assertNotNull($member);
    }
}