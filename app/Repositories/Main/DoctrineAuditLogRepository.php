<?php namespace repositories\main;
/**
 * Copyright 2022 OpenStack Foundation
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

use App\Models\Foundation\Main\Repositories\IAuditLogRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use models\main\AuditLog;
use App\Repositories\SilverStripeDoctrineRepository;
use models\main\Member;
use models\main\SummitAuditLog;
use models\main\SummitEventAuditLog;
use utils\DoctrineFilterMapping;
use utils\DoctrineInstanceOfFilterMapping;
use utils\Filter;
use utils\Order;
use utils\PagingInfo;
use utils\PagingResponse;

/**
 * Class DoctrineAuditLogRepository
 * @package repositories\main
 */
final class DoctrineAuditLogRepository
    extends SilverStripeDoctrineRepository
    implements IAuditLogRepository
{
//    protected function getFilterMappings(): array
//    {
//        return [
//            'summit_id'      => new DoctrineFilterMapping("sal.summit_id :operator :value"),
//            'event_id'       => new DoctrineFilterMapping("seal.event_id :operator :value"),
//            'user_id'        => new DoctrineFilterMapping("u.id :operator :value"),
//            'user_email'     => new DoctrineFilterMapping("u.email :operator :value"),
//            'user_full_name' => new DoctrineFilterMapping("concat(u.first_name, ' ', u.last_name) :operator :value"),
//            'action'         => 'e.action:json_string',
//        ];
//    }

    protected function getCustomFilterMappings(): array
    {
        return [
            'class_name' => new DoctrineInstanceOfFilterMapping(
                "e",
                [
                    SummitAuditLog::ClassName => SummitAuditLog::class,
                    SummitEventAuditLog::ClassName => SummitEventAuditLog::class,
                ]
            ),
            'summit_id'      => new DoctrineFilterMapping("sal.summit :operator :value"),
            'event_id'       => new DoctrineFilterMapping("seal.event :operator :value"),
            'user_id'        => new DoctrineFilterMapping("u.id :operator :value"),
            'user_email'     => new DoctrineFilterMapping("u.email :operator :value"),
            'user_full_name' => new DoctrineFilterMapping("concat(u.first_name, ' ', u.last_name) :operator :value"),
            'action'         => 'e.action:json_string',
        ];
    }

    protected function getOrderMappings(): array
    {
        return [
            'id' => 'e.id',
            'user_id' => 'e.user_id',
        ];
    }

    /**
     * @return string
     */
    protected function getBaseEntity()
    {
        return AuditLog::class;
    }

    public function getAllByPage(PagingInfo $paging_info, Filter $filter = null, Order $order = null): PagingResponse
    {
        $query = $this->getEntityManager()->createQueryBuilder()
            ->distinct("e")
            ->select("e")
            ->from($this->getBaseEntity(), "e")
            ->leftJoin(SummitAuditLog::class, 'sal', 'WITH', 'e.id = sal.id')
            ->leftJoin(SummitEventAuditLog::class, 'seal', 'WITH', 'e.id = seal.id')
            ->leftJoin(Member::class, 'u', 'WITH', 'e.user = u.id');

        $query = $this->applyExtraJoins($query, $filter);

        if (!is_null($filter)) {
            $filter->apply2Query($query, $this->getCustomFilterMappings());
        }

        if (!is_null($order)) {
            $order->apply2Query($query, [
                'id' => 'e.id',
            ]);
        } else {
            //default order
            $query = $query->addOrderBy("e.id", 'ASC');
        }

        $query = $query
            ->setFirstResult($paging_info->getOffset())
            ->setMaxResults($paging_info->getPerPage());

        $paginator = new Paginator($query);
        $total = $paginator->count();
        $data = [];

        foreach ($paginator as $entity)
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
}