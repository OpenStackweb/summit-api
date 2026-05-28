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

use App\Models\Foundation\Summit\Factories\SummitPromoCodeFactory;
use App\Models\Foundation\Summit\Factories\SummitRegistrationDiscountCodeTicketTypeRuleFactory;
use App\Models\Foundation\Summit\Registration\PromoCodes\Strategies\RegularPromoCodeTicketTypesStrategy;
use Doctrine\Common\Collections\ArrayCollection;
use models\main\Member;
use models\summit\Summit;
use models\summit\SummitRegistrationDiscountCode;
use models\summit\SummitTicketType;
use Tests\TestCase;

/**
 * Class GetAllowedTicketTypesEmptyJoinTableTest
 *
 * Reproduces the production issue where GET /api/v1/summits/73/ticket-types/allowed
 * with filter=promo_code==FREET3ST returns {"total":0} even though ticket type 202
 * (audience=WithPromoCode) is currently on sale and FREET3ST has a 100% discount
 * rule for it.
 *
 * Root cause:
 *   The strategy reads WithPromoCode ticket types exclusively from
 *   $promo_code->getAllowedTicketTypes(), which is backed by the
 *   SummitRegistrationPromoCode_AllowedTicketTypes join table.
 *   When that join table has no rows for the promo code (data-integrity gap —
 *   ticket_types_rules rows exist but the join table was never populated),
 *   getAllowedTicketTypes() returns an empty collection and ticket 202 is never
 *   surfaced.
 *
 * Data used (as provided by the team):
 *
 *   Promo code FREET3ST (id=4606, SUMMIT_DISCOUNT_CODE)
 *     quantity_available=30, quantity_used=0
 *     valid_since_date=1779778800, valid_until_date=1788246000
 *     ticket_types_rules: [202 @100%, 203 @100%, 204 @100%]
 *
 *   Ticket type 202  audience=WithPromoCode  cost=$800
 *     sales_start=1779778800 (May 27 2026)  sales_end=1784357940 (Jul 17 2026)
 *     quantity_2_sell=7250  quantity_sold=4  → canSell()=true NOW
 *
 *   Ticket type 203  audience=All  cost=$800
 *     sales_start=1784358000 (Jul 18 2026)  → NOT yet on sale
 *
 *   Ticket type 204  audience=All  cost=$1000
 *     sales_start=1785567600 (Aug 1 2026)   → NOT yet on sale
 */
class GetAllowedTicketTypesEmptyJoinTableTest extends TestCase
{
    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function buildSummitMock(array $audienceAllTickets, array $audienceWithoutInvitationTickets = []): Summit
    {
        $summit = $this->createMock(Summit::class);
        $summit->method('getTicketTypesByAudience')
            ->willReturnCallback(function (string $audience) use ($audienceAllTickets, $audienceWithoutInvitationTickets) {
                if ($audience === SummitTicketType::Audience_All) {
                    return new ArrayCollection($audienceAllTickets);
                }
                if ($audience === SummitTicketType::Audience_Without_Invitation) {
                    return new ArrayCollection($audienceWithoutInvitationTickets);
                }
                return new ArrayCollection([]);
            });
        $summit->method('getSummitRegistrationInvitationByEmail')->willReturn(null);
        return $summit;
    }

    private function buildMemberMock(string $email = 'tester@example.com'): Member
    {
        $member = $this->createMock(Member::class);
        $member->method('getEmail')->willReturn($email);
        $member->method('getCompany')->willReturn(null);
        return $member;
    }

    /** Ticket type 202: WithPromoCode, $800, on sale May 27 – Jul 17 2026. */
    private function buildTicket202(): SummitTicketType
    {
        $tt = $this->createMock(SummitTicketType::class);
        $tt->method('getId')->willReturn(202);
        $tt->method('getAudience')->willReturn(SummitTicketType::Audience_With_Promo_Code);
        $tt->method('isPromoCodeOnly')->willReturn(true);
        $tt->method('canSell')->willReturn(true);
        $tt->method('isFree')->willReturn(false);
        return $tt;
    }

