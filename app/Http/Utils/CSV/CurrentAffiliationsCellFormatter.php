<?php namespace App\Http\Utils;
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

/**
 * Class CurrentAffiliationsCellFormatter
 * @package App\Http\Utils
 */
final class CurrentAffiliationsCellFormatter implements ICellFormatter {
  /**
   * @param string $val
   * @return string
   */
  public function format($val) {
    $res = "";
    foreach ($val as $affiliation) {
      if (!isset($affiliation["is_current"])) {
        continue;
      }
      if (boolval($affiliation["is_current"]) == false) {
        continue;
      }
      if (!isset($affiliation["organization"])) {
        continue;
      }
      $organization = $affiliation["organization"];
      if (!empty($res)) {
        $res .= "|";
      }
      $res .= $organization["name"];
    }
    return $res;
  }
}
