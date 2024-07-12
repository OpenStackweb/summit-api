<?php namespace App\Services\Model;
/*
 * Copyright 2022 OpenStack Foundation
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

use App\Models\Foundation\Main\IFileConstants;
use Illuminate\Http\UploadedFile;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\main\File;
use models\summit\Summit;
use models\summit\SummitSponsorshipType;

/**
 * Interface ISummitSponsorshipTypeService
 * @package App\Services\Model
 */
interface ISummitSponsorshipTypeService {
  /**
   * @param Summit $summit
   * @param array $payload
   * @return SummitSponsorshipType
   * @throws EntityNotFoundException
   * @throws ValidationException
   */
  public function add(Summit $summit, array $payload): SummitSponsorshipType;

  /**
   * @param Summit $summit
   * @param int $sponsorship_id
   * @param array $payload
   * @return SummitSponsorshipType
   * @throws EntityNotFoundException
   * @throws ValidationException
   */
  public function update(
    Summit $summit,
    int $sponsorship_id,
    array $payload,
  ): SummitSponsorshipType;

  /**
   * @param Summit $summit
   * @param int $sponsorship_id
   * @throws EntityNotFoundException
   * @throws ValidationException
   */
  public function delete(Summit $summit, int $sponsorship_id): void;

  /**
   * @param Summit $summit
   * @param int $sponsorship_id
   * @param UploadedFile $file
   * @param int $max_file_size
   * @return File
   * @throws EntityNotFoundException
   * @throws ValidationException
   */
  public function addBadgeImage(
    Summit $summit,
    int $sponsorship_id,
    UploadedFile $file,
    $max_file_size = IFileConstants::MaxImageSizeInBytes,
  ): File;

  /**
   * @param Summit $summit
   * @param int $sponsorship_id
   * @throws EntityNotFoundException
   * @throws ValidationException
   */
  public function deleteBadgeImage(Summit $summit, int $sponsorship_id): void;
}
