<?php namespace App\Repositories\Summit;
/*
 * Copyright 2023 OpenStack Foundation
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

use App\Models\Foundation\Summit\Repositories\ISummitSignRepository;
use App\Models\Foundation\Summit\Signs\SummitSign;
use App\Repositories\SilverStripeDoctrineRepository;
use Doctrine\ORM\QueryBuilder;
use utils\Filter;
use utils\Order;

/**
 * Class DoctrineSummitSignRepository
 * @package App\Repositories\Summit
 */
final class DoctrineSummitSignRepository
    extends SilverStripeDoctrineRepository implements ISummitSignRepository
{

    protected function getBaseEntity()
    {
        return SummitSign::class;
    }

    /**
     * @param QueryBuilder $query
     * @return QueryBuilder
     */
    protected function applyExtraJoins(QueryBuilder $query, ?Filter $filter = null, ?Order $order = null)
    {
        $query = $query->leftJoin("e.summit", "s");
        $query = $query->leftJoin("e.location", "l");
        return $query;
    }
    /**
     * @return array
     */
    protected function getFilterMappings()
    {
        return [
            'summit_id' => Filter::buildIntField('s.id'),
            'location_id' => Filter::buildIntField('l.id'),
        ];
    }
}