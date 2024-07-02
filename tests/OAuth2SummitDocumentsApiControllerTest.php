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
use App\Http\Utils\IFileUploader;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\App;
use models\summit\SummitDocument;
/**
 * Class OAuth2SummitDocumentsApiControllerTest
 */
class OAuth2SummitDocumentsApiControllerTest extends ProtectedApiTestCase
{
    use InsertSummitTestData;

    protected function setUp():void
    {
        parent::setUp();
        self::insertSummitTestData();
        self::$summit->seedDefaultEmailFlowEvents();

        $fileUploader = App::make(IFileUploader::class);

        $file1 = $fileUploader->build
        (
            UploadedFile::fake()->image('slide.pdf'),
            sprintf('summits/%s/documents', self::$summit->getId()),
            false
        );

        $doc1 = new SummitDocument();
        $doc1->setName('doc 1');
        $doc1->setLabel("doc 1");
        $doc1->setDescription("this is the doc 1");
        $doc1->setFile($file1);

        self::$summit->addSummitDocument($doc1);

        $file2 = $fileUploader->build
        (
            UploadedFile::fake()->image('slide2.pdf'),
            sprintf('summits/%s/documents', self::$summit->getId()),
            false
        );

        $doc2 = new SummitDocument();
        $doc2->setName('doc 2');
        $doc2->setLabel("doc 2");
        $doc2->setDescription("this is the doc 2");
        $doc2->setFile($file2);
        $doc2->addEventType(self::$summit->getEventTypes()[0]);

        self::$summit->addSummitDocument($doc2);

        self::$em->persist(self::$summit);
        self::$em->flush();
    }

    protected function tearDown():void
    {
        self::clearSummitTestData();
        parent::tearDown();
    }

    public function testGetAllSummitDocuments(){
        $params = [
            'id'       => self::$summit->getId(),
            'page'     => 1,
            'per_page' => 10,
            'order'    => '-id',
            'filter'   => 'event_type=@'.self::$summit->getEventTypes()[0]->getType()
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitDocumentsApiController@getAllBySummit",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $documents = json_decode($content);
        $this->assertTrue(!is_null($documents));
        $this->assertTrue($documents->total >= 1);
        return $documents;
    }

    public function testAddSummitDocument(){
        $params = [
            'id' => self::$summit->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $payload = [
            'name' => 'doc3',
            'label' => 'doc3',
            'event_types' => [self::$summit->getEventTypes()[0]->getId()]
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitDocumentsApiController@add",
            $params,
            [],
            [],
            [
                'file' => UploadedFile::fake()->image('slide1.pdf'),
            ],
            $headers,
            json_encode($payload)
        );

        $content = $response->getContent();
        $document = json_decode($content);
        $this->assertResponseStatus(201);
    }

    public function testAddSummitDocumentFail412(){
        $params = [
            'id' => self::$summit->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $payload = [
            'name' => 'doc 1',
            'label' => 'doc 1',
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitDocumentsApiController@add",
            $params,
            [],
            [],
            [
                'file' => UploadedFile::fake()->image('slide1.pdf'),
            ],
            $headers,
            json_encode($payload)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(412);
    }

    public function testUpdateSummitDocument(){
        $document = self::$summit->getSummitDocuments()[0];
        $params = [
            'id'       => self::$summit->getId(),
            'document_id' => $document->getId()
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $payload = [
            'event_types' => [self::$summit->getEventTypes()[0]->getId()],
            'label' => 'doc 4'
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitDocumentsApiController@update",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($payload)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $document = json_decode($content);
        $this->assertTrue($document->label == "doc 4");
    }

    public function testDeleteDocument(){
        $document = self::$summit->getSummitDocuments()[0];
        $params = [
            'id'       => self::$summit->getId(),
            'document_id' => $document->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitDocumentsApiController@delete",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(204);
    }

    public function testAddEventType2SummitDocument(){
        $document = self::$summit->getSummitDocuments()[0];
        $event_type = self::$summit->getEventTypes()[0];
        $params = [
            'id'       => self::$summit->getId(),
            'document_id' => $document->getId(),
            'event_type_id' => $event_type->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitDocumentsApiController@addEventType",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
    }

    public function testAddDocument2EventType(){
        $document = self::$summit->getSummitDocuments()[0];
        $event_type = self::$summit->getEventTypes()[0];
        $params = [
            'id'       => self::$summit->getId(),
            'document_id' => $document->getId(),
            'event_type_id' => $event_type->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitsEventTypesApiController@addSummitDocument",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
    }

    public function testAddFile2SummitDocument(){
        $document = self::$summit->getSummitDocuments()[0];

        $params = [
            'id'       => self::$summit->getId(),
            'document_id' => $document->getId()
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitDocumentsApiController@addFile",
            $params,
            [],
            [],
            [
                'file' => UploadedFile::fake()->image('slide2.pdf'),
            ],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
    }

    public function testRemoveFileFromSummitDocument(){
        $document = self::$summit->getSummitDocuments()[0];

        $params = [
            'id'       => self::$summit->getId(),
            'document_id' => $document->getId()
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitDocumentsApiController@removeFile",
            $params,
            [],
            [],
            [],
            $headers
        );

        $this->assertResponseStatus(204);
    }
}