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

use App\Models\Foundation\Summit\Events\Presentations\TrackChairs\PresentationTrackChairRatingType;
use App\Models\Foundation\Summit\Repositories\IPresentationTrackChairRatingTypeRepository;
use App\Repositories\SilverStripeDoctrineRepository;
use Doctrine\ORM\QueryBuilder;
use utils\Filter;

/**
 * Class DoctrinePresentationTrackChairRatingTypeRepository
 * @package App\Repositories\Summit
 */
final class DoctrinePresentationTrackChairRatingTypeRepository
    extends SilverStripeDoctrineRepository
    implements IPresentationTrackChairRatingTypeRepository
{

    /**
     * @return string
     */
    protected function getBaseEntity()
    {
        return PresentationTrackChairRatingType::class;
    }

    /**
     * @param QueryBuilder $query
     * @return QueryBuilder
     */
    protected function applyExtraJoins(QueryBuilder $query, ?Filter $filter = null)
    {
        if($filter->hasFilter('selection_plan_id'))
            $query->join('e.selection_plan', 'sp');
        return $query;
    }


    protected function getFilterMappings()
    {
        return [
            'selection_plan_id' => 'sp.id',
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
            'order' => 'e.order',
            'name' => 'e.name'
        ];
    }
}