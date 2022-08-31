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
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\QueryBuilder;
use models\summit\IOrderConstants;
use models\summit\ISummitAttendeeTicketRepository;
use models\summit\ISummitRefundRequestConstants;
use models\summit\Summit;
use models\summit\SummitAttendeeTicket;
use App\Repositories\SilverStripeDoctrineRepository;
use models\summit\SummitAttendeeTicketRefundRequest;
use models\utils\IEntity;
use utils\DoctrineCaseFilterMapping;
use utils\DoctrineFilterMapping;
use utils\DoctrineJoinFilterMapping;
use utils\DoctrineLeftJoinFilterMapping;
use utils\DoctrineSwitchFilterMapping;
use utils\Filter;

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
     * @return array
     */
    protected function getFilterMappings()
    {
        return [
            'id'                  => 'e.id:json_int',
            'number'              => 'e.number:json_string',
            'is_active'           => 'e.is_active',
            'order_number'        => 'o.number:json_string',
            'owner_name'          => "COALESCE(LOWER(CONCAT(m.first_name, ' ', m.last_name)), LOWER(CONCAT(a.first_name, ' ', a.surname)))",
            'owner_company'       => 'a.company_name:json_string',
            'owner_first_name'    => "COALESCE(LOWER(m.first_name), LOWER(a.first_name))",
            'owner_last_name'     => "COALESCE(LOWER(m.last_name), LOWER(a.surname))",
            'owner_email'         => ['m.email:json_string', 'm.second_email:json_string', 'm.third_email:json_string','a.email:json_string'],
            'summit_id'           => 's.id:json_int',
            'order_owner_id'      => 'ord_m.id:json_int',
            'owner_id'            => 'a.id:json_int',
            'member_id'           => 'm.id:json_int',
            'order_id'            => 'o.id:json_int',
            'status'              => 'e.status:json_string',
            'promo_code_id'       => 'pc.id:json_int',
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
            'has_owner' =>  new DoctrineSwitchFilterMapping([
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
            'has_order_owner' =>  new DoctrineSwitchFilterMapping([
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
            'has_badge' =>  new DoctrineSwitchFilterMapping([
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
        ];
    }

    /**
     * @param QueryBuilder $query
     * @return QueryBuilder
     */
    protected function applyExtraJoins(QueryBuilder $query, ?Filter $filter = null){
        $query->join("e.order","o");
        $query->join("o.summit","s");
        $query->leftJoin("o.owner","ord_m");
        $query->leftJoin("e.owner","a");
        $query->leftJoin("e.badge","b");
        $query->leftJoin("b.type","bt");
        $query->leftJoin("bt.access_levels","al");
        $query->leftJoin("a.member","m");
        if($filter->hasFilter('ticket_type_id')){
            $query = $query->join("e.ticket_type", "tt");
        }
        if($filter->hasFilter('promo_code_id')){
            $query = $query->leftJoin("e.promo_code", "pc");
        }
        if($filter->hasFilter('view_type_id')){
            $query = $query->join("bt.allowed_view_types", "avt");
        }
        return $query;
    }

    /**
     * @return array
     */
    protected function getOrderMappings()
    {
        return [
            'id'     => 'e.id',
            'number' => 'e.number',
            'status' => 'e.status',
            'owner_first_name'        => 'COALESCE(LOWER(m.first_name), LOWER(a.first_name))',
            'owner_last_name'         => 'COALESCE(LOWER(m.last_name), LOWER(a.surname))',
            "owner_name"         => <<<SQL
COALESCE(LOWER(CONCAT(m.first_name, ' ', m.last_name)), LOWER(CONCAT(a.first_name, ' ', a.surname)))
SQL,
        ];
    }

    /**
     * @param string $external_order_id
     * @param string $external_attendee_id
     * @return SummitAttendeeTicket
     */
    public function getByExternalOrderIdAndExternalAttendeeId($external_order_id, $external_attendee_id)
    {
        $query  = $this->getEntityManager()->createQueryBuilder()
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
        $query  = $this->getEntityManager()->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e");

        return $query
            ->join("e.order","o")
            ->join("e.owner","ow")
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
    public function getByNumberExclusiveLock(string $number):?SummitAttendeeTicket{
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
        $query  = $this->getEntityManager()->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e");

        return $query
            ->join("e.order","o")
            ->join("o.summit","s")
            ->join("e.owner","ow")
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
    public function getByExternalAttendeeIdExclusiveLock(Summit $summit, string $external_attendee_id):?SummitAttendeeTicket
    {
        $query  = $this->getEntityManager()->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e");

        return $query
            ->join("e.order","o")
            ->join("o.summit","s")
            ->join("e.owner","ow")
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
    protected function applyExtraFilters(QueryBuilder $query){
        $query = $query->andWhere("e.status <> :cancelled")->setParameter("cancelled", IOrderConstants::CancelledStatus);
        return $query;
    }

}