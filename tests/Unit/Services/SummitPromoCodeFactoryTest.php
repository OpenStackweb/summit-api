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
use models\summit\Summit;
use models\summit\SummitRegistrationDiscountCode;
use models\summit\SummitRegistrationDiscountCodeTicketTypeRule;
use models\summit\SummitTicketType;
use Tests\TestCase;

/**
 * Class SummitPromoCodeFactoryTest
 * Unit tests for SummitPromoCodeFactory::populate.
 * @package Tests\Unit\Services
 */
class SummitPromoCodeFactoryTest extends TestCase
{
    private function buildRule(int $ticketTypeId): SummitRegistrationDiscountCodeTicketTypeRule
    {
        $tt = $this->createMock(SummitTicketType::class);
        $tt->method('getId')->willReturn($ticketTypeId);

        $rule = new SummitRegistrationDiscountCodeTicketTypeRule();
        $rule->setTicketType($tt);
        $rule->setRate(100.0);
        return $rule;
    }

    /**
     * Regression: a PUT to update a discount code echoes back the whole serialized
     * object, including the (leftover) top-level amount/rate fields, alongside
     * ticket_types_rules. SummitRegistrationDiscountCode::setAmount()/setRate()
     * clear the ticket_types_rules collection (orphanRemoval), so applying those
     * leftover values during populate() deleted the existing rules on update.
     *
     * When ticket_types_rules are present in the payload, the top-level amount/rate
     * must be ignored so the per-ticket-type rules survive.
     */
    public function testPopulateKeepsTicketTypesRulesWhenAmountAndRatePresentInPayload(): void
    {
        $summit = $this->createMock(Summit::class);

        $code = new SummitRegistrationDiscountCode();
        $code->addTicketTypeRule($this->buildRule(202));
        $code->addTicketTypeRule($this->buildRule(203));
        $this->assertCount(2, $code->getTicketTypesRules(), 'pre-condition: code starts with 2 rules');

        // Mirrors the showadmin PUT payload: full object echoes amount/rate back.
        $data = [
            'class_name'         => SummitRegistrationDiscountCode::ClassName,
            'amount'             => 0,
            'rate'               => 100,
            'ticket_types_rules' => [
                ['ticket_type_id' => 202, 'rate' => 100, 'amount' => 0],
                ['ticket_type_id' => 203, 'rate' => 100, 'amount' => 0],
            ],
        ];

        SummitPromoCodeFactory::populate($code, $summit, $data);

        $this->assertCount(2, $code->getTicketTypesRules(),
            'ticket_types_rules must survive an update that also carries top-level amount/rate');
    }

    /**
     * Counterpart: with no ticket_types_rules in the payload, a flat discount code
     * must still apply the top-level amount/rate (switching a code to flat clears rules).
     */
    public function testPopulateAppliesAmountAndRateWhenNoTicketTypesRulesInPayload(): void
    {
        $summit = $this->createMock(Summit::class);

        $code = new SummitRegistrationDiscountCode();

        $data = [
            'class_name' => SummitRegistrationDiscountCode::ClassName,
            'rate'       => 25,
        ];

        SummitPromoCodeFactory::populate($code, $summit, $data);

        $this->assertEquals(25.0, $code->getRate(),
            'flat rate must be applied when the payload carries no ticket_types_rules');
    }
}
