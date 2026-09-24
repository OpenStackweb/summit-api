<?php

namespace Tests\OpenTelemetry\Formatters;

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

use App\Audit\ConcreteFormatters\ChildEntityFormatters\PresentationTrackChairScoreAuditLogFormatter;
use App\Audit\ConcreteFormatters\EntityCollectionUpdateAuditLogFormatter;
use App\Models\Foundation\Summit\Events\Presentations\TrackChairs\PresentationTrackChairRatingType;
use App\Models\Foundation\Summit\Events\Presentations\TrackChairs\PresentationTrackChairScore;
use App\Models\Foundation\Summit\Events\Presentations\TrackChairs\PresentationTrackChairScoreType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\OneToManyAssociationMapping;
use Doctrine\ORM\PersistentCollection;
use Mockery;
use models\main\Member;
use models\summit\Presentation;
use models\summit\SummitTrackChair;
use Tests\TestCase;

/**
 * A track chair changing their score is persisted as remove old + add new in the same
 * transaction (PresentationService::addTrackChairScore), and removeScore() nulls the
 * removed score's reviewer. The audit entry must still name the chair and render the
 * replacement as one change instead of "scored ...|Score removed ...".
 * @package Tests\OpenTelemetry\Formatters
 */
final class PresentationTrackChairScoreAuditLogFormatterTest extends TestCase
{
    private const ChairName = 'Ada Lovelace';
    private const PresentationTitle = 'Liquid Cooling Filtration';

    private $chair;
    private $presentation;
    private $rating_type;

    protected function setUp(): void
    {
        parent::setUp();

        $member = Mockery::mock(Member::class);
        $member->shouldReceive('getFullName')->andReturn(self::ChairName);

        $this->chair = Mockery::mock(SummitTrackChair::class);
        $this->chair->shouldReceive('getId')->andReturn(7);
        $this->chair->shouldReceive('getMember')->andReturn($member);

        $this->presentation = Mockery::mock(Presentation::class);
        $this->presentation->shouldReceive('getId')->andReturn(1063);
        $this->presentation->shouldReceive('getTitle')->andReturn(self::PresentationTitle);

        $this->rating_type = Mockery::mock(PresentationTrackChairRatingType::class);
        $this->rating_type->shouldReceive('getId')->andReturn(3);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function buildScore(string $label): PresentationTrackChairScore
    {
        $type = Mockery::mock(PresentationTrackChairScoreType::class);
        $type->shouldReceive('getName')->andReturn($label);
        $type->shouldReceive('getType')->andReturn($this->rating_type);

        $score = new PresentationTrackChairScore();
        $score->setType($type);
        $score->setReviewer($this->chair);
        $score->setPresentation($this->presentation);
        return $score;
    }

    private function buildCollection(object $owner, string $field, array $initial): PersistentCollection
    {
        $mapping = OneToManyAssociationMapping::fromMappingArray([
            'fieldName' => $field,
            'sourceEntity' => get_class($owner),
            'targetEntity' => PresentationTrackChairScore::class,
            'mappedBy' => $field === 'scores' ? 'reviewer' : 'presentation',
            'isOwningSide' => false,
        ]);

        $collection = new PersistentCollection(
            $this->createMock(EntityManagerInterface::class),
            new ClassMetadata(PresentationTrackChairScore::class),
            new ArrayCollection($initial)
        );
        $collection->setOwner($owner, $mapping);
        $collection->takeSnapshot();
        return $collection;
    }

    public function testNewScoreNamesChairAndScore(): void
    {
        $collection = $this->buildCollection($this->chair, 'scores', []);
        $collection->add($this->buildScore('Great'));

        $this->assertSame(
            "Track Chair 'Ada Lovelace' scored 'Great' on presentation 'Liquid Cooling Filtration'",
            (new PresentationTrackChairScoreAuditLogFormatter())->formatCollection($collection)
        );
    }

    public function testReplacedScoreIsOneChangeEntry(): void
    {
        $old = $this->buildScore('Good');
        $collection = $this->buildCollection($this->chair, 'scores', [$old]);

        // same steps as SummitTrackChair::removeScore + addScore
        $collection->removeElement($old);
        $old->clearReviewer();
        $collection->add($this->buildScore('Great'));

        $this->assertSame(
            "Track Chair 'Ada Lovelace' changed score from 'Good' to 'Great' on presentation 'Liquid Cooling Filtration'",
            (new PresentationTrackChairScoreAuditLogFormatter())->formatCollection($collection)
        );
    }

    public function testRemovedScoreStillNamesChair(): void
    {
        $old = $this->buildScore('Good');
        $collection = $this->buildCollection($this->chair, 'scores', [$old]);

        $collection->removeElement($old);
        $old->clearReviewer();

        $this->assertSame(
            "Track Chair 'Ada Lovelace' removed score 'Good' from presentation 'Liquid Cooling Filtration'",
            (new PresentationTrackChairScoreAuditLogFormatter())->formatCollection($collection)
        );
    }

    public function testPresentationSideCollectionResolvesChairFromScore(): void
    {
        // the database audit strategy only sees the presentation side
        $collection = $this->buildCollection($this->presentation, 'track_chairs_scores', []);
        $collection->add($this->buildScore('Great'));

        $this->assertSame(
            "Track Chair 'Ada Lovelace' scored 'Great' on presentation 'Liquid Cooling Filtration'",
            (new PresentationTrackChairScoreAuditLogFormatter())->formatCollection($collection)
        );
    }

    public function testFormatResolvesChairAndScoreForEveryAction(): void
    {
        $formatter = new PresentationTrackChairScoreAuditLogFormatter();
        $score = $this->buildScore('Great');

        $this->assertSame(
            "Track Chair 'Ada Lovelace' scored 'Great' on presentation 'Liquid Cooling Filtration'",
            $formatter->format($score, PresentationTrackChairScoreAuditLogFormatter::CHILD_ENTITY_CREATION)
        );
        $this->assertSame(
            "Track Chair 'Ada Lovelace' score updated to 'Great' on presentation 'Liquid Cooling Filtration'",
            $formatter->format($score, PresentationTrackChairScoreAuditLogFormatter::CHILD_ENTITY_UPDATE)
        );
        $this->assertSame(
            "Track Chair 'Ada Lovelace' removed score 'Great' from presentation 'Liquid Cooling Filtration'",
            $formatter->format($score, PresentationTrackChairScoreAuditLogFormatter::CHILD_ENTITY_DELETION)
        );
    }

    public function testFormatDoesNotThrowOnRemovedScoreWithoutReviewer(): void
    {
        $score = $this->buildScore('Good');
        $score->clearReviewer();

        $this->assertSame(
            "Track Chair 'Unknown Chair' removed score 'Good' from presentation 'Liquid Cooling Filtration'",
            (new PresentationTrackChairScoreAuditLogFormatter())
                ->format($score, PresentationTrackChairScoreAuditLogFormatter::CHILD_ENTITY_DELETION)
        );
    }

    public function testCollectionUpdateFormatterDelegatesToCollectionFormatter(): void
    {
        $old = $this->buildScore('Good');
        $collection = $this->buildCollection($this->chair, 'scores', [$old]);
        $collection->removeElement($old);
        $old->clearReviewer();
        $collection->add($this->buildScore('Great'));

        $formatter = new EntityCollectionUpdateAuditLogFormatter(new PresentationTrackChairScoreAuditLogFormatter());

        $this->assertSame(
            "Track Chair 'Ada Lovelace' changed score from 'Good' to 'Great' on presentation 'Liquid Cooling Filtration'",
            $formatter->format($collection, [])
        );
    }
}
