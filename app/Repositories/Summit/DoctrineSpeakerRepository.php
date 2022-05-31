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
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Illuminate\Support\Facades\Log;
use models\main\Member;
use models\summit\ISpeakerRepository;
use models\summit\PresentationSpeaker;
use models\summit\Summit;
use App\Repositories\SilverStripeDoctrineRepository;
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
     * @param Summit $summit
     * @param PagingInfo $paging_info
     * @param Filter|null $filter
     * @param Order|null $order
     * @return PagingResponse
     */
    public function getSpeakersBySummit(Summit $summit, PagingInfo $paging_info, Filter $filter = null, Order $order = null)
    {

        $extra_filters = '';
        $extra_orders  = '';
        $bindings      = [];

        if(!is_null($filter))
        {
            $where_conditions = $filter->toRawSQL([
                'full_name'  => 'FullName',
                'first_name' => 'FirstName',
                'last_name'  => 'LastName',
                'email'      => 'Email',
                'id'         => 'ID'
            ]);
            if(!empty($where_conditions)) {
                $extra_filters = " WHERE {$where_conditions}";
                $bindings = array_merge($bindings, $filter->getSQLBindings());
            }
        }

        if(!is_null($order))
        {
            $extra_orders = $order->toRawSQL(array
            (
                'id'         => 'ID',
                'email'      => 'Email',
                'first_name' => 'FirstName',
                'last_name'  => 'LastName',
                'full_name'  => 'FullName',
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
	WHERE
	EXISTS
	(
		SELECT E.ID FROM SummitEvent E
		INNER JOIN Presentation P ON E.ID = P.ID
		INNER JOIN Presentation_Speakers PS ON PS.PresentationID = P.ID
		WHERE E.SummitID = {$summit->getId()} AND PS.PresentationSpeakerID = S.ID
	)
	UNION
	SELECT S.ID,
	IFNULL(S.FirstName, M.FirstName) AS FirstName,
	IFNULL(S.LastName, M.Surname) AS LastName,
	CONCAT(IFNULL(S.FirstName, M.FirstName), ' ', IFNULL(S.LastName, M.Surname)) AS FullName,
	IFNULL(M.Email, R.Email) AS Email
	FROM PresentationSpeaker S
	LEFT JOIN Member M ON M.ID = S.MemberID
	LEFT JOIN SpeakerRegistrationRequest R ON R.SpeakerID = S.ID
	WHERE
	EXISTS
	(
		SELECT E.ID FROM SummitEvent E
		INNER JOIN Presentation P ON E.ID = P.ID
		INNER JOIN Presentation_Speakers PS ON PS.PresentationID = P.ID
		WHERE E.SummitID = {$summit->getId()} AND P.ModeratorID = S.ID
	)
	UNION
	SELECT S.ID,
	IFNULL(S.FirstName, M.FirstName) AS FirstName,
	IFNULL(S.LastName, M.Surname) AS LastName,
	CONCAT(IFNULL(S.FirstName, M.FirstName), ' ', IFNULL(S.LastName, M.Surname)) AS FullName,
	IFNULL(M.Email, R.Email) AS Email
	FROM PresentationSpeaker S
	LEFT JOIN Member M ON M.ID = S.MemberID
	LEFT JOIN SpeakerRegistrationRequest R ON R.SpeakerID = S.ID
	WHERE
	EXISTS
	(
		SELECT E.ID FROM SummitEvent E
		INNER JOIN Presentation P ON E.ID = P.ID
		INNER JOIN Presentation_Speakers PS ON PS.PresentationID = P.ID
		WHERE E.SummitID = {$summit->getId()} AND P.ModeratorID = S.ID
	)
	UNION
	SELECT S.ID,
	IFNULL(S.FirstName, M.FirstName) AS FirstName,
	IFNULL(S.LastName, M.Surname) AS LastName,
	CONCAT(IFNULL(S.FirstName, M.FirstName), ' ', IFNULL(S.LastName, M.Surname)) AS FullName,
	IFNULL(M.Email, R.Email) AS Email
	FROM PresentationSpeaker S
	LEFT JOIN Member M ON M.ID = S.MemberID
	LEFT JOIN SpeakerRegistrationRequest R ON R.SpeakerID = S.ID
	WHERE
	EXISTS
	(
		SELECT A.ID FROM PresentationSpeakerSummitAssistanceConfirmationRequest A
		WHERE A.SummitID = {$summit->getId()} AND A.SpeakerID = S.ID
	)
)
SUMMIT_SPEAKERS
{$extra_filters}
SQL;


        $stm   = $this->getEntityManager()->getConnection()->executeQuery($query_count, $bindings);

        $total = intval($stm->fetchColumn(0));

        $bindings = array_merge( $bindings, array
        (
            'per_page'  => $paging_info->getPerPage(),
            'offset'    => $paging_info->getOffset(),
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
    R.ID AS RegistrationRequestID
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
		WHERE E.SummitID = {$summit->getId()} AND PS.PresentationSpeakerID = S.ID
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
    R.ID AS RegistrationRequestID
    FROM PresentationSpeaker S
	LEFT JOIN Member M ON M.ID = S.MemberID
    LEFT JOIN SpeakerRegistrationRequest R ON R.SpeakerID = S.ID
    WHERE
	EXISTS
	(
		SELECT E.ID FROM SummitEvent E
		INNER JOIN Presentation P ON E.ID = P.ID
		INNER JOIN Presentation_Speakers PS ON PS.PresentationID = P.ID
		WHERE E.SummitID = {$summit->getId()} AND P.ModeratorID = S.ID
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
    R.ID AS RegistrationRequestID
    FROM PresentationSpeaker S
	LEFT JOIN Member M ON M.ID = S.MemberID
    LEFT JOIN SpeakerRegistrationRequest R ON R.SpeakerID = S.ID
    WHERE
	EXISTS
	(
		SELECT E.ID FROM SummitEvent E
		INNER JOIN Presentation P ON E.ID = P.ID
		INNER JOIN Presentation_Speakers PS ON PS.PresentationID = P.ID
		WHERE E.SummitID = {$summit->getId()} AND P.ModeratorID = S.ID
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
    IFNULL(M.Email,R.Email) AS Email,
    CONCAT(IFNULL(S.FirstName, M.FirstName), ' ', IFNULL(S.LastName, M.Surname)) AS FullName,
    S.PhotoID,
    S.BigPhotoID,
    R.ID AS RegistrationRequestID
    FROM PresentationSpeaker S
	LEFT JOIN Member M ON M.ID = S.MemberID
    LEFT JOIN SpeakerRegistrationRequest R ON R.SpeakerID = S.ID
    WHERE
	EXISTS
	(
		SELECT A.ID FROM PresentationSpeakerSummitAssistanceConfirmationRequest A
		WHERE A.SummitID = {$summit->getId()} AND A.SpeakerID = S.ID
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

        foreach($bindings as $k => $v)
            $native_query->setParameter($k, $v);

        $speakers = $native_query->getResult();

        $last_page = (int) ceil($total / $paging_info->getPerPage());

        return new PagingResponse($total, $paging_info->getPerPage(), $paging_info->getCurrentPage(), $last_page, $speakers);
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
        $extra_orders  = '';
        $bindings      = [];

        if(!is_null($filter))
        {
            $where_conditions = $filter->toRawSQL([
                'full_name'  => 'FullName',
                'first_name' => 'FirstName',
                'last_name'  => 'LastName',
                'email'      => 'Email',
                'id'         => 'ID',
                'featured'   => 'Featured'
            ]);

            if(!empty($where_conditions)) {
                $extra_filters = " WHERE {$where_conditions}";
                $bindings = array_merge($bindings, $filter->getSQLBindings());
            }

            $where_event_conditions = $filter->toRawSQL([
                'event_start_date' => 'E.StartDate:datetime_epoch',
                'event_end_date' => 'E.EndDate:datetime_epoch',
            ], count($bindings) + 1);

            if(!empty($where_event_conditions)) {
                $extra_events_filters = " AND {$where_event_conditions}";
                $bindings = array_merge($bindings, $filter->getSQLBindings());
            }
        }

        foreach ($bindings as $key => $value){
            if($value == 'true')
                $bindings[$key] =  1;
            if($value == 'false')
                $bindings[$key] =  0;
        }

        if(!is_null($order))
        {
            $extra_orders = $order->toRawSQL(array
            (
                'id'         => 'ID',
                'email'      => 'Email',
                'first_name' => 'FirstName',
                'last_name'  => 'LastName',
                'full_name'  => 'FullName',
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

        $bindings = array_merge( $bindings, array
        (
            'per_page'  => $paging_info->getPerPage(),
            'offset'    => $paging_info->getOffset(),
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

        foreach($bindings as $k => $v)
            $native_query->setParameter($k, $v);

        $speakers = $native_query->getResult();

        $last_page = (int) ceil($total / $paging_info->getPerPage());

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
        $extra_orders  = '';
        $bindings      = [];

        if(!is_null($filter))
        {
            $where_conditions = $filter->toRawSQL([
                'first_name' => 'FirstName',
                'last_name'  => 'LastName',
                'email'      => 'Email',
                'id'         => 'ID',
                'full_name'  =>  "FullName",
            ]);
            if(!empty($where_conditions)) {
                $extra_filters = " WHERE {$where_conditions}";
                $bindings = array_merge($bindings, $filter->getSQLBindings());
            }
        }

        if(!is_null($order))
        {
            $extra_orders = $order->toRawSQL(array
            (
                'first_name' => 'FirstName',
                'last_name'  => 'LastName',
                'email'      => 'Email',
                'id'         => 'ID',
                'full_name'  =>  "FullName",
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


        $stm   = $this->getEntityManager()->getConnection()->executeQuery($query_count, $bindings);

        $total = intval($stm->fetchColumn(0));

        $bindings = array_merge( $bindings, array
        (
            'per_page'  => $paging_info->getPerPage(),
            'offset'    => $paging_info->getOffset(),
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

        foreach($bindings as $k => $v)
            $native_query->setParameter($k, $v);

        $speakers = $native_query->getResult();

        $last_page = (int) ceil($total / $paging_info->getPerPage());

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
    public function getByEmail(string $email):?PresentationSpeaker
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
        $speakerLastName      = trim(trim(array_pop($speakerFullNameParts)));
        $speakerFirstName     = trim(implode(" ", $speakerFullNameParts));

        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select("e")
            ->from(PresentationSpeaker::class, "e")
            ->where("e.first_name = :first_name")
            ->andWhere("e.last_name = :last_name")
            ->setParameter("first_name",$speakerFirstName)
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
    public function speakerBelongsToSummitSchedule(int $speaker_id, int $summit_id):bool {

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
        }
        catch (\Exception $ex){
            Log::warning($ex);
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function getFeaturedSpeakers(Summit $summit, PagingInfo $paging_info, Filter $filter = null, Order $order = null):PagingResponse
    {
        $extra_filters = '';
        $extra_orders  = '';
        $bindings      = [];

        if(!is_null($filter))
        {
            $where_conditions = $filter->toRawSQL([
                'full_name'  => 'FullName',
                'first_name' => 'FirstName',
                'last_name'  => 'LastName',
                'email'      => 'Email',
                'id'         => 'ID'
            ]);
            if(!empty($where_conditions)) {
                $extra_filters = " WHERE {$where_conditions}";
                $bindings = array_merge($bindings, $filter->getSQLBindings());
            }
        }

        if(!is_null($order))
        {
            $extra_orders = $order->toRawSQL(array
            (
                'id'         => 'ID',
                'email'      => 'Email',
                'first_name' => 'FirstName',
                'last_name'  => 'LastName',
                'full_name'  => 'FullName',
                'order'      => '`CustomOrder`'
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


        $stm   = $this->getEntityManager()->getConnection()->executeQuery($query_count, $bindings);

        $total = intval($stm->fetchColumn(0));

        $bindings = array_merge( $bindings, array
        (
            'per_page'  => $paging_info->getPerPage(),
            'offset'    => $paging_info->getOffset(),
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

        foreach($bindings as $k => $v)
            $native_query->setParameter($k, $v);

        $speakers = $native_query->getResult();

        $last_page = (int) ceil($total / $paging_info->getPerPage());

        return new PagingResponse($total, $paging_info->getPerPage(), $paging_info->getCurrentPage(), $last_page, $speakers);
    }
}