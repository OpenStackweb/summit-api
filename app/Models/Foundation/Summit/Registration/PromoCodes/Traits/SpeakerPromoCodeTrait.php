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

use models\exceptions\ValidationException;
use models\main\Member;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping AS ORM;
/**
 * Trait SpeakerPromoCodeTrait
 * @package models\summit
 */
trait SpeakerPromoCodeTrait
{
    /**
     * @ORM\Column(name="Type", type="string")
     * @var string
     */
    protected $type;

    /**
     * @ORM\ManyToOne(targetEntity="PresentationSpeaker", inversedBy="promo_codes")
     * @ORM\JoinColumn(name="SpeakerID", referencedColumnName="ID")
     * @var PresentationSpeaker
     */
    protected $speaker;

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return PresentationSpeaker
     */
    public function getSpeaker()
    {
        return $this->speaker;
    }

    /**
     * @param PresentationSpeaker $speaker
     */
    public function setSpeaker($speaker)
    {
        $this->speaker = $speaker;
    }

    /**
     * @return int
     */
    public function getSpeakerId(){
        try {
            return !is_null($this->speaker) ? $this->speaker->getId() : 0;
        }
        catch(\Exception $ex){
            return 0;
        }
    }

    /**
     * @return bool
     */
    public function hasSpeaker(){
        return $this->getSpeakerId() > 0;
    }

    /**
     * @param string $email
     * @param null|string $company
     * @return bool
     * @throw ValidationException
     */
    public function checkSubject(string $email, ?string $company):bool{
        if($this->hasOwner() && $this->getOwnerEmail() != $email){
            throw new ValidationException(sprintf('The Promo Code “%s” is not valid for the %s. Promo Code restrictions are associated with the purchaser email not the attendee.', $this->getCode(), $email));
        }
        return true;
    }
}