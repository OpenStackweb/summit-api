<?php namespace App\Repositories\Summit;
/*
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
use App\Models\Foundation\Summit\Repositories\ISummitScheduleConfigRepository;
use App\Repositories\SilverStripeDoctrineRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use models\summit\SummitScheduleConfig;
use utils\Filter;

/**
 * Class DoctrineSummitScheduleConfigRepository
 * @package App\Repositories\Summit
 */
class DoctrineSummitScheduleConfigRepository extends SilverStripeDoctrineRepository implements
  ISummitScheduleConfigRepository {
  /**
   * @param QueryBuilder $query
   * @return QueryBuilder
   */
  protected function applyExtraJoins(QueryBuilder $query, ?Filter $filter = null) {
    $query = $query->innerJoin("e.summit", "s", Join::ON);
    return $query;
  }

  /**
   * @return array
   */
  protected function getFilterMappings(): array {
    return [
      "key" => "e.key",
      "is_enabled" => "e.is_enabled",
      "is_my_schedule" => "e.is_my_schedule",
      "only_events_with_attendee_access" => "e.only_events_with_attendee_access",
      "color_source" => "e.color_source",
      "summit_id" => "s.id",
      "hide_past_events_with_show_always_on_schedule" =>
        "e.hide_past_events_with_show_always_on_schedule",
    ];
  }

  /**
   * @return array
   */
  protected function getOrderMappings(): array {
    return [
      "key" => "e.key",
      "id" => "e.id",
    ];
  }

  /**
   * @param QueryBuilder $query
   * @return QueryBuilder
   */
  protected function applyExtraFilters(QueryBuilder $query): QueryBuilder {
    return $query;
  }

  /**
   * @return string
   */
  protected function getBaseEntity(): string {
    return SummitScheduleConfig::class;
  }
}
