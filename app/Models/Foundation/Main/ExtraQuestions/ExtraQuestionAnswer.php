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
use Illuminate\Support\Facades\Log;
use models\exceptions\ValidationException;
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
 *     "SponsorBadgeScanExtraQuestionAnswer" = "models\summit\SponsorBadgeScanExtraQuestionAnswer",
 * })
 * Class ExtraQuestionAnswer
 * @package App\Models\Foundation\ExtraQuestionAnswer
 */
abstract class ExtraQuestionAnswer extends SilverstripeBaseModel
{
    use One2ManyPropertyTrait;

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

    /**
     * @var bool
     */
    private $should_delete_it;

    protected $getIdMappings = [
        'getQuestionId' => 'question',
    ];

    protected $hasPropertyMappings = [
        'hasQuestion' => 'question',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->value = "";
        $this->should_delete_it = false;
    }

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
     * @param string|array $value
     * @throws ValidationException
     */
    public function setValue($value): void
    {
        if(is_null($this->question)){
            throw new ValidationException("Question is not set.");
        }

        $res = $value;
        if(is_array($res))
        {
            $res = implode(ExtraQuestionTypeConstants::AnswerCharDelimiter, $value);
        }

        if($this->question->allowsValues() && $this->question->getMaxSelectedValues() > 0 && $this->question->getMaxSelectedValues() < count(explode(ExtraQuestionTypeConstants::AnswerCharDelimiter, $value)))
            throw new ValidationException
            (
                sprintf
                (
                    "You can select a Max. of %s values for Question %s.",
                    $this->question->getMaxSelectedValues(),
                    $this->question->getId()
                )
            );

        $this->value = $res;
    }

    public function __toString():string
    {
        $value = $this->value;
        if($this->question->allowsValues()){
            $value = $this->question->getNiceValue($value);
        }
        return sprintf("%s : %s", strip_tags($this->question->getLabel()), $value);
    }

    /**
     * @param string $val
     * @return bool
     */
    public function contains(string $val):bool{
        if(!$this->question->allowsValues()) return false;
        return in_array($val, explode(ExtraQuestionTypeConstants::AnswerCharDelimiter, $this->value));
    }

    public function markForDeletion():void{
        Log::debug
        (
            sprintf
            (
                "ExtraQuestionAnswer::markForDeletion id %s value %s question %s",
                $this->id,
                $this->value,
                $this->getQuestionId()
            )
        );
        $this->should_delete_it = true;
    }

    public function shouldDeleteIt():bool{
        return !is_null($this->should_delete_it) ? $this->should_delete_it: false;
    }
}