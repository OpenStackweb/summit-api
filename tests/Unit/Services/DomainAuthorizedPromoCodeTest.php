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

use App\Models\Foundation\Summit\Registration\PromoCodes\Strategies\RegularPromoCodeTicketTypesStrategy;
use Doctrine\Common\Collections\ArrayCollection;
use models\exceptions\ValidationException;
use models\summit\DomainAuthorizedSummitRegistrationDiscountCode;
use models\summit\DomainAuthorizedSummitRegistrationPromoCode;
use models\summit\MemberSummitRegistrationDiscountCode;
use models\summit\MemberSummitRegistrationPromoCode;
use models\summit\SpeakerSummitRegistrationDiscountCode;
use models\summit\SpeakerSummitRegistrationPromoCode;
use models\summit\Summit;
use models\summit\SummitRegistrationDiscountCodeTicketTypeRule;
use models\summit\SummitRegistrationPromoCode;
use models\summit\SummitTicketType;
use models\main\Member;
use ModelSerializers\SerializerRegistry;
use Tests\TestCase;

/**
 * Class DomainAuthorizedPromoCodeTest
 * Unit tests for domain matching, audience filtering, and collision avoidance.
 * @package Tests\Unit\Services
 */
class DomainAuthorizedPromoCodeTest extends TestCase
{
    // -----------------------------------------------------------------------
    // Domain matching — DomainAuthorizedPromoCodeTrait::matchesEmailDomain
    // -----------------------------------------------------------------------

    public function testExactDomainMatchSucceeds(): void
    {
        $code = new DomainAuthorizedSummitRegistrationPromoCode();
        $code->setAllowedEmailDomains(['@acme.com']);
        $this->assertTrue($code->matchesEmailDomain('user@acme.com'));
    }

    public function testExactDomainMatchRejectsOtherDomain(): void
    {
        $code = new DomainAuthorizedSummitRegistrationPromoCode();
        $code->setAllowedEmailDomains(['@acme.com']);
        $this->assertFalse($code->matchesEmailDomain('user@other.com'));
    }

    public function testTldSuffixMatchSucceeds(): void
    {
        $code = new DomainAuthorizedSummitRegistrationPromoCode();
        $code->setAllowedEmailDomains(['.edu']);
        $this->assertTrue($code->matchesEmailDomain('user@mit.edu'));
        $this->assertTrue($code->matchesEmailDomain('user@cs.stanford.edu'));
    }

    public function testTldSuffixMatchRejectsNonMatching(): void
    {
        $code = new DomainAuthorizedSummitRegistrationPromoCode();
        $code->setAllowedEmailDomains(['.edu']);
        $this->assertFalse($code->matchesEmailDomain('user@acme.com'));
    }

    public function testGovSuffixMatch(): void
    {
        $code = new DomainAuthorizedSummitRegistrationPromoCode();
        $code->setAllowedEmailDomains(['.gov']);
        $this->assertTrue($code->matchesEmailDomain('user@agency.gov'));
    }

    public function testExactEmailMatch(): void
    {
        $code = new DomainAuthorizedSummitRegistrationPromoCode();
        $code->setAllowedEmailDomains(['specific@email.com']);
        $this->assertTrue($code->matchesEmailDomain('specific@email.com'));
        $this->assertFalse($code->matchesEmailDomain('other@email.com'));
    }

    public function testCaseInsensitiveMatching(): void
    {
        $code = new DomainAuthorizedSummitRegistrationPromoCode();
        $code->setAllowedEmailDomains(['@ACME.COM']);
        $this->assertTrue($code->matchesEmailDomain('user@acme.com'));
        $this->assertTrue($code->matchesEmailDomain('USER@ACME.COM'));
    }

    public function testEmptyDomainsPassesAll(): void
    {
        $code = new DomainAuthorizedSummitRegistrationPromoCode();
        $code->setAllowedEmailDomains([]);
        $this->assertTrue($code->matchesEmailDomain('anyone@anywhere.com'));
    }

