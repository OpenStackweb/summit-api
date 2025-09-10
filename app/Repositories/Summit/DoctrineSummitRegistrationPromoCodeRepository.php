<?php namespace App\Repositories\Summit;
/**
 * Copyright 2018 OpenStack Foundation
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

use App\Http\Utils\Filters\SQL\SQLInFilterMapping;
use App\Http\Utils\Filters\SQL\SQLInstanceOfFilterMapping;
use App\Http\Utils\Filters\SQL\SQLNotInFilterMapping;
use App\Repositories\SilverStripeDoctrineRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use models\summit\ISummitRegistrationPromoCodeRepository;
use models\summit\MemberSummitRegistrationDiscountCode;
use models\summit\MemberSummitRegistrationPromoCode;
use models\summit\PrePaidSummitRegistrationDiscountCode;
use models\summit\PrePaidSummitRegistrationPromoCode;
use models\summit\SpeakersRegistrationDiscountCode;
use models\summit\SpeakersSummitRegistrationPromoCode;
use models\summit\SpeakerSummitRegistrationDiscountCode;
use models\summit\SpeakerSummitRegistrationPromoCode;
use models\summit\SponsorSummitRegistrationDiscountCode;
use models\summit\SponsorSummitRegistrationPromoCode;
use models\summit\Summit;
use models\summit\SummitRegistrationDiscountCode;
use models\summit\SummitRegistrationPromoCode;
use utils\DoctrineFilterMapping;
use utils\DoctrineInstanceOfFilterMapping;
use utils\DoctrineLeftJoinFilterMapping;
use utils\Filter;
use utils\Order;
use utils\PagingInfo;
use utils\PagingResponse;
/**
 * Class DoctrineSummitRegistrationPromoCodeRepository
 * @package App\Repositories\Summit
 */
