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
use models\utils\One2ManyPropertyTrait;
use models\utils\SilverstripeBaseModel;
use Doctrine\ORM\Mapping AS ORM;
/**
 * @ORM\Entity
 * @ORM\Table(name="SummitOrderExtraQuestionAnswer")
 * Class SummitOrderExtraQuestionAnswer
 * @package models\summit
 */
class SummitOrderExtraQuestionAnswer extends SilverstripeBaseModel
{
    use One2ManyPropertyTrait;

    protected $getIdMappings = [
        'getOrderId'    => 'order',
        'getAttendeeId' => 'attendee',
        'getQuestionId' => 'question',
    ];

    protected $hasPropertyMappings = [
        'hasOrder'    => 'order',
        'hasAttendee' => 'attendee',
        'hasQuestion' => 'question',
    ];
    /**
     * @ORM\ManyToOne(targetEntity="models\summit\SummitOrder", inversedBy="extra_question_answers")
     * @ORM\JoinColumn(name="OrderID", referencedColumnName="ID")
     * @var SummitOrder
     */
    private $order;

    /**
     * @ORM\ManyToOne(targetEntity="models\summit\SummitAttendee", inversedBy="extra_question_answers")
     * @ORM\JoinColumn(name="SummitAttendeeID", referencedColumnName="ID")
     * @var SummitAttendee
     */
    private $attendee;

    /**
     * @ORM\ManyToOne(targetEntity="models\summit\SummitOrderExtraQuestionType")
     * @ORM\JoinColumn(name="QuestionID", referencedColumnName="ID")
     * @var SummitOrderExtraQuestionType
     */
    private $question;

    /**
     * @ORM\Column(name="Value", type="string")
     * @var string
     */
    private $value;

    /**
     * @return SummitOrder
     */
    public function getOrder(): ?SummitOrder
    {
        return $this->order;
    }

    /**
     * @param SummitOrder $order
     */
    public function setOrder(SummitOrder $order): void
    {
        $this->order = $order;
    }

    /**
     * @return SummitAttendee
     */
    public function getAttendee(): ?SummitAttendee
    {
        return $this->attendee;
    }

    /**
     * @param SummitAttendee $attendee
     */
    public function setAttendee(SummitAttendee $attendee): void
    {
        $this->attendee = $attendee;
    }

    /**
     * @return SummitOrderExtraQuestionType
     */
    public function getQuestion(): ?SummitOrderExtraQuestionType
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

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @return bool
     */
    public function hasValue():bool {
        return !empty($this->value);
    }

    /**
     * @param string $value
     */
    public function setValue(string $value): void
    {
        $this->value = $value;
    }

    public function clearOrder(){
        $this->order = null;
    }

    public function clearAttendee(){
        $this->attendee = null;
    }

}