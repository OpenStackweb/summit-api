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
use Doctrine\DBAL\Logging\SQLLogger;
use Illuminate\Support\Facades\App;
use LaravelDoctrine\ORM\Facades\Registry;
use models\summit\ISummitAttendeeRepository;
use models\utils\SilverstripeBaseModel;
use utils\Filter;
use utils\FilterElement;
use utils\PagingInfo;

/**
 * Covers the deterministic-ordering fix for DoctrineSummitAttendeeRepository::getAllIdsByPage.
 * The generic DoctrineRepository::getAllIdsByPage applies no ORDER BY, so paging through it with
 * no explicit Order can silently skip or repeat rows across pages. A row-count/id-set assertion
 * alone is not a reliable RED signal for this: on a small, freshly-inserted fixture table MySQL
 * commonly returns rows in primary-key order anyway even with no ORDER BY (clustered index scan),
 * so this asserts directly on the SQL the repository generates - the only way to distinguish "an
 * explicit ORDER BY is present" from "the physical storage order happened to match" without
 * touching non-public API.
 *
 * Class DoctrineSummitAttendeeRepositoryTest
 */
final class DoctrineSummitAttendeeRepositoryTest extends TestCase
{
    use InsertSummitTestData;

    use InsertMemberTestData;

    protected function setUp(): void
    {
        parent::setUp();

        self::insertMemberTestData(IGroup::TrackChairs);
        self::$defaultMember = self::$member;
        self::insertSummitTestData();
    }

    protected function tearDown(): void
    {
        self::clearSummitTestData();
        self::clearMemberTestData();
        parent::tearDown();
    }

    public function testGetAllIdsByPageWithNoExplicitOrderStillGeneratesAnOrderByOnId(): void
    {
        $repository = App::make(ISummitAttendeeRepository::class);

        $filter = new Filter();
        $filter->addFilterCondition(FilterElement::makeEqual('summit_id', self::$summit->getId()));

        $captured_sql = [];
        $logger = new class($captured_sql) implements SQLLogger {
            private array $sink;

            public function __construct(array &$sink)
            {
                $this->sink = &$sink;
            }

            public function startQuery($sql, ?array $params = null, ?array $types = null)
            {
                $this->sink[] = $sql;
            }

            public function stopQuery()
            {
            }
        };

        $connection = Registry::getManager(SilverstripeBaseModel::EntityManager)->getConnection();
        $previous_logger = $connection->getConfiguration()->getSQLLogger();
        $connection->getConfiguration()->setSQLLogger($logger);

        try {
            $repository->getAllIdsByPage(new PagingInfo(1, 2), $filter);
        } finally {
            $connection->getConfiguration()->setSQLLogger($previous_logger);
        }

        $this->assertNotEmpty($captured_sql, 'expected at least one query to be logged for getAllIdsByPage');

        $select_queries = array_values(array_filter($captured_sql, function ($sql) {
            return stripos($sql, 'SELECT') === 0;
        }));

        $this->assertNotEmpty($select_queries, 'expected the paginated attendee id SELECT to be logged');

        $matched = array_filter($select_queries, function ($sql) {
            return stripos($sql, 'ORDER BY') !== false;
        });

        $this->assertNotEmpty(
            $matched,
            sprintf(
                'getAllIdsByPage with no explicit Order must still generate an ORDER BY clause so pagination is deterministic across pages; captured SQL: %s',
                implode(" | ", $select_queries)
            )
        );
    }
}
