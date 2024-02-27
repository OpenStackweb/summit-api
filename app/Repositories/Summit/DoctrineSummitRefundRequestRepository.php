<?php namespace App\Repositories\Summit;
/*
 * Copyright 2024 OpenStack Foundation
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

use App\Models\Foundation\Summit\Repositories\ISummitRefundRequestRepository;
use App\Repositories\SilverStripeDoctrineRepository;
use Doctrine\ORM\QueryBuilder;
use models\summit\SummitAttendeeTicketRefundRequest;
use models\summit\SummitRefundRequest;
use utils\DoctrineInstanceOfFilterMapping;
use utils\Filter;

/**
 * Class DoctrineSummitRefundRequestRepository
 * @package App\Repositories\Summit
 */
final class DoctrineSummitRefundRequestRepository
    extends SilverStripeDoctrineRepository
    implements ISummitRefundRequestRepository
{

    protected function getBaseEntity()
    {
        return SummitRefundRequest::class;
    }

    /**
     * @param QueryBuilder $query
     * @return QueryBuilder
     */
    protected function applyExtraJoins(QueryBuilder $query, ?Filter $filter = null)
    {
        $query = $query->leftJoin(SummitAttendeeTicketRefundRequest::class, 'trr', 'WITH', 'e.id = trr.id');
        $query = $query->leftJoin("ttr.ticket", "t");
        $query = $query->leftJoin("t.order", "o");
        return $query->leftJoin("o.summit", "s");
    }

    /**
     * @return array
     */
    protected function getFilterMappings()
    {
        return [
            'class_name'     => new DoctrineInstanceOfFilterMapping(
                "e",
                [
                    SummitRefundRequest::ClassName => SummitRefundRequest::class,
                    SummitAttendeeTicketRefundRequest::ClassName => SummitAttendeeTicketRefundRequest::class,
                ]
            ),
            'status' => 'e.status',
            'order_id' => 'o.id',
            'ticket_id' => 't.id',
            'summit_id' => 's.id',
        ];
    }

    /**
     * @return array
     */
    protected function getOrderMappings()
    {
        return [
            'id'   => 'e.id',
            'action_date' => 'e.action_date',
        ];
    }
}