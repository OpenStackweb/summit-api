<?php namespace models\summit;
/**
 * Copyright 2017 OpenStack Foundation
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
use models\exceptions\ValidationException;

/**
 * Class PresentationType
 * @ORM\Entity
 * @ORM\Table(name="PresentationType")
 * @ORM\HasLifecycleCallbacks
 * @package models\summit
 */
class PresentationType extends SummitEventType {
  /**
   * @ORM\Column(name="MaxSpeakers", type="integer")
   * @var int
   */
  protected $max_speakers;

  /**
   * @ORM\Column(name="MinSpeakers", type="integer")
   * @var int
   */
  protected $min_speakers;

  /**
   * @ORM\Column(name="MaxModerators", type="integer")
   * @var int
   */
  protected $max_moderators;

  /**
   * @ORM\Column(name="MinModerators", type="integer")
   * @var int
   */
  protected $min_moderators;

  /**
   * @ORM\Column(name="UseSpeakers", type="boolean")
   * @var bool
   */
  protected $use_speakers;

  /**
   * @ORM\Column(name="AreSpeakersMandatory", type="boolean")
   * @var bool
   */
  protected $are_speakers_mandatory;

  /**
   * @ORM\Column(name="UseModerator", type="boolean")
   * @var bool
   */
  protected $use_moderator;

  /**
   * @ORM\Column(name="IsModeratorMandatory", type="boolean")
   * @var bool
   */
  protected $is_moderator_mandatory;

  /**
   * @ORM\Column(name="ShouldBeAvailableOnCFP", type="boolean")
   * @var bool
   */
  protected $should_be_available_on_cfp;

  /**
   * @ORM\Column(name="ModeratorLabel", type="string")
   * @var string
   */
  protected $moderator_label;

  /**
   * @ORM\ManyToMany(targetEntity="SummitMediaUploadType", mappedBy="presentation_types")
   */
  protected $allowed_media_upload_types;

  /**
   * @ORM\Column(name="AllowAttendeeVote", type="boolean")
   * @var bool
   */
  protected $allow_attendee_vote;

  /**
   * @ORM\Column(name="AllowCustomOrdering", type="boolean")
   * @var bool
   */
  protected $allow_custom_ordering;

  /**
   * @ORM\Column(name="AllowsSpeakerAndEventCollision", type="boolean")
   * @var bool
   */
  protected $allows_speaker_event_collision;

  /**
   * @ORM\Column(name="MinDuration", type="integer")
   * @var int
   */
  protected $min_duration;

  /**
   * @ORM\Column(name="MaxDuration", type="integer")
   * @var int
   */
  protected $max_duration;

  /**
   * @param Summit $summit
   * @param string $type
   * @return bool
   */
  public static function IsPresentationEventType(Summit $summit, $type) {
    try {
      $sql = <<<SQL
                  SELECT COUNT(DISTINCT(PresentationType.ID))
                  FROM PresentationType
                  INNER JOIN SummitEventType ON SummitEventType.ID = PresentationType.ID
                  WHERE SummitEventType.SummitID = :summit_id
                  AND SummitEventType.Type = :type
      SQL;
      $stmt = self::prepareRawSQLStatic($sql, [
        "summit_id" => $summit->getId(),
        "type" => $type,
      ]);
      $res = $stmt->executeQuery();
      $res = $res->fetchFirstColumn();
      return count($res) > 0;
    } catch (\Exception $ex) {
    }
    return false;
  }

  /**
   * @return array()
   */
  public static function presentationTypes() {
    return [
      IPresentationType::Presentation,
      IPresentationType::Keynotes,
      IPresentationType::LightingTalks,
      IPresentationType::Panel,
    ];
  }

  /**
   * @return int
   */
  public function getMaxSpeakers(): int {
    return $this->max_speakers;
  }

  /**
   * @return int
   */
  public function getMinSpeakers(): int {
    return $this->min_speakers;
  }

