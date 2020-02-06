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
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use models\main\Member;
use models\summit\ISummitNotificationRepository;
use models\summit\Summit;
use models\summit\SummitPushNotification;
use App\Repositories\SilverStripeDoctrineRepository;
use models\summit\SummitPushNotificationChannel;
use utils\DoctrineFilterMapping;
use utils\Filter;
use utils\Order;
use utils\PagingInfo;
use utils\PagingResponse;
/**
 * Class DoctrineSummitNotificationRepository
 * @package App\Repositories\Summit
 */
final class DoctrineSummitNotificationRepository
    extends SilverStripeDoctrineRepository
    implements ISummitNotificationRepository
{

    /**
     * @return array
     */
    protected function getFilterMappings()
    {
        return [
            'event_id'  => 'e.id:json_int',
            'message'   => 'n.message:json_string',
            'channel'   => 'n.channel:json_string',
            'sent_date' => 'n.sent_date:datetime_epoch',
            'created'   => 'n.created:datetime_epoch',
            'is_sent'   => 'n.is_sent:json_boolean',
            'approved'  => 'n.approved:json_boolean',
            'recipient_id'  => new DoctrineFilterMapping("
                r.id :operator :value
            "),
            'group_id'  => new DoctrineFilterMapping("
                g.id :operator :value
            ")
        ];
    }

    /**
     * @return array
     */
    protected function getOrderMappings()
    {
        return [
            'sent_date' => 'n.sent_date',
            'created'   => 'n.created',
            'id'        => 'n.id',
        ];
    }

    /**
     * @param Member|null $current_member
     * @param Summit $summit
     * @param PagingInfo $paging_info
     * @param Filter|null $filter
     * @param Order|null $order
     * @return PagingResponse
     */
    public function getAllByPageByUserBySummit
    (
        ?Member $current_member,
        Summit $summit,
        PagingInfo $paging_info,
        Filter $filter = null,
        Order $order = null
    ):PagingResponse
    {
        $query = $this->createBaseQuery($summit);

        $query->orWhere(sprintf("n.channel = '%s'", SummitPushNotificationChannel::Everyone));
        $query->orWhere(sprintf("n.channel = '%s'", SummitPushNotificationChannel::Summit));

        if(!is_null($current_member)){

            $groups_ids = $current_member->getGroupsIds();
            $events_ids = $current_member->getScheduledEventsIds($summit);

            $query->orWhere(sprintf("r = :current_member and n.channel = '%s'", SummitPushNotificationChannel::Members))->setParameter("current_member", $current_member);

            if(count($groups_ids) > 0){
                $query->orWhere(sprintf("g.id in (:groups_ids) and n.channel = '%s'", SummitPushNotificationChannel::Group))->setParameter("groups_ids", $groups_ids);
            }

            if(count($events_ids) > 0){
                $query->orWhere(sprintf("e.id in (:events_id) and n.channel = '%s'", SummitPushNotificationChannel::Event))->setParameter('events_id', $events_ids);
                $query->orWhere(sprintf("n.channel = '%s'", SummitPushNotificationChannel::Attendees));
            }

            if($current_member->hasSpeaker() && $current_member->getSpeaker()->isSpeakerOfSummit($summit)){
                $query->orWhere(sprintf("n.channel = '%s'", SummitPushNotificationChannel::Speakers));
            }
        }
        return $this->applyPaginationLogic($query, $paging_info, $filter, $order);
    }

    /**
     * @param QueryBuilder $query
     * @param PagingInfo $paging_info
     * @param Filter|null $filter
     * @param Order|null $order
     * @return PagingResponse
     */
    private function applyPaginationLogic(QueryBuilder $query,
                                          PagingInfo $paging_info,
                                          Filter $filter = null,
                                          Order $order = null):PagingResponse{

        if (!is_null($filter)) {
            $filter->apply2Query($query, $this->getFilterMappings());
        }

        if (!is_null($order)) {
            $order->apply2Query($query, $this->getOrderMappings());
        } else {
            //default order
            $query = $query->orderBy('n.id', Criteria::DESC);
        }

        $query = $query
            ->setFirstResult($paging_info->getOffset())->setMaxResults($paging_info->getPerPage());

        $paginator = new Paginator($query, $fetchJoinCollection = true);
        $total     = $paginator->count();
        $data      = array();

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
    /**
     * @param Summit $summit
     * @return QueryBuilder
     */
    private function createBaseQuery( Summit $summit):QueryBuilder{
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select("n")
            ->from(SummitPushNotification::class, "n")
            ->leftJoin('n.summit_event', 'e')
            ->leftJoin('n.group', 'g')
            ->leftJoin('n.recipients', 'r')
            ->join('n.summit', 's', Join::WITH, " s.id = :summit_id")
            ->setParameter('summit_id', $summit->getId());
    }


    /**
     * @param Summit $summit
     * @param PagingInfo $paging_info
     * @param Filter|null $filter
     * @param Order|null $order
     * @return PagingResponse
     */
    public function getAllByPageBySummit
    (
        Summit $summit,
        PagingInfo $paging_info,
        Filter $filter = null,
        Order $order = null
    ):PagingResponse
    {
        return $this->applyPaginationLogic($this->createBaseQuery($summit), $paging_info, $filter, $order);
    }

    /**
     * @return string
     */
    protected function getBaseEntity()
    {
       return SummitPushNotification::class;
    }
}