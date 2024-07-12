<?php namespace ModelSerializers;
/**
 * Copyright 2021 OpenStack Foundation
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
use models\summit\PresentationAction;
/**
 * Class PresentationActionSerializer
 * @package ModelSerializers
 */
final class PresentationActionSerializer extends SilverStripeSerializer {
  protected static $array_mappings = [
    "Completed" => "is_completed:json_boolean",
    "PresentationId" => "presentation_id:json_int",
    "TypeId" => "type_id:json_int",
    "CreatedById" => "created_by_id:json_int",
    "UpdatedById" => "updated_by_id:json_int",
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
    $action = $this->object;
    if (!$action instanceof PresentationAction) {
      return [];
    }
    $values = parent::serialize($expand, $fields, $relations, $params);
    if (!empty($expand)) {
      $exp_expand = explode(",", $expand);
      foreach ($exp_expand as $relation) {
        switch (trim($relation)) {
          case "presentation":
            unset($values["presentation_id"]);
            $values["presentation"] = SerializerRegistry::getInstance()
              ->getSerializer($action->getPresentation())
              ->serialize(
                AbstractSerializer::filterExpandByPrefix($expand, $relation),
                AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                $params,
              );
            break;
          case "type":
            unset($values["type_id"]);
            $values["type"] = SerializerRegistry::getInstance()
              ->getSerializer($action->getType())
              ->serialize(
                AbstractSerializer::filterExpandByPrefix($expand, $relation),
                AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                $params,
              );
            break;
          case "created_by":
            if ($action->hasCreatedBy()) {
              unset($values["created_by_id"]);
              $values["created_by"] = SerializerRegistry::getInstance()
                ->getSerializer($action->getCreatedBy())
                ->serialize(
                  AbstractSerializer::filterExpandByPrefix($expand, $relation),
                  AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                  AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                  $params,
                );
            }
            break;
          case "updated_by":
            if ($action->hasUpdatedBy()) {
              unset($values["updated_by_id"]);
              $values["updated_by"] = SerializerRegistry::getInstance()
                ->getSerializer($action->getUpdatedBy())
                ->serialize(
                  AbstractSerializer::filterExpandByPrefix($expand, $relation),
                  AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                  AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                  $params,
                );
            }
            break;
        }
      }
    }
    return $values;
  }
}