  /**
   * @return int
   */
  public function getMaxModerators(): int {
    return $this->max_moderators;
  }

  /**
   * @return int
   */
  public function getMinModerators(): int {
    return $this->min_moderators;
  }

  /**
   * @return bool
   */
  public function isAreSpeakersMandatory(): bool {
    return $this->min_speakers > 0;
  }

  /**
   * @return bool
   */
  public function isUseSpeakers(): bool {
    return $this->use_speakers;
  }

  /**
   * @return bool
   */
  public function isUseModerator(): bool {
    return $this->use_moderator;
  }

  /**
   * @return bool
   */
  public function isModeratorMandatory(): bool {
    return $this->min_moderators > 0;
  }

  /**
   * @return bool
   */
  public function isShouldBeAvailableOnCfp(): bool {
    return $this->should_be_available_on_cfp;
  }

  /**
   * @return string|null
   */
  public function getModeratorLabel(): ?string {
    return $this->moderator_label;
  }

  public function getClassName() {
    return "PresentationType";
  }

  const ClassName = "PRESENTATION_TYPE";

  /**
   * @param int $max_speakers
   * @throws ValidationException
   */
  public function setMaxSpeakers(int $max_speakers): void {
    if ($max_speakers < 0) {
      throw new ValidationException("Max. Speakers should be greater than zero.");
    }

    if ($this->min_speakers > $max_speakers) {
      throw new ValidationException(
        sprintf(
          "Max. Speakers (%s) can not be lower than Min. Speakers (%s).",
          $max_speakers,
          $this->min_speakers,
        ),
      );
    }

    $this->max_speakers = $max_speakers;
  }

  /**
   * @param int $min_speakers
   * @throws ValidationException
   */
  public function setMinSpeakers(int $min_speakers): void {
    if ($min_speakers < 0) {
      throw new ValidationException("Min. Speakers should be greater than zero.");
    }

    $this->min_speakers = $min_speakers;
  }

  /**
   * @param int $max_moderators
   * @throws ValidationException
   */
  public function setMaxModerators(int $max_moderators): void {
    if ($max_moderators < 0) {
      throw new ValidationException("Max. Moderators should be greater than zero.");
    }

    if ($this->min_moderators > $max_moderators) {
      throw new ValidationException(
        sprintf(
          "Max. Moderators (%s) can not be lower than Min. Moderators (%s)",
          $max_moderators,
          $this->min_moderators,
        ),
      );
    }

    $this->max_moderators = $max_moderators;
  }

  /**
   * @param int $min_moderators
   * @throws ValidationException
   */
  public function setMinModerators(int $min_moderators): void {
    if ($min_moderators < 0) {
      throw new ValidationException("Min. Moderators should be greater than zero.");
    }

    $this->min_moderators = $min_moderators;
  }

  /**
   * @param bool $use_speakers
   */
  public function setUseSpeakers(bool $use_speakers): void {
    $this->use_speakers = $use_speakers;
  }

  /**
   * @deprecated
   * @param bool $are_speakers_mandatory
   */
  public function setAreSpeakersMandatory(bool $are_speakers_mandatory): void {
    $this->are_speakers_mandatory = $are_speakers_mandatory;
  }

  /**
   * @deprecated
   * @param bool $use_moderator
   */
  public function setUseModerator(bool $use_moderator): void {
    $this->use_moderator = $use_moderator;
  }

  /**
   * @deprecated
   * @param bool $is_moderator_mandatory
   */
  public function setIsModeratorMandatory(bool $is_moderator_mandatory): void {
    $this->is_moderator_mandatory = $is_moderator_mandatory;
  }

  /**
   * @param bool $should_be_available_on_cfp
   */
  public function setShouldBeAvailableOnCfp(bool $should_be_available_on_cfp): void {
    $this->should_be_available_on_cfp = $should_be_available_on_cfp;
  }

