<?php namespace services\model;
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

use models\exceptions\ValidationException;
use models\summit\Summit;
use utils\Filter;

/**
 * Interface ISubmitterService
 * @package services\model
 */
interface ISubmitterService {
  /**
   * @param Summit $summit
   * @param array $payload
   * @param mixed $filter
   */
  public function triggerSendEmails(Summit $summit, array $payload, $filter = null): void;

  /**
   * @param int $summit_id
   * @param array $payload
   * @param Filter|null $filter
   * @throws ValidationException
   */
  public function sendEmails(int $summit_id, array $payload, Filter $filter = null): void;
}
