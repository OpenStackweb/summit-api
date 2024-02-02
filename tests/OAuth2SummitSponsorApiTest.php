<?php namespace Tests;
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

use App\Models\Foundation\Main\IGroup;
use Illuminate\Http\UploadedFile;
use Mockery;
/**
 * Class OAuth2SummitSponsorApiTest
 */
final class OAuth2SummitSponsorApiTest extends ProtectedApiTest
{
    use InsertSummitTestData;

    use InsertMemberTestData;

    public function createApplication()
    {
        $app = parent::createApplication();

        $fileUploaderMock = Mockery::mock(\App\Http\Utils\IFileUploader::class)
            ->shouldIgnoreMissing();

        $fileUploaderMock->shouldReceive('build')->andReturn(new \models\main\File());

        $app->instance(\App\Http\Utils\IFileUploader::class, $fileUploaderMock);

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        self::insertMemberTestData(IGroup::TrackChairs);
        self::$defaultMember = self::$member;
        self::insertSummitTestData();
    }

    protected function tearDown(): void
    {
        self::clearSummitTestData();
        self::clearMemberTestData();
        parent::tearDown();
    }

    public function testAddSponsor(){

        $params = [
            'id' => self::$summit->getId(),
            'expand' => 'sponsorship,sponsorship.type',
        ];

        $data = [
            'company_id'  => self::$companies_without_sponsor[0]->getId(),
            'sponsorship_id' => self::$default_summit_sponsor_type->getId(),
            'marquee' => 'this is a marquee',
            'intro' => 'this is an intro',
            'is_published' => false,
            'external_link' => 'https://external.com',
            'chat_link' => 'https://chat.com',
            'video_link' => 'https://video.com',
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitSponsorApiController@add",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $sponsor = json_decode($content);
        $this->assertTrue(!is_null($sponsor));
        $this->assertTrue($sponsor->marquee === 'this is a marquee');
        $this->assertTrue($sponsor->external_link === 'https://external.com');
        $this->assertObjectHasAttribute('sponsorship', $sponsor);
        return $sponsor;
    }

    public function testUploadSponsorSideImage(){
        $params = [
            'id' => self::$summit->getId(),
            "sponsor_id" => self::$sponsors[0]->getId()
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitSponsorApiController@addSponsorSideImage",
            $params,
            [],
            [],
            [
                'file' => UploadedFile::fake()->image('image.svg'),
            ],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $file = json_decode($content);
        $this->assertTrue(!is_null($file));
    }

    public function testGetAllSponsorsBySummit(){
        $params = [
            'id' => self::$summit->getId(),
            'filter'=> 'company_name=@'.substr(self::$companies[0]->getName(),0,3),
            'expand' => 'company,sponsorship,extra_questions'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSponsorApiController@getAllBySummit",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $page = json_decode($content);
        $this->assertTrue(!is_null($page));
        return $page;
    }

    public function testGetAllSponsorsAdsBySponsor(){
        $params = [
            'id' => self::$summit->getId(),
            'sponsor_id' => self::$sponsors[0]->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSponsorApiController@getAds",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $page = json_decode($content);
        $this->assertTrue(!is_null($page));
        $this->assertNotEmpty($page->data);
        return $page;
    }

    public function testGetAllSponsorsMaterialsBySponsor(){
        $params = [
            'id' => self::$summit->getId(),
            'sponsor_id' => self::$sponsors[0]->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSponsorApiController@getMaterials",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $page = json_decode($content);
        $this->assertTrue(!is_null($page));
        $this->assertNotEmpty($page->data);
        return $page;
    }

    public function testGetAllSponsorsMaterialsBySponsorAndType(){

        $params = [
            'id' => self::$summit->getId(),
            'sponsor_id' => self::$sponsors[0]->getId(),
            'filter'=> 'type==Video',
            'order' => '-order',
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSponsorApiController@getMaterials",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $page = json_decode($content);
        $this->assertTrue(!is_null($page));
        $this->assertNotEmpty($page->data);
        return $page;
    }

    public function testDeleteMaterial(){
        $params = [
            'id' => self::$summit->getId(),
            'sponsor_id'=> self::$sponsors[0]->getId(),
            'material_id' => self::$sponsors[0]->getMaterials()[0]->getId()
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitSponsorApiController@deleteMaterial",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(204);
        $this->assertTrue(empty($content));
    }

    public function testGetAllSponsorsSocialNetworksBySponsor(){
        $params = [
            'id' => self::$summit->getId(),
            'sponsor_id' => self::$sponsors[0]->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSponsorApiController@getSocialNetworks",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $page = json_decode($content);
        $this->assertTrue(!is_null($page));
        $this->assertNotEmpty($page->data);
        return $page;
    }

    public function testDeleteSponsor(){

        $params = [
            'id' => self::$summit->getId(),
            'sponsor_id'=> self::$sponsors[0]->getId()
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitSponsorApiController@delete",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(204);
        $this->assertTrue(empty($content));
    }

    public function testAddSponsorUserMember(){
        $params = [
            'id'         => self::$summit->getId(),
            'sponsor_id' => self::$sponsors[0]->getId(),
            'member_id'  => self::$member->getId()
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitSponsorApiController@addSponsorUser",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $sponsor = json_decode($content);
        $this->assertTrue(!is_null($sponsor));
        return $sponsor;
    }
}