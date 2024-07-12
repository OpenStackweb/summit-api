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
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Google\Service\AdMob\Date;
use models\exceptions\ValidationException;
use models\main\Member;
use models\utils\SilverstripeBaseModel;
use DateTime;
use DateTimeZone;

/**
 * @ORM\Entity(repositoryClass="repositories\main\DoctrineElectionsRepository")
 * @ORM\Table(name="Election")
 * Class Election
 * @package App\Models\Foundation\Elections
 */
class Election extends SilverstripeBaseModel {
  const StatusClosed = "Closed";
  const StatusNominationsOpen = "NominationsOpen";
  const StatusElectionUpcoming = "ElectionUpComing";
  const StatusElectionOpen = "ElectionOpen";

  /**
   * Max. number of nominations per election for the same candidate to be able
   * to show in ballot
   */
  const NominationLimit = 10;

  // default form label values

  const CandidateApplicationFormRelationshipToOpenStackLabelDefault = 'What is your relationship to OpenStack, and why is its success important to you? What would you say is your biggest contribution to OpenStack\'s success to date?';
  const CandidateApplicationFormExperienceLabelDefault = "Describe your experience with other non profits or serving as a board member. How does your experience prepare you for the role of a board member?";
  const CandidateApplicationFormBoardsRoleLabelDefault = 'What do you see as the Board\'s role in OpenStack\'s success?';
  const CandidateApplicationFormTopPriorityLabelDefault = "What do you think the top priority of the Board should be over the next year?";

  /**
   * @var string
   * @ORM\Column(name="Name", type="string")
   */
  private $name;

  /**
   * @var \DateTime
   * @ORM\Column(name="NominationsOpen", type="datetime")
   */
  private $nomination_opens;

  /**
   * @var \DateTime
   * @ORM\Column(name="NominationsClose", type="datetime")
   */
  private $nomination_closes;

  /**
   * @var \DateTime
   * @ORM\Column(name="NominationAppDeadline", type="datetime")
   */
  private $nomination_deadline;

  /**
   * @var \DateTime
   * @ORM\Column(name="ElectionsOpen", type="datetime")
   */
  private $opens;

  /**
   * @var \DateTime
   * @ORM\Column(name="ElectionsClose", type="datetime")
   */
  private $closes;

  /**
   * @var string
   * @ORM\Column(name="TimeZoneIdentifier", type="string")
   */
  private $timezone_id;

  /**
   * Question labels
   */

  /**
   * @var string
   * @ORM\Column(name="CandidateApplicationFormRelationshipToOpenStackLabel", type="string")
   */
  private $candidate_application_form_relationship_to_openstack_label;

  /**
   * @var string
   * @ORM\Column(name="CandidateApplicationFormExperienceLabel", type="string")
   */
  private $candidate_application_form_experience_label;

  /**
   * @var string
   * @ORM\Column(name="CandidateApplicationFormBoardsRoleLabel", type="string")
   */
  private $candidate_application_form_boards_role_label;

  /**
   * @var string
   * @ORM\Column(name="CandidateApplicationFormTopPriorityLabel", type="string")
   */
  private $candidate_application_form_top_priority_label;

  /**
   * @ORM\OneToMany(targetEntity="App\Models\Foundation\Elections\Candidate", mappedBy="election", cascade={"persist"}, orphanRemoval=true)
   * @var Candidate[]
   */
  private $candidates;

  /**
   * @ORM\OneToMany(targetEntity="App\Models\Foundation\Elections\Nomination", mappedBy="election", cascade={"persist"}, orphanRemoval=true)
   * @var Nomination[]
   */
  private $nominations;

  /**
   * Election constructor.
   */
  public function __construct() {
    parent::__construct();
    $this->candidates = new ArrayCollection();
    $this->nominations = new ArrayCollection();
  }

  // Return whether the nominations are open by looking at the dates provided
  public function isNominationsOpen(): bool {
    $start_date = $this->nomination_opens;
    $end_date = $this->nomination_closes;

    if (empty($start_date) || empty($end_date)) {
      return false;
    }

    $now = new DateTime("now", new DateTimeZone("UTC"));

    return $now >= $start_date && $now <= $end_date;
  }

