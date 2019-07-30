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
use App\Models\Foundation\Main\Repositories\ISummitAdministratorPermissionGroupRepository;
use App\Repositories\SilverStripeDoctrineRepository;
use Doctrine\ORM\QueryBuilder;
use models\main\SummitAdministratorPermissionGroup;
/**
 * Class DoctrineSummitAdministratorPermissionGroupRepository
 * @package repositories\main
 */
class DoctrineSummitAdministratorPermissionGroupRepository
    extends SilverStripeDoctrineRepository
    implements ISummitAdministratorPermissionGroupRepository
{

    /**
     * @return array
     */
    protected function getFilterMappings()
    {
        return [
            'title' => 'e.title',
            'member_id' => "m.id :operator :value",
            'summit_id' => "s.id :operator :value",
            'member_first_name' => "m.first_name :operator :value",
            'member_last_name' => "m.last_name :operator :value",
            'member_full_name' => "concat(m.first_name, ' ', m.last_name) :operator :value",
            'member_email' => "m.email :operator :value",
        ];
    }

    /**
     * @return array
     */
    protected function getOrderMappings()
    {
        return [
            'id' => 'e.id',
            'created' => 'e.created',
            'title' => "e.title",
        ];
    }

    /**
     * @param QueryBuilder $query
     * @return QueryBuilder
     */
    protected function applyExtraJoins(QueryBuilder $query)
    {
        $query = $query->join('e.summits', 's')
            ->join('e.members', 'm');
        return $query;
    }

    /**
     * @inheritDoc
     */
    protected function getBaseEntity()
    {
        return SummitAdministratorPermissionGroup::class;
    }

    /**
     * @inheritDoc
     */
    public function getByTitle(string $title): ?SummitAdministratorPermissionGroup
    {
        return $this->findOneBy(['title' => trim($title)]);
    }
}