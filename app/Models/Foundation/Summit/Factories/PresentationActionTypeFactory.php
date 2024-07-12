<?php namespace App\Models\Foundation\Summit\Factories;
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
use models\summit\PresentationActionType;
/**
 * Class PresentationActionTypeFactory
 * @package App\Models\Foundation\Summit\Factories
 */
final class PresentationActionTypeFactory {
  /**
   * @param array $data
   * @return PresentationActionType
   */
  public static function build(array $data): PresentationActionType {
    return self::populate(new PresentationActionType(), $data);
  }

  /**
   * @param PresentationActionType $action
   * @param array $data
   * @return PresentationActionType
   */
  public static function populate(
    PresentationActionType $action,
    array $data,
  ): PresentationActionType {
    if (isset($data["label"])) {
      $action->setLabel(trim($data["label"]));
    }

    return $action;
  }
}
