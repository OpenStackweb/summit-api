<?php namespace models\main;
/**
 * Copyright 2015 OpenStack Foundation
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
 * @package models\main
 */
#[ORM\Table(name: 'Tag')]
#[ORM\Entity(repositoryClass: \repositories\main\DoctrineTagRepository::class)] // Class Tag
class Tag extends SilverstripeBaseModel
{

    /**
     * @var string
     */
    #[ORM\Column(name: 'Tag', type: 'string')]
    private $tag;

    #[ORM\ManyToMany(targetEntity: \models\summit\SummitEvent::class, mappedBy: 'tags')]
    private $events;

    /**
     * @return string
     */
    public function getTag()
    {
        return $this->tag;
    }

    /**
     * @param string $tag
     */
    public function setTag($tag)
    {
        $this->tag = $tag;
    }

    /**
     * Tag constructor.
     * @param string $tag
     */
    public function __construct($tag)
    {
        parent::__construct();
        $this->tag = $tag;
        $this->events = new ArrayCollection();
    }

    public function getEvents(){
        return $this->events;
    }

}