<?php namespace App\Models\Foundation\Software;
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
use Doctrine\ORM\Mapping as ORM;
use models\utils\SilverstripeBaseModel;
use DateTime;
/**
 * @ORM\Entity(repositoryClass="App\Repositories\Main\Software\DoctrineOpenStackReleaseRepository")
 * @ORM\Table(name="OpenStackRelease")
 * Class OpenStackRelease
 * @package App\Models\Foundation\Software
 */
class OpenStackRelease extends SilverstripeBaseModel {
  /**
   * @ORM\Column(name="Name", type="string")
   * @var string
   */
  private $name;

  /**
   * @ORM\Column(name="ReleaseNumber", type="string")
   * @var string
   */
  private $release_number;

  /**
   * @ORM\Column(name="ReleaseDate", type="datetime")
   * @var DateTime
   */
  private $release_date;

  /**
   * @ORM\Column(name="Status", type="string")
   * @var string
   */
  private $status;

  /**
   * @ORM\OneToMany(targetEntity="OpenStackReleaseComponent", mappedBy="release", cascade={"persist","remove"}, orphanRemoval=true, fetch="EXTRA_LAZY")
   */
  private $components;

  public function __construct() {
    parent::__construct();
    $this->components = new ArrayCollection();
  }

  /**
   * @return string
   */
  public function getName(): string {
    return $this->name;
  }

  /**
   * @return string
   */
  public function getReleaseNumber(): string {
    return $this->release_number;
  }

  /**
   * @return DateTime
   */
  public function getReleaseDate(): DateTime {
    return $this->release_date;
  }

  /**
   * @return string
   */
  public function getStatus(): string {
    return $this->status;
  }

  /**
   * @return ArrayCollection
   */
  public function getComponents(): ArrayCollection {
    return $this->components;
  }

  /**
   * @return OpenStackReleaseComponent[]
   */
  public function getOrderedComponents(): array {
    return $this->createQueryBuilder()
      ->select("distinct e")
      ->from(OpenStackReleaseComponent::class, "e")
      ->join("e.release", "r")
      ->join("e.component", "c")
      ->where("r.id = :release_id")
      ->orderBy("c.is_core_service", "DESC")
      ->orderBy("c.order", "ASC")
      ->setParameter("release_id", $this->getId())
      ->getQuery()
      ->getResult();
  }
}
