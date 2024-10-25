<?php namespace App\Repositories\Summit;
/**
 * Copyright 2023 OpenStack Foundation
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

use App\Models\Foundation\Summit\ProposedSchedule\SummitProposedScheduleSummitEvent;
use App\Repositories\SilverStripeDoctrineRepository;
use Doctrine\ORM\QueryBuilder;
use models\summit\ISummitProposedScheduleEventRepository;
use utils\Filter;
use utils\Order;

/**
 * Class DoctrineSummitProposedScheduleEventRepository
 * @package App\Repositories\Summit
 */
final class DoctrineSummitProposedScheduleEventRepository
    extends SilverStripeDoctrineRepository
    implements ISummitProposedScheduleEventRepository
{
    /**
     * @return string
     */
    protected function getBaseEntity()
    {
       return SummitProposedScheduleSummitEvent::class;
    }

    /**
     * @param QueryBuilder $query
     * @param Filter|null $filter
     * @return QueryBuilder
     */
    protected function applyExtraJoins(QueryBuilder $query, ?Filter $filter = null, ?Order $order = null)
    {
        return $query->innerJoin('e.summit_proposed_schedule', 's')
            ->innerJoin('e.summit_event', 'ev')
            ->innerJoin("ev.type","et")
            ->innerJoin('ev.category', 'cat');
    }

    protected function getFilterMappings()
    {
        return [
            'summit_id'          => 's.summit:json_int',
            'source'             => 's.source:json_string',
            'start_date'         => 'e.start_date:datetime_epoch',
            'end_date'           => 'e.end_date:datetime_epoch',
            'duration'           => 'e.duration:json_int',
            'location_id'        => 'e.location:json_int',
            'presentation_id'    => 'ev.id:json_int',
            'presentation_title' => 'ev.title:json_string',
            'track_id'           => 'cat.id:json_int',
            'type_show_always_on_schedule' => 'et.show_always_on_schedule',
        ];
    }

    /**
     * @return array
     */
    protected function getOrderMappings(): array
    {
        return [
            'start_date'            => 'e.start_date',
            'end_date'              => 'e.end_date',
            'presentation_id'       => 'ev.id',
            'presentation_title'    => 'ev.title',
            'track_id'              => 'cat.id',
        ];
    }

    /**
     * @param int $summit_id
     * @param string $source
     * @param int $event_id
     * @return SummitProposedScheduleSummitEvent|null
     */
    public function getBySummitSourceAndEventId(int $summit_id, string $source, int $event_id): ?SummitProposedScheduleSummitEvent
    {
        $query = $this->getEntityManager()->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e")
            ->join("e.summit_event","ev")
            ->join("e.summit_proposed_schedule","ps")
            ->join("ps.summit","s")
            ->where('ps.source = :source')
            ->andWhere('s.id = :summit_id')
            ->andWhere('ev.id = :event_id')
            ->setParameter('source', $source)
            ->setParameter('summit_id', $summit_id)
            ->setParameter('event_id', $event_id);

        return $query->getQuery()->getOneOrNullResult();
    }
}