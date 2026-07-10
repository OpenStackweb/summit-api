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

use App\Models\Foundation\Summit\PromoCodes\PromoCodesConstants;
use Illuminate\Support\Facades\App;
use models\exceptions\EntityNotFoundException;
use models\summit\SpeakersSummitRegistrationPromoCode;
use services\model\ISummitPromoCodeService;

/**
 * Class SummitPromoCodeServiceTest
 *
 * Functional counterpart to tests/Unit/Services/SummitPromoCodeServiceDiscoveryTest.php
 * (which covers discoverPromoCodes() with pure Mockery, no real DB). This class exercises
 * addPromoCode() against a real database, needed to prove its two-transaction, partial-commit
 * behavior - not expressible with mocks.
 */
final class SummitPromoCodeServiceTest extends BrowserKitTestCase
{
    use InsertSummitTestData;

    protected function setUp(): void
    {
        parent::setUp();
        self::insertSummitTestData();
    }

    protected function tearDown(): void
    {
        self::clearSummitTestData();
        parent::tearDown();
    }

    /**
     * SummitPromoCodeService::addPromoCode() (SummitPromoCodeService.php:221) is actually TWO
     * SEPARATE, independent root transactions, not one root with everything nested inside: the
     * promo code itself commits in the first tx_service->transaction() call (:223-311); the
     * ticket-type-rules loop runs in a SECOND, separate tx_service->transaction() call
     * (:313-326) that only starts once the first has already committed. A failure inside the
     * rules loop (nested addPromoCodeTicketTypeRule() call, EntityNotFoundException at :496-497
     * for a nonexistent ticket_type_id) rolls back the SECOND transaction only - the
     * already-committed promo code from the first transaction survives with zero rules applied.
     * This test proves that partial-commit behavior explicitly, not a full rollback.
     */
    public function testAddPromoCodeSurvivesWhenTicketTypeRuleFails()
    {
        $service = App::make(ISummitPromoCodeService::class);

        $code = 'TEST_PC_' . uniqid();

        $data = [
            'type' => PromoCodesConstants::SpeakerSummitRegistrationPromoCodeTypeAlternate,
            'class_name' => SpeakersSummitRegistrationPromoCode::ClassName,
            'code' => $code,
            'description' => 'TEST PROMO CODE',
            'quantity_available' => 10,
            'allowed_ticket_types' => [],
            'badge_features' => [],
            'valid_since_date' => 1649108093,
            'valid_until_date' => 1649109093,
            'ticket_types_rules' => [
                ['ticket_type_id' => 999999999],
            ],
        ];

        try {
            $service->addPromoCode(self::$summit, $data);
            $this->fail('Expected EntityNotFoundException was not thrown');
        } catch (EntityNotFoundException $ex) {
            $this->assertStringContainsString('Ticket Type', $ex->getMessage());
        }

        self::$em->clear();
        self::$summit = self::$summit_repository->find(self::$summit->getId());

        $promo_code = self::$summit->getPromoCodeByCode($code);
        $this->assertNotNull($promo_code);
        $this->assertEquals(0, $promo_code->getAllowedTicketTypes()->count());
    }
}
