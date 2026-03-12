<?php namespace App\Repositories\Marketplace;
/**
 * Copyright 2017 OpenStack Foundation
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
use App\Models\Foundation\Marketplace\Driver;
use App\Models\Foundation\Marketplace\IDriverRepository;
use App\Repositories\SilverStripeDoctrineRepository;
use Doctrine\ORM\QueryBuilder;
use utils\DoctrineJoinFilterMapping;

/**
 * Class DoctrineDriverRepository
 * @package App\Repositories\Marketplace
 */
final class DoctrineDriverRepository
    extends SilverStripeDoctrineRepository
    implements IDriverRepository
{
    /**
     * @return string
     */
    protected function getBaseEntity()
    {
        return Driver::class;
    }

    /**
     * @param QueryBuilder $query
     * @return QueryBuilder
     */
    protected function applyExtraFilters(QueryBuilder $query)
    {
        $query = $query->andWhere('e.active = 1');
        return $query;
    }

    /**
     * @return array
     */
    protected function getFilterMappings()
    {
        return [
            'name'    => 'e.name',
            'project' => 'e.project',
            'vendor'  => 'e.vendor',
            'release' => new DoctrineJoinFilterMapping(
                'e.releases',
                'r',
                "r.name :operator :value"
            ),
        ];
    }

    /**
     * @return array
     */
    protected function getOrderMappings()
    {
        return [
            'id'      => 'e.id',
            'name'    => 'e.name',
            'project' => 'e.project',
            'vendor'  => 'e.vendor',
        ];
    }
}
