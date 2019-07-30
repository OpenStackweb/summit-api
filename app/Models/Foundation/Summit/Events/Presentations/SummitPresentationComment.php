<?php namespace models\summit;
/**
 * Copyright 2019 OpenStack Foundation
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
use models\main\Member;
use models\utils\SilverstripeBaseModel;
/**
 * Class SummitPresentationComment
 * @ORM\Entity
 * @ORM\Table(name="SummitPresentationComment")
 * @package models\summit
 */
class SummitPresentationComment extends SilverstripeBaseModel
{
    /**
     * @ORM\Column(name="Body", type="string")
     * @var string
     */
    private $body;

    /**
     * @ORM\Column(name="IsActivity", type="boolean")
     * @var bool
     */
    private $is_activity;

    /**
     * @ORM\Column(name="IsPublic", type="boolean")
     * @var bool
     */
    private $is_public;

    /**
     * @ORM\ManyToOne(targetEntity="Presentation", inversedBy="comments")
     * @ORM\JoinColumn(name="PresentationID", referencedColumnName="ID", onDelete="SET NULL")
     * @var Presentation
     */
    private $presentation;

    /**
     * @ORM\ManyToOne(targetEntity="models\main\Member")
     * @ORM\JoinColumn(name="CommenterID", referencedColumnName="ID", onDelete="SET NULL")
     * @var Member
     */
    private $creator;

    /**
     * Presentation constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->is_activity = false;
        $this->is_public   = false;
    }

    /**
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * @param string $body
     */
    public function setBody(string $body): void
    {
        $this->body = $body;
    }

    /**
     * @return bool
     */
    public function isActivity(): bool
    {
        return $this->is_activity;
    }

    /**
     * @param bool $is_activity
     */
    public function setIsActivity(bool $is_activity): void
    {
        $this->is_activity = $is_activity;
    }

    /**
     * @return bool
     */
    public function isPublic(): bool
    {
        return $this->is_public;
    }

    /**
     * @param bool $is_public
     */
    public function setIsPublic(bool $is_public): void
    {
        $this->is_public = $is_public;
    }

    /**
     * @return Presentation
     */
    public function getPresentation(): Presentation
    {
        return $this->presentation;
    }

    /**
     * @param Presentation $presentation
     */
    public function setPresentation(Presentation $presentation): void
    {
        $this->presentation = $presentation;
    }

    /**
     * @return Member
     */
    public function getCreator(): Member
    {
        return $this->creator;
    }

    /**
     * @param Member $creator
     */
    public function setCreator(Member $creator): void
    {
        $this->creator = $creator;
    }

    /**
     * @return int
     */
    public function getCreatorId():int
    {
        try{
            if(is_null($this->creator)) return 0;
            return $this->creator->getId();
        }
        catch(\Exception $ex){
            return 0;
        }
    }


    /**
     * @return int
     */
    public function getPresentationId():int
    {
        try{
            if(is_null($this->presentation)) return 0;
            return $this->presentation->getId();
        }
        catch(\Exception $ex){
            return 0;
        }
    }


}