<?php namespace Tests;
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

use models\main\Member;
use models\summit\PresentationSpeaker;
use models\summit\SpeakerRegistrationRequest;
use models\summit\SpeakersSummitRegistrationPromoCode;
use App\Models\Foundation\Summit\PromoCodes\PromoCodesConstants;

/**
 * Covers SpeakersPromoCodeTrait::setEmailSent, which resolves the assignment of a speakers
 * promo code by recipient e-mail.
 *
 * The recipient string is produced by PresentationSpeaker::getEmail(), which is NOT a mapped
 * column: it returns the member's e-mail when the speaker has a member, and only falls back to
 * the speaker registration request's e-mail when there is none. Any lookup that resolves the
 * assignment from that string has to reproduce that precedence exactly, so both branches are
 * exercised here, together with the case-insensitivity the original comparison had.
 *
 * Class SpeakersPromoCodeMarkSentTest
 */
class SpeakersPromoCodeMarkSentTest extends ProtectedApiTestCase
{
    use InsertSummitTestData;

    /** @var SpeakersSummitRegistrationPromoCode */
    private $promo_code;

    /** @var PresentationSpeaker speaker whose e-mail comes from its Member */
    private $speaker_with_member;

    /** @var PresentationSpeaker speaker whose e-mail comes from its registration request */
    private $speaker_with_request;

    /** @var PresentationSpeaker speaker that has BOTH a member and a registration request */
    private $speaker_with_both;

    /** @var SpeakersSummitRegistrationPromoCode a second code the same speaker is assigned to */
    private $other_promo_code;

    private $member_email;
    private $request_email;
    private $both_member_email;
    private $both_request_email;

    protected function setUp(): void
    {
        parent::setUp();
        self::insertSummitTestData();

        $em = self::$em;
        $prefix = str_random(10);

        $this->member_email  = "speaker+member_{$prefix}@test.com";
        $this->request_email = "speaker+request_{$prefix}@test.com";

        // speaker A: has a Member, so getEmail() resolves to the member's e-mail
        $member = new Member();
        $member->setEmail($this->member_email);
        $member->setActive(true);
        $member->setFirstName("Member");
        $member->setLastName("Speaker");
        $member->setEmailVerified(true);
        $member->setUserExternalId(mt_rand());
        $em->persist($member);

        $this->speaker_with_member = new PresentationSpeaker();
        $this->speaker_with_member->setFirstName("Member");
        $this->speaker_with_member->setLastName("Speaker");
        $this->speaker_with_member->setMember($member);
        $em->persist($this->speaker_with_member);

        // speaker B: no Member, so getEmail() falls back to the registration request
        $this->speaker_with_request = new PresentationSpeaker();
        $this->speaker_with_request->setFirstName("Request");
        $this->speaker_with_request->setLastName("Speaker");
        $em->persist($this->speaker_with_request);

        $request = new SpeakerRegistrationRequest();
        $request->setEmail($this->request_email);
        $request->setSpeaker($this->speaker_with_request);
        $em->persist($request);
        $this->speaker_with_request->setRegistrationRequest($request);

        // speaker C: has both. getEmail() must resolve to the member's e-mail and the
        // registration request's must never match, otherwise the wrong assignment is marked.
        $this->both_member_email  = "speaker+both_member_{$prefix}@test.com";
        $this->both_request_email = "speaker+both_request_{$prefix}@test.com";

        $both_member = new Member();
        $both_member->setEmail($this->both_member_email);
        $both_member->setActive(true);
        $both_member->setFirstName("Both");
        $both_member->setLastName("Speaker");
        $both_member->setEmailVerified(true);
        $both_member->setUserExternalId(mt_rand());
        $em->persist($both_member);

        $this->speaker_with_both = new PresentationSpeaker();
        $this->speaker_with_both->setFirstName("Both");
        $this->speaker_with_both->setLastName("Speaker");
        $this->speaker_with_both->setMember($both_member);
        $em->persist($this->speaker_with_both);

        $both_request = new SpeakerRegistrationRequest();
        $both_request->setEmail($this->both_request_email);
        $both_request->setSpeaker($this->speaker_with_both);
        $em->persist($both_request);
        $this->speaker_with_both->setRegistrationRequest($both_request);

        $this->promo_code = new SpeakersSummitRegistrationPromoCode();
        $this->promo_code->setCode("SPKTEST_{$prefix}");
        $this->promo_code->setType(PromoCodesConstants::SpeakerSummitRegistrationPromoCodeTypeAccepted);
        self::$summit->addPromoCode($this->promo_code);
        $em->persist($this->promo_code);

        $this->promo_code->assignSpeaker($this->speaker_with_member);
        $this->promo_code->assignSpeaker($this->speaker_with_request);
        $this->promo_code->assignSpeaker($this->speaker_with_both);

        // the same speaker assigned to a second code: marking one must never touch the other.
        // The original in-memory lookup got this scoping implicitly from $this->owners.
        $this->other_promo_code = new SpeakersSummitRegistrationPromoCode();
        $this->other_promo_code->setCode("SPKOTHER_{$prefix}");
        $this->other_promo_code->setType(PromoCodesConstants::SpeakerSummitRegistrationPromoCodeTypeAccepted);
        self::$summit->addPromoCode($this->other_promo_code);
        $em->persist($this->other_promo_code);
        $this->other_promo_code->assignSpeaker($this->speaker_with_member);

        $em->flush();
    }

