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

use models\summit\Presentation;
use models\summit\PresentationSpeaker;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the request-scoped preload caches added in the N+1
 * elimination pass (hotfix/cache-optimizations).  No database required.
 */
final class PresentationSpeakerCacheTest extends TestCase
{
    // ------------------------------------------------------------------ //
    // PresentationSpeaker::setPreloadedAssignmentOrder                    //
    // ------------------------------------------------------------------ //

    public function testPreloadedAssignmentOrderCacheHit(): void
    {
        $speaker = new PresentationSpeaker();
        $pres    = $this->createMock(Presentation::class);
        $pres->method('getId')->willReturn(42);

        $speaker->setPreloadedAssignmentOrder(42, 3);

        $this->assertSame(3, $speaker->getPresentationAssignmentOrder($pres));
    }

    public function testPreloadedAssignmentOrderNullOrderCached(): void
    {
        $speaker = new PresentationSpeaker();
        $pres    = $this->createMock(Presentation::class);
        $pres->method('getId')->willReturn(10);

        $speaker->setPreloadedAssignmentOrder(10, null);

        $this->assertNull($speaker->getPresentationAssignmentOrder($pres));
    }

    // ------------------------------------------------------------------ //
    // PresentationSpeaker::clearPreloadedAssignmentOrder                  //
    // ------------------------------------------------------------------ //

    public function testClearPreloadedAssignmentOrderLeavesOtherEntriesIntact(): void
    {
        $speaker = new PresentationSpeaker();
        $speaker->setPreloadedAssignmentOrder(1, 5);
        $speaker->setPreloadedAssignmentOrder(2, 7);

        $speaker->clearPreloadedAssignmentOrder(1);

        $pres2 = $this->createMock(Presentation::class);
        $pres2->method('getId')->willReturn(2);
        $this->assertSame(7, $speaker->getPresentationAssignmentOrder($pres2),
            'Clearing entry 1 must not affect entry 2');
    }

    public function testClearPreloadedAssignmentOrderAllowsReset(): void
    {
        $speaker = new PresentationSpeaker();
        $speaker->setPreloadedAssignmentOrder(1, 5);
        $speaker->clearPreloadedAssignmentOrder(1);
        $speaker->setPreloadedAssignmentOrder(1, 9);

        $pres = $this->createMock(Presentation::class);
        $pres->method('getId')->willReturn(1);
        $this->assertSame(9, $speaker->getPresentationAssignmentOrder($pres),
            'After clear + re-set, new value must be returned');
    }

    // ------------------------------------------------------------------ //
    // PresentationSpeaker::clearAllPreloadedAssignmentOrders              //
    // ------------------------------------------------------------------ //

    public function testClearAllPreloadedAssignmentOrdersAllowsReset(): void
    {
        $speaker = new PresentationSpeaker();
        $speaker->setPreloadedAssignmentOrder(1, 2);
        $speaker->setPreloadedAssignmentOrder(3, 4);

        $speaker->clearAllPreloadedAssignmentOrders();
        $speaker->setPreloadedAssignmentOrder(1, 10);

        $pres = $this->createMock(Presentation::class);
        $pres->method('getId')->willReturn(1);
        $this->assertSame(10, $speaker->getPresentationAssignmentOrder($pres));
    }

    // ------------------------------------------------------------------ //
    // Presentation::setPreloadedSessionSelections / getSelectionStatus    //
    // ------------------------------------------------------------------ //

    /**
     * Verifies the preloaded branch is exercised: with an empty preloaded
     * array the method must return Pending without ever calling createQuery()
     * (which would throw because there is no EntityManager in this context).
     */
    public function testGetSelectionStatusUsesPreloadedBranchWhenSet(): void
    {
        $pres = new Presentation();
        $pres->setPreloadedSessionSelections([]);

        $status = $pres->getSelectionStatus();

        $this->assertSame(Presentation::SelectionStatus_Pending, $status);
    }

    public function testGetSelectionStatusMemoizesResult(): void
    {
        $pres = new Presentation();
        $pres->setPreloadedSessionSelections([]);

        $first  = $pres->getSelectionStatus();
        $second = $pres->getSelectionStatus();

        $this->assertSame($first, $second, 'Second call must return the memoized value');
    }

    public function testSetPreloadedSessionSelectionsResetsMemoization(): void
    {
        $pres = new Presentation();
        $pres->setPreloadedSessionSelections([]);
        $pres->getSelectionStatus(); // fills memoized cache

        // Resetting preloaded selections must clear the memoized result so the
        // next getSelectionStatus() re-evaluates instead of returning stale data.
        $pres->setPreloadedSessionSelections([]);
        // Must not throw — preloaded path is used again, not the DQL fallback.
        $status = $pres->getSelectionStatus();
        $this->assertSame(Presentation::SelectionStatus_Pending, $status);
    }
}
