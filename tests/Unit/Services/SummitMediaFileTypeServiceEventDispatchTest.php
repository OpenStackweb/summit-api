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

use App\Events\SponsorServices\SummitMediaFileTypeDomainEvents;
use App\Jobs\SponsorServices\PublishSponsorServiceDomainEventsJob;
use App\Services\Model\ISummitMediaFileTypeService;
use Illuminate\Support\Facades\Queue;
use models\summit\SummitMediaFileType;
use Tests\InsertSummitTestData;
use Tests\TestCase;

/**
 * Class SummitMediaFileTypeServiceEventDispatchTest
 *
 * Regression coverage for the same 'id' => 0 dispatch bug fixed in
 * SummitSponsorshipServiceEventDispatchTest: SummitMediaFileTypeService::delete()
 * removes the entity inside the transaction (real Doctrine remove(), not a
 * soft-delete), which flushes before the domain event dispatch. Doctrine nulls
 * the identifier on the deleted entity, so DeletedEventDTO::fromEntity($type)
 * read a stale getId(). The fix builds the DTO from the id captured before
 * removal (DeletedEventDTO::fromId($id)).
 *
 * @package Tests\Unit\Services
 */
class SummitMediaFileTypeServiceEventDispatchTest extends TestCase
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

    private function getService(): ISummitMediaFileTypeService
    {
        return app(ISummitMediaFileTypeService::class);
    }

    public function testDeleteDispatchesSummitMediaFileTypeDeletedWithOriginalId(): void
    {
        $type = new SummitMediaFileType();
        $type->setName('Test Type ' . str_random(6));
        $type->setDescription('desc');
        $type->setAllowedExtensions('.PDF');
        self::$em->persist($type);
        self::$em->flush();
        $id = $type->getId();

        Queue::fake();

        $this->getService()->delete($id);

        $jobs = Queue::pushed(PublishSponsorServiceDomainEventsJob::class, function ($job) {
            return $job->getEventType() === SummitMediaFileTypeDomainEvents::SummitMediaFileTypeDeleted;
        })->all();

        $this->assertCount(1, $jobs, 'Expected 1 SummitMediaFileTypeDeleted');
        $this->assertSame($id, $jobs[0]->getPayload()['id'], 'Dispatched id must be the removed type id, not 0');
    }
}
