<?php namespace Tests;
/*
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

use App\Models\Foundation\Main\IGroup;
use Illuminate\Http\UploadedFile;
use Mockery;
use models\summit\SummitSponsorshipType;

/**
 * Class OAuth2SummitSponsorshipTypeApiControllerTest
 * @package Tests
 */
final class OAuth2SummitSponsorshipTypeApiControllerTest
    extends ProtectedApiTestCase
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

    public function testAdd(){

        $params = [
            'id' => self::$summit->getId(),
            'expand' => 'type',
        ];

        $data = [
            'widget_title' => 'test',
            'lobby_template' => SummitSponsorshipType::LobbyTemplate_BigImages,
            'event_page_template' => SummitSponsorshipType::EventPageTemplate_HorizontalImages,
            'sponsor_page_use_disqus_widget' => false,
            'sponsor_page_use_live_event_widget' => true,
            'sponsor_page_use_schedule_widget' => false,
            'sponsor_page_use_banner_widget' => true,
            'type_id' => self::$default_sponsor_ship_type2->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitSponsorshipTypeApiController@add",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $summit_sponsorship_type = json_decode($content);
        $this->assertTrue(!is_null($summit_sponsorship_type));
        $this->assertTrue($summit_sponsorship_type->widget_title === 'test');
        $this->assertObjectHasAttribute('type', $summit_sponsorship_type);
    }

    public function testUpdate(){
        $params = [
            'id' => self::$summit->getId(),
            'expand' => 'type',
        ];

        $data = [
            'widget_title' => 'test',
            'lobby_template' => SummitSponsorshipType::LobbyTemplate_BigImages,
            'event_page_template' => SummitSponsorshipType::EventPageTemplate_HorizontalImages,
            'sponsor_page_use_disqus_widget' => false,
            'sponsor_page_use_live_event_widget' => true,
            'sponsor_page_use_schedule_widget' => false,
            'sponsor_page_use_banner_widget' => true,
            'type_id' => self::$default_sponsor_ship_type2->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitSponsorshipTypeApiController@add",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $summit_sponsorship_type = json_decode($content);
        $this->assertTrue(!is_null($summit_sponsorship_type));
        $this->assertTrue($summit_sponsorship_type->widget_title === 'test');
        $this->assertObjectHasAttribute('type', $summit_sponsorship_type);

        $params = [
            'id' => self::$summit->getId(),
            'type_id' => $summit_sponsorship_type->id,
            'expand' => 'type',
        ];

        $data = [
            'widget_title' => 'test update',
            'order' => 1
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitSponsorshipTypeApiController@update",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $summit_sponsorship_type = json_decode($content);
        $this->assertTrue(!is_null($summit_sponsorship_type));
        $this->assertTrue($summit_sponsorship_type->widget_title === 'test update');
        $this->assertTrue($summit_sponsorship_type->order === 1);
    }

    public function testDelete(){

        $params = [
            'id' => self::$summit->getId(),
            'expand' => 'type',
        ];

        $data = [
            'widget_title' => 'test',
            'lobby_template' => SummitSponsorshipType::LobbyTemplate_BigImages,
            'event_page_template' => SummitSponsorshipType::EventPageTemplate_HorizontalImages,
            'sponsor_page_use_disqus_widget' => false,
            'sponsor_page_use_live_event_widget' => true,
            'sponsor_page_use_schedule_widget' => false,
            'sponsor_page_use_banner_widget' => true,
            'type_id' => self::$default_sponsor_ship_type2->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitSponsorshipTypeApiController@add",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $summit_sponsorship_type = json_decode($content);

        $params = [
            'id' => self::$summit->getId(),
            'expand' => 'type',
            'type_id' => $summit_sponsorship_type->id
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitSponsorshipTypeApiController@delete",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(204);

    }

    public function testGetById(){
        $params = [
            'id' => self::$summit->getId(),
            'expand' => 'type',
        ];

        $data = [
            'widget_title' => 'test',
            'lobby_template' => SummitSponsorshipType::LobbyTemplate_BigImages,
            'event_page_template' => SummitSponsorshipType::EventPageTemplate_HorizontalImages,
            'sponsor_page_use_disqus_widget' => false,
            'sponsor_page_use_live_event_widget' => true,
            'sponsor_page_use_schedule_widget' => false,
            'sponsor_page_use_banner_widget' => true,
            'type_id' => self::$default_sponsor_ship_type2->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitSponsorshipTypeApiController@add",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $summit_sponsorship_type = json_decode($content);

        $params = [
            'id' => self::$summit->getId(),
            'expand' => 'type',
            'type_id' => $summit_sponsorship_type->id
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSponsorshipTypeApiController@get",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);

        $summit_sponsorship_type = json_decode($content);

        $this->assertTrue(!is_null($summit_sponsorship_type));
        $this->assertTrue($summit_sponsorship_type->widget_title === 'test');
        $this->assertObjectHasAttribute('type', $summit_sponsorship_type);
    }

    public function testGetAllBySummitId(){

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $params = [
            'id' => self::$summit->getId(),
            'expand' => 'type',
            'order' => 'order'
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSponsorshipTypeApiController@getAllBySummit",
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
    }

    public function testAddBadgeImage(){

        $params = [
            'id' => self::$summit->getId(),
            'expand' => 'type',
        ];

        $data = [
            'widget_title' => 'test',
            'lobby_template' => SummitSponsorshipType::LobbyTemplate_BigImages,
            'event_page_template' => SummitSponsorshipType::EventPageTemplate_HorizontalImages,
            'sponsor_page_use_disqus_widget' => false,
            'sponsor_page_use_live_event_widget' => true,
            'sponsor_page_use_schedule_widget' => false,
            'sponsor_page_use_banner_widget' => true,
            'type_id' => self::$default_sponsor_ship_type2->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitSponsorshipTypeApiController@add",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $summit_sponsorship_type = json_decode($content);

        $params = [
            'id' => self::$summit->getId(),
            "type_id" => $summit_sponsorship_type->id
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitSponsorshipTypeApiController@addBadgeImage",
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

    public function testDeleteBadgeImage(){
        $params = [
            'id' => self::$summit->getId(),
            'expand' => 'type',
        ];

        $data = [
            'widget_title' => 'test',
            'lobby_template' => SummitSponsorshipType::LobbyTemplate_BigImages,
            'event_page_template' => SummitSponsorshipType::EventPageTemplate_HorizontalImages,
            'sponsor_page_use_disqus_widget' => false,
            'sponsor_page_use_live_event_widget' => true,
            'sponsor_page_use_schedule_widget' => false,
            'sponsor_page_use_banner_widget' => true,
            'type_id' => self::$default_sponsor_ship_type2->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitSponsorshipTypeApiController@add",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $summit_sponsorship_type = json_decode($content);

        $params = [
            'id' => self::$summit->getId(),
            "type_id" => $summit_sponsorship_type->id
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitSponsorshipTypeApiController@addBadgeImage",
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

        $this->action(
            "DELETE",
            "OAuth2SummitSponsorshipTypeApiController@removeBadgeImage",
            $params,
            [],
            [],
            [
            ],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(204);
    }
}