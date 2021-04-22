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
use models\summit\Summit;
use models\summit\SummitAttendeeTicket;
use App\Repositories\SilverStripeDoctrineRepository;
use models\utils\IEntity;
use utils\DoctrineFilterMapping;
use utils\DoctrineJoinFilterMapping;
use utils\DoctrineLeftJoinFilterMapping;
/**
 * Class DoctrineSummitAttendeeTicketRepository
 * @package App\Repositories\Summit
 */
final class DoctrineSummitAttendeeTicketRepository
    extends SilverStripeDoctrineRepository
    implements ISummitAttendeeTicketRepository
{


    /**
     * @return array
     */
    protected function getFilterMappings()
    {
        return [
            'number'              => 'e.number:json_string',
            'is_active'           => 'e.is_active',
            'order_number'        => 'o.number:json_string',
            'owner_name'          => [
                "concat(m.first_name, ' ', m.last_name) :operator :value",
                "concat(a.first_name, ' ', a.surname) :operator :value"
            ],
            'owner_company' => 'a.company_name:json_string',
            'owner_first_name' => [
                'm.first_name:json_string',
                'a.first_name:json_string'
            ],
            'owner_last_name'     => ['m.last_name:json_string', 'a.surname:json_string'],
            'owner_email'         => ['m.email:json_string', 'm.second_email:json_string', 'm.third_email:json_string','a.email:json_string'],
            'summit_id'           => 's.id:json_int',
            'owner_id'            => 'a.id:json_int',
            'member_id'           => 'm.id:json_int',
            'order_id'            => 'o.id:json_int',
            'status'              => 'e.status:json_string',
        ];
    }

    /**
     * @param QueryBuilder $query
     * @return QueryBuilder
     */
    protected function applyExtraJoins(QueryBuilder $query){
        $query->join("e.order","o");
        $query->join("o.summit","s");
        $query->leftJoin("e.owner","a");
        $query->leftJoin("a.member","m");
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
     * @return string
     */
    protected function getBaseEntity()
    {
       return SummitAttendeeTicket::class;
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
            ->where('o.external_id = :external_order_id')
            ->andWhere('ow.external_id = :external_attendee_id')
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