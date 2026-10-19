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
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Monolog\Handler\TestHandler;
use models\summit\ISummitEventRepository;
use models\summit\Presentation;
use models\summit\SummitEvent;
use Tests\InsertSummitTestData;
use Tests\ProtectedApiTestCase;
use utils\FilterParser;
use utils\Order;
use utils\OrderElement;
use utils\PagingInfo;

/**
 * Regression test for the "unexpected hydration row" warnings of
 * DoctrineSummitEventRepository::getAllByPage.
 *
 * The page query joins Presentation and PresentationType on columns (JOIN ... WITH),
 * so Doctrine returns them as extra root elements of getResult(), or null when the join
 * finds no row. The loop that indexes the page skips them correctly, but it used to log a
 * warning for each one, flooding the logs on every event listing.
 *
 * The fixture holds presentations and events that are not presentations, so both kinds of
 * extra element (null and PresentationType) are produced.
 */
final class EventsHydrationRowWarningTest extends ProtectedApiTestCase
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

    public function testGetAllByPageDoesNotLogUnexpectedHydrationRows(): void
    {
        $expectedIds = [];
        $hasPresentation = false;
        $hasPlainEvent = false;
        foreach (self::$presentations as $event) {
            $expectedIds[] = $event->getId();
            if ($event instanceof Presentation) $hasPresentation = true;
            elseif ($event instanceof SummitEvent) $hasPlainEvent = true;
        }
        sort($expectedIds);
        $this->assertTrue(
            $hasPresentation && $hasPlainEvent,
            'precondition: fixture must hold presentations and events that are not presentations'
        );

        $summitId = self::$summit->getId();

        // Evict everything so getAllByPage hydrates the page fresh, as a real request does.
        self::$em->clear();

        $handler = new TestHandler();
        $logger = Log::getLogger();
        $logger->pushHandler($handler);

        try {
            /** @var ISummitEventRepository $repo */
            $repo = App::make(ISummitEventRepository::class);
            $filter = FilterParser::parse(['summit_id==' . $summitId], ['summit_id' => ['==']]);
            $order = new Order([OrderElement::buildAscFor('id')]);

            $response = $repo->getAllByPage(new PagingInfo(1, 200), $filter, $order);
        } finally {
            $logger->popHandler();
        }

        $returnedIds = array_map(fn($event) => $event->getId(), $response->getItems());
        $this->assertSame($expectedIds, $returnedIds, 'every fixture event must be returned in page order');

        $hydrationWarnings = array_filter(
            $handler->getRecords(),
            fn($record) => str_contains((string)($record['message'] ?? $record->message ?? ''), 'unexpected hydration row')
        );
        $this->assertCount(
            0,
            $hydrationWarnings,
            sprintf('getAllByPage must not log "unexpected hydration row"; got %d', count($hydrationWarnings))
        );
    }
}
