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
use Doctrine\ORM\QueryBuilder;
use Illuminate\Support\Facades\Log;
use models\summit\ISummitRepository;
use models\summit\Summit;
use App\Repositories\SilverStripeDoctrineRepository;
use utils\DoctrineHavingFilterMapping;
use utils\Filter;
use utils\Order;
use utils\PagingInfo;
use utils\PagingResponse;

/**
 * Class DoctrineSummitRepository
 * @package App\Repositories\Summit
 */
final class DoctrineSummitRepository
    extends SilverStripeDoctrineRepository
    implements ISummitRepository
{

    /**
     * @return array
     */
    protected function getFilterMappings()
    {
        return [
            'name' => 'e.name',
            'start_date' => Filter::buildDateTimeEpochField('e.begin_date'),
            'end_date' => Filter::buildDateTimeEpochField('e.end_date'),
            'submission_begin_date' => Filter::buildDateTimeEpochField('sp.submission_begin_date'),
            'submission_end_date' => Filter::buildDateTimeEpochField('sp.submission_end_date'),
            'voting_begin_date' => Filter::buildDateTimeEpochField('sp.voting_begin_date'),
            'voting_end_date' => Filter::buildDateTimeEpochField('sp.voting_end_date'),
            'selection_begin_date' => Filter::buildDateTimeEpochField('sp.selection_begin_date'),
            'selection_end_date' => Filter::buildDateTimeEpochField('sp.selection_end_date'),
            'selection_plan_enabled' => Filter::buildIntField( 'sp.is_enabled'),
            'registration_begin_date' => Filter::buildDateTimeEpochField('e.registration_begin_date'),
            'registration_end_date' => Filter::buildDateTimeEpochField('e.registration_end_date'),
            'available_on_api' => Filter::buildBooleanField('e.available_on_api'),
            'summit_id' => Filter::buildIntField('e.id'),
            'ticket_types_count' => new DoctrineHavingFilterMapping("", "tt.summit", "count(tt.id) :operator :value"),
            'begin_allow_booking_date' => Filter::buildDateTimeEpochField('e.begin_allow_booking_date'),
            'end_allow_booking_date' => Filter::buildDateTimeEpochField('e.end_allow_booking_date'),
            'mark_as_deleted' => Filter::buildBooleanField('e.mark_as_deleted'),
        ];
    }

    /**
     * @return array
     */
    protected function getOrderMappings()
    {
        return [
            'id' => 'e.id',
            'name' => 'e.name',
            'start_date' => 'e.begin_date',
            'registration_begin_date' => 'e.registration_begin_date',
        ];
    }

    /**
     * @param QueryBuilder $query
     * @return QueryBuilder
     */
    protected function applyExtraFilters(QueryBuilder $query)
    {
        return $query;
    }

    /**
     * @param QueryBuilder $query
     * @return QueryBuilder
     */
    protected function applyExtraJoins(QueryBuilder $query, ?Filter $filter = null, ?Order $order = null)
    {
        $query = $query->leftJoin("e.ticket_types", "tt");
        $query = $query->leftJoin("e.selection_plans", "sp");
        return $query;
    }

    /**
     * @return Summit
     */
    public function getCurrent()
    {
        $res = $this->getEntityManager()->createQueryBuilder()
            ->select("s")
            ->from($this->getBaseEntity(), "s")
            ->where('s.active = 1')
            ->orderBy('s.begin_date', 'DESC')
            ->getQuery()
            ->getResult();
        if (count($res) == 0) return null;
        return $res[0];
    }

    /**
     * @return Summit[]
     */
    public function getCurrentAndFutureSummits()
    {
        $now_utc = new \DateTime('now', new \DateTimeZone('UTC'));
        return $this->getEntityManager()->createQueryBuilder()
            ->select("s")
            ->from($this->getBaseEntity(), "s")
            // current
            ->where('s.begin_date <= :now and s.end_date >= :now')
            // or future
            ->orWhere('s.begin_date >= :now')
            ->orderBy('s.begin_date', 'DESC')
            ->setParameter('now', $now_utc)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Summit[]
     */
    public function getAvailables()
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select("s")
            ->from($this->getBaseEntity(), "s")
            ->where('s.available_on_api = 1')
            ->orderBy('s.begin_date', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Summit[]
     */
    public function getAllOrderedByBeginDate()
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select("s")
            ->from($this->getBaseEntity(), "s")
            ->orderBy('s.begin_date', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return string
     */
    protected function getBaseEntity()
    {
        return Summit::class;
    }

    /**
     * @param string $name
     * @return Summit
     */
    public function getByName($name)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select("s")
            ->from($this->getBaseEntity(), "s")
            ->where('s.name = :name')
            ->setParameter('name', $name)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param string $slug
     * @return Summit|null
     */
    public function getBySlug(string $slug): ?Summit
    {
        try {
            return $this->getEntityManager()->createQueryBuilder()
                ->select("s")
                ->from($this->getBaseEntity(), "s")
                ->where('s.slug = :slug')
                ->setParameter('slug', strtolower($slug))
                ->getQuery()
                ->getOneOrNullResult();
        } catch (\Exception $ex) {
            Log::warning($ex);
            return null;
        }
    }

    /**
     * @param string $registration_slug_prefix
     * @return Summit|null
     */
    public function getByRegistrationSlugPrefix(string $registration_slug_prefix): ?Summit
    {
        try {
            return $this->getEntityManager()->createQueryBuilder()
                ->select("s")
                ->from($this->getBaseEntity(), "s")
                ->where('s.registration_slug_prefix = :registration_slug_prefix')
                ->setParameter('registration_slug_prefix', trim($registration_slug_prefix))
                ->getQuery()
                ->getOneOrNullResult();
        } catch (\Exception $ex) {
            Log::warning($ex);
            return null;
        }
    }

    /**
     * @param string $qr_enc_key
     * @return Summit|null
     */
    public function getByQREncryptionKey(string $qr_enc_key): ?Summit
    {
        try {
            return $this->getEntityManager()->createQueryBuilder()
                ->select("s")
                ->from($this->getBaseEntity(), "s")
                ->where('s.qr_codes_enc_key = :qr_codes_enc_key')
                ->setParameter('qr_codes_enc_key', strtoupper($qr_enc_key))
                ->getQuery()
                ->getOneOrNullResult();
        } catch (\Exception $ex) {
            Log::warning($ex);
            return null;
        }
    }

    /**
     * @return Summit
     */
    public function getActive()
    {
        $res = $this->getEntityManager()->createQueryBuilder()
            ->select("s")
            ->from($this->getBaseEntity(), "s")
            ->where('s.active = 1')
            ->orderBy('s.begin_date', 'DESC')
            ->getQuery()
            ->getResult();
        if (count($res) == 0) return null;
        return $res[0];
    }

    /**
     * @return Summit
     */
    public function getCurrentAndAvailable()
    {
        $res = $this->getEntityManager()->createQueryBuilder()
            ->select("s")
            ->from($this->getBaseEntity(), 's')
            ->where('s.active = 1')
            ->andWhere('s.available_on_api = 1')
            ->orderBy('s.begin_date', 'DESC')
            ->getQuery()
            ->getResult();
        if (count($res) == 0) return null;
        return $res[0];
    }

    /**
     * @return Summit[]
     */
    public function getWithExternalFeed(): array
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e")
            ->where("e.api_feed_type is not null")
            ->andWhere("e.api_feed_type <> ''")
            ->andWhere("e.api_feed_url is not null")
            ->andWhere("e.api_feed_url <> ''")
            ->andWhere("e.api_feed_key is not null")
            ->andWhere("e.api_feed_key <>''")
            ->andWhere("e.end_date >= :now")
            ->orderBy('e.id', 'DESC')
            ->setParameter("now", new \DateTime('now', new \DateTimeZone('UTC')))
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Summit[]
     */
    public function getAllWithExternalRegistrationFeed(): array
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e")
            ->where("e.api_feed_type is not null")
            ->andWhere("e.external_registration_feed_type <> ''")
            ->andWhere("e.external_registration_feed_type is not null")
            ->andWhere("e.external_registration_feed_api_key <> ''")
            ->andWhere("e.external_registration_feed_api_key is not null")
            ->andWhere("e.external_summit_id <> ''")
            ->andWhere("e.external_summit_id is not null")
            ->andWhere("e.end_date >= :now")
            ->orderBy('e.id', 'DESC')
            ->setParameter("now", new \DateTime('now', new \DateTimeZone('UTC')))
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array
     */
    public function getNotEnded(): array
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e")
            ->where("e.end_date >= :now")
            ->orderBy('e.id', 'DESC')
            ->setParameter("now", new \DateTime('now', new \DateTimeZone('UTC')))
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getOnGoing(): array
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e")
            ->where("e.begin_date <= :now")
            ->andWhere("e.end_date >= :now")
            ->orderBy('e.id', 'DESC')
            ->setParameter("now", new \DateTime('now', new \DateTimeZone('UTC')))
            ->getQuery()
            ->getResult();
    }

    /**
     * @param Summit $summit
     * @param PagingInfo $paging_info
     * @param Filter|null $filter
     * @param Order|null $order
     * @return PagingResponse
     * @throws \Doctrine\DBAL\Exception
     */
    public function getRegistrationCompanies(Summit $summit, PagingInfo $paging_info, Filter $filter = null, Order $order = null):PagingResponse
    {
        $extra_filters = '';
        $extra_orders  = '';
        $bindings      = [];

        if(!is_null($filter))
        {
            $where_conditions = $filter->toRawSQL([
                'name'  => 'Name',
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
                'name'  => 'Name',
            ));
        }

        $query_count = <<<SQL
SELECT COUNT(DISTINCT(ID)) AS QTY
FROM (
	SELECT C.*
	FROM Summit_RegistrationCompanies SC
	INNER JOIN Company C ON SC.CompanyID = C.ID AND SC.SummitID = {$summit->getId()}	
)
SUMMIT_COMPANIES
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
	SELECT C.*
    FROM Summit_RegistrationCompanies SC
	INNER JOIN Company C ON SC.CompanyID = C.ID AND SC.SummitID = {$summit->getId()}	
)
SUMMIT_COMPANIES
{$extra_filters} {$extra_orders} limit :per_page offset :offset;
SQL;

        $rsm = new ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addRootEntityFromClassMetadata(\models\main\Company::class, 'c');

        // build rsm here
        $native_query = $this->getEntityManager()->createNativeQuery($query, $rsm);

        foreach($bindings as $k => $v)
            $native_query->setParameter($k, $v);

        $companies = $native_query->getResult();

        $last_page = (int) ceil($total / $paging_info->getPerPage());

        return new PagingResponse($total, $paging_info->getPerPage(), $paging_info->getCurrentPage(), $last_page, $companies);
    }
}