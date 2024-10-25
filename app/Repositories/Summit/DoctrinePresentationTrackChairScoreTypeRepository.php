<?php namespace App\Repositories\Summit;
/**
 * Copyright 2022 OpenStack Foundation
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

use App\Models\Foundation\Summit\Events\Presentations\TrackChairs\PresentationTrackChairScoreType;
use App\Models\Foundation\Summit\Repositories\IPresentationTrackChairScoreTypeRepository;
use App\Repositories\SilverStripeDoctrineRepository;
use Doctrine\ORM\QueryBuilder;
use utils\Filter;
use utils\Order;

/**
 * Class DoctrinePresentationScoreTypeRepository
 * @package App\Repositories\Summit
 */
final class DoctrinePresentationTrackChairScoreTypeRepository
    extends SilverStripeDoctrineRepository
    implements IPresentationTrackChairScoreTypeRepository
{

    /**
     * @return string
     */
    protected function getBaseEntity()
    {
        return PresentationTrackChairScoreType::class;
    }

    /**
     * @param QueryBuilder $query
     * @return QueryBuilder
     */
    protected function applyExtraJoins(QueryBuilder $query, ?Filter $filter = null, ?Order $order = null)
    {
        if($filter->hasFilter('type_id'))
            $query->join('e.type', 't');
        return $query;
    }


    protected function getFilterMappings()
    {
        return [
            'type_id' => 't.id',
            'name' => "e.name",
        ];
    }

    /**
     * @return array
     */
    protected function getOrderMappings()
    {
        return [
            'id'    => 'e.id',
            'score' => 'e.score',
            'name' => 'e.name',
        ];
    }
}