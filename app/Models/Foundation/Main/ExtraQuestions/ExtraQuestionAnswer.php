<?php namespace App\Models\Foundation\ExtraQuestions;
/**
 * Copyright 2021 OpenStack Foundation
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

use models\utils\One2ManyPropertyTrait;
use models\utils\SilverstripeBaseModel;
use Doctrine\ORM\Mapping AS ORM;
/**
 * @ORM\Entity
 * @ORM\Table(name="ExtraQuestionAnswer")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="ClassName", type="string")
 * @ORM\DiscriminatorMap({
 *     "ExtraQuestionAnswer" = "ExtraQuestionAnswer",
 *     "SummitOrderExtraQuestionAnswer" = "models\summit\SummitOrderExtraQuestionAnswer",
 *     "PresentationExtraQuestionAnswer" = "models\summit\PresentationExtraQuestionAnswer",
 * })
 * Class ExtraQuestionAnswer
 * @package App\Models\Foundation\ExtraQuestionAnswer
 */
abstract class ExtraQuestionAnswer extends SilverstripeBaseModel
{
    /**
     * @ORM\ManyToOne(targetEntity="ExtraQuestionType")
     * @ORM\JoinColumn(name="QuestionID", referencedColumnName="ID")
     * @var ExtraQuestionType
     */
    protected $question;

    /**
     * @ORM\Column(name="Value", type="string")
     * @var string
     */
    protected $value;

    use One2ManyPropertyTrait;

    protected $getIdMappings = [
        'getQuestionId' => 'question',
    ];

    protected $hasPropertyMappings = [
        'hasQuestion' => 'question',
    ];

    /**
     * @return bool
     */
    public function hasValue():bool {
        return !empty($this->value);
    }

    /**
     * @return ExtraQuestionType
     */
    public function getQuestion(): ExtraQuestionType
    {
        return $this->question;
    }

    /**
     * @param ExtraQuestionType $question
     */
    public function setQuestion(ExtraQuestionType $question): void
    {
        $this->question = $question;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @param string $value
     */
    public function setValue(string $value): void
    {
        $this->value = $value;
    }

    public function __toString():string
    {
        $value = $this->value;
        if($this->question->allowsValues()){
            $value = $this->question->getNiceValue($value);
        }
        return sprintf("%s : %s", $this->question->getLabel(), $value);
    }
}