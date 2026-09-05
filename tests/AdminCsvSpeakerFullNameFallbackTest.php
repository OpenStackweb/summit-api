<?php namespace Tests;
/*
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

use Doctrine\Common\Collections\ArrayCollection;
use models\main\Member;
use models\oauth2\IResourceServerContext;
use models\summit\AssignedPromoCodeSpeaker;
use models\summit\Presentation;
use models\summit\PresentationSpeaker;
use models\summit\Summit;
use models\summit\SpeakersRegistrationDiscountCode;
use models\summit\SpeakersSummitRegistrationPromoCode;
use ModelSerializers\AdminPresentationCSVSerializer;
use ModelSerializers\SpeakersRegistrationDiscountCodeCSVSerializer;
use ModelSerializers\SpeakersSummitRegistrationPromoCodeCSVSerializer;
use ModelSerializers\TrackChairPresentationCSVSerializer;
use Mockery;

/**
 * Policy Rule 9 (docs vault policy/profile-data-handling.md): a borrowed-from-account name
 * fallback must honor the account visibility toggle everywhere EXCEPT admin/track-chair-only
 * tooling, which is explicitly out of scope of that policy. These CSV export serializers are
 * exactly that tooling, so each one must call PresentationSpeaker::getFullName(true) - the
 * override that bypasses the toggle - rather than the un-overridden getFullName(). Each speaker
 * mock here only answers to getFullName(true); calling the bare getFullName() has no matching
 * expectation and fails the test, which is what pins the regression this covers.
 *
 * @package Tests
 */
final class AdminCsvSpeakerFullNameFallbackTest extends TestCase
{
    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function buildResourceServerContext(): IResourceServerContext
    {
        // TrackChairPresentationSerializer::serialize() passes this straight into
        // Summit::getTrackChairByMember(Member $member), a non-nullable parameter, so this
        // has to be a real Member mock rather than null even though none of these tests are
        // about track-chair identity.
        $current_user = Mockery::mock(Member::class);

        $context = Mockery::mock(IResourceServerContext::class);
        $context->shouldReceive('getApplicationType')->andReturn('JS_CLIENT');
        $context->shouldReceive('getCurrentUser')->andReturn($current_user);
        return $context;
    }

    private function buildSpeaker(string $full_name): PresentationSpeaker
    {
        $speaker = Mockery::mock(PresentationSpeaker::class);
        $speaker->shouldReceive('getFullName')->once()->with(true)->andReturn($full_name);
        $speaker->shouldReceive('getId')->andReturn(1);
        $speaker->shouldReceive('getEmail')->andReturn('speaker@example.com');
        $speaker->shouldReceive('getTitle')->andReturn('');
        $speaker->shouldReceive('getCompany')->andReturn('');
        $speaker->shouldReceive('getCountry')->andReturn('');
        return $speaker;
    }

    private function buildPresentation
    (
        int $id,
        PresentationSpeaker $moderator,
        PresentationSpeaker $speaker,
        PresentationSpeaker $submitter
    ): Presentation
    {
        $creator = Mockery::mock(Member::class);
        $creator->shouldReceive('hasSpeaker')->andReturn(true);
        $creator->shouldReceive('getSpeaker')->andReturn($submitter);

        $summit = Mockery::mock(Summit::class);
        $summit->shouldReceive('getTrackChairByMember')->andReturn(null);

        $presentation = Mockery::mock(Presentation::class);
        $presentation->shouldReceive('getId')->andReturn($id);
        $presentation->shouldReceive('getLastEditedUTC')->andReturn(null);
        $presentation->shouldReceive('hasModerator')->andReturn(true);
        $presentation->shouldReceive('getModerator')->andReturn($moderator);
        $presentation->shouldReceive('getSpeakers')->andReturn(new ArrayCollection([$speaker]));
        $presentation->shouldReceive('hasCreatedBy')->andReturn(true);
        $presentation->shouldReceive('getCreatedBy')->andReturn($creator);
        $presentation->shouldReceive('getMediaUploads')->andReturn(new ArrayCollection([]));
        $presentation->shouldReceive('getSummit')->andReturn($summit);
        $presentation->shouldReceive('getExtraQuestionAnswers')->andReturn(new ArrayCollection([]));
        $presentation->shouldReceive('hasCategory')->andReturn(false);
        $presentation->shouldReceive('getPresentationActions')->andReturn(new ArrayCollection([]));

        return $presentation;
    }

