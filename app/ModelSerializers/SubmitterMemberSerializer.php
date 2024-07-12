<?php namespace ModelSerializers;

/**
 * Copyright 2023 OpenStack Foundation
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
use models\main\Member;

/**
 * Class SubmitterMemberSerializer
 * @package ModelSerializers
 */
final class SubmitterMemberSerializer extends AdminMemberSerializer {
  protected static $allowed_relations = [
    "accepted_presentations",
    "alternate_presentations",
    "rejected_presentations",
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
  ): array {
    $submitter = $this->object;

    if (!$submitter instanceof Member) {
      return [];
    }

    $values = parent::serialize($expand, $fields, $relations, $params);

    $summit = $params["summit"] ?? null;

    if (in_array("accepted_presentations", $relations) && !is_null($summit)) {
      $values["accepted_presentations"] = $submitter->getAcceptedPresentationIds(
        $summit,
        $params["filter"] ?? null,
      );
    }

    if (in_array("alternate_presentations", $relations) && !is_null($summit)) {
      $values["alternate_presentations"] = $submitter->getAlternatePresentationIds(
        $summit,
        $params["filter"] ?? null,
      );
    }

    if (in_array("rejected_presentations", $relations) && !is_null($summit)) {
      $values["rejected_presentations"] = $submitter->getRejectedPresentationIds(
        $summit,
        $params["filter"] ?? null,
      );
    }

    if (!empty($expand)) {
      foreach (explode(",", $expand) as $relation) {
        $relation = trim($relation);
        switch ($relation) {
          case "accepted_presentations":
            $accepted_presentations = [];
            foreach (
              $submitter->getAcceptedPresentations($summit, $params["filter"] ?? null)
              as $p
            ) {
              $accepted_presentations[] = SerializerRegistry::getInstance()
                ->getSerializer($p)
                ->serialize(
                  AbstractSerializer::filterExpandByPrefix($expand, $relation),
                  AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                  AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                  $params,
                );
            }
            $values["accepted_presentations"] = $accepted_presentations;
            break;
          case "alternate_presentations":
            $alternate_presentations = [];
            foreach (
              $submitter->getAlternatePresentations($summit, $params["filter"] ?? null)
              as $p
            ) {
              $alternate_presentations[] = SerializerRegistry::getInstance()
                ->getSerializer($p)
                ->serialize(
                  AbstractSerializer::filterExpandByPrefix($expand, $relation),
                  AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                  AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                  $params,
                );
            }
            $values["alternate_presentations"] = $alternate_presentations;
            break;
          case "rejected_presentations":
            $rejected_presentations = [];
            foreach (
              $submitter->getRejectedPresentations($summit, $params["filter"] ?? null)
              as $p
            ) {
              $rejected_presentations[] = SerializerRegistry::getInstance()
                ->getSerializer($p)
                ->serialize(
                  AbstractSerializer::filterExpandByPrefix($expand, $relation),
                  AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                  AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                  $params,
                );
            }
            $values["rejected_presentations"] = $rejected_presentations;
            break;
        }
      }
    }

    return $values;
  }
}
