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

use App\Models\Foundation\Main\IGroup;
use App\Models\Foundation\Summit\IPublishableEvent;
use App\Repositories\SilverStripeDoctrineRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use models\main\Tag;
use models\summit\ISummitCategoryChangeStatus;
use models\summit\ISummitEventRepository;
use models\summit\Presentation;
use models\summit\PresentationType;
use models\summit\Summit;
use models\summit\SummitEvent;
use models\summit\SummitGroupEvent;
use models\summit\SummitSelectedPresentation;
use models\summit\SummitSelectedPresentationList;
use models\utils\SilverstripeBaseModel;
use utils\DoctrineCaseFilterMapping;
use utils\DoctrineCollectionFieldsFilterMapping;
use utils\DoctrineFilterMapping;
use utils\DoctrineHavingFilterMapping;
use utils\DoctrineInstanceOfFilterMapping;
use utils\DoctrineJoinFilterMapping;
use utils\DoctrineLeftJoinFilterMapping;
use utils\DoctrineSwitchFilterMapping;
use utils\Filter;
use utils\Order;
use utils\PagingInfo;
use utils\PagingResponse;

/**
 * Class DoctrineSummitEventRepository
 * @package App\Repositories\Summit
 */
final class DoctrineSummitEventRepository
    extends SilverStripeDoctrineRepository
    implements ISummitEventRepository
{

    private static $forbidden_classes = [
        SummitGroupEvent::ClassName,
    ];

    /**
     * @param IPublishableEvent $event
     * @return IPublishableEvent[]
     */
    public function getPublishedOnSameTimeFrame(IPublishableEvent $event): array
    {
        $summit = $event->getSummit();
        $end_date = $event->getEndDate();
        $start_date = $event->getStartDate();

        $query = $this->getEntityManager()->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e")
            ->join('e.summit', 's', Join::WITH, " s.id = :summit_id")
            ->where('e.published = 1')
            ->andWhere('e.start_date < :end_date')
            ->andWhere('e.end_date > :start_date')
            ->setParameter('summit_id', $summit->getId())
            ->setParameter('start_date', $start_date)
            ->setParameter('end_date', $end_date);

        $idx = 1;
        foreach (self::$forbidden_classes as $forbidden_class) {
            $query = $query
                ->andWhere("not e INSTANCE OF :forbidden_class" . $idx);
            $query->setParameter("forbidden_class" . $idx, $forbidden_class);
            $idx++;
        }

        return $query->getQuery()->getResult();
    }

    /**
     * @return string
     */
    protected function getBaseEntity()
    {
        return SummitEvent::class;
    }

    /**
     * @param PagingInfo $paging_info
     * @param Filter|null $filter
     * @param Order|null $order
     * @return PagingResponse
     */
    public function getAllByPage(PagingInfo $paging_info, Filter $filter = null, Order $order = null)
    {

        $current_track_id = 0;
        $current_member_id = 0;

        if (!is_null($filter)) {
            Log::debug(sprintf("DoctrineSummitEventRepository::getAllByPage filter %s", $filter));
            // check for dependant filtering
            $track_id_filter = $filter->getUniqueFilter('track_id');
            if (!is_null($track_id_filter)) {
                $current_track_id = intval($track_id_filter->getValue());
            }
            $current_member_id_filter = $filter->getUniqueFilter('current_member_id');
            if (!is_null($current_member_id_filter)) {
                $current_member_id = intval($current_member_id_filter->getValue());
            }
        }

        $query = $this->getEntityManager()->createQueryBuilder()
            ->distinct("e")
            ->select("e")
            ->from($this->getBaseEntity(), "e")
            ->leftJoin(Presentation::class, 'p', 'WITH', 'e.id = p.id');

        if (is_null($order) || !$order->hasOrder("votes_count")) {
            $query = $query
                ->leftJoin("e.location", 'l', Join::LEFT_JOIN)
                ->leftJoin("e.created_by", 'cb', Join::LEFT_JOIN)
                ->leftJoin("e.sponsors", "sprs", Join::LEFT_JOIN)
                ->leftJoin("p.speakers", "sp_presentation", Join::LEFT_JOIN)
                ->leftJoin("sp_presentation.speaker", "sp", Join::LEFT_JOIN)
                ->leftJoin('p.selected_presentations', "ssp", Join::LEFT_JOIN)
                ->leftJoin('ssp.member', "ssp_member", Join::LEFT_JOIN)
                ->leftJoin('p.selection_plan', "selp", Join::LEFT_JOIN)
                ->leftJoin('ssp.list', "sspl", Join::LEFT_JOIN)
                ->leftJoin('p.moderator', "spm", Join::LEFT_JOIN)
                ->leftJoin('spm.member', "spmm2", Join::LEFT_JOIN)
                ->leftJoin('sp.member', "spmm", Join::LEFT_JOIN)
                ->leftJoin('sp.registration_request', "sprr", Join::LEFT_JOIN)
                ->leftJoin('spm.registration_request', "sprr2", Join::LEFT_JOIN);
        }

        $query = $this->applyExtraJoins($query, $filter);

        if (!is_null($filter)) {
            $filter->apply2Query($query, $this->getCustomFilterMappings($current_member_id, $current_track_id));
        }

        $shouldPerformRandomOrderingByPage = false;
        if (!is_null($order)) {
            if ($order->hasOrder("page_random")) {
                $shouldPerformRandomOrderingByPage = true;
                $order->removeOrder("page_random");
            }
            $order->apply2Query($query, $this->getOrderMappings());
            if (!$order->hasOrder('id')) {
                $query = $query->addOrderBy("e.id", 'ASC');
            }
        } else {
            //default order
            $query = $query->addOrderBy("e.start_date", 'ASC');
            $query = $query->addOrderBy("e.end_date", 'ASC');
            $query = $query->addOrderBy("e.id", 'ASC');
        }

        $can_view_private_events = self::isCurrentMemberOnGroup(IGroup::SummitAdministrators);

        if (!$can_view_private_events) {
            $idx = 1;
            foreach (self::$forbidden_classes as $forbidden_class) {
                $query = $query
                    ->andWhere("not e INSTANCE OF :forbidden_class" . $idx);
                $query->setParameter("forbidden_class" . $idx, $forbidden_class);
                $idx++;
            }
        }

        $query = $query
            ->setFirstResult($paging_info->getOffset())
            ->setMaxResults($paging_info->getPerPage());

        $paginator = new Paginator($query, $fetchJoinCollection = true);
        $total = $paginator->count();
        $data = [];

        foreach ($paginator as $entity)
            $data[] = $entity;

        if ($shouldPerformRandomOrderingByPage)
            shuffle($data);

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
     * @param QueryBuilder $query
     * @return QueryBuilder
     */
    protected function applyExtraJoins(QueryBuilder $query, ?Filter $filter = null)
    {
        $query = $query->innerJoin("e.type", "et", Join::ON);
        $query = $query->leftJoin(PresentationType::class, 'et2', 'WITH', 'et.id = et2.id');
        // if we delete the track, its set to null
        $query = $query->leftJoin("e.category", "c", Join::ON);
        $query = $query->leftJoin("p.attendees_votes", 'av', Join::ON);
        $query = $query->leftJoin("e.tags", "t", Join::ON);
        return $query;
    }

    /**
     * @param int $current_member_id
     * @param int $current_track_id
     * @return array
     */
    protected function getCustomFilterMappings(int $current_member_id, int $current_track_id)
    {
        return [
            'id' => 'e.id:json_int',
            'title' => 'e.title:json_string',
            'streaming_url' => 'e.streaming_url:json_string',
            'streaming_type' => 'e.streaming_type:json_string',
            'meeting_url' => 'e.meeting_url:json_string',
            'etherpad_link' => 'e.etherpad_link:json_string',
            'abstract' => 'e.abstract:json_string',
            'level' => 'e.level:json_string',
            'status' => 'p.status:json_string',
            'progress' => 'p.progress:json_int',
            'is_chair_visible' => "c.chair_visible :operator :value",
            'is_voting_visible' => "c.voting_visible :operator :value",
            'social_summary' => 'e.social_summary:json_string',
            'published' => 'e.published',
            'type_allows_publishing_dates' => 'et.allows_publishing_dates',
            'type_allows_location' => 'et.allows_location',
            'type_allows_attendee_vote' => 'et2.allow_attendee_vote',
            'type_allows_custom_ordering' => 'et2.allow_custom_ordering',
            'start_date' => 'e.start_date:datetime_epoch',
            'end_date' => 'e.end_date:datetime_epoch',
            'created' => 'e.created:datetime_epoch',
            'last_edited' => 'e.last_edited:datetime_epoch',
            'tags' => "t.tag",
            'summit_id' => new DoctrineJoinFilterMapping
            (
                'e.summit',
                's',
                "s.id  :operator :value"
            ),
            'event_type_id' => "et.id :operator :value",
            'track_id' => "c.id :operator :value",
            'track_group_id' => new DoctrineJoinFilterMapping
            (
                'c.groups',
                'cg',
                "cg.id :operator :value"
            ),
            'selection_plan_id' => new DoctrineFilterMapping
            (
                "(selp.id :operator :value)"
            ),
            'location_id' => new DoctrineLeftJoinFilterMapping
            (
                'e.location',
                'l',
                "l.id :operator :value"
            ),
            'speaker' => new DoctrineFilterMapping
            (
                "( concat(sp.first_name, ' ', sp.last_name) :operator :value " .
                "OR concat(spm.first_name, ' ', spm.last_name) :operator :value " .
                "OR concat(spmm.first_name, ' ', spmm.last_name) :operator :value " .
                "OR sp.first_name :operator :value " .
                "OR sp.last_name :operator :value " .
                "OR spm.first_name :operator :value " .
                "OR spm.last_name :operator :value " .
                "OR spmm.first_name :operator :value " .
                "OR spmm.last_name :operator :value) "
            ),
            'speaker_email' => new DoctrineFilterMapping
            (
                "(sprr.email :operator :value OR spmm.email :operator :value OR spmm2.email :operator :value OR sprr2.email :operator :value)"
            ),
            'speaker_title' => new DoctrineFilterMapping
            (
                "(sp.title :operator :value OR spm.title :operator :value)"
            ),
            'speaker_company' => new DoctrineFilterMapping
            (
                "(sp.company :operator :value OR spm.company :operator :value)"
            ),
            'speaker_id' => new DoctrineFilterMapping
            (
                "(sp.id :operator :value OR spm.id :operator :value)"
            ),
            'sponsor_id' => new DoctrineFilterMapping
            (
                "(sprs.id :operator :value)"
            ),
            'sponsor' => new DoctrineFilterMapping
            (
                "(sprs.name :operator :value)"
            ),
            'selection_status' => new DoctrineSwitchFilterMapping([
                    'selected' => new DoctrineCaseFilterMapping(
                        'selected',
                        "ssp.order is not null and sspl.list_type = 'Group' and sspl.category = e.category"
                    ),
                    'accepted' => new DoctrineCaseFilterMapping(
                        'accepted',
                        "(ssp.order is not null and ssp.order <= c.session_count and sspl.list_type = 'Group' and sspl.list_class = 'Session' and sspl.category = e.category) OR e.published = 1"
                    ),
                    'rejected' => new DoctrineCaseFilterMapping(
                        'rejected',
                        sprintf('e.published = 0 AND NOT EXISTS (
                                            SELECT ___sp31.id 
                                            FROM models\summit\SummitSelectedPresentation ___sp31
                                            JOIN ___sp31.presentation ___p31
                                            JOIN ___sp31.list ___spl31 WITH ___spl31.list_type = \'%2$s\' AND ___spl31.list_class = \'%3$s\'
                                            WHERE ___p31.id = e.id 
                                            AND ___sp31.collection = \'%1$s\'
                                        )',
                            SummitSelectedPresentation::CollectionSelected,
                            SummitSelectedPresentationList::Group,
                            SummitSelectedPresentationList::Session
                        )
                    ),
                    'alternate' => new DoctrineCaseFilterMapping(
                        'alternate',
                        "ssp.order is not null and ssp.order > c.session_count and sspl.list_type = 'Group' and sspl.list_class = 'Session' and sspl.category = e.category"
                    ),
                    'lightning-accepted' => new DoctrineCaseFilterMapping(
                        'lightning-accepted',
                        "ssp.order is not null and ssp.order <= c.lightning_count and sspl.list_type = 'Group' and sspl.list_class = 'Lightning' and sspl.category = e.category"
                    ),
                    'lightning-alternate' => new DoctrineCaseFilterMapping(
                        'lightning-alternate',
                        "ssp.order is not null and ssp.order > c.lightning_count and sspl.list_type = 'Group' and sspl.list_class = 'Lightning' and sspl.category = e.category"
                    ),
                ]
            ),
            'track_chairs_status' => new DoctrineSwitchFilterMapping
            (
                [
                    'voted' => new DoctrineCaseFilterMapping(
                        'voted',
                        "exists (select ssp1 from models\summit\SummitSelectedPresentation ssp1 inner join ssp1.presentation p1 where p1.id = p.id)"
                    ),
                    'untouched' => new DoctrineCaseFilterMapping(
                        'untouched',
                        "not exists (select ssp1 from models\summit\SummitSelectedPresentation ssp1 inner join ssp1.presentation p1 where p1.id = p.id)"
                    ),
                    'team_selected' => new DoctrineCaseFilterMapping(
                        'team_selected',
                        "sspl.list_type = 'Group' and sspl.list_class = 'Session' and ssp.collection= 'selected'"
                    ),
                    'selected' => new DoctrineCaseFilterMapping(
                        'selected',
                        "sspl.list_type = 'Individual' and sspl.list_class = 'Session' and ssp.collection = 'selected' and ssp_member.id = " . $current_member_id
                    ),
                    'maybe' => new DoctrineCaseFilterMapping(
                        'maybe',
                        "sspl.list_type = 'Individual' and sspl.list_class = 'Session' and ssp.collection = 'maybe' and ssp_member.id = " . $current_member_id
                    ),
                    'pass' => new DoctrineCaseFilterMapping(
                        'selected',
                        "sspl.list_type = 'Individual' and sspl.list_class = 'Session' and ssp.collection = 'pass' and ssp_member.id = " . $current_member_id
                    ),
                ]
            ),
            'viewed_status' => new DoctrineSwitchFilterMapping
            (
                [
                    'seen' => new DoctrineCaseFilterMapping(
                        'seen',
                        sprintf("exists (select vw1 from models\summit\PresentationTrackChairView vw1 inner join vw1.presentation p1 join vw1.viewer v1 where p1.id = p.id and v1.id = %s)", $current_member_id)
                    ),
                    'unseen' => new DoctrineCaseFilterMapping(
                        'unseen',
                        sprintf("not exists (select vw1 from models\summit\PresentationTrackChairView vw1 inner join vw1.presentation p1 join vw1.viewer v1 where p1.id = p.id and v1.id = %s)", $current_member_id)
                    ),
                    'moved' => new DoctrineCaseFilterMapping(
                        'moved',
                        sprintf
                        (
                            "not exists 
                            (
                                select vw1 from models\summit\PresentationTrackChairView vw1 
                                inner join vw1.presentation p1 join vw1.viewer v1 where p1.id = p.id and v1.id = %s
                            ) 
                            and exists 
                            ( 
                                select cch from models\summit\SummitCategoryChange cch 
                                inner join cch.presentation p2
                                inner join cch.new_category nc 
                                where p2.id = p.id and 
                                cch.status = %s and
                                nc.id = %s
                            ) ",
                            $current_member_id,
                            ISummitCategoryChangeStatus::Approved,
                            $current_track_id
                        )
                    ),
                ]
            ),
            'actions' => new DoctrineCollectionFieldsFilterMapping
            (
                'p.actions',
                "a",
                [
                    "type" => 'at',
                ],
                [
                    'type_id' => 'at.id',
                    'is_completed' => 'a.is_completed'
                ]
            ),
            'created_by_fullname' => new DoctrineFilterMapping
            (
                "concat(cb.first_name, ' ', cb.last_name) :operator :value "
            ),
            'created_by_email' => 'cb.email',
            'created_by_company' => 'cb.company',
            'class_name' => new DoctrineInstanceOfFilterMapping(
                "e",
                [
                    SummitEvent::ClassName => SummitEvent::class,
                    Presentation::ClassName => Presentation::class,
                ]
            ),
            'presentation_attendee_vote_date' => 'av.created:datetime_epoch|' . SilverstripeBaseModel::DefaultTimeZone,
            'votes_count' => new DoctrineHavingFilterMapping("", "av.presentation", "count(av.id) :operator :value"),
            'duration' => new DoctrineFilterMapping
            (
                "( ( (e.start_date IS NULL OR e.end_date IS NULL ) AND e.duration :operator :value ) OR TIMESTAMPDIFF(SECOND, e.start_date, e.end_date) :operator :value)"
            ),
            'speakers_count' => "SIZE(p.speakers) :operator :value"
        ];
    }

    /**
     * @return array
     */
    protected function getOrderMappings()
    {
        return [
            'id' => 'e.id',
            'title' => 'e.title',
            'start_date' => 'e.start_date',
            'end_date' => 'e.end_date',
            'created' => 'e.created',
            'track' => 'c.title',
            'trackchairsel' => 'ssp.order',
            'last_edited' => 'e.last_edited',
            'page_random' => 'RAND()',
            'random' => 'RAND()',
            'custom_order' => 'p.custom_order',
            'votes_count' => 'COUNT(av.id)',
            'duration' => <<<SQL
CASE WHEN e.start_date is NULL OR e.end_date IS NULL THEN e.duration
ELSE TIMESTAMPDIFF(SECOND, e.start_date, e.end_date) END
SQL,
            'speakers_count' => 'COUNT(DISTINCT(sp.id))',
            'created_by_fullname' => "concat(cb.first_name, ' ', cb.last_name)",
            'created_by_email' => 'cb.email',
            'sponsor' => 'sprs.name',
            'created_by_company' => 'cb.company',
            'speaker_company' => "sp.company",
            'level' => <<<SQL
COALESCE(LOWER(e.level), 'N/A') 
SQL,
            'etherpad_link' => <<<SQL
COALESCE(LOWER(e.etherpad_link), 'N/A') 
SQL,
            'streaming_url' => <<<SQL
COALESCE(LOWER(e.streaming_url), 'N/A')
SQL,
            'streaming_type' => <<<SQL
COALESCE(LOWER(e.streaming_type), 'N/A')
SQL,
            'meeting_url' => <<<SQL
COALESCE(LOWER(e.meeting_url), 'N/A')
SQL,
            'location' => <<<SQL
COALESCE(LOWER(l.name), 'N/A')
SQL,
            'event_type' => 'et.type',
            'tags' => <<<SQL
    LOWER(t.tag)
SQL,
            'published_date' => 'e.published_date',
            'is_published' => 'e.is_published',

            'selection_status' => <<<SQL
CASE 

    WHEN (ssp.order is not null and ssp.order <= c.session_count and sspl.list_type = 'Group' and sspl.list_class = 'Session' and sspl.category = e.category) OR e.published = 1 THEN 'accepted'
    WHEN e.published = 0 AND NOT EXISTS (
                                            SELECT ___sp31.id 
                                            FROM models\summit\SummitSelectedPresentation ___sp31
                                            JOIN ___sp31.presentation ___p31
                                            JOIN ___sp31.list ___spl31 WITH ___spl31.list_type = 'Group' AND ___spl31.list_class = 'Session'
                                            WHERE ___p31.id = e.id 
                                            AND ___sp31.collection = 'selected'
                                        ) THEN 'rejected'
    WHEN ssp.order is not null and ssp.order > c.session_count and sspl.list_type = 'Group' and sspl.list_class = 'Session' and sspl.category = e.category THEN 'alternate'
    ELSE 'pending'
    END 
SQL,
            /*
            'event_type_capacity' => <<<SQL
SQL,
            'speakers' => <<<SQL
SQL,*/
        ];
    }

    /**
     * @param int $event_id
     */
    public function cleanupScheduleAndFavoritesForEvent(int $event_id): void
    {

        $query = "DELETE Member_Schedule FROM Member_Schedule WHERE SummitEventID = {$event_id};";
        $this->getEntityManager()->getConnection()->executeStatement($query);

        $query = "DELETE `Member_FavoriteSummitEvents` FROM `Member_FavoriteSummitEvents` WHERE SummitEventID = {$event_id};";
        $this->getEntityManager()->getConnection()->executeStatement($query);
    }

    /**
     * @param PagingInfo $paging_info
     * @param Filter|null $filter
     * @param Order|null $order
     * @return PagingResponse
     */
    public function getAllByPageLocationTBD(PagingInfo $paging_info, Filter $filter = null, Order $order = null)
    {

        $current_track_id = 0;
        $current_member_id = 0;

        if (!is_null($filter)) {
            // check for dependant filtering
            $track_id_filter = $filter->getUniqueFilter('track_id');
            if (!is_null($track_id_filter)) {
                $current_track_id = intval($track_id_filter->getValue());
            }
            $current_member_id_filter = $filter->getUniqueFilter('current_member_id');
            if (!is_null($current_member_id_filter)) {
                $current_member_id = intval($current_member_id_filter->getValue());
            }
        }

        $query = $this->getEntityManager()->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e")
            ->leftJoin(Presentation::class, 'p', 'WITH', 'e.id = p.id')
            ->leftJoin("e.location", 'l', Join::LEFT_JOIN)
            ->leftJoin("p.speakers", "sp_presentation", Join::LEFT_JOIN)
            ->leftJoin("sp_presentation.speaker", "sp", Join::LEFT_JOIN)
            ->leftJoin('p.selection_plan', "selp", Join::LEFT_JOIN)
            ->leftJoin('p.moderator', "spm", Join::LEFT_JOIN)
            ->leftJoin('sp.member', "spmm", Join::LEFT_JOIN)
            ->leftJoin('sp.registration_request', "sprr", Join::LEFT_JOIN)
            ->where("l.id is null or l.id = 0");

        if (!is_null($filter)) {
            $filter->apply2Query($query, $this->getCustomFilterMappings($current_member_id, $current_track_id));
        }

        if (!is_null($order)) {
            $order->apply2Query($query, $this->getOrderMappings());
        } else {
            //default order
            $query = $query->addOrderBy("e.start_date", 'ASC');
            $query = $query->addOrderBy("e.end_date", 'ASC');
        }

        $can_view_private_events = self::isCurrentMemberOnGroup(IGroup::SummitAdministrators);

        if (!$can_view_private_events) {
            $idx = 1;
            foreach (self::$forbidden_classes as $forbidden_class) {
                $query = $query
                    ->andWhere("not e INSTANCE OF :forbidden_class" . $idx);
                $query->setParameter("forbidden_class" . $idx, $forbidden_class);
                $idx++;
            }
        }

        $query = $query
            ->setFirstResult($paging_info->getOffset())
            ->setMaxResults($paging_info->getPerPage());

        $paginator = new Paginator($query, $fetchJoinCollection = true);
        $total = $paginator->count();
        $data = [];

        foreach ($paginator as $entity)
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

    /**
     * @param Summit $summit ,
     * @param array $external_ids
     * @return mixed
     */
    public function getPublishedEventsBySummitNotInExternalIds(Summit $summit, array $external_ids)
    {
        $query = $this->getEntityManager()->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e")
            ->join('e.summit', 's', Join::WITH, " s.id = :summit_id")
            ->join('e.summit', 's', Join::WITH, " s.id = :summit_id")
            ->where('e.published = 1')
            ->andWhere('e.external_id not in (:external_ids)')
            ->setParameter('summit_id', $summit->getId())
            ->setParameter('external_ids', $external_ids);

        return $query->getQuery()->getResult();
    }

    /**
     * @param int $summit_id ,
     * @return array
     */
    public function getPublishedEventsIdsBySummit(int $summit_id): array
    {
        $query = $this->getEntityManager()
            ->createQueryBuilder()
            ->select("e.id")
            ->from($this->getBaseEntity(), "e")
            ->join('e.summit', 's', Join::WITH, " s.id = :summit_id")
            ->where('e.published = 1')
            ->setParameter('summit_id', $summit_id);

        $res = $query->getQuery()->getArrayResult();
        return array_column($res, 'id');
    }

    /**
     * @param PagingInfo $paging_info
     * @param Filter|null $filter
     * @param Order|null $order
     * @return PagingResponse
     */
    public function getAllPublishedTagsByPage(PagingInfo $paging_info, Filter $filter = null, Order $order = null): PagingResponse
    {

        $query = $this->getEntityManager()->createQueryBuilder()
            ->select("distinct t")
            ->from(Tag::class, "t")
            ->join("t.events", 'e')
            ->leftJoin(Presentation::class, 'p', 'WITH', 'e.id = p.id');

        if (!is_null($filter)) {
            $filter->apply2Query($query, [
                'tag' => 't.tag:json_string',
                'summit_id' => new DoctrineJoinFilterMapping
                (
                    'e.summit',
                    's',
                    "s.id  :operator :value"
                ),
            ]);
        }

        if (!is_null($order)) {
            $order->apply2Query($query, [
                'tag' => 't.tag',
            ]);
        } else {
            $query = $query->addOrderBy("t.tag", 'ASC');
        }

        $query = $query
            ->setFirstResult($paging_info->getOffset())
            ->setMaxResults($paging_info->getPerPage());

        $paginator = new Paginator($query, $fetchJoinCollection = true);
        $total = $paginator->count();
        $data = [];

        foreach ($paginator as $entity)
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