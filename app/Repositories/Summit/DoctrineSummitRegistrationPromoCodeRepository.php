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
use App\Repositories\SilverStripeDoctrineRepository;
use Doctrine\ORM\Query\Expr\Join;
use models\summit\ISummitRegistrationPromoCodeRepository;
use models\summit\MemberSummitRegistrationDiscountCode;
use models\summit\MemberSummitRegistrationPromoCode;
use models\summit\SpeakerSummitRegistrationDiscountCode;
use models\summit\SpeakerSummitRegistrationPromoCode;
use models\summit\SponsorSummitRegistrationDiscountCode;
use models\summit\SponsorSummitRegistrationPromoCode;
use models\summit\Summit;
use models\summit\SummitRegistrationDiscountCode;
use models\summit\SummitRegistrationPromoCode;
use utils\DoctrineFilterMapping;
use utils\DoctrineInstanceOfFilterMapping;
use utils\Filter;
use utils\Order;
use utils\PagingInfo;
use utils\PagingResponse;
use Doctrine\ORM\Tools\Pagination\Paginator;
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

    protected function getFilterMappings()
    {
        return [
            'code'          => 'pc.code:json_string',
            'description'   => 'pc.description:json_string',
            'sponsor' => new DoctrineFilterMapping
            (
                "(spnr.name :operator :value) OR (spnr2.name :operator :value)"
            ),
            'creator'       => new DoctrineFilterMapping
            (
                "( concat(ct.first_name, ' ', ct.last_name) :operator :value ".
                "OR ct.first_name :operator :value ".
                "OR ct.last_name :operator :value ) "
            ),
            'creator_email' => new DoctrineFilterMapping
            (
                "(ct.email :operator :value)"
            ),
            'owner'       => new DoctrineFilterMapping
            (
                "( concat(owr.first_name, ' ', owr.last_name) :operator :value ".
                "OR owr.first_name :operator :value ".
                "OR owr.last_name :operator :value ) ".
                "OR ( concat(owr2.first_name, ' ', owr2.last_name) :operator :value ".
                "OR owr2.first_name :operator :value ".
                "OR owr2.last_name :operator :value ) "
            ),
            'owner_email' => new DoctrineFilterMapping
            (
                "(owr.email :operator :value) ".
                "OR (owr2.email :operator :value) "
            ),
            'speaker'       => new DoctrineFilterMapping
            (
                "( concat(spkr.first_name, ' ', spkr.last_name) :operator :value ".
                "OR concat(spmm.first_name, ' ', spmm.last_name) :operator :value ".
                "OR spkr.first_name :operator :value ".
                "OR spkr.last_name :operator :value ".
                "OR spmm.first_name :operator :value ".
                "OR spmm.last_name :operator :value ) ".
                "OR ( concat(spkr2.first_name, ' ', spkr2.last_name) :operator :value ".
                "OR concat(spmm2.first_name, ' ', spmm2.last_name) :operator :value ".
                "OR spkr2.first_name :operator :value ".
                "OR spkr2.last_name :operator :value ".
                "OR spmm2.first_name :operator :value ".
                "OR spmm2.last_name :operator :value ) "
            ),
            'speaker_email' => new DoctrineFilterMapping
            (
                "(sprr.email :operator :value OR spmm.email :operator :value) ".
                "OR (sprr2.email :operator :value OR spmm2.email :operator :value) "
            ),
            'type' => new DoctrineFilterMapping
            (
                "(mpc.type :operator :value OR spkpc.type :operator :value) ".
                        " OR (mdc.type :operator :value OR spkdc.type :operator :value) "
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
               ]
           )
        ];
    }

    /**
     * @return array
     */
    protected function getOrderMappings()
    {
        return [
            'code' => 'pc.code',
            'id'   => 'pc.id',
        ];
    }

    /**
     * @param Summit $summit
     * @param PagingInfo $paging_info
     * @param Filter|null $filter
     * @param Order|null $order
     * @return mixed
     */
    public function getBySummit
    (
        Summit $summit,
        PagingInfo $paging_info,
        Filter $filter = null,
        Order $order   = null
    )
    {
        $query  =  $this->getEntityManager()
                    ->createQueryBuilder()
                    ->select("pc")
                    ->from($this->getBaseEntity(), "pc")
                    ->leftJoin(SummitRegistrationDiscountCode::class, 'dc', 'WITH', 'pc.id = dc.id')
                    ->leftJoin(MemberSummitRegistrationDiscountCode::class, 'mdc', 'WITH', 'pc.id = mdc.id')
                    ->leftJoin(SpeakerSummitRegistrationDiscountCode::class, 'spkdc', 'WITH', 'pc.id = spkdc.id')
                    ->leftJoin(SponsorSummitRegistrationDiscountCode::class, 'spdc', 'WITH', 'pc.id = spdc.id')
                    ->leftJoin(MemberSummitRegistrationPromoCode::class, 'mpc', 'WITH', 'pc.id = mpc.id')
                    ->leftJoin(SponsorSummitRegistrationPromoCode::class, 'spc', 'WITH', 'mpc.id = spc.id')
                    ->leftJoin(SpeakerSummitRegistrationPromoCode::class, 'spkpc', 'WITH', 'spkpc.id = pc.id')
                    ->leftJoin('pc.summit', 's')
                    ->leftJoin('pc.creator', 'ct')
                    ->leftJoin("spkpc.speaker", "spkr")
                    ->leftJoin("spkdc.speaker", "spkr2")
                    ->leftJoin('spkr.member', "spmm", Join::LEFT_JOIN)
                    ->leftJoin('spkr2.member', "spmm2", Join::LEFT_JOIN)
                    ->leftJoin('spkr.registration_request', "sprr", Join::LEFT_JOIN)
                    ->leftJoin('spkr2.registration_request', "sprr2", Join::LEFT_JOIN)
                    ->leftJoin("mpc.owner", "owr")
                    ->leftJoin("mdc.owner", "owr2")
                    ->leftJoin("spc.sponsor", "spnr")
                    ->leftJoin("spdc.sponsor", "spnr2")
                    ->where("s.id = :summit_id");

        $query->setParameter("summit_id", $summit->getId());

        if(!is_null($filter)){
            $filter->apply2Query($query, $this->getFilterMappings());
        }

        if (!is_null($order)) {
            $order->apply2Query($query, $this->getOrderMappings());
        } else {
            //default order
            $query = $query->addOrderBy("pc.code",'ASC');
        }

        $query = $query
        ->setFirstResult($paging_info->getOffset())
        ->setMaxResults($paging_info->getPerPage());

        $paginator = new Paginator($query, $fetchJoinCollection = true);
        $total     = $paginator->count();
        $data      = [];

        foreach($paginator as $entity)
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
     * @param Summit $summit
     * @return array
     */
    public function getMetadata(Summit $summit)
    {
       return [
           SummitRegistrationPromoCode::getMetadata(),
           SummitRegistrationDiscountCode::getMetadata(),
           MemberSummitRegistrationPromoCode::getMetadata(),
           SpeakerSummitRegistrationPromoCode::getMetadata(),
           SponsorSummitRegistrationPromoCode::getMetadata(),
           SponsorSummitRegistrationDiscountCode::getMetadata(),
           SpeakerSummitRegistrationDiscountCode::getMetadata(),
           MemberSummitRegistrationDiscountCode::getMetadata(),
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
        $query  =   $this->getEntityManager()
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
}