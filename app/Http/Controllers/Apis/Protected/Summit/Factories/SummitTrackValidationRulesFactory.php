<?php namespace App\Http\Controllers;
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

use App\Http\ValidationRulesFactories\AbstractValidationRulesFactory;

/**
 * Class SummitTrackValidationRulesFactory
 * @package App\Http\Controllers
 */
final class SummitTrackValidationRulesFactory extends AbstractValidationRulesFactory {
  /**
   * @param array $payload
   * @return array
   */
  public static function buildForAdd(array $payload = []): array {
    return [
      "name" => "sometimes|string|max:100",
      "description" => "sometimes|string|max:1500",
      "color" => "sometimes|hex_color|max:50",
      "text_color" => "sometimes|hex_color|max:50",
      "code" => "sometimes|string|max:5",
      "session_count" => "sometimes|integer",
      "alternate_count" => "sometimes|integer",
      "lightning_count" => "sometimes|integer",
      "lightning_alternate_count" => "sometimes|integer",
      "voting_visible" => "sometimes|boolean",
      "chair_visible" => "sometimes|boolean",
      "allowed_tags" => "sometimes|string_array",
      "allowed_access_levels" => "sometimes|int_array",
      "order" => "sometimes|integer|min:1",
      "proposed_schedule_transition_time" => "nullable|integer|min:1",
    ];
  }

  /**
   * @param array $payload
   * @return array
   */
  public static function buildForUpdate(array $payload = []): array {
    return self::buildForAdd($payload);
  }
}
