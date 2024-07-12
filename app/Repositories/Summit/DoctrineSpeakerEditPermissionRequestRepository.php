<?php namespace App\Repositories\Summit;
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
use App\Models\Foundation\Summit\Repositories\ISpeakerEditPermissionRequestRepository;
use App\Models\Foundation\Summit\Speakers\SpeakerEditPermissionRequest;
use App\Repositories\SilverStripeDoctrineRepository;
use models\main\Member;
use models\summit\PresentationSpeaker;
/**
 * Class DoctrineSpeakerEditPermissionRequestRepository
 * @package App\Repositories\Summit
 */
final class DoctrineSpeakerEditPermissionRequestRepository
  extends SilverStripeDoctrineRepository
  implements ISpeakerEditPermissionRequestRepository {
  /**
   * @return string
   */
  protected function getBaseEntity() {
    return SpeakerEditPermissionRequest::class;
  }

  /**
   * @param PresentationSpeaker $speaker
   * @param Member $requestor
   * @return ?SpeakerEditPermissionRequest
   */
  public function getBySpeakerAndRequestor(
    PresentationSpeaker $speaker,
    Member $requestor,
  ): ?SpeakerEditPermissionRequest {
    return $this->getEntityManager()
      ->createQueryBuilder()
      ->select("r")
      ->from(SpeakerEditPermissionRequest::class, "r")
      ->where("r.speaker = :speaker")
      ->andWhere("r.requested_by = :requestor")
      ->setParameter("speaker", $speaker)
      ->setParameter("requestor", $requestor)
      ->setMaxResults(1)
      ->getQuery()
      ->getOneOrNullResult();
  }

  /**
   * @param string $token
   * @return ?SpeakerEditPermissionRequest
   */
  public function getByToken(string $token): ?SpeakerEditPermissionRequest {
    return $this->getEntityManager()
      ->createQueryBuilder()
      ->select("r")
      ->from(SpeakerEditPermissionRequest::class, "r")
      ->where("r.hash = :hash")
      ->setParameter("hash", SpeakerEditPermissionRequest::HashConfirmationToken($token))
      ->setMaxResults(1)
      ->getQuery()
      ->getOneOrNullResult();
  }
}
