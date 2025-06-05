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
use models\main\Tag;
use Doctrine\ORM\Mapping AS ORM;
/**
 * @package models\summit\DefaultTrackTagGroupAllowedTag
 */
#[ORM\Table(name: 'DefaultTrackTagGroup_AllowedTags')]
#[ORM\Entity]
class DefaultTrackTagGroupAllowedTag extends BaseEntity
{
    /**
     * @var Tag
     */
    #[ORM\JoinColumn(name: 'TagID', referencedColumnName: 'ID')]
    #[ORM\ManyToOne(targetEntity: \models\main\Tag::class)]
    private $tag;

    /**
     * @var DefaultTrackTagGroup
     */
    #[ORM\JoinColumn(name: 'DefaultTrackTagGroupID', referencedColumnName: 'ID')]
    #[ORM\ManyToOne(targetEntity: \DefaultTrackTagGroup::class, inversedBy: 'allowed_tags')]
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
    public function getTagId(){
        try {
            return is_null($this->tag) ? 0 : $this->tag->getId();
        }
        catch(\Exception $ex){
            return 0;
        }
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
     * @return DefaultTrackTagGroup
     */
    public function getTrackTagGroup()
    {
        return $this->track_tag_group;
    }

    /**
     * @param DefaultTrackTagGroup $track_tag_group
     */
    public function setTrackTagGroup(DefaultTrackTagGroup $track_tag_group)
    {
        $this->track_tag_group = $track_tag_group;
    }
}