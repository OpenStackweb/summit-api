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

use models\summit\SummitAbstractLocation;
use ModelSerializers\SilverStripeSerializer;

/**
 * Class SummitAbstractLocationSerializer
 * @package ModelSerializers\Locations
 */
class SummitAbstractLocationSerializer extends SilverStripeSerializer {
  protected static $array_mappings = [
    "Name" => "name:json_string",
    "ShortName" => "short_name:json_string",
    "Description" => "description:json_string",
    "LocationType" => "location_type",
    "Order" => "order:json_int",
    "OpeningHour" => "opening_hour:json_int",
    "ClosingHour" => "closing_hour:json_int",
    "ClassName" => "class_name:json_string",
  ];

  protected static $allowed_relations = ["published_events"];

  public function serialize(
    $expand = null,
    array $fields = [],
    array $relations = [],
    array $params = [],
  ) {
    $location = $this->object;
    if (!$location instanceof SummitAbstractLocation) {
      return [];
    }

    $values = parent::serialize($expand, $fields, $relations, $params);

    if (in_array("published_events", $relations) && !isset($values["published_events"])) {
      $events = [];
      foreach ($location->getPublishedEvents() as $e) {
        $events[] = $e->getId();
      }
      $values["published_events"] = $events;
    }

    return $values;
  }
}
