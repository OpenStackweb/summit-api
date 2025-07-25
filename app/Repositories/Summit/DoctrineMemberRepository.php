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
use App\Http\Utils\Filters\SQL\SQLInFilterMapping;
use App\Http\Utils\Filters\SQL\SQLNotInFilterMapping;
use App\libs\Utils\PunnyCodeHelper;
use Doctrine\ORM\QueryBuilder;
use Illuminate\Support\Facades\Log;
use libs\utils\TextUtils;
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
    protected function applyExtraJoins(QueryBuilder $query, ?Filter $filter = null, ?Order $order = null): QueryBuilder
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
        $extraMediaUploadFilter = '';

        if(!is_null($filter) && $filter instanceof Filter){
            if($filter->hasFilter("presentations_selection_plan_id")){
                $v = $filter->getValue("presentations_selection_plan_id");
                $extraSelectionStatusFilter .= ' AND __sel_plan%1$s.id IN ('.implode(',', $v).')';
                $extraMediaUploadFilter .= ' AND __sel_plan%1$s:i.id IN ('.implode(',', $v).')';
            }
            if($filter->hasFilter("presentations_track_id")){
                $v = $filter->getValue("presentations_track_id");
                $extraSelectionStatusFilter .= ' AND __cat%1$s.id IN ('.implode(',', $v).')';
                $extraSelectionPlanFilter .= ' AND __tr%1$s_:i.id IN ('.implode(',', $v).')';
                $extraMediaUploadFilter .= ' AND __tr%1$s:i.id IN ('.implode(',', $v).')';
            }
            if($filter->hasFilter("presentations_type_id")){
                $v = $filter->getValue("presentations_type_id");
                $extraSelectionStatusFilter .= ' AND __t%1$s.id IN ('.implode(',', $v).')';
                $extraSelectionPlanFilter .= ' AND __type%1$s_:i.id IN ('.implode(',', $v).')';
                $extraMediaUploadFilter .= ' AND __type%1$s:i.id IN ('.implode(',', $v).')';
            }

            if($filter->hasFilter("has_media_upload_with_type")){
                $v = $filter->getValue("has_media_upload_with_type");
                $extraSelectionStatusFilter .= ' AND __mut%1$s.id IN ('.implode(',', $v).')';
            }
            if($filter->hasFilter("has_not_media_upload_with_type")){
                $v = $filter->getValue("has_not_media_upload_with_type");
                $extraSelectionStatusFilter .= ' AND NOT EXISTS (   
                     SELECT __pm%1$s_%1$s.id FROM models\summit\PresentationMediaUpload __pm%1$s_%1$s 
                     LEFT JOIN __pm%1$s_%1$s.media_upload_type __mut%1$s_%1$s
                     WHERE  __pm%1$s_%1$s.presentation = __p%1$s AND  __mut%1$s_%1$s.id IN ('.implode(',', $v).')
                 ) ';
            }
        }

        return [
            'id' => new DoctrineInFilterMapping('e.id'),
            'not_id' => new DoctrineNotInFilterMapping('e.id'),
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
            'member_id' => new DoctrineFilterMapping(
                "( e.id :operator :value )"
            ),
            'member_user_external_id' => new DoctrineFilterMapping(
                "( e.user_external_id :operator :value )"
            ),
            'membership_type'   => 'e.membership_type:json_string',
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
                              __sel_plan51_:i.id :operator :value".(!empty($extraSelectionPlanFilter) ? sprintf($extraSelectionPlanFilter, '51'):''). ")"
            ),
            'presentations_type_id' =>  new DoctrineFilterMapping(
                "EXISTS ( 
                              SELECT __p61_:i.id 
                              FROM models\summit\Presentation __p61_:i 
                              JOIN __p61_:i.created_by __c61:i WITH __c61:i = e.id
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
                              WHERE 
                              __p91.summit = :summit AND
                              concat(LOWER(__c91.first_name), ' ', LOWER(__c91.last_name)) :operator LOWER(:value) )"
            ),
            'presentations_submitter_email' => new DoctrineFilterMapping(
                "EXISTS ( 
                              SELECT __p10_1.id FROM models\summit\Presentation __p10_1
                              JOIN __p10_1.created_by __c10 WITH __c10 = e.id
                              WHERE 
                              __p10_1.summit = :summit AND
                              LOWER(__c10.email) :operator LOWER(:value) )"
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
                                        LEFT JOIN __p12.selection_plan __sel_plan12 
                                        LEFT JOIN __p12.selected_presentations __sp12 
                                        LEFT JOIN __sp12.list __spl12 
                                        LEFT JOIN models\summit\PresentationMediaUpload __pm12 WITH __pm12.presentation = __p12
                                        LEFT JOIN __pm12.media_upload_type __mut12
                                        WHERE 
                                        __p12.summit = :summit AND
                                        ((__sp12.order is not null AND
                                        __sp12.order <= __cat12.session_count AND __sp12.collection = \'%1$s\' AND
                                        __spl12.list_type = \'%2$s\' AND __spl12.list_class = \'%3$s\') OR __p12.published = 1) ',
                                SummitSelectedPresentation::CollectionSelected,
                                SummitSelectedPresentationList::Group,
                                SummitSelectedPresentationList::Session
                            ).(!empty($extraSelectionStatusFilter)? sprintf($extraSelectionStatusFilter, '12'): '').
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
                                        LEFT JOIN __p12.selection_plan __sel_plan12 
                                        LEFT JOIN __p12.selected_presentations __sp12 
                                        LEFT JOIN __sp12.list __spl12
                                        LEFT JOIN models\summit\PresentationMediaUpload __pm12 WITH __pm12.presentation = __p12
                                        LEFT JOIN __pm12.media_upload_type __mut12
                                        WHERE 
                                        __p12.summit = :summit AND
                                        ((__sp12.order is not null AND
                                        __sp12.order <= __cat12.session_count AND __sp12.collection = \'%1$s\' AND
                                         __spl12.list_type = \'%2$s\' AND __spl12.list_class = \'%3$s\' ) OR __p12.published = 1) '
                                ,
                                SummitSelectedPresentation::CollectionSelected,
                                SummitSelectedPresentationList::Group,
                                SummitSelectedPresentationList::Session
                            ).(!empty($extraSelectionStatusFilter)? sprintf($extraSelectionStatusFilter, '12'): '').
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
                                        LEFT JOIN __p21.selection_plan __sel_plan21 
                                        JOIN __p21.selected_presentations __sp21 WITH __sp21.collection = \'%1$s\'
                                        JOIN __sp21.list __spl21 WITH __spl21.list_type = \'%2$s\' AND __spl21.list_class = \'%3$s\'
                                        LEFT JOIN models\summit\PresentationMediaUpload __pm21 WITH __pm21.presentation = __p21
                                        LEFT JOIN __pm21.media_upload_type __mut21
                                        WHERE 
                                        __p21.summit = :summit AND
                                        __sp21.order is not null AND
                                        __sp21.order > __cat21.session_count ',
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
                                        LEFT JOIN __p21.selection_plan __sel_plan21 
                                        JOIN __p21.selected_presentations __sp21 WITH __sp21.collection = \'%1$s\'
                                        JOIN __sp21.list __spl21 WITH __spl21.list_type = \'%2$s\' AND __spl21.list_class = \'%3$s\'
                                        LEFT JOIN models\summit\PresentationMediaUpload __pm21 WITH __pm21.presentation = __p21
                                        LEFT JOIN __pm21.media_upload_type __mut21
                                        WHERE 
                                        __p21.summit = :summit AND
                                        __sp21.order is not null AND
                                        __sp21.order > __cat21.session_count ',
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
                                        LEFT JOIN __p31.selection_plan __sel_plan31 
                                        LEFT JOIN models\summit\PresentationMediaUpload __pm31 WITH __pm31.presentation = __p31
                                        LEFT JOIN __pm31.media_upload_type __mut31
                                        WHERE 
                                        __p31.summit = :summit 
                                        AND __p31.published = 0 '.
                                (!empty($extraSelectionStatusFilter)? sprintf($extraSelectionStatusFilter, '31'): ' ').
                                ' AND NOT EXISTS (
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
                                        LEFT JOIN __p31.selection_plan __sel_plan31 
                                        LEFT JOIN models\summit\PresentationMediaUpload __pm31 WITH __pm31.presentation = __p31
                                        LEFT JOIN __pm31.media_upload_type __mut31
                                        WHERE 
                                        __p31.summit = :summit  
                                        AND __p31.published = 0 '.
                                (!empty($extraSelectionStatusFilter)? sprintf($extraSelectionStatusFilter, '31'): ' ').
                                ' AND NOT EXISTS (
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
                            '(
                                 EXISTS (
                                    SELECT __p61.id FROM models\summit\Presentation __p61 
                                    JOIN __p61.created_by __c61 WITH __c61 = e.id 
                                    JOIN __p61.speakers __pspk61 
                                    JOIN __pspk61.speaker __spk61 WITH __spk61.member = e.id 
                                    WHERE __p61.summit = :summit
                                 ) 
                                 OR 
                                 EXISTS (
                                    SELECT __p62.id FROM models\summit\Presentation __p62 
                                    JOIN __p62.created_by __c62 WITH __c62 = e.id 
                                    JOIN __p62.moderator __md62 WITH __md62.member = e.id 
                                    WHERE __p62.summit = :summit
                                 ))'
                        ),
                        'false' => new DoctrineCaseFilterMapping(
                            'false',
                            '(
                                NOT EXISTS (
                                    SELECT __p61.id FROM models\summit\Presentation __p61 
                                    JOIN __p61.created_by __c61 WITH __c61 = e.id 
                                    JOIN __p61.speakers __pspk61
                                    JOIN __pspk61.speaker __spk61 WITH __spk61.member = e
                                    WHERE __p61.summit = :summit
                                ) 
                                AND  
                                NOT EXISTS (
                                    SELECT __p62.id FROM models\summit\Presentation __p62 
                                    JOIN __p62.created_by __c62 WITH __c62 = e.id 
                                    JOIN __p62.moderator __md62 WITH __md62.member = e
                                    WHERE __p62.summit = :summit
                                ))'
                        ),
                    ]
                ),
            'has_media_upload_with_type' =>  new DoctrineFilterMapping(
                "EXISTS (
                            SELECT __pm10_3:i.id 
                            FROM models\summit\PresentationMediaUpload __pm10_3:i
                            JOIN __pm10_3:i.media_upload_type __mut10_3:i
                            JOIN __pm10_3:i.presentation __p10_3:i
                            JOIN __p10_3:i.created_by __c10_3:i WITH __c10_3:i = e
                            LEFT JOIN __p10_3:i.selection_plan __sel_plan10_3:i
                            JOIN __p10_3:i.category __tr10_3:i
                            JOIN __p10_3:i.type __type10_3:i
                            WHERE 
                            __p10_3:i.summit = :summit AND
                            __mut10_3:i.id :operator :value ".
                            (!empty($extraMediaUploadFilter)? sprintf($extraMediaUploadFilter, '10_3'): ' ').
                        ")"
            ),
            'has_not_media_upload_with_type' =>  new DoctrineFilterMapping(
                "NOT EXISTS (
                            SELECT __pm10_4:i.id 
                            FROM models\summit\PresentationMediaUpload __pm10_4:i
                            JOIN __pm10_4:i.media_upload_type __mut10_4:i
                            JOIN __pm10_4:i.presentation __p10_4:i
                            JOIN __p10_4:i.created_by __c10_4:i WITH __c10_4:i = e 
                            LEFT JOIN __p10_4:i.selection_plan __sel_plan10_4:i
                            JOIN __p10_4:i.category __tr10_4:i
                            JOIN __p10_4:i.type __type10_4:i
                            WHERE 
                            __p10_4:i.summit = :summit AND
                            __mut10_4:i.id :operator :value ".
                            (!empty($extraMediaUploadFilter)? sprintf($extraMediaUploadFilter, '10_4'): ' ').
                        ")"
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
            'membership_type' => 'e.membership_type',
        ];
    }

    /**
     * @param string $email
     * @return Member|null
     */
    public function getByEmail($email): ?Member
    {
        $email = PunnyCodeHelper::encodeEmail($email);
        if(empty($email)) return null;
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e")
            ->where("e.email = :email")
            ->setParameter("email", strtolower(TextUtils::trim($email)))
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
                ->from($this->getBaseEntity(), "e");
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
        Log::debug(sprintf("DoctrineMemberRepository::getSubmittersBySummit summit %s", $summit->getId()));
        $start  = time();
        $res = $this->getParametrizedAllByPage(function () use ($summit) {
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

        $end = time();
        $delta = $end - $start;

        Log::debug(sprintf("DoctrineMemberRepository::getSubmittersBySummit summit %s duration %s seconds.", $summit->getId(), $delta));

        return $res;
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

    /**
     * @param PagingInfo $paging_info
     * @param Filter|null $filter
     * @param Order|null $order
     * @return PagingResponse
     * @throws \Doctrine\DBAL\Exception
     */
    public function getAllCompaniesByPage(PagingInfo $paging_info, Filter $filter = null, Order $order = null)
    {
        $bindings = [];
        $extra_filters = " WHERE Company IS NOT NULL ";
        $extra_orders = '';
        if ($filter instanceof Filter) {
            $where_conditions = $filter->toRawSQL([
                'company'   => 'Company'
            ]);

           if (!empty($where_conditions)) {
               $extra_filters .= " AND {$where_conditions} ";
               $bindings = array_merge($bindings, $filter->getSQLBindings());
           }
        }
        if (!is_null($order)) {
            $extra_orders = $order->toRawSQL([
                'company'   => 'Company',
            ]);
        }

        $query_from = <<<SQL
FROM `Member`
SQL;


        $query_count = <<<SQL
SELECT COUNT(DISTINCT(Company)) AS QTY
{$query_from}
{$extra_filters}
SQL;

        $stm = $this->getEntityManager()->getConnection()->executeQuery($query_count, $bindings);

        $total = intval($stm->fetchOne());

        $limit = $paging_info->getPerPage();
        $offset = $paging_info->getOffset();

        $query = <<<SQL
        SELECT DISTINCT(Company) AS company
        {$query_from}
        {$extra_filters}
        {$extra_orders} LIMIT {$limit} OFFSET {$offset};
SQL;

        $res = $this->getEntityManager()->getConnection()->executeQuery($query, $bindings);

        return new PagingResponse
        (
            $total,
            $paging_info->getPerPage(),
            $paging_info->getCurrentPage(),
            $paging_info->getLastPage($total),
            $res->fetchAllAssociative(),
        );
    }
}