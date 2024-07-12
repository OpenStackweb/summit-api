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
use models\summit\PresentationActionType;

/**
 * Class PresentationActionTypeSerializer
 * @package ModelSerializers
 */
final class PresentationActionTypeSerializer extends SilverStripeSerializer {
  protected static $array_mappings = [
    "Label" => "label:json_string",
    "SummitId" => "summit_id:json_int",
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
    if (!$action instanceof PresentationActionType) {
      return [];
    }
    $values = parent::serialize($expand, $fields, $relations, $params);

    if (array_has($params, "selection_plan_id")) {
      $values["order"] = $action->getSelectionPlanAssignmentOrder(
        intval($params["selection_plan_id"]),
      );
    }

    if (!empty($expand)) {
      $exp_expand = explode(",", $expand);
      foreach ($exp_expand as $relation) {
        switch (trim($relation)) {
          case "summit":
            unset($values["summit_id"]);
            $values["summit"] = SerializerRegistry::getInstance()
              ->getSerializer($action->getSummit())
              ->serialize(
                AbstractSerializer::filterExpandByPrefix($expand, $relation),
                AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                $params,
              );
            break;
        }
      }
    }
    return $values;
  }
}
