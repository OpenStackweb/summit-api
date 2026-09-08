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

use App\Audit\ConcreteFormatters\EntityUpdateAuditLogFormatter;
use App\Audit\ConcreteFormatters\FeaturedSpeakerAuditLogFormatter;
use App\Audit\ConcreteFormatters\PresentationFormatters\PresentationSpeakerAuditLogFormatter;
use App\Audit\ConcreteFormatters\PresentationFormatters\PresentationSpeakerSummitAssistanceConfirmationAuditLogFormatter;
use App\Audit\ConcreteFormatters\SpeakerAssistanceAuditLogFormatter;
use App\Audit\Interfaces\IAuditStrategy;
use App\Models\Foundation\Summit\Speakers\FeaturedSpeaker;
use Mockery;
use models\main\Member;
use models\summit\Presentation;
use models\summit\PresentationSpeaker;
use models\summit\PresentationSpeakerSummitAssistanceConfirmationRequest;
use models\summit\Summit;
use Tests\TestCase;

/**
 * Policy Rule 9 scope (policy/profile-data-handling.md Sec 2): the audit trail is internal,
 * admin-only tooling, so a speaker whose own name is empty must still be recorded under the
 * linked Member's name even when that Member's public_profile_show_fullname toggle is off.
 * Every formatter here must therefore read the name through the override
 * (getFirstName(true) / getLastName(true) / getFullName(true)); the bare getters honor the
 * toggle and would record a blank name in a compliance record.
 *
 * SubmissionInvitationAuditLogFormatter and SpeakerRegistrationRequestAuditLogFormatter also
 * read the speaker name, but never print it, so they have no observable behavior to pin here.
 *
 * @package Tests\OpenTelemetry\Formatters
 */
final class SpeakerNameMemberFallbackAuditLogFormatterTest extends TestCase
{
    const MemberFirstName = 'Ada';
    const MemberLastName = 'Lovelace';
    const MemberEmail = 'ada@example.com';

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * A speaker with no name of its own, linked to a Member whose full-name toggle is OFF.
     * The Member's name is stubbed (not omitted) so a formatter that drops the override
     * surfaces a blank instead of an unstubbed-call exception swallowed by its try/catch.
     */
    private function buildSpeakerRelyingOnMemberFallback(): PresentationSpeaker
    {
        $member = Mockery::mock(Member::class);
        $member->shouldReceive('getId')->andReturn(42);
        $member->shouldReceive('setSpeaker')->andReturnNull();
        $member->shouldReceive('isPublicProfileShowFullname')->andReturn(false);
        $member->shouldReceive('getFirstName')->andReturn(self::MemberFirstName);
        $member->shouldReceive('getLastName')->andReturn(self::MemberLastName);
        $member->shouldReceive('getFullName')->andReturn(self::MemberFirstName . ' ' . self::MemberLastName);
        $member->shouldReceive('getEmail')->andReturn(self::MemberEmail);

        $speaker = new PresentationSpeaker();
        $speaker->setMember($member);
        return $speaker;
    }

    private function buildSummit(): Summit
    {
        /** @var Summit&\Mockery\MockInterface $summit */
        $summit = Mockery::mock(Summit::class);
        $summit->shouldReceive('getName')->andReturn('Test Summit');
        return $summit;
    }

    public function testSpeakerAssistanceFormatterRecordsMemberNameWhenToggleIsOff()
    {
        $subject = Mockery::mock(PresentationSpeakerSummitAssistanceConfirmationRequest::class);
        $subject->shouldReceive('getSpeaker')->andReturn($this->buildSpeakerRelyingOnMemberFallback());
        $subject->shouldReceive('getSummit')->andReturn($this->buildSummit());
        $subject->shouldReceive('isConfirmed')->andReturn(true);
        $subject->shouldReceive('isRegistered')->andReturn(false);

        $formatter = new SpeakerAssistanceAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_CREATION);
        $result = $formatter->format($subject, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString("for 'Ada Lovelace'", $result);
    }

    public function testFeaturedSpeakerFormatterRecordsMemberNameWhenToggleIsOff()
    {
        $subject = Mockery::mock(FeaturedSpeaker::class);
        $subject->shouldReceive('getSpeaker')->andReturn($this->buildSpeakerRelyingOnMemberFallback());
        $subject->shouldReceive('getSummit')->andReturn($this->buildSummit());
        $subject->shouldReceive('getOrder')->andReturn(1);

        $formatter = new FeaturedSpeakerAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_CREATION);
        $result = $formatter->format($subject, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString("Speaker 'Ada Lovelace'", $result);
    }

    public function testPresentationSpeakerFormatterRecordsMemberNameWhenToggleIsOff()
    {
        $formatter = new PresentationSpeakerAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_CREATION);
        $result = $formatter->format($this->buildSpeakerRelyingOnMemberFallback(), []);

        $this->assertNotNull($result);
        $this->assertStringContainsString("Speaker 'Ada Lovelace'", $result);
    }

    public function testAssistanceConfirmationFormatterRecordsMemberNameWhenToggleIsOff()
    {
        $subject = Mockery::mock(PresentationSpeakerSummitAssistanceConfirmationRequest::class);
        $subject->shouldReceive('getId')->andReturn(7);
        $subject->shouldReceive('getSpeaker')->andReturn($this->buildSpeakerRelyingOnMemberFallback());
        $subject->shouldReceive('getSummit')->andReturn($this->buildSummit());

        $formatter = new PresentationSpeakerSummitAssistanceConfirmationAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_CREATION);
        $result = $formatter->format($subject, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString("for 'Ada Lovelace'", $result);
    }

    public function testEntityUpdateFormatterRecordsMemberNameWhenToggleIsOff()
    {
        $presentation = Mockery::mock(Presentation::class);
        $presentation->shouldReceive('getId')->andReturn(99);

        $formatter = new EntityUpdateAuditLogFormatter();
        $result = $formatter->format(
            $presentation,
            ['moderator' => [null, $this->buildSpeakerRelyingOnMemberFallback()]]
        );

        $this->assertNotNull($result);
        $this->assertStringContainsString('Ada Lovelace (' . self::MemberEmail . ')', $result);
    }
}
