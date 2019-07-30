<?php namespace App\Repositories\Summit;
/**
 * Copyright 2019 OpenStack Foundation
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
use App\Models\Foundation\Summit\Repositories\ISponsorRepository;
use App\Repositories\SilverStripeDoctrineRepository;
use Doctrine\ORM\QueryBuilder;
use models\summit\Sponsor;
use utils\DoctrineHavingFilterMapping;
use utils\DoctrineJoinFilterMapping;
use utils\DoctrineLeftJoinFilterMapping;
/**
 * Class DoctrineSponsorRepository
 * @package App\Repositories\Summit
 */
final class DoctrineSponsorRepository extends SilverStripeDoctrineRepository
implements ISponsorRepository
{

    /**
     * @return array
     */
    protected function getFilterMappings()
    {
        return [
            'company_name'      => new DoctrineJoinFilterMapping("e.company", "c" ,"c.name :operator :value"),
            'sponsorship_name'  => new DoctrineJoinFilterMapping("e.sponsorship", "sp" ,"sp.name :operator :value"),
            'sponsorship_label' => new DoctrineJoinFilterMapping("e.sponsorship", "sp" ,"sp.label :operator :value"),
            'sponsorship_size'  => new DoctrineJoinFilterMapping("e.sponsorship", "sp" ,"sp.size :operator :value"),
            'summit_id'         => new DoctrineLeftJoinFilterMapping("e.summit", "s" ,"s.id :operator :value"),
            'badge_scans_count' => new DoctrineHavingFilterMapping("", "bs.sponsor", "count(bs.id) :operator :value"),
        ];
    }

    /**
     * @param QueryBuilder $query
     * @return QueryBuilder
     */
    protected function applyExtraJoins(QueryBuilder $query){
        $query = $query->leftJoin("e.badge_scans", "bs");
        return $query;
    }

    /**
     * @return array
     */
    protected function getOrderMappings()
    {
        return [
            'id'    => 'e.id',
            'name'  => 'e.name',
            'order' => 'e.order',
        ];
    }

    /**
     * @return string
     */
    protected function getBaseEntity()
    {
       return Sponsor::class;
    }
}