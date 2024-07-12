<?php namespace App\Services\Model;
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
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\main\Member;
use models\summit\SponsorBadgeScan;
use models\summit\SponsorUserInfoGrant;
use models\summit\Summit;
use models\summit\SummitAttendeeBadge;

/**
 * Interface ISponsorBadgeScanService
 * @package App\Services\Model
 */
interface ISponsorUserInfoGrantService {
  /**
   * @param Summit $summit
   * @param int $sponsor_id
   * @param Member $current_member
   * @return SponsorUserInfoGrant
   * @throws EntityNotFoundException
   * @throws ValidationException
   */
  public function addGrant(
    Summit $summit,
    int $sponsor_id,
    Member $current_member,
  ): SponsorUserInfoGrant;

  /**
   * @param Summit $summit
   * @param Member $current_member
   * @param array $data
   * @return SponsorBadgeScan
   * @throws EntityNotFoundException
   * @throws ValidationException
   */
  public function addBadgeScan(
    Summit $summit,
    Member $current_member,
    array $data,
  ): SponsorBadgeScan;

  /**
   * @param Summit $summit
   * @param Member $current_member
   * @param int $scan_id
   * @param array $data
   * @return SponsorBadgeScan
   * @throws EntityNotFoundException
   * @throws ValidationException
   */
  public function updateBadgeScan(
    Summit $summit,
    Member $current_member,
    int $scan_id,
    array $data,
  ): SponsorBadgeScan;

  /**
   * @param Summit $summit
   * @param Member $current_member
   * @param int $scan_id
   * @return SponsorBadgeScan
   * @throws EntityNotFoundException
   * @throws ValidationException
   */
  public function getBadgeScan(
    Summit $summit,
    Member $current_member,
    int $scan_id,
  ): SponsorBadgeScan;
}
