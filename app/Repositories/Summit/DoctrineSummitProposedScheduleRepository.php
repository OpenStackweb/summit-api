<?php namespace App\Repositories\Summit;
/**
 * Copyright 2023 OpenStack Foundation
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

use App\Models\Foundation\Summit\IPublishableEvent;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\Expr\Join;
use Dotenv\Exception\ValidationException;
use models\summit\ISummitProposedScheduleRepository;
use App\Repositories\SilverStripeDoctrineRepository;
use models\summit\SummitProposedSchedule;
use models\summit\SummitProposedScheduleSummitEvent;

/**
 * Class DoctrineSummitProposedScheduleRepository
 * @package App\Repositories\Summit
 */
final class DoctrineSummitProposedScheduleRepository
    extends SilverStripeDoctrineRepository
    implements ISummitProposedScheduleRepository
{
    /**
     * @return string
     */
    protected function getBaseEntity()
    {
       return SummitProposedSchedule::class;
    }

    /**
     *@inheritDoc
     */
    public function getBySourceAndSummitId(string $source, int $summit_id): ?SummitProposedSchedule
    {
        $query = $this->getEntityManager()->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e")
            ->where('e.source = :source')
            ->andWhere('e.summit = :summit_id')
            ->setParameter('source', $source)
            ->setParameter('summit_id', $summit_id);

        try {
            return $query->getQuery()->getSingleResult();
        } catch (NoResultException|NonUniqueResultException $e) {
            return null;
        }
    }

    /**
     *@inheritDoc
     */
    public function getPublishedOnSameTimeFrame(IPublishableEvent $event): array
    {
        if (!$event instanceof SummitProposedScheduleSummitEvent)
            throw new ValidationException(
                "Event id {$event->getId()} is not a valid schedule event.");

        $end_date = $event->getEndDate();
        $start_date = $event->getStartDate();

        $query = $this->getEntityManager()->createQueryBuilder()
            ->select("e")
            ->from(\models\summit\SummitProposedScheduleSummitEvent::class, "e")
            ->join('e.summit_proposed_schedule', 's', Join::WITH, " s.id = :schedule_id")
            ->where('e.start_date < :end_date')
            ->andWhere('e.end_date > :start_date')
            ->setParameter('schedule_id', $event->getSchedule()->getId())
            ->setParameter('start_date', $start_date)
            ->setParameter('end_date', $end_date);

        return $query->getQuery()->getResult();
    }
}