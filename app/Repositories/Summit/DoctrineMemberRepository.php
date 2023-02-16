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

use App\libs\Utils\PunnyCodeHelper;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use models\main\IMemberRepository;
use models\main\Member;
use App\Repositories\SilverStripeDoctrineRepository;
use models\summit\Summit;
use models\summit\SummitSelectedPresentation;
use models\summit\SummitSelectedPresentationList;
use models\utils\SilverstripeBaseModel;
use utils\DoctrineCaseFilterMapping;
use utils\DoctrineFilterMapping;
use utils\DoctrineJoinFilterMapping;
use utils\DoctrineSwitchFilterMapping;
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
     * @return string
     */
    protected function getBaseEntity()
    {
        return Member::class;
    }

    /**
     * @param QueryBuilder $query
     * @param Filter|null $filter
     * @return QueryBuilder
     */
    protected function applyExtraJoins(QueryBuilder $query, ?Filter $filter = null): QueryBuilder
    {
        if($filter->hasFilter("summit_id") || $filter->hasFilter("schedule_event_id")){
            $query
                ->leftJoin("e.schedule","sch")
                ->leftJoin("sch.event", "evt")
                ->leftJoin("evt.summit", "s");
        }
        return $query;
    }

    /**
     * @return array
     */
    protected function getFilterMappings()
    {
        $args = func_get_args();
        $filter = count($args) > 0 ? $args[0] : null;

        $extraSelectionStatusFilter = '';
        $extraSelectionPlanFilter = '';

        if(!is_null($filter) && $filter instanceof Filter){
            if($filter->hasFilter("presentations_selection_plan_id")){
                $e = $filter->getFilter("presentations_selection_plan_id");
                $v = [];
                foreach($e as $f){
                    if(is_array($f->getValue())){
                        foreach ($f->getValue() as $iv){
                            $v[] = $iv;
                        }
                    }
                    else
                        $v[] = $f->getValue();
                }
                $extraSelectionStatusFilter .= ' AND __sel_plan%1$s.id IN ('.implode(',', $v).')';
            }
            if($filter->hasFilter("presentations_track_id")){
                $e = $filter->getFilter("presentations_track_id");
                $v = [];
                foreach($e as $f){
                    if(is_array($f->getValue())){
                        foreach ($f->getValue() as $iv){
                            $v[] = $iv;
                        }
                    }
                    else
                        $v[] = $f->getValue();
                }
                $extraSelectionStatusFilter .= ' AND __cat%1$s.id IN ('.implode(',', $v).')';
                $extraSelectionPlanFilter .= ' AND __tr%1$s_:i.id IN ('.implode(',', $v).')';
            }
            if($filter->hasFilter("presentations_type_id")){
                $e = $filter->getFilter("presentations_type_id");
                $v = [];
                foreach($e as $f){
                    if(is_array($f->getValue())){
                        foreach ($f->getValue() as $iv){
                            $v[] = $iv;
                        }
                    }
                    else
                        $v[] = $f->getValue();
                }
                $extraSelectionStatusFilter .= ' AND __t%1$s.id IN ('.implode(',', $v).')';
                $extraSelectionPlanFilter .= ' AND __type%1$s_:i.id IN ('.implode(',', $v).')';
            }
        }

        return [
            'last_name' => new DoctrineFilterMapping(
                "( LOWER(e.last_name) :operator LOWER(:value) )"
            ),
            'full_name' => new DoctrineFilterMapping(
                "( CONCAT(LOWER(e.first_name), ' ', LOWER(e.last_name)) :operator LOWER(:value) )"
            ),
            'first_name' => new DoctrineFilterMapping(
                "( LOWER(e.first_name) :operator LOWER(:value) )"
            ),
            'email' => [
                Filter::buildEmailField('e.email'),
                Filter::buildEmailField('e.second_email'),
                Filter::buildEmailField('e.third_email'),
            ],
            'id' => 'e.id',
            'member_id' => new DoctrineFilterMapping(
                "( e.id :operator :value )"
            ),
            'member_user_external_id' => new DoctrineFilterMapping(
                "( e.user_external_id :operator :value )"
            ),
            'irc'               => 'e.irc_handle:json_string',
            'created'           => sprintf('e.created:datetime_epoch|%s', SilverstripeBaseModel::DefaultTimeZone),
            'last_edited'       => sprintf('e.last_edited:datetime_epoch|%s', SilverstripeBaseModel::DefaultTimeZone),
            'twitter'           => 'e.twitter_handle:json_string',
            'github_user'       => 'e.github_user:json_string',
            'schedule_event_id' => 'evt.id',
            'summit_id'         => 's.id',
            'group_slug'        => new DoctrineJoinFilterMapping
            (
                'e.groups',
                'g',
                "g.code :operator :value"
            ),
            'group_id'    => new DoctrineJoinFilterMapping
            (
                'e.groups',
                'g',
                "g.id :operator :value"
            ),
            'email_verified' => 'e.email_verified:json_int',
            'active'         => 'e.active:json_int',
            'presentations_track_id' => new DoctrineFilterMapping(
                "EXISTS ( 
                              SELECT __p41_:i.id FROM models\summit\Presentation __p41_:i 
                              JOIN __p41_:i.created_by __c41_:i WITH __c41_:i = e.id
                              JOIN __p41_:i.category __tr41_:i 
                              WHERE 
                              __p41_:i.summit = :summit AND
                              __tr41_:i.id :operator :value )"
            ),
            'presentations_selection_plan_id' => new DoctrineFilterMapping(
                "EXISTS ( 
                              SELECT __p51_:i.id FROM models\summit\Presentation __p51_:i 
                              JOIN __p51_:i.created_by __c51_:i WITH __c51_:i = e.id
                              JOIN __p51_:i.selection_plan __sel_plan51_:i 
                              JOIN __p51_:i.category __tr51_:i 
                              JOIN __p51_:i.type __type51_:i 
                              WHERE 
                              __p51_:i.summit = :summit AND
                              __sel_plan51_:i.id :operator :value'.(!empty($extraSelectionPlanFilter) ? sprintf($extraSelectionPlanFilter, '51'):''). ')"
            ),
            'presentations_type_id' =>  new DoctrineFilterMapping(
                "EXISTS ( 
                              SELECT __p61_:i.id FROM models\summit\Presentation __p61_:i 
                              JOIN __p61.created_by __c61 WITH __c61 = e.id
                              JOIN __p61_:i.type __type61_:i 
                              WHERE 
                              __p61_:i.summit = :summit AND
                              __type61_:i.id :operator :value )"
            ),
            'presentations_title' =>  new DoctrineFilterMapping(
                "EXISTS ( 
                              SELECT __p71.id FROM models\summit\Presentation __p71
                              JOIN __p71.created_by __c71 WITH __c71 = e.id
                              WHERE 
                              __p71.summit = :summit AND
                              LOWER(__p71.title) :operator LOWER(:value) )"
            ),
            'presentations_abstract' =>  new DoctrineFilterMapping(
                "EXISTS ( 
                              SELECT __p81.id FROM models\summit\Presentation __p81
                              JOIN __p81.created_by __c81 WITH __c81 = e.id
                              WHERE 
                              __p81.summit = :summit AND
                              LOWER(__p81.abstract) :operator LOWER(:value) )"
            ),
            'presentations_submitter_full_name' => new DoctrineFilterMapping(
                "EXISTS ( 
                              SELECT __p91.id FROM models\summit\Presentation __p91
                              JOIN __p91.created_by __c91 WITH __c91 = e.id
                              JOIN __p91.created_by __cb91
                              WHERE 
                              __p91.summit = :summit AND
                              concat(LOWER(__cb91.first_name), ' ', LOWER(__cb91.last_name)) :operator LOWER(:value) )"
            ),
            'presentations_submitter_email' => new DoctrineFilterMapping(
                "EXISTS ( 
                              SELECT __p10_1.id FROM models\summit\Presentation __p10_1
                              JOIN __p10.created_by __c10 WITH __c10 = e.id
                              JOIN __p10_1.created_by __cb10_1
                              WHERE 
                              __p10_1.summit = :summit AND
                              LOWER(__cb10_1.email) :operator LOWER(:value) )"
            ),
            'has_accepted_presentations' =>
                new DoctrineSwitchFilterMapping([
                        'true' => new DoctrineCaseFilterMapping(
                            'true',
                            sprintf('
                                     EXISTS (
                                        SELECT __p12.id FROM models\summit\Presentation __p12 
                                        JOIN __p12.created_by __c12 WITH __c12 = e.id 
                                        JOIN __p12.category __cat12
                                        JOIN __p12.type __t12
                                        JOIN __p12.selection_plan __sel_plan12 
                                        LEFT JOIN __p12.selected_presentations __sp12 
                                        LEFT JOIN __sp12.list __spl12 
                                        WHERE 
                                        __p12.summit = :summit AND
                                        ((__sp12.order is not null AND
                                        __sp12.order <= __cat12.session_count AND __sp12.collection = \'%1$s\' AND
                                        __spl12.list_type = \'%2$s\' AND __spl12.list_class = \'%3$s\') OR __p12.published = 1) ',
                                SummitSelectedPresentation::CollectionSelected,
                                SummitSelectedPresentationList::Group,
                                SummitSelectedPresentationList::Session).(!empty($extraSelectionStatusFilter)? sprintf($extraSelectionStatusFilter, '12'): '').
                            ' )'
                        ),
                        'false' => new DoctrineCaseFilterMapping(
                            'false',
                            sprintf('
                                     NOT EXISTS (
                                        SELECT __p12.id FROM models\summit\Presentation __p12 
                                        JOIN __p12.created_by __c12 WITH __c12 = e.id 
                                        JOIN __p12.category __cat12
                                        JOIN __p12.type __t12
                                        JOIN __p12.selection_plan __sel_plan12 
                                        LEFT JOIN __p12.selected_presentations __sp12 
                                        LEFT JOIN __sp12.list __spl12
                                        WHERE 
                                        __p12.summit = :summit AND
                                        ((__sp12.order is not null AND
                                        __sp12.order <= __cat12.session_count AND __sp12.collection = \'%1$s\' AND
                                         __spl12.list_type = \'%2$s\' AND __spl12.list_class = \'%3$s\' ) OR __p12.published = 1) '
                                ,
                                SummitSelectedPresentation::CollectionSelected,
                                SummitSelectedPresentationList::Group,
                                SummitSelectedPresentationList::Session).(!empty($extraSelectionStatusFilter)? sprintf($extraSelectionStatusFilter, '12'): '').
                            ')'
                        ),
                    ]
                ),
            'has_alternate_presentations' =>
                new DoctrineSwitchFilterMapping([
                        'true' => new DoctrineCaseFilterMapping(
                            'true',
                            sprintf('EXISTS (
                                        SELECT __p21.id FROM models\summit\Presentation __p21 
                                        JOIN __p21.created_by __c21 WITH __c21 = e.id 
                                        JOIN __p21.category __cat21
                                        JOIN __p21.type __t21
                                        JOIN __p21.selection_plan __sel_plan21 
                                        JOIN __p21.selected_presentations __sp21 WITH __sp21.collection = \'%1$s\'
                                        JOIN __sp21.list __spl21 WITH __spl21.list_type = \'%2$s\' AND __spl21.list_class = \'%3$s\'
                                        WHERE 
                                        __p21.summit = :summit AND
                                        __sp21.order is not null AND
                                        __sp21.order > __cat21.session_count',
                                SummitSelectedPresentation::CollectionSelected,
                                SummitSelectedPresentationList::Group,
                                SummitSelectedPresentationList::Session
                            ).(!empty($extraSelectionStatusFilter)? sprintf($extraSelectionStatusFilter, '21'): '').
                            ' )'
                        ),
                        'false' => new DoctrineCaseFilterMapping(
                            'false',
                            sprintf('NOT EXISTS (
                                        SELECT __p21.id FROM models\summit\Presentation __p21 
                                        JOIN __p21.created_by __c21 WITH __c21 = e.id 
                                        JOIN __p21.category __cat21
                                        JOIN __p21.type __t21
                                        JOIN __p21.selection_plan __sel_plan21 
                                        JOIN __p21.selected_presentations __sp21 WITH __sp21.collection = \'%1$s\'
                                        JOIN __sp21.list __spl21 WITH __spl21.list_type = \'%2$s\' AND __spl21.list_class = \'%3$s\'
                                        WHERE 
                                        __p21.summit = :summit AND
                                        __sp21.order is not null AND
                                        __sp21.order > __cat21.session_count',
                                SummitSelectedPresentation::CollectionSelected,
                                SummitSelectedPresentationList::Group,
                                SummitSelectedPresentationList::Session
                            ).(!empty($extraSelectionStatusFilter)? sprintf($extraSelectionStatusFilter, '21'): '').
                            ')'
                        ),
                    ]
                ),
            'has_rejected_presentations' =>
                new DoctrineSwitchFilterMapping([
                        'true' => new DoctrineCaseFilterMapping(
                            'true',
                            sprintf('EXISTS (
                                        SELECT __p31.id FROM models\summit\Presentation __p31 
                                        JOIN __p31.created_by __c31 WITH __c31 = e.id 
                                        JOIN __p31.category __cat31
                                        JOIN __p31.type __t31
                                        JOIN __p31.selection_plan __sel_plan31 
                                        WHERE 
                                        __p31.summit = :summit 
                                        AND __p31.published = 0'.
                                (!empty($extraSelectionStatusFilter)? sprintf($extraSelectionStatusFilter, '31'): ' ').
                                'AND NOT EXISTS (
                                            SELECT ___sp31.id 
                                            FROM models\summit\SummitSelectedPresentation ___sp31
                                            JOIN ___sp31.presentation ___p31
                                            JOIN ___sp31.list ___spl31 WITH ___spl31.list_type = \'%2$s\' AND ___spl31.list_class = \'%3$s\'
                                            WHERE ___p31.id = __p31.id AND ___sp31.collection = \'%1$s\'
                                        ))',
                                SummitSelectedPresentation::CollectionSelected,
                                SummitSelectedPresentationList::Group,
                                SummitSelectedPresentationList::Session
                            )
                        ),
                        'false' => new DoctrineCaseFilterMapping(
                            'false',
                            sprintf('
                                     NOT EXISTS (
                                        SELECT __p31.id FROM models\summit\Presentation __p31 
                                        JOIN __p31.created_by __c31 WITH __c31 = e.id 
                                        JOIN __p31.category __cat31
                                        JOIN __p31.type __t31
                                        JOIN __p31.selection_plan __sel_plan31 
                                        WHERE 
                                        __p31.summit = :summit  
                                        AND __p31.published = 0'
                                .(!empty($extraSelectionStatusFilter)? sprintf($extraSelectionStatusFilter, '31'): ' ').
                                'AND NOT EXISTS (
                                            SELECT ___sp31.id 
                                            FROM models\summit\SummitSelectedPresentation ___sp31
                                            JOIN ___sp31.presentation ___p31
                                            JOIN ___sp31.list ___spl31 WITH ___spl31.list_type = \'%2$s\' AND ___spl31.list_class = \'%3$s\'
                                            WHERE ___p31.id = __p31.id AND ___sp31.collection = \'%1$s\'
                                        ))',
                                SummitSelectedPresentation::CollectionSelected,
                                SummitSelectedPresentationList::Group,
                                SummitSelectedPresentationList::Session
                            )
                        ),
                    ]
                ),
            'is_speaker' =>
                new DoctrineSwitchFilterMapping([
                        'true' => new DoctrineCaseFilterMapping(
                            'true',
                            '
                                 EXISTS (
                                    SELECT __p12.id FROM models\summit\Presentation __p12 
                                    JOIN __p12.speakers __spk12 WITH __spk12.member = e.id 
                                    WHERE __p12.summit = :summit
                                 ) 
                                 OR 
                                 EXISTS (
                                    SELECT __p14.id FROM models\summit\Presentation __p14 
                                    JOIN __p14.moderator __md14 WITH __md14.member = e.id 
                                    WHERE __p14.summit = :summit
                                 )'
                        ),
                        'false' => new DoctrineCaseFilterMapping(
                            'false',
                            '
                                NOT EXISTS (
                                    SELECT __p12.id FROM models\summit\Presentation __p12 
                                    JOIN __p12.speakers __spk12 WITH __spk12.member = e.id 
                                    WHERE __p12.summit = :summit
                                ) 
                                AND  
                                NOT EXISTS (
                                    SELECT __p14.id FROM models\summit\Presentation __p14 
                                    JOIN __p14.moderator __md14 WITH __md14.member = e.id 
                                    WHERE __p14.summit = :summit
                                )'
                        ),
                    ]
                ),
        ];
    }

    /**
     * @return array
     */
    protected function getOrderMappings()
    {
        return [
            'id' => 'e.id',
            'first_name' => 'e.first_name',
            'last_name' => 'e.last_name',
            'full_name' => <<<SQL
LOWER(CONCAT(e.first_name, ' ', e.last_name))
SQL,
            'email' => 'e.email',
            'created' => 'e.created',
            'last_edited' => 'e.last_edited',
        ];
    }

    /**
     * @param string $email
     * @return Member|null
     */
    public function getByEmail($email): ?Member
    {
        $email = PunnyCodeHelper::encodeEmail($email);
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
        return $this->getParametrizedAllByPage(function () {
            return $this->getEntityManager()->createQueryBuilder()
                ->select("e")
                ->from($this->getBaseEntity(), "e")
                ->andWhere("e.first_name is not null")
                ->andWhere("e.last_name is not null");
        },
            $paging_info,
            $filter,
            $order,
            function ($query) {
                //default order
                $query->addOrderBy("e.first_name",'ASC');
                $query->addOrderBy("e.last_name", 'ASC');
                return $query;
            });
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
        $email = PunnyCodeHelper::encodeEmail($email);
        $query = $this->getEntityManager()
            ->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e")
            ->where("e.email = :email");

        $query->setParameter("email", $email);

        return $query->getQuery()
            ->setLockMode(\Doctrine\DBAL\LockMode::PESSIMISTIC_WRITE)
            ->getOneOrNullResult();
    }

    /**
     * @inheritDoc
     */
    public function getSubmittersBySummit(Summit $summit, PagingInfo $paging_info, Filter $filter = null, Order $order = null)
    {
        return $this->getParametrizedAllByPage(function () use ($summit) {
            return $this->getEntityManager()->createQueryBuilder()
                ->distinct(true)
                ->select("e")
                ->from($this->getBaseEntity(), "e")
                ->where(" 
                         EXISTS (
                            SELECT __p.id FROM models\summit\Presentation __p 
                            JOIN __p.created_by __cb93 WITH __cb93 = e.id 
                            WHERE __p.summit = :summit
                         )")
                ->setParameter("summit", $summit);
        },
            $paging_info,
            $filter,
            $order,
            function ($query) {
                //default order
                return $query->addOrderBy("e.id", 'ASC');
            });
    }

    /**
     * @inheritDoc
     */
    public function getSubmittersIdsBySummit(Summit $summit, PagingInfo $paging_info, Filter $filter = null, Order $order = null)
    {
        return $this->getParametrizedAllIdsByPage(function () use ($summit) {
            return $this->getEntityManager()->createQueryBuilder()
                ->distinct(true)
                ->select("e.id")
                ->from($this->getBaseEntity(), "e")
                ->where(" 
                         EXISTS (
                            SELECT __p.id FROM models\summit\Presentation __p 
                            JOIN __p.created_by __cb93 WITH __cb93 = e.id 
                            WHERE __p.summit = :summit
                         )")
                ->setParameter("summit", $summit);
        },
            $paging_info,
            $filter,
            $order,
            function ($query) {
                //default order
                return $query->addOrderBy("e.id", 'ASC');
            });
    }
}