    public function testAdminPresentationCSVSerializerBypassesToggleForModeratorSpeakerAndSubmitter()
    {
        $moderator = $this->buildSpeaker('Moderator Real Name');
        $speaker = $this->buildSpeaker('Co-Speaker Real Name');
        $submitter = $this->buildSpeaker('Submitter Real Name');
        $presentation = $this->buildPresentation(80101, $moderator, $speaker, $submitter);

        $serializer = new AdminPresentationCSVSerializer($presentation, $this->buildResourceServerContext());
        $values = $serializer->serialize(
            null,
            ['moderator_full_name', 'speaker_fullnames', 'submitter_full_name'],
            ['none']
        );

        $this->assertSame('Moderator Real Name', $values['moderator_full_name']);
        $this->assertSame('Co-Speaker Real Name', $values['speaker_fullnames']);
        $this->assertSame('Submitter Real Name', $values['submitter_full_name']);
    }

    public function testTrackChairPresentationCSVSerializerBypassesToggleForModeratorSpeakerAndSubmitter()
    {
        $moderator = $this->buildSpeaker('Moderator Real Name');
        $speaker = $this->buildSpeaker('Co-Speaker Real Name');
        $submitter = $this->buildSpeaker('Submitter Real Name');
        $presentation = $this->buildPresentation(80102, $moderator, $speaker, $submitter);

        $serializer = new TrackChairPresentationCSVSerializer($presentation, $this->buildResourceServerContext());
        $values = $serializer->serialize(null, ['id'], ['none']);

        $this->assertSame('Moderator Real Name', $values['moderator_full_name']);
        $this->assertSame('Co-Speaker Real Name', $values['speaker_fullnames']);
        $this->assertSame('Submitter Real Name', $values['submitter_full_name']);
    }

    public function testSpeakersRegistrationDiscountCodeCSVSerializerBypassesToggleForOwner()
    {
        $owner_speaker = $this->buildSpeaker('Owner Real Name');
        $owner = Mockery::mock(AssignedPromoCodeSpeaker::class);
        $owner->shouldReceive('getSpeaker')->andReturn($owner_speaker);

        $code = Mockery::mock(SpeakersRegistrationDiscountCode::class);
        $code->shouldReceive('getOwners')->andReturn(new ArrayCollection([$owner]));
        $code->shouldReceive('getBadgeFeatures')->andReturn(new ArrayCollection([]));
        $code->shouldReceive('getTicketTypesRules')->andReturn(new ArrayCollection([]));
        $code->shouldReceive('getTags')->andReturn(new ArrayCollection([]));
        $code->shouldReceive('isInfinite')->andReturn(false);

        $serializer = new SpeakersRegistrationDiscountCodeCSVSerializer($code, $this->buildResourceServerContext());
        $values = $serializer->serialize(null, ['owner_name'], ['none']);

        $this->assertSame('Owner Real Name', $values['owner_name']);
    }

    public function testSpeakersSummitRegistrationPromoCodeCSVSerializerBypassesToggleForOwner()
    {
        $owner_speaker = $this->buildSpeaker('Owner Real Name');
        $owner = Mockery::mock(AssignedPromoCodeSpeaker::class);
        $owner->shouldReceive('getSpeaker')->andReturn($owner_speaker);

        $code = Mockery::mock(SpeakersSummitRegistrationPromoCode::class);
        $code->shouldReceive('getOwners')->andReturn(new ArrayCollection([$owner]));
        $code->shouldReceive('getBadgeFeatures')->andReturn(new ArrayCollection([]));
        $code->shouldReceive('getAllowedTicketTypes')->andReturn(new ArrayCollection([]));
        $code->shouldReceive('getTags')->andReturn(new ArrayCollection([]));
        $code->shouldReceive('isInfinite')->andReturn(false);

        $serializer = new SpeakersSummitRegistrationPromoCodeCSVSerializer($code, $this->buildResourceServerContext());
        $values = $serializer->serialize(null, ['owner_name'], ['none']);

        $this->assertSame('Owner Real Name', $values['owner_name']);
    }
}
