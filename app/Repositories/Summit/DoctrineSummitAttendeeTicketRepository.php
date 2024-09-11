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

use App\Http\Utils\Filters\DoctrineInFilterMapping;
use App\Http\Utils\Filters\DoctrineNotInFilterMapping;
use App\Repositories\SilverStripeDoctrineRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\QueryBuilder;
use models\summit\IOrderConstants;
use models\summit\ISummitAttendeeTicketRepository;
use models\summit\ISummitRefundRequestConstants;
use models\summit\Summit;
use models\summit\SummitAccessLevelType;
use models\summit\SummitAttendeeTicket;
use models\summit\SummitAttendeeTicketRefundRequest;
use models\utils\IEntity;
use utils\DoctrineCaseFilterMapping;
use utils\DoctrineSwitchFilterMapping;
use utils\Filter;
use utils\Order;

/**
 * Class DoctrineSummitAttendeeTicketRepository
 * @package App\Repositories\Summit
 */
final class DoctrineSummitAttendeeTicketRepository
    extends SilverStripeDoctrineRepository
    implements ISummitAttendeeTicketRepository
{

    /**
     * @return string
     */
    protected function getBaseEntity()
    {
        return SummitAttendeeTicket::class;
    }

    /**
     * @param QueryBuilder $query
     * @param Filter|null $filter
     * @param Order|null $order
     * @return QueryBuilder
     */
    protected function applyExtraSelects(QueryBuilder $query, ?Filter $filter = null, ?Order $order = null):QueryBuilder{
        $query = $query->addSelect("COALESCE(SUM(ta.amount),0) AS HIDDEN HIDDEN_APPLIED_TAXES");
        $query = $query->addSelect("(e.raw_cost - e.discount) AS HIDDEN HIDDEN_FINAL_AMOUNT");
        $query = $query->addSelect("COALESCE(SUM(rr.refunded_amount),0) AS HIDDEN HIDDEN_REFUNDED_AMOUNT");
        $query = $query->addSelect("( (e.raw_cost - e.discount) - COALESCE(SUM(rr.refunded_amount),0) ) AS HIDDEN HIDDEN_FINAL_AMOUNT_ADJUSTED");
        $query = $query->addSelect("COUNT(prt.id) AS HIDDEN HIDDEN_BADGE_PRINTS_COUNT");
        $query->groupBy("e");
        return $query;
    }
    /**
     * @return array
     */
    protected function getFilterMappings()
    {
        return [
            'id' => new DoctrineInFilterMapping('e.id'),
            'not_id' => new DoctrineNotInFilterMapping('e.id'),
            'number' => 'e.number:json_string',
            'is_active' => 'e.is_active',
            'order_number' => 'o.number:json_string',
            'owner_name' => "COALESCE(LOWER(CONCAT(a.first_name, ' ', a.surname)),LOWER(CONCAT(m.first_name, ' ', m.last_name)))",
            'owner_company' => 'COALESCE(a.company_name, a_c.name)',
            'has_owner_company' => new DoctrineSwitchFilterMapping([
                    '1' => new DoctrineCaseFilterMapping(
                        'true',
                        "((a.company_name is not null and a.company_name <> '') OR (a_c.name is not null and a_c.name <> ''))"
                    ),
                    '0' => new DoctrineCaseFilterMapping(
                        'false',
                        "((a.company_name is null OR a.company_name = '') AND (a_c.name is null OR a_c.name = ''))"
                    ),
                ]
            ),
            'owner_first_name' => "COALESCE(LOWER(a.first_name),LOWER(m.first_name))",
            'owner_last_name' => "COALESCE(LOWER(a.surname),LOWER(m.last_name))",
            'owner_email' => [
                'm.email:json_string',
                'm.second_email:json_string',
                'm.third_email:json_string',
                'm2.email:json_string',
                'm2.second_email:json_string',
                'm2.third_email:json_string',
                'a.email:json_string'
            ],
            'summit_id' => 's.id:json_int',
            'order_owner_id' => 'ord_m.id:json_int',
            'owner_id' => 'a.id:json_int',
            'member_id' => ['m.id:json_int','m2.id:json_int'],
            'order_id' => 'o.id:json_int',
            'status' => 'e.status:json_string',
            'promo_code_id' => 'pc.id:json_int',
            'promo_code_description' => 'pc.description:json_string',
            'promo_code' => 'pc.code:json_string',
            'promo_code_tag_id' => 'pct.id:json_int',
            'promo_code_tag' => 'pct.tag:json_string',
            'has_requested_refund_requests' => new DoctrineSwitchFilterMapping([
                    '1' => new DoctrineCaseFilterMapping(
                        'true',
                        sprintf
                        (
                            "EXISTS (select rr1 from %s rr1 where rr1.ticket = e and rr1.status = '%s')",
                            SummitAttendeeTicketRefundRequest::class,
                            ISummitRefundRequestConstants::RefundRequestedStatus
                        )
                    ),
                    '0' => new DoctrineCaseFilterMapping(
                        'false',
                        sprintf
                        (
                            "NOT EXISTS (select rr1 from %s rr1 where rr1.ticket = e and rr1.status = '%s')"
                            ,
                            SummitAttendeeTicketRefundRequest::class,
                            ISummitRefundRequestConstants::RefundRequestedStatus
                        )
                    ),
                ]
            ),
            'access_level_type_name' => 'al.name :operator :value',
            'ticket_type_id' => 'tt.id:json_int',
            'view_type_id' => 'avt.id:json_int',
            'has_owner' => new DoctrineSwitchFilterMapping([
                    '1' => new DoctrineCaseFilterMapping(
                        'true',
                        "a is not null"
                    ),
                    '0' => new DoctrineCaseFilterMapping(
                        'false',
                        "a is null"
                    ),
                ]
            ),
            'has_order_owner' => new DoctrineSwitchFilterMapping([
                    '1' => new DoctrineCaseFilterMapping(
                        'true',
                        "ord_m is not null"
                    ),
                    '0' => new DoctrineCaseFilterMapping(
                        'false',
                        "ord_m is null"
                    ),
                ]
            ),
            'has_badge' => new DoctrineSwitchFilterMapping([
                    '1' => new DoctrineCaseFilterMapping(
                        'true',
                        "b is not null"
                    ),
                    '0' => new DoctrineCaseFilterMapping(
                        'false',
                        "b is null"
                    ),
                ]
            ),
            'owner_status' => 'a.status',
            'final_amount' =>  "(e.raw_cost - e.discount) :operator :value",
            'is_printable' =>
                new DoctrineSwitchFilterMapping([
                        '1' => new DoctrineCaseFilterMapping(
                            'true',
                            sprintf("e.is_active = 1 and al.name = '%s'", SummitAccessLevelType::IN_PERSON),
                        ),
                        '0' => new DoctrineCaseFilterMapping(
                            'false',
                            sprintf("not(e.is_active = 1 and al.name = '%s')", SummitAccessLevelType::IN_PERSON),
                        ),
                    ]
                ),
            'badge_type_id' => 'bt.id:json_int',
            'has_badge_prints' =>  new DoctrineSwitchFilterMapping([
                    '1' => new DoctrineCaseFilterMapping(
                        'true',
                        "SIZE(prt) > 0"
                    ),
                    '0' => new DoctrineCaseFilterMapping(
                        'false',
                        "SIZE(prt) = 0"
                    ),
                ]
            ),
            'badge_prints_count' => 'SIZE(prt) :operator :value',
            'exclude_is_printable_free_unassigned' =>    new DoctrineSwitchFilterMapping([
                    '1' => new DoctrineCaseFilterMapping(
                        'true',
                        sprintf
                        (
                            "NOT EXISTS ( select e2 from %s e2 left join e2.owner a2 left join e2.badge b2 ".
                            " left join b2.type bt2 left join bt2.access_levels al2 ".
                            " where e2.id = e.id and e2.is_active = 1 and al2.name = '%s' and  a2 is null ".
                            " and (e2.raw_cost - e2.discount) = 0 )",
                            $this->getBaseEntity(),
                            SummitAccessLevelType::IN_PERSON
                        ),

                    ),
                    '0' => new DoctrineCaseFilterMapping(
                        'false',
                        " "
                    ),
                ]
            ),
        ];
    }

    /**
     * @param QueryBuilder $query
     * @return QueryBuilder
     */
    protected function applyExtraJoins(QueryBuilder $query, ?Filter $filter = null)
    {
        $query->join("e.order", "o");
        $query = $query->join("o.summit", "s");
        $query = $query->leftJoin("o.owner", "ord_m");
        $query = $query->leftJoin("e.owner", "a");
        $query = $query->leftJoin("a.company", "a_c");
        $query = $query->leftJoin("e.badge", "b");
        $query = $query->leftJoin("b.prints", "prt");
        $query = $query->leftJoin("b.type", "bt");
        $query = $query->leftJoin("bt.access_levels", "al");
        $query = $query->leftJoin("a.member", "m");
        $query = $query->leftJoin("e.refund_requests", "rr");
        $query = $query->leftJoin("e.applied_taxes", "ta");
        $query = $query->join("e.ticket_type", "tt");
        $query = $query->leftJoin("e.promo_code", "pc");

        if ($filter->hasFilter('promo_code_tag_id') || $filter->hasFilter('promo_code_tag')) {
            if (!collect($query->getAllAliases())->contains('pc')) {
                $query = $query->leftJoin("e.promo_code", "pc");
            }
            $query = $query->leftJoin("pc.tags", "pct");
        }
        if ($filter->hasFilter('view_type_id')) {
            $query = $query->join("bt.allowed_view_types", "avt");
        }
        if($filter->hasFilter("member_id") || $filter->hasFilter("owner_email")){
            // add all managed tickets too
            $query = $query->leftJoin("a.manager", "am");
            $query = $query->leftJoin("am.member", "m2");
        }
        return $query;
    }

    /**
     * @return array
     */
    protected function getOrderMappings()
    {
        return [
            'id' => 'e.id',
            'number' => 'e.number',
            'status' => 'e.status',
            'owner_first_name' => 'COALESCE(LOWER(a.first_name), LOWER(m.first_name))',
            'owner_last_name' => 'COALESCE(LOWER(a.surname), LOWER(m.last_name))',
            "owner_name" => <<<SQL
COALESCE(LOWER(CONCAT(a.first_name, ' ', a.surname)),LOWER(CONCAT(m.first_name, ' ', m.last_name)))
SQL,
            'ticket_type' => 'tt.name',
            'final_amount' => 'HIDDEN_FINAL_AMOUNT',
            'owner_email' => 'COALESCE(LOWER(m.email), LOWER(m.second_email), LOWER(m.third_email), LOWER(a.email))',
            'owner_company' => 'COALESCE(a.company_name, a_c.name)',
            'promo_code' => 'pc.code',
            'bought_date' => 'e.bought_date',
            'refunded_amount' => 'HIDDEN_REFUNDED_AMOUNT',
            'final_amount_adjusted' => 'HIDDEN_FINAL_AMOUNT_ADJUSTED',
            'badge_type_id' => 'bt.id',
            'badge_type' => 'bt.name',
            'badge_prints_count' => 'HIDDEN_BADGE_PRINTS_COUNT',
        ];
    }

    /**
     * @param string $external_order_id
     * @param string $external_attendee_id
     * @return SummitAttendeeTicket
     */
    public function getByExternalOrderIdAndExternalAttendeeId($external_order_id, $external_attendee_id)
    {
        $query = $this->getEntityManager()->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e");

        $tickets = $query
            ->where('e.external_order_id = :external_order_id')
            ->andWhere('e.external_attendee_id = :external_attendee_id')
            ->setParameter('external_order_id', $external_order_id)
            ->setParameter('external_attendee_id', $external_attendee_id)->getQuery()->getResult();

        return count($tickets) > 0 ? $tickets[0] : null;
    }

    /**
     * @param string $external_order_id
     * @param string $external_attendee_id
     * @return SummitAttendeeTicket
     */
    public function getByExternalOrderIdAndExternalAttendeeIdExclusiveLock
    (
        $external_order_id,
        $external_attendee_id
    ): ?SummitAttendeeTicket
    {
        $query = $this->getEntityManager()->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e");

        return $query
            ->join("e.order", "o")
            ->join("e.owner", "ow")
            ->where('o.external_id = :external_order_id')
            ->andWhere('ow.external_id = :external_attendee_id')
            ->setParameter('external_order_id', $external_order_id)
            ->setParameter('external_attendee_id', $external_attendee_id)
            ->getQuery()
            ->setLockMode(LockMode::PESSIMISTIC_WRITE)
            ->setHint(\Doctrine\ORM\Query::HINT_REFRESH, true)
            ->getOneOrNullResult();
    }

    /**
     * @param IEntity $entity
     * @return void
     */
    public function delete($entity)
    {
        $this->getEntityManager()->getConnection()->delete("
        SummitAttendeeTicket
        ", ["ID" => $entity->getIdentifier()]);
    }

    /**
     * @param string $number
     * @return bool
     */
    public function existNumber(string $number): bool
    {
        return $this->count(['number' => $number]) > 0;
    }

    /**
     * @param string $hash
     * @return SummitAttendeeTicket|null
     */
    public function getByHashExclusiveLock(string $hash): ?SummitAttendeeTicket
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e")
            ->where('e.hash = :hash')
            ->setParameter('hash', trim($hash))->getQuery()
            ->setLockMode(LockMode::PESSIMISTIC_WRITE)
            ->setHint(\Doctrine\ORM\Query::HINT_REFRESH, true)
            ->getOneOrNullResult();
    }

    /**
     * @param string $hash
     * @return SummitAttendeeTicket|null
     */
    public function getByNumberExclusiveLock(string $number): ?SummitAttendeeTicket
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e")
            ->where('e.number = :number')
            ->setParameter('number', trim($number))->getQuery()
            ->setLockMode(LockMode::PESSIMISTIC_WRITE)
            ->setHint(\Doctrine\ORM\Query::HINT_REFRESH, true)
            ->getOneOrNullResult();
    }

    /**
     * @param string $number
     * @return SummitAttendeeTicket|null
     */
    public function getByNumber(string $number): ?SummitAttendeeTicket
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e")
            ->where('e.number = :number')
            ->setParameter('number', trim($number))->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param string $hash
     * @return SummitAttendeeTicket|null
     */
    public function getByFormerHashExclusiveLock(string $hash): ?SummitAttendeeTicket
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e")
            ->join("e.former_hashes", "fh")
            ->where('fh.hash = :hash')
            ->setParameter('hash', trim($hash))->getQuery()
            ->setLockMode(LockMode::PESSIMISTIC_WRITE)
            ->setHint(\Doctrine\ORM\Query::HINT_REFRESH, true)
            ->getOneOrNullResult();
    }

    /**
     * @param Summit $summit
     * @param string $external_order_id
     * @param string $external_attendee_id
     * @return SummitAttendeeTicket|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function getBySummitAndExternalOrderIdAndExternalAttendeeIdExclusiveLock
    (
        Summit $summit,
               $external_order_id,
               $external_attendee_id
    ): ?SummitAttendeeTicket
    {
        $query = $this->getEntityManager()->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e");

        return $query
            ->join("e.order", "o")
            ->join("o.summit", "s")
            ->join("e.owner", "ow")
            ->where('e.external_order_id = :external_order_id')
            ->andWhere('e.external_attendee_id = :external_attendee_id')
            ->andWhere('s.id = :summit_id')
            ->setParameter('external_order_id', $external_order_id)
            ->setParameter('external_attendee_id', $external_attendee_id)
            ->setParameter('summit_id', $summit->getId())
            ->getQuery()
            ->setLockMode(LockMode::PESSIMISTIC_WRITE)
            ->setHint(\Doctrine\ORM\Query::HINT_REFRESH, true)
            ->getOneOrNullResult();
    }

    /**
     * @param Summit $summit
     * @param string $external_attendee_id
     * @return SummitAttendeeTicket|null
     */
    public function getByExternalAttendeeIdExclusiveLock(Summit $summit, string $external_attendee_id): ?SummitAttendeeTicket
    {
        $query = $this->getEntityManager()->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e");

        return $query
            ->join("e.order", "o")
            ->join("o.summit", "s")
            ->join("e.owner", "ow")
            ->where('ow.external_id = :external_attendee_id')
            ->andWhere('s.id = :summit_id')
            ->setParameter('external_attendee_id', $external_attendee_id)
            ->setParameter('summit_id', $summit->getId())
            ->getQuery()
            ->setLockMode(LockMode::PESSIMISTIC_WRITE)
            ->setHint(\Doctrine\ORM\Query::HINT_REFRESH, true)
            ->getOneOrNullResult();
    }

    /**
     * @param QueryBuilder $query
     * @return QueryBuilder
     */
    protected function applyExtraFilters(QueryBuilder $query)
    {
        $query = $query->andWhere("e.status <> :cancelled")->setParameter("cancelled", IOrderConstants::CancelledStatus);
        return $query;
    }

}