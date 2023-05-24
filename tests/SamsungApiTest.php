<?php namespace Tests;
/*
 * Copyright 2023 OpenStack Foundation
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

use App\Models\Foundation\Summit\Registration\ISummitExternalRegistrationFeedType;
use App\Services\Apis\Samsung\CheckUserRequest;
use App\Services\Apis\Samsung\DecryptedListResponse;
use App\Services\Apis\Samsung\DecryptedSingleResponse;
use App\Services\Apis\Samsung\EmptyResponse;
use App\Services\Apis\Samsung\EncryptedPayload;
use App\Services\Apis\Samsung\ForumTypes;
use App\Services\Apis\Samsung\InvalidResponse;
use App\Services\Apis\Samsung\ISamsungRegistrationAPI;
use App\Services\Apis\Samsung\Regions;
use App\Services\Apis\Samsung\RequestTypes;
use App\Utils\AES;
use GuzzleHttp\ClientInterface;
use Illuminate\Support\Facades\App;
use models\summit\Summit;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Mockery;
/**
 * Class SamsungApiTest
 * @package Tests
 */
final class SamsungApiTest extends TestCase
{

    public function tearDown():void
    {
        Mockery::close();
    }

    public function testUserCheckRequest(){

        $summit = new Summit();
        $summit->setExternalSummitId(ForumTypes::SAFE);
        $userId = '123456789';
        $region = Regions::US;
        $request = new CheckUserRequest($userId, $summit->getExternalSummitId(), $region);
        $this->assertTrue($request == '{"type":"userCheck","userId":"123456789","forum":"SAFE™ Forum","region":"US"}');
    }

    public function testUserCheckRequestEnc(){

        $summit = new Summit();
        $summit->setExternalSummitId(ForumTypes::SAFE);
        $summit->setApiFeedKey("12345601234567890123456789012345");

        $userId = '123456789';
        $region = Regions::US;
        $request = new CheckUserRequest($userId, $summit->getExternalSummitId(), $region);
        $this->assertTrue($request == '{"type":"userCheck","userId":"123456789","forum":"SAFE™ Forum","region":"US"}');

        $data = (string)new EncryptedPayload($summit->getApiFeedKey(), $request);
        $this->assertTrue(!empty($data));

        $response = new DecryptedSingleResponse($summit->getApiFeedKey(), $data, $summit->getExternalSummitId());

        $this->assertTrue($response == '{"type":"userCheck","userId":"123456789","forum":"SAFE™ Forum","region":"US"}');

    }

    public function testEmptyResponse(){

        $summit = new Summit();
        $summit->setExternalSummitId(ForumTypes::SAFE);
        $summit->setApiFeedKey("12345601234567890123456789012345");
        $data = "[]";
        $this->expectException(EmptyResponse::class);
        $response = new DecryptedSingleResponse($summit->getApiFeedKey(), $data, $summit->getExternalSummitId());
    }

    public function testNonEmptyResponse(){

        $summit = new Summit();
        $summit->setExternalSummitId(ForumTypes::SAFE);
        $summit->setApiFeedKey("12345601234567890123456789012345");

        $raw_data = '[{"type":"emailCheck","userId":"0CBl5NpPDg5kcFXzXhHkSx","email":"jpmaxman@samsung.com","forum":"SFF \u0026 SAFE™ Forum","session":"SFF \u0026 SAFE™ 2023,Tech Session I - Advanced Technology and Design Infrastructure","country":"United States","firstName":"JP","lastName":"Maxwell","companyName":"Samsung","companyType":"Samsung","jobFunction":"Architect","jobTitle":"Architect","groupId":"Attendee","additional":"V5riM96EwXCfocPdp3WGeq,ReR7Jyqm5LEWYgWaIpiqiC"}]';
        $enc = AES::encrypt($summit->getApiFeedKey(), $raw_data);
        $data = ['data' => $enc->getData()];
        $response = new DecryptedSingleResponse($summit->getApiFeedKey(), json_encode($data), $summit->getExternalSummitId());
        $payload = $response->getPayload();
        $this->assertTrue(!is_null($payload));
    }

