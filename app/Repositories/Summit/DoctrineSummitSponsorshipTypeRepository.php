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
use App\Models\Foundation\Summit\Repositories\ISummitSponsorshipTypeRepository;
use App\Repositories\SilverStripeDoctrineRepository;
use Doctrine\ORM\QueryBuilder;
use models\summit\SummitSponsorshipType;
use utils\Filter;
/**
 * Class DoctrineSummitSponsorshipTypeRepository
 * @package App\Repositories\Summit
 */
final class DoctrineSummitSponsorshipTypeRepository
  extends SilverStripeDoctrineRepository
  implements ISummitSponsorshipTypeRepository {
  /**
   * @param QueryBuilder $query
   * @return QueryBuilder
   */
  protected function applyExtraJoins(QueryBuilder $query, ?Filter $filter = null) {
    $query = $query->innerJoin("e.type", "t");
    $query = $query->innerJoin("e.summit", "s");
    return $query;
  }

  /**
   * @return array
   */
  protected function getFilterMappings() {
    return [
      "name" => "t.name:json_string",
      "label" => "t.label:json_string",
      "size" => "t.size:json_string",
      "summit_id" => "s.id",
    ];
  }

  /**
   * @return array
   */
  protected function getOrderMappings() {
    return [
      "id" => "e.id",
      "name" => "t.name",
      "label" => "t.label",
      "size" => "t.size",
      "order" => "e.order",
    ];
  }

  /**
   * @return string
   */
  protected function getBaseEntity() {
    return SummitSponsorshipType::class;
  }
}
