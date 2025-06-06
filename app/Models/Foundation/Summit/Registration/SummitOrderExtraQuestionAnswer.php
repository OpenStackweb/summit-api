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
use App\Models\Foundation\ExtraQuestions\ExtraQuestionAnswer;
use models\exceptions\ValidationException;
use models\utils\One2ManyPropertyTrait;
use Doctrine\ORM\Mapping AS ORM;
/**
 * @package models\summit
 */
#[ORM\Table(name: 'SummitOrderExtraQuestionAnswer')]
#[ORM\Entity]
class SummitOrderExtraQuestionAnswer extends ExtraQuestionAnswer
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
     * @var SummitOrder
     */
    #[ORM\JoinColumn(name: 'OrderID', referencedColumnName: 'ID')]
    #[ORM\ManyToOne(targetEntity: \models\summit\SummitOrder::class, inversedBy: 'extra_question_answers')]
    private $order;

    /**
     * @var SummitAttendee
     */
    #[ORM\JoinColumn(name: 'SummitAttendeeID', referencedColumnName: 'ID')]
    #[ORM\ManyToOne(targetEntity: \models\summit\SummitAttendee::class, inversedBy: 'extra_question_answers')]
    private $attendee;

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


    public function clearOrder(){
        $this->order = null;
    }

    public function clearAttendee(){
        $this->attendee = null;
    }

    /**
     * @param string|array $value
     * @throws ValidationException
     */
    public function setValue($value): void
    {
        parent::setValue($value);
        if(!is_null($this->attendee))
            $this->attendee->updateLastEdited();
    }

    public function __toString():string
    {
        return sprintf("SummitOrderExtraQuestionAnswer attendee %s question %s value %s", $this->attendee->getId(), $this->question->getId(), $this->value);
    }
}