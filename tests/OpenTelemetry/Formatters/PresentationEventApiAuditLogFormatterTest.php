<?php namespace Tests\OpenTelemetry\Formatters;
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

use App\Audit\ConcreteFormatters\PresentationFormatters\PresentationEventApiAuditLogFormatter;
use App\Audit\Interfaces\IAuditStrategy;
use Mockery;
use models\summit\Presentation;
use Tests\OpenTelemetry\Formatters\Support\AuditContextBuilder;
use Tests\TestCase;

/**
 * Covers the change set a "save & publish" from summit-admin produces on a presentation:
 * updateEventDates() and publish() assign fresh DateTime instances (same instant) and
 * PresentationFactory writes int 0 over attending_media=false, none of which is a real change.
 */
class PresentationEventApiAuditLogFormatterTest extends TestCase
{
    private const MOCK_ID = 9643;
    private const TITLE = 'Rack-Scale AI Systems';

    private PresentationEventApiAuditLogFormatter $formatter_update;

    protected function setUp(): void
    {
        parent::setUp();

        $this->formatter_update = new PresentationEventApiAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_UPDATE);
        $this->formatter_update->setContext(AuditContextBuilder::default()->build());
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function createMockPresentation(): object
    {
        $mock = Mockery::mock(Presentation::class);
        $mock->shouldReceive('getTitle')->andReturn(self::TITLE);
        $mock->shouldReceive('getId')->andReturn(self::MOCK_ID);
        $mock->shouldReceive('getCreator')->andReturn(null);
        $mock->shouldReceive('getCategory')->andReturn(null);
        $mock->shouldReceive('getSelectionPlan')->andReturn(null);

        return $mock;
    }

    /**
     * The change set Doctrine reports for a republish without edits.
     */
    private function republishNoiseChangeSet(): array
    {
        return [
            'start_date'     => [new \DateTime('2026-10-12 21:30:00'), new \DateTime('2026-10-12 21:30:00')],
            'end_date'       => [new \DateTime('2026-10-12 21:50:00'), new \DateTime('2026-10-12 21:50:00')],
            'published_date' => [new \DateTime('2026-09-24 00:44:26'), new \DateTime('2026-09-24 00:44:26')],
            'attending_media' => [false, 0],
        ];
    }

    public function testRepublishWithoutEditsIsSuppressed(): void
    {
        $result = $this->formatter_update->format($this->createMockPresentation(), $this->republishNoiseChangeSet());

        $this->assertNull($result);
    }

    public function testRealChangeSurvivesRepublishNoise(): void
    {
        $change_set = $this->republishNoiseChangeSet();
        $change_set['show_sponsors'] = [false, true];

        $result = $this->formatter_update->format($this->createMockPresentation(), $change_set);

        $this->assertNotNull($result);
        $this->assertStringContainsString("Presentation '" . self::TITLE . "' (" . self::MOCK_ID . ") updated: 1 field(s) modified:", $result);
        $this->assertStringContainsString('Property "show_sponsors" has changed from "false" to "true"', $result);
        $this->assertStringNotContainsString('start_date', $result);
        $this->assertStringNotContainsString('end_date', $result);
        $this->assertStringNotContainsString('published_date', $result);
        $this->assertStringNotContainsString('attending_media', $result);
    }

    public function testPublishingSetsPublishedDateAndIsLogged(): void
    {
        $result = $this->formatter_update->format($this->createMockPresentation(), [
            'published_date' => [null, new \DateTime('2026-09-24 00:44:26')],
            'published'      => [false, true],
        ]);

        $this->assertNotNull($result);
        $this->assertStringContainsString('2 field(s) modified', $result);
        $this->assertStringContainsString('Property "published_date" has changed from "null" to "2026-09-24 00:44:26"', $result);
        $this->assertStringContainsString('Property "published" has changed from "false" to "true"', $result);
    }

    public function testUnpublishingClearsPublishedDateAndIsLogged(): void
    {
        $result = $this->formatter_update->format($this->createMockPresentation(), [
            'published_date' => [new \DateTime('2026-09-24 00:44:26'), null],
        ]);

        $this->assertNotNull($result);
        $this->assertStringContainsString('Property "published_date" has changed from "2026-09-24 00:44:26" to "null"', $result);
    }

    public function testMovedStartDateIsLogged(): void
    {
        $change_set = $this->republishNoiseChangeSet();
        $change_set['start_date'] = [new \DateTime('2026-10-12 21:30:00'), new \DateTime('2026-10-12 22:00:00')];

        $result = $this->formatter_update->format($this->createMockPresentation(), $change_set);

        $this->assertNotNull($result);
        $this->assertStringContainsString('1 field(s) modified', $result);
        $this->assertStringContainsString('Property "start_date" has changed from "2026-10-12 21:30:00" to "2026-10-12 22:00:00"', $result);
    }
}
