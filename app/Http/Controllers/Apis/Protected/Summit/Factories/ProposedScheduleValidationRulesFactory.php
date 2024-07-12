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
 * Class ProposedScheduleValidationRulesFactory
 * @package App\Http\Controllers
 */
final class ProposedScheduleValidationRulesFactory extends AbstractValidationRulesFactory {
  /**
   * @param array $payload
   * @return array
   */
  public static function buildForAdd(array $payload = []): array {
    return [
      "start_date" => "required|date_format:U",
      "end_date" => "required|required_with:start_date|date_format:U|after:start_date",
      "duration" => "sometimes|integer",
      "location_id" => "required|integer",
    ];
  }

  /**
   * @param array $payload
   * @return array
   */
  public static function buildForUpdate(array $payload = []): array {
    return [
      "event_ids" => "sometimes|int_array",
    ];
  }
}
