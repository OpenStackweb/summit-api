<?php namespace ModelSerializers;
/**
 * Copyright 2016 OpenStack Foundation
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

use Libs\ModelSerializers\Many2OneExpandSerializer;
use Libs\ModelSerializers\One2ManyExpandSerializer;
use models\main\Group;

/**
 * Class GroupSerializer
 * @package ModelSerializers
 */
final class GroupSerializer extends SilverStripeSerializer {
  protected static $array_mappings = [
    "Title" => "title:json_string",
    "Description" => "description:json_string",
    "Code" => "code:json_string",
  ];

  protected static $allowed_relations = ["members"];

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
    $group = $this->object;
    if (!$group instanceof Group) {
      return [];
    }
    return parent::serialize($expand, $fields, $relations, $params);
  }

  protected static $expand_mappings = [
    "members" => [
      "type" => Many2OneExpandSerializer::class,
      "getter" => "getMembers",
    ],
  ];
}