    public function testMultiplePatternsMatchesAny(): void
    {
        $code = new DomainAuthorizedSummitRegistrationPromoCode();
        $code->setAllowedEmailDomains(['@acme.com', '.edu', 'vip@special.org']);

        $this->assertTrue($code->matchesEmailDomain('user@acme.com'));
        $this->assertTrue($code->matchesEmailDomain('student@mit.edu'));
        $this->assertTrue($code->matchesEmailDomain('vip@special.org'));
        $this->assertFalse($code->matchesEmailDomain('nobody@random.net'));
    }

    public function testEmptyEmailReturnsFalse(): void
    {
        $code = new DomainAuthorizedSummitRegistrationPromoCode();
        $code->setAllowedEmailDomains(['@acme.com']);
        $this->assertFalse($code->matchesEmailDomain(''));
    }

    // -----------------------------------------------------------------------
    // checkSubject — throws ValidationException on failure
    // -----------------------------------------------------------------------

    public function testCheckSubjectThrowsForNonMatchingEmail(): void
    {
        $code = new DomainAuthorizedSummitRegistrationPromoCode();
        $code->setAllowedEmailDomains(['@acme.com']);

        $this->expectException(ValidationException::class);
        $code->checkSubject('user@other.com', null);
    }

    public function testCheckSubjectSucceedsForMatchingEmail(): void
    {
        $code = new DomainAuthorizedSummitRegistrationPromoCode();
        $code->setAllowedEmailDomains(['@acme.com']);

        $result = $code->checkSubject('user@acme.com', null);
        $this->assertTrue($result);
    }

    // -----------------------------------------------------------------------
    // AutoApplyPromoCodeTrait
    // -----------------------------------------------------------------------

    public function testAutoApplyDefaultsFalse(): void
    {
        $code = new DomainAuthorizedSummitRegistrationPromoCode();
        $this->assertFalse($code->getAutoApply());
    }

    public function testAutoApplyCanBeSet(): void
    {
        $code = new DomainAuthorizedSummitRegistrationPromoCode();
        $code->setAutoApply(true);
        $this->assertTrue($code->getAutoApply());
    }

    // -----------------------------------------------------------------------
    // QuantityPerAccount
    // -----------------------------------------------------------------------

    public function testQuantityPerAccountDefaultsToZero(): void
    {
        $code = new DomainAuthorizedSummitRegistrationPromoCode();
        $this->assertEquals(0, $code->getQuantityPerAccount());
    }

    public function testQuantityPerAccountCanBeSet(): void
    {
        $code = new DomainAuthorizedSummitRegistrationPromoCode();
        $code->setQuantityPerAccount(5);
        $this->assertEquals(5, $code->getQuantityPerAccount());
    }

    // -----------------------------------------------------------------------
    // ClassName constants
    // -----------------------------------------------------------------------

    public function testDiscountCodeClassName(): void
    {
        $code = new DomainAuthorizedSummitRegistrationDiscountCode();
        $this->assertEquals('DOMAIN_AUTHORIZED_DISCOUNT_CODE', $code->getClassName());
    }

    public function testPromoCodeClassName(): void
    {
        $code = new DomainAuthorizedSummitRegistrationPromoCode();
        $this->assertEquals('DOMAIN_AUTHORIZED_PROMO_CODE', $code->getClassName());
    }

    // -----------------------------------------------------------------------
    // IDomainAuthorizedPromoCode interface
    // -----------------------------------------------------------------------

    public function testImplementsInterface(): void
    {
        $discountCode = new DomainAuthorizedSummitRegistrationDiscountCode();
        $promoCode = new DomainAuthorizedSummitRegistrationPromoCode();

        $this->assertInstanceOf(\models\summit\IDomainAuthorizedPromoCode::class, $discountCode);
        $this->assertInstanceOf(\models\summit\IDomainAuthorizedPromoCode::class, $promoCode);
    }

    // -----------------------------------------------------------------------
    // SummitTicketType — WithPromoCode audience
    // -----------------------------------------------------------------------

    public function testWithPromoCodeAudienceConstant(): void
    {
        $this->assertEquals('WithPromoCode', SummitTicketType::Audience_With_Promo_Code);
        $this->assertContains('WithPromoCode', SummitTicketType::AllowedAudience);
    }

