<?php namespace Tests\Repositories;

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

use App\Repositories\Summit\DoctrineSummitEventRepository;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;
use utils\Filter;
use utils\FilterElement;
use utils\Order;
use utils\OrderElement;

/**
 * Class DoctrineSummitEventRepositoryRequiredAliasesTest
 * Tests that requiredAliases() returns the correct join aliases for each filter/order combination.
 */
final class DoctrineSummitEventRepositoryRequiredAliasesTest extends TestCase
{
    private object $repository;
    private ReflectionMethod $requiredAliases;

    protected function setUp(): void
    {
        parent::setUp();
        // Bypass constructor — requiredAliases is a pure function that only reads
        // the inline-initialized joinCatalog property, no EntityManager needed.
        $reflector = new ReflectionClass(DoctrineSummitEventRepository::class);
        $this->repository = $reflector->newInstanceWithoutConstructor();
        $this->requiredAliases = new ReflectionMethod(
            DoctrineSummitEventRepository::class,
            'requiredAliases'
        );
        $this->requiredAliases->setAccessible(true);
    }

    private function invokeRequiredAliases(?Filter $filter = null, ?Order $order = null): array
    {
        return $this->requiredAliases->invoke($this->repository, $filter, $order);
    }

    private function buildFilterWith(string $field, string $value = '1'): Filter
    {
        $filter = new Filter();
        $filter->addFilterCondition(FilterElement::makeEqual($field, $value));
        return $filter;
    }

    private function buildOrderWith(string $field): Order
    {
        return new Order([OrderElement::buildAscFor($field)]);
    }

    // =========================================================================
    // NULL inputs
    // =========================================================================

    public function testNullFilterAndOrderReturnsEmptyAliases(): void
    {
        $aliases = $this->invokeRequiredAliases(null, null);
        $this->assertEmpty($aliases);
    }

    // =========================================================================
    // summit_id filter  →  s
    // =========================================================================

    public function testSummitIdFilterIncludesSummitJoin(): void
    {
        $aliases = $this->invokeRequiredAliases($this->buildFilterWith('summit_id', '1'));
        $this->assertContains('s', $aliases);
    }

    // =========================================================================
    // tags filter / order  →  t
    // =========================================================================

    public function testTagsFilterIncludesTagJoin(): void
    {
        $aliases = $this->invokeRequiredAliases($this->buildFilterWith('tags', 'nova'));
        $this->assertContains('t', $aliases);
    }

    public function testTagsOrderIncludesTagJoin(): void
    {
        $aliases = $this->invokeRequiredAliases(null, $this->buildOrderWith('tags'));
        $this->assertContains('t', $aliases);
    }

    // =========================================================================
    // is_chair_visible / is_voting_visible / track_id filters + track order → c
    // =========================================================================

    public function testIsChairVisibleFilterIncludesCategoryJoin(): void
    {
        $aliases = $this->invokeRequiredAliases($this->buildFilterWith('is_chair_visible', 'true'));
        $this->assertContains('c', $aliases);
    }

    public function testIsVotingVisibleFilterIncludesCategoryJoin(): void
    {
        $aliases = $this->invokeRequiredAliases($this->buildFilterWith('is_voting_visible', 'true'));
        $this->assertContains('c', $aliases);
    }

    public function testTrackIdFilterIncludesCategoryJoin(): void
    {
        $aliases = $this->invokeRequiredAliases($this->buildFilterWith('track_id', '1'));
        $this->assertContains('c', $aliases);
    }

    public function testTrackOrderIncludesCategoryJoin(): void
    {
        $aliases = $this->invokeRequiredAliases(null, $this->buildOrderWith('track'));
        $this->assertContains('c', $aliases);
    }

    // =========================================================================
    // actions filter → allowed_at_type  /  actions order → a
    // (special: order adds 'a' ONLY when filter is absent)
    // =========================================================================

    public function testActionsFilterIncludesAllowedActionTypeJoin(): void
    {
        $aliases = $this->invokeRequiredAliases($this->buildFilterWith('actions', '1'));
        $this->assertContains('allowed_at_type', $aliases);
    }

    public function testActionsOrderWithoutFilterIncludesActionsJoin(): void
    {
        $aliases = $this->invokeRequiredAliases(null, $this->buildOrderWith('actions'));
        $this->assertContains('a', $aliases);
    }

    public function testActionsOrderWithFilterDoesNotDuplicateActionsJoin(): void
    {
        $filter = $this->buildFilterWith('actions', '1');
        $order = $this->buildOrderWith('actions');
        $aliases = $this->invokeRequiredAliases($filter, $order);

        // When both filter + order, only allowed_at_type is added (not 'a')
        $this->assertContains('allowed_at_type', $aliases);
        $this->assertNotContains('a', $aliases);
    }

    // =========================================================================
    // track_group_id filter → cg
    // =========================================================================

