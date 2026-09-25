<?php

namespace Tests\OpenTelemetry;

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

use App\Audit\AuditContext;
use App\Audit\AuditLogOtlpStrategy;
use App\Audit\IAuditLogFormatterFactory;
use App\Audit\Interfaces\IAuditStrategy;
use App\Models\Foundation\Summit\Events\Presentations\TrackChairs\PresentationTrackChairScore;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\OneToManyAssociationMapping;
use Doctrine\ORM\PersistentCollection;
use Illuminate\Support\Facades\Queue;
use Mockery;
use models\summit\Presentation;
use models\summit\SummitTrackChair;

/**
 * Every track chair score lives in both Presentation::track_chairs_scores and
 * SummitTrackChair::scores; only the track chair side may be audited or each score is
 * logged twice.
 */
class AuditLogOtlpStrategySkippedCollectionTest extends OpenTelemetryTestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function buildStrategy(IAuditLogFormatterFactory $factory): AuditLogOtlpStrategy
    {
        $strategy = new AuditLogOtlpStrategy($factory);
        $enabled = (new \ReflectionClass($strategy))->getProperty('enabled');
        $enabled->setAccessible(true);
        $enabled->setValue($strategy, true);
        return $strategy;
    }

    private function buildCollection(object $owner, string $field, string $mapped_by): PersistentCollection
    {
        $collection = new PersistentCollection(
            $this->createMock(EntityManagerInterface::class),
            new ClassMetadata(PresentationTrackChairScore::class),
            new ArrayCollection()
        );
        $collection->setOwner($owner, OneToManyAssociationMapping::fromMappingArray([
            'fieldName' => $field,
            'sourceEntity' => get_class($owner),
            'targetEntity' => PresentationTrackChairScore::class,
            'mappedBy' => $mapped_by,
            'isOwningSide' => false,
        ]));
        return $collection;
    }

    public function testPresentationTrackChairScoresCollectionIsNotAudited(): void
    {
        $factory = $this->createMock(IAuditLogFormatterFactory::class);
        $factory->expects($this->never())->method('make');

        Queue::fake();

        $this->buildStrategy($factory)->audit(
            $this->buildCollection(Mockery::mock(Presentation::class), 'track_chairs_scores', 'presentation'),
            [],
            IAuditStrategy::EVENT_COLLECTION_UPDATE,
            new AuditContext()
        );

        Queue::assertNothingPushed();
    }

    public function testTrackChairScoresCollectionIsAudited(): void
    {
        $factory = $this->createMock(IAuditLogFormatterFactory::class);
        $factory->expects($this->once())->method('make')->willReturn(null);

        $this->buildStrategy($factory)->audit(
            $this->buildCollection(Mockery::mock(SummitTrackChair::class), 'scores', 'reviewer'),
            [],
            IAuditStrategy::EVENT_COLLECTION_UPDATE,
            new AuditContext()
        );
    }
}
