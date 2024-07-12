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
use Libs\ModelSerializers\Many2OneExpandSerializer;
use Libs\ModelSerializers\One2ManyExpandSerializer;
/**
 * Class ProjectSponsorshipTypeSerializer
 * @package ModelSerializers
 */
class ProjectSponsorshipTypeSerializer extends SilverStripeSerializer {
  protected static $array_mappings = [
    "Name" => "name:json_string",
    "Description" => "description:json_string",
    "Active" => "is_active:json_boolean",
    "Order" => "order:json_int",
    "SponsoredProjectId" => "sponsored_project_id:json_int",
    "SupportingCompaniesIds" => "supporting_companies",
  ];

  protected static $expand_mappings = [
    "sponsored_project" => [
      "type" => One2ManyExpandSerializer::class,
      "original_attribute" => "sponsored_project_id",
      "getter" => "getSponsoredProject",
      "has" => "hasSponsoredProject",
    ],
    "supporting_companies" => [
      "type" => Many2OneExpandSerializer::class,
      "getter" => "getSupportingCompanies",
    ],
  ];
}
