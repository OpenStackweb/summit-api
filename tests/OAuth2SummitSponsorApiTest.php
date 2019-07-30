<?php
/**
 * Copyright 2019 OpenStack Foundation
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
use LaravelDoctrine\ORM\Facades\EntityManager;
/**
 * Class OAuth2SummitSponsorApiTest
 */
final class OAuth2SummitSponsorApiTest extends ProtectedApiTest
{
    /**
     * @param int $summit_id
     * @param int $company_id
     * @return mixed
     */
    public function testAddSponsor($summit_id =27, $company_id = 1){
        $params = [
            'id' => $summit_id
        ];

        $company_repository =  EntityManager::getRepository(\models\main\Company::class);
        $sponsorship_type_repository = EntityManager::getRepository(\models\summit\SponsorshipType::class);
        $company = $company_repository->find($company_id);
        $sponsorship_type = $sponsorship_type_repository->find(1);

        $data = [
            'company_id'  => $company->getId(),
            'sponsorship_id' => $sponsorship_type->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitSponsorApiController@add",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $sponsor = json_decode($content);
        $this->assertTrue(!is_null($sponsor));
        return $sponsor;
    }

    public function testGetAllSponsorsBySummit($summit_id =27){
        $params = [
            'id' => $summit_id,
            'filter'=> 'company_name=@Rack',
            'expand' => 'company,sponsorship'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSponsorApiController@getAllBySummit",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $page = json_decode($content);
        $this->assertTrue(!is_null($page));
        return $page;
    }

    public function testDeleteSponsor($summit_id = 27){
        $sponsor = $this->testAddSponsor($summit_id, 10);
        $params = [
            'id' => $summit_id,
            'sponsor_id'=> $sponsor->id
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitSponsorApiController@delete",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(204);
        $this->assertTrue(empty($content));
    }

    /**
     * @param int $summit_id
     * @param int $sponsor_id
     * @param int $member_id
     * @return mixed
     */
    public function testAddSponsorUserMember($summit_id =27, $sponsor_id = 750, $member_id=1){
        $params = [
            'id'         => $summit_id,
            'sponsor_id' => $sponsor_id,
            'member_id'  => $member_id
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitSponsorApiController@addSponsorUser",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $sponsor = json_decode($content);
        $this->assertTrue(!is_null($sponsor));
        return $sponsor;
    }
}