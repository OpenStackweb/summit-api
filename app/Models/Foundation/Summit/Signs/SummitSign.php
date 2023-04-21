<?php namespace App\Models\Foundation\Summit\Signs;
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
use models\summit\SummitAbstractLocation;
use models\summit\SummitOwned;
use models\utils\One2ManyPropertyTrait;
use models\utils\SilverstripeBaseModel;
/**
 * @ORM\Entity(repositoryClass="App\Repositories\Summit\DoctrineSummitSignRepository")
 * @ORM\Table(name="SummitSign")
 * @ORM\AssociationOverrides({
 *     @ORM\AssociationOverride(
 *          name="summit",
 *          inversedBy="signs"
 *     )
 * })
 * Class SummitSign
 * @package App\Models\Foundation\Summit\Signs;
 */
class SummitSign extends SilverstripeBaseModel
{

    use One2ManyPropertyTrait;

    protected $getIdMappings = [
        'getLocationId' => 'location',
    ];

    protected $hasPropertyMappings = [
        'hasLocation' => 'location',
    ];

    use SummitOwned;

    /**
     * @ORM\Column(name="Template", type="string")
     * @var string
     */
    private $template;


    /**
     * @ORM\OneToOne(targetEntity="models\summit\SummitAbstractLocation")
     * @ORM\JoinColumn(name="LocationID", referencedColumnName="ID")
     * @var SummitAbstractLocation
     */
    private $location;

    /**
     * @return string
     */
    public function getTemplate(): ?string
    {
        return $this->template;
    }

    /**
     * @param string $template
     */
    public function setTemplate(string $template): void
    {
        $this->template = trim($template);
    }

    /**
     * @return SummitAbstractLocation
     */
    public function getLocation(): ?SummitAbstractLocation
    {
        return $this->location;
    }

    /**
     * @param SummitAbstractLocation $location
     */
    public function setLocation(SummitAbstractLocation $location): void
    {
        $this->location = $location;
    }


}