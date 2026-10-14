<?php namespace Tests\Unit\Models;
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

use App\Models\Foundation\Summit\Speakers\PresentationSpeakerAssignment;
use Mockery;
use models\summit\Presentation;
use models\summit\PresentationSpeaker;
use models\summit\Summit;
use PHPUnit\Framework\TestCase;

/**
 * Class PresentationSpeakerPresentationsByRoleTest
 *
 * Pure unit tests for {@see PresentationSpeaker::getPresentationsBySummitAndRole} with
 * ROLE_SPEAKER, covering assignments whose presentation is null.
 *
 * An assignment's constructor requires a Presentation, so a null one only comes from the
 * database (a NULL or dangling PresentationID). The orphan here is a real assignment whose
 * presentation field is nulled, which reproduces that row.
 *
 * @package Tests\Unit\Models
 */
class PresentationSpeakerPresentationsByRoleTest extends TestCase
{
    private const SUMMIT_ID = 1;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function summit(): Summit
    {
        $summit = Mockery::mock(Summit::class);
        $summit->shouldReceive('getId')->andReturn(self::SUMMIT_ID);
        return $summit;
    }

    /**
     * A presentation the speaker is assigned to as a plain speaker in the given summit.
     * Moderator and creator ids are non-zero so they can't match a fresh speaker's ids.
     */
    private function presentation(Summit $summit): Presentation
    {
        $presentation = Mockery::mock(Presentation::class);
        $presentation->shouldReceive('getSummit')->andReturn($summit);
        $presentation->shouldReceive('getModeratorId')->andReturn(99);
        $presentation->shouldReceive('getCreatorId')->andReturn(98);
        $presentation->shouldReceive('getSpeakerAssignmentsMaxOrder')->andReturn(0);
        return $presentation;
    }

    private function addOrphanAssignment(PresentationSpeaker $speaker): void
    {
        $assignment = new PresentationSpeakerAssignment(Mockery::mock(Presentation::class), $speaker, 1);

        $presentation_field = new \ReflectionProperty(PresentationSpeakerAssignment::class, 'presentation');
        $presentation_field->setValue($assignment, null);

        $assignments_field = new \ReflectionProperty(PresentationSpeaker::class, 'presentations');
        $assignments_field->getValue($speaker)->add($assignment);
    }

    public function testSkipsAssignmentWithNullPresentationAndKeepsValidOne(): void
    {
        $summit = $this->summit();
        $presentation = $this->presentation($summit);

        $speaker = new PresentationSpeaker();
        $speaker->addPresentation($presentation);
        $this->addOrphanAssignment($speaker);

        $result = $speaker->getPresentationsBySummitAndRole($summit, PresentationSpeaker::ROLE_SPEAKER);

        $this->assertSame([$presentation], array_values($result));
    }

    public function testReturnsEmptyWhenOnlyAssignmentHasNullPresentation(): void
    {
        $speaker = new PresentationSpeaker();
        $this->addOrphanAssignment($speaker);

        $result = $speaker->getPresentationsBySummitAndRole($this->summit(), PresentationSpeaker::ROLE_SPEAKER);

        $this->assertSame([], $result);
    }
}
