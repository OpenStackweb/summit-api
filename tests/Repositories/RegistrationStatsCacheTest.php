<?php namespace Tests\Repositories;
/*
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

use App\Http\Middleware\Doctrine\QueryTimingCollector;
use App\Models\Foundation\Main\IGroup;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use ModelSerializers\SerializerRegistry;
use Mockery;
use Tests\InsertOrdersTestData;
use Tests\InsertSummitTestData;
use Tests\ProtectedApiTestCase;
use utils\Filter;
use utils\FilterParser;

/**
 * The registration stats response is cached per summit and date range. Tests observe how many
 * queries a sequence of requests runs, never the cache key itself.
 *
 * @package Tests\Repositories
 */
final class RegistrationStatsCacheTest extends ProtectedApiTestCase
{
    use InsertSummitTestData;
    use InsertOrdersTestData;

    private const Lifetime = 'cache_api_response.registration_stats_response_lifetime';

    /**
     * @var array
     */
    private array $store = [];

    /**
     * @var int[] ttl of every Cache::put
     */
    private array $ttls = [];

    protected function setUp(): void
    {
        $this->current_group = IGroup::TrackChairs;
        parent::setUp();
        self::insertSummitTestData();
        self::InsertOrdersTestData();

        $this->store = [];
        $this->ttls = [];
        // a stateful fake keeps the test off redis; clearing $store stands for the entry expiring
        Cache::shouldReceive('put')->andReturnUsing(function ($key, $value, $ttl = null) {
            $this->store[$key] = $value;
            $this->ttls[] = $ttl;
            return true;
        });
        Cache::shouldReceive('get')->andReturnUsing(function ($key, $default = null) {
            return $this->store[$key] ?? $default;
        });
    }

    protected function tearDown(): void
    {
        Mockery::close();
        self::clearOrdersTestData();
        self::clearSummitTestData();
        parent::tearDown();
    }

    private function filter(?int $start = null, ?int $end = null): Filter
    {
        if (is_null($start)) return new Filter();
        $expression = ["start_date>=$start"];
        if (!is_null($end)) $expression[] = "end_date<=$end";
        return FilterParser::parse($expression, ['start_date' => ['>='], 'end_date' => ['<=']]);
    }

    /**
     * @return array [payload, number of queries the request ran]
     */
    private function request(?Filter $filter = null): array
    {
        $serializer = SerializerRegistry::getInstance()
            ->getSerializer(self::$summit, SerializerRegistry::SerializerType_Admin_Registration_Stats);
        QueryTimingCollector::reset();
        $payload = $serializer->serialize(null, [], [], ['filter' => $filter ?? new Filter()]);
        return [$payload, QueryTimingCollector::$count];
    }

    public function testSecondRequestInsideTheLifetimeDoesNotRunTheQueries(): void
    {
        Config::set(self::Lifetime, 600);

        [$first, $first_queries] = $this->request();
        [$second, $second_queries] = $this->request();

        $this->assertGreaterThan(0, $first_queries, 'the first request must run the stats queries');
        $this->assertSame(0, $second_queries);
        $this->assertSame($first, $second);
        $this->assertSame([600], $this->ttls);
    }

    public function testRequestAfterTheLifetimeRunsTheQueriesAgain(): void
    {
        Config::set(self::Lifetime, 600);

        $this->request();
        $this->store = []; // the entry expired

        [, $queries] = $this->request();

        $this->assertGreaterThan(0, $queries);
        $this->assertCount(2, $this->ttls);
    }

    public function testZeroLifetimeDisablesTheCache(): void
    {
        Config::set(self::Lifetime, 0);

        [, $first_queries] = $this->request();
        [, $second_queries] = $this->request();

        $this->assertGreaterThan(0, $first_queries);
        $this->assertSame($first_queries, $second_queries);
        $this->assertSame([], $this->ttls, 'nothing is stored when the cache is off');
    }

    public function testDifferentDateRangesDoNotShareAnEntry(): void
    {
        Config::set(self::Lifetime, 600);
        $start = time() - 86400;

        [, $unfiltered] = $this->request();
        [, $filtered] = $this->request($this->filter($start, $start + 3600));
        [, $filtered_again] = $this->request($this->filter($start, $start + 3600));
        [, $other_range] = $this->request($this->filter($start, $start + 7200));

        $this->assertGreaterThan(0, $unfiltered);
        $this->assertGreaterThan(0, $filtered, 'a date range must not read the unfiltered entry');
        $this->assertSame(0, $filtered_again);
        $this->assertGreaterThan(0, $other_range);
        $this->assertCount(3, $this->store);
    }
}
