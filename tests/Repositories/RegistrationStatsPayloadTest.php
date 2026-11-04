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

use App\Models\Foundation\Main\IGroup;
use App\Models\Foundation\Summit\IStatsConstants;
use DateTime;
use DateTimeZone;
use Illuminate\Support\Facades\DB;
use models\summit\Summit;
use Tests\InsertOrdersTestData;
use Tests\InsertSummitTestData;
use Tests\ProtectedApiTestCase;
use utils\PagingInfo;

/**
 * The registration stats queries filter on SummitAttendeeTicket.SummitID instead of joining
 * SummitOrder. Each test computes the answer the old way (join on the order) and compares it with
 * what the trait returns now, so the payload is proven unchanged rather than assumed.
 *
 * @package Tests\Repositories
 */
final class RegistrationStatsPayloadTest extends ProtectedApiTestCase
{
    use InsertSummitTestData;
    use InsertOrdersTestData;

    // the summit offset is taken at its begin date, so it is pinned to keep the buckets deterministic
    // America/Chicago in July: UTC-05:00
    private const SummitBeginDate = '2026-07-15 12:00:00';
    private const LegacyOffset = '-05:00';

    private Summit $stats_summit;

    /**
     * @var int[] ticket ids, one per seeded order
     */
    private array $ticket_ids = [];

    protected function setUp(): void
    {
        $this->current_group = IGroup::TrackChairs;
        parent::setUp();
        self::insertSummitTestData();
        self::InsertOrdersTestData();

        self::$summit->setBeginDate(new DateTime(self::SummitBeginDate, new DateTimeZone('UTC')));
        self::$em->flush();

        foreach (self::$summit_orders as $order) {
            $this->ticket_ids[] = $order->getTickets()->first()->getId();
        }

        // [0] inactive, [2] bought at 23:30 local on Jul 15, [3] bought 23:30 local on the day DST ends
        DB::table('SummitAttendeeTicket')->where('ID', $this->ticket_ids[0])->update(['IsActive' => 0]);
        DB::table('SummitAttendeeTicket')->whereIn('ID', array_slice($this->ticket_ids, 1))
            ->update(['TicketBoughtDate' => '2026-07-20 15:00:00']);
        DB::table('SummitAttendeeTicket')->where('ID', $this->ticket_ids[2])
            ->update(['TicketBoughtDate' => '2026-07-16 04:30:00']);
        DB::table('SummitAttendeeTicket')->where('ID', $this->ticket_ids[3])
            ->update(['TicketBoughtDate' => '2026-11-02 05:30:00']);

        $id = self::$summit->getId();
        self::$em->clear();
        $this->stats_summit = self::$em->find(Summit::class, $id);
    }

    protected function tearDown(): void
    {
        self::clearOrdersTestData();
        self::clearSummitTestData();
        parent::tearDown();
    }

    private function legacyScalar(string $sql): int
    {
        $rows = DB::connection('model')->select($sql, ['summit_id' => $this->stats_summit->getId()]);
        return intval(array_values((array)$rows[0])[0]);
    }

    public function testTicketCountsMatchTheOrderJoin(): void
    {
        $join = 'FROM SummitAttendeeTicket t INNER JOIN SummitOrder o ON o.ID = t.OrderID WHERE o.SummitID = :summit_id';

        $this->assertSame(9, $this->stats_summit->getActiveTicketsCount());
        $this->assertSame(
            $this->legacyScalar("SELECT COUNT(t.ID) $join AND t.IsActive = 1 AND t.Status = 'Paid'"),
            $this->stats_summit->getActiveTicketsCount()
        );
        $this->assertSame(1, $this->stats_summit->getInactiveTicketsCount());
        $this->assertSame(
            $this->legacyScalar("SELECT COUNT(t.ID) $join AND t.IsActive = 0 AND t.Status = 'Paid'"),
            $this->stats_summit->getInactiveTicketsCount()
        );
        $this->assertSame(
            $this->legacyScalar(
                "SELECT COUNT(t.ID) FROM SummitAttendeeTicket t INNER JOIN SummitOrder o ON o.ID = t.OrderID
                 INNER JOIN SummitAttendee a ON a.ID = t.OwnerID
                 WHERE o.SummitID = :summit_id AND t.IsActive = 1 AND t.Status = 'Paid'"
            ),
            $this->stats_summit->getActiveAssignedTicketsCount()
        );
    }

