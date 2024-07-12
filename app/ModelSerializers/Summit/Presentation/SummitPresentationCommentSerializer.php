<?php namespace App\ModelSerializers\Summit\Presentation;
/**
 * Copyright 2019 OpenStack Foundation
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
use models\summit\SummitPresentationComment;
use ModelSerializers\SerializerRegistry;
use ModelSerializers\SilverStripeSerializer;
/**
 * Class SummitPresentationCommentSerializer
 * @package App\ModelSerializers\Summit\Presentation
 */
final class SummitPresentationCommentSerializer extends SilverStripeSerializer {
  protected static $array_mappings = [
    "Body" => "body:json_string",
    "Activity" => "is_activity:json_boolean",
    "Public" => "is_public:json_boolean",
    "CreatorId" => "creator_id:json_int",
    "PresentationId" => "presentation_id:json_int",
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
    $comment = $this->object;

    if (!$comment instanceof SummitPresentationComment) {
      return [];
    }

    $values = parent::serialize($expand, $fields, $relations, $params);
    if (!empty($expand)) {
      foreach (explode(",", $expand) as $relation) {
        switch (trim($relation)) {
          case "creator":
            if ($comment->getCreatorId() > 0) {
              unset($values["creator_id"]);
              $values["creator"] = SerializerRegistry::getInstance()
                ->getSerializer($comment->getCreator())
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
