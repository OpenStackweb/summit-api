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

use Doctrine\ORM\Mapping AS ORM;
use models\utils\SilverstripeBaseModel;
use models\main\Member;

/**
 * @ORM\Entity
 * @ORM\Table(name="SummitEventFeedback")
 * @ORM\Entity(repositoryClass="App\Repositories\Summit\DoctrineEventFeedbackRepository")
 * @ORM\Cache(usage="NONSTRICT_READ_WRITE", region="summit_event_feedback_region")
 * Class SummitEventFeedback
 * @package models\summit
 */
class SummitEventFeedback extends SilverstripeBaseModel
{
    /**
     * @ORM\Column(name="Rate", type="integer")
     * @var int
     */
    private $rate;

    /**
     * @ORM\Column(name="note", type="string")
     * @var string
     */
    private $note;

    /**
     * @ORM\ManyToOne(targetEntity="models\main\Member", inversedBy="feedback")
     * @ORM\JoinColumn(name="OwnerID", referencedColumnName="ID", onDelete="CASCADE")
     * @var Member
     */
    private $owner;

    /**
     * @ORM\ManyToOne(targetEntity="models\summit\SummitEvent", inversedBy="feedback", fetch="LAZY")
     * @ORM\JoinColumn(name="EventID", referencedColumnName="ID", onDelete="CASCADE")
     * @var SummitEvent
     */
    private $event;

    /**
     * @return int
     */
    public function getRate()
    {
        return $this->rate;
    }

    /**
     * @param int $rate
     */
    public function setRate($rate)
    {
        $this->rate = $rate;
    }

    /**
     * @return string
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * @param string $note
     */
    public function setNote($note)
    {
        $this->note = $note;
    }

    /**
     * @return Member
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @param Member $owner
     */
    public function setOwner(Member $owner){
        $this->owner = $owner;
    }

    /**
     * @return SummitEvent
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * @return int
     */
    public function getEventId(){
        try{
            return $this->event->getId();
        }
        catch(\Exception $ex){
            return 0;
        }
    }

    /**
     * @return bool
     */
    public function hasOwner(){
        return $this->getOwnerId() > 0;
    }

    /**
     * @return int
     */
    public function getOwnerId(){
        try{
            return $this->owner->getId();
        }
        catch(\Exception $ex){
            return 0;
        }
    }

    public function clearOwner():void{
        $this->owner = null;
    }

    public function clearEvent():void{
        $this->event = null;
    }

    /**
     * @param SummitEvent $event
     */
    public function setEvent(SummitEvent $event){
        $this->event = $event;
    }

}