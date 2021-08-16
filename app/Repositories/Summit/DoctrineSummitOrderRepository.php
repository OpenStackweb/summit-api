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
use App\Models\Foundation\Summit\Repositories\ISummitOrderRepository;
use App\Repositories\SilverStripeDoctrineRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use models\summit\IOrderConstants;
use models\summit\Summit;
use models\summit\SummitAttendee;
use models\summit\SummitOrder;
use models\utils\SilverstripeBaseModel;
use utils\DoctrineFilterMapping;
use utils\PagingInfo;
use utils\PagingResponse;
/**
 * Class DoctrineSummitOrderRepository
 * @package App\Repositories\Summit
 */
final class DoctrineSummitOrderRepository
    extends SilverStripeDoctrineRepository
    implements ISummitOrderRepository
{

    /**
     * @return array
     */
    protected function getFilterMappings()
    {
        return [
            'number'             => 'e.number:json_string',
            'summit_id'          =>  new DoctrineFilterMapping("s.id :operator :value"),
            'owner_id'           =>  new DoctrineFilterMapping("o.id :operator :value"),
            'owner_name'         => [
                "LOWER(CONCAT(o.first_name, ' ', o.last_name)) :operator :value" ,
                "LOWER(CONCAT(e.owner_first_name, ' ', e.owner_surname)) :operator :value"
            ],
            'owner_email'        => [
                "o.email :operator :value",
                "e.owner_email :operator :value"
            ],
            'owner_company'      => 'e.owner_company:json_string',
            'status'             => 'e.status:json_string',
            'ticket_owner_name'  => [
                "LOWER(CONCAT(to.first_name, ' ', to.surname)) :operator :value",
                "LOWER(CONCAT(tom.first_name, ' ', tom.last_name)) :operator :value"
            ],
            'ticket_owner_email' => [
                "to.email :operator :value",
                "tom.email :operator :value"
            ],
            'ticket_number'      =>  new DoctrineFilterMapping("t.number :operator :value"),
        ];
    }

    /**
     * @param QueryBuilder $query
     * @return QueryBuilder
     */
    protected function applyExtraJoins(QueryBuilder $query){
        $query
            ->join('e.tickets','t')
            ->join('e.summit','s')
            ->leftJoin('e.owner','o')
            ->leftJoin('t.owner','to')
            ->leftJoin('to.member', 'tom');
        return $query;
    }

    /**
     * @return array
     */
    protected function getOrderMappings()
    {
        return [
            'number' => 'e.number',
            'id'     => 'e.id',
            'status' => 'e.status',
        /*    'owner_name' =>  <<<SQL
    CASE WHEN o IS NOT NULL THEN LOWER(CONCAT(o.first_name, ' ', o.last_name))
    ELSE LOWER(CONCAT(e.owner_first_name, ' ', e.owner_surname)) END 
SQL*/
                'owner_name' =>  <<<SQL
COALESCE(LOWER(CONCAT(o.first_name, ' ', o.last_name)), LOWER(CONCAT(e.owner_first_name, ' ', e.owner_surname))) 
SQL
        ];
    }

    /**
     * @param QueryBuilder $query
     * @return QueryBuilder
     */
    protected function applyExtraFilters(QueryBuilder $query){
        $query = $query->andWhere("e.status <> :cancelled")->setParameter("cancelled", IOrderConstants::CancelledStatus);
        return $query;
    }

    /**
     * @return string
     */
    protected function getBaseEntity()
    {
        return SummitOrder::class;
    }

    /**
     * @param string $hash
     * @return SummitOrder|null
     */
    public function getByHashLockExclusive(string $hash): ?SummitOrder
    {
        $query = $this->getEntityManager()
            ->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e")
            ->where("e.hash = :hash");

        $query->setParameter("hash", $hash);
        return $query->getQuery()
            ->setLockMode(\Doctrine\DBAL\LockMode::PESSIMISTIC_WRITE)
            ->setHint(\Doctrine\ORM\Query::HINT_REFRESH, true)
            ->getOneOrNullResult();
    }

    /**
     * @param string $payment_gateway_cart_id
     * @return SummitOrder|null
     */
    public function getByPaymentGatewayCartIdExclusiveLock(string $payment_gateway_cart_id): ?SummitOrder
    {
        $query = $this->getEntityManager()
            ->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e")
            ->where("e.payment_gateway_cart_id = :payment_gateway_cart_id");

        $query->setParameter("payment_gateway_cart_id", $payment_gateway_cart_id);
        return $query->getQuery()
            ->setLockMode(\Doctrine\DBAL\LockMode::PESSIMISTIC_WRITE)
            ->setHint(\Doctrine\ORM\Query::HINT_REFRESH, true)
            ->getOneOrNullResult();
    }

    /**
     * @param string $email
     * @return mixed
     */
    public function getAllByOwnerEmail(string $email)
    {
        $query = $this->getEntityManager()
            ->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e")
            ->where("e.owner_email = :owner_email");

        $query->setParameter("owner_email", trim($email));
        return $query->getQuery()
            ->setLockMode(\Doctrine\DBAL\LockMode::PESSIMISTIC_WRITE)
            ->setHint(\Doctrine\ORM\Query::HINT_REFRESH, true)
            ->getResult();
    }

    /**
     * @param string $email
     * @return mixed
     */
    public function getAllByOwnerEmailAndOwnerNotSet(string $email){

        $query = $this->getEntityManager()
            ->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e")
            ->where("e.owner_email = :owner_email")
            ->andWhere("e.owner is null");

        $query->setParameter("owner_email", trim($email));
        return $query->getQuery()
            ->setLockMode(\Doctrine\DBAL\LockMode::PESSIMISTIC_WRITE)
            ->setHint(\Doctrine\ORM\Query::HINT_REFRESH, true)
            ->getResult();
    }

    /**
     * @param int $minutes
     * @param int $max
     * @return mixed
     */
    public function getAllReservedOlderThanXMinutes(int $minutes, int $max = 100)
    {
        $eol = new \DateTime('now', new \DateTimeZone(SilverstripeBaseModel::DefaultTimeZone));
        $eol->sub(new \DateInterval('PT' . $minutes . 'M'));

        $query = $this->getEntityManager()
            ->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e")
            ->where("e.created <= :eol")
            ->andWhere("(e.status = :status1 or e.status = :status2)");

        $query->setParameter("eol", $eol);
        $query->setParameter("status1", IOrderConstants::ReservedStatus);
        $query->setParameter("status1", IOrderConstants::ErrorStatus);
        return $query->getQuery()->setMaxResults($max)->getResult();

    }

    /**
     * @param int $minutes
     * @param int $max
     * @return mixed
     */
    public function getAllConfirmedOlderThanXMinutes(int $minutes, int $max = 100)
    {
        $eol = new \DateTime('now', new \DateTimeZone(SilverstripeBaseModel::DefaultTimeZone));
        $eol->sub(new \DateInterval('PT' . $minutes . 'M'));

        $query = $this->getEntityManager()
            ->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e")
            ->where("e.created <= :eol")
            ->andWhere("e.status = :status");

        $query->setParameter("eol", $eol);
        $query->setParameter("status", IOrderConstants::ConfirmedStatus);

        return $query->getQuery()->setMaxResults($max)->getResult();

    }

    /**
     * @param string $externalId
     * @return SummitOrder|null
     */
    public function getByExternalIdLockExclusive(string $externalId): ?SummitOrder
    {
        $query = $this->getEntityManager()
            ->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e")
            ->where("e.external_id = :external_id");

        $query->setParameter("external_id", $externalId);
        return $query->getQuery()
            ->setLockMode(\Doctrine\DBAL\LockMode::PESSIMISTIC_WRITE)
            ->setHint(\Doctrine\ORM\Query::HINT_REFRESH, true)
            ->getOneOrNullResult();
    }

    /**
     * @param Summit $summit
     * @param string $externalId
     * @return SummitOrder|null
     */
    public function getByExternalIdAndSummitLockExclusive(Summit $summit, string $externalId): ?SummitOrder
    {
        $query = $this->getEntityManager()
            ->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e")
            ->join('e.summit', 's')
            ->where("e.external_id = :external_id")
            ->andWhere('s.id = :summit_id')
        ;

        $query->setParameter("external_id", $externalId);
        $query->setParameter("summit_id", $summit->getId())
        ;
        return $query->getQuery()
            ->setLockMode(\Doctrine\DBAL\LockMode::PESSIMISTIC_WRITE)
            ->setHint(\Doctrine\ORM\Query::HINT_REFRESH, true)
            ->getOneOrNullResult();
    }

    /**
     * @param Summit $summit
     * @param PagingInfo $paging_info
     * @return PagingResponse
     */
    public function getAllOrderThatNeedsEmailActionReminder(Summit $summit, PagingInfo $paging_info):PagingResponse
    {
        $query = $this->getEntityManager()
            ->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e")
            ->join("e.tickets","t")
            ->join("e.summit","s")
            ->leftJoin("t.owner","o")
            ->where('e.status = :order_status')
            ->andWhere('s.id = :summit_id')
            ->andWhere("o is null OR o.status = :attendee_status");

        $query->setParameter("order_status", IOrderConstants::PaidStatus);
        $query->setParameter("summit_id", $summit->getId());
        $query->setParameter("attendee_status", SummitAttendee::StatusIncomplete);

        $query= $query
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