    public function testTicketsPerTypeMatchTheOrderJoin(): void
    {
        $legacy = DB::connection('model')->select(
            "SELECT tt.Name AS type, COUNT(t.ID) AS qty FROM SummitAttendeeTicket t
             INNER JOIN SummitOrder o ON o.ID = t.OrderID
             INNER JOIN SummitTicketType tt ON t.TicketTypeID = tt.ID
             WHERE o.SummitID = :summit_id AND t.IsActive = 1 AND t.Status = 'Paid' GROUP BY tt.Name",
            ['summit_id' => $this->stats_summit->getId()]
        );
        $legacy = array_map(fn($r) => ['type' => $r->type, 'qty' => intval($r->qty)], $legacy);
        $current = array_map(
            fn($r) => ['type' => $r['type'], 'qty' => intval($r['qty'])],
            $this->stats_summit->getActiveTicketsCountPerTicketType()
        );

        $this->assertSame([['type' => 'TICKET TYPE 1', 'qty' => 9]], $current);
        $this->assertSame($legacy, $current);
    }

    /**
     * Same buckets as the query that joined SummitOrder, including the ticket bought at 23:30 local
     * time (still Jul 15 although it is already Jul 16 in UTC).
     */
    public function testPurchasedTicketsHistogramMatchesTheOrderJoin(): void
    {
        $offset = self::LegacyOffset;
        $legacy = DB::connection('model')->select(
            "SELECT COUNT(t.ID) AS qty, DATE_FORMAT(CONVERT_TZ(t.TicketBoughtDate,'+00:00','{$offset}'), '%b %D') AS label
             FROM SummitAttendeeTicket t INNER JOIN SummitOrder o ON o.ID = t.OrderID
             WHERE o.SummitID = :summit_id AND t.IsActive = 1 AND t.Status = 'Paid'
             GROUP BY DATE_FORMAT(CONVERT_TZ(t.TicketBoughtDate,'+00:00','{$offset}'), '%b %D')
             ORDER BY t.TicketBoughtDate ASC",
            ['summit_id' => $this->stats_summit->getId()]
        );
        $legacy = array_map(fn($r) => ['qty' => intval($r->qty), 'label' => $r->label], $legacy);

        $response = $this->stats_summit->getPurchasedTicketsGroupedBy(IStatsConstants::GroupByDay, new PagingInfo(1, 100));
        $current = array_map(
            fn($r) => ['qty' => intval($r['qty']), 'label' => $r['label']],
            $response->getItems()
        );

        $this->assertSame($legacy, $current);
        $this->assertSame(count($legacy), $response->getTotal());
        $this->assertSame('Jul 15th', $current[0]['label'], 'the 23:30 local purchase belongs to Jul 15');
        $this->assertSame(1, $current[0]['qty']);
    }

    /**
     * Pins the known limitation documented on getTimeZoneOffset(): one fixed offset for the whole
     * summit. The ticket bought at 23:30 local time on Nov 1 (UTC-06:00 after DST ended) is bucketed
     * with the July offset (UTC-05:00) and lands on Nov 2. Not a bug of this change; if the offset
     * is ever fixed, this expectation changes with it.
     */
    public function testHistogramKeepsTheFixedSummitOffsetAcrossDst(): void
    {
        $response = $this->stats_summit->getPurchasedTicketsGroupedBy(IStatsConstants::GroupByDay, new PagingInfo(1, 100));
        $labels = array_column($response->getItems(), 'label');

        $this->assertContains('Nov 2nd', $labels);
        $this->assertNotContains('Nov 1st', $labels);
    }

    public function testHistogramPaginatesTheBucketsInMemory(): void
    {
        $page1 = $this->stats_summit->getPurchasedTicketsGroupedBy(IStatsConstants::GroupByDay, new PagingInfo(1, 2));
        $page2 = $this->stats_summit->getPurchasedTicketsGroupedBy(IStatsConstants::GroupByDay, new PagingInfo(2, 2));

        $this->assertSame(3, $page1->getTotal());
        $this->assertSame(3, $page2->getTotal());
        $this->assertSame(['Jul 15th', 'Jul 20th'], array_column($page1->getItems(), 'label'));
        $this->assertSame(['Nov 2nd'], array_column($page2->getItems(), 'label'));
        $this->assertSame(2, $page1->getLastPage());
    }
}
