<?php namespace App\Repositories\Summit;
/**
 * Copyright 2021 OpenStack Foundation
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
use App\Models\Foundation\Summit\Repositories\ISummitCategoryChangeRepository;
use App\Repositories\SilverStripeDoctrineRepository;
use Doctrine\ORM\QueryBuilder;
use models\summit\SummitCategoryChange;
use utils\Filter;

/**
 * Class DoctrineSummitCategoryChangeRepository
 * @package App\Repositories\Summit
 */
final class DoctrineSummitCategoryChangeRepository extends SilverStripeDoctrineRepository implements
  ISummitCategoryChangeRepository {
  /**
   * @inheritDoc
   */
  protected function getBaseEntity() {
    return SummitCategoryChange::class;
  }

  /**
   * @param QueryBuilder $query
   * @return QueryBuilder
   */
  protected function applyExtraJoins(QueryBuilder $query, ?Filter $filter = null) {
    $query->join("e.presentation", "p");
    $query->join("p.selection_plan", "sp");
    $query->join("p.summit", "s");
    $query->leftJoin("e.new_category", "ncat");
    $query->leftJoin("e.old_category", "ocat");
    $query->leftJoin("e.requester", "r");
    $query->leftJoin("e.aprover", "a");
    return $query;
  }

  /**
   * @return array
   */
  protected function getFilterMappings() {
    return [
      "selection_plan_id" => "sp.id",
      "new_category_id" => "ncat.id",
      "old_category_id" => "ocat.id",
      "new_category_name" => "ncat.title",
      "old_category_name" => "ocat.title",
      "presentation_title" => "p.title",
      "requester_fullname" => "concat(r.first_name, ' ', r.last_name) :operator :value",
      "requester_email" => "r.email",
      "aprover_fullname" => "concat(a.first_name, ' ', a.last_name) :operator :value",
      "aprover_email" => "a.email",
      "summit_id" => "s.id",
    ];
  }

  /**
   * @return array
   */
  protected function getOrderMappings() {
    return [
      "id" => "e.id",
      "approval_date" => "e.approval_date",
      "presentation_title" => "p.id",
      "status" => "e.status",
      "new_category_name" => "ncat.title",
      "old_category_name" => "ocat.title",
      "requester_fullname" => "concat(r.first_name, ' ', r.last_name)",
    ];
  }
}
