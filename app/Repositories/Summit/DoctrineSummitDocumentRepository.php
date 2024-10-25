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
use App\Models\Foundation\Summit\Repositories\ISummitDocumentRepository;
use App\Repositories\SilverStripeDoctrineRepository;
use Doctrine\ORM\QueryBuilder;
use models\summit\SummitDocument;
use utils\DoctrineLeftJoinFilterMapping;
use utils\Filter;
use utils\Order;

/**
 * Class DoctrineSummitDocumentRepository
 * @package App\Repositories\Summit
 */
class DoctrineSummitDocumentRepository extends SilverStripeDoctrineRepository
implements ISummitDocumentRepository
{
    /**
     * @inheritDoc
     */
    protected function getBaseEntity()
    {
        return SummitDocument::class;
    }

    /**
     * @param QueryBuilder $query
     * @return QueryBuilder
     */
    protected function applyExtraJoins(QueryBuilder $query, ?Filter $filter = null, ?Order $order = null){

        $query = $query->join('e.summit', 's')
            ->leftJoin('e.event_types', 'et');
        if($filter->hasFilter("selection_plan_id")){
            $query = $query->leftJoin("e.selection_plan", "sp");
        }
        return $query;
    }

    /**
     * @return array
     */
    protected function getFilterMappings()
    {
        return [
            'name' => 'e.name:json_string',
            'description' => 'e.description:json_string',
            'label' => 'e.label:json_string',
            'event_type' =>  'et.type:json_string',
            'selection_plan_id' => 'sp.id',
            'summit_id' => new DoctrineLeftJoinFilterMapping("e.summit", "s" ,"s.id :operator :value")
        ];
    }

    /**
     * @return array
     */
    protected function getOrderMappings()
    {
        return [
            'id'   => 'e.id',
            'name' => 'e.name',
            'label' => 'e.label',
        ];
    }
}