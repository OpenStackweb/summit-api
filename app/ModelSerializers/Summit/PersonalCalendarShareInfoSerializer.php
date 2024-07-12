<?php namespace App\ModelSerializers\Summit;
/**
 * Copyright 2020 OpenStack Foundation
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
use models\main\PersonalCalendarShareInfo;
use ModelSerializers\SilverStripeSerializer;
/**
 * Class PersonalCalendarShareInfoSerializer
 * @package App\ModelSerializers\Summit
 */
final class PersonalCalendarShareInfoSerializer extends SilverStripeSerializer {
  protected static $array_mappings = [
    "Link" => "link:json_url",
    "SummitId" => "summit_id:json_int",
    "OwnerId" => "owner_id:json_int",
    "Cid" => "cid:json_string",
  ];

  /**
   * @param null $expand
   * @param array $fields
   * @param array $relations
   * @param array $params
   * @return array
   */
  public function serialize(
    $expand = null,
    array $fields = [],
    array $relations = [],
    array $params = [],
  ) {
    $link = $this->object;
    if (!$link instanceof PersonalCalendarShareInfo) {
      return [];
    }
    $values = parent::serialize($expand, $fields, $relations, $params);
    return $values;
  }
}
