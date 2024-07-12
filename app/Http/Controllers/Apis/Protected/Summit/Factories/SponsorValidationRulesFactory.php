<?php namespace App\Http\Controllers;
/**
 * Copyright 2019 OpenStack Foundation
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
 * Class SponsorValidationRulesFactory
 * @package App\Http\Controllers
 */
final class SponsorValidationRulesFactory extends AbstractValidationRulesFactory {
  /**
   * @param array $payload
   * @return array
   */
  public static function buildForAdd(array $payload = []): array {
    return [
      "company_id" => "required|integer",
      "featured_event_id" => "sometimes|integer",
      "sponsorship_id" => "required|integer",
      "is_published" => "sometimes|boolean",
      "show_logo_in_event_page" => "sometimes|boolean",
      "marquee" => "sometimes|string|max:150",
      "intro" => "sometimes|string|max:1500",
      "external_link" => "sometimes|url|max:255",
      "video_link" => "sometimes|url|max:255",
      "chat_link" => "sometimes|url|max:255",
      "side_image_alt_text" => "sometimes|string|max:255",
      "header_image_alt_text" => "sometimes|string|max:255",
      "header_image_mobile_alt_text" => "sometimes|string|max:255",
      "carousel_advertise_image_alt_text" => "sometimes|string|max:255",
    ];
  }

  /**
   * @param array $payload
   * @return array
   */
  public static function buildForUpdate(array $payload = []): array {
    return [
      "company_id" => "sometimes|integer",
      "featured_event_id" => "sometimes|integer",
      "sponsorship_id" => "sometimes|integer",
      "order" => "sometimes|integer|min:1",
      "is_published" => "sometimes|boolean",
      "show_logo_in_event_page" => "sometimes|boolean",
      "marquee" => "sometimes|string|max:150",
      "intro" => "sometimes|string|max:1500",
      "external_link" => "sometimes|string|max:255",
      "video_link" => "sometimes|string|max:255",
      "chat_link" => "sometimes|string|max:255",
      "side_image_alt_text" => "sometimes|string|max:255",
      "header_image_alt_text" => "sometimes|string|max:255",
      "header_image_mobile_alt_text" => "sometimes|string|max:255",
      "carousel_advertise_image_alt_text" => "sometimes|string|max:255",
    ];
  }
}
