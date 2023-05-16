<?php namespace App\Repositories\Summit;
/*
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

use App\Models\Foundation\Summit\ProposedSchedule\SummitProposedScheduleAllowedLocation;
use App\Models\Foundation\Summit\Repositories\ISummitProposedScheduleAllowedLocationRepository;
use App\Repositories\SilverStripeDoctrineRepository;
use Doctrine\ORM\QueryBuilder;
use utils\Filter;

/**
 * Class DoctrineSummitProposedScheduleAllowedLocationRepository
 * @package App\Repositories\Summit
 */
final class DoctrineSummitProposedScheduleAllowedLocationRepository
    extends SilverStripeDoctrineRepository
    implements ISummitProposedScheduleAllowedLocationRepository
{

    protected function getBaseEntity()
    {
        return SummitProposedScheduleAllowedLocation::class;
    }

    /**
     * @param QueryBuilder $query
     * @param Filter|null $filter
     * @return QueryBuilder
     */
    protected function applyExtraJoins(QueryBuilder $query, ?Filter $filter = null)
    {
        return $query->innerJoin('e.track', 't')
            ->innerJoin('e.location', 'l');
    }

    protected function getFilterMappings()
    {
        return [
            'track_id'    => 't.id:json_int',
            'location_id' => 'l.id:json_int',
        ];
    }

    /**
     * @return array
     */
    protected function getOrderMappings(): array
    {
        return [
            'location_id' => 'l.id',
        ];
    }

}