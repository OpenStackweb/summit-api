<?php namespace App\Models\Foundation\UserStories;
/*
 * Copyright 2024 OpenStack Foundation
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
 * @ORM\Entity
 * @ORM\Table(name="UserStoriesIndustry")
 * Class File
 * @package App\Models\Foundation\UserStories
 */
class UserStoriesIndustry extends SilverstripeBaseModel
{
    /**
     * @ORM\Column(name="IndustryName", type="string")
     */
    private $name;

    /**
     * @ORM\Column(name="Active", type="boolean")
     * @var bool
     */
    private $is_active;

    /**
     * @ORM\OneToMany(targetEntity="App\Models\Foundation\UserStories\UserStory", mappedBy="industry", cascade={"persist","remove"}, orphanRemoval=true)
     */
    private $user_stories;

    public function __construct()
    {
        parent::__construct();
        $this->user_stories = new ArrayCollection();
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name): void
    {
        $this->name = $name;
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * @param bool $is_active
     */
    public function setIsActive(bool $is_active): void
    {
        $this->is_active = $is_active;
    }

    /**
     * @return ArrayCollection
     */
    public function getUserStories(): ArrayCollection
    {
        return $this->user_stories;
    }

    /**
     * @param UserStory $user_story
     */
    public function addUserStory(UserStory $user_story)
    {
        if ($this->user_stories->contains($user_story)) return;
        $this->user_stories->add($user_story);
    }

    public function clearUserStories()
    {
        $this->user_stories->clear();
    }
}