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

use App\Models\Foundation\Main\IGroup;
use App\Services\Model\ISummitSelectedPresentationListService;
use Illuminate\Support\Facades\App;
use models\exceptions\EntityNotFoundException;
use models\summit\SummitSelectedPresentation;

/**
 * Class SummitSelectedPresentationListServiceTest
 */
final class SummitSelectedPresentationListServiceTest extends BrowserKitTestCase
{
    use InsertSummitTestData;
    use InsertMemberTestData;

    protected function setUp(): void
    {
        parent::setUp();
        self::insertMemberTestData(IGroup::SuperAdmins);
        self::$defaultMember = self::$member;
        self::insertSummitTestData();

        // Summit::isTrackChair() short-circuits true via Member::isAdmin() for a
        // SuperAdmins member, avoiding the need to wire a real SummitTrackChair entity.
        // Unlike PresentationService (which reads the current user via the
        // \App\Facades\ResourceServerContext static facade), this service takes
        // IResourceServerContext as a plain constructor dependency - bind the
        // interface directly in the container instead of rebinding the facade accessor.
        $resource_server_context_mock = \Mockery::mock(\models\oauth2\IResourceServerContext::class);
        $resource_server_context_mock->shouldReceive('getCurrentUser')->andReturn(self::$defaultMember);
        App::instance(\models\oauth2\IResourceServerContext::class, $resource_server_context_mock);
    }

    protected function tearDown(): void
    {
        self::clearSummitTestData();
        self::clearMemberTestData();
        parent::tearDown();
        \Mockery::close();
    }

    /**
     * SummitSelectedPresentationListService::assignPresentationToMyIndividualList()
     * (SummitSelectedPresentationListService.php:379) calls createIndividualSelectionList()
     * (:443, its own nested transaction) only when no individual list exists yet for the
     * current member - no try/catch. That call commits a real, new
     * SummitSelectedPresentationList. AFTER it returns, the outer's own presentation lookup
     * (:449-455) throws EntityNotFoundException for a nonexistent presentation_id - rolling
     * back the entire call, including the just-committed individual selection list.
     */
    public function testAssignPresentationToMyIndividualListRollsBackAlreadyCommittedListWhenPresentationNotFound()
    {
        $service = App::make(ISummitSelectedPresentationListService::class);

        try {
            $service->assignPresentationToMyIndividualList(
                self::$summit,
                self::$default_selection_plan->getId(),
                self::$defaultTrack->getId(),
                SummitSelectedPresentation::CollectionMaybe,
                999999999
            );
            $this->fail('Expected EntityNotFoundException was not thrown');
        } catch (EntityNotFoundException $ex) {
            $this->assertStringContainsString('999999999', $ex->getMessage());
        }

        self::$em->clear();
        self::$summit = self::$summit_repository->find(self::$summit->getId());
        $selectionPlan = self::$em->find(\App\Models\Foundation\Summit\SelectionPlan::class, self::$default_selection_plan->getId());
        $category = self::$summit->getPresentationCategory(self::$defaultTrack->getId());
        $member = self::$member_repository->find(self::$defaultMember->getId());

        $list = $selectionPlan->getSelectionListByTrackAndTypeAndOwner(
            $category,
            \models\summit\SummitSelectedPresentationList::Individual,
            $member
        );
        $this->assertNull($list);
    }
}
