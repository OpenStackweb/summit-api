<?php namespace App\Models\Foundation\Summit\ProposedSchedule;
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

use Doctrine\ORM\Mapping as ORM;
use models\summit\PresentationCategory;
use models\summit\SummitTrackChair;
use models\utils\One2ManyPropertyTrait;
use models\utils\SilverstripeBaseModel;

/**
 * @ORM\Entity(repositoryClass="App\Repositories\Summit\DoctrineSummitProposedScheduleLockRepository")
 * @ORM\Table(name="SummitProposedScheduleLock")
 * Class SummitProposedScheduleLock
 * @package App\Models\Foundation\Summit\ProposedSchedule
 */
class SummitProposedScheduleLock extends SilverstripeBaseModel {
  use One2ManyPropertyTrait;

  protected $getIdMappings = [
    "getTrackId" => "track",
    "getCreatedById" => "created_by",
  ];

  protected $hasPropertyMappings = [
    "hasTrack" => "track",
    "hasCreatedBy" => "created_by",
  ];

  /**
   * @ORM\Column(name="Reason", type="string")
   * @var string
   */
  private $reason;

  /**
   * @ORM\ManyToOne(targetEntity="models\summit\PresentationCategory", fetch="EXTRA_LAZY")
   * @ORM\JoinColumn(name="TrackID", referencedColumnName="ID", onDelete="SET NULL")
   * @var PresentationCategory
   */
  protected $track;

  /**
   * @ORM\ManyToOne(targetEntity="models\summit\SummitTrackChair", fetch="EXTRA_LAZY")
   * @ORM\JoinColumn(name="CreatedByID", referencedColumnName="ID", onDelete="SET NULL")
   * @var SummitTrackChair
   */
  protected $created_by;

  /**
   * @ORM\ManyToOne(targetEntity="SummitProposedSchedule", fetch="EXTRA_LAZY", inversedBy="locks")
   * @ORM\JoinColumn(name="SummitProposedScheduleID", referencedColumnName="ID")
   * @var SummitProposedSchedule
   */
  protected $summit_proposed_schedule;

  /**
   * @return string
   */
  public function getReason(): string {
    return $this->reason;
  }

  /**
   * @param string $reason
   */
  public function setReason(string $reason): void {
    $this->reason = $reason;
  }

  /**
   * @return PresentationCategory
   */
  public function getTrack(): PresentationCategory {
    return $this->track;
  }

  /**
   * @param PresentationCategory $track
   */
  public function setTrack(PresentationCategory $track): void {
    $this->track = $track;
  }

  /**
   * @return SummitTrackChair
   */
  public function getCreatedBy(): SummitTrackChair {
    return $this->created_by;
  }

  /**
   * @param SummitTrackChair $created_by
   */
  public function setCreatedBy(SummitTrackChair $created_by): void {
    $this->created_by = $created_by;
  }

  /**
   * @return SummitProposedSchedule
   */
  public function getProposedSchedule(): SummitProposedSchedule {
    return $this->summit_proposed_schedule;
  }

  /**
   * @param SummitProposedSchedule $summit_proposed_schedule
   */
  public function setProposedSchedule(SummitProposedSchedule $summit_proposed_schedule): void {
    $this->summit_proposed_schedule = $summit_proposed_schedule;
  }

  public function clearProposedSchedule(): void {
    $this->summit_proposed_schedule = null;
  }
}