    /** Ticket type 203: All, $800, sale starts Jul 18 2026 – not yet on sale. */
    private function buildTicket203(): SummitTicketType
    {
        $tt = $this->createMock(SummitTicketType::class);
        $tt->method('getId')->willReturn(203);
        $tt->method('getAudience')->willReturn(SummitTicketType::Audience_All);
        $tt->method('isPromoCodeOnly')->willReturn(false);
        $tt->method('canSell')->willReturn(false);
        $tt->method('isFree')->willReturn(false);
        return $tt;
    }

    /** Ticket type 204: All, $1000, sale starts Aug 1 2026 – not yet on sale. */
    private function buildTicket204(): SummitTicketType
    {
        $tt = $this->createMock(SummitTicketType::class);
        $tt->method('getId')->willReturn(204);
        $tt->method('getAudience')->willReturn(SummitTicketType::Audience_All);
        $tt->method('isPromoCodeOnly')->willReturn(false);
        $tt->method('canSell')->willReturn(false);
        $tt->method('isFree')->willReturn(false);
        return $tt;
    }

    // -----------------------------------------------------------------------
    // Bug-reproduction test (RED until fixed)
    // -----------------------------------------------------------------------

    /**
     * @group regression
     *
     * Verifies the getAllowedTicketTypes() override: when the join table
     * (SummitRegistrationPromoCode_AllowedTicketTypes) is empty but ticket_types_rules
     * exist, the strategy must still surface WithPromoCode ticket types.
     *
     * Uses a real SummitRegistrationDiscountCode with rules added via addTicketTypeRule()
     * so the override derives ticket types from ticket_types_rules, not the join table.
     * Dates on the promo code are left null so isLive() returns true without DB setup.
     */
    public function testFREET3STWithEmptyAllowedTicketTypesReturnsTicket202(): void
    {
        $ticket202 = $this->buildTicket202();
        $ticket203 = $this->buildTicket203();
        $ticket204 = $this->buildTicket204();

        // Real discount code — no DB, dates null so isLive() = true, quantity 0 so
        // hasQuantityAvailable() = true. ticket_types_rules populated via addTicketTypeRule().
        $promoCode = new SummitRegistrationDiscountCode();

        foreach ([$ticket202, $ticket203, $ticket204] as $tt) {
            $rule = SummitRegistrationDiscountCodeTicketTypeRuleFactory::build([
                'ticket_type' => $tt,
                'rate'        => 100.0,
                'amount'      => 0.0,
            ]);
            $promoCode->addTicketTypeRule($rule);
        }

        $summit = $this->buildSummitMock([$ticket203, $ticket204]);
        $member = $this->buildMemberMock();

        $strategy = new RegularPromoCodeTicketTypesStrategy($summit, $member, $promoCode);
        $result = $strategy->getTicketTypes();

        // Tickets 203 and 204 are Audience_All but not yet on sale → skipped.
        // Ticket 202 is WithPromoCode, on sale, and in ticket_types_rules → returned.
        $this->assertCount(1, $result,
            'getAllowedTicketTypes() must derive ticket types from ticket_types_rules so ' .
            'WithPromoCode ticket 202 is returned even when the join table is empty.');
        $this->assertSame(202, $result[0]->getId());
    }

    // -----------------------------------------------------------------------
    // Correct-state test (GREEN — shows the behaviour when data is intact)
    // -----------------------------------------------------------------------