    public function testTrackGroupIdFilterIncludesCategoryGroupJoin(): void
    {
        $aliases = $this->invokeRequiredAliases($this->buildFilterWith('track_group_id', '1'));
        $this->assertContains('cg', $aliases);
    }

    // =========================================================================
    // selection_plan_id filter / selection_plan order → selp
    // =========================================================================

    public function testSelectionPlanIdFilterIncludesSelectionPlanJoin(): void
    {
        $aliases = $this->invokeRequiredAliases($this->buildFilterWith('selection_plan_id', '1'));
        $this->assertContains('selp', $aliases);
    }

    public function testSelectionPlanOrderIncludesSelectionPlanJoin(): void
    {
        $aliases = $this->invokeRequiredAliases(null, $this->buildOrderWith('selection_plan'));
        $this->assertContains('selp', $aliases);
    }

    // =========================================================================
    // location_id filter / location order → l
    // =========================================================================

    public function testLocationIdFilterIncludesLocationJoin(): void
    {
        $aliases = $this->invokeRequiredAliases($this->buildFilterWith('location_id', '1'));
        $this->assertContains('l', $aliases);
    }

    public function testLocationOrderIncludesLocationJoin(): void
    {
        $aliases = $this->invokeRequiredAliases(null, $this->buildOrderWith('location'));
        $this->assertContains('l', $aliases);
    }

    // =========================================================================
    // speakers_count order / speaker_company order → sp
    // =========================================================================

    public function testSpeakersCountOrderIncludesSpeakerJoin(): void
    {
        $aliases = $this->invokeRequiredAliases(null, $this->buildOrderWith('speakers_count'));
        $this->assertContains('sp', $aliases);
    }

    public function testSpeakerCompanyOrderIncludesSpeakerJoin(): void
    {
        $aliases = $this->invokeRequiredAliases(null, $this->buildOrderWith('speaker_company'));
        $this->assertContains('sp', $aliases);
    }

    // =========================================================================
    // speaker filter → sp, spm, spmm
    // =========================================================================

    public function testSpeakerFilterIncludesAllSpeakerJoins(): void
    {
        $aliases = $this->invokeRequiredAliases($this->buildFilterWith('speaker', 'John'));
        $this->assertContains('sp', $aliases);
        $this->assertContains('spm', $aliases);
        $this->assertContains('spmm', $aliases);
    }

    // =========================================================================
    // speaker_email filter → sprr, spmm, spmm2, sprr2
    // =========================================================================

    public function testSpeakerEmailFilterIncludesEmailJoins(): void
    {
        $aliases = $this->invokeRequiredAliases($this->buildFilterWith('speaker_email', 'test@test.com'));
        $this->assertContains('sprr', $aliases);
        $this->assertContains('spmm', $aliases);
        $this->assertContains('spmm2', $aliases);
        $this->assertContains('sprr2', $aliases);
    }

    // =========================================================================
    // speaker_title / speaker_company / speaker_id filters → sp, spm
    // =========================================================================

    public function testSpeakerTitleFilterIncludesSpeakerAndModeratorJoins(): void
    {
        $aliases = $this->invokeRequiredAliases($this->buildFilterWith('speaker_title', 'CEO'));
        $this->assertContains('sp', $aliases);
        $this->assertContains('spm', $aliases);
    }

    public function testSpeakerCompanyFilterIncludesSpeakerAndModeratorJoins(): void
    {
        $aliases = $this->invokeRequiredAliases($this->buildFilterWith('speaker_company', 'Acme'));
        $this->assertContains('sp', $aliases);
        $this->assertContains('spm', $aliases);
    }

    public function testSpeakerIdFilterIncludesSpeakerAndModeratorJoins(): void
    {
        $aliases = $this->invokeRequiredAliases($this->buildFilterWith('speaker_id', '1'));
        $this->assertContains('sp', $aliases);
        $this->assertContains('spm', $aliases);
    }

    // =========================================================================
    // sponsor / sponsor_id filter + sponsor order → sprs
    // =========================================================================

    public function testSponsorFilterIncludesSponsorJoin(): void
    {
        $aliases = $this->invokeRequiredAliases($this->buildFilterWith('sponsor', 'Acme'));
        $this->assertContains('sprs', $aliases);
    }

    public function testSponsorIdFilterIncludesSponsorJoin(): void
    {
        $aliases = $this->invokeRequiredAliases($this->buildFilterWith('sponsor_id', '1'));
        $this->assertContains('sprs', $aliases);
    }

    public function testSponsorOrderIncludesSponsorJoin(): void
    {
        $aliases = $this->invokeRequiredAliases(null, $this->buildOrderWith('sponsor'));
        $this->assertContains('sprs', $aliases);
    }

    // =========================================================================
    // created_by_fullname / created_by_email / created_by_company filters
    // + created_by_company / created_by_fullname / created_by_email orders → cb
    // =========================================================================

