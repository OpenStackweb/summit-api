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
use models\summit\SummitSelectedPresentation;
use models\summit\SummitSelectedPresentationList;

/**
 * Class SummitSelectedPresentationList
 * @package ModelSerializers
 */
final class SummitSelectedPresentationListSerializer extends SilverStripeSerializer {
  protected static $array_mappings = [
    "Name" => "name:json_string",
    "ListType" => "type:json_string",
    "Hash" => "hash:json_string",
    "CategoryId" => "category_id:json_int",
    "OwnerId" => "owner_id:json_int",
    "SelectionPlanId" => "selection_plan_id:json_int",
  ];

  protected static $allowed_relations = ["selected_presentations", "interested_presentations"];

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
    $presentation_list = $this->object;

    if (!$presentation_list instanceof SummitSelectedPresentationList) {
      return [];
    }

    $values = parent::serialize($expand, $fields, $relations, $params);

    if (in_array("selected_presentations", $relations)) {
      $selected_presentations = [];
      foreach (
        $presentation_list->getSelectedPresentationsByCollection(
          SummitSelectedPresentation::CollectionSelected,
        )
        as $p
      ) {
        $selected_presentations[] = $p->getId();
      }
      $values["selected_presentations"] = $selected_presentations;
    }

    if (
      in_array("interested_presentations", $relations) &&
      $presentation_list->getListType() == SummitSelectedPresentationList::Individual
    ) {
      $interested_presentations = [];
      foreach (
        $presentation_list->getSelectedPresentationsByCollection(
          SummitSelectedPresentation::CollectionMaybe,
        )
        as $p
      ) {
        $interested_presentations[] = $p->getId();
      }
      $values["interested_presentations"] = $interested_presentations;
    }

    if (!empty($expand)) {
      foreach (explode(",", $expand) as $relation) {
        $relation = trim($relation);
        switch ($relation) {
          case "owner":
            if ($presentation_list->getMemberId() > 0) {
              unset($values["owner_id"]);
              $values["owner"] = SerializerRegistry::getInstance()
                ->getSerializer($presentation_list->getMember())
                ->serialize(
                  AbstractSerializer::filterExpandByPrefix($expand, $relation),
                  AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                  AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                  $params,
                );
            }
            break;
          case "category":
            if ($presentation_list->getCategoryId() > 0) {
              unset($values["category_id"]);
              $values["category"] = SerializerRegistry::getInstance()
                ->getSerializer($presentation_list->getCategory())
                ->serialize(
                  AbstractSerializer::filterExpandByPrefix($expand, $relation),
                  AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                  AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                  $params,
                );
            }
            break;
          case "selection_plan":
            if ($presentation_list->getSelectionPlanId() > 0) {
              unset($values["selection_plan_id"]);
              $values["selection_plan"] = SerializerRegistry::getInstance()
                ->getSerializer($presentation_list->getSelectionPlan())
                ->serialize(
                  AbstractSerializer::filterExpandByPrefix($expand, $relation),
                  AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                  AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                  $params,
                );
            }
            break;
          case "selected_presentations":
            $selected_presentations = [];
            foreach (
              $presentation_list->getSelectedPresentationsByCollection(
                SummitSelectedPresentation::CollectionSelected,
              )
              as $p
            ) {
              $selected_presentations[] = SerializerRegistry::getInstance()
                ->getSerializer($p)
                ->serialize(
                  AbstractSerializer::filterExpandByPrefix($expand, $relation),
                  AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                  AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                  $params,
                );
            }
            $values["selected_presentations"] = $selected_presentations;
            break;
          case "interested_presentations":
            if ($presentation_list->getListType() == SummitSelectedPresentationList::Individual) {
              $interested_presentations = [];
              foreach (
                $presentation_list->getSelectedPresentationsByCollection(
                  SummitSelectedPresentation::CollectionMaybe,
                )
                as $p
              ) {
                $interested_presentations[] = SerializerRegistry::getInstance()
                  ->getSerializer($p)
                  ->serialize(
                    AbstractSerializer::filterExpandByPrefix($expand, $relation),
                    AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                    AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                    $params,
                  );
              }
              $values["interested_presentations"] = $interested_presentations;
            }
            break;
        }
      }
    }
    return $values;
  }
}
