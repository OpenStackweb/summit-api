<?php namespace App\Models\Foundation\Summit;
/**
 * Copyright 2018 OpenStack Foundation
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

use App\Models\Foundation\Main\OrderableChilds;
use App\Models\Foundation\Summit\ExtraQuestions\SummitSelectionPlanExtraQuestionType;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping AS ORM;
use App\Models\Utils\TimeZoneEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Illuminate\Support\Facades\Log;
use models\exceptions\ValidationException;
use models\main\Member;
use models\summit\Presentation;
use models\summit\PresentationCategory;
use models\summit\PresentationCategoryGroup;
use models\summit\Summit;
use models\summit\SummitEventType;
use models\summit\SummitOwned;
use models\summit\SummitSelectedPresentationList;
use models\utils\SilverstripeBaseModel;
use DateTime;
use DateTimeZone;
/**
 * @ORM\Entity(repositoryClass="App\Repositories\Summit\DoctrineSelectionPlanRepository")
 * @ORM\AssociationOverrides({
 *     @ORM\AssociationOverride(
 *          name="summit",
 *          inversedBy="selection_plans"
 *     )
 * })
 * @ORM\Table(name="SelectionPlan")
 * Class SelectionPlan
 * @package App\Models\Foundation\Summit
 */
class SelectionPlan extends SilverstripeBaseModel
{
    use SummitOwned;

    use TimeZoneEntity;

    const STATUS_SUBMISSION = 'SUBMISSION';
    const STATUS_SELECTION  = 'SELECTION';
    const STATUS_VOTING     = 'VOTING';

    /**
     * @ORM\Column(name="Name", type="string")
     * @var String
     */
    private $name;

    /**
     * @ORM\Column(name="MaxSubmissionAllowedPerUser", type="integer")
     * @var int
     */
    private $max_submission_allowed_per_user;

    /**
     * @ORM\Column(name="Enabled", type="boolean")
     * @var bool
     */
    private $is_enabled;

    /**
     * @ORM\Column(name="AllowNewPresentations", type="boolean")
     * @var bool
     */
    private $allow_new_presentations;

    /**
     * @ORM\Column(name="SubmissionBeginDate", type="datetime")
     * @var \DateTime
     */
    private $submission_begin_date;

    /**
     * @ORM\Column(name="SubmissionEndDate", type="datetime")
     * @var \DateTime
     */
    private $submission_end_date;

    /**
     * @ORM\Column(name="VotingBeginDate", type="datetime")
     * @var \DateTime
     */
    private $voting_begin_date;

    /**
     * @ORM\Column(name="VotingEndDate", type="datetime")
     * @var \DateTime
     */
    private $voting_end_date;

    /**
     * @ORM\Column(name="SelectionBeginDate", type="datetime")
     * @var \DateTime
     */
    private $selection_begin_date;

    /**
     * @ORM\Column(name="SelectionEndDate", type="datetime")
     * @var \DateTime
     */
    private $selection_end_date;

    /**
     * @ORM\Column(name="SubmissionPeriodDisclaimer", type="string")
     * @var String
     */
    private $submission_period_disclaimer;

    /**
     * @ORM\ManyToMany(targetEntity="models\summit\PresentationCategoryGroup")
     * @ORM\JoinTable(name="SelectionPlan_CategoryGroups",
     *      joinColumns={@ORM\JoinColumn(name="SelectionPlanID", referencedColumnName="ID")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="PresentationCategoryGroupID", referencedColumnName="ID")}
     *      )
     * @var PresentationCategoryGroup[]
     */
    private $category_groups;

    /**
     * @ORM\ManyToMany(targetEntity="models\summit\SummitEventType")
     * @ORM\JoinTable(name="SelectionPlan_SummitEventTypes",
     *      joinColumns={@ORM\JoinColumn(name="SelectionPlanID", referencedColumnName="ID")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="SummitEventTypeID", referencedColumnName="ID")}
     *      )
     * @var SummitEventType[]
     */
    private $event_types;

    /**
     * @ORM\OneToMany(targetEntity="models\summit\Presentation", mappedBy="selection_plan", cascade={"persist"})
     * @var Presentation[]
     */
    private $presentations;

