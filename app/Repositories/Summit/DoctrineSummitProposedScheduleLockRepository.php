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

use App\Models\Foundation\Summit\ProposedSchedule\SummitProposedScheduleLock;
use App\Repositories\SilverStripeDoctrineRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use models\summit\ISummitProposedScheduleLockRepository;
use utils\Filter;
use utils\Order;
use utils\PagingInfo;
use utils\PagingResponse;

/**
 * Class DoctrineSummitProposedScheduleLockRepository
 * @package App\Repositories\Summit
 */
final class DoctrineSummitProposedScheduleLockRepository
    extends SilverStripeDoctrineRepository
    implements ISummitProposedScheduleLockRepository
{
    /**
     * @return string
     */
    protected function getBaseEntity()
    {
       return SummitProposedScheduleLock::class;
    }

    /**
     * @param QueryBuilder $query
     * @param Filter|null $filter
     * @return QueryBuilder
     */
    protected function applyExtraJoins(QueryBuilder $query, ?Filter $filter = null)
    {
        return $query->innerJoin('e.summit_proposed_schedule', 's')
            ->innerJoin('e.track', 't');
    }

    protected function getFilterMappings()
    {
        return [
            'summit_id'          => 's.summit:json_int',
            'source'             => 's.source:json_string',
            'track_id'           => 't.id:json_int',
        ];
    }

    /**
     * @return array
     */
    protected function getOrderMappings(): array
    {
        return [
            'track_id' => 't.id',
        ];
    }

    /**
     * @inheritDoc
     */
    public function getBySummitAndSource
    (
        int $summit_id,
        string $source,
        PagingInfo $paging_info,
        Filter $filter = null,
        Order $order   = null
    )
    {
        $query = $this->getEntityManager()->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e")
            ->join("e.track","t")
            ->join("e.summit_proposed_schedule","ps")
            ->join("ps.summit","s")
            ->where('ps.source = :source')
            ->andWhere('s.id = :summit_id')
            ->setParameter('source', $source)
            ->setParameter('summit_id', $summit_id);

        if(!is_null($filter)){
            $filter->apply2Query($query, $this->getFilterMappings());
        }

        if (!is_null($order)) {
            $order->apply2Query($query, $this->getOrderMappings());
        } else {
            //default order
            $query = $query->addOrderBy("t.id",'ASC');
        }

        $query = $query
            ->setFirstResult($paging_info->getOffset())
            ->setMaxResults($paging_info->getPerPage());

        $paginator = new Paginator($query, $fetchJoinCollection = true);
        $total     = $paginator->count();
        $data      = [];

        foreach($paginator as $entity)
            $data[] = $entity;

        return new PagingResponse
        (
            $total,
            $paging_info->getPerPage(),
            $paging_info->getCurrentPage(),
            $paging_info->getLastPage($total),
            $data
        );
    }

    /**
     * @inheritDoc
     */
    public function getBySummitAndTrackId(int $summit_id, int $track_id): ?SummitProposedScheduleLock
    {
        $query = $this->getEntityManager()->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e")
            ->join("e.track", "t")
            ->join("e.summit_proposed_schedule","ps")
            ->join("ps.summit","s")
            ->where('s.id = :summit_id')
            ->andWhere('t.id = :track_id')
            ->setParameter('summit_id', $summit_id)
            ->setParameter('track_id', $track_id);

        return $query->getQuery()->getOneOrNullResult();
    }
}