  // Return whether the election is currently running by looking at the dates provided
  function isOnVotingPeriod(): bool {
    $start_date = $this->opens;
    $end_date = $this->closes;

    if (empty($start_date) || empty($end_date)) {
      return false;
    }
    $now = new DateTime("now", new DateTimeZone("UTC"));
    return $now >= $start_date && $now <= $end_date;
  }

  /**
   * @return bool
   */
  public function isOpen(): bool {
    return $this->isNominationsOpen() ||
      $this->isOnVotingPeriod() ||
      $this->isUpcomingVotingPeriod();
  }

  public function isUpcomingVotingPeriod(): bool {
    $start_date = $this->opens;
    $now = new DateTime("now", new DateTimeZone("UTC"));
    return $now < $start_date;
  }

  public function getStatus(): string {
    $status = Election::StatusClosed;
    if ($this->isNominationsOpen()) {
      $status = Election::StatusNominationsOpen;
    } elseif ($this->isOnVotingPeriod()) {
      $status = Election::StatusElectionOpen;
    } elseif ($this->isUpcomingVotingPeriod()) {
      $status = Election::StatusElectionUpcoming;
    }
    return $status;
  }

  /**
   * @return mixed
   */
  public function getName() {
    return $this->name;
  }

  /**
   * @return DateTime
   */
  public function getNominationOpens(): ?DateTime {
    return $this->nomination_opens;
  }

  /**
   * @return DateTime
   */
  public function getNominationCloses(): ?DateTime {
    return $this->nomination_closes;
  }

  /**
   * @return DateTime
   */
  public function getNominationDeadline(): ?DateTime {
    return $this->nomination_deadline;
  }

  /**
   * @param Candidate $candidate
   * @return bool
   * @throws \Exception
   */
  public function canEditCandidateProfile(Candidate $candidate): bool {
    if ($candidate->isGoldMember()) {
      return true;
    }
    $now = new DateTime("now", new DateTimeZone("UTC"));
    if (!is_null($this->nomination_deadline) && $now < $this->nomination_deadline) {
      return true;
    }
    if ($this->isNominationsOpen()) {
      return true;
    }
    return false;
  }

  /**
   * @return DateTime
   */
  public function getOpens(): ?DateTime {
    return $this->opens;
  }

  /**
   * @return DateTime
   */
  public function getCloses(): ?DateTime {
    return $this->closes;
  }

  /**
   * @return string
   */
  public function getTimezoneId(): ?string {
    return $this->timezone_id;
  }

  // Question Labels

  /**
   * @return string
   */
  public function getCandidateApplicationFormRelationshipToOpenstackLabel(): string {
    if (empty($this->candidate_application_form_relationship_to_openstack_label)) {
      return self::CandidateApplicationFormRelationshipToOpenStackLabelDefault;
    }
    return $this->candidate_application_form_relationship_to_openstack_label;
  }

  /**
   * @return string
   */
  public function getCandidateApplicationFormExperienceLabel(): string {
    if (empty($this->candidate_application_form_experience_label)) {
      return self::CandidateApplicationFormExperienceLabelDefault;
    }
    return $this->candidate_application_form_experience_label;
  }

  /**
   * @return string
   */
  public function getCandidateApplicationFormBoardsRoleLabel(): string {
    if (empty($this->candidate_application_form_boards_role_label)) {
      return self::CandidateApplicationFormBoardsRoleLabelDefault;
    }
    return $this->candidate_application_form_boards_role_label;
  }

  /**
   * @return string
   */
  public function getCandidateApplicationFormTopPriorityLabel(): string {
    if (empty($this->candidate_application_form_top_priority_label)) {
      return self::CandidateApplicationFormTopPriorityLabelDefault;
    }
    return $this->candidate_application_form_top_priority_label;
  }