    /**
     * @ORM\OneToMany(targetEntity="App\Models\Foundation\Summit\ExtraQuestions\SummitSelectionPlanExtraQuestionType", mappedBy="selection_plan",  cascade={"persist","remove"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @var SummitSelectionPlanExtraQuestionType[]
     */
    private $extra_questions;

    /**
     * @ORM\OneToMany(targetEntity="models\summit\SummitSelectedPresentationList", mappedBy="selection_plan", cascade={"persist","remove"}, orphanRemoval=true)
     * @var SummitSelectedPresentationList[]
     */
    private $selection_lists;

    /**
     * @return string
     */
    public function getTimeZoneId()
    {
        return $this->summit->getTimeZoneId();
    }

    /**
     * @return DateTimeZone|null
     */
    public function getTimeZone()
    {
        return $this->summit->getTimeZone();
    }

    /**
     * @return DateTime
     */
    public function getSubmissionBeginDate()
    {
        return $this->submission_begin_date;
    }

    /**
     * @param DateTime $submission_begin_date
     */
    public function setSubmissionBeginDate(DateTime $submission_begin_date){
        $this->submission_begin_date = $this->convertDateFromTimeZone2UTC($submission_begin_date);
    }

    /**
     * @return $this
     */
    public function clearSubmissionDates(){
        $this->submission_begin_date =  $this->submission_end_date = null;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getSubmissionEndDate()
    {
        return $this->submission_end_date;
    }

    public function getSubmissionEndDateLocal():?DateTime{
        return $this->convertDateFromUTC2TimeZone($this->submission_end_date);
    }

    public function getSubmissionBeginDateLocal():?DateTime{
        return $this->convertDateFromUTC2TimeZone($this->submission_begin_date);
    }

    /**
     * @param DateTime $submission_end_date
     */
    public function setSubmissionEndDate(DateTime $submission_end_date){
        $this->submission_end_date = $this->convertDateFromTimeZone2UTC($submission_end_date);
    }

    /**
     * @return DateTime
     */
    public function getVotingBeginDate()
    {
        return $this->voting_begin_date;
    }

    /**
     * @param DateTime $voting_begin_date
     */
    public function setVotingBeginDate(DateTime $voting_begin_date){
        $this->voting_begin_date = $this->convertDateFromTimeZone2UTC($voting_begin_date);
    }

    /**
     * @return $this
     */
    public function clearVotingDates(){
        $this->voting_begin_date = $this->voting_end_date = null;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getVotingEndDate()
    {
        return $this->voting_end_date;
    }

    /**
     * @param DateTime $voting_end_date
     */
    public function setVotingEndDate(DateTime $voting_end_date){
        $this->voting_end_date = $this->convertDateFromTimeZone2UTC($voting_end_date);
    }

    /**
     * @return DateTime
     */
    public function getSelectionBeginDate()
    {
        return $this->selection_begin_date;
    }

    /**
     * @param DateTime $selection_begin_date
     */
    public function setSelectionBeginDate(DateTime $selection_begin_date){
        $this->selection_begin_date = $this->convertDateFromTimeZone2UTC($selection_begin_date);
    }

    /**
     * @return $this
     */
    public function clearSelectionDates(){
        $this->selection_begin_date =  $this->selection_end_date = null;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getSelectionEndDate()
    {
        return $this->selection_end_date;
    }

    /**
     * @param DateTime $selection_end_date
     */
    public function setSelectionEndDate(DateTime $selection_end_date){
        $this->selection_end_date = $this->convertDateFromTimeZone2UTC($selection_end_date);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return bool
     */
    public function IsEnabled()
    {
        return $this->is_enabled;
    }

    /**
     * @param bool $is_enabled
     */
    public function setIsEnabled($is_enabled)
    {
        $this->is_enabled = $is_enabled;
    }

    /**
     * SelectionPlan constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->is_enabled                      = false;
        $this->allow_new_presentations         = true;
        $this->category_groups                 = new ArrayCollection;
        $this->presentations                   = new ArrayCollection;
        $this->extra_questions                 = new ArrayCollection;
        $this->event_types                     = new ArrayCollection;
        $this->max_submission_allowed_per_user = Summit::DefaultMaxSubmissionAllowedPerUser;
        $this->submission_period_disclaimer    = null;
        $this->selection_lists = new ArrayCollection;
    }

    /**
     * @return PresentationCategoryGroup[]
     */
    public function getCategoryGroups()
    {
        return $this->category_groups;
    }

    public function getEventTypes(){
        return $this->event_types;
    }

    /**
     * @param PresentationCategoryGroup $track_group
     */
    public function addTrackGroup(PresentationCategoryGroup $track_group){
        if($this->category_groups->contains($track_group)) return;
        $this->category_groups->add($track_group);
    }

    /**
     * @param PresentationCategoryGroup $track_group
     */
    public function removeTrackGroup(PresentationCategoryGroup $track_group){
        if(!$this->category_groups->contains($track_group)) return;
        $this->category_groups->removeElement($track_group);
    }

    public function addEventType(SummitEventType $eventType){
        if($this->event_types->contains($eventType)) return;
        $this->event_types->add($eventType);
    }

    public function removeEventType(SummitEventType $eventType){
        if(!$this->event_types->contains($eventType)) return;
        $this->event_types->removeElement($eventType);
    }

    /**
     * @return int
     */
    public function getMaxSubmissionAllowedPerUser()
    {
        return $this->max_submission_allowed_per_user;
    }

    /**
     * @param int $max_submission_allowed_per_user
     */
    public function setMaxSubmissionAllowedPerUser($max_submission_allowed_per_user)
    {
        $this->max_submission_allowed_per_user = $max_submission_allowed_per_user;
    }

    /**
     * @return Presentation[]
     */
    public function getPresentations()
    {
        return $this->presentations;
    }

    /**
     * @param Presentation $presentation
     */
    public function addPresentation(Presentation $presentation){
        if($this->presentations->contains($presentation)) return;
        $this->presentations->add($presentation);
        $presentation->setSelectionPlan($this);
    }

    public function getStageStatus($stage) {

        $getStartDate = "get{$stage}BeginDate";
        $getEndDate = "get{$stage}EndDate";
        $start_date = $this->$getStartDate();
        $end_date = $this->$getEndDate();

        if (empty($start_date) || empty($end_date)) {
            return null;
        }

        $utc_time_zone = new \DateTimeZone('UTC');
        $start_date->setTimeZone($utc_time_zone);
        $end_date->setTimeZone($utc_time_zone);
        $now = new \DateTime('now', new \DateTimeZone(  'UTC'));

        if ($now > $end_date) {
            return Summit::STAGE_FINISHED;
        } else if ($now < $start_date) {
            return Summit::STAGE_UNSTARTED;
        } else {
            return Summit::STAGE_OPEN;
        }
    }

    /**
     * @param PresentationCategory $track
     * @return bool
     */
    public function hasTrack(PresentationCategory $track){
        foreach($this->category_groups as $track_group){
            if($track_group->hasCategory($track->getIdentifier())) return true;
        }
        return false;
    }

    /**
     * @return bool
     */
    public function isVotingOpen()
    {
        return $this->getStageStatus('Voting') === Summit::STAGE_OPEN;
    }

    /**
     * @return bool
     */
    public function isSubmissionOpen()
    {
        return $this->getStageStatus('Submission') === Summit::STAGE_OPEN;
    }

    /**
     * @return bool
     */
    public function isSelectionOpen()
    {
        return $this->getStageStatus('Selection') === Summit::STAGE_OPEN;
    }

    /**
     * @return bool
     */
    public function isAllowNewPresentations(): bool
    {
        return $this->allow_new_presentations;
    }

    /**
     * @param bool $allow_new_presentations
     */
    public function setAllowNewPresentations(bool $allow_new_presentations): void
    {
        $this->allow_new_presentations = $allow_new_presentations;
    }

    /**
     * @param int $id
     * @return Presentation|null
     */
    public function getPresentation(int $id):?Presentation{
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', intval($id)));
        $presentation = $this->presentations->matching($criteria)->first();
        return $presentation === false ? null : $presentation;
    }

    /**
     *  Extra Questions
     */


    /**
     * @param int $question_id
     * @return SummitSelectionPlanExtraQuestionType|null
     */
    public function getExtraQuestionById(int $question_id):?SummitSelectionPlanExtraQuestionType{
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', $question_id));
        $question = $this->extra_questions->matching($criteria)->first();
        return $question === false ? null : $question;
    }

    /**
     * @param string $name
     * @return SummitSelectionPlanExtraQuestionType|null
     */
    public function getExtraQuestionByName(string $name):?SummitSelectionPlanExtraQuestionType{
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('name', trim($name)));
        $question = $this->extra_questions->matching($criteria)->first();
        return $question === false ? null : $question;
    }

    /**
     * @param string $label
     * @return SummitSelectionPlanExtraQuestionType|null
     */
    public function getExtraQuestionByLabel(string $label):?SummitSelectionPlanExtraQuestionType{
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('label', trim($label)));
        $question = $this->extra_questions->matching($criteria)->first();
        return $question === false ? null : $question;
    }

    /**
     * @return int
     */
    private function getExtraQuestionMaxOrder():int{
        $criteria = Criteria::create();
        $criteria->orderBy(['order' => 'DESC']);
        $question = $this->extra_questions->matching($criteria)->first();
        return $question === false ? 0 : $question->getOrder();
    }

    /**
     * @param SummitSelectionPlanExtraQuestionType $question
     * @throws ValidationException
     */
    public function addExtraQuestion(SummitSelectionPlanExtraQuestionType $question){
        if($this->extra_questions->contains($question)) return;
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('name', $question->getName()));
        $formerExtraQuestion = $this->extra_questions->matching($criteria)->first();
        if($formerExtraQuestion){
            throw new ValidationException(sprintf("Question Name %s already exists.", $question->getName()));
        };
        $question->setOrder($this->getExtraQuestionMaxOrder()+1);
        $this->extra_questions->add($question);
        $question->setSelectionPlan($this);
    }

    public function removeExtraQuestion(SummitSelectionPlanExtraQuestionType $question){
        if(!$this->extra_questions->contains($question)) return;
        $this->extra_questions->removeElement($question);
        $question->clearSelectionPlan();
    }

    use OrderableChilds;

    /**
     * @param SummitSelectionPlanExtraQuestionType $question
     * @param int $new_order
     * @throws ValidationException
     */
    public function recalculateQuestionOrder(SummitSelectionPlanExtraQuestionType $question, $new_order){
        self::recalculateOrderForSelectable($this->extra_questions, $question, $new_order);
    }

    /**
     * @return ArrayCollection|\Doctrine\Common\Collections\Collection
     */
    public function getExtraQuestions(){
        $criteria = Criteria::create();
        $criteria->orderBy(['order' => 'ASC']);
        return $this->extra_questions->matching($criteria);
    }

    /**
     * @return ArrayCollection|\Doctrine\Common\Collections\Collection
     */
    public function getMandatoryExtraQuestions(){
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('mandatory', true));
        return $this->extra_questions->matching($criteria);
    }

