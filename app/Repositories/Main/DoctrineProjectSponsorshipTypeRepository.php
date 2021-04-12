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
use App\Models\Foundation\Main\Repositories\IProjectSponsorshipTypeRepository;
use App\Repositories\SilverStripeDoctrineRepository;
use Doctrine\ORM\QueryBuilder;
use models\main\ProjectSponsorshipType;
/**
 * Class DoctrineProjectSponsorshipTypeRepository
 * @package repositories\main
 */
final class DoctrineProjectSponsorshipTypeRepository
    extends SilverStripeDoctrineRepository
    implements IProjectSponsorshipTypeRepository
{


    /**
     * @return array
     */
    protected function getFilterMappings()
    {
        return [
            'name' => 'e.name:json_string',
            'slug' => 'e.slug:json_string',
            'is_active' => 'e.is_active:json_int',
            'sponsored_project_slug' => 'sp.slug:json_string',
            'sponsored_project_id' => 'sp.id:json_int'
        ];
    }

    /**
     * @param QueryBuilder $query
     * @return QueryBuilder
     */
    protected function applyExtraJoins(QueryBuilder $query){
        $query = $query->innerJoin("e.sponsored_project", "sp");
        return $query;
    }

    /**
     * @return array
     */
    protected function getOrderMappings()
    {
        return [
            'id'   => 'e.id',
            'name' => 'e.name',
            'order'=> 'e.order',
        ];
    }


    /**
     * @inheritDoc
     */
    protected function getBaseEntity()
    {
        return ProjectSponsorshipType::class;
    }
}