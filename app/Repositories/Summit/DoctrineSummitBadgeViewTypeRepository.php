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

use App\Repositories\SilverStripeDoctrineRepository;
use models\summit\ISummitBadgeViewTypeRepository;
use models\summit\SummitBadgeViewType;
use utils\DoctrineLeftJoinFilterMapping;

/**
 * Class DoctrineSummitBadgeViewTypeRepository
 * @package App\Repositories\Summit
 */
final class DoctrineSummitBadgeViewTypeRepository extends SilverStripeDoctrineRepository implements
  ISummitBadgeViewTypeRepository {
  /**
   * @return string
   */
  protected function getBaseEntity() {
    return SummitBadgeViewType::class;
  }

  /**
   * @return array
   */
  protected function getFilterMappings() {
    return [
      "name" => "e.name:json_string",
      "is_default" => "e.is_default|json_boolean",
      "summit_id" => new DoctrineLeftJoinFilterMapping("e.summit", "s", "s.id :operator :value"),
    ];
  }

  /**
   * @return array
   */
  protected function getOrderMappings() {
    return [
      "name" => "e.name",
      "id" => "e.id",
    ];
  }
}
