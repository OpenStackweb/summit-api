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
use App\Models\Foundation\Elections\Election;
use App\Models\Foundation\Elections\Nomination;
use models\exceptions\ValidationException;
use models\main\Member;
/**
 * Interface IElectionService
 * @package App\Services\Model
 */
interface IElectionService {
  /**
   * @param Member $candidate
   * @param Election $election
   * @param array $payload
   * @return Member
   * @throws ValidationException
   */
  public function updateCandidateProfile(
    Member $candidate,
    Election $election,
    array $payload,
  ): Member;

  /**
   * @param Member $nominator
   * @param int $candidate_id
   * @param Election $election
   * @return Nomination
   * @throws ValidationException
   */
  public function nominateCandidate(
    Member $nominator,
    int $candidate_id,
    Election $election,
  ): Nomination;
}
