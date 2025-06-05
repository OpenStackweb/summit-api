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
 * @package models\summit
 */
#[ORM\Table(name: 'SummitPresentationComment')]
#[ORM\Entity(repositoryClass: \App\Repositories\Summit\DoctrineSummitPresentationCommentRepository::class)]
class SummitPresentationComment extends SilverstripeBaseModel
{
    /**
     * @var string
     */
    #[ORM\Column(name: 'Body', type: 'string')]
    private $body;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'IsActivity', type: 'boolean')]
    private $is_activity;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'IsPublic', type: 'boolean')]
    private $is_public;

    /**
     * @var Presentation
     */
    #[ORM\JoinColumn(name: 'PresentationID', referencedColumnName: 'ID', onDelete: 'SET NULL')]
    #[ORM\ManyToOne(targetEntity: \Presentation::class, inversedBy: 'comments')]
    private $presentation;

    /**
     * @var Member
     */
    #[ORM\JoinColumn(name: 'CommenterID', referencedColumnName: 'ID', onDelete: 'SET NULL')]
    #[ORM\ManyToOne(targetEntity: \models\main\Member::class)]
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

    public static function createComment
    (
        Member $creator,
        Presentation $presentation,
        string $body,
        bool $is_public = true
    ):SummitPresentationComment{
        $comment = new SummitPresentationComment();
        $comment->is_public = $is_public;
        $comment->body = $body;
        $comment->creator = $creator;
        $comment->presentation = $presentation;
        return $comment;
    }

    public static function createNotification
    (
        Member $creator,
        Presentation $presentation,
        string $body
    ):SummitPresentationComment{
        $comment = new SummitPresentationComment();

        $body = str_replace(
            [
                '{member}',
                '{presentation}'
            ],
            [
                $creator->getFullName(),
                $presentation->getTitle()
            ],
            $body
        );
        $comment->body = $body;
        $comment->creator = $creator;
        $comment->is_activity = true;
        $comment->presentation = $presentation;
        return $comment;
    }

}