    // -----------------------------------------------------------------------
    // RemainingQuantityPerAccount transient property
    // -----------------------------------------------------------------------

    public function testRemainingQuantityPerAccountTransient(): void
    {
        $code = new DomainAuthorizedSummitRegistrationPromoCode();
        $this->assertNull($code->getRemainingQuantityPerAccount());

        $code->setRemainingQuantityPerAccount(3);
        $this->assertEquals(3, $code->getRemainingQuantityPerAccount());
    }

    // -----------------------------------------------------------------------
    // Domain matching on discount code variant
    // -----------------------------------------------------------------------

    public function testDiscountCodeDomainMatching(): void
    {
        $code = new DomainAuthorizedSummitRegistrationDiscountCode();
        $code->setAllowedEmailDomains(['@partner.com', '.edu']);

        $this->assertTrue($code->matchesEmailDomain('user@partner.com'));
        $this->assertTrue($code->matchesEmailDomain('student@university.edu'));
        $this->assertFalse($code->matchesEmailDomain('user@random.org'));
    }

    // -----------------------------------------------------------------------
    // RegularPromoCodeTicketTypesStrategy — audience filtering
    // -----------------------------------------------------------------------

    private function buildMockSummit(array $audienceAllTypes = [], array $audienceWithoutInvitationTypes = []): Summit
    {
        $summit = $this->createMock(Summit::class);
        $summit->method('getId')->willReturn(1);
        $summit->method('getSummitRegistrationInvitationByEmail')->willReturn(null);

        $summit->method('getTicketTypesByAudience')->willReturnCallback(
            function (string $audience) use ($audienceAllTypes, $audienceWithoutInvitationTypes) {
                if ($audience === SummitTicketType::Audience_All) {
                    return new ArrayCollection($audienceAllTypes);
                }
                if ($audience === SummitTicketType::Audience_Without_Invitation) {
                    return new ArrayCollection($audienceWithoutInvitationTypes);
                }
                return new ArrayCollection();
            }
        );

        return $summit;
    }

    private function buildMockMember(string $email = 'user@test.com'): Member
    {
        $member = $this->createMock(Member::class);
        $member->method('getId')->willReturn(1);
        $member->method('getEmail')->willReturn($email);
        $member->method('getCompany')->willReturn(null);
        return $member;
    }

    private function buildMockTicketType(int $id, string $audience, bool $canSell = true): SummitTicketType
    {
        $tt = $this->createMock(SummitTicketType::class);
        $tt->method('getId')->willReturn($id);
        $tt->method('getAudience')->willReturn($audience);
        $tt->method('canSell')->willReturn($canSell);
        $tt->method('isSoldOut')->willReturn(!$canSell);
        $tt->method('isPromoCodeOnly')->willReturn($audience === SummitTicketType::Audience_With_Promo_Code);
        return $tt;
    }

    /**
     * WithPromoCode ticket type + no promo code → NOT returned;
     * Audience_All type IS returned (proves strategy returns results, but filters WithPromoCode).
     */
    public function testWithPromoCodeAudienceNoPromoCodeNotReturned(): void
    {
        $allTT = $this->buildMockTicketType(30, SummitTicketType::Audience_All);
        $summit = $this->buildMockSummit([$allTT]);
        $member = $this->buildMockMember();

        $strategy = new RegularPromoCodeTicketTypesStrategy($summit, $member, null);
        $result = $strategy->getTicketTypes();

        $ids = array_map(fn($tt) => $tt->getId(), $result);
        // Audience_All type IS returned (non-vacuous: proves the strategy produces results)
        $this->assertContains(30, $ids, 'Audience_All ticket type should be returned without a promo code');
        // WithPromoCode type (id 99) is NOT returned — it only lives in promo_code->getAllowedTicketTypes()
        $this->assertNotContains(99, $ids, 'WithPromoCode ticket types should not be returned without a promo code');
    }

