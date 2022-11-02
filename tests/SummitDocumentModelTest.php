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
use models\summit\SummitDocument;
use Illuminate\Support\Facades\App;
use App\Http\Utils\IFileUploader;
use Illuminate\Http\UploadedFile;
/**
 * Class SummitDocumentModelTest
 */
class SummitDocumentModelTest extends BrowserKitTestCase
{
    use InsertSummitTestData;

    protected function setUp():void
    {
        parent::setUp();

        self::insertSummitTestData();
        self::$em->persist(self::$summit);
        self::$em->flush();
    }

    protected function tearDown():void
    {
        self::clearSummitTestData();
        parent::tearDown();
    }

    public function testModelRelations(){

        $fileUploader = App::make(IFileUploader::class);
        $file = $fileUploader->build
        (
            UploadedFile::fake()->image('slide.pdf'),
            sprintf('summits/%s/documents', self::$summit->getId()),
            false
        );

        $doc1 = new SummitDocument();
        $doc1->setName('doc 1');
        $doc1->setLabel("doc 1");
        $doc1->setDescription("this is the doc 1");
        $doc1->setFile($file);

        self::$summit->addSummitDocument($doc1);

        self::$em->persist(self::$summit);
        self::$em->flush();

        $link = $file->getCloudLink();

        $this->assertTrue(!empty($link));
    }

}