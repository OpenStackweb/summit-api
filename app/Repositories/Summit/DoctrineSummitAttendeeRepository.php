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
use Doctrine\ORM\QueryBuilder;
use Illuminate\Support\Facades\Log;
use models\main\Member;
use models\summit\IOrderConstants;
use models\summit\ISummitAttendeeRepository;
use models\summit\Summit;
use models\summit\SummitAttendee;
use App\Repositories\SilverStripeDoctrineRepository;
use models\summit\SummitAttendeeTicket;
use models\utils\SilverstripeBaseModel;
use utils\DoctrineCaseFilterMapping;
use utils\DoctrineFilterMapping;
use utils\DoctrineHavingFilterMapping;
use utils\DoctrineLeftJoinFilterMapping;
use utils\DoctrineSwitchFilterMapping;
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

    /**
     * @param QueryBuilder $query
     * @param Filter|null $filter
     * @return QueryBuilder
     */
    protected function applyExtraJoins(QueryBuilder $query, ?Filter $filter = null, ?Order $order = null){
        $query =  $query->join('e.summit', 's')
            ->leftJoin('e.member', 'm')
            ->leftJoin('e.tickets', 't')
            ->leftJoin('e.notes', 'n')
            ->leftJoin('e.company', 'a_c');

        if($filter->hasFilter("presentation_votes_count")){
            $query = $query->leftJoin("e.presentation_votes","pv");
        }

        if($filter->hasFilter("presentation_votes_track_group_id")){
            $query = $query->leftJoin("pv.presentation", "p")
                ->leftJoin("p.category", "pc")
                ->leftJoin("pc.groups","pcg");
        }

        if(
            $filter->hasFilter("ticket_type") ||
            $filter->hasFilter("badge_type") ||
            $filter->hasFilter("badge_type_id") ||
            $filter->hasFilter('features') ||
            $filter->hasFilter('features_id') ||
            $filter->hasFilter('access_levels') ||
            $filter->hasFilter('access_levels_ids') ||
            $filter->hasFilter('ticket_type_id')
        ) {
            $query = $query->leftJoin('t.badge', 'b', 'WITH', sprintf("t.is_active = 1 AND t.status='%s'",  IOrderConstants::PaidStatus))
                ->leftJoin('b.type', 'bt')
                ->leftJoin('t.ticket_type', 'tt','WITH', sprintf("t.is_active = 1 AND t.status='%s'",  IOrderConstants::PaidStatus));
        }

        if(
            $filter->hasFilter('features') ||
            $filter->hasFilter('features_id')
        ) {
            $query = $query->leftJoin('b.features', 'bf')
                ->leftJoin("bt.badge_features","btf");
        }

        if(
            $filter->hasFilter('access_levels') ||
            $filter->hasFilter('access_levels_ids') ){
            $query = $query->leftJoin("bt.access_levels","bac");
        }

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
            'summit_id'            => new DoctrineFilterMapping("s.id :operator :value"),
            'member_id'            => new DoctrineFilterMapping("m.id :operator :value"),
            'first_name'           => [
                "m.first_name :operator :value",
                "e.first_name :operator :value"
            ],
            'has_member' => new DoctrineSwitchFilterMapping([
                    'true' => new DoctrineCaseFilterMapping(
                        'true',
                        "m.id is not null and m.id > 0"
                    ),
                    'false' => new DoctrineCaseFilterMapping(
                        'false',
                        "m.id is null"
                    ),
                ]
            ),
            'has_tickets' => new DoctrineSwitchFilterMapping([
                    'true' => new DoctrineCaseFilterMapping(
                        'true',
                        sprintf
                        (
                            "EXISTS (select t1 from %s t1 where t1.owner = e and t1.status = '%s' and t1.is_active = 1)",
                            SummitAttendeeTicket::class,
                            IOrderConstants::PaidStatus,
                        )
                    ),
                    'false' => new DoctrineCaseFilterMapping(
                        'false',
                        sprintf
                        (
                            "NOT EXISTS (select t1 from %s t1 where t1.owner = e and t1.status = '%s' and t1.is_active = 1)"
                            ,
                            SummitAttendeeTicket::class,
                            IOrderConstants::PaidStatus,
                        )
                    ),
                ]
            ),
            'has_virtual_checkin' => new DoctrineSwitchFilterMapping([
                    'true' => new DoctrineCaseFilterMapping(
                        'true',
                        "e.summit_virtual_checked_in_date is not null"
                    ),
                    'false' => new DoctrineCaseFilterMapping(
                        'false',
                        "e.summit_virtual_checked_in_date is null"
                    ),
                ]
            ),
            'has_checkin' => new DoctrineSwitchFilterMapping([
                    'true' => new DoctrineCaseFilterMapping(
                        'true',
                        "e.summit_hall_checked_in = 1"
                    ),
                    'false' => new DoctrineCaseFilterMapping(
                        'false',
                        "e.summit_hall_checked_in = 0"
                    ),
                ]
            ),
            'tickets_count' => new DoctrineHavingFilterMapping
            (
                "",
                "t.owner",
                sprintf
                (
                    "COALESCE(SUM(CASE WHEN (t.status = '%s' AND t.is_active = 1) THEN 1 ELSE 0 END), 0)  :operator :value", IOrderConstants::PaidStatus
                )
            ),
            'ticket_type' => new DoctrineFilterMapping("tt.name :operator :value"),
            'ticket_type_id' => new DoctrineFilterMapping("tt.id :operator :value"),
            'badge_type' => new DoctrineFilterMapping("bt.name :operator :value"),
            'badge_type_id' => new DoctrineFilterMapping("bt.id :operator :value"),
            'status' =>  new DoctrineFilterMapping("e.status :operator :value"),
            'last_name'            => [
                "m.last_name :operator :value",
                "e.surname :operator :value"
            ],
            'full_name'            => [
                "concat(m.first_name, ' ', m.last_name) :operator :value",
                "concat(e.first_name, ' ', e.surname) :operator :value"
            ],
            'company'     => 'COALESCE(e.company_name, a_c.name)',
            'has_company' => new DoctrineSwitchFilterMapping([
                    '1' => new DoctrineCaseFilterMapping(
                        'true',
                        "((e.company_name is not null AND e.company_name <> '') OR (a_c.name is not null AND a_c.name <> ''))"
                    ),
                    '0' => new DoctrineCaseFilterMapping(
                        'false',
                        "((e.company_name is null OR e.company_name = '') AND (a_c.name is null OR a_c.name = ''))"
                    ),
                ]
            ),
            'email'                => [
                Filter::buildEmailField("m.email"),
                Filter::buildEmailField("e.email")
            ],
            'external_order_id'    => new DoctrineFilterMapping("t.external_order_id :operator :value"),
            'external_attendee_id' => new DoctrineFilterMapping("t.external_attendee_id :operator :value"),
            'presentation_votes_date' => 'pv.created:datetime_epoch|'.SilverstripeBaseModel::DefaultTimeZone,
            'presentation_votes_count' => new DoctrineHavingFilterMapping("", "pv.voter", "count(pv.id) :operator :value"),
            'presentation_votes_track_group_id' => new DoctrineFilterMapping("pcg.id :operator :value"),
            'features' => [
                'bf.name :operator :value',
                'btf.name :operator :value',
            ],
            'features_id' => [
                'bf.id :operator :value',
                'btf.id :operator :value',
            ],
            'access_levels' => 'bac.name :operator :value',
            'access_levels_id' => 'bac.id :operator :value',
            'summit_hall_checked_in_date' => Filter::buildDateTimeEpochField("e.summit_hall_checked_in_date"),
            'tags' => new DoctrineLeftJoinFilterMapping("e.tags", "tags","tags.tag :operator :value"),
            'tags_id' => new DoctrineLeftJoinFilterMapping("e.tags", "tags","tags.id :operator :value"),
            'notes' => new DoctrineLeftJoinFilterMapping("e.notes", "notes","notes.content :operator :value"),
            'has_notes' => new DoctrineSwitchFilterMapping([
                    'true' => new DoctrineCaseFilterMapping(
                        'true',
                        "SIZE(e.notes) > 0"
                    ),
                    'false' => new DoctrineCaseFilterMapping(
                        'false',
                        "SIZE(e.notes) = 0"
                    )
                ]
            ),
            'has_manager' => new DoctrineSwitchFilterMapping([
                    'true' => new DoctrineCaseFilterMapping(
                        'true',
                        "e.manager is not null"
                    ),
                    'false' => new DoctrineCaseFilterMapping(
                        'false',
                        "e.manager is null"
                    ),
                ]
            ),
        ];
    }

    protected function applyExtraSelects(QueryBuilder $query, ?Filter $filter = null, ?Order $order = null):QueryBuilder{
        if(!is_null($order)) {
            if($order->hasOrder('tickets_count'))
                $query = $query->addSelect
                (
                    sprintf
                    (
                        "COALESCE(SUM(CASE WHEN (t.status = '%s' AND t.is_active = 1) THEN 1 ELSE 0 END), 0) AS HIDDEN HIDDEN_TICKETS_QTY",
                        IOrderConstants::PaidStatus
                    )
                );
            if($order->hasOrder('has_notes'))
                $query = $query->addSelect(
                    "COALESCE(CASE WHEN (COUNT(n.id) = 0) THEN 0 ELSE 1 END, 0) AS HIDDEN HIDDEN_HAS_NOTES_ORDER"
                );
        }
        $query->groupBy("e");
        return $query;
    }

    /**
     * @return array
     */
    protected function getOrderMappings()
    {
        return [
            'id'                => 'e.id',
            'first_name'        => 'e.first_name',
            'last_name'         => 'e.surname',
            "full_name"         => <<<SQL
COALESCE(LOWER(CONCAT(e.first_name, ' ', e.surname)), LOWER(CONCAT(m.first_name, ' ', m.last_name)))
SQL,
            'external_order_id' => 't.external_order_id',
            'company'           => 'COALESCE(e.company_name, a_c.name)',
            'member_id'         => 'm.id',
            'status'            => 'e.status',
            'email'             => <<<SQL
COALESCE(LOWER(m.email), LOWER(e.email)) 
SQL,
            'presentation_votes_count' => 'COUNT(pv.id)',
            'summit_hall_checked_in_date' => 'e.summit_hall_checked_in_date',
            'tickets_count' => 'HIDDEN_TICKETS_QTY',
            'has_notes'     => 'HIDDEN_HAS_NOTES_ORDER'
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

        $paginator = new Paginator($query);
        $data      = [];
        foreach($paginator as $entity)
            $data[] = $entity;

        $total     = $paginator->count();

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
            ->andWhere("(m.email = :email or e.email = :email)")
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
     * @param string $email
     * @return mixed
     */
    public function getByEmailAndMemberNotSet(string $email)
    {
        $query  = $this->getEntityManager()
            ->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e")
            ->where("e.email = :email")
            ->andWhere("e.member is null")
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


    /**
     * @param Summit $summit
     * @param null|string $external_id
     * @return SummitAttendee|null
     */
    public function getBySummitAndExternalId(Summit $summit, string $external_id):?SummitAttendee
    {
        $query  = $this->getEntityManager()
            ->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e")
            ->leftJoin('e.summit', 's')
            ->leftJoin('e.member', 'm')
            ->where("s.id = :summit_id")
            ->andWhere("e.external_id = :external_id");

        $query = $query->setParameter("summit_id", $summit->getId())->setParameter("external_id", $external_id);

        return $query->getQuery()->getOneOrNullResult();
    }

    /**
     * @param Member $member
     * @return mixed
     */
    public function getByMember(Member $member)
    {
        $query  = $this->getEntityManager()
            ->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e")
            ->leftJoin('e.summit', 's')
            ->leftJoin('e.member', 'm')
            ->where("m.id = :member_id")
            ->setParameter("member_id", $member->getId());

        return $query->getQuery()->getResult();
    }

    /**
     * @param int $summit_id
     * @return bool
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    public function deleteAllBySummit(int $summit_id):bool{
        try {
            $sql = <<<SQL
DELETE A FROM SummitAttendee A WHERE A.SummitID = :summit_id;
SQL;

            $stmt = $this->getEntityManager()->getConnection()->prepare($sql);
            return $stmt->executeStatement([
                'summit_id' => $summit_id,
            ])  > 0;

        }
        catch (\Exception $ex)
        {
            Log::error($ex);
        }
    }

    /**
     * @param Summit $summit
     * @param string $first_name
     * @param string $last_name
     * @param SummitAttendee $manager
     * @return SummitAttendee|null
     */
    public function getBySummitAndFirstNameAndLastNameAndManager(Summit $summit, string $first_name, string $last_name, SummitAttendee $manager):?SummitAttendee
    {
        $query  = $this->getEntityManager()
            ->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e")
            ->leftJoin('e.summit', 's')
            ->leftJoin('e.member', 'm')
            ->where("s.id = :summit_id")->andWhere("e.manager = :manager");
        $query = $query->andWhere("e.first_name = :first_name")->setParameter("first_name", $first_name);
        $query = $query->andWhere("e.surname = :surname")->setParameter("surname", $last_name);
        $query = $query
            ->setParameter("manager", $manager)
            ->setParameter("summit_id", $summit->getId());
        return $query->getQuery()->getOneOrNullResult();
    }

}