    /**
     * @return int
     */
    public function getSubmissionLimitFor():int
    {
        $res = -1;
        if ($this->isSubmissionOpen()) {
            $res = $this->getMaxSubmissionAllowedPerUser();
        }

        // zero means infinity
        return $res === 0 ? PHP_INT_MAX : $res;
    }

    /**
     * @param SummitEventType $type
     * @return bool
     */
    public function hasEventType(SummitEventType $type):bool{
        return $this->getEventTypeById($type->getId()) != null;
    }

    /**
     * @param int $eventTypeId
     * @return SummitEventType|null
     */
    public function getEventTypeById(int $eventTypeId):?SummitEventType{
        //$criteria = Criteria::create();
        //$criteria->where(Criteria::expr()->eq('id', intval($eventTypeId)));
        $event_type = $this->event_types->filter(function($e) use($eventTypeId){
            return $e->getId() === $eventTypeId;
        })->first();
        return $event_type === false ? null : $event_type;
    }

    /*
     * @return String
     */
    public function getSubmissionPeriodDisclaimer(): ?string
    {
        return $this->submission_period_disclaimer;
    }

    /**
     * @param String $submission_period_disclaimer
     */
    public function setSubmissionPeriodDisclaimer(?string $submission_period_disclaimer): void
    {
        $this->submission_period_disclaimer = $submission_period_disclaimer;
    }

