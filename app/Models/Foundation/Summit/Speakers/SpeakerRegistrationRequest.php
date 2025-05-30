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

use App\libs\Utils\PunnyCodeHelper;
use models\main\Member;
use models\utils\RandomGenerator;
use models\utils\SilverstripeBaseModel;
use Doctrine\ORM\Mapping AS ORM;

/**
 * Class SpeakerRegistrationRequest
 * @package models\summit
 */
#[ORM\Table(name: 'SpeakerRegistrationRequest')]
#[ORM\Entity(repositoryClass: \App\Repositories\Summit\DoctrineSpeakerRegistrationRequestRepository::class)]
class SpeakerRegistrationRequest extends SilverstripeBaseModel
{

    /**
     * @var string
     */
    #[ORM\Column(name: 'Email', type: 'string')]
    private $email;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'IsConfirmed', type: 'boolean')]
    private $is_confirmed;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'ConfirmationDate', type: 'datetime')]
    private $confirmation_date;

    /**
     * @var PresentationSpeaker
     */
    #[ORM\JoinColumn(name: 'SpeakerID', referencedColumnName: 'ID')]
    #[ORM\ManyToOne(targetEntity: \PresentationSpeaker::class)]
    private $speaker;

    /**
     * @var Member
     */
    #[ORM\JoinColumn(name: 'ProposerID', referencedColumnName: 'ID')]
    #[ORM\ManyToOne(targetEntity: \models\main\Member::class)]
    private $proposer;

    /**
     * @var string
     */
    #[ORM\Column(name: 'ConfirmationHash', type: 'string')]
    private $confirmation_hash;

    /**
     * @var string
     */
    private $token;

    /**
     * @return string
     */
    public function getEmail()
    {
        return PunnyCodeHelper::decodeEmail($this->email);
    }

    /**
     * @param mixed $email
     */
    public function setEmail($email)
    {
        $this->email = PunnyCodeHelper::encodeEmail($email);
    }

    /**
     * @return mixed
     */
    public function isConfirmed()
    {
        return $this->is_confirmed;
    }

    /**
     * @param mixed $is_confirmed
     */
    public function setIsConfirmed($is_confirmed)
    {
        $this->is_confirmed = $is_confirmed;
    }

    /**
     * @return mixed
     */
    public function getConfirmationDate()
    {
        return $this->confirmation_date;
    }

    /**
     * @param mixed $confirmation_date
     */
    public function setConfirmationDate($confirmation_date)
    {
        $this->confirmation_date = $confirmation_date;
    }

    /**
     * @return PresentationSpeaker
     */
    public function getSpeaker()
    {
        return $this->speaker;
    }

    /**
     * @return int
     */
    public function getSpeakerId(){
        try {
            return is_null($this->speaker) ? 0 : $this->speaker->getId();
        }
        catch(\Exception $ex){
            return 0;
        }
    }

    /**
     * @return bool
     */
    public function hasSpeaker():bool{
        return $this->getSpeakerId() > 0;
    }

    public function clearSpeaker(){
        $this->speaker = null;
    }

    /**
     * @param PresentationSpeaker $speaker
     */
    public function setSpeaker($speaker)
    {
        $this->speaker = $speaker;
    }

    /**
     * @return Member
     */
    public function getProposer()
    {
        return $this->proposer;
    }

    /**
     * @param Member $proposer
     */
    public function setProposer($proposer)
    {
        $this->proposer = $proposer;
    }

    /**
     * @return string
     */
    public function generateConfirmationToken() {
        $generator               = new RandomGenerator();
        $this->is_confirmed      = false;
        $this->confirmation_date = null;
        $this->token             = $generator->randomToken();
        $this->confirmation_hash = self::HashConfirmationToken($this->token);
        return $this->token;
    }

    public static function HashConfirmationToken($token){
        return md5($token);
    }

    /**
     * @return string
     */
    public function getConfirmationHash()
    {
        return $this->confirmation_hash;
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    public function confirm():void{
        $this->confirmation_date = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->is_confirmed = true;
    }

    public function __construct()
    {
        parent::__construct();
        $this->is_confirmed = false;
    }
}