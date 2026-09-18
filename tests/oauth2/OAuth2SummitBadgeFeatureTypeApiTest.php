<?php namespace Tests;
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
use App\Jobs\FileProcessingJob;
use App\Models\Foundation\Summit\Repositories\ISummitBadgeFeatureTypeRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Mockery;
use models\summit\SummitBadgeFeatureType;
/**
 * Class OAuth2SummitBadgeFeatureTypeApiTest
 */
final class OAuth2SummitBadgeFeatureTypeApiTest extends ProtectedApiTestCase
{

    use InsertSummitTestData;

    public function createApplication()
    {
        $app = parent::createApplication();

        $fileUploaderMock = Mockery::mock(\App\Http\Utils\IFileUploader::class)
            ->shouldIgnoreMissing();

        $fileUploaderMock->shouldReceive('build')->andReturn(new \models\main\File());

        $app->instance(\App\Http\Utils\IFileUploader::class, $fileUploaderMock);

        return $app;
    }

    protected function setUp():void
    {
        parent::setUp();
        self::insertSummitTestData();
    }

    protected function tearDown():void
    {
        self::clearSummitTestData();
        parent::tearDown();
    }

    public function testAddBadgeFeatureType(){
        return $this->_testAddBadgeFeatureType();
    }

    /**
     * @param int $summit_id
     * @return mixed
     */
    protected function _testAddBadgeFeatureType(){
        $params = [
            'id' => self::$summit->getId(),
        ];

        $name        = str_random(16).'_feature_type';
        $template = <<<HTML
<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<svg
   xmlns:dc="http://purl.org/dc/elements/1.1/"
   xmlns:cc="http://creativecommons.org/ns#"
   xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
   xmlns:svg="http://www.w3.org/2000/svg"
   xmlns="http://www.w3.org/2000/svg"
   version="1.0"
   width="340pt"
   height="362pt"
   viewBox="0 0 340 362"
   id="svg3120">
  <defs
     id="defs3130" />
  <metadata
     id="metadata3122">
<rdf:RDF>
  <cc:Work
     rdf:about="">
    <dc:format>image/svg+xml</dc:format>
    <dc:type
       rdf:resource="http://purl.org/dc/dcmitype/StillImage" />
    <dc:title></dc:title>
  </cc:Work>
</rdf:RDF>
</metadata>
  <g
     transform="matrix(0.1,0,0,-0.1,0,362)"
     id="g3124"
     style="fill:#000000;stroke:none">
    <path
       d="m 3190,3550 c -80,-21 -249,-59 -375,-84 -321,-63 -372,-82 -515,-188 -203,-151 -345,-443 -344,-708 1,-101 16,-173 33,-154 4,5 18,34 30,64 61,147 238,371 389,492 77,62 232,123 232,91 0,-11 -53,-77 -116,-143 -59,-63 -79,-89 -190,-250 -115,-169 -265,-471 -366,-740 -98,-261 -170,-469 -218,-625 -81,-267 -154,-478 -167,-482 -16,-6 -60,137 -152,492 -143,556 -204,747 -350,1095 -100,237 -232,427 -500,718 -147,161 -356,315 -482,357 -82,28 -89,14 -27,-55 292,-329 461,-573 640,-920 151,-295 255,-588 444,-1260 48,-172 154,-610 188,-780 38,-189 80,-374 91,-403 4,-9 19,-21 34,-25 34,-9 198,-9 233,0 52,15 62,56 134,528 48,319 89,510 171,795 139,486 287,842 377,900 13,8 77,24 144,35 260,43 469,158 617,341 99,122 125,212 150,512 13,159 21,204 51,299 19,62 32,118 30,125 -7,18 -23,16 -186,-27 z"
       id="path3126"
       style="fill:#017f00;fill-opacity:1" />
  </g>
</svg>
HTML;

        $data = [
            'name'             => $name,
            'description'      => "this is a description",
            'template_content' => $template,
            'tag_name'         => "vegan",
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitBadgeFeatureTypeApiController@add",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $feature = json_decode($content);
        $this->assertTrue(!is_null($feature));
        $this->assertTrue($feature->name == $name);
        return $feature;
    }

    public function testUpdateBadgeFeatureType(){

        $feature_old = $this->_testAddBadgeFeatureType();
        $params = [
            'id' => self::$summit->getId(),
            "feature_id" => $feature_old->id
        ];

        $data = [
            'description'      => "this is a description update",
            'is_default'       => false,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitBadgeFeatureTypeApiController@update",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $feature = json_decode($content);
        $this->assertTrue(!is_null($feature));
        $this->assertTrue($feature->name == $feature_old->name);
        return $feature;
    }

    public function testGetAllBySummit(){

        $this->_testAddBadgeFeatureType();
        $this->_testAddBadgeFeatureType();
        $this->_testAddBadgeFeatureType();

        $params = [
            'id' => self::$summit->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitBadgeFeatureTypeApiController@getAllBySummit",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $data = json_decode($content);
        $this->assertTrue(!is_null($data));
        $this->assertTrue($data->total == 3);
        return $data;
    }

    /**
     * @param int $summit_id
     */
    public function testDeleteFeature(){
        $feature_old = $this->_testAddBadgeFeatureType();
        $params = [
            'id' => self::$summit->getId(),
            "feature_id" => $feature_old->id
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitBadgeFeatureTypeApiController@delete",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(204);
    }

    public function testAddFeatureImage(){

        $feature_old = $this->_testAddBadgeFeatureType();

        $params = [
            'id' => self::$summit->getId(),
            "feature_id" => $feature_old->id
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitBadgeFeatureTypeApiController@addFeatureImage",
            $params,
            [],
            [],
            [
                'file' => UploadedFile::fake()->image('feat.svg'),
            ],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $file = json_decode($content);
        $this->assertTrue(!is_null($file));
    }

    public function testDeleteFeatureImage(){

        $feature_old = $this->_testAddBadgeFeatureType();

        $params = [
            'id' => self::$summit->getId(),
            "feature_id" => $feature_old->id
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitBadgeFeatureTypeApiController@addFeatureImage",
            $params,
            [],
            [],
            [
                'file' => UploadedFile::fake()->image('feat.svg'),
            ],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $file = json_decode($content);
        $this->assertTrue(!is_null($file));

        $response = $this->action(
            "DELETE",
            "OAuth2SummitBadgeFeatureTypeApiController@deleteFeatureImage",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(204);
    }

    // -------------------------------------------------------------------------
    // image as File API payload (async FileProcessingJob)
    // -------------------------------------------------------------------------

    private function jsonHeaders(): array
    {
        return [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"       => "application/json"
        ];
    }

    /**
     * Pins the File API storage to a fake local disk and stores $content at $remotePath.
     */
    private function putRemoteFile(string $remotePath, string $content): void
    {
        Config::set('file_upload.storage_driver', 'local');
        Storage::fake('local');
        Storage::disk('local')->put($remotePath, $content);
    }

    private function buildImagePayload(string $remotePath, string $filename, string $content, string $mime_type = 'image/png'): array
    {
        return [
            'filepath'  => $remotePath,
            'filename'  => $filename,
            'md5'       => md5($content),
            'size'      => strlen($content),
            'mime_type' => $mime_type,
        ];
    }

    private function fakePngContent(): string
    {
        return file_get_contents(UploadedFile::fake()->image('feature.png')->getRealPath());
    }

    private function postFeature(array $data)
    {
        return $this->action(
            "POST",
            "OAuth2SummitBadgeFeatureTypeApiController@add",
            ['id' => self::$summit->getId()],
            [],
            [],
            [],
            $this->jsonHeaders(),
            json_encode($data)
        );
    }

    private function putFeature(int $feature_id, array $data)
    {
        return $this->action(
            "PUT",
            "OAuth2SummitBadgeFeatureTypeApiController@update",
            ['id' => self::$summit->getId(), 'feature_id' => $feature_id],
            [],
            [],
            [],
            $this->jsonHeaders(),
            json_encode($data)
        );
    }

    private function assertImageJobPushedFor(int $feature_id): void
    {
        Queue::assertPushed(FileProcessingJob::class, 1);
        Queue::assertPushed(FileProcessingJob::class, function (FileProcessingJob $job) use ($feature_id) {
            return $job->fileInfoDTO->owner_entity_class === SummitBadgeFeatureType::class
                && $job->fileInfoDTO->owner_member_name === 'image'
                && $job->fileInfoDTO->owner_entity_id === $feature_id;
        });
    }

    public function testAddBadgeFeatureTypeWithImageQueuesFileProcessingJob(): void
    {
        $content = $this->fakePngContent();
        $this->putRemoteFile('badge-features/tmp/feature.png', $content);
        Queue::fake();

        $response = $this->postFeature([
            'name'  => str_random(16) . '_feature_type',
            'image' => $this->buildImagePayload('badge-features/tmp/feature.png', 'feature.png', $content),
        ]);

        $this->assertResponseStatus(201);
        $feature = json_decode($response->getContent());
        $this->assertImageJobPushedFor($feature->id);
    }

    public function testUpdateBadgeFeatureTypeWithImageQueuesFileProcessingJob(): void
    {
        $content = $this->fakePngContent();
        $this->putRemoteFile('badge-features/tmp/feature.png', $content);
        Queue::fake();

        $feature = $this->_testAddBadgeFeatureType();

        // PUT goes through JsonController::updated(), which responds 201
        $this->putFeature($feature->id, [
            'image' => $this->buildImagePayload('badge-features/tmp/feature.png', 'feature.png', $content),
        ]);

        $this->assertResponseStatus(201);
        $this->assertImageJobPushedFor($feature->id);
    }

    public function testUpdateBadgeFeatureTypeWithoutImageQueuesNoJobAndKeepsImage(): void
    {
        $feature = $this->_testAddBadgeFeatureType();

        $this->action(
            "POST",
            "OAuth2SummitBadgeFeatureTypeApiController@addFeatureImage",
            ['id' => self::$summit->getId(), 'feature_id' => $feature->id],
            [],
            [],
            ['file' => UploadedFile::fake()->image('feat.png')],
            $this->jsonHeaders()
        );
        $this->assertResponseStatus(201);

        Queue::fake();

        $this->putFeature($feature->id, ['description' => 'updated without image']);

        $this->assertResponseStatus(201);
        Queue::assertNotPushed(FileProcessingJob::class);
        $entity = App::make(ISummitBadgeFeatureTypeRepository::class)->getById($feature->id);
        $this->assertNotNull($entity->getImage());
    }

    public function testEmptyStringImageIsIgnoredOnAddAndUpdate(): void
    {
        Queue::fake();

        $response = $this->postFeature([
            'name'  => str_random(16) . '_feature_type',
            'image' => '',
        ]);
        $this->assertResponseStatus(201);
        $feature = json_decode($response->getContent());

        $this->putFeature($feature->id, ['image' => '']);
        $this->assertResponseStatus(201);

        Queue::assertNotPushed(FileProcessingJob::class);
    }

    public function testImageWithInvalidExtensionReturns412AndPersistsNothing(): void
    {
        $this->putRemoteFile('badge-features/tmp/feature.bmp', 'fake-bmp-content');
        Queue::fake();
        $image = $this->buildImagePayload('badge-features/tmp/feature.bmp', 'feature.bmp', 'fake-bmp-content', 'image/bmp');

        // POST: nothing is created, so the same name is still free afterwards
        $name = str_random(16) . '_feature_type';
        $this->postFeature(['name' => $name, 'image' => $image]);
        $this->assertResponseStatus(412);
        $this->postFeature(['name' => $name]);
        $this->assertResponseStatus(201);

        // PUT: the name change in the same request is not persisted
        $feature = $this->_testAddBadgeFeatureType();
        $this->putFeature($feature->id, ['name' => str_random(16) . '_renamed', 'image' => $image]);
        $this->assertResponseStatus(412);

        $response = $this->action(
            "GET",
            "OAuth2SummitBadgeFeatureTypeApiController@get",
            ['id' => self::$summit->getId(), 'feature_id' => $feature->id],
            [],
            [],
            [],
            $this->jsonHeaders()
        );
        $this->assertResponseStatus(200);
        $this->assertEquals($feature->name, json_decode($response->getContent())->name);

        Queue::assertNotPushed(FileProcessingJob::class);
    }

    public function testImageNotPresentInStorageReturns412AndPersistsNothing(): void
    {
        Config::set('file_upload.storage_driver', 'local');
        Storage::fake('local');
        Queue::fake();

        $name = str_random(16) . '_feature_type';
        $this->postFeature([
            'name'  => $name,
            'image' => $this->buildImagePayload('badge-features/tmp/missing.png', 'missing.png', 'whatever'),
        ]);
        $this->assertResponseStatus(412);

        $this->postFeature(['name' => $name]);
        $this->assertResponseStatus(201);

        Queue::assertNotPushed(FileProcessingJob::class);
    }

    public function testImageOverMaxFileSizeReturns412AndPersistsNothing(): void
    {
        // valid extension, one byte over the limit enforced by addFeatureImage
        $content = str_repeat('a', SummitBadgeFeatureType::ImageMaxFileSize + 1);
        $this->putRemoteFile('badge-features/tmp/huge.png', $content);
        Queue::fake();

        $name = str_random(16) . '_feature_type';
        $this->postFeature([
            'name'  => $name,
            'image' => $this->buildImagePayload('badge-features/tmp/huge.png', 'huge.png', $content),
        ]);
        $this->assertResponseStatus(412);

        $this->postFeature(['name' => $name]);
        $this->assertResponseStatus(201);

        Queue::assertNotPushed(FileProcessingJob::class);
    }
}