  /**
   * @param Member $candidate
   * @return Candidate
   * @throws ValidationException
   */
  public function createCandidancy(Member $candidate): Candidate {
    if (!$this->isNominationsOpen()) {
      throw new ValidationException("Nomination Period is closed.");
    }

    if ($this->isCandidate($candidate)) {
      throw new ValidationException(
        sprintf("Member %s is already a candidate.", $candidate->getId()),
      );
    }

    $newCandidateProfile = new Candidate($candidate, $this);
    $candidate->addCandidateProfile($newCandidateProfile);
    $this->candidates->add($newCandidateProfile);
    return $newCandidateProfile;
  }

  /**
   * @return Candidate[]|ArrayCollection
   */
  public function getCandidates() {
    return $this->candidates;
  }

  public function addNomination(Nomination $nomination) {
    if ($this->nominations->contains($nomination)) {
      return;
    }
    $this->nominations->add($nomination);
  }

  /**
   * @param string $name
   */
  public function setName(string $name): void {
    $this->name = $name;
  }

  /**
   * @param DateTime $nomination_opens
   */
  public function setNominationOpens(DateTime $nomination_opens): void {
    $this->nomination_opens = $nomination_opens;
  }

  /**
   * @param DateTime $nomination_closes
   */
  public function setNominationCloses(DateTime $nomination_closes): void {
    $this->nomination_closes = $nomination_closes;
  }

  /**
   * @param DateTime $nomination_deadline
   */
  public function setNominationDeadline(DateTime $nomination_deadline): void {
    $this->nomination_deadline = $nomination_deadline;
  }

  /**
   * @param DateTime $opens
   */
  public function setOpens(DateTime $opens): void {
    $this->opens = $opens;
  }

  /**
   * @param DateTime $closes
   */
  public function setCloses(DateTime $closes): void {
    $this->closes = $closes;
  }

  /**
   * @param string $timezone_id
   */
  public function setTimezoneId(string $timezone_id): void {
    $this->timezone_id = $timezone_id;
  }

  /**
   * @param string $candidate_application_form_relationship_to_openstack_label
   */
  public function setCandidateApplicationFormRelationshipToOpenstackLabel(
    string $candidate_application_form_relationship_to_openstack_label,
  ): void {
    $this->candidate_application_form_relationship_to_openstack_label = $candidate_application_form_relationship_to_openstack_label;
  }

  /**
   * @param string $candidate_application_form_experience_label
   */
  public function setCandidateApplicationFormExperienceLabel(
    string $candidate_application_form_experience_label,
  ): void {
    $this->candidate_application_form_experience_label = $candidate_application_form_experience_label;
  }

  /**
   * @param string $candidate_application_form_boards_role_label
   */
  public function setCandidateApplicationFormBoardsRoleLabel(
    string $candidate_application_form_boards_role_label,
  ): void {
    $this->candidate_application_form_boards_role_label = $candidate_application_form_boards_role_label;
  }

  /**
   * @param string $candidate_application_form_top_priority_label
   */
  public function setCandidateApplicationFormTopPriorityLabel(
    string $candidate_application_form_top_priority_label,
  ): void {
    $this->candidate_application_form_top_priority_label = $candidate_application_form_top_priority_label;
  }

  /**
   * @param Member $candidate
   * @return bool
   */
  public function isCandidate(Member $candidate): bool {
    $criteria = Criteria::create();
    $criteria->where(Criteria::expr()->eq("member", $candidate));
    return $this->candidates->matching($criteria)->count() > 0;
  }

  /**
   * @param Member $candidate
   * @return Candidate|null
   */
  public function getCandidancyFor(Member $candidate): ?Candidate {
    $criteria = Criteria::create();
    $criteria->where(Criteria::expr()->eq("member", $candidate));
    $res = $this->candidates->matching($criteria)->first();
    return $res === false ? null : $res;
  }

  /**
   * @param Member $candidate
   * @return int
   */
  public function getNominationCountFor(Member $candidate): int {
    $criteria = Criteria::create();
    $criteria->where(Criteria::expr()->eq("candidate", $candidate));
    return $this->nominations->matching($criteria)->count() > 0;
  }

  /**
   * @return int
   */
  public function getNominationsLimit(): int {
    return self::NominationLimit;
  }
}
