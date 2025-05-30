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
#[ORM\Table(name: 'MarketPlaceVideo')]
#[ORM\Entity]
class MarketPlaceVideo extends SilverstripeBaseModel
{
    /**
     * @var string
     */
    #[ORM\Column(name: 'Name', type: 'string')]
    private $name;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Description', type: 'string')]
    private $description;

    /**
     * @var string
     */
    #[ORM\Column(name: 'YouTubeID', type: 'string')]
    private $youtube_id;

    /**
     * @var MarketPlaceVideoType
     */
    #[ORM\JoinColumn(name: 'TypeID', referencedColumnName: 'ID')]
    #[ORM\ManyToOne(targetEntity: \MarketPlaceVideoType::class, fetch: 'LAZY')]
    private $type;

    /**
     * @var CompanyService
     */
    #[ORM\JoinColumn(name: 'OwnerID', referencedColumnName: 'ID')]
    #[ORM\ManyToOne(targetEntity: \CompanyService::class, inversedBy: 'videos', fetch: 'LAZY')]
    private $company_service;

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return string
     */
    public function getYoutubeId()
    {
        return $this->youtube_id;
    }

    /**
     * @return MarketPlaceVideoType
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return CompanyService
     */
    public function getCompanyService()
    {
        return $this->company_service;
    }
}