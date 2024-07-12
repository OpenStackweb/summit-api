<?php namespace App\Http\Controllers;
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

use App\Http\ValidationRulesFactories\AbstractValidationRulesFactory;

/**
 * Class SummitBadgeViewTypeValidationRulesFactory
 * @package App\Http\Controllers
 */
final class SummitBadgeViewTypeValidationRulesFactory extends AbstractValidationRulesFactory {
  /**
   * @param array $payload
   * @return array
   */
  public static function buildForAdd(array $payload = []): array {
    return [
      "name" => "required|single_word|max:50",
      "description" => "required|string|max:255",
      "is_default" => "required|boolean",
    ];
  }

  /**
   * @param array $payload
   * @return array
   */
  public static function buildForUpdate(array $payload = []): array {
    return [
      "name" => "sometimes|single_word|max:50",
      "description" => "sometimes|string|max:255",
      "is_default" => "sometimes|boolean",
    ];
  }
}
