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
use Libs\ModelSerializers\One2ManyExpandSerializer;
use models\summit\SummitVenueRoom;
use ModelSerializers\SerializerRegistry;
/**
 * Class SummitVenueRoomSerializer
 * @package ModelSerializers\Locations
 */
class SummitVenueRoomSerializer extends SummitAbstractLocationSerializer {
  protected static $array_mappings = [
    "VenueId" => "venue_id:json_int",
    "FloorId" => "floor_id:json_int",
    "Capacity" => "capacity:json_int",
    "OverrideBlackouts" => "override_blackouts:json_boolean",
  ];

  protected static $expand_mappings = [
    "venue" => [
      "type" => One2ManyExpandSerializer::class,
      "original_attribute" => "venue_id",
      "getter" => "getVenue",
      "has" => "hasVenue",
    ],
    "floor" => [
      "type" => One2ManyExpandSerializer::class,
      "original_attribute" => "floor_id",
      "getter" => "getFloor",
      "has" => "hasFloor",
    ],
  ];

  public function serialize(
    $expand = null,
    array $fields = [],
    array $relations = [],
    array $params = [],
  ) {
    $room = $this->object;
    if (!$room instanceof SummitVenueRoom) {
      return [];
    }
    $values = parent::serialize($expand, $fields, $relations, $params);

    if ($room->hasImage()) {
      $values["image"] = SerializerRegistry::getInstance()
        ->getSerializer($room->getImage())
        ->serialize();
    }

    return $values;
  }
}
