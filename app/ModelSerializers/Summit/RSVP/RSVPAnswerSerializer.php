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
use models\summit\RSVPAnswer;
/**
 * Class RSVPAnswerSerializer
 * @package ModelSerializers
 */
class RSVPAnswerSerializer extends SilverStripeSerializer {
  protected static $array_mappings = [
    "Value" => "value:json_string",
    "RSVPId" => "rsvp_id:json_int",
    "QuestionId" => "question_id:json_string",
    "Created" => "created:datetime_epoch",
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
    if (!$answer instanceof RSVPAnswer) {
      return [];
    }
    $values = parent::serialize($expand, $fields, $relations, $params);

    if (!empty($expand)) {
      foreach (explode(",", $expand) as $relation) {
        $relation = trim($relation);
        switch ($relation) {
          case "rsvp":
            unset($values["rsvp_id"]);
            $values["rsvp_id"] = SerializerRegistry::getInstance()
              ->getSerializer($answer->getRsvp())
              ->serialize(AbstractSerializer::filterExpandByPrefix($expand, $relation));
            break;
          case "question":
            unset($values["question_id"]);
            $values["question"] = SerializerRegistry::getInstance()
              ->getSerializer($answer->getQuestion())
              ->serialize(AbstractSerializer::filterExpandByPrefix($expand, $relation));
            break;
        }
      }
    }

    return $values;
  }
}
