<?php namespace models\summit;
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

use App\Models\Foundation\Summit\Events\Presentations\TrackChairs\PresentationTrackChairRatingType;
use App\Models\Foundation\Summit\Events\Presentations\TrackChairs\PresentationTrackChairScore;
use App\Models\Foundation\Summit\Events\Presentations\TrackChairs\PresentationTrackChairScoreType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use models\exceptions\ValidationException;
use models\main\Member;
use models\utils\SilverstripeBaseModel;
/**
 * @ORM\Entity(repositoryClass="App\Repositories\Summit\DoctrineSummitTrackChairRepository")
 * @ORM\Table(name="SummitTrackChair")
 * @ORM\AssociationOverrides({
 *     @ORM\AssociationOverride(
 *          name="summit",
 *          inversedBy="track_chairs"
 *     )
 * })
 * Class SummitTrackChair
 * @package models\summit;
 */
class SummitTrackChair extends SilverstripeBaseModel {
  use SummitOwned;
  /**
   * @ORM\ManyToOne(targetEntity="models\main\Member", inversedBy="track_chairs")
   * @ORM\JoinColumn(name="MemberID", referencedColumnName="ID", onDelete="SET NULL")
   * @var Member
   */
  private $member;

  /**
   * owning side
   * @ORM\ManyToMany(targetEntity="models\summit\PresentationCategory", inversedBy="track_chairs",  fetch="EXTRA_LAZY")
   * @ORM\JoinTable(name="SummitTrackChair_Categories",
   *      joinColumns={@ORM\JoinColumn(name="SummitTrackChairID", referencedColumnName="ID")},
   *      inverseJoinColumns={@ORM\JoinColumn(name="PresentationCategoryID", referencedColumnName="ID")}
   * )
   * @var PresentationCategory[]
   */
  private $categories;

  /**
   * @ORM\OneToMany(targetEntity="App\Models\Foundation\Summit\Events\Presentations\TrackChairs\PresentationTrackChairScore", mappedBy="reviewer", cascade={"persist","remove"}, orphanRemoval=true, fetch="EXTRA_LAZY")
   * @var PresentationTrackChairScore[]
   */
  private $scores;

  /**
   * @return PresentationCategory[]
   */
  public function getCategories() {
    return $this->categories;
  }

  /**
   * @param int $track_id
   * @return PresentationCategory|null
   */
  public function getCategory(int $track_id): ?PresentationCategory {
    $criteria = Criteria::create();
    $criteria->where(Criteria::expr()->eq("id", $track_id));
    $category = $this->categories->matching($criteria)->first();
    return $category === false ? null : $category;
  }

  /**
   * @param PresentationCategory $track
   * @throws ValidationException
   */
  public function addCategory(PresentationCategory $track) {
    if ($this->categories->contains($track)) {
      return;
    }
    if (!$track->isChairVisible()) {
      throw new ValidationException(
        sprintf("Category %s is not visible by track chairs.", $track->getId()),
      );
    }

    $track->addToTrackChairs($this);
    $this->categories[] = $track;
  }

  /**
   * @param PresentationCategory $track
   * @throws ValidationException
   */
  public function removeCategory(PresentationCategory $track) {
    if (!$this->categories->contains($track)) {
      return;
    }
    $track->removeFromTrackChairs($this);
    $this->categories->removeElement($track);

    $list = $track->getSelectionListByTypeAndOwner(
      SummitSelectedPresentationList::Individual,
      $this->member,
    );
    // if we remove the track , then we need to remove the selection lists
    if (!is_null($list)) {
      $track->removeSelectionList($list);
    }
  }

  /**
   * @return Member
   */
  public function getMember(): Member {
    return $this->member;
  }

  /**
   * @param Member $member
   */
  public function setMember(Member $member): void {
    $this->member = $member;
  }

  public function isCategoryAllowed(PresentationCategory $category): bool {
    $criteria = Criteria::create();
    $criteria->where(Criteria::expr()->eq("id", $category->getId()));
    return $this->categories->matching($criteria)->count() > 0;
  }

  public function clearCategories(): void {
    $this->categories->clear();
  }

  /**
   * @return int
   */
  public function getMemberId() {
    try {
      return is_null($this->member) ? 0 : $this->member->getId();
    } catch (\Exception $ex) {
      return 0;
    }
  }

  public function __construct() {
    parent::__construct();
    $this->categories = new ArrayCollection();
    $this->scores = new ArrayCollection();
  }

  public function clearMember(): void {
    $this->member = null;
  }

  public function getCategoriesIds(): array {
    $res = [];
    foreach ($this->categories as $c) {
      $res[] = $c->getId();
    }
    return $res;
  }

  /**
   * @param PresentationTrackChairRatingType $type
   * @param Presentation $presentation
   * @return bool
   */
  public function hasScoreByRatingTypeAndPresentation(
    PresentationTrackChairRatingType $type,
    Presentation $presentation,
  ): bool {
    return $this->getScoreByRatingTypeAndPresentation($type, $presentation) !== null;
  }

  /**
   * @param PresentationTrackChairScore $score
   * @throws ValidationException
   */
  public function addScore(PresentationTrackChairScore $score): void {
    if ($this->scores->contains($score)) {
      return;
    }

    if (
      $this->hasScoreByRatingTypeAndPresentation(
        $score->getType()->getType(),
        $score->getPresentation(),
      )
    ) {
      throw new ValidationException(
        sprintf(
          "Track chair %s already has a score rating type %s.",
          $this->getId(),
          $score->getType()->getType()->getName(),
        ),
      );
    }

    $this->scores->add($score);
    $score->setReviewer($this);
  }

  public function removeScore(PresentationTrackChairScore $score): void {
    if (!$this->scores->contains($score)) {
      return;
    }
    $this->scores->removeElement($score);
    $score->clearReviewer();
  }

  /**
   * @param PresentationTrackChairRatingType $ratingType
   * @param Presentation $presentation
   * @return PresentationTrackChairScore|null
   */
  public function getScoreByRatingTypeAndPresentation(
    PresentationTrackChairRatingType $ratingType,
    Presentation $presentation,
  ): ?PresentationTrackChairScore {
    $score = $this->scores
      ->filter(function ($e) use ($ratingType, $presentation) {
        return $e instanceof PresentationTrackChairScore &&
          $e->getType()->getType()->getId() === $ratingType->getId() &&
          $e->getPresentation()->getId() === $presentation->getId();
      })
      ->first();
    return $score === false ? null : $score;
  }
}