class DoctrineSummitRegistrationPromoCodeRepository
    extends SilverStripeDoctrineRepository
    implements ISummitRegistrationPromoCodeRepository
{

    /**
     * @return string
     */
    protected function getBaseEntity()
    {
        return SummitRegistrationPromoCode::class;
    }

    /**
     * @param string $code
     * @return SummitRegistrationPromoCode|null
     */
    public function getByCode(string $code):?SummitRegistrationPromoCode{
        $query  =   $this->getEntityManager()
            ->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e")
            ->where("e.code = :code");

        $query->setParameter("code", $code);

        return $query->getQuery()->getOneOrNullResult();
    }

    /**
     * @param QueryBuilder $query
     * @param Filter|null $filter
     * @return QueryBuilder
     */
    protected function applyExtraJoins(QueryBuilder $query, ?Filter $filter = null, ?Order $order = null){

        if (!is_null($filter)) {

            if ($filter->hasFilter("creator") || $filter->hasFilter("creator_email")) {
                $query = $query->leftJoin('pc.creator', 'ct');
            }

            if ($filter->hasFilter("owner") || $filter->hasFilter("owner_email")) {
                $query = $query
                    ->leftJoin("mpc.owner", "owr")
                    ->leftJoin("mdc.owner", "owr2");
            }

            if ($filter->hasFilter("speaker") || $filter->hasFilter("speaker_email")) {
                $query = $query
                    ->leftJoin("spkpc.speaker", "spkr")
                    ->leftJoin("spkdc.speaker", "spkr2")
                    ->leftJoin("spksdc.owners", "spksdc_owr")
                    ->leftJoin("spksdc_owr.speaker", "spksdc_owr_speaker")
                    ->leftJoin("spkspc.owners", "spkspc_owr")
                    ->leftJoin("spkspc_owr.speaker", "spkspc_owr_speaker");

                if ($filter->hasFilter("speaker_email")) {
                    $query = $query
                        ->leftJoin('spkr.member', "spmm", Join::LEFT_JOIN)
                        ->leftJoin('spkr2.member', "spmm2", Join::LEFT_JOIN)
                        ->leftJoin('spksdc_owr_speaker.member', "spmm3", Join::LEFT_JOIN)
                        ->leftJoin('spkspc_owr_speaker.member', "spmm4", Join::LEFT_JOIN)
                        ->leftJoin('spkr.registration_request', "sprr", Join::LEFT_JOIN)
                        ->leftJoin('spkr2.registration_request', "sprr2", Join::LEFT_JOIN)
                        ->leftJoin('spksdc_owr_speaker.registration_request', "sprr3", Join::LEFT_JOIN)
                        ->leftJoin('spkspc_owr_speaker.registration_request', "sprr4", Join::LEFT_JOIN);
                }
            }

            if ($filter->hasFilter("sponsor")) {
                $query = $query
                    ->leftJoin("spc.sponsor", "spnr")
                    ->leftJoin("spdc.sponsor", "spnr2");
            }
        }

        return $query;
    }

    /**
     * @return array
     */
    protected function getFilterMappings(): array
    {
        $args  = func_get_args();
        $filter = count($args) > 0 ? $args[0] : null;

        $res = [
            'code'          => 'pc.code:json_string',
            'description'   => 'pc.description:json_string',
            'type' => new DoctrineFilterMapping
            (
                "(mpc.type :operator :value OR spkpc.type :operator :value ".
                "OR mdc.type :operator :value OR spkdc.type :operator :value ".
                "OR spkspc.type :operator :value OR spksdc.type :operator :value) "
            ),
            'class_name' => new DoctrineInstanceOfFilterMapping(
                "pc",
                [
                    SummitRegistrationPromoCode::ClassName           => SummitRegistrationPromoCode::class,
                    SummitRegistrationDiscountCode::ClassName        => SummitRegistrationDiscountCode::class,
                    MemberSummitRegistrationPromoCode::ClassName     => MemberSummitRegistrationPromoCode::class,
                    SpeakerSummitRegistrationPromoCode::ClassName    => SpeakerSummitRegistrationPromoCode::class,
                    SponsorSummitRegistrationPromoCode::ClassName    => SponsorSummitRegistrationPromoCode::class,
                    MemberSummitRegistrationDiscountCode::ClassName  => MemberSummitRegistrationDiscountCode::class,
                    SpeakerSummitRegistrationDiscountCode::ClassName => SpeakerSummitRegistrationDiscountCode::class,
                    SponsorSummitRegistrationDiscountCode::ClassName => SponsorSummitRegistrationDiscountCode::class,
                    SpeakersSummitRegistrationPromoCode::ClassName   => SpeakersSummitRegistrationPromoCode::class,
                    SpeakersRegistrationDiscountCode::ClassName      => SpeakersRegistrationDiscountCode::class,
                    PrePaidSummitRegistrationPromoCode::ClassName    => PrePaidSummitRegistrationPromoCode::class,
                    PrePaidSummitRegistrationDiscountCode::ClassName => PrePaidSummitRegistrationDiscountCode::class
                ]
            ),
            'allows_to_delegate'  => 'pc.allows_to_delegate:json_boolean',
        ];

        if ($filter instanceof Filter) {

            if ($filter->hasFilter("creator")) {
                $res['creator'] = new DoctrineFilterMapping
                (
                    "( concat(ct.first_name, ' ', ct.last_name) :operator :value " .
                    "OR ct.first_name :operator :value " .
                    "OR ct.last_name :operator :value ) "
                );
            }

            if ($filter->hasFilter("creator_email")) {
                $res['creator_email'] = new DoctrineFilterMapping("(ct.email :operator :value)");
            }

            if ($filter->hasFilter("owner")) {
                $res['owner'] = new DoctrineFilterMapping
                (
                    "( concat(owr.first_name, ' ', owr.last_name) :operator :value ".
                    "OR owr.first_name :operator :value ".
                    "OR owr.last_name :operator :value ) ".
                    "OR ( concat(owr2.first_name, ' ', owr2.last_name) :operator :value ".
                    "OR owr2.first_name :operator :value ".
                    "OR owr2.last_name :operator :value ) "
                );
            }

            if ($filter->hasFilter("owner_email")) {
                $res['owner_email'] = new DoctrineFilterMapping
                (
                    "(owr.email :operator :value) ".
                    "OR (owr2.email :operator :value) "
                );
            }

            if ($filter->hasFilter("speaker")) {
                $res['speaker'] = new DoctrineFilterMapping
                (
                    "( " .
                    "concat(spkr.first_name, ' ', spkr.last_name) :operator :value " .
                    "OR concat(spmm.first_name, ' ', spmm.last_name) :operator :value " .
                    "OR spkr.first_name :operator :value " .
                    "OR spkr.last_name :operator :value " .
                    "OR spmm.first_name :operator :value " .
                    "OR spmm.last_name :operator :value ) " .
                    "OR ( concat(spkr2.first_name, ' ', spkr2.last_name) :operator :value " .
                    "OR concat(spmm2.first_name, ' ', spmm2.last_name) :operator :value " .
                    "OR spkr2.first_name :operator :value " .
                    "OR spkr2.last_name :operator :value " .
                    "OR spmm2.first_name :operator :value " .
                    "OR spmm2.last_name :operator :value " .
                    "OR concat(spksdc_owr_speaker.first_name, ' ', spksdc_owr_speaker.last_name) :operator :value " .
                    "OR concat(spkspc_owr_speaker.first_name, ' ', spkspc_owr_speaker.last_name) :operator :value " .
                    "OR spksdc_owr_speaker.first_name :operator :value " .
                    "OR spksdc_owr_speaker.last_name :operator :value " .
                    "OR spkspc_owr_speaker.first_name :operator :value " .
                    "OR spkspc_owr_speaker.last_name :operator :value " .
                    ")"
                );
            }

            if ($filter->hasFilter("speaker_email")) {
                $res['speaker_email'] = new DoctrineFilterMapping
                (
                    "(sprr.email :operator :value OR spmm.email :operator :value " .
                    "OR sprr2.email :operator :value OR spmm2.email :operator :value " .
                    "OR sprr3.email :operator :value OR spmm3.email :operator :value " .
                    "OR sprr4.email :operator :value OR spmm4.email :operator :value)"
                );
            }

            if ($filter->hasFilter("sponsor")) {
                $res['sponsor'] = new DoctrineFilterMapping(
                    "(spnr.name :operator :value) OR (spnr2.name :operator :value)");
            }

            if ($filter->hasFilter("tag")) {
                $res['tag'] = new DoctrineLeftJoinFilterMapping(
                    "pc.tags", "t","t.tag :operator :value");
            }

            if ($filter->hasFilter("tag_id")) {
                $res['tag_id'] = new DoctrineLeftJoinFilterMapping(
                    "pc.tags", "t","t.id :operator :value");
            }
        }

        return $res;
    }

    /**
     * @return array
     */
    protected function getOrderMappings()
    {
        return [
            'id'   => 'pc.id',
            'code' => 'pc.code',
            'redeemed' => 'pc.redeemed',
            'allows_to_delegate' => 'pc.allows_to_delegate',
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
    public function getIdsBySummit
    (
        Summit $summit,
        PagingInfo $paging_info,
        Filter $filter = null,
        Order $order   = null
    ): PagingResponse
    {
        /*
         * we perform raw sql query because doctrine is generating a very complex query
         * over ( 61 joins) and that breaks mysql max join limit (SQLSTATE[HY000]: General error: 1116
         * Too many tables; MySQL can only use 61 tables in a join in)
         */

        $bindings = ['param_summit_id' => $summit->getId()];
        $extra_filters = " WHERE pc.SummitID = :param_summit_id";
        $extra_orders = '';


        if ($filter instanceof Filter) {
            $where_conditions = $filter->toRawSQL([
                'id'            => new SQLInFilterMapping('pc.ID'),
                'not_id'        => new SQLNotInFilterMapping('pc.ID'),
                'code'          => 'pc.Code',
                'notes'         => 'pc.Notes',
                'description'   => 'pc.Description',
                'email_sent'    => 'pc.EmailSent',
                'allows_to_delegate' => 'pc.AllowsToDelegate',
                'tag'           => 't.Tag',
                'tag_id'        => 't.ID',
                'class_name'    =>
                    new SQLInstanceOfFilterMapping(
                        "pc",
                        [
                            SummitRegistrationPromoCode::ClassName           => SummitRegistrationPromoCode::class,
                            SummitRegistrationDiscountCode::ClassName        => SummitRegistrationDiscountCode::class,
                            MemberSummitRegistrationPromoCode::ClassName     => MemberSummitRegistrationPromoCode::class,
                            SpeakerSummitRegistrationPromoCode::ClassName    => SpeakerSummitRegistrationPromoCode::class,
                            SponsorSummitRegistrationPromoCode::ClassName    => SponsorSummitRegistrationPromoCode::class,
                            MemberSummitRegistrationDiscountCode::ClassName  => MemberSummitRegistrationDiscountCode::class,
                            SpeakerSummitRegistrationDiscountCode::ClassName => SpeakerSummitRegistrationDiscountCode::class,
                            SponsorSummitRegistrationDiscountCode::ClassName => SponsorSummitRegistrationDiscountCode::class,
                            SpeakersSummitRegistrationPromoCode::ClassName   => SpeakersSummitRegistrationPromoCode::class,
                            SpeakersRegistrationDiscountCode::ClassName      => SpeakersRegistrationDiscountCode::class,
                            PrePaidSummitRegistrationPromoCode::ClassName    => PrePaidSummitRegistrationPromoCode::class,
                            PrePaidSummitRegistrationDiscountCode::ClassName => PrePaidSummitRegistrationDiscountCode::class
                        ]
                    ),
                'type' => [
                    "mpc.Type :operator :value",
                    "mdc.Type :operator :value",
                    "spkpc.Type :operator :value",
                    "spkdc.type :operator :value",
                    "spkspc.type :operator :value",
                    "spksdc.type :operator :value"
                ],
                'creator' => [
                    Filter::buildConcatStringFields(["ct.FirstName", "ct.Surname"]),
                    "ct.FirstName :operator :value",
                    "ct.Surname :operator :value"
                ],
                'creator_email' => Filter::buildEmailField('ct.Email'),
                'owner' => [
                    Filter::buildConcatStringFields(["owr.FirstName", "owr.Surname"]),
                    "owr.FirstName :operator :value",
                    "owr.Surname :operator :value",
                    Filter::buildConcatStringFields(["owr2.FirstName", "owr2.Surname"]),
                    "owr2.FirstName :operator :value",
                    "owr2.Surname :operator :value",
                    Filter::buildConcatStringFields(["mpc.FirstName", "mpc.LastName"]),
                    Filter::buildConcatStringFields(["mdc.FirstName", "mdc.LastName"]),
                ],
                'owner_email' => [
                    Filter::buildEmailField('owr.Email'),
                    Filter::buildEmailField('owr2.Email'),
                    Filter::buildEmailField('mpc.Email'),
                    Filter::buildEmailField('mdc.Email'),
                ],
                'speaker' => [
                    Filter::buildConcatStringFields(["ps3.FirstName", "ps3.LastName"]),
                    "ps3.FirstName :operator :value",
                    "ps3.LastName :operator :value",
                    Filter::buildConcatStringFields(["mps3.FirstName", "mps3.Surname"]),
                    "mps3.FirstName :operator :value",
                    "mps3.Surname :operator :value )",
                    Filter::buildConcatStringFields(["ps4.FirstName", "ps4.LastName"]),
                    "ps4.FirstName :operator :value",
                    "ps4.LastName :operator :value",
                    Filter::buildConcatStringFields(["mps4.FirstName", "mps4.Surname"]),
                    "mps4.FirstName :operator :value",
                    "mps4.Surname :operator :value",
                    Filter::buildConcatStringFields(["ps2.FirstName", "ps2.LastName"]),
                    "ps2.FirstName :operator :value",
                    "ps2.LastName :operator :value",
                    Filter::buildConcatStringFields(["ps1.FirstName", "ps1.LastName"]),
                    "ps1.FirstName :operator :value",
                    "ps1.LastName :operator :value"
                ],
                'speaker_email' => [
                    Filter::buildEmailField('mps1.Email'),
                    Filter::buildEmailField('mps2.Email'),
                    Filter::buildEmailField('mps3.Email'),
                    Filter::buildEmailField('mps4.Email'),
                    Filter::buildEmailField('rrps1.Email'),
                    Filter::buildEmailField('rrps2.Email'),
                    Filter::buildEmailField('rrps3.Email'),
                    Filter::buildEmailField('rrps4.Email')
                ],
                'sponsor_company_name' => [
                    "spn1.Name :operator :value",
                    "spn2.Name :operator :value"
                ],
                'sponsor_id' => [
                    "sp1.Id :operator :value",
                    "sp2.Id :operator :value"
                ],
                'contact_email' => [
                    "spnpc.ContactEmail :operator :value",
                    "spndc.ContactEmail :operator :value"
                ],
                'tier_name' => [
                    "sp1stt.Name :operator :value",
                    "sp2stt.Name :operator :value"
                ],
            ]);

            if (!empty($where_conditions)) {
                $extra_filters .= " AND {$where_conditions}";
                $bindings = array_merge($bindings, $filter->getSQLBindings());
            }
        }

        if (!is_null($order)) {
            $extra_orders = $order->toRawSQL([
                'id'   => 'Id',
                'code' => 'Code',
                'redeemed' => 'REDEEMED_ORDER',
                'email_sent'    => 'EMAIL_SENT_ORDER',
                'quantity_available' => 'pc.QuantityAvailable',
                'quantity_used' => 'pc.QuantityUsed',
                'tier_name' => 'TIER_NAME_ORDER',
                'sponsor_company_name' => 'SPONSOR_COMPANY_NAME_ORDER',
                'allows_to_delegate' => 'pc.AllowsToDelegate',
            ]);
        }

        $query_from = <<<SQL
FROM SummitRegistrationPromoCode pc
LEFT JOIN SummitRegistrationDiscountCode dc ON pc.ID = dc.ID
LEFT JOIN SpeakerSummitRegistrationPromoCode spkpc ON pc.ID = spkpc.ID
LEFT JOIN SpeakerSummitRegistrationDiscountCode spkdc ON pc.ID = spkdc.ID
LEFT JOIN MemberSummitRegistrationPromoCode mpc ON pc.ID = mpc.ID
LEFT JOIN MemberSummitRegistrationDiscountCode mdc ON pc.ID = mdc.ID
LEFT JOIN SponsorSummitRegistrationPromoCode spnpc ON pc.ID = spnpc.ID
LEFT JOIN SponsorSummitRegistrationDiscountCode spndc ON pc.ID = spndc.ID
LEFT JOIN SpeakersSummitRegistrationPromoCode spkspc ON pc.ID = spkspc.ID
LEFT JOIN SpeakersRegistrationDiscountCode spksdc ON pc.ID = spksdc.ID
LEFT JOIN PrePaidSummitRegistrationPromoCode pppc ON pc.ID = pppc.ID
LEFT JOIN PrePaidSummitRegistrationDiscountCode ppdc ON pc.ID = ppdc.ID
LEFT JOIN AssignedPromoCodeSpeaker aspkrdc ON spksdc.ID = aspkrdc.RegistrationPromoCodeID
LEFT JOIN AssignedPromoCodeSpeaker aspkrpc ON spkspc.ID = aspkrpc.RegistrationPromoCodeID
LEFT JOIN PresentationSpeaker ps1 ON aspkrdc.SpeakerID = ps1.ID
LEFT JOIN PresentationSpeaker ps2 ON aspkrpc.SpeakerID = ps2.ID
LEFT JOIN PresentationSpeaker ps3 ON spkpc.SpeakerID = ps3.ID
LEFT JOIN PresentationSpeaker ps4 ON spkdc.SpeakerID = ps4.ID
LEFT JOIN `Member` mps1 ON ps1.MemberID = mps1.ID
LEFT JOIN `Member` mps2 ON ps2.MemberID = mps2.ID
LEFT JOIN `Member` mps3 ON ps3.MemberID = mps3.ID
LEFT JOIN `Member` mps4 ON ps4.MemberID = mps4.ID
LEFT JOIN `Member` ct ON pc.CreatorID = ct.ID
LEFT JOIN `Member` owr ON mpc.OwnerID = owr.ID
LEFT JOIN `Member` owr2 ON mdc.OwnerID = owr2.ID
LEFT JOIN SpeakerRegistrationRequest rrps1 ON ps1.RegistrationRequestID = rrps1.ID
LEFT JOIN SpeakerRegistrationRequest rrps2 ON ps2.RegistrationRequestID = rrps2.ID
LEFT JOIN SpeakerRegistrationRequest rrps3 ON ps3.RegistrationRequestID = rrps3.ID
LEFT JOIN SpeakerRegistrationRequest rrps4 ON ps4.RegistrationRequestID = rrps4.ID
LEFT JOIN Sponsor sp1  ON spnpc.SponsorID = sp1.ID
LEFT JOIN SummitSponsorship sp1s OM sp1s.SponsorID = sp1.ID 
LEFT JOIN Summit_SponsorshipType sp1st ON sp1s.TypeID = sp1st.ID
LEFT JOIN SponsorshipType sp1stt ON sp1st.SponsorshipTypeID = sp1stt.ID
LEFT JOIN Company spn1 ON sp1.CompanyID = spn1.ID
LEFT JOIN Sponsor sp2  ON spndc.SponsorID = sp2.ID
LEFT JOIN SummitSponsorship sp2s ON sp2s.SponsorID = sp2.ID
LEFT JOIN Summit_SponsorshipType sp2st ON sp2s.TypeID = sp2st.ID
LEFT JOIN SponsorshipType sp2stt ON sp2st.SponsorshipTypeID = sp2stt.ID
LEFT JOIN Company spn2 ON sp2.CompanyID = spn2.ID
LEFT JOIN SummitRegistrationPromoCode_Tags pct ON pct.SummitRegistrationPromoCodeID = pc.ID
LEFT JOIN Tag t ON pct.TagID = t.ID
SQL;

        $query_count = <<<SQL
SELECT COUNT(DISTINCT(pc.ID)) AS QTY
{$query_from}
{$extra_filters}
SQL;

        $stm = $this->getEntityManager()->getConnection()->executeQuery($query_count, $bindings);

        $total = intval($stm->fetchOne());

        $limit = $paging_info->getPerPage();
        $offset = $paging_info->getOffset();

        $query = <<<SQL
SELECT pc.ID, pc.Code, CASE 
WHEN pc.ClassName = 'SpeakersSummitRegistrationPromoCode' THEN COUNT(NOT ISNULL(aspkrpc.RedeemedAt)) - SUM(NOT ISNULL(aspkrpc.RedeemedAt)) = 0
WHEN pc.ClassName = 'SpeakersRegistrationDiscountCode' THEN COUNT(NOT ISNULL(aspkrdc.RedeemedAt)) - SUM(NOT ISNULL(aspkrdc.RedeemedAt)) = 0
ELSE pc.redeemed 
END AS REDEEMED_ORDER,
CASE 
WHEN pc.ClassName = 'SponsorSummitRegistrationPromoCode' THEN sp1stt.Name
WHEN pc.ClassName = 'SponsorSummitRegistrationDiscountCode' THEN sp2stt.Name
ELSE NULL 
END AS TIER_NAME_ORDER,
CASE 
WHEN pc.ClassName = 'SponsorSummitRegistrationPromoCode' THEN spn1.Name
WHEN pc.ClassName = 'SponsorSummitRegistrationDiscountCode' THEN spn2.Name
ELSE NULL 
END AS SPONSOR_COMPANY_NAME_ORDER,
CASE 
WHEN pc.SentDate IS NOT NULL THEN 1
ELSE 0
END AS EMAIL_SENT_ORDER
{$query_from}
{$extra_filters} 
GROUP BY pc.ID, pc.Code
{$extra_orders} LIMIT {$limit} OFFSET {$offset};
SQL;

        $res = $this->getEntityManager()->getConnection()->executeQuery($query, $bindings);

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
     * @param Summit $summit
     * @param PagingInfo $paging_info
     * @param Filter|null $filter
     * @param Order|null $order
     * @return mixed
     * @throws \Doctrine\DBAL\Exception
     */
    public function getBySummit
    (
        Summit $summit,
        PagingInfo $paging_info,
        Filter $filter = null,
        Order $order   = null
    )
    {
        $page = $this->getIdsBySummit($summit, $paging_info, $filter, $order);
        $total = $page->getTotal();

        $data = $this->getEntityManager()->createQueryBuilder()
            ->select("pc, FIELD(pc.id, :ids) AS HIDDEN ids")
            ->from($this->getBaseEntity(), "pc")
            ->where("pc.id in (:ids)")
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

    /**
     * @param Summit $summit
     * @return array
     */
    public function getMetadata(Summit $summit)
    {
        return [
            SummitRegistrationPromoCode::getMetadata(),
            SummitRegistrationDiscountCode::getMetadata(),
            MemberSummitRegistrationPromoCode::getMetadata(),
            MemberSummitRegistrationDiscountCode::getMetadata(),
            SponsorSummitRegistrationPromoCode::getMetadata(),
            SponsorSummitRegistrationDiscountCode::getMetadata(),
            SpeakersSummitRegistrationPromoCode::getMetadata(),
            SpeakersRegistrationDiscountCode::getMetadata(),
            PrePaidSummitRegistrationPromoCode::getMetadata(),
            PrePaidSummitRegistrationDiscountCode::getMetadata()
        ];
    }

    /**
     * @param Summit $sumit
     * @param array $codes
     * @return SummitRegistrationPromoCode|null
     */
    public function getByValuesExclusiveLock(Summit $summit, array $codes)
    {
        $query  =   $this->getEntityManager()
            ->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e")
            ->leftJoin('e.summit', 's')
            ->where("s.id = :summit_id")
            ->andWhere("e.code in (:codes)");

        $query->setParameter("summit_id", $summit->getId());
        $query->setParameter("codes", $codes);
        return $query->getQuery()
            ->setLockMode(\Doctrine\DBAL\LockMode::PESSIMISTIC_WRITE)
            ->setHint(\Doctrine\ORM\Query::HINT_REFRESH, true)
            ->getResult();
    }

    /**
     * @param Summit $summit
     * @param string $code
     * @return SummitRegistrationPromoCode|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function getByValueExclusiveLock(Summit $summit, string $code): ?SummitRegistrationPromoCode
    {
        $query = $this->getEntityManager()
            ->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e")
            ->leftJoin('e.summit', 's')
            ->where("s.id = :summit_id")
            ->andWhere("e.code = :code");

        $query->setParameter("code", strtoupper(trim($code)));
        $query->setParameter("summit_id", $summit->getId());

        return $query->getQuery()
            ->setLockMode(\Doctrine\DBAL\LockMode::PESSIMISTIC_WRITE)
            ->setHint(\Doctrine\ORM\Query::HINT_REFRESH, true)
            ->getOneOrNullResult();
    }

    /**
     * @param Summit $summit
     * @param string $code
     * @return SummitRegistrationPromoCode|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getBySummitAndCode(Summit $summit, string $code):?SummitRegistrationPromoCode{
        $query = $this->getEntityManager()
            ->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e")
            ->leftJoin('e.summit', 's')
            ->where("s.id = :summit_id")
            ->andWhere("e.code = :code");

        $query->setParameter("code", trim($code));
        $query->setParameter("summit_id", $summit->getId());

        return $query->getQuery()
            ->setHint(\Doctrine\ORM\Query::HINT_REFRESH, true)
            ->getOneOrNullResult();
    }
}