<?php namespace models\summit;
/**
 * Copyright 2016 OpenStack Foundation
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
/**
 * Class PresentationLink
 * @package models\summit
 */
#[ORM\Table(name: 'PresentationLink')]
#[ORM\Entity]
class PresentationLink extends PresentationMaterial
{
    const ClassName = 'PresentationLink';

    /**
     * @return string
     */
    public function getClassName(){
        return self::ClassName;
    }

    /**
     * @return string
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * @param string $link
     */
    public function setLink($link)
    {
        $this->link = $link;
    }
    /**
     * @var string
     */
    #[ORM\Column(name: 'Link', type: 'string')]
    private $link;

    /**
     * @return PresentationMaterial
     */
    public function clone(): PresentationMaterial {
        $clone = parent::clone();
        $clone->setLink($this->getLink());
        return $clone;
    }

    protected function createInstance(): PresentationMaterial
    {
        return new PresentationSlide();
    }
}