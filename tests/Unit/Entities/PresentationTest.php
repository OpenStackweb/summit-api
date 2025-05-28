<?php

namespace Tests\Unit\Entities;

/**
 * Copyright 2025 OpenStack Foundation
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

use models\summit\Presentation;
use models\summit\PresentationMediaUpload;
use Tests\InsertSummitTestData;
use Tests\TestCase;

class PresentationTest extends TestCase
{
    use InsertSummitTestData;

    /**
     * @throws \Exception
     */
    protected function setUp():void
    {
        parent::setUp();
        self::insertSummitTestData();
    }

    public function tearDown():void
    {
        self::clearSummitTestData();
        parent::tearDown();
    }

    public function testUpdatePresentation(){
        $presentation_id = self::$summit->getPresentations()[0]->getId();
        $repository = self::$em->getRepository(Presentation::class);
        $presentation = $repository->find($presentation_id);

        $category = TestUtils::mockPresentationCategory(self::$summit);
        self::$em->persist($category);

        $presentation->setCategory($category);

        $previous_materials_count = count($presentation->getMaterials()->toArray());

        $media_upload = new PresentationMediaUpload();
        $media_upload_type_idx = array_rand(self::$media_uploads_types);
        $media_upload->setName("Test Media Upload");
        $media_upload->setDescription("Test Media Upload Description");
        $media_upload->setFilename("Test Media Upload Filename %s");
        $media_upload->setMediaUploadType(self::$media_uploads_types[$media_upload_type_idx]);
        $presentation->addMediaUpload($media_upload);

        self::$em->persist($presentation);
        self::$em->flush();
        self::$em->clear();

        $found_presentation = $repository->find($presentation->getId());

        //Test DiscriminatorMap
        $this->assertInstanceOf(Presentation::class, $found_presentation);

        //Test ManyToOne relations
        $found_category = $found_presentation->getCategory();
        $this->assertEquals($category->getCode(), $found_category->getCode());

        //Test OneToMany relations
        $this->assertCount($previous_materials_count + 1, $found_presentation->getMaterials()->toArray());
    }

    public function testDeletePresentationChildren(){
        $presentation_id = self::$summit->getPresentations()[0]->getId();
        $repository = self::$em->getRepository(Presentation::class);
        $presentation = $repository->find($presentation_id);

        $media_uploads = $presentation->getMediaUploads();
        $previous_media_uploads_count = count($presentation->getMediaUploads()->toArray());

        $presentation->removeMediaUpload($media_uploads[0]);
        $presentation->clearTags();

        $this->assertEquals(self::$default_selection_plan->getId(), $presentation->getSelectionPlan()->getId());

        $presentation->setSelectionPlan(self::$default_selection_plan2);

        self::$em->flush();
        self::$em->clear();

        $found_presentation = $repository->find($presentation->getId());

        //Test ManyToOne relations
        $this->assertEquals(self::$default_selection_plan2->getId(), $found_presentation->getSelectionPlan()->getId());

        //Test ManyToMany relations
        $this->assertEmpty($found_presentation->getTags()->toArray());

        //Test OneToMany relations
        $this->assertCount($previous_media_uploads_count - 1, $found_presentation->getMediaUploads()->toArray());
    }
}