<?php namespace ModelSerializers;
/**
 * Copyright 2018 OpenStack Foundation
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
use models\summit\RSVP;
/**
 * Class RSVPSerializer
 * @package ModelSerializers
 */
final class RSVPSerializer extends SilverStripeSerializer {
  protected static $array_mappings = [
    "OwnerId" => "owner_id:json_int",
    "EventId" => "event_id:json_int",
    "SeatType" => "seat_type:json_string",
    "Created" => "created:datetime_epoch",
    "ConfirmationNumber" => "confirmation_number:json_string",
    "EventUri" => "event_uri:json_string",
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
    $rsvp = $this->object;
    if (!$rsvp instanceof RSVP) {
      return [];
    }
    $values = parent::serialize($expand, $fields, $relations, $params);

    $answers = [];
    foreach ($rsvp->getAnswers() as $answer) {
      $answers[] = $answer->getId();
    }

    $values["answers"] = $answers;

    if (!empty($expand)) {
      foreach (explode(",", $expand) as $relation) {
        $relation = trim($relation);
        switch ($relation) {
          case "owner":
            if (!$rsvp->hasOwner()) {
              continue;
            }
            unset($values["owner_id"]);
            $values["owner"] = SerializerRegistry::getInstance()
              ->getSerializer($rsvp->getOwner())
              ->serialize(AbstractSerializer::filterExpandByPrefix($expand, $relation));
            break;
          case "event":
            if (!$rsvp->hasEvent()) {
              continue;
            }
            unset($values["event_id"]);
            $values["event"] = SerializerRegistry::getInstance()
              ->getSerializer($rsvp->getEvent())
              ->serialize(AbstractSerializer::filterExpandByPrefix($expand, $relation));
            break;
          case "answers":
            $answers = [];
            foreach ($rsvp->getAnswers() as $answer) {
              $answers[] = SerializerRegistry::getInstance()
                ->getSerializer($answer)
                ->serialize(AbstractSerializer::filterExpandByPrefix($expand, $relation));
            }
            $values["answers"] = $answers;
            break;
        }
      }
    }

    return $values;
  }
}
