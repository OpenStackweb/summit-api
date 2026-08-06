<?php namespace Tests\Unit\Services;
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

use App\Jobs\ProcessScheduleEntityLifeCycleEvent;
use Illuminate\Support\Facades\Queue;
use models\summit\PresentationMediaUpload;
use Tests\InsertSummitTestData;
use Tests\TestCase;

/**
 * Class PresentationMaterialEventDispatchTest
 *
 * Regression coverage for a bug where PresentationMaterial (PresentationMediaUpload,
 * PresentationSlide, PresentationVideo, PresentationLink) lifecycle events were
 * dispatched with summit_id => 0: ScheduleEntity::_getSummitId() resolves the
 * summit id via reflection (a "summit" property, or a getSummitId() method), and
 * PresentationMaterial exposed neither - it only has a "presentation" relation.
 * The fix adds PresentationMaterial::getSummitId(), delegating to the owning
 * Presentation.
 *
 * @package Tests\Unit\Services
 */
class PresentationMaterialEventDispatchTest extends TestCase
{
    use InsertSummitTestData;

    protected function setUp(): void
    {
        parent::setUp();
        self::insertSummitTestData();
    }

    public function tearDown(): void
    {
        self::clearSummitTestData();
        parent::tearDown();
    }

    private function getMediaUpload(): PresentationMediaUpload
    {
        foreach (self::$presentations as $presentation) {
            $media_upload = $presentation->getMediaUploads()->first();
            if ($media_upload !== false) {
                return $media_upload;
            }
        }
        $this->fail('Pre-condition: no presentation with a media upload found in fixtures');
    }

    /**
     * @return ProcessScheduleEntityLifeCycleEvent[]
     */
    private function jobsFor(string $entity_type): array
    {
        return Queue::pushed(ProcessScheduleEntityLifeCycleEvent::class, function ($job) use ($entity_type) {
            return $job->entity_type === $entity_type;
        })->all();
    }

    public function testUpdateMediaUploadDispatchesLifeCycleEventWithSummitId(): void
    {
        $media_upload = $this->getMediaUpload();
        $summit_id = $media_upload->getPresentation()->getSummitId();
        $this->assertGreaterThan(0, $summit_id, 'Pre-condition: presentation must belong to a summit');

        Queue::fake();

        $media_upload->setName('Updated Media Upload Name');
        self::$em->persist($media_upload);
        self::$em->flush();

        $jobs = $this->jobsFor('PresentationMediaUpload');
        $this->assertCount(1, $jobs, 'Expected 1 ProcessScheduleEntityLifeCycleEvent for PresentationMediaUpload update');
        $this->assertSame($summit_id, $jobs[0]->summit_id, 'Dispatched summit_id must be the presentation summit id, not 0');
    }

    public function testInsertMediaUploadDispatchesLifeCycleEventWithSummitId(): void
    {
        $presentation = self::$presentations[0];
        $summit_id = $presentation->getSummitId();
        $this->assertGreaterThan(0, $summit_id, 'Pre-condition: presentation must belong to a summit');

        Queue::fake();

        $media_upload = new PresentationMediaUpload();
        $media_upload->setName('New Media Upload');
        $media_upload->setDescription('New Media Upload Description');
        $media_upload->setFilename('new_media_upload.png');
        $media_upload->setMediaUploadType(self::$media_uploads_types[0]);
        $presentation->addMediaUpload($media_upload);
        self::$em->persist($media_upload);
        self::$em->flush();

        $jobs = $this->jobsFor('PresentationMediaUpload');
        $this->assertCount(1, $jobs, 'Expected 1 ProcessScheduleEntityLifeCycleEvent for PresentationMediaUpload insert');
        $this->assertSame($summit_id, $jobs[0]->summit_id, 'Dispatched summit_id must be the presentation summit id, not 0');
    }

    /**
     * Characterization test for the deleting() (PreRemove) path: the production
     * delete flow (Presentation::removeMediaUpload() -> unsetPresentation(),
     * relying on the materials collection's orphanRemoval to schedule the actual
     * Doctrine delete) nulls the "presentation" association *before* flush()
     * triggers PreRemove. So getSummitId() legitimately falls into its
     * defensive catch and returns 0 here - same accepted degraded case already
     * called out for getPresentationId(), and the same shape as SummitOwned's
     * former_summit_id gap for entities whose owning reference is cleared ahead
     * of removal. This is not a regression: the important behavior is that the
     * lifecycle event still dispatches without throwing (see
     * PresentationMaterial::getSummitId() catching \Throwable, not just
     * \Exception, to survive exactly this null-presentation case).
     */
    public function testDeleteMediaUploadDispatchesLifeCycleEventWithoutError(): void
    {
        $media_upload = $this->getMediaUpload();
        $presentation = $media_upload->getPresentation();
        $this->assertGreaterThan(0, $presentation->getSummitId(), 'Pre-condition: presentation must belong to a summit');

        Queue::fake();

        $presentation->removeMediaUpload($media_upload);
        self::$em->flush();

        $jobs = $this->jobsFor('PresentationMediaUpload');
        $this->assertCount(1, $jobs, 'Expected 1 ProcessScheduleEntityLifeCycleEvent for PresentationMediaUpload delete');
        $this->assertSame(0, $jobs[0]->summit_id, 'summit_id is 0 here because unsetPresentation() runs before PreRemove - accepted degraded case, not a regression');
    }
}
