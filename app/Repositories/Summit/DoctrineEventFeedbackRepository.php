<?php namespace App\Repositories\Summit;
/**
 * Copyright 2016 OpenStack Foundation
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

use Doctrine\ORM\Cache;
use models\summit\IEventFeedbackRepository;
use models\summit\SummitEventFeedback;
use App\Repositories\SilverStripeDoctrineRepository;
use models\summit\SummitEvent;
use utils\DoctrineFilterMapping;
use utils\PagingInfo;
use utils\Filter;
use utils\Order;
use utils\PagingResponse;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\ORM\Query\Expr\Join;

/**
 * Class DoctrineEventFeedbackRepository
 * @package App\Repositories\Summit
 */
final class DoctrineEventFeedbackRepository
    extends SilverStripeDoctrineRepository
    implements IEventFeedbackRepository
{

    /**
     * @return array
     */
    protected function getFilterMappings()
    {
        return [
            'owner_id' => 'o.id',
            'owner_full_name' => new DoctrineFilterMapping("concat(o.first_name, ' ', o.last_name) :operator :value"),
            'note' => 'f.note'
        ];
    }

    /**
     * @return array
     */
    protected function getOrderMappings()
    {
        return [
            'created'      => 'f.created',
            'owner_id'     => 'o.id',
            'rate'         => 'f.rate',
            'id'           => 'f.id',
            "owner_full_name" => <<<SQL
LOWER(CONCAT(o.first_name, ' ', o.last_name))
SQL,
        ];
    }

    /**
     * @param SummitEvent $event
     * @param PagingInfo $paging_info
     * @param Filter|null $filter
     * @param Order|null $order
     * @return PagingResponse
     */
    public function getByEvent(SummitEvent $event, PagingInfo $paging_info, Filter $filter = null, Order $order = null)
    {
        $query  = $this->getEntityManager()
                ->createQueryBuilder()
                ->select("f")
                ->from(\models\summit\SummitEventFeedback::class, "f")
                ->join('f.event', 'e', Join::WITH, " e.id = :event_id")
                ->join('f.owner', 'o')
                ->setParameter('event_id', $event->getId());

        if(!is_null($filter)){

            $filter->apply2Query($query, $this->getFilterMappings());
        }

        if(!is_null($order))
        {
            $order->apply2Query($query, $this->getOrderMappings());
        }
        else
        {
            //default order
            $query = $query->orderBy('f.created' , Criteria::DESC);
        }

        $query= $query
            ->setFirstResult($paging_info->getOffset())
            ->setMaxResults($paging_info->getPerPage());

        $paginator = new Paginator($query, $fetchJoinCollection = true);
        $total     = $paginator->count();
        $data      = array();

        foreach($paginator as $entity)
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

    /**
     * @return string
     */
    protected function getBaseEntity()
    {
       return SummitEventFeedback::class;
    }
}