    /**
     * WithPromoCode ticket type + live promo code → IS returned
     */
    public function testWithPromoCodeAudienceLivePromoCodeReturned(): void
    {
        $promoCodeTicket = $this->buildMockTicketType(10, SummitTicketType::Audience_With_Promo_Code);

        $promoCode = $this->createMock(SummitRegistrationPromoCode::class);
        $promoCode->method('getCode')->willReturn('DOMAIN-CODE');
        $promoCode->method('isLive')->willReturn(true);
        $promoCode->method('getAllowedTicketTypes')->willReturn(new ArrayCollection([$promoCodeTicket]));
        $promoCode->method('canBeAppliedTo')->willReturn(true);
        $promoCode->method('validate')->willReturn(true);

        $summit = $this->buildMockSummit();
        $member = $this->buildMockMember();

        $strategy = new RegularPromoCodeTicketTypesStrategy($summit, $member, $promoCode);
        $result = $strategy->getTicketTypes();

        $ids = array_map(fn($tt) => $tt->getId(), $result);
        $this->assertContains(10, $ids, 'WithPromoCode ticket type should be returned with a live promo code');
    }

    /**
     * WithPromoCode ticket type + live generic promo code → IS returned (any type unlocks)
     */
    public function testWithPromoCodeAudienceLiveGenericPromoCodeReturned(): void
    {
        $promoCodeTicket = $this->buildMockTicketType(20, SummitTicketType::Audience_With_Promo_Code);

        $promoCode = $this->createMock(SummitRegistrationPromoCode::class);
        $promoCode->method('getCode')->willReturn('GENERIC-CODE');
        $promoCode->method('isLive')->willReturn(true);
        $promoCode->method('getAllowedTicketTypes')->willReturn(new ArrayCollection([$promoCodeTicket]));
        $promoCode->method('canBeAppliedTo')->willReturn(true);
        $promoCode->method('validate')->willReturn(true);

        $summit = $this->buildMockSummit();
        $member = $this->buildMockMember();

        $strategy = new RegularPromoCodeTicketTypesStrategy($summit, $member, $promoCode);
        $result = $strategy->getTicketTypes();

        $ids = array_map(fn($tt) => $tt->getId(), $result);
        $this->assertContains(20, $ids, 'WithPromoCode ticket type should be returned with any live promo code');
    }

    /**
     * Audience_All ticket type + no promo code → IS returned (existing behavior regression test)
     */
    public function testAudienceAllNoPromoCodeReturned(): void
    {
        $allTicket = $this->buildMockTicketType(30, SummitTicketType::Audience_All);
        $summit = $this->buildMockSummit([$allTicket]);
        $member = $this->buildMockMember();

        $strategy = new RegularPromoCodeTicketTypesStrategy($summit, $member, null);
        $result = $strategy->getTicketTypes();

        $ids = array_map(fn($tt) => $tt->getId(), $result);
        $this->assertContains(30, $ids, 'Audience_All ticket type should be returned without a promo code');
    }

    /**
     * Audience_All ticket type + promo code → IS returned with promo applied (existing behavior regression test)
     */
    public function testAudienceAllWithPromoCodeReturnedWithPromo(): void
    {
        $allTicket = $this->buildMockTicketType(40, SummitTicketType::Audience_All);

        $promoCode = $this->createMock(SummitRegistrationPromoCode::class);
        $promoCode->method('getCode')->willReturn('PROMO-ALL');
        $promoCode->method('isLive')->willReturn(true);
        $promoCode->method('getAllowedTicketTypes')->willReturn(new ArrayCollection());
        $promoCode->method('canBeAppliedTo')->willReturn(true);
        $promoCode->method('validate')->willReturn(true);

        $summit = $this->buildMockSummit([$allTicket]);
        $member = $this->buildMockMember();

        $strategy = new RegularPromoCodeTicketTypesStrategy($summit, $member, $promoCode);
        $result = $strategy->getTicketTypes();

        $ids = array_map(fn($tt) => $tt->getId(), $result);
        $this->assertContains(40, $ids, 'Audience_All ticket type should be returned with a promo code');
    }

    // -----------------------------------------------------------------------
    // Collision avoidance — DomainAuthorizedSummitRegistrationDiscountCode
    // -----------------------------------------------------------------------

