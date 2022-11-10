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
use App\Models\Foundation\Summit\ExtraQuestions\SummitSelectionPlanExtraQuestionType;
use App\Models\Foundation\Summit\Repositories\ISummitSelectionPlanExtraQuestionTypeRepository;
use App\Repositories\Main\DoctrineExtraQuestionTypeRepository;
use Doctrine\ORM\QueryBuilder;
use utils\DoctrineLeftJoinFilterMapping;
use utils\Filter;

/**
 * Class DoctrineSummitSelectionPlanExtraQuestionTypeRepository
 * @package App\Repositories\Summit
 */
final class DoctrineSummitSelectionPlanExtraQuestionTypeRepository
    extends DoctrineExtraQuestionTypeRepository
    implements ISummitSelectionPlanExtraQuestionTypeRepository
{

    /**
     * @param QueryBuilder $query
     * @return QueryBuilder
     */
    protected function applyExtraJoins(QueryBuilder $query, ?Filter $filter = null){

        if(!is_null($filter) && $filter->hasFilter("selection_plan_id")){
            $query = $query->innerJoin("e.assigned_selection_plans", "a");
            $query = $query->innerJoin("a.selection_plan", "sp");
        }

        if(!is_null($filter) && $filter->hasFilter("summit_id")){
            $query = $query->innerJoin("e.summit", "s");
        }
        return $query;
    }

    /**
     * @return array
     */
    protected function getFilterMappings()
    {
        return array_merge(parent::getFilterMappings() , [
            'summit_id' => 's.id',
            'selection_plan_id' => "sp.id",
        ]);
    }

    /**
     * @return array
     */
    protected function getOrderMappings()
    {
        $args  = func_get_args();
        $filter = count($args) > 0 ? $args[0] : null;
        $mappings = [
            'id'    => 'e.id',
            'name'  => 'e.name',
            'label' => 'e.label',
        ];
        if(!is_null($filter) && $filter->hasFilter("selection_plan_id")){
           $mappings['order'] = 'sp.order';
        }
        return $mappings;
    }

    /**
     *
     * @return string
     */
    protected function getBaseEntity()
    {
        return SummitSelectionPlanExtraQuestionType::class;
    }
}