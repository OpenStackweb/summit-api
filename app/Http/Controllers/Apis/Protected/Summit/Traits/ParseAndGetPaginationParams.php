<?php namespace App\Http\Controllers;
/*
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

use Illuminate\Support\Facades\Request;
use libs\utils\PaginationValidationRules;

/**
 * Trait ParseAndGetPaginationParams
 * @package App\Http\Controllers
 */
trait ParseAndGetPaginationParams {
  /**
   * @param callable|null $defaultPageSize
   * @return array
   */
  public static function getPaginationParams(callable $defaultPageSize = null): array {
    $page = 1;
    $per_page = is_null($defaultPageSize)
      ? PaginationValidationRules::PerPageMin
      : call_user_func($defaultPageSize);

    if (Request::has(PaginationValidationRules::PageParam)) {
      $page = intval(Request::get(PaginationValidationRules::PageParam));
    }

    if (Request::has(PaginationValidationRules::PerPageParam)) {
      $per_page = intval(Request::get(PaginationValidationRules::PerPageParam));
    }

    return [$page, $per_page];
  }
}
