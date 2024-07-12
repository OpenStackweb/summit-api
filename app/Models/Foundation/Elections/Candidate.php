<?php namespace App\Models\Foundation\Elections;
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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use models\exceptions\ValidationException;
use models\main\Member;
use models\utils\One2ManyPropertyTrait;
use models\utils\SilverstripeBaseModel;
/**
 * @ORM\Entity
 * @ORM\Table(name="Candidate")
 * Class Candidate
 * @package App\Models\Foundation\Elections
 */
class Candidate extends SilverstripeBaseModel {
  use One2ManyPropertyTrait;

  protected $getIdMappings = [
    "getMemberId" => "member",
    "getElectionId" => "election",
  ];

  protected $hasPropertyMappings = [
    "hasMember" => "member",
    "hasElection" => "election",
  ];

  /**
   * @var Election
   * @ORM\ManyToOne(targetEntity="App\Models\Foundation\Elections\Election", inversedBy="candidates")
   * @ORM\JoinColumn(name="ElectionID", referencedColumnName="ID")
   */
  private $election;

  /**
   * @var Member
   * @ORM\ManyToOne(targetEntity="models\main\Member", inversedBy="candidate_profiles")
   * @ORM\JoinColumn(name="MemberID", referencedColumnName="ID")
   */
  private $member;

  /**
   * @ORM\Column(name="HasAcceptedNomination", type="boolean")
   * @var bool
   */
  private $has_accepted_nomination;

  /**
   * @ORM\Column(name="IsGoldMemberCandidate", type="boolean")
   * @var bool
   */
  private $is_gold_member;

  /**
   * Questions
   */

  /**
   * @var string
   * @ORM\Column(name="Bio", type="string")
   */
  private $bio;

  /**
   * @var string
   * @ORM\Column(name="RelationshipToOpenStack", type="string")
   */
  private $relationship_to_openstack;

  /**
   * @var string
   * @ORM\Column(name="Experience", type="string")
   */
  private $experience;

  /**
   * @var string
   * @ORM\Column(name="BoardsRole", type="string")
   */
  private $boards_role;

  /**
   * @var string
   * @ORM\Column(name="TopPriority", type="string")
   */
  private $top_priority;

  /**
   * Candidate constructor.
   * @param Member $member
   * @param Election $election
   * @throws ValidationException
   */
  public function __construct(Member $member, Election $election) {
    parent::__construct();
    $this->nominations = new ArrayCollection();
    $this->member = $member;
    if (!$member->isFoundationMember()) {
      throw new ValidationException("Invalid Candidate.");
    }

    $this->election = $election;
    $this->relationship_to_openstack = null;
    $this->experience = null;
    $this->boards_role = null;
    $this->top_priority = null;
    $this->bio = $member->getBio();
    $this->is_gold_member = false;
    $this->has_accepted_nomination = false;
  }

  /**
   * @return Election
   */
  public function getElection(): Election {
    return $this->election;
  }

  /**
   * @return Member
   */
  public function getMember(): Member {
    return $this->member;
  }

  /**
   * @return bool
   */
  public function isHasAcceptedNomination(): bool {
    return $this->has_accepted_nomination;
  }

  /**
   * @param bool $has_accepted_nomination
   */
  public function setHasAcceptedNomination(bool $has_accepted_nomination): void {
    $this->has_accepted_nomination = $has_accepted_nomination;
  }

  /**
   * @return bool
   */
  public function isGoldMember(): bool {
    return $this->is_gold_member;
  }

  /**
   * @param bool $is_gold_member
   */
  public function setIsGoldMember(bool $is_gold_member): void {
    $this->is_gold_member = $is_gold_member;
  }

  /**
   * @return string|null
   */
  public function getRelationshipToOpenstack(): ?string {
    return $this->relationship_to_openstack;
  }

  /**
   * @param string $relationship_to_openstack
   * @throws ValidationException
   */
  public function setRelationshipToOpenstack(string $relationship_to_openstack): void {
    if (!$this->election->canEditCandidateProfile($this)) {
      throw new ValidationException("Election Nominations are Closed.");
    }
    $this->relationship_to_openstack = $relationship_to_openstack;
    $this->checkIfHasAcceptedNomination();
  }

  /**
   * @return string|null
   */
  public function getExperience(): ?string {
    return $this->experience;
  }

  /**
   * @param string $experience
   * @throws ValidationException
   */
  public function setExperience(string $experience): void {
    if (!$this->election->canEditCandidateProfile($this)) {
      throw new ValidationException("Election Nominations are Closed.");
    }
    $this->experience = $experience;
    $this->checkIfHasAcceptedNomination();
  }

  /**
   * @return string|null
   */
  public function getBoardsRole(): ?string {
    return $this->boards_role;
  }

  private function checkIfHasAcceptedNomination(): bool {
    $this->has_accepted_nomination =
      strlen($this->bio) < 4 ||
      strlen($this->relationship_to_openstack) < 4 ||
      strlen($this->experience) < 4 ||
      strlen($this->top_priority) < 4
        ? false
        : true;
    return $this->has_accepted_nomination;
  }

  /**
   * @param string $boards_role
   * @throws ValidationException
   */
  public function setBoardsRole(string $boards_role): void {
    if (!$this->election->canEditCandidateProfile($this)) {
      throw new ValidationException("Election Nominations are Closed.");
    }
    $this->boards_role = $boards_role;
    $this->checkIfHasAcceptedNomination();
  }

  /**
   * @return string|null
   */
  public function getTopPriority(): ?string {
    return $this->top_priority;
  }

  /**
   * @param string $top_priority
   * @throws ValidationException
   */
  public function setTopPriority(string $top_priority): void {
    if (!$this->election->canEditCandidateProfile($this)) {
      throw new ValidationException("Election Nominations are Closed.");
    }

    $this->top_priority = $top_priority;
    $this->checkIfHasAcceptedNomination();
  }

  /**
   * @param Election $election
   */
  public function setElection(Election $election): void {
    $this->election = $election;
  }

  /**
   * @param Member $member
   */
  public function setMember(Member $member): void {
    $this->member = $member;
  }

  /**
   * @return string
   */
  public function getBio(): ?string {
    return $this->bio;
  }

  /**
   * @param string $bio
   * @throws ValidationException
   */
  public function setBio(string $bio): void {
    if (!$this->election->canEditCandidateProfile($this)) {
      throw new ValidationException("Election Nominations are Closed.");
    }

    $this->bio = $bio;
    $this->checkIfHasAcceptedNomination();
  }
}
