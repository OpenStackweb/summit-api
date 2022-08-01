<?php namespace Tests;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\App;
use services\model\ISummitService;

/**
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

final class OAuth2SummitRegistrationCompaniesApiTest extends ProtectedApiTest
{
    use InsertSummitTestData;

    protected function setUp(): void
    {
        parent::setUp();
        self::insertTestData();
    }

    protected function tearDown(): void
    {
        self::clearTestData();
        parent::tearDown();
    }

    public function testGetCurrentSummitCompanies()
    {
        $summitId = self::$summit->getId();
        $companyId = 1;

        $service = App::make(ISummitService::class);
        $company = $service->addCompany($summitId, $companyId);

        $service->addCompany($summitId, 2);
        $service->addCompany(self::$summit2->getId(), 3);

        $params = [
            'id' => $summitId,
            'page' => 1,
            'per_page' => 15,
            'filter' => ['name=@' . $company->getName()],
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"       => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitRegistrationCompaniesApiController@getAllBySummit",
            $params,
            array(),
            array(),
            array(),
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $data = json_decode($content);
        $this->assertTrue(!is_null($data));
    }

    public function testAddCompanyToSummit($summitId = null, $companyId = 1)
    {
        $params = array(
            'id' => $summitId ?? self::$summit->getId(),
            'company_id' => $companyId,
        );

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"       => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitRegistrationCompaniesApiController@add",
            $params,
            array(),
            array(),
            array(),
            $headers
        );
        $content = $response->getContent();
        $this->assertResponseStatus(201);
    }

    public function testRemoveCompanyFromSummit($companyId = 1)
    {
        $summitId = $summitId ?? self::$summit->getId();

        $service = App::make(ISummitService::class);
        $service->addCompany($summitId, $companyId);

        $params = array(
            'id' => $summitId,
            'company_id' => $companyId,
        );

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"       => "application/json"
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitRegistrationCompaniesApiController@delete",
            $params,
            array(),
            array(),
            array(),
            $headers
        );
        $content = $response->getContent();
        $this->assertResponseStatus(204);
    }

    public function testIngestRegistrationCompanies()
    {
        $csv_content = <<<CSV
name,
Testco,
Testco 2,
Testco 3,
Testco 4,
CSV;
        $path = "/tmp/registration_companies.csv";

        file_put_contents($path, $csv_content);

        $file = new UploadedFile($path, "registration_companies.csv", 'text/csv', null, true);

        $params = [
            'id' => self::$summit->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitRegistrationCompaniesApiController@import",
            $params,
            [],
            [],
            [
                'file' => $file
            ],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
    }
}