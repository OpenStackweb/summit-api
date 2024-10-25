<?php namespace repositories\main;
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

use App\Models\Foundation\Main\Repositories\ISupportingCompanyRepository;
use App\Repositories\SilverStripeDoctrineRepository;
use Doctrine\ORM\QueryBuilder;
use models\main\SupportingCompany;
use utils\Filter;
use utils\Order;

/**
 * Class DoctrineSupportingCompanyRepository
 * @package App\Repositories\Main
 */
final class DoctrineSupportingCompanyRepository
    extends SilverStripeDoctrineRepository
    implements ISupportingCompanyRepository
{

    /**
     * @return array
     */
    protected function getFilterMappings()
    {
        return [
            'name' => 'c.name',
            'sponsorship_type_id' => 'st.id',
            'sponsorship_type_slug' => 'st.slug',
            'sponsored_project_id' => 'sp.id',
            'sponsored_project_slug' => 'sp.slug',
        ];
    }

    /**
     * @return array
     */
    protected function getOrderMappings()
    {
        return [
            'name' => 'c.name',
            'order' => 'e.order',
        ];
    }

    /**
     * @param QueryBuilder $query
     * @return QueryBuilder
     */
    protected function applyExtraJoins(QueryBuilder $query, ?Filter $filter = null, ?Order $order = null)
    {
        $query = $query->innerJoin("e.sponsorship_type", "st");
        $query = $query->innerJoin("e.company", "c");
        $query = $query->innerJoin("st.sponsored_project", "sp");
        return $query;
    }

    /**
     * @inheritDoc
     */
    protected function getBaseEntity()
    {
        return SupportingCompany::class;
    }
}