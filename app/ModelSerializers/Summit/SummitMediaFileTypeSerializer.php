<?php namespace ModelSerializers;
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
use models\summit\SummitMediaFileType;
/**
 * Class SummitMediaFileTypeSerializer
 * @package ModelSerializers
 */
final class SummitMediaFileTypeSerializer extends SilverStripeSerializer {
  protected static $array_mappings = [
    "Name" => "name:json_string",
    "Description" => "description:json_string",
    "SystemDefined" => "is_system_defined:json_boolean",
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
    $type = $this->object;
    if (!$type instanceof SummitMediaFileType) {
      return [];
    }
    $values = parent::serialize($expand, $fields, $relations, $params);
    $allowed_extensions = $type->getAllowedExtensions();
    $values["allowed_extensions"] = !is_null($allowed_extensions)
      ? explode("|", $allowed_extensions)
      : [];
    return $values;
  }
}
