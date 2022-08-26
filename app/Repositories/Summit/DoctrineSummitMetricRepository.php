<?php namespace App\Repositories\Summit;
/**
 * Copyright 2020 OpenStack Foundation
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
use App\Models\Foundation\Summit\Repositories\ISummitMetricRepository;
use App\Repositories\SilverStripeDoctrineRepository;
use models\main\Member;
use models\summit\ISummitMetricType;
use models\summit\SummitAttendee;
use models\summit\SummitEvent;
use models\summit\SummitEventAttendanceMetric;
use models\summit\SummitMetric;
use models\summit\SummitSponsorMetric;
use models\summit\SummitVenueRoom;

/**
 * Class DoctrineSummitMetricRepository
 * @package App\Repositories\Summit
 */
final class DoctrineSummitMetricRepository
    extends SilverStripeDoctrineRepository
    implements ISummitMetricRepository
{

    /**
     * @inheritDoc
     */
    protected function getBaseEntity()
    {
        return SummitMetric::class;
    }

    /**
     * @param Member $member
     * @param string $type
     * @param int|null $source_id
     * @return SummitMetric|null
     */
    public function getNonAbandoned(Member $member, string $type, ?int $source_id = null): ?SummitMetric
    {
        $query =  $this->getEntityManager()
            ->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e")
            ->where("e.type = :type");

        if(!is_null($source_id) && $source_id > 0){
            if($type == ISummitMetricType::Event || $type == ISummitMetricType::Poster){
                $query = $query->leftJoin(SummitEventAttendanceMetric::class, 'sam', 'WITH', 'e.id = sam.id')
                    ->join("sam.event", "evt")
                    ->andWhere("evt.id = :source_id")
                    ->andWhere("sam.sub_type = :sub_type")
                    ->setParameter("source_id", $source_id)
                    ->setParameter("sub_type", SummitEventAttendanceMetric::SubTypeVirtual);
            }
            if($type == ISummitMetricType::Sponsor){
                $query = $query->leftJoin(SummitSponsorMetric::class, 'sm', 'WITH', 'e.id = sm.id')
                    ->join("sm.sponsor", "sp")
                    ->andWhere("sp.id = :source_id")
                    ->setParameter("source_id", $source_id);
            }
        }

        return $query
            ->andWhere("e.outgress_date is null")
            ->andWhere("e.member = :member")
            ->setParameter("member", $member)
            ->setParameter("type", trim($type))
            ->setMaxResults(1)
            ->orderBy('e.ingress_date', 'DESC')
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param SummitAttendee $attendee
     * @param SummitVenueRoom|null $room
     * @param SummitEvent|null $event
     * @return SummitMetric|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getNonAbandonedOnSiteMetric(SummitAttendee $attendee, ?SummitVenueRoom $room , ?SummitEvent $event): ?SummitMetric
    {
        $query =  $this->getEntityManager()
            ->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e")
            ->leftJoin(SummitEventAttendanceMetric::class, "rm", 'WITH', 'rm.id = e.id')
            ->where("rm.sub_type = :sub_type");

        $query = $query
            ->andWhere("e.outgress_date is null")
            ->andWhere("rm.attendee = :attendee")
            ->setParameter("attendee", $attendee)
            ->setParameter("sub_type", SummitEventAttendanceMetric::SubTypeOnSite);

        if(!is_null($room)){
            $query = $query->andWhere("rm.room = :room")->setParameter("room", $room);
        }

        if(!is_null($event)){
            $query = $query->andWhere("rm.event = :event")->setParameter("event", $event);
        }

        return  $query->setMaxResults(1)
        ->orderBy('e.ingress_date', 'DESC')
        ->getQuery()
        ->getOneOrNullResult();

    }
}