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
use models\summit\SpeakerSummitRegistrationDiscountCode;
use models\summit\SpeakerSummitRegistrationPromoCode;
use ModelSerializers\SpeakerSummitRegistrationDiscountCodeSerializer;
use ModelSerializers\SpeakerSummitRegistrationPromoCodeSerializer;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The promo-code endpoints themselves are admin-only, but these serializers are also reached
 * indirectly by non-admin callers: an edit-permission grantee expanding
 * registration_codes.owner_name on someone else's speaker, or an attendee expanding
 * promo_code.owner_name on a ticket paid with a speaker's code. Per policy Rule 9 the Member
 * name fallback in owner_name may bypass the account visibility toggle only for admin tooling
 * callers or the code owner themself - resolved by the shared AccountVisibilityToggleBypass
 * trait, never unconditionally.
 *
 * @package Tests
 */
final class SpeakerPromoCodeOwnerNameSerializerTest extends TestCase
{
    const OwnerMemberId = 42;
    const OtherMemberId = 99;

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public static function serializers(): array
    {
        return [
            'promo code' => [SpeakerSummitRegistrationPromoCodeSerializer::class, SpeakerSummitRegistrationPromoCode::class],
            'discount code' => [SpeakerSummitRegistrationDiscountCodeSerializer::class, SpeakerSummitRegistrationDiscountCode::class],
        ];
    }

    /**
     * The speaker mock only answers getFullName() for the expected override value; the other
     * value has no expectation and fails the test.
     */
    private function buildCode(string $code_class, bool $expectOverride)
    {
        $speaker = Mockery::mock(PresentationSpeaker::class);
        $speaker->shouldReceive('hasMember')->andReturn(true);
        $speaker->shouldReceive('getMemberId')->andReturn(self::OwnerMemberId);
        $speaker->shouldReceive('getFullName')->once()->with($expectOverride)->andReturn('Jane Doe');

        $code = Mockery::mock($code_class);
        $code->shouldReceive('getId')->andReturn(7);
        $code->shouldReceive('hasSpeaker')->andReturn(true);
        $code->shouldReceive('getSpeaker')->andReturn($speaker);
        return $code;
    }

    private function buildContext(bool $is_admin, bool $is_registration_admin, int $member_id): IResourceServerContext
    {
        $member = Mockery::mock(Member::class);
        $member->shouldReceive('isAdmin')->andReturn($is_admin);
        $member->shouldReceive('isSummitAdmin')->andReturn(false);
        $member->shouldReceive('isRegistrationAdmin')->andReturn($is_registration_admin);
        $member->shouldReceive('getId')->andReturn($member_id);

        /** @var IResourceServerContext&\Mockery\MockInterface $context */
        $context = Mockery::mock(IResourceServerContext::class);
        $context->shouldReceive('getCurrentUser')->andReturn($member);
        return $context;
    }

    private function serializeOwnerName(string $serializer_class, $code, IResourceServerContext $context): string
    {
        $serializer = new $serializer_class($code, $context);
        $values = $serializer->serialize('owner_name', ['id'], ['none']);
        return $values['owner_name'];
    }

    #[DataProvider('serializers')]
    public function testAdminCallerBypassesAccountVisibilityToggle(string $serializer_class, string $code_class)
    {
        $context = $this->buildContext(true, false, self::OtherMemberId);
        $this->assertSame('Jane Doe', $this->serializeOwnerName($serializer_class, $this->buildCode($code_class, true), $context));
    }

    #[DataProvider('serializers')]
    public function testRegistrationAdminCallerBypassesAccountVisibilityToggle(string $serializer_class, string $code_class)
    {
        $context = $this->buildContext(false, true, self::OtherMemberId);
        $this->assertSame('Jane Doe', $this->serializeOwnerName($serializer_class, $this->buildCode($code_class, true), $context));
    }

    #[DataProvider('serializers')]
    public function testCodeOwnerBypassesAccountVisibilityToggle(string $serializer_class, string $code_class)
    {
        $context = $this->buildContext(false, false, self::OwnerMemberId);
        $this->assertSame('Jane Doe', $this->serializeOwnerName($serializer_class, $this->buildCode($code_class, true), $context));
    }

    #[DataProvider('serializers')]
    public function testUnrelatedCallerHonorsAccountVisibilityToggle(string $serializer_class, string $code_class)
    {
        $context = $this->buildContext(false, false, self::OtherMemberId);
        $this->assertSame('Jane Doe', $this->serializeOwnerName($serializer_class, $this->buildCode($code_class, false), $context));
    }
}
