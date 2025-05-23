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
use Doctrine\ORM\Mapping AS ORM;
use models\utils\One2ManyPropertyTrait;
/**
 * @package App\Models\Foundation\Software
 */
#[ORM\Table(name: 'OpenStackRelease_OpenStackComponents')]
#[ORM\Entity]
class OpenStackReleaseComponent extends BaseEntity
{

    use One2ManyPropertyTrait;

    protected $getIdMappings = [
        'getComponentId' => 'component',
        'getReleaseId' => 'release',
    ];

    protected $hasPropertyMappings = [
        'hasComponent' => 'component',
        'hasRelease' => 'release',
    ];

    /**
     * @var int
     */
    #[ORM\Column(name: 'Adoption', type: 'integer')]
    private $adoption;

    /**
     * @var int
     */
    #[ORM\Column(name: 'MaturityPoints', type: 'integer')]
    private $maturity_points;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'HasInstallationGuide', type: 'boolean')]
    private $has_installation_guide;

    /**
     * @var OpenStackRelease
     */
    #[ORM\JoinColumn(name: 'OpenStackReleaseID', referencedColumnName: 'ID')]
    #[ORM\ManyToOne(targetEntity: \OpenStackRelease::class, fetch: 'EXTRA_LAZY', cascade: ['persist'], inversedBy: 'components')]
    private $release;

    /**
     * @var OpenStackComponent
     */
    #[ORM\JoinColumn(name: 'OpenStackComponentID', referencedColumnName: 'ID')]
    #[ORM\ManyToOne(targetEntity: \OpenStackComponent::class, fetch: 'EXTRA_LAZY', cascade: ['persist'])]
    private $component;

    public function __construct()
    {
        $this->has_installation_guide = false;
    }

    /**
     * @return int
     */
    public function getAdoption(): int
    {
        return $this->adoption;
    }

    /**
     * @return int
     */
    public function getMaturityPoints(): int
    {
        return $this->maturity_points;
    }

    /**
     * @return bool
     */
    public function isHasInstallationGuide(): bool
    {
        return $this->has_installation_guide;
    }

    /**
     * @return OpenStackRelease
     */
    public function getRelease(): OpenStackRelease
    {
        return $this->release;
    }

    /**
     * @return OpenStackComponent
     */
    public function getComponent(): OpenStackComponent
    {
        return $this->component;
    }
}