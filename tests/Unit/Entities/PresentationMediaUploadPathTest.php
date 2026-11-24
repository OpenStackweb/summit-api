<?php namespace Tests\Unit\Entities;
/**
 * Copyright 2026 OpenStack Foundation
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

use App\Models\Utils\IStorageTypesConstants;
use Illuminate\Support\Facades\Config;
use models\summit\Presentation;
use models\summit\PresentationMediaUpload;
use Tests\InsertSummitTestData;
use Tests\TestCase;

class PresentationMediaUploadPathTest extends TestCase
{
    use InsertSummitTestData;

    protected function setUp(): void
    {
        parent::setUp();
        self::insertSummitTestData();
        Config::set('mediaupload.mounting_folder', 'PresentationMediaUploads');
        Config::set('mediaupload.private_mounting_folder', 'PresentationMediaUploads');
    }

    protected function tearDown(): void
    {
        self::clearSummitTestData();
        parent::tearDown();
    }

    private function makeUpload(Presentation $presentation, bool $legacy, ?string $frozen = null): PresentationMediaUpload
    {
        $upload = new PresentationMediaUpload();
        $upload->setFilename('deck.pptx');
        $presentation->addMediaUpload($upload);
        $r = new \ReflectionProperty(PresentationMediaUpload::class, 'legacy_path_format');
        $r->setAccessible(true);
        $r->setValue($upload, $legacy);
        $r = new \ReflectionProperty(PresentationMediaUpload::class, 'frozen_path_segment');
        $r->setAccessible(true);
        $r->setValue($upload, $frozen);
        return $upload;
    }

    private function presentation(): Presentation
    {
        $p = self::$summit->getPresentations()[0];
        $p->setTitle('Original Title: Café & Co');
        return $p;
    }

    public function testSlugifyMatchesGenerateSlug(): void
    {
        $p = $this->presentation();
        $p->generateSlug();
        $this->assertSame($p->getSlug(), Presentation::slugify('Original Title: Café & Co'));
    }

    public function testLegacyPathUnchanged(): void
    {
        $p = $this->presentation();
        $u = $this->makeUpload($p, true);
        $s = self::$summit->getId();
        $this->assertSame(sprintf('PresentationMediaUploads/%s/%s', $s, $p->getId()), $u->getPath(IStorageTypesConstants::PublicType));
        $this->assertSame(sprintf('PresentationMediaUploads/Private/%s/%s', $s, $p->getId()), $u->getPath(IStorageTypesConstants::PrivateType));
    }

    public function testModernPathWithoutFrozenSegmentMatchesOldFormula(): void
    {
        $p = $this->presentation();
        $u = $this->makeUpload($p, false);
        $summit = self::$summit;
        $expected = sprintf(
            'PresentationMediaUploads/%s-%s/%s-%s',
            $summit->getId(), filter_var($summit->getRawSlug(), FILTER_SANITIZE_ENCODED),
            $p->getId(), filter_var(Presentation::slugify($p->getTitle()), FILTER_SANITIZE_ENCODED)
        );
        $this->assertSame($expected, $u->getPath(IStorageTypesConstants::PublicType));
    }

    public function testBuildModernPathSegmentMatchesUnfrozenPath(): void
    {
        $p = $this->presentation();
        $u = $this->makeUpload($p, false);
        $segment = PresentationMediaUpload::buildModernPathSegment(
            self::$summit->getId(), self::$summit->getRawSlug(), $p->getId(), Presentation::slugify($p->getTitle())
        );
        $this->assertSame('PresentationMediaUploads/' . $segment, $u->getPath(IStorageTypesConstants::PublicType));
        $this->assertSame('PresentationMediaUploads/Private/' . $segment, $u->getPath(IStorageTypesConstants::PrivateType));
    }

    public function testFrozenSegmentSurvivesTitleChange(): void
    {
        $p = $this->presentation();
        $u = $this->makeUpload($p, false, '7-frozen-summit/99-frozen-title');
        $before = $u->getPath(IStorageTypesConstants::PrivateType);
        $p->setTitle('A Completely Different Title');
        $this->assertSame($before, $u->getPath(IStorageTypesConstants::PrivateType));
        $this->assertSame('PresentationMediaUploads/Private/7-frozen-summit/99-frozen-title', $before);
    }

    public function testFrozenSegmentSurvivesSummitSlugChange(): void
    {
        $p = $this->presentation();
        $u = $this->makeUpload($p, false, '7-frozen-summit/99-frozen-title');
        $before = $u->getPath(IStorageTypesConstants::PublicType);
        self::$summit->setRawSlug('renamed summit');
        $this->assertSame($before, $u->getPath(IStorageTypesConstants::PublicType));
    }

    public function testCloneDoesNotCarryFrozenSegment(): void
    {
        $p = $this->presentation();
        $u = $this->makeUpload($p, false, '7-frozen-summit/99-frozen-title');
        $clone = $u->clone();
        $this->assertNull($clone->getFrozenPathSegment());
    }
}
