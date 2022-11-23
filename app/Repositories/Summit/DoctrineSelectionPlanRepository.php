<?php namespace App\Repositories\Summit;
/**
 * Copyright 2018 OpenStack Foundation
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

use App\Models\Foundation\Summit\Repositories\ISelectionPlanRepository;
use App\Models\Foundation\Summit\SelectionPlan;
use App\Repositories\SilverStripeDoctrineRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use models\main\Member;
use utils\DoctrineCaseFilterMapping;
use utils\DoctrineFilterMapping;
use utils\DoctrineSwitchFilterMapping;
use utils\Filter;
use utils\Order;
use utils\PagingInfo;
use utils\PagingResponse;

/**
 * Class DoctrineSelectionPlanRepository
 * @package App\Repositories\Summit
 */
final class DoctrineSelectionPlanRepository
    extends SilverStripeDoctrineRepository
    implements ISelectionPlanRepository
{

    /**
     * @return string
     */
    protected function getBaseEntity()
    {
        return SelectionPlan::class;
    }

    /**
     * @param QueryBuilder $query
     * @param Filter|null $filter
     * @return QueryBuilder
     */
    protected function applyExtraJoins(QueryBuilder $query, ?Filter $filter = null)
    {
        if ($filter->hasFilter("summit_id")) {
            $query = $query->join('e.summit', 's');
        }
        return $query;
    }

    /**
     * @return array
     */
    protected function getFilterMappings()
    {
        $now_utc = date_format(new \DateTime('now', new \DateTimeZone('UTC')), "Y/m/d H:i:s");

        return [
            'summit_id' => new DoctrineFilterMapping("s.id :operator :value"),
            'status' => new DoctrineSwitchFilterMapping([
                    'submission' => new DoctrineCaseFilterMapping(
                        'submission',
                        "e.submission_begin_date <= '$now_utc' and e.submission_end_date >= '$now_utc'"
                    ),
                    'selection' => new DoctrineCaseFilterMapping(
                        'selection',
                        "e.selection_begin_date <= '$now_utc' and e.selection_end_date >= '$now_utc'",
                    ),
                    'voting' => new DoctrineCaseFilterMapping(
                        'voting',
                        "e.voting_begin_date <= '$now_utc' and e.voting_end_date >= '$now_utc'",
                    ),
                ]
            ),
        ];
    }

    /**
     * @param PagingInfo $paging_info
     * @param Filter|null $filter
     * @param Order|null $order
     * @return PagingResponse
     */
    public function getAllAllowedMembersByPage(PagingInfo $paging_info, Filter $filter = null, Order $order = null): PagingResponse
    {
        $query = $this->getEntityManager()
            ->createQueryBuilder()
            ->select("m")
            ->from(Member::class, "m")
            ->innerJoin("m.allowed_selection_plans", "sp")
            ->innerJoin("sp.summit", "s");

        if (!is_null($filter)) {
            $filter->apply2Query($query, [
                'summit_id' => 's.id',
                'id' => 'sp.id',
                'member_full_name' => new DoctrineFilterMapping("( CONCAT(LOWER(m.first_name), ' ', LOWER(m.last_name)) :operator LOWER(:value) )"),
                'member_email' => new DoctrineFilterMapping("LOWER(m.email) :operator LOWER(:value)")
            ]);
        }

        if (!is_null($order)) {
            $order->apply2Query($query, [
                "member_full_name" => <<<SQL
LOWER(CONCAT(m.first_name, ' ', m.last_name))
SQL,
            ]);
        }

        $query = $query
            ->setFirstResult($paging_info->getOffset())
            ->setMaxResults($paging_info->getPerPage());

        $paginator = new Paginator($query, $fetchJoinCollection = true);
        $total = $paginator->count();
        $data = [];

        foreach ($paginator as $entity)
            array_push($data, $entity);

        return new PagingResponse
        (
            $total,
            $paging_info->getPerPage(),
            $paging_info->getCurrentPage(),
            $paging_info->getLastPage($total),
            $data
        );
    }
}