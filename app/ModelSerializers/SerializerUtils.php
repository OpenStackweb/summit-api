<?php namespace App\ModelSerializers;
use Illuminate\Support\Facades\Request;

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

/**
 * Class SerializerUtils
 * @package App\ModelSerializers
 */
final class SerializerUtils {
  public static function getExpand() {
    return Request::input("expand", "");
  }

  public static function getRelations() {
    $relations = Request::input("relations", "");
    return !empty($relations) ? explode(",", $relations) : [];
  }

  public static function getFields() {
    $fields = Request::input("fields", "");
    return !empty($fields) ? explode(",", $fields) : [];
  }
}
