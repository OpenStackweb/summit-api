<?php namespace App\Services\Model\Imp;
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

use App\Jobs\Emails\Elections\NominationEmail;
use App\Models\Foundation\Elections\Election;
use App\Models\Foundation\Elections\Nomination;
use App\Services\Model\AbstractService;
use App\Services\Model\IElectionService;
use libs\utils\ITransactionService;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\main\IMemberRepository;
use models\main\Member;
/**
 * Class ElectionService
 * @package App\Services\Model\Imp
 */
final class ElectionService extends AbstractService implements IElectionService {
  /**
   * @var IMemberRepository
   */
  private $member_repository;

  public function __construct(
    IMemberRepository $member_repository,
    ITransactionService $tx_service,
  ) {
    parent::__construct($tx_service);
    $this->member_repository = $member_repository;
  }

  /**
   * @param Member $candidate
   * @param Election $election
   * @param array $payload
   * @return Member
   * @throws \Exception
   */
  public function updateCandidateProfile(
    Member $candidate,
    Election $election,
    array $payload,
  ): Member {
    return $this->tx_service->transaction(function () use ($candidate, $election, $payload) {
      if (!$candidate->isFoundationMember()) {
        throw new ValidationException("Candidate is not valid.");
      }

      $candidateProfile = $election->getCandidancyFor($candidate);

      if (
        !$election->isNominationsOpen() &&
        !is_null($candidateProfile) &&
        !$candidateProfile->isGoldMember()
      ) {
        throw new ValidationException("Eleciton Nominations are closed.");
      }

      if (!$election->isOpen()) {
        throw new ValidationException("Election is Closed.");
      }

      if (is_null($candidateProfile)) {
        $candidateProfile = $election->createCandidancy($candidate);
      }

      if (isset($payload["bio"])) {
        $candidateProfile->setBio(trim($payload["bio"]));
      }
      if (isset($payload["boards_role"])) {
        $candidateProfile->setBoardsRole(trim($payload["boards_role"]));
      }
      if (isset($payload["experience"])) {
        $candidateProfile->setExperience(trim($payload["experience"]));
      }
      if (isset($payload["relationship_to_openstack"])) {
        $candidateProfile->setRelationshipToOpenstack(trim($payload["relationship_to_openstack"]));
      }
      if (isset($payload["top_priority"])) {
        $candidateProfile->setTopPriority(trim($payload["top_priority"]));
      }

      return $candidate;
    });
  }

  /**
   * @param Member $nominator
   * @param int $candidate_id
   * @param Election $election
   * @return Nomination
   * @throws \Exception
   */
  public function nominateCandidate(
    Member $nominator,
    int $candidate_id,
    Election $election,
  ): Nomination {
    $nomination = $this->tx_service->transaction(function () use (
      $nominator,
      $candidate_id,
      $election,
    ) {
      $candidate = $this->member_repository->getById($candidate_id);
      if (is_null($candidate) || !$candidate instanceof Member) {
        throw new EntityNotFoundException("Candidate not found.");
      }
      return $nominator->nominateCandidate($candidate, $election);
    });

    NominationEmail::dispatch($election, $nomination->getCandidate());
    return $nomination;
  }
}