    protected function tearDown(): void
    {
        self::clearSummitTestData();
        parent::tearDown();
    }

    public function testMarkSentResolvesTheSpeakerWhoseEmailComesFromItsMember(): void
    {
        $this->promo_code->markSent($this->member_email);

        $this->assertTrue(
            $this->promo_code->getSpeakerAssignment($this->speaker_with_member)->isSent(),
            'the assignment of the speaker matching the recipient must be marked as sent'
        );
        $this->assertFalse(
            $this->promo_code->getSpeakerAssignment($this->speaker_with_request)->isSent(),
            'assignments of other speakers must stay untouched'
        );
    }

    public function testMarkSentFallsBackToTheSpeakerRegistrationRequestEmail(): void
    {
        $this->promo_code->markSent($this->request_email);

        $this->assertTrue(
            $this->promo_code->getSpeakerAssignment($this->speaker_with_request)->isSent(),
            'a speaker without a member must still be resolved by its registration request e-mail'
        );
        $this->assertFalse(
            $this->promo_code->getSpeakerAssignment($this->speaker_with_member)->isSent(),
            'assignments of other speakers must stay untouched'
        );
    }

    public function testMarkSentIsCaseInsensitive(): void
    {
        $this->promo_code->markSent(strtoupper($this->member_email));

        $this->assertTrue(
            $this->promo_code->getSpeakerAssignment($this->speaker_with_member)->isSent(),
            'the recipient comparison must ignore case'
        );
    }

    public function testMarkSentPrefersTheMemberEmailWhenTheSpeakerHasBoth(): void
    {
        $this->promo_code->markSent($this->both_member_email);

        $this->assertTrue(
            $this->promo_code->getSpeakerAssignment($this->speaker_with_both)->isSent(),
            'a speaker with both must be resolved by its member e-mail, which is what getEmail() returns'
        );
    }

    public function testMarkSentIgnoresTheRegistrationRequestEmailWhenTheSpeakerHasAMember(): void
    {
        // getEmail() would never produce this address for this speaker, so nothing must match:
        // matching it would mark an assignment the caller never asked for.
        $this->promo_code->markSent($this->both_request_email);

        $this->assertFalse(
            $this->promo_code->getSpeakerAssignment($this->speaker_with_both)->isSent(),
            'the registration request e-mail must not resolve a speaker that has a member'
        );
    }

    public function testMarkSentOnlyTouchesTheAssignmentsOfThisPromoCode(): void
    {
        $this->promo_code->markSent($this->member_email);

        $this->assertTrue(
            $this->promo_code->getSpeakerAssignment($this->speaker_with_member)->isSent(),
            'the assignment on the code being marked must be sent'
        );
        $this->assertFalse(
            $this->other_promo_code->getSpeakerAssignment($this->speaker_with_member)->isSent(),
            'the assignment of the same speaker on a different code must stay untouched'
        );
    }

    public function testMarkSentWithAnUnknownRecipientLeavesEveryAssignmentUntouched(): void
    {
        $this->promo_code->markSent("nobody+unknown@test.com");

        $this->assertFalse(
            $this->promo_code->getSpeakerAssignment($this->speaker_with_member)->isSent(),
            'an unknown recipient must not mark any assignment'
        );
        $this->assertFalse(
            $this->promo_code->getSpeakerAssignment($this->speaker_with_request)->isSent(),
            'an unknown recipient must not mark any assignment'
        );
    }
}
