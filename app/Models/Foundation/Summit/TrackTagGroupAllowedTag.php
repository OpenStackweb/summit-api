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
use App\Models\Utils\BaseEntity;
use Doctrine\ORM\Cache;
use models\main\Tag;
use models\summit\SummitOwned;
use models\utils\SilverstripeBaseModel;
use Doctrine\ORM\Mapping AS ORM;
/**
 * @package models\summit\TrackTagGroupAllowedTag
 */
#[ORM\Table(name: 'TrackTagGroup_AllowedTags')]
#[ORM\Entity(repositoryClass: \App\Repositories\Summit\DoctrineTrackTagGroupAllowedTagsRepository::class)]
class TrackTagGroupAllowedTag extends BaseEntity
{
    /**
     * @var boolean
     */
    #[ORM\Column(name: 'IsDefault', type: 'boolean')]
    private $is_default;

    /**
     * @var Tag
     */
    #[ORM\JoinColumn(name: 'TagID', referencedColumnName: 'ID')]
    #[ORM\ManyToOne(targetEntity: \models\main\Tag::class, cascade: ['persist'])]
    private $tag;

    /**
     * @var TrackTagGroup
     */
    #[ORM\JoinColumn(name: 'TrackTagGroupID', referencedColumnName: 'ID')]
    #[ORM\ManyToOne(targetEntity: \TrackTagGroup::class, inversedBy: 'allowed_tags')]
    private $track_tag_group;

    /**
     * @return int
     */
    public function getTrackTagGroupId(){
        try {
            return is_null($this->track_tag_group) ? 0 : $this->track_tag_group->getId();
        }
        catch(\Exception $ex){
            return 0;
        }
    }

    /**
     * @return int
     */
    public function getSummitId(){
        try {
            return is_null($this->track_tag_group) ? 0 : $this->track_tag_group->getSummitId();
        }
        catch(\Exception $ex){
            return 0;
        }
    }

    /**
     * @return int
     */
    public function getTagId(){
        try {
            return is_null($this->tag) ? 0 : $this->tag->getId();
        }
        catch(\Exception $ex){
            return 0;
        }
    }


    /**
     * @return bool
     */
    public function isDefault()
    {
        return $this->is_default;
    }

    /**
     * @param bool $is_default
     */
    public function setIsDefault($is_default)
    {
        $this->is_default = $is_default;
    }

    /**
     * @return Tag
     */
    public function getTag()
    {
        return $this->tag;
    }

    /**
     * @param Tag $tag
     */
    public function setTag(Tag $tag)
    {
        $this->tag = $tag;
    }

    /**
     * @return TrackTagGroup
     */
    public function getTrackTagGroup()
    {
        return $this->track_tag_group;
    }

    /**
     * @param TrackTagGroup $track_tag_group
     */
    public function setTrackTagGroup(TrackTagGroup $track_tag_group)
    {
        $this->track_tag_group = $track_tag_group;
    }
}