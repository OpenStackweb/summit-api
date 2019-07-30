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
use App\Repositories\SilverStripeDoctrineRepository;
use Doctrine\ORM\QueryBuilder;
use models\summit\ISponsorBadgeScanRepository;
use models\summit\SponsorBadgeScan;
use utils\DoctrineFilterMapping;

/**
 * Class DoctrineSponsorBadgeScanRepository
 * @package App\Repositories\Summit
 */
final class DoctrineSponsorBadgeScanRepository
    extends SilverStripeDoctrineRepository
    implements ISponsorBadgeScanRepository
{

    /**
     * @return array
     */
    protected function getFilterMappings()
    {
        return [
            'ticket_number'  => new DoctrineFilterMapping("t.number :operator :value"),
            'summit_id'      => new DoctrineFilterMapping("s.id :operator :value"),
            'user_id'        => new DoctrineFilterMapping("u.id :operator :value"),
            'sponsor_id'     => new DoctrineFilterMapping("sp.id :operator :value"),
            'company_id'     => new DoctrineFilterMapping("c.id :operator :value"),
            'order_number'   => new DoctrineFilterMapping("ord.number :operator :value"),
            'attendee_first_name'  => [
                "m.first_name :operator :value",
                "o.first_name :operator :value"
            ],
            'attendee_last_name'   => [
                "m.last_name :operator :value",
                "o.surname :operator :value"
            ],
            'attendee_full_name'   => [
                "concat(m.first_name, ' ', m.last_name) :operator :value",
                "concat(o.first_name, ' ', o.surname) :operator :value"
            ],
            'attendee_email'       => [
                "m.email :operator :value",
                "o.email :operator :value"
            ],
            'attendee_company' =>  new DoctrineFilterMapping("o.company_name :operator :value"),
        ];
    }

    /**
     * @return array
     */
    protected function getOrderMappings()
    {
        return [
            'id'                    => 'e.id',
            'scan_date'             => 'e.scan_date',
            'created'               => 'e.created',
            'ticket_number'         => "t.number",
            'order_number'          => "ord.order_number",
            'sponsor_id'            => "sp.id",
            'attendee_company'      => 'o.company_name',
            "attendee_full_name"    => "LOWER(CONCAT(o.first_name, ' ', o.surname))",
            'attendee_first_name'   => 'o.first_name',
            'attendee_last_name'    => 'o.surname',
            'attendee_email'        => 'o.email',
        ];
    }

    /**
     * @param QueryBuilder $query
     * @return QueryBuilder
     */
    protected function applyExtraJoins(QueryBuilder $query){
        $query = $query->join('e.sponsor', 'sp')
            ->join('sp.summit', 's')
            ->join('sp.company', 'c')
            ->join('e.user', 'u')
            ->join('e.badge', 'b')
            ->join('b.ticket', 't')
            ->join('t.order', 'ord')
            ->leftJoin('t.owner', 'o')
            ->leftJoin('o.member', 'm');
        return $query;
    }

    /**
     * @return string
     */
    protected function getBaseEntity()
    {
        return SponsorBadgeScan::class;
    }
}