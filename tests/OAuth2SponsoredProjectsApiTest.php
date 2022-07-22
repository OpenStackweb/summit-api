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
use LaravelDoctrine\ORM\Facades\EntityManager;
use models\main\SponsoredProject;

/**
 * Class OAuth2SponsoredProjectsApiTest
 */
class OAuth2SponsoredProjectsApiTest extends ProtectedApiTest
{
    public function testAddSponsoredProject(){

        $nav_bar_title = str_random(16).'_sponsored project title';
        $should_show_on_nav_bar = false;
        $learn_more_text = str_random(16).'_sponsored project learn more text';
        $learn_more_link = 'https://'.str_random(16).'_sponsored_project/learn_more_text.html';
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

        $nav_bar_title = str_random(16).'_sponsored project title';
        $should_show_on_nav_bar = true;
        $learn_more_text = str_random(16).'_sponsored project learn more text';
        $learn_more_link = 'https://'.str_random(16).'_sponsored_project/learn_more_text.html';
        $site_url = 'https://'.str_random(16).'_sponsored_project/';

        $params = [
            'id' => 1,
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

        $params = [
            'id' => $sponsored_projects->id,
        ];

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
            $params,
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

        $params = [
            'id' => 1,
            'sponsorship_type_id' => $sponsorship_type->id
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SponsoredProjectApiController@getSponsorshipType",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $sponsorship_type = json_decode($content);
        $this->assertTrue(!is_null($sponsorship_type));

        $params = [
            'id' => 1,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SponsoredProjectApiController@getAllSponsorshipTypes",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $sponsorship_type = json_decode($content);
        $this->assertTrue(!is_null($sponsorship_type));
    }

    public function testUpdateSponsorshipType(){

        $params = [
            'id' => 1,
            'sponsorship_type_id' => 6
        ];

        $data = [
            'order' => 1
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
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

        $content = $response->getContent();
        $this->assertResponseStatus(204);
        $sponsorship_type = json_decode($content);
        $this->assertTrue(!is_null($sponsorship_type));
        return $sponsorship_type;
    }

    public function testGetAllSponsorshipTypes(){
        $params = [
           'id' => 'kata-containers',
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

        $params = [
            'id' => 1,
            'sponsorship_type_id' => 1,
            'company_id' => 12,
        ];

        $data = [
            'order' => 2
        ];

        $headers = array("HTTP_Authorization" => " Bearer " . $this->access_token);
        $response = $this->action(
            "PUT",
            "OAuth2SponsoredProjectApiController@addSupportingCompanies",
            $params,
            array(),
            array(),
            array(),
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $company = json_decode($content);
        $this->assertTrue(!is_null($company));
        $this->assertResponseStatus(200);
    }

    public function testGetAllSupportingCompanies(){

        $params = [
            'id' => 'kata-containers',
            'sponsorship_type_id' => 'platinum-members',
            'expand' => 'company'
        ];

        $headers = array("HTTP_Authorization" => " Bearer " . $this->access_token);
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
        $params = [
            'id' => 1,
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
        $params = [
            'id' => 1,
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

        $params = [
            'id' => $subproject->getId(),
        ];

        $data = [
            'parent_project_id' => 1,
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
            'id' => $subproject->getParentProject()->getId(),
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