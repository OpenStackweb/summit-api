<?php namespace models\summit;
/**
 * Copyright 2017 OpenStack Foundation
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
use models\utils\RandomGenerator;
use models\utils\SilverstripeBaseModel;
use Doctrine\ORM\Mapping AS ORM;
/**
 * Class PresentationSpeakerSummitAssistanceConfirmationRequest
 * @package models\summit
 */
#[ORM\Table(name: 'PresentationSpeakerSummitAssistanceConfirmationRequest')]
#[ORM\Entity(repositoryClass: \App\Repositories\Summit\DoctrinePresentationSpeakerSummitAssistanceConfirmationRequestRepository::class)]
#[ORM\AssociationOverrides([new ORM\AssociationOverride(name: 'summit', inversedBy: 'speaker_assistances')])]
class PresentationSpeakerSummitAssistanceConfirmationRequest extends SilverstripeBaseModel
{
    /**
     * @var string
     */
    #[ORM\Column(name: 'OnSitePhoneNumber', type: 'string')]
    private $on_site_phone;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'RegisteredForSummit', type: 'boolean')]
    private $registered;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'IsConfirmed', type: 'boolean')]
    private $is_confirmed;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'CheckedIn', type: 'boolean')]
    private $checked_in;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'ConfirmationDate', type: 'datetime')]
    private $confirmation_date;

    /**
     * @var PresentationSpeaker
     */
    #[ORM\JoinColumn(name: 'SpeakerID', referencedColumnName: 'ID')]
    #[ORM\ManyToOne(targetEntity: \PresentationSpeaker::class, inversedBy: 'summit_assistances')]
    private $speaker;

    /**
     * @var string
     */
    #[ORM\Column(name: 'ConfirmationHash', type: 'string')]
    private $confirmation_hash;

    /**
     * @return string
     */
    public function getOnSitePhone()
    {
        return $this->on_site_phone;
    }

    /**
     * @param string $on_site_phone
     */
    public function setOnSitePhone($on_site_phone)
    {
        $this->on_site_phone = $on_site_phone;
    }

    /**
     * @return bool
     */
    public function isRegistered()
    {
        return $this->registered;
    }

    /**
     * @param bool $registered
     */
    public function setRegistered($registered)
    {
        $this->registered = $registered;
    }

    /**
     * @return bool
     */
    public function isConfirmed()
    {
        return $this->is_confirmed;
    }

    /**
     * @param bool $is_confirmed
     */
    public function setIsConfirmed($is_confirmed)
    {
        $this->is_confirmed      = $is_confirmed;
        $this->confirmation_date = new \DateTime('now', new \DateTimeZone(self::DefaultTimeZone));
    }

    /**
     * @return bool
     */
    public function isCheckedIn()
    {
        return $this->checked_in;
    }

    /**
     * @param bool $checked_in
     */
    public function setCheckedIn($checked_in)
    {
        $this->checked_in = $checked_in;
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

    use SummitOwned;

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
     * @return \DateTime
     */
    public function getConfirmationDate()
    {
        return $this->confirmation_date;
    }

    /**
     * @param \DateTime $confirmation_date
     */
    public function setConfirmationDate(\DateTime $confirmation_date)
    {
        $this->confirmation_date = $confirmation_date;
    }


    /**
     * @var string
     */
    private $token;

    /**
     * @return string
     */
    public function generateConfirmationToken() {
        $generator               = new RandomGenerator();
        $this->token             = $generator->randomToken();
        $this->confirmation_hash = self::HashConfirmationToken($this->token);
        return $this->token;
    }

    /**
     * @param string $token
     * @return string
     */
    public static function HashConfirmationToken($token){
        return md5($token);
    }

    public function getConfirmationHash():?string{
        return $this->confirmation_hash;
    }

    public function getToken():?string{
        return $this->token;
    }

    public function __construct()
    {
        parent::__construct();
        $this->registered   = false;
        $this->is_confirmed = false;
        $this->checked_in   = false;
    }

    public function getSpeakerEmail():?string{
        return $this->speaker->getEmail();
    }

    public function getSpeakerFullName():?string{
        return $this->speaker->getFullName();
    }
}