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
use App\Models\Foundation\ExtraQuestions\ExtraQuestionAnswer;
use Libs\ModelSerializers\AbstractSerializer;
/**
 * Class ExtraQuestionAnswerSerializer
 * @package ModelSerializers
 */
class ExtraQuestionAnswerSerializer extends SilverStripeSerializer {
  protected static $array_mappings = [
    "Value" => "value:json_string",
    "QuestionId" => "question_id:json_int",
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
    $answer = $this->object;
    if (!$answer instanceof ExtraQuestionAnswer) {
      return [];
    }
    $values = parent::serialize($expand, $fields, $relations, $params);

    if (!empty($expand)) {
      $exp_expand = explode(",", $expand);
      foreach ($exp_expand as $relation) {
        switch (trim($relation)) {
          case "question":
            if ($answer->hasQuestion()) {
              unset($values["question_id"]);
              $values["question"] = SerializerRegistry::getInstance()
                ->getSerializer($answer->getQuestion())
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
