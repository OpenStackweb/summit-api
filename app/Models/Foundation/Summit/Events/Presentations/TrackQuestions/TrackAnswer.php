<?php namespace App\Models\Foundation\Summit\Events\Presentations\TrackQuestions;
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
use Doctrine\ORM\Mapping AS ORM;
use models\summit\Presentation;
use models\utils\SilverstripeBaseModel;
/**
 * @package App\Models\Foundation\Summit\Events\Presentations\TrackQuestions
 */
#[ORM\Table(name: 'TrackAnswer')]
#[ORM\Entity]
class TrackAnswer extends SilverstripeBaseModel
{
    /**
     * @var string
     */
    #[ORM\Column(name: 'Value', type: 'string')]
    private $value;

    /**
     * @var TrackQuestionTemplate
     */
    #[ORM\JoinColumn(name: 'QuestionID', referencedColumnName: 'ID', onDelete: 'SET NULL')]
    #[ORM\ManyToOne(targetEntity: \TrackQuestionTemplate::class, fetch: 'EXTRA_LAZY', inversedBy: 'answers')]
    private $question;

    /**
     * @var Presentation
     */
    #[ORM\JoinColumn(name: 'PresentationID', referencedColumnName: 'ID', onDelete: 'SET NULL')]
    #[ORM\ManyToOne(targetEntity: \models\summit\Presentation::class, fetch: 'EXTRA_LAZY', inversedBy: 'answers')]
    private $presentation;

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param string $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * @return TrackQuestionTemplate
     */
    public function getQuestion()
    {
        return $this->question;
    }

    /**
     * @param TrackQuestionTemplate $question
     */
    public function setQuestion($question)
    {
        $this->question = $question;
    }

    /**
     * @return Presentation
     */
    public function getPresentation()
    {
        return $this->presentation;
    }

    /**
     * @param Presentation $presentation
     */
    public function setPresentation($presentation)
    {
        $this->presentation = $presentation;
    }

    /**
     * @return string
     */
    public function getQuestionName(){
        return $this->question->getName();
    }

    /**
     * @return int
     */
    public function getQuestionId(){
        return $this->question->getId();
    }

    public function __construct()
    {
        parent::__construct();
    }

}