    /**
     * When allowed_ticket_types IS populated (join table has rows), the strategy
     * correctly returns ticket type 202.
     *
     * This is the state that exists when addTicketTypeRule() is called through
     * the API — it writes to both ticket_types_rules and allowed_ticket_types.
     * The production bug arises when the join table rows are missing (legacy data
     * or direct-DB insert), so this test documents the passing baseline.
     */
    public function testFREET3STWithPopulatedAllowedTicketTypesReturnsTicket202(): void
    {
        $ticket202 = $this->buildTicket202();
        $ticket203 = $this->buildTicket203();
        $ticket204 = $this->buildTicket204();

        // Correct state: allowed_ticket_types has the three ticket types from the rules.
        $promoCode = $this->createMock(SummitRegistrationDiscountCode::class);
        $promoCode->method('getCode')->willReturn('FREET3ST');
        $promoCode->method('isLive')->willReturn(true);
        $promoCode->method('validate')->willReturn(null);
        $promoCode->method('getAllowedTicketTypes')
            ->willReturn(new ArrayCollection([$ticket202, $ticket203, $ticket204]));
        $promoCode->method('canBeAppliedTo')
            ->willReturnCallback(fn(SummitTicketType $tt) => $tt->getId() === 202);

        $summit = $this->buildSummitMock([$ticket203, $ticket204]);
        $member = $this->buildMemberMock();

        $strategy = new RegularPromoCodeTicketTypesStrategy($summit, $member, $promoCode);
        $result = $strategy->getTicketTypes();

        // Ticket 203 and 204 are skipped (Audience_All but not on sale yet).
        // Ticket 202 is WithPromoCode, on sale, and in allowed_ticket_types → returned.
        $this->assertCount(1, $result);
        $this->assertSame(202, $result[0]->getId());
    }

    // -----------------------------------------------------------------------
    // No promo code — baseline empty result
    // -----------------------------------------------------------------------

    /**
     * Without a promo code, no WithPromoCode tickets are surfaced and the only
     * Audience_All tickets (203, 204) are not yet on sale → result is empty.
     * This is the state callers see when they omit the filter=promo_code parameter.
     */
    public function testNoPromoCodeAndAllAudienceTicketsNotOnSaleReturnsEmpty(): void
    {
        $ticket203 = $this->buildTicket203();
        $ticket204 = $this->buildTicket204();

        $summit = $this->buildSummitMock([$ticket203, $ticket204]);
        $member = $this->buildMemberMock();

        $strategy = new RegularPromoCodeTicketTypesStrategy($summit, $member, null);
        $result = $strategy->getTicketTypes();

        $this->assertCount(0, $result);
    }

    // -----------------------------------------------------------------------
    // Factory fix — clearTicketTypes must not be called on discount codes
    // -----------------------------------------------------------------------

    /**
     * @group regression
     *
     * Reproduces the "save clears the join table" bug:
     *   1. Admin adds ticket type 202 to FREET3ST via PUT /promo-codes/4606/ticket-types/202
     *      → addTicketTypeRule() → allowed_ticket_types populated ✓
     *   2. Admin saves promo code via PUT /promo-codes/4606 with body that includes
     *      "allowed_ticket_types": [] (admin UI always sends this field)
     *      → updatePromoCode → SummitPromoCodeFactory::populate
     *      → clearTicketTypes() called → join table wiped ✗
     *
     * Fix: skip clearTicketTypes() when the promo code is a SummitRegistrationDiscountCode.
     * Discount codes own allowed_ticket_types exclusively through ticket_types_rules;
     * the allowed_ticket_types param from the update payload must be ignored.
     */
    public function testFactoryPopulateWithEmptyAllowedTicketTypesDoesNotClearDiscountCode(): void
    {
        $ticket202 = $this->buildTicket202();

        $promoCode = new SummitRegistrationDiscountCode();
        $promoCode->addAllowedTicketType($ticket202);

        $this->assertCount(1, $promoCode->getAllowedTicketTypes(),
            'Pre-condition: allowed_ticket_types must have ticket 202 before the save.');

        $summit = $this->createMock(Summit::class);

        // Simulate the admin-UI PUT body: allowed_ticket_types arrives as an empty array.
        SummitPromoCodeFactory::populate(
            $promoCode,
            $summit,
            ['class_name' => SummitRegistrationDiscountCode::ClassName],
            ['allowed_ticket_types' => []]
        );

        $this->assertCount(1, $promoCode->getAllowedTicketTypes(),
            'SummitPromoCodeFactory::populate must NOT clear allowed_ticket_types on a ' .
            'SummitRegistrationDiscountCode — it is managed exclusively via ticket_types_rules.');
    }
}
