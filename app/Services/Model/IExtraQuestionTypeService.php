<?php namespace App\Services\Model;
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
use App\Models\Foundation\Main\ExtraQuestions\SubQuestionRule;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\summit\Summit;
/**
 * Interface IExtraQuestionTypeService
 * @package App\Services\Model
 */
interface IExtraQuestionTypeService {
  /**
   * @param Summit $summit
   * @param int $parent_id
   * @param array $payload
   * @return SubQuestionRule
   * @throws EntityNotFoundException
   * @throws ValidationException
   */
  public function addSubQuestionRule(
    Summit $summit,
    int $parent_id,
    array $payload,
  ): SubQuestionRule;

  /**
   * @param Summit $summit
   * @param int $parent_id
   * @param int $rule_id
   * @param array $payload
   * @return SubQuestionRule
   * @throws EntityNotFoundException
   * @throws ValidationException
   */
  public function updateSubQuestionRule(
    Summit $summit,
    int $parent_id,
    int $rule_id,
    array $payload,
  ): SubQuestionRule;

  /**
   * @param Summit $summit
   * @param int $parent_id
   * @param int $rule_id
   * @throws EntityNotFoundException
   * @throws ValidationException
   */
  public function deleteSubQuestionRule(Summit $summit, int $parent_id, int $rule_id): void;
}