    /**
     * addTicketTypeRule rejects rules for types not in allowed_ticket_types (Truth #4).
     */
    public function testAddTicketTypeRuleRejectsWhenTypeNotInAllowedTicketTypes(): void
    {
        $code = new DomainAuthorizedSummitRegistrationDiscountCode();

        $ticketType = $this->createMock(SummitTicketType::class);
        $ticketType->method('getId')->willReturn(1);

        $rule = new SummitRegistrationDiscountCodeTicketTypeRule();
        $rule->setTicketType($ticketType);

        $this->expectException(ValidationException::class);
        $code->addTicketTypeRule($rule);
    }

    /**
     * addTicketTypeRule does NOT mutate allowed_ticket_types — override skips parent's add().
     */
    public function testAddTicketTypeRuleDoesNotMutateAllowedTicketTypes(): void
    {
        $code = new DomainAuthorizedSummitRegistrationDiscountCode();

        $ticketType = $this->createMock(SummitTicketType::class);
        $ticketType->method('getId')->willReturn(1);

        // First add to allowed_ticket_types
        $code->addAllowedTicketType($ticketType);
        $this->assertEquals(1, $code->getAllowedTicketTypes()->count());

        // Now add a discount rule — should NOT add a second entry to allowed_ticket_types
        $rule = new SummitRegistrationDiscountCodeTicketTypeRule();
        $rule->setTicketType($ticketType);
        $code->addTicketTypeRule($rule);

        $this->assertEquals(1, $code->getAllowedTicketTypes()->count(),
            'addTicketTypeRule must not mutate allowed_ticket_types');
    }

    /**
     * removeTicketTypeRule does NOT mutate allowed_ticket_types.
     */
    public function testRemoveTicketTypeRuleDoesNotMutateAllowedTicketTypes(): void
    {
        $code = new DomainAuthorizedSummitRegistrationDiscountCode();

        $ticketType = $this->createMock(SummitTicketType::class);
        $ticketType->method('getId')->willReturn(1);

        $code->addAllowedTicketType($ticketType);

        $rule = new SummitRegistrationDiscountCodeTicketTypeRule();
        $rule->setTicketType($ticketType);
        $code->addTicketTypeRule($rule);

        // Remove the rule — allowed_ticket_types must remain intact
        $code->removeTicketTypeRule($rule);

        $this->assertEquals(1, $code->getAllowedTicketTypes()->count(),
            'removeTicketTypeRule must not mutate allowed_ticket_types');
    }

    // -----------------------------------------------------------------------
    // canBeAppliedTo override — DomainAuthorizedSummitRegistrationDiscountCode
    // -----------------------------------------------------------------------

    /**
     * Free WithPromoCode ticket type accepted — override skips free-ticket guard (Truth #15).
     */
    public function testCanBeAppliedToFreeWithPromoCodeTicketType(): void
    {
        $code = new DomainAuthorizedSummitRegistrationDiscountCode();

        $ticketType = $this->createMock(SummitTicketType::class);
        $ticketType->method('getId')->willReturn(100);
        $ticketType->method('isFree')->willReturn(true);

        $code->addAllowedTicketType($ticketType);

        // Parent SummitRegistrationDiscountCode::canBeAppliedTo would return false
        // because of the free-ticket guard. The override bypasses it.
        $this->assertTrue($code->canBeAppliedTo($ticketType),
            'Domain-authorized discount code should be applicable to free WithPromoCode ticket types');
    }

    /**
     * Paid ticket type accepted — normal discount behavior preserved.
     */
    public function testCanBeAppliedToPaidTicketType(): void
    {
        $code = new DomainAuthorizedSummitRegistrationDiscountCode();

        $ticketType = $this->createMock(SummitTicketType::class);
        $ticketType->method('getId')->willReturn(200);
        $ticketType->method('isFree')->willReturn(false);

        $code->addAllowedTicketType($ticketType);

        $this->assertTrue($code->canBeAppliedTo($ticketType),
            'Domain-authorized discount code should be applicable to paid ticket types');
    }

