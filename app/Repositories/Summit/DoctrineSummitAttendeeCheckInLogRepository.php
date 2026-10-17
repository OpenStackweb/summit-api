<?php namespace App\Repositories\Summit;
/**
 * Copyright 2026 OpenStack Foundation
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
use App\Models\Foundation\Summit\Repositories\ISummitAttendeeCheckInLogRepository;
use App\Repositories\SilverStripeDoctrineRepository;
use Doctrine\ORM\QueryBuilder;
use models\summit\SummitAttendeeCheckInLog;
use models\utils\SilverstripeBaseModel;
use utils\Filter;
use utils\Order;

/**
 * Class DoctrineSummitAttendeeCheckInLogRepository
 * @package App\Repositories\Summit
 */
final class DoctrineSummitAttendeeCheckInLogRepository
    extends SilverStripeDoctrineRepository
    implements ISummitAttendeeCheckInLogRepository
{

    protected function getBaseEntity()
    {
        return SummitAttendeeCheckInLog::class;
    }

    protected function applyExtraJoins(QueryBuilder $query, ?Filter $filter = null, ?Order $order = null)
    {
        $query = $query->innerJoin("e.attendee", "a");
        $query = $query->innerJoin("a.summit", "s");
        $query = $query->leftJoin("e.actor", "m");
        return $query;
    }

    protected function getFilterMappings()
    {
        return [
            'id' => new DoctrineInFilterMapping('e.id'),
            'attendee_id' => Filter::buildIntField('a.id'),
            'summit_id' => Filter::buildIntField('s.id'),
            'action' => new DoctrineInFilterMapping('e.action'),
            'source' => new DoctrineInFilterMapping('e.source'),
            'actor_id' => new DoctrineInFilterMapping('m.id'),
            'client_id' => 'e.client_id',
            'created' => sprintf('e.created:datetime_epoch|%s', SilverstripeBaseModel::DefaultTimeZone),
            'actor_email' => 'm.email',
        ];
    }

    protected function getOrderMappings()
    {
        return [
            'id' => 'e.id',
            'created' => 'e.created',
            'action' => 'e.action',
            'source' => 'e.source',
            'actor_email' => 'm.email',
        ];
    }
}
