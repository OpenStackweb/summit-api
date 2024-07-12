<?php namespace ModelSerializers;

/**
 * Copyright 2017 OpenStack Foundation
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

use models\summit\SummitEventWithFile;

/**
 * Class SummitEventWithFileSerializer
 * @package ModelSerializers
 */
class SummitEventWithFileSerializer extends SummitEventSerializer {
  protected static $allowed_fields = ["attachment"];

  public function serialize(
    $expand = null,
    array $fields = [],
    array $relations = [],
    array $params = [],
  ) {
    $event = $this->object;
    if (!$event instanceof SummitEventWithFile) {
      return [];
    }

    $values = parent::serialize($expand, $fields, $relations, $params);
    if (in_array("attachment", $fields)) {
      $values["attachment"] = $event->hasAttachment() ? $event->getAttachment()->getUrl() : null;
    }

    return $values;
  }
}
