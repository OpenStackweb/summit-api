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
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\summit\SummitMediaFileType;
/**
 * Interface ISummitMediaFileTypeService
 * @package App\Services\Model
 */
interface ISummitMediaFileTypeService {
  /**
   * @param array $payload
   * @return SummitMediaFileType
   * @throws ValidationException
   */
  public function add(array $payload): SummitMediaFileType;

  /**
   * @param int $id
   * @param array $payload
   * @return SummitMediaFileType
   * @throws EntityNotFoundException
   * @throws ValidationException
   */
  public function update(int $id, array $payload): SummitMediaFileType;

  /**
   * @param int $id
   * @throws EntityNotFoundException
   * @throws ValidationException
   */
  public function delete(int $id): void;
}
