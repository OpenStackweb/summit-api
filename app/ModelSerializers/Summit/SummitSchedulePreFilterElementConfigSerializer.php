<?php namespace App\ModelSerializers\Summit;
/*
 * Copyright 2022 OpenStack Foundation
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
use models\summit\SummitSchedulePreFilterElementConfig;
use ModelSerializers\SilverStripeSerializer;
/**
 * Class SummitSchedulePreFilterElementConfigSerializer
 * @package App\ModelSerializers\Summit
 */
final class SummitSchedulePreFilterElementConfigSerializer extends SilverStripeSerializer {
  /**
   * @param null $expand
   * @param array $fields
   * @param array $relation
   * @param array $params
   * @return array
   */
  public function serialize(
    $expand = null,
    array $fields = [],
    array $relations = [],
    array $params = [],
  ) {
    $filter = $this->object;
    if (!$filter instanceof SummitSchedulePreFilterElementConfig) {
      return [];
    }
    $values = parent::serialize($expand, $fields, $relations, $params);

    if (in_array("values", $relations) && !isset($values["values"])) {
      $values["values"] = $filter->getValues();
    }

    return [$filter->getType() => $values];
  }
}
