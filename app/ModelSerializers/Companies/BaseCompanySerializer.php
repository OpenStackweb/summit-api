<?php namespace App\ModelSerializers\Companies;
/*
 * Copyright 2024 OpenStack Foundation
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
use ModelSerializers\SilverStripeSerializer;
/**
 * Class BaseCompanySerializer
 * @package App\ModelSerializers\Companies
 */
class BaseCompanySerializer extends SilverStripeSerializer {
  protected static $array_mappings = [
    "Name" => "name:json_string",
    "Url" => "url:json_string",
    "UrlSegment" => "url_segment:json_string",
    "City" => "city:json_string",
    "State" => "state:json_string",
    "Country" => "country:json_string",
    "Description" => "description:json_string",
    "Industry" => "industry:json_string",
    "Contributions" => "contributions:json_string",
    "MemberLevel" => "member_level:json_string",
    "Overview" => "overview:json_string",
    "Products" => "products:json_string",
    "Commitment" => "commitment:json_string",
    "CommitmentAuthor" => "commitment_author:json_string",
    "LogoUrl" => "logo:json_url",
    "BigLogoUrl" => "big_logo:json_url",
    "Color" => "color:json_color",
  ];
}
