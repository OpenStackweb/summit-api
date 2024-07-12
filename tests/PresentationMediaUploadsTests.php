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
use models\summit\SummitMediaFileType;
use Doctrine\Persistence\ObjectRepository;
use models\summit\Presentation;
use models\summit\SummitMediaUploadType;
/**
 * Class PresentationMediaUploadsTests
 */
class PresentationMediaUploadsTests extends ProtectedApiTestCase {
  use InsertSummitTestData;
  /**
   * @var ObjectRepository
   */
  static $media_file_type_repository;

  /**
   * @var Presentation
   */
  static $presentation;

  /**
   * @var SummitMediaUploadType
   */
  static $media_upload_type;

  protected function setUp(): void {
    parent::setUp();
    self::$media_file_type_repository = EntityManager::getRepository(SummitMediaFileType::class);
    $types = self::$media_file_type_repository->findAll();
    self::insertSummitTestData();
    self::$media_upload_type = new SummitMediaUploadType();
    self::$media_upload_type->setType($types[0]);
    self::$media_upload_type->setName("TEST");
    self::$media_upload_type->setDescription("TEST");
    self::$media_upload_type->setMaxSize(2048);
    self::$media_upload_type->setMinUploadsQty(2);
    self::$media_upload_type->setMaxUploadsQty(4);
    self::$media_upload_type->setPrivateStorageType(
      \App\Models\Utils\IStorageTypesConstants::DropBox,
    );
    self::$media_upload_type->setPublicStorageType(\App\Models\Utils\IStorageTypesConstants::Swift);

    self::$presentation = new Presentation();
    $event_types = self::$summit->getEventTypes();
    self::$presentation->setTitle("TEST PRESENTATION");
    self::$presentation->setType($event_types[0]);
    self::$summit->addEvent(self::$presentation);
    self::$media_upload_type->addPresentationType($event_types[0]);
    self::$summit->addMediaUploadType(self::$media_upload_type);
    self::$em->persist(self::$summit);
    self::$em->flush();
  }

  protected function tearDown(): void {
    self::clearSummitTestData();
    parent::tearDown();
  }

  public function testAddMediaUpload() {
    $params = [
      "id" => self::$summit->getId(),
      "presentation_id" => self::$presentation->getId(),
      "expand" => "media_upload_type,media_upload_type.type",
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      //   "CONTENT_TYPE" => "multipart/form-data; boundary=----WebKitFormBoundaryBkSYnzBIiFtZu4pb"
    ];

    $payload = [
      "media_upload_type_id" => self::$media_upload_type->getId(),
      "filepath" =>
        "upload/video-mp4/2020-10-41/OpenDev 2020- Hardware Automation_ab8dbdb02b52fea11b7e3e5e80c63086.mp4",
    ];

    $response = $this->action(
      "POST",
      "OAuth2PresentationApiController@addPresentationMediaUpload",
      $params,
      $payload,
      [],
      [
        "file" => UploadedFile::fake()->image("slide.png"),
      ],
      $headers,
      json_encode($payload),
    );

    $content = $response->getContent();
    $this->assertResponseStatus(201);
    $response = json_decode($content, true);

    $this->assertTrue(isset($response["public_url"]));
  }
}
