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

use App\Models\Foundation\Main\IOrderable;
use Doctrine\ORM\Mapping AS ORM;
use models\main\File;
use models\utils\One2ManyPropertyTrait;
use models\utils\SilverstripeBaseModel;

/**
 * @package models\summit
 */
#[ORM\Table(name: 'SponsorAd')]
#[ORM\Entity(repositoryClass: \App\Repositories\Summit\DoctrineSponsorAdRepository::class)]
class SponsorAd extends SilverstripeBaseModel implements IOrderable
{
    use One2ManyPropertyTrait;

    protected $getIdMappings = [
        'getSponsorId' => 'sponsor',
        'getImageId' => 'image',
    ];

    protected $hasPropertyMappings = [
        'hasSponsor' => 'sponsor',
        'hasImage' => 'image',
    ];

    /**
     * @var Sponsor
     */
    #[ORM\JoinColumn(name: 'SponsorID', referencedColumnName: 'ID', onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: \Sponsor::class, inversedBy: 'ads', fetch: 'EXTRA_LAZY')]
    private $sponsor;

    /**
     * @var int
     */
    #[ORM\Column(name: '`CustomOrder`', type: 'integer')]
    private $order;

    /**
     * @var File
     */
    #[ORM\JoinColumn(name: 'ImageID', referencedColumnName: 'ID')]
    #[ORM\ManyToOne(targetEntity: \models\main\File::class, cascade: ['persist'])]
    private $image;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Link', type: 'string')]
    private $link;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Alt', type: 'string')]
    private $alt;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Text', type: 'string')]
    private $text;

    /**
     * @return int
     */
    public function getOrder(): ?int
    {
        return $this->order;
    }

    /**
     * @param int $order
     */
    public function setOrder($order): void
    {
        $this->order = $order;
    }

    /**
     * @return string
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
    public function getAlt(): ?string
    {
        return $this->alt;
    }

    /**
     * @param string $alt
     */
    public function setAlt(string $alt): void
    {
        $this->alt = $alt;
    }

    /**
     * @return string
     */
    public function getText(): ?string
    {
        return $this->text;
    }

    /**
     * @param string $text
     */
    public function setText(string $text): void
    {
        $this->text = $text;
    }

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

    /**
     * @return File
     */
    public function getImage(): ?File
    {
        return $this->image;
    }

    /**
     * @param File $image
     */
    public function setImage(File $image): void
    {
        $this->image = $image;
    }

    public function clearImage():void{
        $this->image = null;
    }

    /**
     * @return string|null
     */
    public function getImageUrl():?string{
        if($this->hasImage())
            return $this->image->getUrl();
        return null;
    }

    public function clearSponsor():void{
        $this->sponsor = null;
    }
}