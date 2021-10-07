<?php namespace repositories\main;
/**
 * Copyright 2021 OpenStack Foundation
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

use App\Models\Foundation\Elections\Candidate;
use App\Models\Foundation\Elections\Election;
use App\Models\Foundation\Elections\IElectionsRepository;
use App\Repositories\SilverStripeDoctrineRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use utils\DoctrineFilterMapping;
use utils\Filter;
use utils\Order;
use utils\PagingInfo;
use utils\PagingResponse;

/**
 * Class DoctrineElectionsRepository
 * @package repositories\main
 */
final class DoctrineElectionsRepository
    extends SilverStripeDoctrineRepository implements IElectionsRepository
{

    protected function getBaseEntity()
    {
        return Election::class;
    }

    public function getCurrent(): ?Election
    {
       $e = $this->getOpen();

       if(is_null($e))
           $e = $this->getActive();

        if(is_null($e))
            $e = $this->getLatest();

        return $e;
    }

    public function getOpen():?Election{
        $now = new \DateTime("now", new \DateTimeZone("UTC"));
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e")
            ->where("e.nomination_opens <= :now and e.nomination_closes >= :now")
            ->setParameter("now", $now)
            ->orderBy("e.nomination_opens", "desc")
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getActive():?Election{
        $now = new \DateTime("now", new \DateTimeZone("UTC"));
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e")
            ->where("e.opens <= :now and e.closes >= :now")
            ->setParameter("now", $now)
            ->orderBy("e.opens", "desc")
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getLatest():?Election{
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e")
            ->orderBy("e.opens", "desc")
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    private static function getMemberFilterMappings():array{
        return [
            'first_name'        => 'm.first_name:json_string',
            'last_name'         => 'm.last_name:json_string',
            'github_user'       => 'm.github_user:json_string',
            'full_name'         => new DoctrineFilterMapping("concat(m.first_name, ' ', m.last_name) :operator :value"),
            'email'             => ['m.email:json_string', 'm.second_email:json_string', 'm.third_email:json_string'],
        ];
    }

    private static function getMemberOrderMappings():array{
        return [
            'id'          => 'm.id',
            'first_name'  => 'm.first_name',
            'last_name'   => 'm.last_name',
        ];
    }

    /**
     * @param Election $election
     * @param PagingInfo $paging_info
     * @param Filter|null $filter
     * @param Order|null $order
     * @return PagingResponse
     */
    public function getAcceptedCandidates(Election $election, PagingInfo $paging_info, Filter $filter = null, Order $order = null): PagingResponse
    {
        $query  = $this->getEntityManager()
            ->createQueryBuilder()
            ->select("c")
            ->from(Candidate::class, "c")
            ->join("c.member", "m")
            ->join("c.election", "e")
            ->where('e.id = :election_id')
            ->andWhere('c.has_accepted_nomination = :has_accepted_nomination')
            ->setParameter("election_id", $election->getId())
            ->setParameter("has_accepted_nomination", true);

        return $this->getAllAbstractByPage
        (
            $query,
            $paging_info,
            $filter,
            $order,
            self::getMemberFilterMappings(),
            self::getMemberOrderMappings()
        );
    }

    /**
     * @param Election $election
     * @param PagingInfo $paging_info
     * @param Filter|null $filter
     * @param Order|null $order
     * @return PagingResponse
     */
    public function getGoldCandidates(Election $election, PagingInfo $paging_info, Filter $filter = null, Order $order = null): PagingResponse
    {
        $query  = $this->getEntityManager()
            ->createQueryBuilder()
            ->select("c")
            ->from(Candidate::class, "c")
            ->join("c.member", "m")
            ->join("c.election", "e")
            ->where('e.id = :election_id')
            ->andWhere('c.is_gold_member = :is_gold_member')
            ->setParameter("election_id", $election->getId())
            ->setParameter("is_gold_member", true);

        return $this->getAllAbstractByPage
        (
            $query,
            $paging_info,
            $filter,
            $order,
            self::getMemberFilterMappings(),
            self::getMemberOrderMappings()
        );
    }
}