    public function testInvalidResponse(){
        $summit = new Summit();
        $summit->setExternalSummitId(ForumTypes::SAFE);
        $summit->setApiFeedKey("12345601234567890123456789012345");
        $data = '{"data":"123456789"}';
        $this->expectException(InvalidResponse::class);
        $response = new DecryptedSingleResponse($summit->getApiFeedKey(), $data, $summit->getExternalSummitId());
    }

    public function testList(){

        $summit = new Summit();
        $summit->setExternalSummitId(ForumTypes::SAFE);
        $summit->setApiFeedKey("12345601234567890123456789012345");

        $raw_data = <<<JSON
[{"type":"emailCheck","userId":"0CBl5NpPDg5kcFXzXhHkSx","email":"jpmaxman@samsung.com","forum":"SFF \u0026 SAFE™ Forum","session":"SFF \u0026 SAFE™ 2023,Tech Session I - Advanced Technology and Design Infrastructure","country":"United States","firstName":"JP","lastName":"Maxwell","companyName":"Samsung","companyType":"Samsung","jobFunction":"Architect","jobTitle":"Architect","groupId":"Attendee","additional":"V5riM96EwXCfocPdp3WGeq,ReR7Jyqm5LEWYgWaIpiqiC"},
{"type":"emailCheck","userId":"0CBl5NpPDg5kcFXzXhHkSx","email":"jpmaxman@samsung.com","forum":"SFF \u0026 SAFE™ Forum","session":"SFF \u0026 SAFE™ 2023,Tech Session I - Advanced Technology and Design Infrastructure","country":"United States","firstName":"JP","lastName":"Maxwell","companyName":"Samsung","companyType":"Samsung","jobFunction":"Architect","jobTitle":"Architect","groupId":"Attendee","additional":"V5riM96EwXCfocPdp3WGeq,ReR7Jyqm5LEWYgWaIpiqiC"}]
JSON;
        $enc = AES::encrypt($summit->getApiFeedKey(), $raw_data);
        $data = ['data' => $enc->getData()];

        $response = new DecryptedListResponse($summit->getApiFeedKey(), json_encode($data), $summit->getExternalSummitId());

        foreach ($response as $index => $external_attendee) {
            $this->assertTrue(!is_null($external_attendee));
        }
    }

    public function testSamsungAPICheckkUser(){

        $summit = new Summit();
        $summit->setExternalSummitId(ForumTypes::SAFE);
        $summit->setExternalRegistrationFeedApiKey("12345601234567890123456789012345");
        $summit->setExternalRegistrationFeedType(ISummitExternalRegistrationFeedType::Samsung);

        $jsonResponse = sprintf(<<<JSON
[{"type":"%s","userId":"0CBl5NpPDg5kcFXzXhHkSx","email":"test@samsung.com","forum":"SFF \u0026 SAFE™ Forum","session":"SFF \u0026 SAFE™ 2023,Tech Session I - Advanced Technology and Design Infrastructure","country":"United States","firstName":"JP","lastName":"Maxwell","companyName":"Samsung","companyType":"Samsung","jobFunction":"Architect","jobTitle":"Architect","groupId":"Attendee","additional":"V5riM96EwXCfocPdp3WGeq,ReR7Jyqm5LEWYgWaIpiqiC"}]
JSON, RequestTypes::UserCheck);

        $enc = AES::encrypt($summit->getExternalRegistrationFeedApiKey(), $jsonResponse);

        $dict_data = ['data' => $enc->getData()];

        // mock events response
        $streamMock = Mockery::mock(StreamInterface::class);
        $streamMock->shouldReceive('getContents')->andReturn(json_encode($dict_data));

        $responseMock = Mockery::mock(ResponseInterface::class);
        $responseMock->shouldReceive('getStatusCode')->andReturn(200);
        $responseMock->shouldReceive('getBody')->andReturn($streamMock);

        $clientMock = Mockery::mock(ClientInterface::class);
        $clientMock->shouldReceive('request')->withAnyArgs()->andReturn($responseMock);

        // replace implementation with mock on IOC containter
        App::singleton(ClientInterface::class, function() use ($clientMock){
            return $clientMock;
        });

        $api = App::make(ISamsungRegistrationAPI::class);

        $res = $api->checkUser($summit, "0CBl5NpPDg5kcFXzXhHkSx");

        $this->assertNotEmpty($res);
        $this->assertTrue(isset($res['id']) && $res["id"] === "0CBl5NpPDg5kcFXzXhHkSx");
    }
}