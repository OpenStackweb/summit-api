<?php namespace models\summit;
/**
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
use Doctrine\ORM\Mapping AS ORM;
use models\utils\One2ManyPropertyTrait;
use models\utils\SilverstripeBaseModel;

/**
 * @package models\summit
 */
#[ORM\Table(name: 'SponsorSocialNetwork')]
#[ORM\Entity(repositoryClass: \App\Repositories\Summit\DoctrineSponsorSocialNetworkRepository::class)]
class SponsorSocialNetwork extends SilverstripeBaseModel
{
    use One2ManyPropertyTrait;

    protected $getIdMappings = [
        'getSponsorId' => 'sponsor',
    ];

    protected $hasPropertyMappings = [
        'hasSponsor' => 'sponsor',
    ];

    /**
     * @var Sponsor
     */
    #[ORM\JoinColumn(name: 'SponsorID', referencedColumnName: 'ID', onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: \Sponsor::class, inversedBy: 'social_networks', fetch: 'EXTRA_LAZY')]
    private $sponsor;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Link', type: 'string')]
    private $link;

    /**
     * @var string
     */
    #[ORM\Column(name: 'IconCSSClass', type: 'string')]
    private $icon_css_class;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'IsEnable', type: 'boolean')]
    private $is_enabled;

    /**
     * @return Sponsor
     */
    public function getSponsor(): ?Sponsor
    {
        return $this->sponsor;
    }

    /**
     * @param Sponsor $sponsor
     */
    public function setSponsor(Sponsor $sponsor): void
    {
        $this->sponsor = $sponsor;
    }

    public function clearSponsor():void{
        $this->sponsor = null;
    }

    /**
     * @return string|null
     */
    public function getLink(): ?string
    {
        return $this->link;
    }

    /**
     * @param string $link
     */
    public function setLink(string $link): void
    {
        $this->link = $link;
    }

    /**
     * @return string
     */
    public function getIconCssClass(): string
    {
        return $this->icon_css_class;
    }

    /**
     * @param string $icon_css_class
     */
    public function setIconCssClass(string $icon_css_class): void
    {
        $this->icon_css_class = $icon_css_class;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->is_enabled;
    }

    /**
     * @param bool $is_enabled
     */
    public function setIsEnabled(bool $is_enabled): void
    {
        $this->is_enabled = $is_enabled;
    }

    public function __construct()
    {
        parent::__construct();
        $this->is_enabled = true;
    }
}