    /**
     * @return SummitSelectedPresentationList[]
     */
    public function getSelectionLists()
    {
        return $this->selection_lists;
    }

    /**
     * @param SummitSelectedPresentationList $selection_list
     */
    public function addSelectionList(SummitSelectedPresentationList $selection_list){
        if($this->selection_lists->contains($selection_list)) return;
        $this->selection_lists->add($selection_list);
        $selection_list->setSelectionPlan($this);
    }

    /**
     * @param SummitSelectedPresentationList $selection_list
     */
    public function removeSelectionList(SummitSelectedPresentationList $selection_list){
        if(!$this->selection_lists->contains($selection_list)) return;
        $this->selection_lists->removeElement($selection_list);
        $selection_list->clearSelectionPlan();
    }

    /**
     * @param PresentationCategory $track
     * @param string $list_type
     * @param Member|null $owner
     * @return SummitSelectedPresentationList|null
     * @throws ValidationException
     */
    public function getSelectionListByTrackAndTypeAndOwner
    (
        PresentationCategory $track,
        string $list_type,
        ?Member $owner = null
    ):?SummitSelectedPresentationList{
        if(!in_array($list_type, SummitSelectedPresentationList::ValidListTypes))
            throw new ValidationException(sprintf("List Type %s is not valid.", $list_type));

        $criteria = Criteria::create();
        $criteria->andWhere(Criteria::expr()->eq('list_type', $list_type));
        $criteria->andWhere(Criteria::expr()->eq('category', $track));

        if($list_type == SummitSelectedPresentationList::Individual){
            $criteria->andWhere(Criteria::expr()->eq('owner', $owner));
        }

        $list = $this->selection_lists->matching($criteria)->first();
        return $list === false ? null : $list;
    }

