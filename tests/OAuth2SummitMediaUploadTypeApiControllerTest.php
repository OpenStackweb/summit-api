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
use LaravelDoctrine\ORM\Facades\EntityManager;
use models\summit\SummitMediaFileType;
use Doctrine\Persistence\ObjectRepository;
use LaravelDoctrine\ORM\Facades\Registry;
use models\utils\SilverstripeBaseModel;
/**
 * Class OAuth2SummitMediaUploadTypeApiControllerTest
 */
final class OAuth2SummitMediaUploadTypeApiControllerTest
    extends ProtectedApiTest
{

    use \InsertSummitTestData;
    /**
     * @var ObjectRepository
     */
    static $media_file_type_repository;

    protected function setUp():void
    {
        parent::setUp();
        self::$media_file_type_repository = EntityManager::getRepository(SummitMediaFileType::class);
        self::insertTestData();
        self::$em->persist(self::$summit);
        self::$em->flush();
    }

    protected function tearDown():void
    {
        self::clearTestData();
        parent::tearDown();
    }

    public function testAddGet(){

        $types = self::$media_file_type_repository->findAll();
        $params = [
             'id' => self::$summit->getId(),
            'expand' => 'type,presentation_types'
        ];

        $event_types = self::$summit->getEventTypes();

        $payload = [
            'name' => str_random(16).'media_upload_type',
            'type_id' => $types[0]->getId(),
            'description' => 'this is a description',
            'max_size' => 2048,
            'is_mandatory' => false,
            'private_storage_type' => \App\Models\Utils\IStorageTypesConstants::DropBox,
            'public_storage_type' => \App\Models\Utils\IStorageTypesConstants::Swift,
            'presentation_types' => [$event_types[0]->getId()]
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitMediaUploadTypeApiController@add",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($payload)
        );

        $content = $response->getContent();
        $response = json_decode($content, true);
        $this->assertResponseStatus(201);
        $this->assertTrue(isset($response['id']));

        $response = $this->action(
            "GET",
            "OAuth2SummitMediaUploadTypeApiController@getAllBySummit",
            [
                'id' => self::$summit->getId(),
            ],
            [],
            [],
            [],
            $headers,
            json_encode($payload)
        );

        $content = $response->getContent();
        $response = json_decode($content, true);

        $this->assertResponseStatus(200);

    }

    public function testAddAndDeleteCascade(){

        $types = self::$media_file_type_repository->findAll();
        $params = [
            'id' => self::$summit->getId(),
            'expand' => 'type,presentation_types'
        ];

        $event_types = self::$summit->getEventTypes();

        $payload = [
            'name' => str_random(16).'media_upload_type',
            'type_id' => $types[0]->getId(),
            'description' => 'this is a description',
            'max_size' => 2048,
            'is_mandatory' => false,
            'private_storage_type' => \App\Models\Utils\IStorageTypesConstants::DropBox,
            'public_storage_type' => \App\Models\Utils\IStorageTypesConstants::Swift,
            'presentation_types' => [$event_types[0]->getId()]
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitMediaUploadTypeApiController@add",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($payload)
        );

        $content = $response->getContent();
        $response = json_decode($content, true);
        $this->assertResponseStatus(201);
        $this->assertTrue(isset($response['id']));

        self::$em  = Registry::resetManager(SilverstripeBaseModel::EntityManager);
        $type = self::$media_file_type_repository->find($types[0]->getId());
        self::$em->remove($type);
        self::$em->flush();
    }

    public function testAddDelete(){
        $types = self::$media_file_type_repository->findAll();
        $params = [
            'id' => self::$summit->getId(),
            'expand' => 'type,presentation_types'
        ];

        $event_types = self::$summit->getEventTypes();

        $payload = [
            'name' => str_random(16).'media_upload_type',
            'type_id' => $types[0]->getId(),
            'description' => 'this is a description',
            'max_size' => 2048,
            'is_mandatory' => false,
            'private_storage_type' => \App\Models\Utils\IStorageTypesConstants::DropBox,
            'public_storage_type' => \App\Models\Utils\IStorageTypesConstants::Swift,
            'presentation_types' => [ $event_types[0]->getId() ]
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitMediaUploadTypeApiController@add",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($payload)
        );

        $content = $response->getContent();
        $response = json_decode($content, true);
        $this->assertResponseStatus(201);
        $this->assertTrue(isset($response['id']));


        $response = $this->action(
            "DELETE",
            "OAuth2SummitMediaUploadTypeApiController@delete",
            [
                'id' => self::$summit->getId(),
                'type_id' => intval($response['id'])
            ],
            [],
            [],
            [],
            $headers,
            json_encode($payload)
        );
        $this->assertResponseStatus(204);
    }

    public function testAddAddPresentationType(){

        $types = self::$media_file_type_repository->findAll();
        $params = [
            'id' => self::$summit->getId(),
            'expand' => 'type,presentation_types'
        ];

        $event_types = self::$summit->getEventTypes();

        $payload = [
            'name' => str_random(16).'media_upload_type',
            'type_id' => $types[0]->getId(),
            'description' => 'this is a description',
            'max_size' => 2048,
            'is_mandatory' => false,
            'private_storage_type' => \App\Models\Utils\IStorageTypesConstants::DropBox,
            'public_storage_type' => \App\Models\Utils\IStorageTypesConstants::Swift,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitMediaUploadTypeApiController@add",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($payload)
        );

        $content = $response->getContent();
        $response = json_decode($content, true);
        $this->assertResponseStatus(201);
        $this->assertTrue(isset($response['id']));

        $response = $this->action(
            "PUT",
            "OAuth2SummitMediaUploadTypeApiController@addToPresentationType",
            [
                'id' => self::$summit->getId(),
                'type_id' => intval($response['id']),
                'presentation_type_id' => $event_types[0]->getId()
            ],
            [],
            [],
            [],
            $headers,
            json_encode($payload)
        );

        $content = $response->getContent();
        $response = json_decode($content, true);
        $this->assertResponseStatus(201);
    }
}