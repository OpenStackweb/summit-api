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

use models\main\Member;
use models\oauth2\IResourceServerContext;
use models\summit\PresentationSpeaker;
use ModelSerializers\AdminPresentationSpeakerSerializer;
use Mockery;

/**
 * AdminPresentationSpeakerSerializer is selected for three different callers
 * (OAuth2SummitSpeakersApiController::getSpeaker/getMySpeaker/getMySummitSpeaker): an
 * Admin/SummitAdmin, the speaker viewing/editing their own record, and a submitter who only
 * holds an approved edit-permission request on someone else's speaker profile
 * (PresentationSpeaker::canBeEditedBy()). Per policy/profile-data-handling.md Rule 9 + Sec 2
 * Scope, only the first two may bypass the account visibility toggle on the Member name/photo
 * fallback - the third is neither an admin nor the account owner and must see exactly what a
 * Public caller would see.
 *
 * @package Tests
 */
final class AdminPresentationSpeakerSerializerTest extends TestCase
{
    const OwnerMemberId = 42;
    const OtherMemberId = 99;

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function buildSpeaker(bool $expectOverride): PresentationSpeaker
    {
        /** @var PresentationSpeaker&\Mockery\MockInterface $speaker */
        $speaker = Mockery::mock(PresentationSpeaker::class);
        $speaker->shouldReceive('hasMember')->andReturn(true);
        $speaker->shouldReceive('getMember')->andReturn(Mockery::mock(Member::class));
        $speaker->shouldReceive('getMemberId')->andReturn(self::OwnerMemberId);
        // The generic array_mappings reflection in AbstractSerializer::serialize() calls
        // getFirstName() with no arguments first (its return value is discarded - overwritten
        // below by the admin-serializer's explicit call); the value under test is the second,
        // explicit call with the computed override.
        $speaker->shouldReceive('getFirstName')->with()->andReturn('');
        $speaker->shouldReceive('getFirstName')->once()->with($expectOverride)->andReturn('Jane');
        return $speaker;
    }

    private function buildContext(Member $current_member): IResourceServerContext
    {
        /** @var IResourceServerContext&\Mockery\MockInterface $context */
        $context = Mockery::mock(IResourceServerContext::class);
        $context->shouldReceive('getCurrentUser')->andReturn($current_member);
        return $context;
    }

    public function testAdminCallerBypassesAccountVisibilityToggle()
    {
        $admin = Mockery::mock(Member::class);
        $admin->shouldReceive('isAdmin')->andReturn(true);
        $admin->shouldReceive('isSummitAdmin')->andReturn(false);
        $admin->shouldReceive('getId')->andReturn(self::OtherMemberId);

        $speaker = $this->buildSpeaker(true);
        $serializer = new AdminPresentationSpeakerSerializer($speaker, $this->buildContext($admin));

        $values = $serializer->serialize(null, ['first_name'], ['none']);

        $this->assertSame('Jane', $values['first_name']);
    }

    public function testSpeakerViewingOwnRecordBypassesAccountVisibilityToggle()
    {
        $owner = Mockery::mock(Member::class);
        $owner->shouldReceive('isAdmin')->andReturn(false);
        $owner->shouldReceive('isSummitAdmin')->andReturn(false);
        $owner->shouldReceive('getId')->andReturn(self::OwnerMemberId);

        $speaker = $this->buildSpeaker(true);
        $serializer = new AdminPresentationSpeakerSerializer($speaker, $this->buildContext($owner));

        $values = $serializer->serialize(null, ['first_name'], ['none']);

        $this->assertSame('Jane', $values['first_name']);
    }

    /**
     * The regression this pins: PresentationSpeaker::canBeEditedBy() grants this same serializer
     * to a submitter with an approved edit-permission request who is neither the account owner
     * nor an admin. Before the fix, AdminPresentationSpeakerSerializer bypassed the toggle
     * unconditionally for every caller reaching it - this submitter must NOT get that bypass.
     */
    public function testEditPermissionGranteeWhoIsNotOwnerDoesNotBypassAccountVisibilityToggle()
    {
        $submitter = Mockery::mock(Member::class);
        $submitter->shouldReceive('isAdmin')->andReturn(false);
        $submitter->shouldReceive('isSummitAdmin')->andReturn(false);
        $submitter->shouldReceive('getId')->andReturn(self::OtherMemberId);

        $speaker = $this->buildSpeaker(false);
        $serializer = new AdminPresentationSpeakerSerializer($speaker, $this->buildContext($submitter));

        $values = $serializer->serialize(null, ['first_name'], ['none']);

        $this->assertSame('Jane', $values['first_name']);
    }
}
