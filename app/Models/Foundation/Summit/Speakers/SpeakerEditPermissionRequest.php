<?php namespace App\Models\Foundation\Summit\Speakers;
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
use Doctrine\ORM\Mapping AS ORM;
use DateTime;
use Illuminate\Support\Facades\Config;
use models\main\Member;
use models\summit\PresentationSpeaker;
use models\utils\RandomGenerator;
use models\utils\SilverstripeBaseModel;
/**
 * @ORM\Entity(repositoryClass="App\Repositories\Summit\DoctrineSpeakerEditPermissionRequestRepository")
 * @ORM\Table(name="SpeakerEditPermissionRequest")
 * Class SpeakerEditPermissionRequest
 * @package models\summit
 */
class SpeakerEditPermissionRequest extends SilverstripeBaseModel
{

    /**
     * @ORM\ManyToOne(targetEntity="models\summit\PresentationSpeaker", inversedBy="granted_edit_permissions")
     * @ORM\JoinColumn(name="SpeakerID", referencedColumnName="ID")
     * @var PresentationSpeaker
     */
    private $speaker;

    /**
     * @ORM\ManyToOne(targetEntity="models\main\Member")
     * @ORM\JoinColumn(name="RequestedByID", referencedColumnName="ID")
     * @var Member
     */
    private $requested_by;

    /**
     * @ORM\Column(name="Approved", type="boolean")
     */
    private $approved;

    /**
     * @ORM\Column(name="ApprovedDate", type="datetime")
     */
    private $approved_date;

    /**
     * @ORM\Column(name="Hash", type="string")
     */
    private $hash;


    public function __construct()
    {
        parent::__construct();
        $this->approved = false;
    }

    /**
     * @return PresentationSpeaker
     */
    public function getSpeaker(): PresentationSpeaker
    {
        return $this->speaker;
    }

    /**
     * @param PresentationSpeaker $speaker
     */
    public function setSpeaker(PresentationSpeaker $speaker): void
    {
        $this->speaker = $speaker;
    }

    /**
     * @return Member
     */
    public function getRequestedBy(): Member
    {
        return $this->requested_by;
    }

    /**
     * @param Member $requested_by
     */
    public function setRequestedBy(Member $requested_by): void
    {
        $this->requested_by = $requested_by;
    }

    /**
     * @return bool
     */
    public function isApproved():bool
    {
        return $this->approved;
    }

    /**
     * @param mixed $approved
     */
    public function setApproved($approved): void
    {
        $this->approved = $approved;
    }

    /**
     * @return DateTime
     */
    public function getApprovedDate():DateTime
    {
        return $this->approved_date;
    }

    /**
     * @param DateTime $approved_date
     */
    public function setApprovedDate($approved_date): void
    {
        $this->approved_date = $approved_date;
    }

    /**
     * @return string
     */
    public function getHash():string
    {
        return $this->hash;
    }

    /**
     * @return string
     */
    public function generateConfirmationToken() {
        $generator  = new RandomGenerator();
        $token      = sprintf("%s.%s.%s",$generator->randomToken(), $this->speaker->getId() , $this->requested_by->getId());
        $this->hash = self::HashConfirmationToken($token);
        return $token;
    }

    /**
     * @param string $token
     * @return string
     */
    public static function HashConfirmationToken(string $token){
        return md5($token);
    }


    public function approve():void{
        $this->approved = true;
        $this->approved_date = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    public function reject():void{
        $this->approved = false;
        $this->approved_date = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * @return bool
     */
    public function isActionTaken():bool
    {
        return !is_null( $this->approved_date );
    }

    /**
     * @param int $speaker_id
     * @param string $token
     * @return string
     */
    public function getConfirmationLink(int $speaker_id, string $token): string{
        return sprintf("%s/api/public/v1/speakers/%s/edit-permission/%s/approve", Config::get("app.url", '#'), $speaker_id, $token);
    }

    /**
     * @return int
     */
    public function getSpeakerId():int {
        try {
            return !is_null($this->speaker) ? $this->speaker->getId() : 0;
        }
        catch(\Exception $ex){
            return 0;
        }
    }

    /**
     * @return int
     */
    public function getRequestedById():int {
        try {
            return !is_null($this->requested_by) ? $this->requested_by->getId() : 0;
        }
        catch(\Exception $ex){
            return 0;
        }
    }
}