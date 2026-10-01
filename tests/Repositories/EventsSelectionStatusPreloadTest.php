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

use App\Models\Foundation\Main\IGroup;
use DateInterval;
use DateTime;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Mockery;
use models\summit\ISummitEventRepository;
use models\summit\Presentation;
use models\summit\SummitSelectedPresentation;
use models\summit\SummitSelectedPresentationList;
use ReflectionProperty;
use Tests\InsertSummitTestData;
use Tests\ProtectedApiTestCase;
use utils\FilterParser;
use utils\PagingInfo;

/**
 * Regression test for the selection-status batch preload in
 * DoctrineSummitEventRepository::getAllByPage.
 *
 * The preload query used "JOIN FETCH", which is JPQL/Hibernate syntax and not valid
 * Doctrine DQL. The resulting Semantical Error was swallowed by a catch block that only
 * logged a warning, so responses stayed correct but every Presentation fell back to its own
 * getSelectionStatus() query (N+1).
 *
 * Because the fallback hides the failure, this test asserts both that the warning is not
 * logged and that the preloaded rows were actually handed to each Presentation, and it
 * checks the resulting selection status values.
 */
final class EventsSelectionStatusPreloadTest extends ProtectedApiTestCase
{
    use InsertSummitTestData;

    protected function setUp(): void
    {
        $this->current_group = IGroup::TrackChairs;
        parent::setUp();
        self::insertSummitTestData();
    }

    public function tearDown(): void
    {
        self::clearSummitTestData();
        parent::tearDown();
    }

    private function newUnpublishedPresentation(string $label): Presentation
    {
        $presentation = new Presentation();
        self::$summit->addEvent($presentation);
        $presentation->setTitle(sprintf("Selection Preload %s %s", $label, str_random(8)));
        $presentation->setAbstract("Selection preload abstract");
        $presentation->setCategory(self::$defaultTrack);
        $presentation->setProgress(Presentation::PHASE_COMPLETE);
        $presentation->setStatus(Presentation::STATUS_RECEIVED);
        $presentation->setType(self::$defaultPresentationType);
        self::$default_selection_plan->addPresentation($presentation);
        self::$em->persist($presentation);
        return $presentation;
    }

    public function testSelectionStatusPreloadSucceedsAndYieldsExpectedStatuses(): void
    {
        $em = self::$em;
        $track = self::$defaultTrack;
        $plan = self::$default_selection_plan;

        // selection period must be open so getSelectionStatus() reaches the selection lookup
        $tz = self::$summit->getTimeZone();
        $now = new DateTime('now', $tz);
        $plan->setSelectionBeginDate((clone $now)->sub(new DateInterval('P1D')));
        $plan->setSelectionEndDate((clone $now)->add(new DateInterval('P7D')));

        $sessionCount = $track->getSessionCount();
        $this->assertGreaterThanOrEqual(1, $sessionCount, 'precondition: track must have session slots');

        $accepted = $this->newUnpublishedPresentation('accepted');
        $alternate = $this->newUnpublishedPresentation('alternate');
        $unaccepted = $this->newUnpublishedPresentation('unaccepted');
        $em->flush();

        // team (Group / Session) list with two "selected" rows: one inside the session
        // slots, one past them (alternate). The third presentation has no selection.
        $list = new SummitSelectedPresentationList();
        $list->setName('Preload regression team list');
        $list->setListType(SummitSelectedPresentationList::Group);
        $list->setListClass(SummitSelectedPresentationList::Session);
        $list->setHash(md5('preload-regression'));
        $track->addSelectionList($list);
        $plan->addSelectionList($list);
        $em->persist($list);

        foreach ([[$accepted, 1], [$alternate, $sessionCount + 1]] as [$presentation, $order]) {
            $selection = new SummitSelectedPresentation();
            $selection->setCollection(SummitSelectedPresentation::CollectionSelected);
            $selection->setOrder($order);
            $selection->setPresentation($presentation);
            $list->addSelection($selection);
            $em->persist($selection);
        }
        $em->flush();

        $expected = [
            $accepted->getId() => Presentation::SelectionStatus_Accepted,
            $alternate->getId() => Presentation::SelectionStatus_Alternate,
            $unaccepted->getId() => Presentation::SelectionStatus_Unaccepted,
        ];
        $summitId = self::$summit->getId();

        // start from an empty identity map, like a real request
        $em->clear();

        Log::spy();

        /** @var ISummitEventRepository $repo */
        $repo = App::make(ISummitEventRepository::class);
        $filter = FilterParser::parse(['summit_id==' . $summitId], ['summit_id' => ['==']]);
        $response = $repo->getAllByPage(new PagingInfo(1, 200), $filter);

        Log::shouldNotHaveReceived('warning', [
            Mockery::pattern('/selection-status preload failed/'),
            Mockery::any(),
        ]);

        $byId = [];
        foreach ($response->getItems() as $event) {
            if ($event instanceof Presentation) $byId[$event->getId()] = $event;
        }

        $preloaded = new ReflectionProperty(Presentation::class, 'preloadedSessionSelections');
        $preloaded->setAccessible(true);

        foreach ($expected as $id => $status) {
            $this->assertArrayHasKey($id, $byId, "presentation $id must appear on the page");
            $presentation = $byId[$id];

            $this->assertIsArray(
                $preloaded->getValue($presentation),
                "presentation $id must receive preloaded session selections; null means the preload failed and the N+1 fallback ran"
            );
            $this->assertSame($status, $presentation->getSelectionStatus(), "selection status for presentation $id");
        }

        // grouping by getPresentation()->getId() must hand each presentation only its own row
        $this->assertCount(1, $preloaded->getValue($byId[$accepted->getId()]));
        $this->assertCount(1, $preloaded->getValue($byId[$alternate->getId()]));
        $this->assertCount(0, $preloaded->getValue($byId[$unaccepted->getId()]));
        $this->assertSame(
            $accepted->getId(),
            $preloaded->getValue($byId[$accepted->getId()])[0]->getPresentation()->getId()
        );
    }
}
