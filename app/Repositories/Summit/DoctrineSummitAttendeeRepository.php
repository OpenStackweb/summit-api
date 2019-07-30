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
use Doctrine\ORM\QueryBuilder;
use models\main\Member;
use models\summit\ISummitAttendeeRepository;
use models\summit\Summit;
use models\summit\SummitAttendee;
use App\Repositories\SilverStripeDoctrineRepository;
use utils\DoctrineFilterMapping;
use utils\DoctrineJoinFilterMapping;
use utils\DoctrineLeftJoinFilterMapping;
use utils\Filter;
use utils\Order;
use utils\PagingInfo;
use utils\PagingResponse;
use Doctrine\ORM\Tools\Pagination\Paginator;
/**
 * Class DoctrineSummitAttendeeRepository
 * @package App\Repositories\Summit
 */
final class DoctrineSummitAttendeeRepository
    extends SilverStripeDoctrineRepository
    implements ISummitAttendeeRepository
{

    protected function applyExtraJoins(QueryBuilder $query){
        $query =  $query->join('e.summit', 's')
            ->leftJoin('e.member', 'm')
            ->leftJoin('e.tickets', 't');
        return $query;
    }

    /**
     * @return array
     */
    protected function getFilterMappings()
    {
        return [
            'summit_id'            => new DoctrineFilterMapping("s.id :operator :value"),
            'first_name'           => [
                "m.first_name :operator :value",
                "e.first_name :operator :value"
            ],
            'last_name'            => [
                "m.last_name :operator :value",
                "e.surname :operator :value"
            ],
            'full_name'            => [
                "concat(m.first_name, ' ', m.last_name) :operator :value",
                "concat(e.first_name, ' ', e.surname) :operator :value"
            ],
            'company'               => new DoctrineFilterMapping("e.company_name :operator :value"),
            'email'                => [
                "m.email :operator :value",
                "e.email :operator :value"
            ],
            'external_order_id'    => new DoctrineFilterMapping("t.external_order_id :operator :value"),
            'external_attendee_id' => new DoctrineFilterMapping("t.external_attendee_id :operator :value")
        ];
    }

    /**
     * @return array
     */
    protected function getOrderMappings()
    {
        return [
            'id'                => 'e.id',
            'first_name'        => 'm.first_name',
            'last_name'         => 'm.last_name',
            'external_order_id' => 't.external_order_id',
            'company'           => 'e.company_name'
        ];
    }

    /**
     * @return string
     */
    protected function getBaseEntity()
    {
        return SummitAttendee::class;
    }

    /**
     * @param Summit $summit
     * @param PagingInfo $paging_info
     * @param Filter|null $filter
     * @param Order|null $order
     * @return PagingResponse
     */
    public function getBySummit(Summit $summit, PagingInfo $paging_info, Filter $filter = null, Order $order = null)
    {
        $query  = $this->getEntityManager()
            ->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e")
            ->leftJoin('e.summit', 's')
            ->leftJoin('e.member', 'm')
            ->leftJoin('e.tickets', 't')
            ->where("s.id = :summit_id");

        $query->setParameter("summit_id", $summit->getId());

        if(!is_null($filter)){

            $filter->apply2Query($query, $this->getFilterMappings());
        }

        if (!is_null($order)) {
            $order->apply2Query($query, $this->getOrderMappings());
        } else {
            //default order
            $query = $query->addOrderBy("m.first_name",'ASC');
            $query = $query->addOrderBy("m.last_name", 'ASC');
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
     * @param Summit $summit
     * @param Member $member
     * @return SummitAttendee
     */
    public function getBySummitAndMember(Summit $summit, Member $member):?SummitAttendee
    {
        $query  = $this->getEntityManager()
            ->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e")
            ->leftJoin('e.summit', 's')
            ->leftJoin('e.member', 'm')
            ->where("s.id = :summit_id")->andWhere("m.id = :member_id")
            ->setParameter("summit_id", $summit->getId())
            ->setParameter("member_id", $member->getId());

       $res = $query->getQuery()->getOneOrNullResult();

       return $res;
    }

    /**
     * @param Summit $summit
     * @param string $email
     * @return SummitAttendee|null
     */
    public function getBySummitAndEmail(Summit $summit, string $email): ?SummitAttendee
    {
        $query  = $this->getEntityManager()
            ->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e")
            ->leftJoin('e.summit', 's')
            ->leftJoin('e.member', 'm')
            ->where("s.id = :summit_id")
            ->andWhere("m.email = :email or e.email = :email")
            ->setParameter("summit_id", $summit->getId())
            ->setParameter("email", strtolower(trim($email)));

        return $query->getQuery()->getOneOrNullResult();
    }

    /**
     * @param string $email
     * @return mixed
     */
    public function getByEmail(string $email)
    {
        $query  = $this->getEntityManager()
            ->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e")
            ->where("e.email = :email")
            ->setParameter("email", strtolower(trim($email)));
        return $query->getQuery()->getResult();
    }

    /**
     * @param Summit $summit
     * @param string $email
     * @param null|string $first_name
     * @param null|string $last_name
     * @param null|string $external_id
     * @return SummitAttendee|null
     */
    public function getBySummitAndEmailAndFirstNameAndLastNameAndExternalId(Summit $summit, string $email, ?string $first_name = null, ?string $last_name = null, ?string $external_id = null):?SummitAttendee
    {
        $query  = $this->getEntityManager()
            ->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e")
            ->leftJoin('e.summit', 's')
            ->leftJoin('e.member', 'm')
            ->where("s.id = :summit_id")
            ->andWhere("m.email = :email or e.email = :email");

        if(!empty($first_name)){
            $query = $query->andWhere("e.first_name = :first_name")->setParameter("first_name", $first_name);
        }

        if(!empty($last_name)){
            $query = $query->andWhere("e.surname = :surname")->setParameter("surname", $last_name);
        }
        if(!empty($external_id)){
            $query = $query->andWhere("e.external_id = :external_id")->setParameter("external_id", $external_id);
        }

        $query =
            $query
                ->setParameter("summit_id", $summit->getId())
                ->setParameter("email", trim($email));

        return $query->getQuery()->getOneOrNullResult();
    }
}