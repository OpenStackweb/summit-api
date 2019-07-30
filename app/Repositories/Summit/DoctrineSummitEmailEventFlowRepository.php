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
use App\Models\Foundation\Summit\EmailFlows\SummitEmailEventFlow;
use App\Models\Foundation\Summit\Repositories\ISummitEmailEventFlowRepository;
use App\Repositories\SilverStripeDoctrineRepository;
use Doctrine\ORM\QueryBuilder;
use utils\DoctrineLeftJoinFilterMapping;

/**
 * Class DoctrineSummitEmailEventFlowRepository
 * @package App\Repositories\Summit
 */
class DoctrineSummitEmailEventFlowRepository
    extends SilverStripeDoctrineRepository
    implements ISummitEmailEventFlowRepository
{

    /**
     * @inheritDoc
     */
    protected function getBaseEntity()
    {
        return SummitEmailEventFlow::class;
    }

    /**
     * @param QueryBuilder $query
     * @return QueryBuilder
     */
    protected function applyExtraJoins(QueryBuilder $query){
        $query = $query->join('e.event_type', 'et')
            ->join('et.flow', 'f');
        return $query;
    }

    /**
     * @return array
     */
    protected function getFilterMappings()
    {
        return [
            'email_template_identifier' => 'e.email_template_identifier:json_string',
            'event_type_name' =>  'et.name:json_string',
            'flow_name' =>  'f.name:json_string',
            'summit_id'   => new DoctrineLeftJoinFilterMapping("e.summit", "s" ,"s.id :operator :value")
        ];
    }

    /**
     * @return array
     */
    protected function getOrderMappings()
    {
        return [
            'id'   => 'e.id',
            'email_template_identifier' => 'e.email_template_identifier',
        ];
    }

}