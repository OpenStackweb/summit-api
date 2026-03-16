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

final class OAuth2SummitBadgeTypeApiTest extends ProtectedApiTestCase
{
    use InsertSummitTestData;

    use InsertOrdersTestData;

    protected function setUp(): void
    {
        $this->setCurrentGroup(IGroup::TrackChairs);
        parent::setUp();
        self::$defaultMember = self::$member;
        self::insertSummitTestData();
        self::InsertOrdersTestData();
    }

    protected function tearDown(): void
    {
        self::clearSummitTestData();
        parent::tearDown();
    }

    public function testAddBadgeType(){
        $params = [
            'id' => self::$summit->getId(),
        ];

        $name = str_random(16).'_badge_type';
        $template = <<<HTML
<html>
<body>
<div>
<h1>this is a badge</h1>
<features-container></features-container>
</div>
</body>
</html>
HTML;

        $data = [
            'name'             => $name,
            'description'      => "this is a description",
            'template_content' => $template,
            'is_default'       => false
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitBadgeTypeApiController@add",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $badge_type = json_decode($content);
        $this->assertNotNull($badge_type);
        $this->assertEquals($name, $badge_type->name);
        return $badge_type;
    }

    public function testGetAllBySummit(){
        $params = [
            'id' => self::$summit->getId(),
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitBadgeTypeApiController@getAllBySummit",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $data = json_decode($content);
        $this->assertNotNull($data);
        return $data;
    }

    public function testGetBadgeTypeById(){
        $params = [
            'id' => self::$summit->getId(),
            'badge_type_id' => self::$default_badge_type->getId(),
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitBadgeTypeApiController@get",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $badge_type = json_decode($content);
        $this->assertNotNull($badge_type);
        $this->assertEquals(self::$default_badge_type->getId(), $badge_type->id);
        return $badge_type;
    }

    public function testUpdateBadgeType(){
        $new_badge_type = $this->testAddBadgeType();
        $params = [
            'id' => self::$summit->getId(),
            'badge_type_id' => $new_badge_type->id
        ];

        $data = [
            'description' => 'this is a description update',
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitBadgeTypeApiController@update",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $badge_type = json_decode($content);
        $this->assertNotNull($badge_type);
        $this->assertEquals($new_badge_type->name, $badge_type->name);
        $this->assertEquals('this is a description update', $badge_type->description);
        return $badge_type;
    }

    public function testDeleteBadgeType(){
        $new_badge_type = $this->testAddBadgeType();
        $params = [
            'id' => self::$summit->getId(),
            'badge_type_id' => $new_badge_type->id
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitBadgeTypeApiController@delete",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $this->assertResponseStatus(204);
    }

    public function testAssignAccessLevelToBadgeType(){
        $new_badge_type = $this->testAddBadgeType();
        $access_level = self::$summit->getBadgeAccessLevelTypes()->first();
        $this->assertNotNull($access_level);

        $params = [
            'id' => self::$summit->getId(),
            'badge_type_id' => $new_badge_type->id,
            'access_level_id' => $access_level->getId()
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitBadgeTypeApiController@addAccessLevelToBadgeType",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $badge_type = json_decode($content);
        $this->assertNotNull($badge_type);
        return $badge_type;
    }

    public function testRemoveAccessLevelFromBadgeType(){
        $badge_type = $this->testAssignAccessLevelToBadgeType();
        $access_level = self::$summit->getBadgeAccessLevelTypes()->first();
        $this->assertNotNull($access_level);

        $params = [
            'id' => self::$summit->getId(),
            'badge_type_id' => $badge_type->id,
            'access_level_id' => $access_level->getId()
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitBadgeTypeApiController@removeAccessLevelFromBadgeType",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
    }

    public function testAddFeatureToBadgeType(){
        $new_badge_type = $this->testAddBadgeType();
        $feature = self::$badge_features[0];

        $params = [
            'id' => self::$summit->getId(),
            'badge_type_id' => $new_badge_type->id,
            'feature_id' => $feature->getId()
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitBadgeTypeApiController@addFeatureToBadgeType",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $badge_type = json_decode($content);
        $this->assertNotNull($badge_type);
        return $badge_type;
    }

    public function testRemoveFeatureFromBadgeType(){
        $badge_type = $this->testAddFeatureToBadgeType();
        $feature = self::$badge_features[0];

        $params = [
            'id' => self::$summit->getId(),
            'badge_type_id' => $badge_type->id,
            'feature_id' => $feature->getId()
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitBadgeTypeApiController@removeFeatureFromBadgeType",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
    }

    public function testAddViewTypeToBadgeType(){
        $new_badge_type = $this->testAddBadgeType();

        $params = [
            'id' => self::$summit->getId(),
            'badge_type_id' => $new_badge_type->id,
            'badge_view_type_id' => self::$default_badge_view_type->getId()
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitBadgeTypeApiController@addViewTypeToBadgeType",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $badge_type = json_decode($content);
        $this->assertNotNull($badge_type);
        return $badge_type;
    }

    public function testRemoveViewTypeFromBadgeType(){
        $badge_type = $this->testAddViewTypeToBadgeType();

        $params = [
            'id' => self::$summit->getId(),
            'badge_type_id' => $badge_type->id,
            'badge_view_type_id' => self::$default_badge_view_type->getId()
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitBadgeTypeApiController@removeViewTypeFromBadgeType",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
    }
}
