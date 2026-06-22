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

use App\Jobs\FileProcessingJob;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use models\main\Company;

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
        Config::set('file_upload.storage_driver', 'local');
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
        Config::set('file_upload.storage_driver', 'local');
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

    // -------------------------------------------------------------------------
    // addCompany - logo dispatch path
    // -------------------------------------------------------------------------

    /**
     * When addCompany receives a logo file_dto payload, a FileProcessingJob must
     * be dispatched for the 'logo' member with the correct entity class.
     */
    public function testAddCompanyWithLogoPayloadDispatchesFileProcessingJob(): void
    {
        Config::set('file_upload.storage_driver', 'local');
        Storage::fake('local');
        Bus::fake();

        $fakeFile   = UploadedFile::fake()->image('logo.png');
        $content    = file_get_contents($fakeFile->getRealPath());
        $remotePath = 'companies/tmp/logo.png';
        Storage::disk('local')->put($remotePath, $content);

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"       => "application/json",
        ];

        $data = [
            'name'        => str_random(16) . '_company',
            'description' => str_random(16) . '_description',
            'logo'        => [
                'filepath'  => $remotePath,
                'filename'  => 'logo.png',
                'md5'       => md5($content),
                'size'      => strlen($content),
                'mime_type' => 'image/png',
            ],
        ];

        $this->action("POST", "OAuth2CompaniesApiController@add", [], [], [], [], $headers, json_encode($data));

        $this->assertResponseStatus(201);

        Bus::assertDispatched(FileProcessingJob::class, function (FileProcessingJob $job) {
            return $job->fileInfoDTO->owner_member_name  === 'logo'
                && $job->fileInfoDTO->owner_entity_class === Company::class;
        });
    }

    /**
     * When addCompany receives both logo and big_logo file_dto payloads, two
     * separate FileProcessingJob instances must be dispatched - one per member.
     */
    public function testAddCompanyWithBothLogosDispatchesTwoFileProcessingJobs(): void
    {
        Config::set('file_upload.storage_driver', 'local');
        Storage::fake('local');
        Bus::fake();

        $content = file_get_contents(UploadedFile::fake()->image('logo.png')->getRealPath());
        Storage::disk('local')->put('companies/tmp/logo.png',     $content);
        Storage::disk('local')->put('companies/tmp/big_logo.png', $content);

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"       => "application/json",
        ];

        $data = [
            'name'        => str_random(16) . '_company',
            'description' => str_random(16) . '_description',
            'logo' => [
                'filepath'  => 'companies/tmp/logo.png',
                'filename'  => 'logo.png',
                'md5'       => md5($content),
                'size'      => strlen($content),
                'mime_type' => 'image/png',
            ],
            'big_logo' => [
                'filepath'  => 'companies/tmp/big_logo.png',
                'filename'  => 'big_logo.png',
                'md5'       => md5($content),
                'size'      => strlen($content),
                'mime_type' => 'image/png',
            ],
        ];

        $this->action("POST", "OAuth2CompaniesApiController@add", [], [], [], [], $headers, json_encode($data));

        $this->assertResponseStatus(201);

        Bus::assertDispatched(FileProcessingJob::class, function (FileProcessingJob $job) {
            return $job->fileInfoDTO->owner_member_name === 'logo';
        });
        Bus::assertDispatched(FileProcessingJob::class, function (FileProcessingJob $job) {
            return $job->fileInfoDTO->owner_member_name === 'big_logo';
        });
        Bus::assertDispatchedTimes(FileProcessingJob::class, 2);
    }

    /**
     * Documents the known partial-commit behaviour: when addCompany's logo
     * extension validation fails (runs after the DB transaction commits),
     * the company is persisted but the caller receives a 412 error.
     */
    public function testAddCompanyWithInvalidLogoExtensionCommitsCompanyDespiteError(): void
    {
        Config::set('file_upload.storage_driver', 'local');
        Storage::fake('local');

        // .bmp is not in Company::LogoAllowedExtensions
        Storage::disk('local')->put('companies/tmp/logo.bmp', 'fake-bmp-content');

        $companyName = str_random(16) . '_company';

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"       => "application/json",
        ];

        $data = [
            'name'        => $companyName,
            'description' => str_random(16) . '_description',
            'logo'        => [
                'filepath'  => 'companies/tmp/logo.bmp',
                'filename'  => 'logo.bmp',
                'md5'       => md5('fake-bmp-content'),
                'size'      => strlen('fake-bmp-content'),
                'mime_type' => 'image/bmp',
            ],
        ];

        $this->action("POST", "OAuth2CompaniesApiController@add", [], [], [], [], $headers, json_encode($data));

        // Extension validation throws after the company transaction commits -> 412
        $this->assertResponseStatus(412);

        // The company was already persisted: a second attempt with the same name
        // must fail with 412 "Company X already exists".
        $this->action("POST", "OAuth2CompaniesApiController@add", [], [], [], [], $headers,
            json_encode(['name' => $companyName, 'description' => 'retry']));
        $this->assertResponseStatus(412);
        $this->assertStringContainsString('already exists', $this->response->getContent());
    }

}