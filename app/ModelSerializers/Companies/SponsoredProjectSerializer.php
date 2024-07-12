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

use Libs\ModelSerializers\AbstractSerializer;
use Libs\ModelSerializers\Many2OneExpandSerializer;
use models\main\SponsoredProject;

/**
 * Class SponsoredProjectSerializer
 * @package ModelSerializers
 */
final class SponsoredProjectSerializer extends SilverStripeSerializer {
  protected static $array_mappings = [
    "Name" => "name:json_string",
    "Description" => "description:json_string",
    "Slug" => "slug:json_string",
    "Active" => "is_active:json_boolean",
    "ShouldShowOnNavBar" => "should_show_on_nav_bar:json_boolean",
    "SiteURL" => "site_url:json_url",
    "LogoUrl" => "logo_url:json_url",
    "ParentProjectId" => "parent_project_id:json_int",
    "SponsorshipTypesIds" => "sponsorship_types",
    "SubprojectIds" => "subprojects",
  ];

  protected static $expand_mappings = [
    "sponsorship_types" => [
      "type" => Many2OneExpandSerializer::class,
      "getter" => "getSponsorshipTypes",
    ],
    "subprojects" => [
      "type" => Many2OneExpandSerializer::class,
      "getter" => "getSubProjects",
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
    $project = $this->object;
    if (!$project instanceof SponsoredProject) {
      return $values;
    }

    if (!empty($expand)) {
      foreach (explode(",", $expand) as $relation) {
        $relation = trim($relation);
        if ($relation == "parent_project") {
          $parentProject = $project->getParentProject();
          if (!is_null($parentProject) && $project instanceof SponsoredProject) {
            $values["parent_project"] = SerializerRegistry::getInstance()
              ->getSerializer($project->getParentProject())
              ->serialize(
                AbstractSerializer::filterExpandByPrefix($expand, $relation),
                AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                $params,
              );
          }
        }
      }
    }

    return $values;
  }
}
