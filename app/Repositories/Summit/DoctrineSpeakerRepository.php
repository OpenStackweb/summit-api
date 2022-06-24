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

use App\Repositories\SilverStripeDoctrineRepository;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Illuminate\Support\Facades\Log;
use models\main\Member;
use models\summit\ISpeakerRepository;
use models\summit\PresentationSpeaker;
use models\summit\Summit;
use models\summit\SummitSelectedPresentation;
use models\summit\SummitSelectedPresentationList;
use utils\DoctrineCaseFilterMapping;
use utils\DoctrineSwitchFilterMapping;
use utils\Filter;
use utils\Order;
use utils\PagingInfo;
use utils\PagingResponse;

/**
 * Class DoctrineSpeakerRepository
 * @package App\Repositories\Summit
 */
final class DoctrineSpeakerRepository
    extends SilverStripeDoctrineRepository
    implements ISpeakerRepository
{

    /**
     * @return array
     */
    protected function getFilterMappings()
    {
        return [
            'last_name' => [
                "m.last_name :operator :value",
                "e.last_name :operator :value"
            ],
            'full_name' => [
                "concat(m.first_name, ' ', m.last_name) :operator :value",
                "concat(e.first_name, ' ', e.last_name) :operator :value"
            ],
            'first_name' => [
                "m.first_name :operator :value",
                "e.first_name :operator :value"
            ],
            'email' => [
                "m.email :operator :value",
                "rr.email :operator :value"
            ],
            'id' => 'e.id',
            'presentations_track_id' => [
                'EXISTS ( 
                              SELECT __p41.id FROM models\summit\Presentation __p41 
                              JOIN __p41.speakers __spk41 WITH __spk41.id = e.id 
                              JOIN __p41.category __tr41 
                              WHERE 
                              __p41.summit = :summit AND
                              __tr41.id :operator :value )',
                'EXISTS ( 
                              SELECT __p42.id FROM models\summit\Presentation __p42 
                              JOIN __p42.moderator __md42 WITH __md42.id = e.id 
                              JOIN __p42.category __tr42 
                              WHERE 
                              __p42.summit = :summit AND
                              __tr42.id :operator :value )',
            ],
            'presentations_selection_plan_id' => [
                'EXISTS ( 
                              SELECT __p51.id FROM models\summit\Presentation __p51 
                              JOIN __p51.speakers __spk51 WITH __spk51.id = e.id 
                              JOIN __p51.selection_plan __sel_plan51 
                              WHERE 
                              __p51.summit = :summit AND
                              __sel_plan51.id :operator :value )',
                'EXISTS ( 
                              SELECT __p52.id FROM models\summit\Presentation __p52 
                              JOIN __p52.moderator __md52 WITH __md52.id = e.id 
                              JOIN __p52.selection_plan __sel_plan52
                              WHERE 
                              __p52.summit = :summit AND
                              __sel_plan52.id :operator :value )',
            ],
            'presentations_type_id' => [

            ],
            'has_accepted_presentations' =>
                new DoctrineSwitchFilterMapping([
                        'true' => new DoctrineCaseFilterMapping(
                            'true',
                            sprintf('(
                                     EXISTS (
                                        SELECT __p11.id FROM models\summit\Presentation __p11 
                                        JOIN __p11.speakers __spk11 WITH __spk11.id = e.id 
                                        WHERE __p11.summit = :summit AND __p11.published = 1
                                     )
                                     OR
                                     EXISTS (
                                        SELECT __p12.id FROM models\summit\Presentation __p12 
                                        JOIN __p12.speakers __spk12 WITH __spk12.id = e.id 
                                        JOIN __p12.category __cat12
                                        JOIN __p12.selected_presentations __sp12 WITH __sp12.collection = \'%1$s\'
                                        JOIN __sp12.list __spl12 WITH __spl12.list_type = \'%2$s\' AND __spl12.list_class = \'%3$s\'
                                        WHERE 
                                        __p12.summit = :summit AND
                                        __sp12.order is not null AND
                                        __sp12.order <= __cat12.session_count
                                     )
                                     OR
                                     EXISTS (
                                        SELECT __p13.id FROM models\summit\Presentation __p13 
                                        JOIN __p13.moderator __md13 WITH __md13.id = e.id 
                                        WHERE __p13.summit = :summit AND __p13.published = 1
                                     )
                                     OR
                                     EXISTS (
                                        SELECT __p14.id FROM models\summit\Presentation __p14 
                                        JOIN __p14.moderator __md14 WITH __md14.id = e.id 
                                        JOIN __p14.category __cat14
                                        JOIN __p14.selected_presentations __sp14 WITH __sp14.collection = \'%1$s\'
                                        JOIN __sp14.list __spl14 WITH __spl14.list_type = \'%2$s\' AND __spl14.list_class = \'%3$s\'
                                        WHERE 
                                        __p14.summit = :summit AND
                                        __sp14.order is not null AND
                                        __sp14.order <= __cat14.session_count
                                     )
                                )',
                                SummitSelectedPresentation::CollectionSelected,
                                SummitSelectedPresentationList::Group,
                                SummitSelectedPresentationList::Session
                            )
                        ),
                        'false' => new DoctrineCaseFilterMapping(
                            'false',
                            sprintf('(
                                     NOT EXISTS (
                                        SELECT __p11.id FROM models\summit\Presentation __p11 
                                        JOIN __p11.speakers __spk11 WITH __spk11.id = e.id 
                                        WHERE __p11.summit = :summit AND __p11.published = 1
                                     )
                                     AND
                                     NOT EXISTS (
                                        SELECT __p12.id FROM models\summit\Presentation __p12 
                                        JOIN __p12.speakers __spk12 WITH __spk12.id = e.id 
                                        JOIN __p12.category __cat12
                                        JOIN __p12.selected_presentations __sp12 WITH __sp12.collection = \'%1$s\'
                                        JOIN __sp12.list __spl12 WITH __spl12.list_type = \'%2$s\' AND __spl12.list_class = \'%3$s\'
                                        WHERE 
                                        __p12.summit = :summit AND
                                        __sp12.order is not null AND
                                        __sp12.order <= __cat12.session_count
                                     )
                                     AND
                                     EXISTS (
                                        SELECT __p13.id FROM models\summit\Presentation __p13 
                                        JOIN __p13.moderator __md13 WITH __md13.id = e.id 
                                        WHERE __p13.summit = :summit AND __p13.published = 1
                                     )
                                     AND
                                     NOT EXISTS (
                                        SELECT __p14.id FROM models\summit\Presentation __p14 
                                        JOIN __p14.moderator __md14 WITH __md14.id = e.id 
                                        JOIN __p14.category __cat14
                                        JOIN __p14.selected_presentations __sp14 WITH __sp14.collection = \'%1$s\'
                                        JOIN __sp14.list __spl14 WITH __spl14.list_type = \'%2$s\' AND __spl14.list_class = \'%3$s\'
                                        WHERE 
                                        __p14.summit = :summit AND
                                        __sp14.order is not null AND
                                        __sp14.order <= __cat14.session_count
                                     )
                                )',
                                SummitSelectedPresentation::CollectionSelected,
                                SummitSelectedPresentationList::Group,
                                SummitSelectedPresentationList::Session
                            )
                        ),

                    ]
                ),
            'has_alternate_presentations' =>
                new DoctrineSwitchFilterMapping([
                        'true' => new DoctrineCaseFilterMapping(
                            'true',
                            sprintf('(
                                     EXISTS (
                                        SELECT __p21.id FROM models\summit\Presentation __p21 
                                        JOIN __p21.speakers __spk21 WITH __spk21.id = e.id 
                                        JOIN __p21.category __cat21
                                        JOIN __p21.selected_presentations __sp21 WITH __sp21.collection = \'%1$s\'
                                        JOIN __sp21.list __spl21 WITH __spl21.list_type = \'%2$s\' AND __spl21.list_class = \'%3$s\'
                                        WHERE 
                                        __p21.summit = :summit AND
                                        __sp21.order is not null AND
                                        __sp21.order > __cat21.session_count
                                     )
                                     OR
                                     EXISTS (
                                        SELECT __p22.id FROM models\summit\Presentation __p22 
                                        JOIN __p22.moderator __md22 WITH __md22.id = e.id 
                                        JOIN __p22.category __cat22
                                        JOIN __p22.selected_presentations __sp22 WITH __sp22.collection = \'%1$s\'
                                        JOIN __sp22.list __spl22 WITH __spl22.list_type = \'%2$s\' AND __spl22.list_class = \'%3$s\'
                                        WHERE 
                                        __p22.summit = :summit AND
                                        __sp22.order is not null AND
                                        __sp22.order > __cat22.session_count
                                     )
                                )',
                                SummitSelectedPresentation::CollectionSelected,
                                SummitSelectedPresentationList::Group,
                                SummitSelectedPresentationList::Session
                            )
                        ),
                        'false' => new DoctrineCaseFilterMapping(
                            'false',
                            sprintf('(
                                     NOT EXISTS (
                                        SELECT __p21.id FROM models\summit\Presentation __p21 
                                        JOIN __p21.speakers __spk21 WITH __spk21.id = e.id 
                                        JOIN __p21.category __cat21
                                        JOIN __p21.selected_presentations __sp21 WITH __sp21.collection = \'%1$s\'
                                        JOIN __sp21.list __spl21 WITH __spl21.list_type = \'%2$s\' AND __spl21.list_class = \'%3$s\'
                                        WHERE 
                                        __p21.summit = :summit AND
                                        __sp21.order is not null AND
                                        __sp21.order > __cat21.session_count
                                     )
                                     AND
                                     NOT EXISTS (
                                        SELECT __p22.id FROM models\summit\Presentation __p22 
                                        JOIN __p22.moderator __md22 WITH __md22.id = e.id 
                                        JOIN __p22.category __cat22
                                        JOIN __p22.selected_presentations __sp22 WITH __sp22.collection = \'%1$s\'
                                        JOIN __sp22.list __spl22 WITH __spl22.list_type = \'%2$s\' AND __spl22.list_class = \'%3$s\'
                                        WHERE 
                                        __p22.summit = :summit AND
                                        __sp22.order is not null AND
                                        __sp22.order > __cat22.session_count
                                     )
                                )',
                                SummitSelectedPresentation::CollectionSelected,
                                SummitSelectedPresentationList::Group,
                                SummitSelectedPresentationList::Session
                            )
                        ),
                    ]
                ),
            'has_rejected_presentations' =>
                new DoctrineSwitchFilterMapping([
                        'true' => new DoctrineCaseFilterMapping(
                            'true',
                            sprintf('(
                                     EXISTS (
                                        SELECT __p31.id FROM models\summit\Presentation __p31 
                                        JOIN __p31.speakers __spk31 WITH __spk31.id = e.id 
                                        WHERE 
                                        __p31.summit = :summit 
                                        AND __p31.published = 0
                                        AND NOT EXISTS (
                                            SELECT ___sp31.id 
                                            FROM models\summit\SummitSelectedPresentation ___sp31
                                            JOIN ___sp31.presentation ___p31
                                            JOIN ___sp31.list ___spl31 WITH ___spl31.list_type = \'%2$s\' AND ___spl31.list_class = \'%3$s\'
                                            WHERE ___p31.id = __p31.id AND ___sp31.collection = \'%1$s\'
                                        )
                                     )
                                     OR
                                     EXISTS (
                                        SELECT __p32.id FROM models\summit\Presentation __p32 
                                        JOIN __p32.moderator __md32 WITH __md32.id = e.id 
                                        WHERE 
                                        __p32.summit = :summit 
                                        AND __p32.published = 0
                                        AND NOT EXISTS  (
                                            SELECT ___sp32.id 
                                            FROM models\summit\SummitSelectedPresentation ___sp32 
                                            JOIN ___sp32.presentation ___p32
                                            JOIN ___sp32.list ___spl32 WITH ___spl32.list_type = \'%2$s\' AND ___spl32.list_class = \'%3$s\'
                                            WHERE ___p32.id = __p32.id AND ___sp32.collection = \'%1$s\'
                                        )
                                     )
                                )',
                                SummitSelectedPresentation::CollectionSelected,
                                SummitSelectedPresentationList::Group,
                                SummitSelectedPresentationList::Session
                            )
                        ),
                        'false' => new DoctrineCaseFilterMapping(
                            'false',
                            sprintf('(
                                     NOT EXISTS (
                                        SELECT __p31.id FROM models\summit\Presentation __p31 
                                        JOIN __p31.speakers __spk31 WITH __spk31.id = e.id 
                                        WHERE 
                                        __p31.summit = :summit  
                                        AND __p31.published = 0
                                        AND NOT EXISTS (
                                            SELECT ___sp31.id 
                                            FROM models\summit\SummitSelectedPresentation ___sp31
                                            JOIN ___sp31.presentation ___p31
                                            JOIN ___sp31.list ___spl31 WITH ___spl31.list_type = \'%2$s\' AND ___spl31.list_class = \'%3$s\'
                                            WHERE ___p31.id = __p31.id AND ___sp31.collection = \'%1$s\'
                                        )
                                     )
                                     AND
                                     NOT EXISTS (
                                        SELECT __p32.id FROM models\summit\Presentation __p32 
                                        JOIN __p32.moderator __md32 WITH __md32.id = e.id 
                                        WHERE 
                                        __p32.summit = :summit 
                                        AND __p32.published = 0
                                        AND NOT EXISTS  (
                                            SELECT ___sp32.id 
                                            FROM models\summit\SummitSelectedPresentation ___sp32 
                                            JOIN ___sp32.presentation ___p32
                                            JOIN ___sp32.list ___spl32 WITH ___spl32.list_type = \'%2$s\' AND ___spl32.list_class = \'%3$s\'
                                            WHERE ___p32.id = __p32.id AND ___sp32.collection = \'%1$s\'
                                        )
                                     )
                                )',
                                SummitSelectedPresentation::CollectionSelected,
                                SummitSelectedPresentationList::Group,
                                SummitSelectedPresentationList::Session
                            )
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
            "first_name" => <<<SQL
COALESCE(LOWER(m.first_name), LOWER(e.first_name)) 
SQL,
            "last_name" => <<<SQL
COALESCE(LOWER(m.last_name), LOWER(e.last_name)) 
SQL,
            "full_name" => <<<SQL
COALESCE(LOWER(CONCAT(m.first_name, ' ', m.last_name)), LOWER(CONCAT(e.first_name, ' ', e.last_name)))
SQL,
            'email' => <<<SQL
COALESCE(LOWER(m.email), LOWER(rr.email)) 
SQL,
        ];
    }

    /**
     * @param Summit $summit
     * @param PagingInfo $paging_info
     * @param Filter|null $filter
     * @param Order|null $order
     * @return PagingResponse
     * @throws \Doctrine\DBAL\Exception
     */
    public function getSpeakersBySummit(Summit $summit, PagingInfo $paging_info, Filter $filter = null, Order $order = null)
    {

        return $this->getParametrizedAllByPage(function () use ($summit) {
            return $this->getEntityManager()->createQueryBuilder()
                ->distinct("e")
                ->select("e")
                ->from($this->getBaseEntity(), "e")
                ->leftJoin("e.registration_request", "rr")
                ->leftJoin("e.member", "m")
                // we need to have SIZE(e.presentations) > 0 OR SIZE(e.moderated_presentations) > 0 for a particular summit
                ->where(" ( 
                         EXISTS (
                            SELECT __p.id FROM models\summit\Presentation __p JOIN __p.speakers __spk WITH __spk.id = e.id 
                            WHERE __p.summit = :summit
                         ) OR
                         EXISTS (
                            SELECT __p1.id FROM models\summit\Presentation __p1 JOIN __p1.moderator __md WITH __md.id = e.id 
                            WHERE __p1.summit = :summit
                         )) ")
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
     * @param Summit $summit
     * @param PagingInfo $paging_info
     * @param Filter|null $filter
     * @param Order|null $order
     * @return PagingResponse
     */
    public function getSpeakersBySummitAndOnSchedule(Summit $summit, PagingInfo $paging_info, Filter $filter = null, Order $order = null)
    {

        $extra_filters = '';
        $extra_events_filters = '';
        $extra_orders = '';
        $bindings = [];

        if (!is_null($filter)) {
            $where_conditions = $filter->toRawSQL([
                'full_name' => 'FullName',
                'first_name' => 'FirstName',
                'last_name' => 'LastName',
                'email' => 'Email',
                'id' => 'ID',
                'featured' => 'Featured'
            ]);

            if (!empty($where_conditions)) {
                $extra_filters = " WHERE {$where_conditions}";
                $bindings = array_merge($bindings, $filter->getSQLBindings());
            }

            $where_event_conditions = $filter->toRawSQL([
                'event_start_date' => 'E.StartDate:datetime_epoch',
                'event_end_date' => 'E.EndDate:datetime_epoch',
            ], count($bindings) + 1);

            if (!empty($where_event_conditions)) {
                $extra_events_filters = " AND {$where_event_conditions}";
                $bindings = array_merge($bindings, $filter->getSQLBindings());
            }
        }

        foreach ($bindings as $key => $value) {
            if ($value == 'true')
                $bindings[$key] = 1;
            if ($value == 'false')
                $bindings[$key] = 0;
        }

        if (!is_null($order)) {
            $extra_orders = $order->toRawSQL(array
            (
                'id' => 'ID',
                'email' => 'Email',
                'first_name' => 'FirstName',
                'last_name' => 'LastName',
                'full_name' => 'FullName',
            ));
        }

        $query_count = <<<SQL
SELECT COUNT(DISTINCT(ID)) AS QTY
FROM (
	SELECT S.ID,
	IFNULL(S.FirstName, M.FirstName) AS FirstName,
	IFNULL(S.LastName, M.Surname) AS LastName,
	CONCAT(IFNULL(S.FirstName, M.FirstName), ' ', IFNULL(S.LastName, M.Surname)) AS FullName,
	IFNULL(M.Email, R.Email) AS Email,
	EXISTS(SELECT 1 FROM Summit_FeaturedSpeakers WHERE Summit_FeaturedSpeakers.PresentationSpeakerID = S.ID AND Summit_FeaturedSpeakers.SummitID = {$summit->getId()}) AS Featured
	FROM PresentationSpeaker S
	LEFT JOIN Member M ON M.ID = S.MemberID
	LEFT JOIN SpeakerRegistrationRequest R ON R.SpeakerID = S.ID
	WHERE
	EXISTS
	(
		SELECT E.ID FROM SummitEvent E
		INNER JOIN Presentation P ON E.ID = P.ID
		INNER JOIN Presentation_Speakers PS ON PS.PresentationID = P.ID
		WHERE E.SummitID = {$summit->getId()} AND PS.PresentationSpeakerID = S.ID AND E.Published = 1 {$extra_events_filters}
	)
	UNION
	SELECT S.ID,
	IFNULL(S.FirstName, M.FirstName) AS FirstName,
	IFNULL(S.LastName, M.Surname) AS LastName,
	CONCAT(IFNULL(S.FirstName, M.FirstName), ' ', IFNULL(S.LastName, M.Surname)) AS FullName,
	IFNULL(M.Email, R.Email) AS Email,
	EXISTS(SELECT 1 FROM Summit_FeaturedSpeakers WHERE Summit_FeaturedSpeakers.PresentationSpeakerID = S.ID AND Summit_FeaturedSpeakers.SummitID = {$summit->getId()}) AS Featured
	FROM PresentationSpeaker S
	LEFT JOIN Member M ON M.ID = S.MemberID
	LEFT JOIN SpeakerRegistrationRequest R ON R.SpeakerID = S.ID
	WHERE
	EXISTS
	(
		SELECT E.ID FROM SummitEvent E
		INNER JOIN Presentation P ON E.ID = P.ID
		INNER JOIN Presentation_Speakers PS ON PS.PresentationID = P.ID
		WHERE E.SummitID = {$summit->getId()} AND P.ModeratorID = S.ID AND E.Published = 1 {$extra_events_filters}
	)
	UNION
	SELECT S.ID,
	IFNULL(S.FirstName, M.FirstName) AS FirstName,
	IFNULL(S.LastName, M.Surname) AS LastName,
	CONCAT(IFNULL(S.FirstName, M.FirstName), ' ', IFNULL(S.LastName, M.Surname)) AS FullName,
	IFNULL(M.Email, R.Email) AS Email,
	EXISTS(SELECT 1 FROM Summit_FeaturedSpeakers WHERE Summit_FeaturedSpeakers.PresentationSpeakerID = S.ID AND Summit_FeaturedSpeakers.SummitID = {$summit->getId()}) AS Featured
	FROM PresentationSpeaker S
	LEFT JOIN Member M ON M.ID = S.MemberID
	LEFT JOIN SpeakerRegistrationRequest R ON R.SpeakerID = S.ID 
	WHERE
	EXISTS
	(
		SELECT E.ID FROM SummitEvent E
		INNER JOIN Presentation P ON E.ID = P.ID
		INNER JOIN Presentation_Speakers PS ON PS.PresentationID = P.ID
		WHERE E.SummitID = {$summit->getId()} AND P.ModeratorID = S.ID AND E.Published = 1 {$extra_events_filters}
	)
)
SUMMIT_SPEAKERS
{$extra_filters}
SQL;


        $stm = $this->getEntityManager()->getConnection()->prepare($query_count);
        $stm->execute($bindings);
        $res = $stm->fetchAll(\PDO::FETCH_COLUMN);

        $total = count($res) > 0 ? $res[0] : 0;

        $bindings = array_merge($bindings, array
        (
            'per_page' => $paging_info->getPerPage(),
            'offset' => $paging_info->getOffset(),
        ));

        $query = <<<SQL
SELECT *
FROM (
	SELECT
    S.ID,
    S.ClassName,
    S.Created,
    S.LastEdited,
    S.Title AS SpeakerTitle,
    S.Bio,
    S.IRCHandle,
    S.AvailableForBureau,
    S.FundedTravel,
    S.Country,
    S.MemberID,
    S.WillingToTravel,
    S.WillingToPresentVideo,
    S.Notes,
    S.TwitterName,
    IFNULL(S.FirstName, M.FirstName) AS FirstName,
	IFNULL(S.LastName, M.Surname) AS LastName,
	CONCAT(IFNULL(S.FirstName, M.FirstName), ' ', IFNULL(S.LastName, M.Surname)) AS FullName,
    IFNULL(M.Email,R.Email) AS Email,
    S.PhotoID,
    S.BigPhotoID,
    R.ID AS RegistrationRequestID,
    EXISTS(SELECT 1 FROM Summit_FeaturedSpeakers WHERE Summit_FeaturedSpeakers.PresentationSpeakerID = S.ID AND Summit_FeaturedSpeakers.SummitID = {$summit->getId()}) AS Featured
    FROM PresentationSpeaker S
	LEFT JOIN Member M ON M.ID = S.MemberID
	LEFT JOIN File F ON F.ID = S.PhotoID
	LEFT JOIN SpeakerRegistrationRequest R ON R.SpeakerID = S.ID
	WHERE
	EXISTS
	(
		SELECT E.ID FROM SummitEvent E
		INNER JOIN Presentation P ON E.ID = P.ID
		INNER JOIN Presentation_Speakers PS ON PS.PresentationID = P.ID
		WHERE E.SummitID = {$summit->getId()} AND PS.PresentationSpeakerID = S.ID AND E.Published = 1 {$extra_events_filters}
	)
	UNION
	SELECT
    S.ID,
    S.ClassName,
    S.Created,
    S.LastEdited,
    S.Title AS SpeakerTitle,
    S.Bio,
    S.IRCHandle,
    S.AvailableForBureau,
    S.FundedTravel,
    S.Country,
    S.MemberID,
    S.WillingToTravel,
    S.WillingToPresentVideo,
    S.Notes,
    S.TwitterName,
    IFNULL(S.FirstName, M.FirstName) AS FirstName,
	IFNULL(S.LastName, M.Surname) AS LastName,
	CONCAT(IFNULL(S.FirstName, M.FirstName), ' ', IFNULL(S.LastName, M.Surname)) AS FullName,
    IFNULL(M.Email,R.Email) AS Email,
    S.PhotoID,
    S.BigPhotoID,
    R.ID AS RegistrationRequestID,
    EXISTS(SELECT 1 FROM Summit_FeaturedSpeakers WHERE Summit_FeaturedSpeakers.PresentationSpeakerID = S.ID AND Summit_FeaturedSpeakers.SummitID = {$summit->getId()}) AS Featured
    FROM PresentationSpeaker S
	LEFT JOIN Member M ON M.ID = S.MemberID
    LEFT JOIN SpeakerRegistrationRequest R ON R.SpeakerID = S.ID
    WHERE
	EXISTS
	(
		SELECT E.ID FROM SummitEvent E
		INNER JOIN Presentation P ON E.ID = P.ID
		INNER JOIN Presentation_Speakers PS ON PS.PresentationID = P.ID
		WHERE E.SummitID = {$summit->getId()} AND P.ModeratorID = S.ID AND E.Published = 1 {$extra_events_filters}
	)
	UNION
	SELECT
    S.ID,
    S.ClassName,
    S.Created,
    S.LastEdited,
    S.Title AS SpeakerTitle,
    S.Bio,
    S.IRCHandle,
    S.AvailableForBureau,
    S.FundedTravel,
    S.Country,
    S.MemberID,
    S.WillingToTravel,
    S.WillingToPresentVideo,
    S.Notes,
    S.TwitterName,
    IFNULL(S.FirstName, M.FirstName) AS FirstName,
	IFNULL(S.LastName, M.Surname) AS LastName,
	CONCAT(IFNULL(S.FirstName, M.FirstName), ' ', IFNULL(S.LastName, M.Surname)) AS FullName,
    IFNULL(M.Email,R.Email) AS Email,
    S.PhotoID,
    S.BigPhotoID,
    R.ID AS RegistrationRequestID,
    EXISTS(SELECT 1 FROM Summit_FeaturedSpeakers WHERE Summit_FeaturedSpeakers.PresentationSpeakerID = S.ID AND Summit_FeaturedSpeakers.SummitID = {$summit->getId()}) AS Featured
    FROM PresentationSpeaker S
	LEFT JOIN Member M ON M.ID = S.MemberID
    LEFT JOIN SpeakerRegistrationRequest R ON R.SpeakerID = S.ID
    WHERE
	EXISTS
	(
		SELECT E.ID FROM SummitEvent E
		INNER JOIN Presentation P ON E.ID = P.ID
		INNER JOIN Presentation_Speakers PS ON PS.PresentationID = P.ID
		WHERE E.SummitID = {$summit->getId()} AND P.ModeratorID = S.ID AND E.Published = 1 {$extra_events_filters}
	)
)
SUMMIT_SPEAKERS
{$extra_filters} {$extra_orders} limit :per_page offset :offset;
SQL;

        /*$rsm = new ResultSetMapping();
        $rsm->addEntityResult(\models\summit\PresentationSpeaker::class, 's');
        $rsm->addJoinedEntityResult(\models\main\File::class,'p', 's', 'photo');
        $rsm->addJoinedEntityResult(\models\main\Member::class,'m', 's', 'member');

        $rsm->addFieldResult('s', 'ID', 'id');
        $rsm->addFieldResult('s', 'FirstName', 'first_name');
        $rsm->addFieldResult('s', 'LastName', 'last_name');
        $rsm->addFieldResult('s', 'Bio', 'last_name');
        $rsm->addFieldResult('s', 'SpeakerTitle', 'title' );
        $rsm->addFieldResult('p', 'PhotoID', 'id');
        $rsm->addFieldResult('p', 'PhotoTitle', 'title');
        $rsm->addFieldResult('p', 'PhotoFileName', 'filename');
        $rsm->addFieldResult('p', 'PhotoName', 'name');
        $rsm->addFieldResult('m', 'MemberID', 'id');*/

        $rsm = new ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addRootEntityFromClassMetadata(\models\summit\PresentationSpeaker::class, 's', ['Title' => 'SpeakerTitle']);

        // build rsm here
        $native_query = $this->getEntityManager()->createNativeQuery($query, $rsm);

        foreach ($bindings as $k => $v)
            $native_query->setParameter($k, $v);

        $speakers = $native_query->getResult();

        $last_page = (int)ceil($total / $paging_info->getPerPage());

        return new PagingResponse($total, $paging_info->getPerPage(), $paging_info->getCurrentPage(), $last_page, $speakers);
    }

    /**
     * @param PagingInfo $paging_info
     * @param Filter|null $filter
     * @param Order|null $order
     * @return PagingResponse
     */
    public function getAllByPage(PagingInfo $paging_info, Filter $filter = null, Order $order = null)
    {

        $extra_filters = '';
        $extra_orders = '';
        $bindings = [];

        if (!is_null($filter)) {
            $where_conditions = $filter->toRawSQL([
                'first_name' => 'FirstName',
                'last_name' => 'LastName',
                'email' => 'Email',
                'id' => 'ID',
                'full_name' => "FullName",
            ]);
            if (!empty($where_conditions)) {
                $extra_filters = " WHERE {$where_conditions}";
                $bindings = array_merge($bindings, $filter->getSQLBindings());
            }
        }

        if (!is_null($order)) {
            $extra_orders = $order->toRawSQL(array
            (
                'first_name' => 'FirstName',
                'last_name' => 'LastName',
                'email' => 'Email',
                'id' => 'ID',
                'full_name' => "FullName",
            ));
        }

        $query_count = <<<SQL
SELECT COUNT(DISTINCT(ID)) AS QTY
FROM (
	SELECT S.ID,
	IFNULL(S.FirstName, M.FirstName) AS FirstName,
	IFNULL(S.LastName, M.Surname) AS LastName,
	CONCAT(IFNULL(S.FirstName, M.FirstName), ' ', IFNULL(S.LastName, M.Surname)) AS FullName,
	IFNULL(M.Email,R.Email) AS Email
	FROM PresentationSpeaker S
	LEFT JOIN Member M ON M.ID = S.MemberID
	LEFT JOIN SpeakerRegistrationRequest R ON R.SpeakerID = S.ID
)
SUMMIT_SPEAKERS
{$extra_filters}
SQL;


        $stm = $this->getEntityManager()->getConnection()->executeQuery($query_count, $bindings);

        $total = intval($stm->fetchColumn(0));

        $bindings = array_merge($bindings, array
        (
            'per_page' => $paging_info->getPerPage(),
            'offset' => $paging_info->getOffset(),
        ));

        $query = <<<SQL
SELECT *
FROM (
	SELECT
    S.ID,
    S.ClassName,
    S.Created,
    S.LastEdited,
    S.Title AS SpeakerTitle,
    S.Bio,
    S.IRCHandle,
    S.AvailableForBureau,
    S.FundedTravel,
    S.Country,
    S.WillingToTravel,
    S.WillingToPresentVideo,
    S.Notes,
    S.TwitterName,
    IFNULL(S.FirstName, M.FirstName) AS FirstName,
	IFNULL(S.LastName, M.Surname) AS LastName,
    IFNULL(M.Email,R.Email) AS Email,
    CONCAT(IFNULL(S.FirstName, M.FirstName), ' ', IFNULL(S.LastName, M.Surname)) AS FullName,
    S.PhotoID,
    S.BigPhotoID,
    M.ID AS MemberID,
    R.ID AS RegistrationRequestID
    FROM PresentationSpeaker S
	LEFT JOIN Member M ON M.ID = S.MemberID
	LEFT JOIN File F ON F.ID = S.PhotoID
    LEFT JOIN SpeakerRegistrationRequest R ON R.SpeakerID = S.ID
)
SUMMIT_SPEAKERS
{$extra_filters} {$extra_orders} limit :per_page offset :offset;
SQL;

        /*$rsm = new ResultSetMapping();
        $rsm->addEntityResult(\models\summit\PresentationSpeaker::class, 's');
        $rsm->addJoinedEntityResult(\models\main\File::class,'p', 's', 'photo');
        $rsm->addJoinedEntityResult(\models\main\Member::class,'m', 's', 'member');

        $rsm->addFieldResult('s', 'ID', 'id');
        $rsm->addFieldResult('s', 'FirstName', 'first_name');
        $rsm->addFieldResult('s', 'LastName', 'last_name');
        $rsm->addFieldResult('s', 'Bio', 'last_name');
        $rsm->addFieldResult('s', 'SpeakerTitle', 'title' );
        $rsm->addFieldResult('p', 'PhotoID', 'id');
        $rsm->addFieldResult('p', 'PhotoTitle', 'title');
        $rsm->addFieldResult('p', 'PhotoFileName', 'filename');
        $rsm->addFieldResult('p', 'PhotoName', 'name');
        $rsm->addFieldResult('m', 'MemberID', 'id');*/

        $rsm = new ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addRootEntityFromClassMetadata(\models\summit\PresentationSpeaker::class, 's', ['Title' => 'SpeakerTitle']);

        // build rsm here
        $native_query = $this->getEntityManager()->createNativeQuery($query, $rsm);

        foreach ($bindings as $k => $v)
            $native_query->setParameter($k, $v);

        $speakers = $native_query->getResult();

        $last_page = (int)ceil($total / $paging_info->getPerPage());

        return new PagingResponse($total, $paging_info->getPerPage(), $paging_info->getCurrentPage(), $last_page, $speakers);
    }

    /**
     * @return string
     */
    protected function getBaseEntity()
    {
        return PresentationSpeaker::class;
    }

    /**
     * @param Member $member
     * @return PresentationSpeaker
     */
    public function getByMember(Member $member)
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select("s")
            ->from(PresentationSpeaker::class, "s")
            ->where("s.member = :member")
            ->setParameter("member", $member)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param string $email
     * @return PresentationSpeaker|null
     */
    public function getByEmail(string $email): ?PresentationSpeaker
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select("s")
            ->from(PresentationSpeaker::class, "s")
            ->leftJoin("s.member", "m")
            ->leftJoin("s.registration_request", "r")
            ->where("m.email = :email1 or r.email = :email2")
            ->setParameter("email1", trim($email))
            ->setParameter("email2", trim($email))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }


    /**
     * @param string $fullname
     * @return PresentationSpeaker|null
     */
    public function getByFullName(string $fullname): ?PresentationSpeaker
    {
        $speakerFullNameParts = explode(" ", $fullname);
        $speakerLastName = trim(trim(array_pop($speakerFullNameParts)));
        $speakerFirstName = trim(implode(" ", $speakerFullNameParts));

        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select("e")
            ->from(PresentationSpeaker::class, "e")
            ->where("e.first_name = :first_name")
            ->andWhere("e.last_name = :last_name")
            ->setParameter("first_name", $speakerFirstName)
            ->setParameter("last_name", $speakerLastName)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param int $speaker_id
     * @param int $summit_id
     * @return bool
     */
    public function speakerBelongsToSummitSchedule(int $speaker_id, int $summit_id): bool
    {

        try {
            $sql = <<<SQL
	SELECT COUNT(E.ID) FROM SummitEvent E
		INNER JOIN Presentation P ON E.ID = P.ID
		INNER JOIN Presentation_Speakers PS ON PS.PresentationID = P.ID
		WHERE E.SummitID = :summit_id AND PS.PresentationSpeakerID = :speaker_id AND E.Published = 1
SQL;

            $stmt = $this->getEntityManager()->getConnection()->prepare($sql);
            $stmt->execute([
                'summit_id' => $summit_id,
                'speaker_id' => $speaker_id
            ]);

            $res = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            if (count($res) > 0 && intval($res[0]) > 0) return true;

            $sql = <<<SQL
	SELECT COUNT(E.ID) FROM SummitEvent E
		INNER JOIN Presentation P ON E.ID = P.ID
		WHERE E.SummitID = :summit_id AND P.ModeratorID = :speaker_id AND E.Published = 1
SQL;

            $stmt = $this->getEntityManager()->getConnection()->prepare($sql);
            $stmt->execute([
                'summit_id' => $summit_id,
                'speaker_id' => $speaker_id
            ]);

            $res = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            if (count($res) > 0 && intval($res[0]) > 0) return true;
        } catch (\Exception $ex) {
            Log::warning($ex);
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function getFeaturedSpeakers(Summit $summit, PagingInfo $paging_info, Filter $filter = null, Order $order = null): PagingResponse
    {
        $extra_filters = '';
        $extra_orders = '';
        $bindings = [];

        if (!is_null($filter)) {
            $where_conditions = $filter->toRawSQL([
                'full_name' => 'FullName',
                'first_name' => 'FirstName',
                'last_name' => 'LastName',
                'email' => 'Email',
                'id' => 'ID'
            ]);
            if (!empty($where_conditions)) {
                $extra_filters = " WHERE {$where_conditions}";
                $bindings = array_merge($bindings, $filter->getSQLBindings());
            }
        }

        if (!is_null($order)) {
            $extra_orders = $order->toRawSQL(array
            (
                'id' => 'ID',
                'email' => 'Email',
                'first_name' => 'FirstName',
                'last_name' => 'LastName',
                'full_name' => 'FullName',
                'order' => '`CustomOrder`'
            ));
        }

        $query_count = <<<SQL
SELECT COUNT(DISTINCT(ID)) AS QTY
FROM (
	SELECT S.ID,
	IFNULL(S.FirstName, M.FirstName) AS FirstName,
	IFNULL(S.LastName, M.Surname) AS LastName,
	CONCAT(IFNULL(S.FirstName, M.FirstName), ' ', IFNULL(S.LastName, M.Surname)) AS FullName,
	IFNULL(M.Email, R.Email) AS Email
	FROM PresentationSpeaker S
	LEFT JOIN Member M ON M.ID = S.MemberID
	LEFT JOIN SpeakerRegistrationRequest R ON R.SpeakerID = S.ID
	INNER JOIN Summit_FeaturedSpeakers FS ON FS.PresentationSpeakerID = S.ID AND FS.SummitID = {$summit->getId()}	
)
SUMMIT_SPEAKERS
{$extra_filters}
SQL;


        $stm = $this->getEntityManager()->getConnection()->executeQuery($query_count, $bindings);

        $total = intval($stm->fetchColumn(0));

        $bindings = array_merge($bindings, array
        (
            'per_page' => $paging_info->getPerPage(),
            'offset' => $paging_info->getOffset(),
        ));

        $query = <<<SQL
SELECT *
FROM (
	SELECT
    S.ID,
    S.ClassName,
    S.Created,
    S.LastEdited,
    S.Title AS SpeakerTitle,
    S.Bio,
    S.IRCHandle,
    S.AvailableForBureau,
    S.FundedTravel,
    S.Country,
    S.MemberID,
    S.WillingToTravel,
    S.WillingToPresentVideo,
    S.Notes,
    S.TwitterName,
    IFNULL(S.FirstName, M.FirstName) AS FirstName,
	IFNULL(S.LastName, M.Surname) AS LastName,
	CONCAT(IFNULL(S.FirstName, M.FirstName), ' ', IFNULL(S.LastName, M.Surname)) AS FullName,
    IFNULL(M.Email,R.Email) AS Email,
    S.PhotoID,
    S.BigPhotoID,
    R.ID AS RegistrationRequestID,
	FS.`CustomOrder` AS `CustomOrder`
    FROM PresentationSpeaker S
	LEFT JOIN Member M ON M.ID = S.MemberID
	LEFT JOIN File F ON F.ID = S.PhotoID
    LEFT JOIN SpeakerRegistrationRequest R ON R.SpeakerID = S.ID
	INNER JOIN Summit_FeaturedSpeakers FS ON FS.PresentationSpeakerID = S.ID AND FS.SummitID = {$summit->getId()}	
)
SUMMIT_SPEAKERS
{$extra_filters} {$extra_orders} limit :per_page offset :offset;
SQL;

        /*$rsm = new ResultSetMapping();
        $rsm->addEntityResult(\models\summit\PresentationSpeaker::class, 's');
        $rsm->addJoinedEntityResult(\models\main\File::class,'p', 's', 'photo');
        $rsm->addJoinedEntityResult(\models\main\Member::class,'m', 's', 'member');

        $rsm->addFieldResult('s', 'ID', 'id');
        $rsm->addFieldResult('s', 'FirstName', 'first_name');
        $rsm->addFieldResult('s', 'LastName', 'last_name');
        $rsm->addFieldResult('s', 'Bio', 'last_name');
        $rsm->addFieldResult('s', 'SpeakerTitle', 'title' );
        $rsm->addFieldResult('p', 'PhotoID', 'id');
        $rsm->addFieldResult('p', 'PhotoTitle', 'title');
        $rsm->addFieldResult('p', 'PhotoFileName', 'filename');
        $rsm->addFieldResult('p', 'PhotoName', 'name');
        $rsm->addFieldResult('m', 'MemberID', 'id');*/

        $rsm = new ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addRootEntityFromClassMetadata(\models\summit\PresentationSpeaker::class, 's', ['Title' => 'SpeakerTitle']);

        // build rsm here
        $native_query = $this->getEntityManager()->createNativeQuery($query, $rsm);

        foreach ($bindings as $k => $v)
            $native_query->setParameter($k, $v);

        $speakers = $native_query->getResult();

        $last_page = (int)ceil($total / $paging_info->getPerPage());

        return new PagingResponse($total, $paging_info->getPerPage(), $paging_info->getCurrentPage(), $last_page, $speakers);
    }
}