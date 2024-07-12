<?php namespace App\ModelSerializers\Locations;
/**
 * Copyright 2019 OpenStack Foundation
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

use Libs\ModelSerializers\AbstractSerializer;
use models\summit\SummitBookableVenueRoom;
use ModelSerializers\Locations\SummitVenueRoomSerializer;
use ModelSerializers\SerializerRegistry;
/**
 * Class SummitBookableVenueRoomSerializer
 * @package App\ModelSerializers\Locations
 */
class SummitBookableVenueRoomSerializer extends SummitVenueRoomSerializer {
  protected static $array_mappings = [
    "TimeSlotCost" => "time_slot_cost:json_int",
    "Currency" => "currency:json_string",
  ];

  public function serialize(
    $expand = null,
    array $fields = [],
    array $relations = [],
    array $params = [],
  ) {
    $room = $this->object;
    if (!$room instanceof SummitBookableVenueRoom) {
      return [];
    }

    $values = parent::serialize($expand, $fields, $relations, $params);

    $attributes = [];
    foreach ($room->getAttributes() as $attribute) {
      $attributes[] = SerializerRegistry::getInstance()
        ->getSerializer($attribute)
        ->serialize(
          AbstractSerializer::filterExpandByPrefix($expand, "attributes"),
          AbstractSerializer::filterFieldsByPrefix($fields, "attributes"),
          AbstractSerializer::filterFieldsByPrefix($relations, "attributes"),
          $params,
        );
    }
    $values["attributes"] = $attributes;
    return $values;
  }
}
