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
use App\Models\Foundation\Main\IOrderable;
use models\utils\SilverstripeBaseModel;
use Doctrine\ORM\Mapping AS ORM;
/**
 * @ORM\Entity
 * @ORM\Table(name="SummitOrderExtraQuestionValue")
 * Class SummitOrderExtraQuestionValue
 * @package models\summit
 */
class SummitOrderExtraQuestionValue extends SilverstripeBaseModel
    implements IOrderable
{
    /**
     * @ORM\Column(name="Label", type="string")
     * @var string
     */
    private $label;

    /**
     * @ORM\Column(name="Value", type="string")
     * @var string
     */
    private $value;

    /**
     * @ORM\Column(name="`Order`", type="integer")
     * @var int
     */
    private $order;

    /**
     * @ORM\ManyToOne(targetEntity="SummitOrderExtraQuestionType", inversedBy="values")
     * @ORM\JoinColumn(name="QuestionID", referencedColumnName="ID")
     * @var SummitOrderExtraQuestionType
     */
    private $question;

    /**
     * @return string
     */
    public function getLabel(): ?string
    {
        return $this->label;
    }

    /**
     * @param string $label
     */
    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

    /**
     * @return string
     */
    public function getValue(): ?string
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

    /**
     * @return int
     */
    public function getOrder(): int
    {
        return $this->order;
    }

    /**
     * @param int $order
     */
    public function setOrder($order): void
    {
        $this->order = $order;
    }

    /**
     * @return SummitOrderExtraQuestionType
     */
    public function getQuestion(): SummitOrderExtraQuestionType
    {
        return $this->question;
    }

    /**
     * @param SummitOrderExtraQuestionType $question
     */
    public function setQuestion(SummitOrderExtraQuestionType $question): void
    {
        $this->question = $question;
    }

    public function __construct()
    {
        parent::__construct();
        $this->order = 0;
    }

    /**
     * @return int
     */
    public function getQuestionId():int{
        try {
            return is_null($this->question) ? 0 : $this->question->getId();
        }
        catch(\Exception $ex){
            return 0;
        }
    }


}