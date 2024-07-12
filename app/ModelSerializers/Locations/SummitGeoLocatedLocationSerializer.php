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

/**
 * Class SummitGeoLocatedLocationSerializer
 * @package ModelSerializers\Locations
 */
class SummitGeoLocatedLocationSerializer extends SummitAbstractLocationSerializer {
  protected static $array_mappings = [
    "Address1" => "address_1:json_string",
    "Address2" => "address_2:json_string",
    "ZipCode" => "zip_code",
    "City" => "city:json_string",
    "State" => "state:json_string",
    "Country" => "country:json_string",
    "Lng" => "lng",
    "Lat" => "lat",
    "WebsiteUrl" => "website_url:json_string",
    "DisplayOnSite" => "display_on_site:json_boolean",
    "DetailsPage" => "details_page:json_boolean",
    "LocationMessage" => "location_message:json_string",
  ];

  protected static $allowed_relations = ["maps", "images"];

  protected static $expand_mappings = [
    "maps" => [
      "type" => Many2OneExpandSerializer::class,
      "getter" => "getMaps",
    ],
    "images" => [
      "type" => Many2OneExpandSerializer::class,
      "getter" => "getImages",
    ],
  ];
}
