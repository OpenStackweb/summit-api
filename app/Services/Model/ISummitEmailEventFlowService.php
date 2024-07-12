<?php namespace App\Services\Model;
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
use App\Models\Foundation\Summit\EmailFlows\SummitEmailEventFlow;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\summit\Summit;
/**
 * Interface ISummitEmailEventFlowService
 * @package App\Services\Model
 */
interface ISummitEmailEventFlowService {
  /**
   * @param Summit $summit
   * @param int $event_id
   * @param array $data
   * @return SummitEmailEventFlow
   * @throws EntityNotFoundException
   * @throws ValidationException
   */
  public function updateEmailEventFlow(
    Summit $summit,
    int $event_id,
    array $data,
  ): SummitEmailEventFlow;

  /**
   * @param Summit $summit
   * @param int $event_id
   * @throws EntityNotFoundException
   */
  public function deleteEmailEventFlow(Summit $summit, int $event_id): void;
}
