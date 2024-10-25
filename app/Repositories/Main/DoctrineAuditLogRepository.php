<?php namespace repositories\main;
/**
 * Copyright 2022 OpenStack Foundation
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

use App\Models\Foundation\Main\Repositories\IAuditLogRepository;
use DateTime;
use Doctrine\ORM\QueryBuilder;
use models\main\AuditLog;
use App\Repositories\SilverStripeDoctrineRepository;
use models\main\Member;
use models\main\SummitAttendeeBadgeAuditLog;
use models\main\SummitAuditLog;
use models\main\SummitEventAuditLog;
use models\summit\SummitAttendeeBadge;
use models\summit\SummitEvent;
use models\utils\SilverstripeBaseModel;
use utils\DoctrineFilterMapping;
use utils\DoctrineInstanceOfFilterMapping;
use utils\Filter;
use utils\Order;

/**
 * Class DoctrineAuditLogRepository
 * @package repositories\main
 */
final class DoctrineAuditLogRepository
    extends SilverStripeDoctrineRepository
    implements IAuditLogRepository
{
    /**
     * @return string
     */
    protected function getBaseEntity()
    {
        return AuditLog::class;
    }

    /**
     * @param QueryBuilder $query
     * @param Filter|null $filter
     * @return QueryBuilder
     */
    protected function applyExtraJoins(QueryBuilder $query, ?Filter $filter = null, ?Order $order = null): QueryBuilder
    {
        $query = $query->leftJoin(Member::class, 'u', 'WITH', 'e.user = u.id');

        if ($filter instanceof Filter) {
            $e = $filter->getFilter("class_name");
            foreach($e as $f){
                if ($f->getValue() === SummitAuditLog::ClassName ||
                    $f->getValue() === SummitEventAuditLog::ClassName ||
                    $f->getValue() === SummitAttendeeBadgeAuditLog::ClassName)
                {
                    $query = $query->leftJoin(SummitAuditLog::class, 'sal', 'WITH', 'e.id = sal.id');
                    if ($f->getValue() === SummitEventAuditLog::ClassName) {
                        $query = $query->leftJoin(SummitEventAuditLog::class, 'seal', 'WITH', 'e.id = seal.id')
                            ->leftJoin(SummitEvent::class, 're', 'WITH', 're.id = seal.related_entity');
                    } else if ($f->getValue() === SummitAttendeeBadgeAuditLog::ClassName) {
                        $query = $query->leftJoin(SummitAttendeeBadgeAuditLog::class, 'seal', 'WITH', 'e.id = seal.id')
                            ->leftJoin(SummitAttendeeBadge::class, 're', 'WITH', 're.id = seal.related_entity');
                    }
                    break;
                }
            }
        }

        return $query;
    }

    /**
     * @return array
     */
    protected function getFilterMappings(): array
    {
        return [
            'class_name' => new DoctrineInstanceOfFilterMapping(
                "e",
                [
                    SummitAuditLog::ClassName               => SummitAuditLog::class,
                    SummitEventAuditLog::ClassName          => SummitEventAuditLog::class,
                    SummitAttendeeBadgeAuditLog::ClassName  => SummitAttendeeBadgeAuditLog::class,
                ]
            ),
            'summit_id'      => new DoctrineFilterMapping("sal.summit :operator :value"),
            'event_id'       => new DoctrineFilterMapping("seal.related_entity :operator :value"),  //REMARK: backward compatibility
            'entity_id'      => new DoctrineFilterMapping("seal.related_entity :operator :value"),
            'user_id'        => new DoctrineFilterMapping("u.id :operator :value"),
            'user_email'     => new DoctrineFilterMapping("u.email :operator :value"),
            'user_full_name' => new DoctrineFilterMapping("concat(u.first_name, ' ', u.last_name) :operator :value"),
            'action'         => 'e.action:json_string',
            'created'        => sprintf('e.created:datetime_epoch|%s', SilverstripeBaseModel::DefaultTimeZone),
            'last_edited'    => sprintf('e.last_edited:datetime_epoch|%s', SilverstripeBaseModel::DefaultTimeZone),
        ];
    }

    protected function getOrderMappings(): array
    {
        $args  = func_get_args();
        $filter = count($args) > 0 ? $args[0] : null;

        $order_mappings = [
            'id' => 'e.id',
            'created' => 'e.created',
            'user_id' => 'u.id',
            'user_full_name' => <<<SQL
LOWER(CONCAT(u.first_name, ' ', u.last_name))
SQL,
            'user_email' => <<<SQL
LOWER(u.email)
SQL,
        ];

        if($filter instanceof Filter && $filter->hasFilter("class_name")){
            $e = $filter->getFilter("class_name");
            foreach($e as $f){
                if ($f->getValue() === SummitEventAuditLog::ClassName) {
                    $order_mappings['event_id'] = 're.id';
                    break;
                } else if ($f->getValue() === SummitAttendeeBadgeAuditLog::ClassName) {
                    $order_mappings['entity_id'] = 're.id';
                    break;
                }
            }
        }
        return $order_mappings;
    }

    /**
     * @inheritDoc
     */
    public function deleteOldLogEntries(int $summit_id, DateTime $date_backward_from)
    {
        $query = "DELETE FROM AuditLog WHERE ID IN (SELECT ID FROM SummitAuditLog WHERE SummitID = {$summit_id}) AND Created < '{$date_backward_from->format('Y-m-d')}';";
        $this->getEntityManager()->getConnection()->executeStatement($query);
    }
}