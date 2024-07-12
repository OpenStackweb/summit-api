<?php namespace ModelSerializers;
use App\Models\Foundation\ExtraQuestions\ExtraQuestionType;
use App\Models\Foundation\Summit\ExtraQuestions\SummitSelectionPlanExtraQuestionType;

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

/**
 * Class SelectionPlanExtraQuestionTypeSerializer
 * @package ModelSerializers
 */
final class SummitSelectionPlanExtraQuestionTypeSerializer extends ExtraQuestionTypeSerializer {
  protected static $array_mappings = [
    "SummitID" => "summit_id:json_int",
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
    $question = $this->object;
    if (!$question instanceof SummitSelectionPlanExtraQuestionType) {
      return [];
    }
    $values = parent::serialize($expand, $fields, $relations, $params);
    if (isset($values["order"])) {
      unset($values["order"]);
    }

    if (isset($params["selection_plan_id"])) {
      $selection_plan_id = intval($params["selection_plan_id"]);
      $order = $question->getOrderByAssignedSelectionPlan($selection_plan_id);
      if (!is_null($order)) {
        $values["order"] = $order;
      }
    }
    return $values;
  }
}
