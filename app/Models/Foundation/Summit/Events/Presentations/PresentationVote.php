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
use Doctrine\ORM\Mapping as ORM;
use models\main\Member;
use models\utils\SilverstripeBaseModel;
/**
 * @ORM\Entity
 * @ORM\Table(name="PresentationVote")
 * Class PresentationVote
 * @package models\summit;
 */
class PresentationVote extends SilverstripeBaseModel {
  /**
   * @ORM\Column(name="Vote", type="integer")
   * @var int
   */
  private $vote;

  /**
   * @ORM\Column(name="Content", type="string")
   * @var string
   */
  private $content;

  /**
   * @ORM\ManyToOne(targetEntity="models\main\Member")
   * @ORM\JoinColumn(name="MemberID", referencedColumnName="ID", onDelete="SET NULL")
   * @var Member
   */
  private $voter;

  /**
   * @ORM\ManyToOne(targetEntity="models\summit\Presentation", inversedBy="votes")
   * @ORM\JoinColumn(name="PresentationID", referencedColumnName="ID", onDelete="SET NULL")
   * @var Presentation
   */
  private $presentation;

  /**
   * @return int
   */
  public function getVote(): int {
    return $this->vote;
  }

  /**
   * @return string
   */
  public function getContent(): string {
    return $this->content;
  }

  /**
   * @return Member
   */
  public function getVoter(): Member {
    return $this->voter;
  }

  /**
   * @return Presentation
   */
  public function getPresentation(): Presentation {
    return $this->presentation;
  }
}
