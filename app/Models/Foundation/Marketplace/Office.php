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
use Doctrine\ORM\Mapping AS ORM;
use models\utils\SilverstripeBaseModel;
/**
 * @package App\Models\Foundation\Marketplace
 */
#[ORM\Table(name: 'Office')]
#[ORM\Entity]
class Office extends SilverstripeBaseModel
{
    /**
     * @var string
     */
    #[ORM\Column(name: 'Address', type: 'string')]
    private $address;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Address2', type: 'string')]
    private $address2;

    /**
     * @var string
     */
    #[ORM\Column(name: 'State', type: 'string')]
    private $state;

    /**
     * @var string
     */
    #[ORM\Column(name: 'ZipCode', type: 'string')]
    private $zip_code;

    /**
     * @var string
     */
    #[ORM\Column(name: 'City', type: 'string')]
    private $city;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Country', type: 'string')]
    private $country;

    /**
     * @var float
     */
    #[ORM\Column(name: 'Lat', type: 'float')]
    private $lat;

    /**
     * @var float
     */
    #[ORM\Column(name: 'Lng', type: 'float')]
    private $lng;

    /**
     * @var Consultant
     */
    #[ORM\JoinColumn(name: 'ConsultantID', referencedColumnName: 'ID')]
    #[ORM\ManyToOne(targetEntity: \Consultant::class, inversedBy: 'offices', fetch: 'LAZY')]
    private $consultant;

    /**
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @return string
     */
    public function getAddress2()
    {
        return $this->address2;
    }

    /**
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @return string
     */
    public function getZipCode()
    {
        return $this->zip_code;
    }

    /**
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @return float
     */
    public function getLat()
    {
        return $this->lat;
    }

    /**
     * @return float
     */
    public function getLng()
    {
        return $this->lng;
    }

    /**
     * @return Consultant
     */
    public function getConsultant()
    {
        return $this->consultant;
    }

}