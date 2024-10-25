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

use App\Models\Foundation\Summit\ProposedSchedule\SummitProposedScheduleAllowedDay;
use App\Models\Foundation\Summit\Repositories\ISummitProposedScheduleAllowedDayRepository;
use App\Repositories\SilverStripeDoctrineRepository;
use Doctrine\ORM\QueryBuilder;
use utils\Filter;
use utils\Order;

/**
 * Class DoctrineSummitProposedScheduleAllowedDayRepository
 * @package App\Repositories\Summit
 */
final class DoctrineSummitProposedScheduleAllowedDayRepository
    extends SilverStripeDoctrineRepository
    implements ISummitProposedScheduleAllowedDayRepository
{

    protected function getBaseEntity()
    {
        return SummitProposedScheduleAllowedDay::class;
    }

    /**
     * @param QueryBuilder $query
     * @param Filter|null $filter
     * @return QueryBuilder
     */
    protected function applyExtraJoins(QueryBuilder $query, ?Filter $filter = null, ?Order $order = null)
    {
        return $query->innerJoin('e.allowed_location', 'al')
            ->innerJoin('al.location', 'l')
            ->innerJoin('al.track', 't');
    }

    protected function getFilterMappings()
    {
        return [
            'allowed_location_id' => 'al.id:json_int',
            'track_id'    => 't.id:json_int',
            'location_id' => 'l.id:json_int',
            'day'  => 'e.day:json_int',
            'opening_hour'  => 'e.opening_hour:json_int',
            'closing_hour'   => 'e.closing_hour:json_int',
        ];
    }

    /**
     * @return array
     */
    protected function getOrderMappings(): array
    {
        return [
            'id' => 'e.id',
            'day'  => 'e.day',
            'opening_hour' => 'e.opening_hour',
            'closing_hour'  => 'e.closing_hour',
            'allowed_location_id' => 'al.id',
            'location_id' => 'l.id',
            'track_id' => 't.id'
        ];
    }
}