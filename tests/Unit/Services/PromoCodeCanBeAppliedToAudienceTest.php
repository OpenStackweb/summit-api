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

use models\summit\DomainAuthorizedSummitRegistrationDiscountCode;
use models\summit\SummitRegistrationDiscountCode;
use models\summit\SummitRegistrationPromoCode;
use models\summit\SummitTicketType;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Class PromoCodeCanBeAppliedToAudienceTest
 *
 * Guards the audience restriction on SummitRegistrationPromoCode::canBeAppliedTo().
 *
 * Before the fix: an empty allowed_ticket_types collection meant "applies to all
 * ticket types, regardless of audience" — which leaked WithPromoCode tickets
 * (intentionally hidden from the public) into every implicit-sweep discount code.
 *
 * After the fix: empty allowed_ticket_types only matches Audience = All. Other
 * audiences (WithInvitation, WithoutInvitation, WithPromoCode) must be opted in
 * via explicit membership in allowed_ticket_types.
 *
 * See: doc/promo-code-apply-to-all-audience-restriction.md
 * ClickUp: 86b9vrpxp
 */
class PromoCodeCanBeAppliedToAudienceTest extends TestCase
{
    private function buildMockTicketType(int $id, string $audience): SummitTicketType
    {
        $tt = $this->createMock(SummitTicketType::class);
        $tt->method('getId')->willReturn($id);
        $tt->method('getAudience')->willReturn($audience);
        $tt->method('isFree')->willReturn(false);
        return $tt;
    }

    // -----------------------------------------------------------------------
    // Empty allowed_ticket_types branch — implicit "apply to all"
    // -----------------------------------------------------------------------

    public function testEmptyAllowedTicketTypesAndAudienceAllReturnsTrue(): void
    {
        $code = new SummitRegistrationPromoCode();
        $ticketType = $this->buildMockTicketType(1, SummitTicketType::Audience_All);

        $this->assertTrue(
            $code->canBeAppliedTo($ticketType),
            'Empty allowed_ticket_types must match Audience=All ticket types (the implicit sweep).'
        );
    }

    public function testEmptyAllowedTicketTypesAndAudienceWithInvitationReturnsFalse(): void
    {
        $code = new SummitRegistrationPromoCode();
        $ticketType = $this->buildMockTicketType(2, SummitTicketType::Audience_With_Invitation);

        $this->assertFalse(
            $code->canBeAppliedTo($ticketType),
            'WithInvitation tickets must require explicit allowed_ticket_types membership; the implicit sweep must skip them.'
        );
    }

    public function testEmptyAllowedTicketTypesAndAudienceWithoutInvitationReturnsFalse(): void
    {
        $code = new SummitRegistrationPromoCode();
        $ticketType = $this->buildMockTicketType(3, SummitTicketType::Audience_Without_Invitation);

        $this->assertFalse(
            $code->canBeAppliedTo($ticketType),
            'WithoutInvitation tickets must require explicit allowed_ticket_types membership; the implicit sweep must skip them.'
        );
    }

    public function testEmptyAllowedTicketTypesAndAudienceWithPromoCodeReturnsFalse(): void
    {
        $code = new SummitRegistrationPromoCode();
        $ticketType = $this->buildMockTicketType(4, SummitTicketType::Audience_With_Promo_Code);

        $this->assertFalse(
            $code->canBeAppliedTo($ticketType),
            'WithPromoCode tickets must NEVER be swept in by the implicit "apply to all" branch — that is the core of the leak this test guards against.'
        );
    }

    // -----------------------------------------------------------------------
    // Non-empty allowed_ticket_types branch — explicit membership
    // -----------------------------------------------------------------------

    #[DataProvider('audienceProvider')]
    public function testNonEmptyAllowedTicketTypesContainingTicketReturnsTrueRegardlessOfAudience(string $audience): void
    {
        $code = new SummitRegistrationPromoCode();
        $ticketType = $this->buildMockTicketType(10, $audience);

        $code->addAllowedTicketType($ticketType);

        $this->assertTrue(
            $code->canBeAppliedTo($ticketType),
            "Explicit membership in allowed_ticket_types must apply regardless of audience (audience under test: $audience)."
        );
    }

    public static function audienceProvider(): array
    {
        return [
            'Audience=All'              => [SummitTicketType::Audience_All],
            'Audience=WithInvitation'   => [SummitTicketType::Audience_With_Invitation],
            'Audience=WithoutInvitation'=> [SummitTicketType::Audience_Without_Invitation],
            'Audience=WithPromoCode'    => [SummitTicketType::Audience_With_Promo_Code],
        ];
    }

    public function testNonEmptyAllowedTicketTypesNotContainingTicketReturnsFalse(): void
    {
        $code = new SummitRegistrationPromoCode();
        $included = $this->buildMockTicketType(20, SummitTicketType::Audience_All);
        $code->addAllowedTicketType($included);

        $excluded = $this->buildMockTicketType(21, SummitTicketType::Audience_All);

        $this->assertFalse(
            $code->canBeAppliedTo($excluded),
            'A ticket type not in a non-empty allowed_ticket_types collection must not be matched, even when its audience is All.'
        );
    }

    // -----------------------------------------------------------------------
    // Subclass overrides — confirm they still flow through the patched base
    // -----------------------------------------------------------------------

    public function testDiscountCodeDelegatesToBaseAndRejectsNonAllAudienceUnderImplicitSweep(): void
    {
        $code = new SummitRegistrationDiscountCode();
        $ticketType = $this->buildMockTicketType(30, SummitTicketType::Audience_With_Promo_Code);

        $this->assertFalse(
            $code->canBeAppliedTo($ticketType),
            'SummitRegistrationDiscountCode::canBeAppliedTo must inherit the audience restriction via parent::canBeAppliedTo().'
        );
    }

    public function testDomainAuthorizedDiscountCodeStillAppliesToExplicitAllowedTicketTypes(): void
    {
        $code = new DomainAuthorizedSummitRegistrationDiscountCode();
        $ticketType = $this->buildMockTicketType(40, SummitTicketType::Audience_With_Promo_Code);

        $code->addAllowedTicketType($ticketType);

        $this->assertTrue(
            $code->canBeAppliedTo($ticketType),
            'DomainAuthorizedSummitRegistrationDiscountCode::canBeAppliedTo must continue to apply to explicitly allowed WithPromoCode tickets — that is the entire point of the override.'
        );
    }
}
