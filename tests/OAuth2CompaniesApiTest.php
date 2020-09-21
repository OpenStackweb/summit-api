<?php
/**
 * Copyright 2017 OpenStack Foundation
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

class OAuth2CompaniesApiTest extends ProtectedApiTest
{

    public function testGetCompanies()
    {

        $params = [
            //AND FILTER
            'filter' => ['name=@Dell\,'],
            'order'  => '-id'
        ];

        $headers = array("HTTP_Authorization" => " Bearer " . $this->access_token);
        $response = $this->action(
            "GET",
            "OAuth2CompaniesApiController@getAllCompanies",
            $params,
            array(),
            array(),
            array(),
            $headers
        );

        $content = $response->getContent();
        $companies = json_decode($content);
        $this->assertTrue(!is_null($companies));
        $this->assertResponseStatus(200);
    }

    public function testAddCompany(){
        $data = [
            'name' => str_random(16).'_company',
            'description' => str_random(16).'_description',
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2CompaniesApiController@add",
            [],
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $company = json_decode($content);
        $this->assertTrue(!is_null($company));
        return $company;
    }

}