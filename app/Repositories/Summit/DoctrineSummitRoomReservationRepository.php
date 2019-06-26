<?php namespace App\Repositories\Summit;
/**
 * Copyright 2019 OpenStack Foundation
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
use App\Models\Foundation\Summit\Repositories\ISummitRoomReservationRepository;
use App\Repositories\SilverStripeDoctrineRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use models\summit\Summit;
use models\summit\SummitRoomReservation;
use utils\DoctrineFilterMapping;
use utils\DoctrineJoinFilterMapping;
use utils\Filter;
use utils\Order;
use utils\PagingInfo;
use utils\PagingResponse;

/**
 * Class DoctrineSummitRoomReservationRepository
 * @package App\Repositories\Summit
 */
class DoctrineSummitRoomReservationRepository
    extends SilverStripeDoctrineRepository
    implements ISummitRoomReservationRepository
{

    /**
     * @return string
     */
    protected function getBaseEntity()
    {
        return SummitRoomReservation::class;
    }

    /**
     * @return array
     */
    protected function getFilterMappings()
    {
        return [
            'status'         => 'e.status:json_string',
            'start_datetime' => 'e.start_datetime:datetime_epoch',
            'end_datetime'   => 'e.end_datetime:datetime_epoch',
            'created'        => 'e.created:datetime_epoch',
            'room_id' => new DoctrineJoinFilterMapping
            (
                'e.room',
                'r',
                "r.id :operator :value"
            ),
            'venue_id' => new DoctrineJoinFilterMapping
            (
                'r.venue',
                'v',
                "v.id :operator :value"
            ),
            'owner_id' => new DoctrineJoinFilterMapping
            (
                'e.owner',
                'o',
                "o.id :operator :value"
            ),
        ];
    }

    /**
     * @param Summit $summit
     * @param PagingInfo $paging_info
     * @param Filter|null $filter
     * @param Order|null $order
     * @return PagingResponse
     */
    public function getAllBySummitByPage(Summit $summit, PagingInfo $paging_info, Filter $filter = null, Order $order = null):PagingResponse{

        $query  = $this->getEntityManager()
            ->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e")
            ->join("e.room","r1")
            ->join("r1.venue", "v1")
            ->join("v1.summit", "s1")
            ->where("s1.id = ".$summit->getId())
        ;

        $query = $this->applyExtraFilters($query);

        if(!is_null($filter)){
            $filter->apply2Query($query, $this->getFilterMappings());
        }

        if(!is_null($order)){
            $order->apply2Query($query, $this->getOrderMappings());
        }

        $query = $query
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
     * @return array
     */
    protected function getOrderMappings()
    {
        return [
            'id'              => 'e.id',
            'start_datetime'  => 'e.start_datetime',
            'end_datetime'    => 'e.end_datetime',
        ];
    }

    /**
     * @param string $payment_gateway_cart_id
     * @return SummitRoomReservation|null
     */
    public function getByPaymentGatewayCartId(string $payment_gateway_cart_id):?SummitRoomReservation
    {
        return $this->findOneBy(["payment_gateway_cart_id" => trim($payment_gateway_cart_id)]);
    }


}