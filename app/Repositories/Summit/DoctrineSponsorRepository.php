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
use utils\DoctrineFilterMapping;
use utils\DoctrineHavingFilterMapping;
use utils\DoctrineJoinFilterMapping;
use utils\DoctrineLeftJoinFilterMapping;
use utils\Filter;
use utils\Order;

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
            'sponsor_id'        => new DoctrineFilterMapping("e.id :operator :value"),
            'company_name'      => "c.name",
            'company_id'        => "c.id",
            'sponsorship_name'  => "st.name",
            'sponsorship_label' => "st.label",
            'sponsorship_size'  => "st.size",
            'summit_id'         => new DoctrineLeftJoinFilterMapping("e.summit", "s" ,"s.id :operator :value"),
            'badge_scans_count' => new DoctrineHavingFilterMapping("", "bs.sponsor", "count(bs.id) :operator :value"),
            'is_published' => Filter::buildBooleanField('e.is_published'),
        ];
    }

    /**
     * @param QueryBuilder $query
     * @return QueryBuilder
     */
    protected function applyExtraJoins(QueryBuilder $query, ?Filter $filter = null, ?Order $order = null){
        if(!is_null($filter) && $filter->hasFilter("badge_scans_count"))
            $query = $query->leftJoin("e.user_info_grants", "bs");
        if(
            (!is_null($filter) && $filter->hasFilter("company_name")) ||
            (!is_null($order) && $order->hasOrder("company_name"))
        )
            $query = $query->leftJoin("e.company", "c");

        if(
            (!is_null($filter) && $filter->hasFilter("sponsorship_label")) ||
            (!is_null($filter) && $filter->hasFilter("sponsorship_name")) ||
            (!is_null($filter) && $filter->hasFilter("sponsorship_size")) ||
            (!is_null($order) && $order->hasOrder("sponsorship_name")) ||
            (!is_null($order) && $order->hasOrder("sponsorship_size"))
        )
            $query = $query->leftJoin("e.sponsorships", "sp")
                            ->leftJoin("sp.type", "ssp")
                            ->leftJoin("ssp.type", "st");

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
            'company_name' => 'c.name',
            'sponsorship_name' => 'st.name',
            'sponsorship_size' => 'st.size',
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