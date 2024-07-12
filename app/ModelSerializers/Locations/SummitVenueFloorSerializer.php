<?php namespace ModelSerializers\Locations;
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
use models\summit\SummitVenueFloor;
use ModelSerializers\SilverStripeSerializer;
/**
 * Class SummitVenueFloorSerializer
 * @package ModelSerializers\Locations
 */
final class SummitVenueFloorSerializer extends SilverStripeSerializer {
  protected static $array_mappings = [
    "Name" => "name:json_string",
    "Description" => "description:json_string",
    "Number" => "number:json_int",
    "VenueId" => "venue_id:json_int",
    "ImageUrl" => "image:json_url",
  ];

  protected static $expand_mappings = [
    "venue" => [
      "type" => One2ManyExpandSerializer::class,
      "original_attribute" => "venue_id",
      "getter" => "getVenue",
      "has" => "hasVenue",
    ],
    "rooms" => [
      "type" => Many2OneExpandSerializer::class,
      "getter" => "getRooms",
    ],
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
    $values = parent::serialize($expand, $fields, $relations, $params);
    $floor = $this->object;

    if (!$floor instanceof SummitVenueFloor) {
      return [];
    }

    return $values;
  }
}
