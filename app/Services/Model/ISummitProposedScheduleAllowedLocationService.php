<?php namespace App\Services\Model;
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

use App\Models\Foundation\Summit\ProposedSchedule\SummitProposedScheduleAllowedDay;
use App\Models\Foundation\Summit\ProposedSchedule\SummitProposedScheduleAllowedLocation;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\summit\PresentationCategory;

/**
 * Interface ISummitProposedScheduleAllowedLocationService
 * @package App\Services\Model
 */
interface ISummitProposedScheduleAllowedLocationService {
  /**
   * @param PresentationCategory $track
   * @param array $payload
   * @return SummitProposedScheduleAllowedLocation|null
   * @throws EntityNotFoundException
   * @throws ValidationException
   */
  public function addProposedLocationToTrack(
    PresentationCategory $track,
    array $payload,
  ): ?SummitProposedScheduleAllowedLocation;

  /**
   * @param PresentationCategory $track
   * @param int $allowed_location_id
   * @return void
   * @throws EntityNotFoundException
   * @throws ValidationException
   */
  public function deleteProposedLocationFromTrack(
    PresentationCategory $track,
    int $allowed_location_id,
  ): void;

  /**
   * @param PresentationCategory $track
   * @return void
   * @throws EntityNotFoundException
   * @throws ValidationException
   */
  public function deleteAllProposedLocationFromTrack(PresentationCategory $track): void;

  /**
   * @param PresentationCategory $track
   * @param int $allowed_location_id
   * @param array $payload
   * @return SummitProposedScheduleAllowedDay|null
   * @throws EntityNotFoundException
   * @throws ValidationException
   */
  public function addAllowedDayToProposedLocation(
    PresentationCategory $track,
    int $allowed_location_id,
    array $payload,
  ): ?SummitProposedScheduleAllowedDay;

  /**
   * @param PresentationCategory $track
   * @param int $allowed_location_id
   * @param int $allowed_day_id
   * @param array $payload
   * @return SummitProposedScheduleAllowedDay|null
   * @throws EntityNotFoundException
   * @throws ValidationException
   */
  public function updateAllowedDayToProposedLocation(
    PresentationCategory $track,
    int $allowed_location_id,
    int $allowed_day_id,
    array $payload,
  ): ?SummitProposedScheduleAllowedDay;

  /**
   * @param PresentationCategory $track
   * @param int $allowed_location_id
   * @param int $allowed_day_id
   * @return void
   * @throws EntityNotFoundException
   * @throws ValidationException
   */
  public function deleteAllowedDayToProposedLocation(
    PresentationCategory $track,
    int $allowed_location_id,
    int $allowed_day_id,
  ): void;

  /**
   * @param PresentationCategory $track
   * @param int $allowed_location_id
   * @return void
   * @throws EntityNotFoundException
   * @throws ValidationException
   */
  public function deleteAllAllowedDayToProposedLocation(
    PresentationCategory $track,
    int $allowed_location_id,
  ): void;
}
