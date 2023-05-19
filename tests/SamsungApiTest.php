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

use App\Services\Apis\Samsung\CheckUserRequest;
use App\Services\Apis\Samsung\DecryptedResponse;
use App\Services\Apis\Samsung\EmptyResponse;
use App\Services\Apis\Samsung\EncryptedPayload;
use App\Services\Apis\Samsung\ForumTypes;
use App\Services\Apis\Samsung\InvalidResponse;
use App\Services\Apis\Samsung\Regions;
use App\Utils\AES;
use models\summit\Summit;

/**
 * Class SamsungApiTest
 * @package Tests
 */
final class SamsungApiTest extends TestCase
{
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

        $response = new DecryptedResponse($summit->getApiFeedKey(), $data);

        $this->assertTrue($response == '{"type":"userCheck","userId":"123456789","forum":"SAFE™ Forum   ","region":"US"}');

    }

    public function testEmptyResponse(){

        $summit = new Summit();
        $summit->setExternalSummitId(ForumTypes::SAFE);
        $summit->setApiFeedKey("12345601234567890123456789012345");
        $data = "[]";
        $this->expectException(EmptyResponse::class);
        $response = new DecryptedResponse($summit->getApiFeedKey(), $data);
    }

    public function testNonEmptyResponse(){

        $summit = new Summit();
        $summit->setExternalSummitId(ForumTypes::SAFE);
        $summit->setApiFeedKey("12345601234567890123456789012345");

        $raw_data = '[{"type":"emailCheck","userId":"0CBl5NpPDg5kcFXzXhHkSx","email":"jpmaxman@samsung.com","forum":"SFF \u0026 SAFE™ Forum","session":"SFF \u0026 SAFE™ 2023,Tech Session I - Advanced Technology and Design Infrastructure","country":"United States","firstName":"JP","lastName":"Maxwell","companyName":"Samsung","companyType":"Samsung","jobFunction":"Architect","jobTitle":"Architect","groupId":"Attendee","additional":"V5riM96EwXCfocPdp3WGeq,ReR7Jyqm5LEWYgWaIpiqiC"}]';
        $enc = AES::encrypt($summit->getApiFeedKey(), $raw_data);
        $data = ['data' => $enc->getData()];
        $response = new DecryptedResponse($summit->getApiFeedKey(), json_encode($data));
        $payload = $response->getPayload();
        $this->assertTrue(!is_null($payload));
    }

    public function testInvalidResponse(){
        $summit = new Summit();
        $summit->setExternalSummitId(ForumTypes::SAFE);
        $summit->setApiFeedKey("12345601234567890123456789012345");
        $data = '{"data":"123456789"}';
        $this->expectException(InvalidResponse::class);
        $response = new DecryptedResponse($summit->getApiFeedKey(), $data);
    }
}