    // -----------------------------------------------------------------------
    // AutoApplyPromoCodeTrait — existing email-linked types
    // -----------------------------------------------------------------------

    public function testAutoApplyMemberPromoCode(): void
    {
        $code = new MemberSummitRegistrationPromoCode();
        $this->assertFalse($code->getAutoApply(), 'auto_apply should default to false');
        $code->setAutoApply(true);
        $this->assertTrue($code->getAutoApply(), 'auto_apply should round-trip to true');
    }

    public function testAutoApplyMemberDiscountCode(): void
    {
        $code = new MemberSummitRegistrationDiscountCode();
        $this->assertFalse($code->getAutoApply(), 'auto_apply should default to false');
        $code->setAutoApply(true);
        $this->assertTrue($code->getAutoApply(), 'auto_apply should round-trip to true');
    }

    public function testAutoApplySpeakerPromoCode(): void
    {
        $code = new SpeakerSummitRegistrationPromoCode();
        $this->assertFalse($code->getAutoApply(), 'auto_apply should default to false');
        $code->setAutoApply(true);
        $this->assertTrue($code->getAutoApply(), 'auto_apply should round-trip to true');
    }

    public function testAutoApplySpeakerDiscountCode(): void
    {
        $code = new SpeakerSummitRegistrationDiscountCode();
        $this->assertFalse($code->getAutoApply(), 'auto_apply should default to false');
        $code->setAutoApply(true);
        $this->assertTrue($code->getAutoApply(), 'auto_apply should round-trip to true');
    }

    // -----------------------------------------------------------------------
    // Serializer tests
    // -----------------------------------------------------------------------

    /**
     * auto_apply field serialization for domain-authorized promo code.
     */
    public function testSerializerAutoApplyField(): void
    {
        $code = new DomainAuthorizedSummitRegistrationPromoCode();
        $code->setAutoApply(true);

        $serializer = SerializerRegistry::getInstance()->getSerializer($code);
        $data = $serializer->serialize(null, [], [], []);

        $this->assertArrayHasKey('auto_apply', $data);
        $this->assertTrue($data['auto_apply'], 'auto_apply should serialize as true');

        // Also test false
        $code2 = new DomainAuthorizedSummitRegistrationPromoCode();
        $code2->setAutoApply(false);

        $serializer2 = SerializerRegistry::getInstance()->getSerializer($code2);
        $data2 = $serializer2->serialize(null, [], [], []);

        $this->assertArrayHasKey('auto_apply', $data2);
        $this->assertFalse($data2['auto_apply'], 'auto_apply should serialize as false');
    }

    /**
     * remaining_quantity_per_account transient field serialization.
     */
    public function testSerializerRemainingQuantityPerAccount(): void
    {
        $code = new DomainAuthorizedSummitRegistrationPromoCode();
        $code->setRemainingQuantityPerAccount(3);

        $serializer = SerializerRegistry::getInstance()->getSerializer($code);
        $data = $serializer->serialize(null, [], [], []);

        $this->assertArrayHasKey('remaining_quantity_per_account', $data);
        $this->assertEquals(3, $data['remaining_quantity_per_account']);

        // Test null (unlimited)
        $code2 = new DomainAuthorizedSummitRegistrationPromoCode();
        $serializer2 = SerializerRegistry::getInstance()->getSerializer($code2);
        $data2 = $serializer2->serialize(null, [], [], []);

        $this->assertArrayHasKey('remaining_quantity_per_account', $data2);
        $this->assertNull($data2['remaining_quantity_per_account']);
    }

    /**
     * auto_apply field serialization for existing email-linked type (MemberSummitRegistrationPromoCode).
     */
    public function testSerializerAutoApplyEmailLinkedType(): void
    {
        $code = new MemberSummitRegistrationPromoCode();
        $code->setAutoApply(true);

        $serializer = SerializerRegistry::getInstance()->getSerializer($code);
        $data = $serializer->serialize(null, [], [], []);

        $this->assertArrayHasKey('auto_apply', $data);
        $this->assertTrue($data['auto_apply'], 'auto_apply should serialize as true for member promo code');
    }
}
