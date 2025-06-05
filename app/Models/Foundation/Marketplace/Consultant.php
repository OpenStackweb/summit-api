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
use App\Models\Foundation\Software\OpenStackComponent;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping AS ORM;
/**
 * @package App\Models\Foundation\Marketplace
 */
#[ORM\Table(name: 'Consultant')]
#[ORM\Entity(repositoryClass: \App\Repositories\Marketplace\DoctrineConsultantRepository::class)]
class Consultant extends RegionalSupportedCompanyService
{
    const ClassName = 'Consultant';

    /**
     * @var Office[]
     */
    #[ORM\OneToMany(targetEntity: \Office::class, mappedBy: 'consultant', cascade: ['persist'], orphanRemoval: true)]
    private $offices;

    /**
     * @var ConsultantClient[]
     */
    #[ORM\OneToMany(targetEntity: \ConsultantClient::class, mappedBy: 'consultant', cascade: ['persist'], orphanRemoval: true)]
    private $clients;

    /**
     * @var SpokenLanguage[]
     */
    #[ORM\JoinTable(name: 'Consultant_SpokenLanguages')]
    #[ORM\JoinColumn(name: 'ConsultantID', referencedColumnName: 'ID')]
    #[ORM\InverseJoinColumn(name: 'SpokenLanguageID', referencedColumnName: 'ID')]
    #[ORM\ManyToMany(targetEntity: \SpokenLanguage::class, cascade: ['persist'])]
    private $spoken_languages;

    /**
     * @var ConfigurationManagementType[]
     */
    #[ORM\JoinTable(name: 'Consultant_ConfigurationManagementExpertises')]
    #[ORM\JoinColumn(name: 'ConsultantID', referencedColumnName: 'ID')]
    #[ORM\InverseJoinColumn(name: 'ConfigurationManagementTypeID', referencedColumnName: 'ID')]
    #[ORM\ManyToMany(targetEntity: \ConfigurationManagementType::class, cascade: ['persist'])]
    private $configuration_management_expertise;

    /**
     * @var OpenStackComponent[]
     */
    #[ORM\JoinTable(name: 'Consultant_ExpertiseAreas')]
    #[ORM\JoinColumn(name: 'ConsultantID', referencedColumnName: 'ID')]
    #[ORM\InverseJoinColumn(name: 'OpenStackComponentID', referencedColumnName: 'ID')]
    #[ORM\ManyToMany(targetEntity: \App\Models\Foundation\Software\OpenStackComponent::class, cascade: ['persist'])]
    private $expertise_areas;

    /**
     * @var ConsultantServiceOfferedType[]
     */
    #[ORM\OneToMany(targetEntity: \ConsultantServiceOfferedType::class, mappedBy: 'consultant', cascade: ['persist'], orphanRemoval: true)]
    private $services_offered;

    /**
     * Consultant constructor.
     */
    public function __construct()
    {
        $this->offices                             = new ArrayCollection();
        $this->clients                             = new ArrayCollection();
        $this->spoken_languages                    = new ArrayCollection();
        $this->configuration_management_expertises = new ArrayCollection();
        $this->expertise_areas                     = new ArrayCollection();
        $this->services_offered                    = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function getClassName():string
    {
        return self::ClassName;
    }

    /**
     * @return Office[]
     */
    public function getOffices()
    {
        return $this->offices->toArray();
    }

    /**
     * @return ConsultantClient[]
     */
    public function getClients(){
        return $this->clients->toArray();
    }

    /**
     * @return SpokenLanguage[]
     */
    public function getSpokenLanguages()
    {
        return $this->spoken_languages->toArray();
    }

    /**
     * @return ConfigurationManagementType[]
     */
    public function getConfigurationManagementExpertise()
    {
        return $this->configuration_management_expertise->toArray();
    }

    /**
     * @return OpenStackComponent[]
     */
    public function getExpertiseAreas()
    {
        return $this->expertise_areas->toArray();
    }

    /**
     * @return ConsultantServiceOfferedType[]
     */
    public function getServicesOffered()
    {
        return $this->services_offered->toArray();
    }
}