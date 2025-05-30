<?php namespace App\Models\Foundation\Marketplace;
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
use Doctrine\ORM\Mapping AS ORM;
use models\utils\SilverstripeBaseModel;
/**
 * @package App\Models\Foundation\Marketplace
 */
#[ORM\Table(name: 'InteropProgramVersion')]
#[ORM\Entity]
class InteropProgramVersion extends SilverstripeBaseModel
{
    /**
     * @var string
     */
    #[ORM\Column(name: 'Name', type: 'string')]
    private $name;

    /**
     * @var SupportChannelType[]
     */
    #[ORM\JoinTable(name: 'InteropProgramVersion_Capabilities')]
    #[ORM\JoinColumn(name: 'InteropProgramVersionID', referencedColumnName: 'ID')]
    #[ORM\InverseJoinColumn(name: 'InteropCapabilityID', referencedColumnName: 'ID')]
    #[ORM\ManyToMany(targetEntity: \InteropCapability::class, cascade: ['persist'])]
    private $capabilities;


    /**
     * @var InteropDesignatedSection[]
     */
    #[ORM\JoinTable(name: 'InteropProgramVersion_DesignatedSections')]
    #[ORM\JoinColumn(name: 'InteropProgramVersionID', referencedColumnName: 'ID')]
    #[ORM\InverseJoinColumn(name: 'InteropDesignatedSectionID', referencedColumnName: 'ID')]
    #[ORM\ManyToMany(targetEntity: \InteropDesignatedSection::class, cascade: ['persist'])]
    private $designated_sections;

    public function __construct()
    {
        $this->capabilities        = new ArrayCollection();
        $this->designated_sections = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }



}