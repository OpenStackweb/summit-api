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

use models\summit\SummitScheduleConfig;
use models\summit\SummitScheduleFilterElementConfig;

/**
 * Class OAuth2SummitScheduleSettingsApiTest
 * @package Tests
 */
final class OAuth2SummitScheduleSettingsApiTest extends ProtectedApiTestCase
{
    use InsertSummitTestData;

    protected function setUp(): void
    {
        parent::setUp();
        self::insertSummitTestData();
    }

    protected function tearDown(): void
    {
        self::clearSummitTestData();
        parent::tearDown();
    }

    public function testAdd()
    {
        $params = [
            'id' => self::$summit->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $data = [
            'key' => 'my-schedule-config',
            'is_enabled' => true,
            'is_default' => true,
            'is_my_schedule' => true,
            'only_events_with_attendee_access' => true,
            'hide_past_events_with_show_always_on_schedule' => true,
            'color_source' => SummitScheduleConfig::ColorSource_EventType,
            'time_format' => SummitScheduleConfig::TimeFormat_24,
        ];

        $response = $this->action
        (
            "POST",
            "OAuth2SummitScheduleSettingsApiController@add",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $config = json_decode($content);
        $this->assertTrue($config->id > 0);
        $this->assertTrue($config->summit_id == self::$summit->getId());
        $this->assertTrue($config->key == 'my-schedule-config');
        return $config;
    }

    public function testSeed()
    {
        $params = [
            'id' => self::$summit->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action
        (
            "POST",
            "OAuth2SummitScheduleSettingsApiController@seedDefaults",
            $params,
            [],
            [],
            [],
            $headers,
            ""
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $configs = json_decode($content);

    }

    public function testAddWithFiltersAndPrefilters()
    {
        $params = [
            'id' => self::$summit->getId(),
            'expand' => 'filters,pre_filters',
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $data = [
            'key' => 'my-schedule-config',
            'is_enabled' => true,
            'is_default' => true,
            'is_my_schedule' => true,
            'only_events_with_attendee_access' => true,
            'hide_past_events_with_show_always_on_schedule' => true,
            'color_source' => SummitScheduleConfig::ColorSource_EventType,
            'time_format' => SummitScheduleConfig::TimeFormat_24,
            'filters' => [
                [
                    'type' => SummitScheduleFilterElementConfig::Type_Date,
                    'is_enabled' => true,
                ],
                [
                    'type' => SummitScheduleFilterElementConfig::Type_Tags,
                    'is_enabled' => true,
                ],
                [
                    'type' => SummitScheduleFilterElementConfig::Type_EventTypes,
                    'is_enabled' => true,
                ]
            ],
            'pre_filters' => [
                [
                    'type' => SummitScheduleFilterElementConfig::Type_Tags,
                    'values' => ['tag1','tag2', 'tag3']
                ],
                [
                    'type' => SummitScheduleFilterElementConfig::Type_Company,
                    'values' => ['1', '2']
                ],
            ]
        ];

        $response = $this->action
        (
            "POST",
            "OAuth2SummitScheduleSettingsApiController@add",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $config = json_decode($content);
        $this->assertTrue($config->id > 0);
        $this->assertTrue($config->summit_id == self::$summit->getId());
        $this->assertTrue($config->key == 'my-schedule-config');
        $this->assertTrue(count($config->filters) == 3);
        $this->assertTrue(count($config->pre_filters) == 2);

        return $config;
    }

    public function testAddWithDupFilters()
    {
        $params = [
            'id' => self::$summit->getId(),
            'expand' => 'filters',
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $data = [
            'key' => 'my-schedule-config',
            'is_default' => true,
            'is_enabled' => true,
            'is_my_schedule' => true,
            'only_events_with_attendee_access' => true,
            'hide_past_events_with_show_always_on_schedule' => true,
            'color_source' => SummitScheduleConfig::ColorSource_EventType,
            'time_format' => SummitScheduleConfig::TimeFormat_24,
            'filters' => [
                [
                    'type' => SummitScheduleFilterElementConfig::Type_Date,
                    'is_enabled' => true,
                ],
                [
                    'type' => SummitScheduleFilterElementConfig::Type_Date,
                    'is_enabled' => true,
                ],
                [
                    'type' => SummitScheduleFilterElementConfig::Type_Tags,
                    'is_enabled' => true,
                ],
                [
                    'type' => SummitScheduleFilterElementConfig::Type_EventTypes,
                    'is_enabled' => true,
                ]
            ],
            'pre_filters' => [
                [
                    'type' => SummitScheduleFilterElementConfig::Type_Tags,
                    'values' => ['tag1','tag2', 'tag3']
                ],
                [
                    'type' => SummitScheduleFilterElementConfig::Type_Company,
                    'values' => ['1', '2']
                ],
            ]
        ];

        $response = $this->action
        (
            "POST",
            "OAuth2SummitScheduleSettingsApiController@add",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );
        $content = $response->getContent();
        $this->assertResponseStatus(412);
        $res = json_decode($content);
        $this->assertNotEmpty($res->errors);
        $this->assertTrue($res->errors[0] == "Type DATE already exists");
    }

    public function testUpdateFilter(){
        $params = [
            'id' => self::$summit->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $data = [
            'key' => 'my-schedule-config',
            'is_enabled' => true,
            'is_default' => false,
            'is_my_schedule' => true,
            'only_events_with_attendee_access' => true,
            'hide_past_events_with_show_always_on_schedule' => true,
            'color_source' => SummitScheduleConfig::ColorSource_EventType,
            'time_format' => SummitScheduleConfig::TimeFormat_24,
            'filters' => [
                [
                    'type' => SummitScheduleFilterElementConfig::Type_Date,
                    'is_enabled' => true,
                ],
                [
                    'type' => SummitScheduleFilterElementConfig::Type_Tags,
                    'is_enabled' => true,
                ],
                [
                    'type' => SummitScheduleFilterElementConfig::Type_EventTypes,
                    'is_enabled' => true,
                ]
            ],
            'pre_filters' => [
                [
                    'type' => SummitScheduleFilterElementConfig::Type_Tags,
                    'values' => ['tag1','tag2', 'tag3']
                ],
                [
                    'type' => SummitScheduleFilterElementConfig::Type_Company,
                    'values' => ['1', '2']
                ],
            ]
        ];

        $response = $this->action
        (
            "POST",
            "OAuth2SummitScheduleSettingsApiController@add",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $config = json_decode($content);
        $this->assertTrue($config->id > 0);
        $this->assertTrue($config->summit_id == self::$summit->getId());
        $this->assertTrue($config->key == 'my-schedule-config');


        $params = [
            'id' => self::$summit->getId(),
            'config_id' => $config->id,
            'expand' => 'filters',
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $data = [
            'key' => 'my-schedule-config',
            'is_enabled' => true,
            'is_default' => false,
            'is_my_schedule' => true,
            'only_events_with_attendee_access' => true,
            'hide_past_events_with_show_always_on_schedule' => true,
            'color_source' => SummitScheduleConfig::ColorSource_EventType,
            'time_format' => SummitScheduleConfig::TimeFormat_24,
            'filters' => [
                [
                    'type' => SummitScheduleFilterElementConfig::Type_Date,
                    'is_enabled' => true,
                ],
                [
                    'type' => SummitScheduleFilterElementConfig::Type_Tags,
                    'is_enabled' => true,
                ],
                [
                    'type' => SummitScheduleFilterElementConfig::Type_EventTypes,
                    'is_enabled' => true,
                ]
            ],
            'pre_filters' => [
                [
                    'type' => SummitScheduleFilterElementConfig::Type_Tags,
                    'values' => ['tag1','tag5', 'tag3']
                ],
                [
                    'type' => SummitScheduleFilterElementConfig::Type_Company,
                    'values' => ['1', '3']
                ],
            ]
        ];

        $response = $this->action
        (
            "PUT",
            "OAuth2SummitScheduleSettingsApiController@update",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $config = json_decode($content);
        $this->assertTrue($config->id > 0);
        $this->assertTrue($config->summit_id == self::$summit->getId());
        $this->assertTrue($config->key == 'my-schedule-config');
        $this->assertTrue(count($config->filters) == 3);

    }

    public function testDelete(){
        $params = [
            'id' => self::$summit->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $data = [
            'key' => 'my-schedule-config',
            'is_default' => true,
            'is_enabled' => true,
            'is_my_schedule' => true,
            'only_events_with_attendee_access' => true,
            'hide_past_events_with_show_always_on_schedule' => true,
            'color_source' => SummitScheduleConfig::ColorSource_EventType,
            'time_format' => SummitScheduleConfig::TimeFormat_24,
            'filters' => [
                [
                    'type' => SummitScheduleFilterElementConfig::Type_Date,
                    'is_enabled' => true,
                ],
                [
                    'type' => SummitScheduleFilterElementConfig::Type_Tags,
                    'is_enabled' => true,
                ],
                [
                    'type' => SummitScheduleFilterElementConfig::Type_EventTypes,
                    'is_enabled' => true,
                ]
            ],
            'pre_filters' => [
                [
                    'type' => SummitScheduleFilterElementConfig::Type_Tags,
                    'values' => ['tag1','tag2', 'tag3']
                ],
                [
                    'type' => SummitScheduleFilterElementConfig::Type_Company,
                    'values' => ['1', '2']
                ],
            ]
        ];

        $response = $this->action
        (
            "POST",
            "OAuth2SummitScheduleSettingsApiController@add",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $config = json_decode($content);
        $this->assertTrue($config->id > 0);
        $this->assertTrue($config->summit_id == self::$summit->getId());
        $this->assertTrue($config->key == 'my-schedule-config');


        $params = [
            'id' => self::$summit->getId(),
            'config_id' => $config->id,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action
        (
            "DELETE",
            "OAuth2SummitScheduleSettingsApiController@delete",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content =$response->getContent();
        $this->assertResponseStatus(204);
        $this->assertTrue(empty($content));
    }

    public function testGetAll(){
        $params = [
            'id' => self::$summit->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $data = [
            'key' => 'my-schedule-config',
            'is_default' => true,
            'is_enabled' => true,
            'is_my_schedule' => true,
            'only_events_with_attendee_access' => true,
            'hide_past_events_with_show_always_on_schedule' => true,
            'color_source' => SummitScheduleConfig::ColorSource_EventType,
            'time_format' => SummitScheduleConfig::TimeFormat_24,
            'filters' => [
                [
                    'type' => SummitScheduleFilterElementConfig::Type_Date,
                    'is_enabled' => true,
                ],
                [
                    'type' => SummitScheduleFilterElementConfig::Type_Tags,
                    'is_enabled' => true,
                ],
                [
                    'type' => SummitScheduleFilterElementConfig::Type_EventTypes,
                    'is_enabled' => true,
                ]
            ],
            'pre_filters' => [
                [
                    'type' => SummitScheduleFilterElementConfig::Type_Tags,
                    'values' => ['tag1','tag2', 'tag3']
                ],
                [
                    'type' => SummitScheduleFilterElementConfig::Type_Company,
                    'values' => ['1', '2']
                ],
            ]
        ];

        $response = $this->action
        (
            "POST",
            "OAuth2SummitScheduleSettingsApiController@add",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $config = json_decode($content);
        $this->assertTrue($config->id > 0);
        $this->assertTrue($config->summit_id == self::$summit->getId());
        $this->assertTrue($config->key == 'my-schedule-config');

        $params = [
            'id' => self::$summit->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $data = [
            'key' => 'my-schedule-config2',
            'is_default' => true,
            'is_enabled' => true,
            'is_my_schedule' => true,
            'only_events_with_attendee_access' => true,
            'hide_past_events_with_show_always_on_schedule' => false,
            'color_source' => SummitScheduleConfig::ColorSource_EventType,
            'time_format' => SummitScheduleConfig::TimeFormat_24,
            'filters' => [
                [
                    'type' => SummitScheduleFilterElementConfig::Type_Date,
                    'is_enabled' => true,
                ],
                [
                    'type' => SummitScheduleFilterElementConfig::Type_Tags,
                    'is_enabled' => true,
                ],
                [
                    'type' => SummitScheduleFilterElementConfig::Type_EventTypes,
                    'is_enabled' => true,
                ]
            ],
            'pre_filters' => [
                [
                    'type' => SummitScheduleFilterElementConfig::Type_Tags,
                    'values' => ['tag1','tag2', 'tag3']
                ],
                [
                    'type' => SummitScheduleFilterElementConfig::Type_Company,
                    'values' => ['1', '2']
                ],
            ]
        ];

        $response = $this->action
        (
            "POST",
            "OAuth2SummitScheduleSettingsApiController@add",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $config = json_decode($content);
        $this->assertTrue($config->id > 0);
        $this->assertTrue($config->summit_id == self::$summit->getId());
        $this->assertTrue($config->key == 'my-schedule-config2');

        $params = [
            'id' => self::$summit->getId(),
            'page'     => 1,
            'per_page' => 10,
            'filter'   => [
                'key=@my-schedule-config'
            ],
            'order'    => '+key'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitScheduleSettingsApiController@getAllBySummit",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $res = json_decode($content);
        $this->assertTrue(!is_null($res));
        $this->assertTrue($res->total > 0);
        return $config;
    }

    public function testGetAllSimple(){

        $params = [
            'id'       => self::$summit->getId(),
            'page'     => 1,
            'per_page' => 10,
            'filter'   => [
                'key=@my-schedule-config'
            ],
            'order'    => '+key'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitScheduleSettingsApiController@getAllBySummit",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $res = json_decode($content);
        $this->assertTrue(!is_null($res));
        $this->assertTrue($res->total > 0);
        return $res;
    }
}