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
use App\Models\Foundation\Summit\Repositories\ISummitAttendeeNoteRepository;
use App\Repositories\SilverStripeDoctrineRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use models\summit\Summit;
use models\summit\SummitAttendee;
use models\summit\SummitAttendeeNote;
use models\utils\SilverstripeBaseModel;
use utils\Filter;
use utils\Order;
use utils\PagingInfo;
use utils\PagingResponse;

/**
 * Class DoctrineSummitAttendeeNoteRepository
 * @package App\Repositories\Summit
 */
final class DoctrineSummitAttendeeNoteRepository
    extends SilverStripeDoctrineRepository
    implements ISummitAttendeeNoteRepository
{

    protected function getBaseEntity()
    {
        return SummitAttendeeNote::class;
    }

    protected function applyExtraJoins(QueryBuilder $query, ?Filter $filter = null, ?Order $order = null)
    {
        $query = $query->leftJoin("e.author", "a");
        $query = $query->innerJoin("e.owner", "o");
        $query = $query->leftJoin("e.ticket", "t");
        $query = $query->innerJoin("o.summit", "s");
        return $query;
    }

    protected function getFilterMappings()
    {
        return [
            'id'              => new DoctrineInFilterMapping('e.id'),
            'owner_id'        => Filter::buildIntField('o.id'),
            'ticket_id'       => Filter::buildIntField('t.id'),
            'summit_id'       => Filter::buildIntField('s.id'),
            'content'         => 'e.content',
            'author_fullname' => "CONCAT(LOWER(a.first_name), ' ', LOWER(a.last_name)) :operator LOWER(:value))",
            'author_email'    => 'a.email',
            'owner_fullname'  => "CONCAT(LOWER(o.first_name), ' ', LOWER(o.surname)) :operator LOWER(:value))",
            'owner_email'     => 'o.email',
            'created'           => sprintf('e.created:datetime_epoch|%s', SilverstripeBaseModel::DefaultTimeZone),
            'edited'       => sprintf('e.last_edited:datetime_epoch|%s', SilverstripeBaseModel::DefaultTimeZone),
        ];
    }

    protected function getOrderMappings()
    {
        return [
            'id'                => 'e.id',
            'created'           => 'e.created',
            'author_fullname'   => "CONCAT(LOWER(a.first_name), ' ', LOWER(a.last_name))",
            'author_email'      => 'a.email',
            'owner_fullname'    => "CONCAT(LOWER(o.first_name), ' ', LOWER(o.surname))",
            'owner_email'       => 'o.email',
        ];
    }
}