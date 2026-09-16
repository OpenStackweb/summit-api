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
use LaravelDoctrine\ORM\Facades\Registry;
use models\summit\SummitOrderExtraQuestionTypeConstants;
use models\utils\SilverstripeBaseModel;

/**
 * Covers the memoization fix for Summit::getMainOrderExtraQuestionsByUsage(). Before this fix,
 * every attendee's invitation-flow send (SummitAttendee::getExtraQuestions() ->
 * getMainOrderExtraQuestionsByUsage()) issued an identical, uncached DQL query - the same
 * summit's question set does not change mid-request. This asserts the SQL directly (a
 * black-box, non-test-only-API technique - matching DoctrineSummitAttendeeRepositoryTest's
 * approach in Task 1), the only reliable way to distinguish "queried once" from "queried twice
 * and both times returned the same rows".
 *
 * Class SummitExtraQuestionsMemoizationTest
 */
final class SummitExtraQuestionsMemoizationTest extends TestCase
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

    public function testSecondCallWithSameUsageIssuesNoAdditionalQuery(): void
    {
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
            self::$summit->getMainOrderExtraQuestionsByUsage(SummitOrderExtraQuestionTypeConstants::TicketQuestionUsage);
            $countAfterFirstCall = count(array_filter($captured_sql, fn($sql) => stripos($sql, 'SummitOrderExtraQuestionType') !== false));

            self::$summit->getMainOrderExtraQuestionsByUsage(SummitOrderExtraQuestionTypeConstants::TicketQuestionUsage);
            $countAfterSecondCall = count(array_filter($captured_sql, fn($sql) => stripos($sql, 'SummitOrderExtraQuestionType') !== false));
        } finally {
            $connection->getConfiguration()->setSQLLogger($previous_logger);
        }

        $this->assertSame(1, $countAfterFirstCall, 'the first call must issue exactly one query');
        $this->assertSame(
            $countAfterFirstCall,
            $countAfterSecondCall,
            'a second call with the same usage must not issue an additional query'
        );
    }

    public function testResultsAreIdenticalAcrossCalls(): void
    {
        $first = self::$summit->getMainOrderExtraQuestionsByUsage(SummitOrderExtraQuestionTypeConstants::TicketQuestionUsage);
        $second = self::$summit->getMainOrderExtraQuestionsByUsage(SummitOrderExtraQuestionTypeConstants::TicketQuestionUsage);

        $this->assertSame($first, $second, 'the cached result must be the same data the uncached query would have returned');
    }
}
