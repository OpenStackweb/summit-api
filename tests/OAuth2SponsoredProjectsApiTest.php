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
use Illuminate\Support\Facades\Config;
use LaravelDoctrine\ORM\Facades\EntityManager;
use models\main\SponsoredProject;

/**
 * Class OAuth2SponsoredProjectsApiTest
 */
class OAuth2SponsoredProjectsApiTest extends ProtectedApiTestCase
{
    use InsertSummitTestData;

    protected function setUp(): void
    {
        parent::setUp();
        self::insertSummitTestData();

        // Configure assets disk to use local driver for tests (avoids OpenStack/Swift dependency)
        Config::set('filesystems.disks.assets', [
            'driver' => 'local',
            'root' => storage_path('app/testing'),
        ]);
    }

    protected function tearDown(): void
    {
        self::clearSummitTestData();
        parent::tearDown();
    }

    public function testAddSponsoredProject(){

        $should_show_on_nav_bar = false;
        $site_url = 'https://'.str_random(16).'_sponsored_project/';

        $data = [
            'name'                      => str_random(16).'_sponsored project',
            'description'               => str_random(16).'_sponsored project description',
            'should_show_on_nav_bar'    => $should_show_on_nav_bar,
            'site_url'                  => $site_url,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SponsoredProjectApiController@add",
            [],
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $sponsored_project = json_decode($content);
        $this->assertTrue(!is_null($sponsored_project));
        $this->assertTrue($sponsored_project->should_show_on_nav_bar == $should_show_on_nav_bar);
        $this->assertTrue($sponsored_project->site_url == $site_url);
        return $sponsored_project;
    }

    public function testUpdateSponsoredProject(){

        $project = $this->testAddSponsoredProject();

        $should_show_on_nav_bar = true;
        $site_url = 'https://'.str_random(16).'_sponsored_project/';

        $params = [
            'id' => $project->id,
        ];

        $data = [
            'should_show_on_nav_bar'    => $should_show_on_nav_bar,
            'site_url'                  => $site_url,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SponsoredProjectApiController@update",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $sponsored_project = json_decode($content);
        $this->assertTrue(!is_null($sponsored_project));
        $this->assertTrue($sponsored_project->should_show_on_nav_bar == $should_show_on_nav_bar);
        $this->assertTrue($sponsored_project->site_url == $site_url);
        return $sponsored_project;
    }

    public function testGetAll(){
        $this->testAddSponsoredProject();

        $params = [
            //AND FILTER
            'filter' => ['name=@sponsored project'],
            'order'  => '-id'
        ];

        $headers = array("HTTP_Authorization" => " Bearer " . $this->access_token);
        $response = $this->action(
            "GET",
            "OAuth2SponsoredProjectApiController@getAll",
            $params,
            array(),
            array(),
            array(),
            $headers
        );

        $content = $response->getContent();
        $sponsored_projects = json_decode($content);
        $this->assertTrue(!is_null($sponsored_projects));
        $this->assertResponseStatus(200);
    }

    public function testAddSponsorshipTypeAndGet(){

        // first create a project
        $project = $this->testAddSponsoredProject();

        $data = [
            'name' => str_random(16).' sponsorship type',
            'description' => str_random(16).' sponsorship type description',
            'is_active' => true,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SponsoredProjectApiController@addSponsorshipType",
            ['id' => $project->id],
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $sponsorship_type = json_decode($content);
        $this->assertTrue(!is_null($sponsorship_type));

        // get single sponsorship type
        $params = [
            'id' => $project->id,
            'sponsorship_type_id' => $sponsorship_type->id
        ];

        $response = $this->action(
            "GET",
            "OAuth2SponsoredProjectApiController@getSponsorshipType",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $fetched_type = json_decode($content);
        $this->assertTrue(!is_null($fetched_type));

        // get all sponsorship types for the project
        $params = [
            'id' => $project->id,
        ];

        $response = $this->action(
            "GET",
            "OAuth2SponsoredProjectApiController@getAllSponsorshipTypes",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $all_types = json_decode($content);
        $this->assertTrue(!is_null($all_types));
    }

    public function testUpdateSponsorshipType(){

        // first create a project and sponsorship type
        $project = $this->testAddSponsoredProject();

        $data_type = [
            'name' => str_random(16).' sponsorship type',
            'description' => str_random(16).' sponsorship type description',
            'is_active' => true,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SponsoredProjectApiController@addSponsorshipType",
            ['id' => $project->id],
            [],
            [],
            [],
            $headers,
            json_encode($data_type)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $sponsorship_type = json_decode($content);

        $params = [
            'id' => $project->id,
            'sponsorship_type_id' => $sponsorship_type->id
        ];

        $data = [
            'order' => 1
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SponsoredProjectApiController@updateSponsorshipType",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $this->assertResponseStatus(201);
    }

    public function testGetAllSponsorshipTypes(){
        // create a project with a sponsorship type first
        $project = $this->testAddSponsoredProject();

        $data_type = [
            'name' => str_random(16).' sponsorship type',
            'description' => str_random(16).' sponsorship type description',
            'is_active' => true,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SponsoredProjectApiController@addSponsorshipType",
            ['id' => $project->id],
            [],
            [],
            [],
            $headers,
            json_encode($data_type)
        );

        $this->assertResponseStatus(201);

        $params = [
            'id' => $project->id,
            'expand' => 'supporting_companies, supporting_companies.company'
        ];

        $headers = array("HTTP_Authorization" => " Bearer " . $this->access_token);
        $response = $this->action(
            "GET",
            "OAuth2SponsoredProjectApiController@getAllSponsorshipTypes",
            $params,
            array(),
            array(),
            array(),
            $headers
        );

        $content = $response->getContent();
        $page = json_decode($content);
        $this->assertTrue(!is_null($page));
        $this->assertResponseStatus(200);
    }

    public function testAddSupportingCompany(){

        // create project and sponsorship type
        $project = $this->testAddSponsoredProject();

        $data_type = [
            'name' => str_random(16).' sponsorship type',
            'description' => str_random(16).' sponsorship type description',
            'is_active' => true,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SponsoredProjectApiController@addSponsorshipType",
            ['id' => $project->id],
            [],
            [],
            [],
            $headers,
            json_encode($data_type)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $sponsorship_type = json_decode($content);

        // use a company from test data
        $company = self::$companies[0];

        $params = [
            'id' => $project->id,
            'sponsorship_type_id' => $sponsorship_type->id,
        ];

        $data = [
            'company_id' => $company->getId(),
            'order' => 1
        ];

        $response = $this->action(
            "POST",
            "OAuth2SponsoredProjectApiController@addSupportingCompanies",
            $params,
            array(),
            array(),
            array(),
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $supporting_company = json_decode($content);
        $this->assertTrue(!is_null($supporting_company));
    }

    public function testGetAllSupportingCompanies(){

        // create project, sponsorship type, and add supporting company
        $project = $this->testAddSponsoredProject();

        $data_type = [
            'name' => str_random(16).' sponsorship type',
            'description' => str_random(16).' sponsorship type description',
            'is_active' => true,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SponsoredProjectApiController@addSponsorshipType",
            ['id' => $project->id],
            [],
            [],
            [],
            $headers,
            json_encode($data_type)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $sponsorship_type = json_decode($content);

        // add a supporting company
        $company = self::$companies[0];

        $response = $this->action(
            "POST",
            "OAuth2SponsoredProjectApiController@addSupportingCompanies",
            [
                'id' => $project->id,
                'sponsorship_type_id' => $sponsorship_type->id,
            ],
            [],
            [],
            [],
            $headers,
            json_encode([
                'company_id' => $company->getId(),
                'order' => 1
            ])
        );

        $this->assertResponseStatus(201);

        // now get all supporting companies
        $params = [
            'id' => $project->id,
            'sponsorship_type_id' => $sponsorship_type->id,
            'expand' => 'company'
        ];

        $response = $this->action(
            "GET",
            "OAuth2SponsoredProjectApiController@getSupportingCompanies",
            $params,
            array(),
            array(),
            array(),
            $headers
        );

        $content = $response->getContent();
        $page = json_decode($content);
        $this->assertTrue(!is_null($page));
        $this->assertResponseStatus(200);
    }

    public function testAddSponsoredProjectLogo(){
        $project = $this->testAddSponsoredProject();

        $params = [
            'id' => $project->id,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"       => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SponsoredProjectApiController@addSponsoredProjectLogo",
            $params,
            [],
            [],
            [
                'file' => UploadedFile::fake()->image('logo.jpg'),
            ],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $file = json_decode($content);
        $this->assertTrue(!is_null($file));
    }

    public function testDeleteSponsoredProjectLogo(){
        $project = $this->testAddSponsoredProject();

        $params = [
            'id' => $project->id,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"       => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SponsoredProjectApiController@addSponsoredProjectLogo",
            $params,
            [],
            [],
            [
                'file' => UploadedFile::fake()->image('logo.jpg'),
            ],
            $headers
        );

        $this->assertResponseStatus(201);

        $response = $this->action(
            "DELETE",
            "OAuth2SponsoredProjectApiController@deleteSponsoredProjectLogo",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(204);
    }

    public function testAddSubproject(){
        $data = [
            'name' => str_random(16).'_sponsored project',
            'description' => str_random(16).'_sponsored project description',
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SponsoredProjectApiController@add",
            [],
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $sponsored_projects = json_decode($content);
        $this->assertTrue(!is_null($sponsored_projects));

        $data = [
            'parent_project_id' => $sponsored_projects->id,
            'name'              => str_random(16).'_sponsored subproject',
            'description'       => str_random(16).'_sponsored subproject description',
        ];

        $response = $this->action(
            "POST",
            "OAuth2SponsoredProjectApiController@add",
            [],
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $subproject = json_decode($content);
        $this->assertTrue(!is_null($subproject));
        return $subproject;
    }

    public function testUpdateSubproject(){
        $added_subproject_id = $this->testAddSubproject()->id;
        $sponsored_project_repository = EntityManager::getRepository(SponsoredProject::class);
        $subproject = $sponsored_project_repository->find($added_subproject_id);

        $parent_project = $subproject->getParentProject();

        $params = [
            'id' => $subproject->getId(),
        ];

        $data = [
            'parent_project_id' => $parent_project->getId(),
            'name'              => str_random(16).'_sponsored subproject updated',
            'description'       => str_random(16).'_sponsored subproject description updated',
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"       => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SponsoredProjectApiController@update",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );
        $this->assertResponseStatus(201);
    }

    public function testGetSubprojects(){
        $added_subproject_id = $this->testAddSubproject()->id;
        $sponsored_project_repository = EntityManager::getRepository(SponsoredProject::class);
        $subproject = $sponsored_project_repository->find($added_subproject_id);

        $params = [
            'id'     => $subproject->getParentProject()->getId(),
            'expand' => 'parent_project'
        ];

        $headers = array("HTTP_Authorization" => " Bearer " . $this->access_token);
        $response = $this->action(
            "GET",
            "OAuth2SponsoredProjectApiController@getSubprojects",
            $params,
            array(),
            array(),
            array(),
            $headers
        );

        $content = $response->getContent();
        $subprojects = json_decode($content);
        $this->assertResponseStatus(200);
        $this->assertNotEmpty($subprojects);
    }

    public function testDeleteSponsoredProjectWithSubprojects(){
        $added_subproject_id = $this->testAddSubproject()->id;
        $sponsored_project_repository = EntityManager::getRepository(SponsoredProject::class);
        $subproject = $sponsored_project_repository->find($added_subproject_id);

        $params = [
            'id' => $subproject->getParentProject()->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"       => "application/json"
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SponsoredProjectApiController@delete",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(204);
    }
}