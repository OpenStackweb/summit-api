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
use App\Models\Foundation\Summit\Repositories\ISummitLocationRepository;
use App\Repositories\SilverStripeDoctrineRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use models\summit\Summit;
use models\summit\SummitAbstractLocation;
use models\summit\SummitAirport;
use models\summit\SummitBookableVenueRoom;
use models\summit\SummitExternalLocation;
use models\summit\SummitGeoLocatedLocation;
use models\summit\SummitHotel;
use models\summit\SummitVenue;
use utils\DoctrineHavingFilterMapping;
use utils\DoctrineInstanceOfFilterMapping;
use utils\Filter;
use utils\Order;
use utils\PagingInfo;
use utils\PagingResponse;
use models\summit\SummitVenueRoom;
/**
 * Class DoctrineSummitLocationRepository
 * @package App\Repositories\Summit
 */
final class DoctrineSummitLocationRepository
    extends SilverStripeDoctrineRepository
    implements ISummitLocationRepository
{

    private static $second_level_locations = [
        SummitVenueRoom::ClassName,
        SummitBookableVenueRoom::ClassName,
    ];

    /**
     * @return string
     */
    protected function getBaseEntity()
    {
        return SummitAbstractLocation::class;
    }

    protected function getFilterMappings()
    {
        return [
            'name'             => 'al.name:json_string',
            'rooms_name'       => 'v_rooms.name:json_string',
            'rooms_floor_name' => 'v_rooms_floor.name:json_string',
            'floors_name'      => 'v_floors.name:json_string',
            'description'    => 'al.description:json_string',
            'opening_hour'   => 'al.opening_hour:json_int',
            'closing_hour'   => 'al.closing_hour:json_int',
            'address_1'      => 'gll.address1:json_string',
            'address_2'      => 'gll.address2:json_string',
            'zip_code'       => 'gll.zip_code:json_string',
            'city'           => 'gll.city:json_string',
            'state'          => 'gll.state:json_string',
            'country'        => 'gll.country:json_string',
            'sold_out'       => 'h.sold_out:json_boolean',
            'is_main'        => 'v.is_main:json_boolean',
            'time_slot_cost' => 'br.time_slot_cost',
            'currency'       => 'br.currency',
            'capacity'       => 'r.capacity',
            'attribute'      => new DoctrineHavingFilterMapping
            (
                "bra.value in (:value) or bra.id in (:value)",
                "al.id",
                "count(al) = :value_count"
            ),
            'class_name'     => new DoctrineInstanceOfFilterMapping(
                "al",
                [
                    SummitVenue::ClassName             => SummitVenue::class,
                    SummitHotel::ClassName             => SummitHotel::class,
                    SummitExternalLocation::ClassName  => SummitExternalLocation::class,
                    SummitAirport::ClassName           => SummitAirport::class,
                    SummitBookableVenueRoom::ClassName => SummitBookableVenueRoom::class,
                    SummitVenueRoom::ClassName         => SummitVenueRoom::class,
                ]
            )
        ];
    }

    /**
     * @return array
     */
    protected function getOrderMappings()
    {
        return [
            'id'    => 'al.id',
            'name'  => 'al.name',
            'order' => 'al.order',
        ];
    }

    /**
     * @param Summit $summit
     * @param PagingInfo $paging_info
     * @param Filter|null $filter
     * @param Order|null $order
     * @param bool $first_level
     * @return PagingResponse
     */
    public function getBySummit
    (
        Summit $summit,
        PagingInfo $paging_info,
        Filter $filter = null,
        Order $order = null,
        bool $first_level = true
    )
    {
        $query  =   $this->getEntityManager()
            ->createQueryBuilder()
            ->select("al")
            ->from(SummitAbstractLocation::class, "al")
            ->leftJoin(SummitGeoLocatedLocation::class, 'gll', 'WITH', 'gll.id = al.id')
            ->leftJoin(SummitVenue::class, 'v', 'WITH', 'v.id = gll.id')
            ->leftJoin(SummitExternalLocation::class, 'el', 'WITH', 'el.id = gll.id')
            ->leftJoin(SummitHotel::class, 'h', 'WITH', 'h.id = el.id')
            ->leftJoin(SummitAirport::class, 'ap', 'WITH', 'ap.id = el.id')
            ->leftJoin(SummitVenueRoom::class, 'r', 'WITH', 'r.id = al.id')
            ->leftJoin(SummitBookableVenueRoom::class, 'br', 'WITH', 'br.id = al.id')
            ->leftJoin('br.attributes', 'bra')
            ->leftJoin('al.summit', 's')
            ->where("s.id = :summit_id");

        if($first_level) {
            $idx = 1;
            foreach (self::$second_level_locations as $second_level_location) {
                $query = $query
                    ->andWhere("not al INSTANCE OF :second_level_class" . $idx);
                $query->setParameter("second_level_class" . $idx, $second_level_location);
                $idx++;
            }
        }

        $query->setParameter("summit_id", $summit->getId());

        if(!is_null($filter)){
            $filter->apply2Query($query, $this->getFilterMappings());
        }

        if (!is_null($order)) {
            $order->apply2Query($query, $this->getOrderMappings());
        } else {
            //default order
            $query = $query->addOrderBy("al.id",'ASC');
        }

        if(!is_null($filter) && ($filter->hasFilter("rooms_name") || $filter->hasFilter("rooms_floor_name"))){
            $query = $query->leftJoin('v.rooms', 'v_rooms');
            if($filter->hasFilter("rooms_floor_name")){
                $query = $query->leftJoin('v_rooms.floor', 'v_rooms_floor');
            }
        }
        if(!is_null($filter) && $filter->hasFilter("floors_name")){
            $query = $query->leftJoin('v.floors', 'v_floors');
        }

        if(!is_null($filter) && $filter->hasFilter("availability_day")){
            // special case, we need to figure if each room has available slots
            $res              = $query->getQuery()->execute();
            $rooms            = [];
            $availability_day = $filter->getUniqueFilter("availability_day")->getValue();
            $day              = new \DateTime("@$availability_day");

            foreach ($res as $room){
                if(!$room instanceof SummitBookableVenueRoom) continue;
                if(count($room->getFreeSlots($day)) > 0)
                    $rooms[] = $room;
            }

            return new PagingResponse
            (
                count($rooms),
                $paging_info->getPerPage(),
                $paging_info->getCurrentPage(),
                $paging_info->getLastPage(count($rooms)),
                array_slice( $rooms, $paging_info->getOffset(), $paging_info->getPerPage() )
            );
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
     * @return array
     */
    public function getMetadata(Summit $summit)
    {
        return [
            SummitVenue::getMetadata(),
            SummitAirport::getMetadata(),
            SummitHotel::getMetadata(),
            SummitExternalLocation::getMetadata()
        ];
    }

    /**
     * @param int $summit_id
     * @return bool
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    public function deleteAllBySummit(int $summit_id):bool{
        try {
            $sql = <<<SQL
DELETE E FROM SummitAbstractLocation E WHERE E.SummitID = :summit_id;
SQL;

            $stmt = $this->getEntityManager()->getConnection()->prepare($sql);
            return $stmt->executeStatement([
                'summit_id' => $summit_id,
            ]) > 0;

        }
        catch (\Exception $ex)
        {
            Log::error($ex);
        }
        return true;
    }

    /**
     * @param Summit $summit
     * @param PagingInfo $paging_info
     * @param Filter|null $filter
     * @param Order|null $order
     * @return PagingResponse
     */
    public function getAllVenueRoomsBySummit(Summit $summit, PagingInfo $paging_info, Filter $filter = null, Order $order = null): PagingResponse
    {
        $query  =   $this->getEntityManager()
            ->createQueryBuilder()
            ->select("al")
            ->from(SummitAbstractLocation::class, "al")
            ->leftJoin(SummitVenueRoom::class, 'r', 'WITH', 'al.id = r.id')
            ->leftJoin('al.summit', 's')
            ->where("s.id = :summit_id");

        if((!is_null($filter) &&$filter->hasFilter("floor_name")) ||
            (!is_null($order) && $order->hasOrder("floor_name"))){
            $query = $query->leftJoin('r.floor', 'f');
        }

        if((!is_null($filter) && $filter->hasFilter("venue_name")) ||
            (!is_null($order) && $order->hasOrder("venue_name"))){
            $query = $query->leftJoin('r.venue', 'v');
        }

        $query->setParameter("summit_id", $summit->getId());

        if(!is_null($filter)){
            $filter->apply2Query($query, [
                'name'             => 'al.name:json_string',
                'floor_name'       => 'f.name:json_string',
                'description'      => 'al.description:json_string',
                'venue_name'       => 'v.name:json_string',
            ]);
        }

        if (!is_null($order)) {
            $order->apply2Query($query,  [
                'id'    => 'al.id',
                'name'  => 'al.name',
                'order' => 'al.order',
                'venue_name' => 'v.name',
                'floor_name' => 'f.name',
            ]);
        } else {
            //default order
            $query = $query->addOrderBy("al.id",'ASC');
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
}