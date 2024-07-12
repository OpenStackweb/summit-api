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

use App\Http\Utils\Filters\DoctrineInFilterMapping;
use App\Models\Foundation\Summit\Repositories\ISummitAttendeeBadgePrintRepository;
use App\Repositories\SilverStripeDoctrineRepository;
use Doctrine\ORM\QueryBuilder;
use models\summit\SummitAttendeeBadgePrint;
use models\utils\SilverstripeBaseModel;
use utils\Filter;

/**
 * Class DoctrineSummitAttendeeBadgePrintRepository
 * @package App\Repositories\Summit
 */
final class DoctrineSummitAttendeeBadgePrintRepository
    extends SilverStripeDoctrineRepository
    implements ISummitAttendeeBadgePrintRepository
{

    protected function getBaseEntity()
    {
        return SummitAttendeeBadgePrint::class;
    }

    protected function applyExtraJoins(QueryBuilder $query, ?Filter $filter = null)
    {
        $query = $query->innerJoin("e.badge", "b");
        $query = $query->innerJoin("e.requestor", "r");
        $query = $query->innerJoin("e.view_type", "vt");
        $query = $query->innerJoin("b.ticket", "t");
        $query = $query->innerJoin("t.order", "o");
        $query = $query->innerJoin("o.summit", "s");
        return $query;
    }

    protected function getFilterMappings()
    {
        return [
            'id' => new DoctrineInFilterMapping('e.id'),
            'ticket_id' => Filter::buildIntField('t.id'),
            'summit_id' => Filter::buildIntField('s.id'),
            'view_type_id' => new DoctrineInFilterMapping('vt.id'),
            'created'           => sprintf('e.created:datetime_epoch|%s', SilverstripeBaseModel::DefaultTimeZone),
            'print_date'=>Filter::buildDateTimeEpochField('e.print_date'),
            'requestor_full_name' => "CONCAT(LOWER(r.first_name), ' ', LOWER(r.last_name)) :operator LOWER(:value) )",
            'requestor_email' => 'r.email',
        ];
    }

    protected function getOrderMappings()
    {
        return [
            'id' => 'e.id',
            'created' => 'e.created',
            'view_type_id' => 'vt.id',
            'print_date'=>'e.print_date',
            "requestor_full_name" => <<<SQL
LOWER(CONCAT(r.first_name, ' ', r.first_name))
SQL,
            'requestor_email' => 'r.email',
        ];
    }



}