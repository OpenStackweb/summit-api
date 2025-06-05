<?php namespace App\Models\Foundation\Summit;
/**
 * Copyright 2018 OpenStack Foundation
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
use models\utils\SilverstripeBaseModel;
use Doctrine\ORM\Mapping AS ORM;
/**
 * @package models\summit\DefaultTrackTagGroup
 */
#[ORM\Table(name: 'DefaultTrackTagGroup')]
#[ORM\Entity(repositoryClass: \App\Repositories\Summit\DoctrineDefaultTrackTagGroupRepository::class)]
class DefaultTrackTagGroup extends SilverstripeBaseModel
{
    /**
     * @var string
     */
    #[ORM\Column(name: 'Name', type: 'string')]
    private $name;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Label', type: 'string')]
    private $label;

    /**
     * @var int
     */
    #[ORM\Column(name: '`Order`', type: 'integer')]
    private $order;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'Mandatory', type: 'boolean')]
    private $is_mandatory;

    /**
     * @var DefaultTrackTagGroupAllowedTag[]
     */
    #[ORM\OneToMany(targetEntity: \DefaultTrackTagGroupAllowedTag::class, mappedBy: 'track_tag_group', cascade: ['persist'], orphanRemoval: true)]
    private $allowed_tags;

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param string $label
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }

    /**
     * @return int
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @param int $order
     */
    public function setOrder($order)
    {
        $this->order = $order;
    }

    /**
     * @return bool
     */
    public function isMandatory()
    {
        return $this->is_mandatory;
    }

    /**
     * @param bool $is_mandatory
     */
    public function setIsMandatory($is_mandatory)
    {
        $this->is_mandatory = $is_mandatory;
    }

    /**
     * @return DefaultTrackTagGroupAllowedTag[]
     */
    public function getAllowedTags()
    {
        return $this->allowed_tags;
    }

    /**
     * @param DefaultTrackTagGroupAllowedTag[] $allowed_tags
     */
    public function setAllowedTags($allowed_tags)
    {
        $this->allowed_tags = $allowed_tags;
    }

    public function __construct()
    {
        parent::__construct();
        $this->allowed_tags = new ArrayCollection;
        $this->is_mandatory = false;
    }

}