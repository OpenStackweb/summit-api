<?php namespace App\Repositories\Summit;
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
use App\Models\Foundation\Summit\Repositories\ISummitTrackChairRepository;
use App\Repositories\SilverStripeDoctrineRepository;
use Doctrine\ORM\QueryBuilder;
use models\summit\SummitTrackChair;
/**
 * Class DoctrineSummitTrackChairRepository
 * @package App\Repositories\Summit
 */
final class DoctrineSummitTrackChairRepository
    extends SilverStripeDoctrineRepository
    implements ISummitTrackChairRepository
{

    /**
     * @inheritDoc
     */
    protected function getBaseEntity()
    {
        return SummitTrackChair::class;
    }

    /**
     * @param QueryBuilder $query
     * @return QueryBuilder
     */
    protected function applyExtraJoins(QueryBuilder $query)
    {
        $query
            ->join('e.summit', 's')
            ->leftJoin('e.member', 'm')
            ->leftJoin('e.categories', 'cat');
        return $query;
    }

    protected function getFilterMappings()
    {
        return [
            'summit_id' => 's.id',
            'member_id' => 'm.id',
            'track_id' => 'cat.id',
            'member_first_name' => "m.first_name :operator :value",
            'member_last_name' => "m.last_name :operator :value",
            'member_full_name' => "concat(m.first_name, ' ', m.last_name) :operator :value",
            'member_email' => "m.email :operator :value"
        ];
    }

    /**
     * @return array
     */
    protected function getOrderMappings()
    {
        return [
            'id' => 'e.id',
            'track_id' => 'cat.id',
            "member_full_name" => "LOWER(CONCAT(m.first_name, ' ', m.last_name))",
            'member_first_name' => 'm.first_name',
            'member_last_name' => 'm.last_name',
        ];
    }

}