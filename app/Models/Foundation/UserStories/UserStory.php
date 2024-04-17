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
use Doctrine\ORM\Mapping AS ORM;
use models\main\File;
use models\main\Member;
use models\main\Organization;

/**
 * @ORM\Entity
 * @ORM\Table(name="UserStoryDO")
 * Class File
 * @package App\Models\Foundation\UserStories
 */
class UserStory
{
    /**
     * @ORM\Column(name="Name", type="string")
     */
    private $name;
    /**
     * @ORM\Column(name="Description", type="string")
     */
    private $description;
    /**
     * @ORM\Column(name="ShortDescription", type="string")
     */
    private $short_description;
    /**
     * @ORM\Column(name="Link", type="string")
     */
    private $link;

    /**
     * @ORM\Column(name="Active", type="boolean")
     * @var bool
     */
    private $is_active;

    /**
     * @ORM\Column(name="MillionCoreClub", type="boolean")
     * @var bool
     */
    private $is_million_core_club;

    /**
     * @ORM\ManyToOne(targetEntity="models\main\Organization")
     * @ORM\JoinColumn(name="OrganizationID", referencedColumnName="ID")
     * @var Organization
     */
    private $organization;

    /**
     * @ORM\ManyToOne(targetEntity="models\main\File")
     * @ORM\JoinColumn(name="ImageID", referencedColumnName="ID")
     * @var File
     */
    private $image;

    /**
     * @ORM\ManyToMany(targetEntity="models\main\Tag", cascade={"persist"}, inversedBy="events", fetch="EXTRA_LAZY")
     * @ORM\JoinTable(name="SummitEvent_Tags",
     *      joinColumns={@ORM\JoinColumn(name="SummitEventID", referencedColumnName="ID")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="TagID", referencedColumnName="ID")}
     *      )
     */
    private $tags;
}