<?php namespace models\summit;
/**
 * Copyright 2016 OpenStack Foundation
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
use App\Models\Foundation\Summit\Events\RSVP\RSVPMultiValueQuestionTemplate;
use App\Models\Foundation\Summit\Events\RSVP\RSVPQuestionTemplate;
use models\utils\SilverstripeBaseModel;
use Doctrine\ORM\Mapping AS ORM;
/**
 * @ORM\Entity
 * @ORM\Table(name="RSVPAnswer")
 * Class RSVPAnswer
 * @package models\summit
 */
class RSVPAnswer extends SilverstripeBaseModel
{
    /**
     * @ORM\Column(name="Value", type="string")
     * @var string
     */
    private $value;

    /**
     * @ORM\ManyToOne(targetEntity="models\summit\RSVP", inversedBy="answers")
     * @ORM\JoinColumn(name="RSVPID", referencedColumnName="ID", onDelete="CASCADE")
     * @var RSVP
     */
    private $rsvp;

    /**
     * @ORM\ManyToOne(targetEntity="App\Models\Foundation\Summit\Events\RSVP\RSVPQuestionTemplate")
     * @ORM\JoinColumn(name="QuestionID", referencedColumnName="ID")
     * @var RSVPQuestionTemplate
     */
    private $question;

    /**
     * @return string|null
     */
    public function getValue():?string
    {
        return $this->value;
    }

    /**
     * @param array|string $value
     */
    public function setValue($value)
    {
        if (is_array($value)) {
            $value = implode(',', $value);
        }
        $this->value = $value;
    }

    /**
     * @return int
     */
    public function getQuestionId()
    {
        try{
            return $this->question->getId();
        }
        catch(\Exception $ex){
            return 0;
        }
    }

    /**
     * @return int
     */
    public function getRSVPId()
    {
        try{
            return $this->rsvp->getId();
        }
        catch(\Exception $ex){
            return 0;
        }
    }

    /**
     * @return RSVP
     */
    public function getRsvp(): RSVP
    {
        return $this->rsvp;
    }

    /**
     * @param RSVP $rsvp
     */
    public function setRsvp(RSVP $rsvp): void
    {
        $this->rsvp = $rsvp;
    }

    /**
     * @return RSVPQuestionTemplate
     */
    public function getQuestion(): RSVPQuestionTemplate
    {
        return $this->question;
    }

    /**
     * @param RSVPQuestionTemplate $question
     */
    public function setQuestion(RSVPQuestionTemplate $question): void
    {
        $this->question = $question;
    }

    public function clearRSVP(){
        $this->rsvp = null;
    }

    public function clearQuestion(){
        $this->question = null;
    }

}