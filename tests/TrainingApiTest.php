<?php namespace Tests;
/**
 * Copyright 2026 OpenStack Foundation
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
 * Class TrainingApiTest
 * @package Tests
 */
final class TrainingApiTest extends BrowserKitTestCase
{
    public function testGetAllTrainings()
    {
        $params = [
            'page'     => 1,
            'per_page' => 100,
        ];

        $response = $this->action(
            "GET",
            "TrainingApiController@getAll",
            $params,
            [],
            [],
            [],
            []
        );

        $content   = $response->getContent();
        $trainings = json_decode($content);
        $this->assertTrue(!is_null($trainings));
        $this->assertResponseStatus(200);
    }

    public function testGetAllTrainingsWithExpand()
    {
        $params = [
            'page'     => 1,
            'per_page' => 100,
            'expand'   => 'company,courses',
        ];

        $response = $this->action(
            "GET",
            "TrainingApiController@getAll",
            $params,
            [],
            [],
            [],
            []
        );

        $content   = $response->getContent();
        $trainings = json_decode($content);
        $this->assertTrue(!is_null($trainings));
        $this->assertResponseStatus(200);
    }

    public function testGetAllTrainingsWithFilter()
    {
        $params = [
            'filter' => 'name@@test',
            'order'  => '+name',
        ];

        $response = $this->action(
            "GET",
            "TrainingApiController@getAll",
            $params,
            [],
            [],
            [],
            []
        );

        $content   = $response->getContent();
        $trainings = json_decode($content);
        $this->assertTrue(!is_null($trainings));
        $this->assertResponseStatus(200);
    }
}
