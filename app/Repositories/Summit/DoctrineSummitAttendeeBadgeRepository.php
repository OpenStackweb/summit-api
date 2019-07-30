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
use App\Models\Foundation\Summit\Repositories\ISummitAttendeeBadgeRepository;
use App\Repositories\SilverStripeDoctrineRepository;
use Doctrine\ORM\QueryBuilder;
use models\summit\SummitAttendeeBadge;
use utils\DoctrineFilterMapping;
/**
 * Class DoctrineSummitAttendeeBadgeRepository
 * @package App\Repositories\Summit
 */
final class DoctrineSummitAttendeeBadgeRepository
    extends SilverStripeDoctrineRepository
    implements ISummitAttendeeBadgeRepository
{

    /**
     * @return array
     */
    protected function getFilterMappings()
    {
        return [
            'ticket_number'  => new DoctrineFilterMapping("t.number :operator :value"),
            'summit_id'      => new DoctrineFilterMapping("s.id :operator :value"),
            'order_number'   => new DoctrineFilterMapping("ord.number :operator :value"),
            'owner_first_name'  => [
                "m.first_name :operator :value",
                "o.first_name :operator :value"
            ],
            'owner_last_name'   => [
                "m.last_name :operator :value",
                "o.surname :operator :value"
            ],
            'owner_full_name'   => [
                "concat(m.first_name, ' ', m.last_name) :operator :value",
                "concat(o.first_name, ' ', o.surname) :operator :value"
            ],
            'owner_email'       => [
                "m.email :operator :value",
                "o.email :operator :value"
            ],
        ];
    }

    /**
     * @return array
     */
    protected function getOrderMappings()
    {
        return [
            'id' => 'e.id',
            'created' => 'e.created',
            'ticket_number'  => "t.number",
            'order_number'  => "ord.order_number",
        ];
    }

    /**
     * @param QueryBuilder $query
     * @return QueryBuilder
     */
    protected function applyExtraJoins(QueryBuilder $query){
        $query = $query->join('e.ticket', 't')
            ->leftJoin('t.owner', 'o')
            ->leftJoin('o.member', 'm')
            ->join('t.order', 'ord')
            ->join('ord.summit', 's')
            ->join('e.type', 'tp')
            ->join('e.features','f');
        return $query;
    }

    /**
     * @return string
     */
    protected function getBaseEntity()
    {
        return SummitAttendeeBadge::class;
    }

    /**
     * @param string $ticket_number
     * @return SummitAttendeeBadge|null
     */
    public function getBadgeByTicketNumber(string $ticket_number): ?SummitAttendeeBadge
    {
        $query  = $this->getEntityManager()
            ->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e")
            ->leftJoin('e.ticket', 't')
            ->where("t.number = :ticket_number")
            ->setParameter("ticket_number", trim($ticket_number));

        return $query->getQuery()->getOneOrNullResult();
    }
}