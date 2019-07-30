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
use App\Models\Foundation\Summit\Repositories\ISummitAttendeeBadgePrintRuleRepository;
use App\Repositories\SilverStripeDoctrineRepository;
use models\summit\SummitAttendeeBadgePrintRule;
/**
 * Class DoctrineSummitAttendeeBadgePrintRuleRepository
 * @package App\Repositories\Summit
 */
final class DoctrineSummitAttendeeBadgePrintRuleRepository
    extends SilverStripeDoctrineRepository
    implements ISummitAttendeeBadgePrintRuleRepository
{

    /**
     * @return string
     */
    protected function getBaseEntity()
    {
        return SummitAttendeeBadgePrintRule::class;
    }

    /**
     * @param array $group_ids
     * @return mixed
     */
    public function getByGroupsIds(array $group_ids)
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e")
            ->join('e.group','g')
            ->where("g.id in :group_ids")
            ->setParameter("group_ids", $group_ids)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param array $group_slugs
     * @return mixed
     */
    public function getByGroupsSlugs(array $group_slugs)
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e")
            ->join('e.group','g')
            ->where("g.code in (:group_slugs)")
            ->setParameter("group_slugs", $group_slugs)
            ->getQuery()
            ->getResult();
    }
}