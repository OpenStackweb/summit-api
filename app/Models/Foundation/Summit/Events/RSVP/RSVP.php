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
use Doctrine\Common\Collections\Criteria;
use App\Models\Foundation\Summit\Events\RSVP\RSVPQuestionTemplate;
use models\exceptions\ValidationException;
use models\main\Member;
use models\utils\SilverstripeBaseModel;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping AS ORM;
/**
 * @package models\summit
 */
#[ORM\Table(name: 'RSVP')]
#[ORM\Entity(repositoryClass: \App\Repositories\Summit\DoctrineRSVPRepository::class)] // Class RSVP
class RSVP extends SilverstripeBaseModel
{

    const SeatTypeRegular = 'Regular';
    const SeatTypeWaitList = 'WaitList';

    const ValidSeatTypes = [self::SeatTypeRegular, self::SeatTypeWaitList];

    /**
     * @var string
     */
    #[ORM\Column(name: 'SeatType', type: 'string')]
    protected $seat_type;

    /**
     * @var string
     */
    #[ORM\Column(name: 'EventUri', type: 'string')]
    protected $event_uri;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'BeenEmailed', type: 'boolean')]
    protected $been_emailed;

    /**
     * @var Member
     */
    #[ORM\JoinColumn(name: 'SubmittedByID', referencedColumnName: 'ID', onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: \models\main\Member::class, inversedBy: 'rsvp')]
    private $owner;

    /**
     * @var SummitEvent
     */
    #[ORM\JoinColumn(name: 'EventID', referencedColumnName: 'ID', onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: \models\summit\SummitEvent::class, inversedBy: 'rsvp')]
    private $event;

    /**
     * @var RSVPAnswer[]
     */
    #[ORM\OneToMany(targetEntity: \models\summit\RSVPAnswer::class, mappedBy: 'rsvp', cascade: ['persist', 'remove'], orphanRemoval: true)]
    protected $answers;


    public const string Status_Active = 'Active';
    public const string Status_Inactive = 'Inactive';

    public const string Status_TicketReassigned = 'TicketReassigned';


    public const array AllowedStatus = [
        self::Status_Active,
        self::Status_Inactive,
        self::Status_TicketReassigned
    ];

    #[ORM\Column(name: 'Status', type: 'string')]
    protected string $status;

    /**
     * @var ?\DateTime
     */
    #[ORM\Column(name: 'ActionDate', type: 'datetime')]
    private ?\DateTime $action_date;

    public const string ActionSource_Schedule = 'Schedule';
    public const string ActionSource_Invitation = 'Invitation';

    public const array Valid_ActionSources = [
        self::ActionSource_Schedule,
        self::ActionSource_Invitation
    ];
    /**
     * @var string
     */
    #[ORM\Column(name: 'ActionSource', type: 'string', nullable: true)]
    private ?string $action_source;

    /**
     * RSVP constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->seat_type    = null;
        $this->answers      = new ArrayCollection();
        $this->been_emailed = false;
        $this->event_uri    = null;
        $this->status       = self::Status_Active;
        $this->action_date  = null;
        $this->action_source = null;
    }

    /**
     * @return bool
     */
    public function hasSeatTypeSet():bool{
        return !empty($this->seat_type);
    }

    /**
     * @return ArrayCollection
     */
    public function getAnswers()
    {
        return $this->answers;
    }

    /**
     * @param ArrayCollection $answers
     */
    public function setAnswers($answers)
    {
        $this->answers = $answers;
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
    public function hasEvent(){
        return $this->getEventId() > 0;
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

    /**
     * @param SummitEvent $event
     */
    public function setEvent(SummitEvent $event){
        $this->event = $event;
    }

    /**
     * @return string
     */
    public function getSeatType():?string
    {
        return $this->seat_type;
    }

    /**
     * @param string $seat_type
     * @throws ValidationException
     */
    public function setSeatType(string $seat_type)
    {
        if(!in_array($seat_type, self::ValidSeatTypes))
            throw new ValidationException(sprintf("Seat type %s is not valid."), $seat_type);
        $this->seat_type = $seat_type;
    }

    public function upgradeToRegularSeatType():void{
        $this->setSeatType(self::SeatTypeRegular);
    }

    /**
     * @return void
     * @throws ValidationException
     */
    public function downgradeToWaitSeatType():void{
        if(!$this->getEvent()->hasRSVPWaitList())
            throw new ValidationException(sprintf("Event %s does not has RSVP Wait List", $this->getEventId()));
        $this->setSeatType(self::SeatTypeWaitList);
    }

    /**
     * @return bool
     */
    public function isBeenEmailed(): bool
    {
        return $this->been_emailed;
    }

    /**
     * @param bool $been_emailed
     */
    public function setBeenEmailed(bool $been_emailed): void
    {
        $this->been_emailed = $been_emailed;
    }

    public function clearEvent(){
        $this->event = null;
    }

    public function clearOwner(){
        $this->owner = null;
    }

    /**
     * @param RSVPQuestionTemplate $question
     * @return RSVPAnswer|null
     */
    public function findAnswerByQuestion(RSVPQuestionTemplate $question):?RSVPAnswer{
        $criteria = Criteria::create();
        $criteria = $criteria->where(Criteria::expr()->eq('question', $question));
        $answer = $this->answers->matching($criteria)->first();
        return !$answer ? null:$answer;
    }

    public function addAnswer(RSVPAnswer $answer){
        if($this->answers->contains($answer)) return;
        $this->answers->add($answer);
        $answer->setRsvp($this);
    }

    public function removeAnswer(RSVPAnswer $answer){
        if(!$this->answers->contains($answer)) return;
        $this->answers->removeElement($answer);
        $answer->clearRSVP();
    }

    public function clearAnswers(){
        $this->answers->clear();
    }

    /**
     * @return string|null
     */
    public function getConfirmationNumber():?string{
        if(!$this->hasEvent()) return null;
        if(!$this->getEvent()->hasSummit()) return null;
        $summit        = $this->event->getSummit();
        $summit_title  = substr($summit->getName(),0,3);
        $summit_year   = $summit->getLocalBeginDate()->format('y');
        return strtoupper($summit_title).$summit_year.$this->id;
    }

    /**
     * @return string
     */
    public function getEventUri(): ?string
    {
        return $this->event_uri;
    }

    /**
     * @param string $event_uri
     */
    public function setEventUri(string $event_uri): void
    {
        $this->event_uri = $event_uri;
    }

    public function getStatus(): string{
        return $this->status;
    }

    public function deactivate():void{
        $this->status = self::Status_Inactive;
        $this->action_date = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    public function activate():void{
        $this->status = self::Status_Active;
        $this->action_date = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    public function ticketReassigned():void{
        $this->status = self::Status_TicketReassigned;
        $this->action_date = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    public function getActionSource():?string{
        return $this->action_source;
    }

    public function setActionSource(string $action_source):void{
        if(!in_array($action_source, self::Valid_ActionSources))
            throw new ValidationException(sprintf("Action Source %s is not valid.", $action_source), $action_source);
        $this->action_source = $action_source;
    }

    public function getActionDate():?\DateTime{
        return $this->action_date;
    }

    /**
     * @param string $status
     * @return void
     * @throws ValidationException
     */
    public function setStatus(string $status):void{
        if(!in_array($status, self::AllowedStatus))
            throw new ValidationException(sprintf("Status %s is not valid.", $status), $status);
        $this->status = $status;
    }
}