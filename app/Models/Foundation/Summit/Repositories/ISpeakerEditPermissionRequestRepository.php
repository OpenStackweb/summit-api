<?php namespace App\Models\Foundation\Summit\Repositories;
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
use App\Models\Foundation\Summit\Speakers\SpeakerEditPermissionRequest;
use models\main\Member;
use models\summit\PresentationSpeaker;
use models\utils\IBaseRepository;
/**
 * Interface ISpeakerEditPermissionRequestRepository
 * @package App\Models\Foundation\Summit\Repositories
 */
interface ISpeakerEditPermissionRequestRepository extends IBaseRepository {
  /**
   * @param PresentationSpeaker $speaker
   * @param Member $requestor
   * @return ?SpeakerEditPermissionRequest
   */
  public function getBySpeakerAndRequestor(
    PresentationSpeaker $speaker,
    Member $requestor,
  ): ?SpeakerEditPermissionRequest;

  /**
   * @param string $token
   * @return ?SpeakerEditPermissionRequest
   */
  public function getByToken(string $token): ?SpeakerEditPermissionRequest;
}