    public function testCreatedByFullnameFilterIncludesCreatedByJoin(): void
    {
        $aliases = $this->invokeRequiredAliases($this->buildFilterWith('created_by_fullname', 'John'));
        $this->assertContains('cb', $aliases);
    }

    public function testCreatedByEmailFilterIncludesCreatedByJoin(): void
    {
        $aliases = $this->invokeRequiredAliases($this->buildFilterWith('created_by_email', 'test@test.com'));
        $this->assertContains('cb', $aliases);
    }

    public function testCreatedByCompanyFilterIncludesCreatedByJoin(): void
    {
        $aliases = $this->invokeRequiredAliases($this->buildFilterWith('created_by_company', 'Acme'));
        $this->assertContains('cb', $aliases);
    }

    public function testCreatedByCompanyOrderIncludesCreatedByJoin(): void
    {
        $aliases = $this->invokeRequiredAliases(null, $this->buildOrderWith('created_by_company'));
        $this->assertContains('cb', $aliases);
    }

    public function testCreatedByFullnameOrderIncludesCreatedByJoin(): void
    {
        $aliases = $this->invokeRequiredAliases(null, $this->buildOrderWith('created_by_fullname'));
        $this->assertContains('cb', $aliases);
    }

    public function testCreatedByEmailOrderIncludesCreatedByJoin(): void
    {
        $aliases = $this->invokeRequiredAliases(null, $this->buildOrderWith('created_by_email'));
        $this->assertContains('cb', $aliases);
    }

    // =========================================================================
    // presentation_attendee_vote_date / votes_count filter + votes_count order → av
    // =========================================================================

    public function testPresentationAttendeeVoteDateFilterIncludesAttendeesVotesJoin(): void
    {
        $aliases = $this->invokeRequiredAliases($this->buildFilterWith('presentation_attendee_vote_date', '1681855200'));
        $this->assertContains('av', $aliases);
    }

    public function testVotesCountFilterIncludesAttendeesVotesJoin(): void
    {
        $aliases = $this->invokeRequiredAliases($this->buildFilterWith('votes_count', '1'));
        $this->assertContains('av', $aliases);
    }

    public function testVotesCountOrderIncludesAttendeesVotesJoin(): void
    {
        $aliases = $this->invokeRequiredAliases(null, $this->buildOrderWith('votes_count'));
        $this->assertContains('av', $aliases);
    }

    // =========================================================================
    // selection_status filter / order → selp, ssp, sspl, c  (NOT ssp_member)
    // =========================================================================

    public function testSelectionStatusFilterIncludesRequiredAliases(): void
    {
        $aliases = $this->invokeRequiredAliases($this->buildFilterWith('selection_status', 'accepted'));
        $this->assertContains('selp', $aliases);
        $this->assertContains('ssp', $aliases);
        $this->assertContains('sspl', $aliases);
        $this->assertContains('c', $aliases);
    }

    public function testSelectionStatusFilterDoesNotIncludeSspMember(): void
    {
        $aliases = $this->invokeRequiredAliases($this->buildFilterWith('selection_status', 'accepted'));
        $this->assertNotContains('ssp_member', $aliases, 'selection_status filter must not include ssp_member join');
    }

    public function testSelectionStatusOrderIncludesRequiredAliases(): void
    {
        $aliases = $this->invokeRequiredAliases(null, $this->buildOrderWith('selection_status'));
        $this->assertContains('selp', $aliases);
        $this->assertContains('ssp', $aliases);
        $this->assertContains('sspl', $aliases);
        $this->assertContains('c', $aliases);
    }

    public function testSelectionStatusOrderDoesNotIncludeSspMember(): void
    {
        $aliases = $this->invokeRequiredAliases(null, $this->buildOrderWith('selection_status'));
        $this->assertNotContains('ssp_member', $aliases, 'selection_status order must not include ssp_member join');
    }

    // =========================================================================
    // track_chairs_status filter → sspl, ssp, ssp_member
    // =========================================================================

    public function testTrackChairsStatusFilterIncludesSspMember(): void
    {
        $aliases = $this->invokeRequiredAliases($this->buildFilterWith('track_chairs_status', 'selected'));
        $this->assertContains('ssp_member', $aliases, 'track_chairs_status filter must include ssp_member join');
        $this->assertContains('ssp', $aliases, 'track_chairs_status filter must include ssp join');
        $this->assertContains('sspl', $aliases, 'track_chairs_status filter must include sspl join');
    }

    // =========================================================================
    // trackchairsel order → ssp
    // =========================================================================

    public function testTrackchairselOrderIncludesSspJoin(): void
    {
        $aliases = $this->invokeRequiredAliases(null, $this->buildOrderWith('trackchairsel'));
        $this->assertContains('ssp', $aliases);
    }
}
