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
use ModelSerializers\SilverStripeSerializer;

/**
 * Class SummitLocationImageSerializer
 * @package ModelSerializers\Locations
 */
class SummitLocationImageSerializer extends SilverStripeSerializer {
  protected static $array_mappings = [
    "Name" => "name:json_text",
    "Description" => "description:json_text",
    "ClassName" => "class_name:json_text",
    "LocationId" => "location_id:json_int",
    "Order" => "order:json_int",
    "ImageUrl" => "image_url:json_url",
  ];

  protected static $expand_mappings = [
    "location" => [
      "type" => One2ManyExpandSerializer::class,
      "original_attribute" => "location_id",
      "getter" => "getLocation",
      "has" => "hasLocation",
    ],
  ];
}
