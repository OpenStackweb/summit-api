<?php namespace Tests;
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

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class OAuth2CompaniesApiTest extends ProtectedApiTestCase
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

    // -------------------------------------------------------------------------
    // Logo - dual flow (multipart + JSON payload)
    // -------------------------------------------------------------------------

    public function testAddCompanyLogoViaMultipartFile(): void
    {
        Storage::fake('public');
        Storage::fake('assets');

        $company = $this->testAddCompany();

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"       => "multipart/form-data",
        ];

        $response = $this->action(
            "POST",
            "OAuth2CompaniesApiController@addCompanyLogo",
            ['id' => $company->id],
            [],
            [],
            ['file' => UploadedFile::fake()->image('logo.png')],
            $headers
        );

        $this->assertResponseStatus(201);
        $logo = json_decode($response->getContent());
        $this->assertNotNull($logo);
    }

    public function testAddCompanyLogoViaJsonPayload(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        Storage::fake('assets');

        $company = $this->testAddCompany();

        $fakeFile    = UploadedFile::fake()->image('logo.png');
        $content     = file_get_contents($fakeFile->getRealPath());
        $remotePath  = 'companies/' . $company->id . '/tmp/logo.png';
        Storage::disk('local')->put($remotePath, $content);

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"       => "application/json",
        ];

        $response = $this->action(
            "POST",
            "OAuth2CompaniesApiController@addCompanyLogo",
            ['id' => $company->id],
            [],
            [],
            [],
            $headers,
            json_encode([
                'filepath'   => $remotePath,
                'filename'   => 'logo.png',
                'md5'        => md5($content),
                'size'       => strlen($content),
                'mime_type'  => 'image/png',
            ])
        );

        $this->assertResponseStatus(201);
        $logo = json_decode($response->getContent());
        $this->assertNotNull($logo);
    }

    public function testAddCompanyLogoJsonPayloadReturnsPreconditionFailedOnMissingFields(): void
    {
        $company = $this->testAddCompany();

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"       => "application/json",
        ];

        // No file in request, and JSON body is missing all required fields
        $response = $this->action(
            "POST",
            "OAuth2CompaniesApiController@addCompanyLogo",
            ['id' => $company->id],
            [],
            [],
            [],
            $headers,
            json_encode(['mime_type' => 'image/png'])
        );

        $this->assertResponseStatus(412);
    }

    // -------------------------------------------------------------------------
    // Big logo - dual flow (multipart + JSON payload)
    // -------------------------------------------------------------------------

    public function testAddCompanyBigLogoViaMultipartFile(): void
    {
        Storage::fake('public');
        Storage::fake('assets');

        $company = $this->testAddCompany();

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"       => "multipart/form-data",
        ];

        $response = $this->action(
            "POST",
            "OAuth2CompaniesApiController@addCompanyBigLogo",
            ['id' => $company->id],
            [],
            [],
            ['file' => UploadedFile::fake()->image('big_logo.png')],
            $headers
        );

        $this->assertResponseStatus(201);
        $logo = json_decode($response->getContent());
        $this->assertNotNull($logo);
    }

    public function testAddCompanyBigLogoViaJsonPayload(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        Storage::fake('assets');

        $company = $this->testAddCompany();

        $fakeFile    = UploadedFile::fake()->image('big_logo.png');
        $content     = file_get_contents($fakeFile->getRealPath());
        $remotePath  = 'companies/' . $company->id . '/tmp/big_logo.png';
        Storage::disk('local')->put($remotePath, $content);

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"       => "application/json",
        ];

        $response = $this->action(
            "POST",
            "OAuth2CompaniesApiController@addCompanyBigLogo",
            ['id' => $company->id],
            [],
            [],
            [],
            $headers,
            json_encode([
                'filepath'   => $remotePath,
                'filename'   => 'big_logo.png',
                'md5'        => md5($content),
                'size'       => strlen($content),
                'mime_type'  => 'image/png',
            ])
        );

        $this->assertResponseStatus(201);
        $logo = json_decode($response->getContent());
        $this->assertNotNull($logo);
    }

    // Kept for backward compatibility with any @depends on this method name
    public function testAddCompanyBigLogo(): void
    {
        $this->testAddCompanyBigLogoViaMultipartFile();
    }

}