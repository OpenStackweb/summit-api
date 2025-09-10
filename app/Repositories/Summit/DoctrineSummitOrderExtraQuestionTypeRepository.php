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
use App\Models\Foundation\Summit\Repositories\ISummitOrderExtraQuestionTypeRepository;
use App\Repositories\Main\DoctrineExtraQuestionTypeRepository;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use Illuminate\Support\Facades\Log;
use models\summit\SummitAttendee;
use models\summit\SummitOrderExtraQuestionType;
use models\summit\SummitOrderExtraQuestionTypeConstants;
use utils\DoctrineLeftJoinFilterMapping;
use utils\Filter;
use utils\Order;
use utils\PagingInfo;
use utils\PagingResponse;

/**
 * Class DoctrineSummitOrderExtraQuestionTypeRepository
 * @package App\Repositories\Summit
 */
final class DoctrineSummitOrderExtraQuestionTypeRepository
    extends DoctrineExtraQuestionTypeRepository
    implements ISummitOrderExtraQuestionTypeRepository
{

    /**
     * @return array
     */
    protected function getFilterMappings()
    {
        return array_merge(parent::getFilterMappings() , [
            'printable' => 'e.printable:json_boolean',
            'usage'     => 'e.usage:json_string',
            'summit_id' => new DoctrineLeftJoinFilterMapping("e.summit", "s" ,"s.id :operator :value"),
            'allowed_badge_feature_type_id' => new DoctrineLeftJoinFilterMapping("e.allowed_badge_features_types", "bft" ,"bft.id :operator :value"),
            'allowed_ticket_type_id' => new DoctrineLeftJoinFilterMapping("e.allowed_ticket_types", "tt" ,"tt.id :operator :value")
        ]);
    }

    /**
     * @return array
     */
    protected function getOrderMappings()
    {
        return parent::getOrderMappings();
    }

    /**
     *
     * @return string
     */
    protected function getBaseEntity()
    {
        return SummitOrderExtraQuestionType::class;
    }

    /**
     * @param PagingInfo $paging_info
     * @param Filter|null $filter
     * @param Order|null $order
     * @return PagingResponse
     */
    public function getAllByPage(PagingInfo $paging_info, Filter $filter = null, Order $order = null): PagingResponse
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
                return $query;
            });
    }

    public function getAllAllowedMainQuestionByAttendee  (
        SummitAttendee $attendee, PagingInfo $paging_info, Filter $filter = null, Order $order = null
    ): PagingResponse{
        $page = $this->getAllIdsAllowedMainQuestionByAttendee($attendee, $paging_info, $filter, $order);
        $total = $page->getTotal();

        $data = $this->getEntityManager()->createQueryBuilder()
            ->select("e, FIELD(e.id, :ids) AS HIDDEN ids")
            ->from($this->getBaseEntity(), "e")
            ->where("e.id in (:ids)")
            ->orderBy("ids")
            ->setParameter("ids", $page->getItems())
            ->getQuery()
            ->getResult();

        return new PagingResponse
        (
            $total,
            $paging_info->getPerPage(),
            $paging_info->getCurrentPage(),
            $paging_info->getLastPage($total),
            $data
        );
    }

    public function getAllIdsAllowedMainQuestionByAttendee  (
        SummitAttendee $attendee, PagingInfo $paging_info, Filter $filter = null, Order $order = null
    ): PagingResponse{

        $ticket_types = [];
        $badge_features = [];
        $exclude_inactive_tickets = true;

        if($filter->hasFilter('tickets_exclude_inactives')){
            $exclude_inactive_tickets = $filter->getUniqueFilter('tickets_exclude_inactives')->getBooleanValue();
        }

        foreach ($attendee->getAllowedTicketTypes($exclude_inactive_tickets) as $ticket_type) {
            $ticket_types[] = $ticket_type->getId();
        }

        foreach ($attendee->getAllowedBadgeFeatures($exclude_inactive_tickets) as $badge_feature) {
            $badge_features[] = $badge_feature->getId();
        }

        $bindings = [
            'summit_id' => $attendee->getSummitId(),
            'usage' =>  SummitOrderExtraQuestionTypeConstants::TicketQuestionUsage,
        ];

        $types = [
            'summit_id'      => ParameterType::INTEGER,
            'usage'          => ParameterType::STRING,
        ];

        $filter_restriction = "";

        if(count($ticket_types) > 0){
            $bindings['ticket_types'] = $ticket_types;
            $types['ticket_types']  = ArrayParameterType::INTEGER;
            $filter_restriction = <<<SQL
      -- matches attendee ticket type
      OR EXISTS (
          SELECT 1
          FROM SummitOrderExtraQuestionType_SummitTicketType tt2
          WHERE tt2.SummitOrderExtraQuestionTypeID = s.ID
            AND tt2.SummitTicketTypeID IN (:ticket_types)
      )
SQL;
        }

        if(count($badge_features) > 0){
            $bindings['badge_features'] = $badge_features;
            $types['badge_features']  = ArrayParameterType::INTEGER;
            $filter_restriction .= <<<SQL
      -- matches attendee badge feature
      OR EXISTS (
          SELECT 1
          FROM SummitOrderExtraQuestionType_SummitBadgeFeatureType bf2
          WHERE bf2.SummitOrderExtraQuestionTypeID = s.ID
            AND bf2.SummitBadgeFeatureTypeID IN (:badge_features)
      )
SQL;
        }

        $extra_orders = <<<SQL
ORDER BY e.ID ASC
SQL;

        $extra_filters = <<<SQL
WHERE s.SummitID = :summit_id
  AND s.Usage    = :usage
 -- MAIN QUESTIONS CONDITION ( DOES NOT HAS SUBRULES)
  AND NOT EXISTS (
      SELECT 1
      FROM SubQuestionRule r
      WHERE r.SubQuestionID = e.ID
  )
AND (
      --  (o restrictions on either list
      (
          NOT EXISTS (
            SELECT 1 FROM SummitOrderExtraQuestionType_SummitBadgeFeatureType bf
            WHERE bf.SummitOrderExtraQuestionTypeID = s.ID
          ) AND 
          NOT EXISTS (
            SELECT 1 FROM SummitOrderExtraQuestionType_SummitTicketType tt
            WHERE tt.SummitOrderExtraQuestionTypeID = s.ID
          )
     )
     $filter_restriction
)
SQL;

        $query_from = <<<SQL
FROM SummitOrderExtraQuestionType s
JOIN ExtraQuestionType e ON e.ID = s.ID
SQL;

        $query_count = <<<SQL
SELECT COUNT(DISTINCT(e.ID)) AS QTY
{$query_from}
{$extra_filters}
SQL;


        $stm = $this->getEntityManager()->getConnection()->executeQuery($query_count, $bindings, $types);

        $total = intval($stm->fetchOne());

        $limit = $paging_info->getPerPage();
        $offset = $paging_info->getOffset();


        $query = <<<SQL
SELECT DISTINCT(e.ID)
{$query_from}
{$extra_filters} 
{$extra_orders} LIMIT {$limit} OFFSET {$offset};
SQL;

        $res = $this->getEntityManager()->getConnection()->executeQuery($query, $bindings, $types);

        $ids = $res->fetchFirstColumn();

        return new PagingResponse
        (
            $total,
            $paging_info->getPerPage(),
            $paging_info->getCurrentPage(),
            $paging_info->getLastPage($total),
            $ids
        );
    }

    /**
     * @inheritDoc
     */
    public function getAllAllowedByPage
    (
        SummitAttendee $attendee, PagingInfo $paging_info, Filter $filter = null, Order $order = null
    ): PagingResponse
    {
        Log::debug(sprintf("DoctrineSummitOrderExtraQuestionTypeRepository::getAllAllowedByPage attendee_id %s", $attendee->getId()));

        $attendee_ticket_type_ids = [];

        $exclude_inactive_tickets = true;
        if($filter->hasFilter('tickets_exclude_inactives')){
            $exclude_inactive_tickets = $filter->getUniqueFilter('tickets_exclude_inactives')->getBooleanValue();
            Log::debug
            (
                sprintf
                (
                    "DoctrineSummitOrderExtraQuestionTypeRepository::getAllAllowedByPage exclude_inactive_tickets %b",
                    $exclude_inactive_tickets
                )
            );
        }

        foreach ($attendee->getAllowedTicketTypes($exclude_inactive_tickets) as $ticket_type) {
            $attendee_ticket_type_ids[] = $ticket_type->getId();
        }

        $attendee_badge_feature_type_ids = [];
        foreach ($attendee->getAllowedBadgeFeatures($exclude_inactive_tickets) as $badge_feature) {
            $attendee_badge_feature_type_ids[] = $badge_feature->getId();
        }

        Log::debug(sprintf("DoctrineSummitOrderExtraQuestionTypeRepository::getAllAllowedByPage attendee_ticket_type_ids %s", implode(',', $attendee_ticket_type_ids)));
        Log::debug(sprintf("DoctrineSummitOrderExtraQuestionTypeRepository::getAllAllowedByPage attendee_badge_feature_type_ids %s", implode(',', $attendee_badge_feature_type_ids)));

        return $this->getParametrizedAllByPage(function () use ($attendee_badge_feature_type_ids, $attendee_ticket_type_ids) {
            $qb = $this->getEntityManager()->createQueryBuilder()
                ->select("e")
                ->from($this->getBaseEntity(), "e")
                ->leftJoin('e.allowed_ticket_types', 'att')
                ->leftJoin('e.allowed_badge_features_types', 'aft')
                ->where("(SIZE(e.allowed_badge_features_types) = 0 AND SIZE(e.allowed_ticket_types) = 0)");

            if (count($attendee_ticket_type_ids) > 0) {
                $qb->orWhere('att.id IN ('.implode(',', $attendee_ticket_type_ids).')');
            }
            if (count($attendee_badge_feature_type_ids) > 0) {
                $qb->orWhere('aft.id IN ('.implode(',', $attendee_badge_feature_type_ids).')');
            }
            return $qb;
        },
            $paging_info,
            $filter,
            $order,
            function ($query) {
                //default order
                return $query;
            });
    }
}