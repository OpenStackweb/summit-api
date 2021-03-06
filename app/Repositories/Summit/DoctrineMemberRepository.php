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
use Doctrine\ORM\Tools\Pagination\Paginator;
use models\main\IMemberRepository;
use models\main\Member;
use App\Repositories\SilverStripeDoctrineRepository;
use utils\DoctrineFilterMapping;
use utils\DoctrineJoinFilterMapping;
use utils\Filter;
use utils\Order;
use utils\PagingInfo;
use utils\PagingResponse;
/**
 * Class DoctrineMemberRepository
 * @package App\Repositories\Summit
 */
final class DoctrineMemberRepository
    extends SilverStripeDoctrineRepository
    implements IMemberRepository
{

    /**
     * @param string $email
     * @return Member|null
     */
    public function getByEmail($email): ?Member
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e")
            ->where("e.email = :email")
            ->setParameter("email", strtolower(trim($email)))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param PagingInfo $paging_info
     * @param Filter|null $filter
     * @param Order|null $order
     * @return PagingResponse
     */
    public function getAllByPage(PagingInfo $paging_info, Filter $filter = null, Order $order = null)
    {
        $query  = $this->getEntityManager()
                ->createQueryBuilder()
                ->select("m")
                ->from($this->getBaseEntity(), "m")
                ->andWhere("m.first_name is not null")
                ->andWhere("m.last_name is not null");


        if($filter->hasFilter("summit_id") || $filter->hasFilter("schedule_event_id")){
            $query = $query
                ->leftJoin("m.schedule","sch")
                ->leftJoin("sch.event", "evt")
                ->leftJoin("evt.summit", "s");
        }

        if(!is_null($filter)){

            $filter->apply2Query($query, [
                'irc'               => 'm.irc_handle:json_string',
                'created'           => 'm.created:datetime_epoch',
                'last_edited'       => 'm.last_edited:datetime_epoch',
                'twitter'           => 'm.twitter_handle:json_string',
                'first_name'        => 'm.first_name:json_string',
                'last_name'         => 'm.last_name:json_string',
                'github_user'       => 'm.github_user:json_string',
                'schedule_event_id' => 'evt.id',
                'summit_id'         => 's.id',
                'full_name'         => new DoctrineFilterMapping("concat(m.first_name, ' ', m.last_name) :operator :value"),
                'email'             => ['m.email:json_string', 'm.second_email:json_string', 'm.third_email:json_string'],
                'group_slug'        => new DoctrineJoinFilterMapping
                (
                    'm.groups',
                    'g',
                    "g.code :operator :value"
                ),
                'group_id'    => new DoctrineJoinFilterMapping
                (
                    'm.groups',
                    'g',
                    "g.id :operator :value"
                ),
                'email_verified' => 'm.email_verified:json_int',
                'active'         => 'm.active:json_int',
            ]);
        }

        if (!is_null($order)) {

            $order->apply2Query($query, [
                'id'          => 'm.id',
                'first_name'  => 'm.first_name',
                'last_name'   => 'm.last_name',
                'created'     => 'm.created',
                'last_edited' => 'm.last_edited',
            ]);
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
     * @return string
     */
    protected function getBaseEntity()
    {
       return Member::class;
    }

    /**
     * @param string $fullname
     * @return Member|null
     */
    public function getByFullName(string $fullname): ?Member
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e")
            ->where("concat(e.first_name, ' ', e.last_name) like :full_name")
            ->setParameter("full_name", '%'.trim($fullname).'%')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param int $external_id
     * @return Member|null
     */
    public function getByExternalId(int $external_id): ?Member
    {
       return $this->findOneBy([
           'user_external_id' => $external_id
       ]);
    }

    public function getByExternalIdExclusiveLock(int $external_id): ?Member{
        $query  =   $this->getEntityManager()
            ->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e")
            ->where("e.user_external_id = :user_external_id");

        $query->setParameter("user_external_id", $external_id);

        return $query->getQuery()
            ->setLockMode(\Doctrine\DBAL\LockMode::PESSIMISTIC_WRITE)
            ->getOneOrNullResult();
    }

    /**
     * @inheritDoc
     */
    public function getByEmailExclusiveLock($email): ?Member
    {
        $query  =   $this->getEntityManager()
            ->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e")
            ->where("e.email = :email");

        $query->setParameter("email", $email);

        return $query->getQuery()
            ->setLockMode(\Doctrine\DBAL\LockMode::PESSIMISTIC_WRITE)
            ->getOneOrNullResult();
    }
}