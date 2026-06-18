<?php namespace Tests\Unit;
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

use PHPUnit\Framework\TestCase;

/**
 * RED test (Approach D / D-half-1):
 * Static source-level assertion — no DB or Redis needed. Can run anywhere.
 *
 * Asserts that SummitRegistrationStats.php contains ZERO occurrences of the pattern
 *   INNER JOIN SummitOrder ON SummitOrder.ID = SummitAttendeeTicket.OrderID
 * because after Approach D (D-half-1) all 12 affected queries filter via
 * SummitAttendeeTicket.SummitID directly (the column added by Version20251002160949).
 *
 * Class RegistrationStatsSqlPatternTest
 * @package Tests\Unit
 */
final class RegistrationStatsSqlPatternTest extends TestCase
{
    private const TRAIT_FILE = __DIR__ . '/../../app/Models/Foundation/Summit/SummitRegistrationStats.php';

    /**
     * FAILS today (12 occurrences of the join pattern).
     * PASSES after Approach D (D-half-1) rewrites the 12 queries.
     */
    public function testRegistrationStatsQueriesDoNotJoinSummitOrderForSummitFilter(): void
    {
        $content = file_get_contents(self::TRAIT_FILE);
        $this->assertNotFalse($content, 'SummitRegistrationStats.php not found at ' . self::TRAIT_FILE);

        $matchCount = preg_match_all(
            '/INNER JOIN SummitOrder\s+on\s+SummitOrder\.ID\s*=\s*SummitAttendeeTicket\.OrderID/i',
            $content
        );

        $this->assertSame(
            0,
            $matchCount,
            "Found {$matchCount} queries in SummitRegistrationStats.php with " .
            "'INNER JOIN SummitOrder ON SummitOrder.ID = SummitAttendeeTicket.OrderID'. " .
            "After Approach D (D-half-1), ALL 12 affected queries must filter via " .
            "SummitAttendeeTicket.SummitID directly — the column added by Version20251002160949. " .
            "Rewrite each of the 12 listed methods in the plan: getActiveTicketsCount (L103), " .
            "getInactiveTicketsCount (L132), getActiveAssignedTicketsCount (L161), " .
            "getTotalPaymentAmountCollected (L218), getTotalRefundAmountEmitted (L248), " .
            "getActiveTicketsCountPerTicketType (L278), getCheckedInActiveTicketsCountPerTicketType (L307), " .
            "getActiveBadgesCountPerBadgeType (L344), getActiveCheckedInBadgesCountPerBadgeType (L375), " .
            "getActiveTicketsPerBadgeFeatureType (L606), getAttendeesCheckinPerBadgeFeatureType (L642), " .
            "getPurchasedTicketsGroupedBy (L781)."
        );
    }
}