    /**
     * @param int $list_id
     * @return SummitSelectedPresentationList|null
     */
    public function getSelectionListById(int $list_id):?SummitSelectedPresentationList{
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', $list_id));

        $list = $this->selection_lists->matching($criteria)->first();
        return $list === false ? null : $list;
    }

    /**
     * @param PresentationCategory $track
     * @return SummitSelectedPresentationList
     * @throws ValidationException
     */
    public function createTeamSelectionList(PresentationCategory $track):SummitSelectedPresentationList{
        $team_selection_list = $this->getSelectionListByTrackAndTypeAndOwner($track,SummitSelectedPresentationList::Group);
        if (is_null($team_selection_list)) {
            Log::debug(sprintf("SelectionPlan::createSelectionLists adding team list for track %s selection plan %s", $track->getId(), $this->getId()));
            $team_selection_list = new SummitSelectedPresentationList();
            $team_selection_list->setName(sprintf("Team Selections for %s", $track->getTitle()));
            $team_selection_list->setListType(SummitSelectedPresentationList::Group);
            $team_selection_list->setListClass(SummitSelectedPresentationList::Session);
            $track->addSelectionList($team_selection_list);
            $this->addSelectionList($team_selection_list);
        }
        return $team_selection_list;
    }

    /**
     * @param PresentationCategory $track
     * @param Member $member
     * @return SummitSelectedPresentationList
     * @throws ValidationException
     */
    public function createIndividualSelectionList(PresentationCategory $track, Member $member ):SummitSelectedPresentationList{
        if(!is_null($member)) {
            $individual_selection_list = $this->getSelectionListByTrackAndTypeAndOwner($track, SummitSelectedPresentationList::Individual, $member);

            if (is_null($individual_selection_list)) {
                $individual_selection_list = new SummitSelectedPresentationList();
                Log::debug(sprintf("SelectionPLan::createSelectionLists adding individual list for track %s and member %s", $track->getId(), $member->getId()));
                $individual_selection_list->setName(sprintf("%s Individual Selection List for %s", $member->getFullName(), $track->getTitle()));
                $individual_selection_list->setListType(SummitSelectedPresentationList::Individual);
                $individual_selection_list->setListClass(SummitSelectedPresentationList::Session);
                $individual_selection_list->setOwner($member);
                $this->addSelectionList($individual_selection_list);
                $track->addSelectionList($individual_selection_list);
            }
        }
        return $individual_selection_list;
    }


}