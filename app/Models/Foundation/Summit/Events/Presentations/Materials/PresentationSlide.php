<?php namespace models\summit;
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
use models\main\File;
use Doctrine\ORM\Mapping AS ORM;
/**
 * @package models\summit
 */
#[ORM\Table(name: 'PresentationSlide')]
#[ORM\Entity]
class PresentationSlide extends PresentationMaterial
{
    const ClassName = 'PresentationSlide';

    /**
     * @return string
     */
    public function getClassName(){
        return self::ClassName;
    }

    /**
     * @var string
     */
    #[ORM\Column(name: 'Link', type: 'string')]
    private $link;

    /**
     * @var File
     */
    #[ORM\JoinColumn(name: 'SlideID', referencedColumnName: 'ID')]
    #[ORM\ManyToOne(targetEntity: \models\main\File::class, cascade: ['persist'])]
    private $slide;

    /**
     * @return File
     */
    public function getSlide()
    {
        return $this->slide;
    }

    public function clearSlide(){
        $this->slide = null;
    }

    /**
     * @param File $slide
     */
    public function setSlide($slide)
    {
        $this->slide = $slide;
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

    public function clearLink():void
    {
        $this->link = "";
    }

    /**
     * @return bool
     */
    public function hasSlide(){
        return $this->getSlideId() > 0;
    }

    /**
     * @return int
     */
    public function getSlideId(){
        try{
            return !is_null($this->slide) ? $this->slide->getId():0;
        }
        catch(\Exception $ex){
            return 0;
        }
    }

    /**
     * @return PresentationMaterial
     */
    public function clone(): PresentationMaterial {
        $clone = parent::clone();
        $clone->setLink($this->getLink());
        $clone->setSlide($this->getSlide());
        return $clone;
    }

    protected function createInstance(): PresentationMaterial
    {
        return new PresentationSlide();
    }
}