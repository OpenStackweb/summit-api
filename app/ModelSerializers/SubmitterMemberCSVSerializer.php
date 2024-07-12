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

use models\main\Member;

/**
 * Class SubmitterMemberCSVSerializer
 * @package ModelSerializers
 */
final class SubmitterMemberCSVSerializer extends AdminMemberSerializer {
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
      $accepted_presentations = $submitter->getAcceptedPresentations(
        $summit,
        $params["filter"] ?? null,
      );
      $values["accepted_presentations"] = join(
        "|",
        array_map(function ($value): string {
          return "{$value->getId()}-{$value->getTitle()}";
        }, $accepted_presentations),
      );
      $values["accepted_presentations_count"] = count($accepted_presentations);
    }

    if (in_array("alternate_presentations", $relations) && !is_null($summit)) {
      $alternate_presentations = $submitter->getAlternatePresentations(
        $summit,
        $params["filter"] ?? null,
      );
      $values["alternate_presentations"] = join(
        "|",
        array_map(function ($value): string {
          return "{$value->getId()}-{$value->getTitle()}";
        }, $alternate_presentations),
      );
      $values["alternate_presentations_count"] = count($alternate_presentations);
    }

    if (in_array("rejected_presentations", $relations) && !is_null($summit)) {
      $rejected_presentations = $submitter->getRejectedPresentations(
        $summit,
        $params["filter"] ?? null,
      );
      $values["rejected_presentations"] = join(
        "|",
        array_map(function ($value): string {
          return "{$value->getId()}-{$value->getTitle()}";
        }, $rejected_presentations),
      );
      $values["rejected_presentations_count"] = count($rejected_presentations);
    }

    return $values;
  }
}
