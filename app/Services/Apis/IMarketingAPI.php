<?php namespace services\apis;
/**
 * Copyright 2024 OpenStack Foundation
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
 * Interface IMarketingAPI
 * @package services\apis
 */
interface IMarketingAPI {
  /**
   * @param int $summit_id
   * @param string $search_pattern
   * @param int $page
   * @param int $per_page
   * @return array
   */
  public function getConfigValues(
    int $summit_id,
    string $search_pattern,
    int $page = 1,
    int $per_page = 100,
  ): array;
}
