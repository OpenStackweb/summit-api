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
use App\Repositories\SilverStripeDoctrineRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use models\summit\ISummitTicketTypeRepository;
use models\summit\Summit;
use models\summit\SummitTicketType;
use models\utils\SilverstripeBaseModel;
use utils\Filter;
use utils\Order;
use utils\PagingInfo;
use utils\PagingResponse;
/**
 * Class DoctrineSummitTicketTypeRepository
 * @package App\Repositories\Summit
 */
final class DoctrineSummitTicketTypeRepository
    extends SilverStripeDoctrineRepository
    implements ISummitTicketTypeRepository
{
    /**
     * @return string
     */
    protected function getBaseEntity()
    {
        return SummitTicketType::class;
    }

    protected function getFilterMappings()
    {
        return [
            'id' => 'e.id',
            'badge_type_id' => 'bt.id',
            'name'        => 'e.name:json_string',
            'description' => 'e.description:json_string',
            'external_id' => 'e.external_id:json_string',
            'audience'    => 'e.audience:json_string',
            'sales_start_date' => 'e.sales_start_date:datetime_epoch',
            'sales_end_date' => 'e.sales_end_date:datetime_epoch',
            'created'        => sprintf('e.created:datetime_epoch|%s', SilverstripeBaseModel::DefaultTimeZone),
            'last_edited'    => sprintf('e.last_edited:datetime_epoch|%s', SilverstripeBaseModel::DefaultTimeZone),
            'allows_to_delegate' => 'e.allows_to_delegate:json_boolean',
        ];
    }

    /**
     * @return array
     */
    protected function getOrderMappings()
    {
        return [
            'created'     => 'e.created',
            'name'        => 'e.name',
            'id'          => 'e.id',
            'external_id' => 'e.external_id',
            'audience'    => 'e.audience',
            'allows_to_delegate' => 'e.allows_to_delegate',
        ];
    }

    /**
     * @param Summit $summit
     * @param PagingInfo $paging_info
     * @param Filter|null $filter
     * @param Order|null $order
     * @return PagingResponse
     */
    public function getBySummit
    (
        Summit $summit,
        PagingInfo $paging_info,
        Filter $filter = null,
        Order $order = null
    )
    {
        $query  =   $this->getEntityManager()
            ->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e")
            ->leftJoin('e.summit', 's')
            ->leftJoin('e.badge_type', 'bt')
            ->where("s.id = :summit_id");

        $query->setParameter("summit_id", $summit->getId());

        if(!is_null($filter)){
            $filter->apply2Query($query, $this->getFilterMappings());
        }

        if (!is_null($order)) {
            $order->apply2Query($query, $this->getOrderMappings());
        } else {
            //default order
            $query = $query->addOrderBy("e.id",'ASC');
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
     * @param Summit $summit
     * @param array $ids
     * @return SummitTicketType[]
     */
    public function getByIdsExclusiveLock(Summit $summit, array $ids)
    {
        $query = $this->getEntityManager()
            ->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e")
            ->leftJoin('e.summit', 's')
            ->where("s.id = :summit_id")
            ->andWhere("e.id in (:ticket_ids)");

        $query->setParameter("summit_id", $summit->getId());
        $query->setParameter("ticket_ids", $ids);
        return $query->getQuery()
            ->setLockMode(\Doctrine\DBAL\LockMode::PESSIMISTIC_WRITE)
            ->setHint(\Doctrine\ORM\Query::HINT_REFRESH, true)
            ->getResult();
    }

    /**
     * @param Summit $summit
     * @param string $type
     * @return SummitTicketType|null
     */
    public function getByType(Summit $summit, string $type): ?SummitTicketType
    {
        $query = $this->getEntityManager()
            ->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e")
            ->leftJoin('e.summit', 's')
            ->where("s.id = :summit_id")
            ->andWhere("e.name = :type");

        $query->setParameter("summit_id", $summit->getId());
        $query->setParameter("type", $type);

        return $query->getQuery()->getOneOrNullResult();
    }
}