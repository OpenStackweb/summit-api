<?php namespace App\Http\Controllers;
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

/**
 * Class PresentationVideoValidationRulesFactory
 * @package App\Http\Controllers
 */
final class PresentationVideoValidationRulesFactory {
  public static function build(array $data, $update = false) {
    $former_rules = PresentationMaterialValidationRulesFactory::build($data, $update);
    if ($update) {
      return array_merge($former_rules, [
        "youtube_id" => "required_without:external_url|alpha_dash",
        "external_url" => "required_without:youtube_id|string:512|url",
      ]);
    }

    return array_merge($former_rules, [
      "youtube_id" => "required_without:external_url|alpha_dash",
      "external_url" => "required_without:youtube_id|string:512|url",
    ]);
  }
}
