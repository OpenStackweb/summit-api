<?php namespace ModelSerializers;
/*
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

use models\summit\SpeakersSummitRegistrationPromoCode;

/**
 * Class SpeakersSummitRegistrationPromoCodeCSVSerializer
 * @package ModelSerializers
 */
class SpeakersSummitRegistrationPromoCodeCSVSerializer extends
  SpeakersSummitRegistrationPromoCodeSerializer {
  use SummitRegistrationPromoCodeCSVSerializerTrait;

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
    $code = $this->object;
    if (!$code instanceof SpeakersSummitRegistrationPromoCode) {
      return [];
    }
    $values = self::serializeFields2CSV(
      $code,
      parent::serialize($expand, $fields, $relations, $params),
    );

    $owner_name = [];
    $owner_email = [];
    foreach ($code->getOwners() as $owner) {
      $owner_name[] = $owner->getSpeaker()->getFullName();
      $owner_email[] = $owner->getSpeaker()->getEmail();
    }

    $values["owner_name"] = implode("|", $owner_name);
    $values["owner_email"] = implode("|", $owner_email);
    return $values;
  }
}