  /**
   * @param string $moderator_label
   */
  public function setModeratorLabel(string $moderator_label): void {
    $this->moderator_label = $moderator_label;
  }

  public function __construct() {
    parent::__construct();
    $this->are_speakers_mandatory = false;
    $this->use_speakers = false;
    $this->use_moderator = false;
    $this->is_moderator_mandatory = false;
    $this->should_be_available_on_cfp = false;
    $this->allows_level = true;
    $this->allowed_media_upload_types = new ArrayCollection();
    $this->max_moderators = 0;
    $this->max_speakers = 0;
    $this->min_moderators = 0;
    $this->min_speakers = 0;
    $this->allow_attendee_vote = false;
    $this->allow_custom_ordering = false;
    $this->allows_speaker_event_collision = false;
    $this->min_duration = 0;
    $this->max_duration = 0;
  }

  public function addAllowedMediaUploadType(SummitMediaUploadType $type) {
    if ($this->allowed_media_upload_types->contains($type)) {
      return;
    }
    $this->allowed_media_upload_types->add($type);
  }

  public function removeAllowedMediaUploadType(SummitMediaUploadType $type) {
    if (!$this->allowed_media_upload_types->contains($type)) {
      return;
    }
    $this->allowed_media_upload_types->removeElement($type);
  }

  public function getMandatoryAllowedMediaUploadTypesCount(): int {
    $criteria = Criteria::create();
    $criteria->where(Criteria::expr()->gt("min_uploads_qty", 0));
    return $this->allowed_media_upload_types->matching($criteria)->count();
  }

  /**
   * Returns all allowed media uploads for each upload type
   * @return array
   */
  public function getMandatoryAllowedMediaUploadTypes(): array {
    $res = [];
    $criteria = Criteria::create();
    $criteria->where(Criteria::expr()->gt("min_uploads_qty", 0));

    $allowed_media_upload_types = $this->allowed_media_upload_types->matching($criteria);

    foreach ($allowed_media_upload_types as $allowed_media_upload_type) {
      $res[$allowed_media_upload_type->getId()] = $allowed_media_upload_type;
    }
    return $res;
  }

  public function clearAllowedMediaUploadType() {
    $this->allowed_media_upload_types->clear();
  }

  public function getAllowedMediaUploadTypes() {
    return $this->allowed_media_upload_types;
  }

  /**
   * @return bool
   */
  public function isAllowAttendeeVote(): bool {
    return $this->allow_attendee_vote;
  }

  /**
   * @param bool $allow_attendee_vote
   */
  public function setAllowAttendeeVote(bool $allow_attendee_vote): void {
    $this->allow_attendee_vote = $allow_attendee_vote;
  }

  /**
   * @return bool
   */
  public function isAllowCustomOrdering(): bool {
    return $this->allow_custom_ordering;
  }

  /**
   * @param bool $allow_custom_ordering
   */
  public function setAllowCustomOrdering(bool $allow_custom_ordering): void {
    $this->allow_custom_ordering = $allow_custom_ordering;
  }

  /**
   * @return bool
   */
  public function isAllowsSpeakerEventCollision(): bool {
    return $this->allows_speaker_event_collision;
  }

  /**
   * @param bool $allows_speaker_event_collision
   */
  public function setAllowsSpeakerEventCollision(bool $allows_speaker_event_collision): void {
    $this->allows_speaker_event_collision = $allows_speaker_event_collision;
  }

  /**
   * @return int
   */
  public function getMinDuration(): int {
    return $this->min_duration;
  }

  /**
   * @param int $duration
   */
  public function setMinDuration(int $duration): void {
    $this->min_duration = $duration;
  }

  /**
   * @return int
   */
  public function getMaxDuration(): int {
    return $this->max_duration;
  }

  /**
   * @param int $duration
   */
  public function setMaxDuration(int $duration): void {
    $this->max_duration = $duration;
  }
}
