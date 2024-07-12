<?php namespace models\summit;
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

/**
 * Class SummitBadgeViewTypeFactory
 * @package models\summit
 */
final class SummitBadgeViewTypeFactory {
  /**
   * @param array $payload
   * @return SummitBadgeViewType
   */
  public static function build(array $payload): SummitBadgeViewType {
    return self::populate(new SummitBadgeViewType(), $payload);
  }

  /**
   * @param SummitBadgeViewType $type
   * @param array $payload
   * @return SummitBadgeViewType
   */
  public static function populate(SummitBadgeViewType $type, array $payload): SummitBadgeViewType {
    if (isset($payload["name"])) {
      $type->setName(trim($payload["name"]));
    }

    if (isset($payload["description"])) {
      $type->setDescription(trim($payload["description"]));
    }

    if (isset($payload["is_default"])) {
      $type->setDefault(boolval($payload["is_default"]));
    }

    return $type;
  }
}
