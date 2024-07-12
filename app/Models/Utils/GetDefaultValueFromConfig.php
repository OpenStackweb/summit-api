<?php namespace App\Models\Utils;
/**
 * Copyright 2020 OpenStack Foundation
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
use Illuminate\Support\Facades\Config;
/**
 * Trait GetDefaultValueFromConfig
 * @package App\Models\Utils
 */
trait GetDefaultValueFromConfig {
  /**
   * @param mixed|null $val
   * @param string $config_key
   * @return mixed|string
   */
  private static function _get($val, string $config_key) {
    if (is_null($val) || empty($val)) {
      $val = Config::get($config_key, null);
    }
    return $val;
  }
}
