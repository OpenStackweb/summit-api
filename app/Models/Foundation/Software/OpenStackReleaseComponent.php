<?php namespace App\Models\Foundation\Software;
/*
 * Copyright 2022 OpenStack Foundation
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

use App\Models\Utils\BaseEntity;
use Doctrine\ORM\Mapping as ORM;
use models\utils\One2ManyPropertyTrait;
/**
 * @ORM\Entity
 * @ORM\Table(name="OpenStackRelease_OpenStackComponents")
 * Class OpenStackReleaseComponent
 * @package App\Models\Foundation\Software
 */
class OpenStackReleaseComponent extends BaseEntity {
  use One2ManyPropertyTrait;

  protected $getIdMappings = [
    "getComponentId" => "component",
    "getReleaseId" => "release",
  ];

  protected $hasPropertyMappings = [
    "hasComponent" => "component",
    "hasRelease" => "release",
  ];

  /**
   * @ORM\Column(name="Adoption", type="integer")
   * @var int
   */
  private $adoption;

  /**
   * @ORM\Column(name="MaturityPoints", type="integer")
   * @var int
   */
  private $maturity_points;

  /**
   * @ORM\Column(name="HasInstallationGuide", type="boolean")
   * @var bool
   */
  private $has_installation_guide;

  /**
   * @ORM\ManyToOne(targetEntity="OpenStackRelease", fetch="EXTRA_LAZY", cascade={"persist"},  inversedBy="components")
   * @ORM\JoinColumn(name="OpenStackReleaseID", referencedColumnName="ID")
   * @var OpenStackRelease
   */
  private $release;

  /**
   * @ORM\ManyToOne(targetEntity="OpenStackComponent", fetch="EXTRA_LAZY", cascade={"persist"})
   * @ORM\JoinColumn(name="OpenStackComponentID", referencedColumnName="ID")
   * @var OpenStackComponent
   */
  private $component;

  public function __construct() {
    $this->has_installation_guide = false;
  }

  /**
   * @return int
   */
  public function getAdoption(): int {
    return $this->adoption;
  }

  /**
   * @return int
   */
  public function getMaturityPoints(): int {
    return $this->maturity_points;
  }

  /**
   * @return bool
   */
  public function isHasInstallationGuide(): bool {
    return $this->has_installation_guide;
  }

  /**
   * @return OpenStackRelease
   */
  public function getRelease(): OpenStackRelease {
    return $this->release;
  }

  /**
   * @return OpenStackComponent
   */
  public function getComponent(): OpenStackComponent {
    return $this->component;
  }
}
