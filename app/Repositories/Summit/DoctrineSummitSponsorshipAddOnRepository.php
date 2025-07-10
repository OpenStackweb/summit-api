<?php namespace App\Repositories\Summit;
/**
 * Copyright 2025 OpenStack Foundation
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

use App\Models\Foundation\Summit\Repositories\ISummitSponsorshipAddOnRepository;
use App\Repositories\SilverStripeDoctrineRepository;
use Doctrine\ORM\QueryBuilder;
use models\summit\SummitSponsorshipAddOn;
use utils\DoctrineFilterMapping;
use utils\Filter;
use utils\Order;

/**
 * Class DoctrineSummitSponsorshipAddOnRepository
 * @package App\Repositories\Summit
 */
final class DoctrineSummitSponsorshipAddOnRepository extends SilverStripeDoctrineRepository
implements ISummitSponsorshipAddOnRepository
{
    /**
     * @param QueryBuilder $query
     * @param Filter|null $filter
     * @param Order|null $order
     * @return QueryBuilder
     */
    protected function applyExtraJoins(QueryBuilder $query, ?Filter $filter = null, ?Order $order = null): QueryBuilder
    {
        $query->innerJoin("e.sponsorship", "sps");
        $query->innerJoin("sps.sponsor", "sp");
        $query->innerJoin("sp.summit", "s");
        return $query;
    }

    /**
     * @return array
     */
    protected function getFilterMappings(): array
    {
        return [
            'id'   => new DoctrineFilterMapping("e.id :operator :value"),
            'name' => 'e.name',
            'type' => 'e.type',
            'sponsorship_id' => 'sps.id',
            'sponsor_id' => 'sp.id',
            'summit_id'  => 's.id',
        ];
    }

    /**
     * @return array
     */
    protected function getOrderMappings(): array
    {
        return [
            'id'   => 'e.id',
            'name' => 'e.name',
            'type' => 'e.type',
        ];
    }

    /**
     * @return string
     */
    protected function getBaseEntity(): string
    {
       return SummitSponsorshipAddOn::class;
    }
}