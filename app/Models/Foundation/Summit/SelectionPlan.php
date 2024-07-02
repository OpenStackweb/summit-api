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
use App\Models\Foundation\Summit\Events\Presentations\TrackChairs\PresentationTrackChairRatingType;
use App\Models\Foundation\Summit\ExtraQuestions\AssignedSelectionPlanExtraQuestionType;
use App\Models\Foundation\Summit\ExtraQuestions\SummitSelectionPlanExtraQuestionType;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use App\Models\Utils\TimeZoneEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\NoResultException;
use Illuminate\Support\Facades\Log;
use models\exceptions\ValidationException;
use models\main\Member;
use models\summit\AllowedPresentationActionType;
use models\summit\Presentation;
use models\summit\PresentationActionType;
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

    use OrderableChilds;

    const STATUS_SUBMISSION = 'SUBMISSION';
    const STATUS_SELECTION = 'SELECTION';
    const STATUS_VOTING = 'VOTING';

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
     * @ORM\Column(name="IsHidden", type="boolean")
     * @var bool
     */
    private $is_hidden;

    /**
     * @ORM\Column(name="AllowNewPresentations", type="boolean")
     * @var bool
     */
    private $allow_new_presentations;

    /**
     * @ORM\Column(name="AllowProposedSchedules", type="boolean")
     * @var bool
     */
    private $allow_proposed_schedules;

    /**
     * @ORM\Column(name="AllowTrackChangeRequests", type="boolean")
     * @var bool
     */
    private $allow_track_change_requests;

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
     * @ORM\Column(name="SubmissionLockDownPresentationStatusDate", type="datetime")
     * @var DateTime
     */
    private $submission_lock_down_presentation_status_date;

    /**
     * @ORM\Column(name="VotingBeginDate", type="datetime")
     * @var DateTime
     */
    private $voting_begin_date;

    /**
     * @ORM\Column(name="VotingEndDate", type="datetime")
     * @var DateTime
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
     * @ORM\Column(name="PresentationCreatorNotificationEmailTemplate", type="string")
     * @var String
     */
    private $presentation_creator_notification_email_template;

    /**
     * @ORM\Column(name="PresentationModeratorNotificationEmailTemplate", type="string")
     * @var String
     */
    private $presentation_moderator_notification_email_template;

    /**
     * @ORM\Column(name="PresentationSpeakerNotificationEmailTemplate", type="string")
     * @var String
     */
    private $presentation_speaker_notification_email_template;

    /**
     * @ORM\ManyToMany(targetEntity="models\summit\PresentationCategoryGroup", fetch="EXTRA_LAZY")
     * @ORM\JoinTable(name="SelectionPlan_CategoryGroups",
     *      joinColumns={@ORM\JoinColumn(name="SelectionPlanID", referencedColumnName="ID")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="PresentationCategoryGroupID", referencedColumnName="ID")}
     *      )
     * @var PresentationCategoryGroup[]
     */
    private $category_groups;

    /**
     * @ORM\ManyToMany(targetEntity="models\summit\SummitEventType", fetch="EXTRA_LAZY")
     * @ORM\JoinTable(name="SelectionPlan_SummitEventTypes",
     *      joinColumns={@ORM\JoinColumn(name="SelectionPlanID", referencedColumnName="ID")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="SummitEventTypeID", referencedColumnName="ID")}
     *      )
     * @var SummitEventType[]
     */
    private $event_types;

    /**
     * @ORM\OneToMany(targetEntity="App\Models\Foundation\Summit\SelectionPlanAllowedMember", mappedBy="selection_plan", cascade={"persist","remove"}, orphanRemoval=true)
     * @var SelectionPlanAllowedMember[]
     */
    private $allowed_members;

    /**
     * @ORM\OneToMany(targetEntity="models\summit\Presentation", mappedBy="selection_plan", cascade={"persist"}, fetch="EXTRA_LAZY")
     * @var Presentation[]
     */
    private $presentations;

    /**
     * @ORM\OneToMany(targetEntity="App\Models\Foundation\Summit\Events\Presentations\TrackChairs\PresentationTrackChairRatingType", mappedBy="selection_plan", cascade={"persist","remove"}, orphanRemoval=true)
     * @var PresentationTrackChairRatingType
     */
    private $track_chair_rating_types;

    /**
     * @ORM\OneToMany(targetEntity="models\summit\AllowedPresentationActionType", mappedBy="selection_plan", cascade={"persist"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @var AllowedPresentationActionType[]
     */
    private $allowed_presentation_action_types;

    /**
     * @ORM\OneToMany(targetEntity="App\Models\Foundation\Summit\ExtraQuestions\AssignedSelectionPlanExtraQuestionType", mappedBy="selection_plan",cascade={"persist","remove"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @var AssignedSelectionPlanExtraQuestionType[]
     */
    private $extra_questions;

    /**
     * @ORM\OneToMany(targetEntity="models\summit\SummitSelectedPresentationList", mappedBy="selection_plan", cascade={"persist","remove"}, orphanRemoval=true)
     * @var SummitSelectedPresentationList[]
     */
    private $selection_lists;

    /**
     * @ORM\OneToMany(targetEntity="App\Models\Foundation\Summit\SelectionPlanAllowedPresentationQuestion", mappedBy="selection_plan", cascade={"persist","remove"}, orphanRemoval=true)
     * @var SelectionPlanAllowedPresentationQuestion[]
     */
    private $allowed_presentation_questions;

    /**
     * @ORM\OneToMany(targetEntity="App\Models\Foundation\Summit\SelectionPlanAllowedEditablePresentationQuestion", mappedBy="selection_plan", cascade={"persist","remove"}, orphanRemoval=true)
     * @var SelectionPlanAllowedEditablePresentationQuestion[]
     */
    private $allowed_editable_presentation_questions;

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
    public function setSubmissionBeginDate(DateTime $submission_begin_date)
    {
        $this->submission_begin_date = $this->convertDateFromTimeZone2UTC($submission_begin_date);
    }

    /**
     * @return $this
     */
    public function clearSubmissionDates()
    {
        $this->submission_begin_date = $this->submission_end_date = null;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getSubmissionEndDate()
    {
        return $this->submission_end_date;
    }

    public function getSubmissionEndDateLocal(): ?DateTime
    {
        return $this->convertDateFromUTC2TimeZone($this->submission_end_date);
    }

    public function getSubmissionBeginDateLocal(): ?DateTime
    {
        return $this->convertDateFromUTC2TimeZone($this->submission_begin_date);
    }

    /**
     * @param DateTime $submission_end_date
     */
    public function setSubmissionEndDate(DateTime $submission_end_date)
    {
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
    public function setVotingBeginDate(DateTime $voting_begin_date)
    {
        $this->voting_begin_date = $this->convertDateFromTimeZone2UTC($voting_begin_date);
    }

    /**
     * @return $this
     */
    public function clearVotingDates()
    {
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
    public function setVotingEndDate(DateTime $voting_end_date)
    {
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
    public function setSelectionBeginDate(DateTime $selection_begin_date)
    {
        $this->selection_begin_date = $this->convertDateFromTimeZone2UTC($selection_begin_date);
    }

    /**
     * @return $this
     */
    public function clearSelectionDates()
    {
        $this->selection_begin_date = $this->selection_end_date = null;
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
    public function setSelectionEndDate(DateTime $selection_end_date)
    {
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
     * @return bool
     */
    public function isHidden(): bool
    {
        return $this->is_hidden;
    }

    /**
     * @param bool $is_hidden
     */
    public function setIsHidden(bool $is_hidden)
    {
        $this->is_hidden = $is_hidden;
    }

    /**
     * SelectionPlan constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->is_enabled = false;
        $this->is_hidden = false;
        $this->allow_new_presentations = true;
        $this->allow_proposed_schedules = true;
        $this->allow_track_change_requests = true;
        $this->category_groups = new ArrayCollection;
        $this->presentations = new ArrayCollection;
        $this->extra_questions = new ArrayCollection;
        $this->event_types = new ArrayCollection;
        $this->max_submission_allowed_per_user = Summit::DefaultMaxSubmissionAllowedPerUser;
        $this->submission_period_disclaimer = null;
        $this->selection_lists = new ArrayCollection;
        $this->track_chair_rating_types = new ArrayCollection();
        $this->submission_lock_down_presentation_status_date = null;
        $this->allowed_presentation_action_types = new ArrayCollection();
        $this->allowed_members = new ArrayCollection();
        $this->allowed_presentation_questions = new ArrayCollection();
        $this->allowed_editable_presentation_questions = new ArrayCollection();
        $this->seedAllowedPresentationQuestions();
        $this->seedAllowedEditablePresentationQuestions();
    }

    /**
     * @return AllowedPresentationActionType[]
     */
    public function getAllowedPresentationActionTypes()
    {
        return $this->allowed_presentation_action_types;
    }

    /**
     * @return SelectionPlanAllowedPresentationQuestion[]
     */
    public function getAllowedPresentationQuestions()
    {
        return $this->allowed_presentation_questions;
    }

    public function clearAllAllowedPresentationQuestions()
    {
        $this->allowed_presentation_questions->clear();
    }

    /**
     * @return SelectionPlanAllowedPresentationQuestion[]
     */
    public function getAllowedEditablePresentationQuestions()
    {
        return $this->allowed_editable_presentation_questions;
    }

    public function clearAllAllowedEditablePresentationQuestions()
    {
        $this->allowed_editable_presentation_questions->clear();
    }

    /**
     * @param string $type
     * @throws ValidationException
     */
    public function addPresentationAllowedQuestion(string $type): void
    {

        if (!Presentation::isAllowedField(trim($type)))
            throw new ValidationException(sprintf("Presentation question %s is not allowed.", $type));

        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('type', trim($type)));
        if ($this->allowed_presentation_questions->matching($criteria)->count() > 0)
            throw new ValidationException(sprintf("Presentation Question %s is already allowed.", $type));

        $question = new SelectionPlanAllowedPresentationQuestion($this, $type);

        $this->allowed_presentation_questions->add($question);
    }

    /**
     * @param string $type
     * @return bool
     * @throws ValidationException
     */
    public function isAllowedPresentationQuestion(string $type): bool
    {

        if (!Presentation::isAllowedField(trim($type)))
            throw new ValidationException(sprintf("Presentation question %s is not allowed.", $type));

        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('type', trim($type)));
        return $this->allowed_presentation_questions->matching($criteria)->count() > 0;
    }

    /**
     * @param string $type
     * @throws ValidationException
     */
    public function addPresentationAllowedEditableQuestion(string $type): void
    {

        if (!Presentation::isAllowedEditableField(trim($type)))
            throw new ValidationException(sprintf("Presentation question %s is not edit allowed.", $type));

        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('type', trim($type)));
        if ($this->allowed_editable_presentation_questions->matching($criteria)->count() > 0)
            throw new ValidationException(sprintf("Presentation Question %s is already allowed to edit.", $type));

        $question = new SelectionPlanAllowedEditablePresentationQuestion($this, $type);

        $this->allowed_editable_presentation_questions->add($question);
    }

    /**
     * @param string $type
     * @return bool
     * @throws ValidationException
     */
    public function isAllowedEditablePresentationQuestion(string $type): bool
    {

        if (!Presentation::isAllowedEditableField(trim($type)))
            throw new ValidationException(sprintf("Presentation question %s is not edit allowed.", $type));

        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('type', trim($type)));
        return $this->allowed_editable_presentation_questions->matching($criteria)->count() > 0;
    }


    /**
     * @return PresentationCategoryGroup[]
     */
    public function getCategoryGroups()
    {
        return $this->category_groups;
    }

    /**
     * @return ArrayCollection|SummitEventType[]
     */
    public function getEventTypes()
    {
        return $this->event_types;
    }

    /**
     * @return ArrayCollection|SelectionPlanAllowedMember[]
     */
    public function getAllowedMembers()
    {
        return $this->allowed_members;
    }

    /**
     * @param PresentationCategoryGroup $track_group
     */
    public function addTrackGroup(PresentationCategoryGroup $track_group)
    {
        if ($this->category_groups->contains($track_group)) return;
        $this->category_groups->add($track_group);
    }

    /**
     * @param PresentationCategoryGroup $track_group
     */
    public function removeTrackGroup(PresentationCategoryGroup $track_group)
    {
        if (!$this->category_groups->contains($track_group)) return;
        $this->category_groups->removeElement($track_group);
    }

    public function addEventType(SummitEventType $eventType)
    {
        if ($this->event_types->contains($eventType)) return;
        $this->event_types->add($eventType);
    }

    public function removeEventType(SummitEventType $eventType)
    {
        if (!$this->event_types->contains($eventType)) return;
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
    public function addPresentation(Presentation $presentation)
    {
        if ($this->presentations->contains($presentation)) return;
        $this->presentations->add($presentation);
        $presentation->setSelectionPlan($this);
    }

    public function getStageStatus($stage)
    {

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
        $now = new \DateTime('now', new \DateTimeZone('UTC'));

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
    public function hasTrack(PresentationCategory $track)
    {
        foreach ($this->category_groups as $track_group) {
            if ($track_group->hasCategory($track->getIdentifier())) return true;
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
     * @return bool
     */
    public function isAllowProposedSchedules(): bool
    {
        return $this->allow_proposed_schedules;
    }

    /**
     * @param bool $allow_proposed_schedules
     */
    public function setAllowProposedSchedules(bool $allow_proposed_schedules): void
    {
        $this->allow_proposed_schedules = $allow_proposed_schedules;
    }

    /**
     * @return bool
     */
    public function isAllowTrackChangeRequests(): bool
    {
        return $this->allow_track_change_requests;
    }

    /**
     * @param bool $allow_track_change_requests
     */
    public function setAllowTrackChangeRequests(bool $allow_track_change_requests): void
    {
        $this->allow_track_change_requests = $allow_track_change_requests;
    }

    /**
     * @param int $id
     * @return Presentation|null
     */
    public function getPresentation(int $id): ?Presentation
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', $id));
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
    public function getExtraQuestionById(int $question_id): ?SummitSelectionPlanExtraQuestionType
    {
        try {
            $query = $this->createQuery(
                "SELECT aq from App\Models\Foundation\Summit\ExtraQuestions\AssignedSelectionPlanExtraQuestionType aq 
        JOIN aq.question_type q 
        JOIN aq.selection_plan sp    
        WHERE sp.id = :selection_plan_id and q.id = :question_id
        ");

            $res = $query
                ->setParameter('selection_plan_id', $this->getIdentifier())
                ->setParameter('question_id', $question_id)
                ->getSingleResult();
            return $res->getQuestionType();
        } catch (NoResultException $ex) {

        }
        return null;
    }

    /**
     * @param string $name
     * @return SummitSelectionPlanExtraQuestionType|null
     */
    public function getExtraQuestionByName(string $name): ?SummitSelectionPlanExtraQuestionType
    {
        try {
            $query = $this->createQuery(
                "SELECT aq from App\Models\Foundation\Summit\ExtraQuestions\AssignedSelectionPlanExtraQuestionType aq 
        JOIN aq.question_type q 
        JOIN aq.selection_plan sp    
        WHERE sp.id = :selection_plan_id and q.name = :question_name
        ");

            $res = $query
                ->setParameter('selection_plan_id', $this->getIdentifier())
                ->setParameter('question_name', trim($name))
                ->getSingleResult();
            return $res->getQuestionType();
        } catch (NoResultException $ex) {

        }
        return null;
    }

    /**
     * @param string $label
     * @return SummitSelectionPlanExtraQuestionType|null
     */
    public function getExtraQuestionByLabel(string $label): ?SummitSelectionPlanExtraQuestionType
    {
        try {
            $query = $this->createQuery(
                "SELECT aq from App\Models\Foundation\Summit\ExtraQuestions\AssignedSelectionPlanExtraQuestionType aq 
        JOIN aq.question_type q 
        JOIN aq.selection_plan sp    
        WHERE sp.id = :selection_plan_id and q.label = :question_label
        ");

            $res = $query
                ->setParameter('selection_plan_id', $this->getIdentifier())
                ->setParameter('question_label', trim($label))
                ->getSingleResult();
            return $res->getQuestionType();
        } catch (NoResultException $ex) {

        }
        return null;
    }

    /**
     * @return int
     */
    private function getExtraQuestionMaxOrder(): int
    {
        $criteria = Criteria::create();
        $criteria->orderBy(['order' => 'DESC']);
        $res = $this->extra_questions->matching($criteria)->first();
        return $res === false ? 0 : $res->getOrder();
    }

    /**
     * @param SummitSelectionPlanExtraQuestionType $question
     * @return AssignedSelectionPlanExtraQuestionType|null
     */
    public function addExtraQuestion(SummitSelectionPlanExtraQuestionType $question): ?AssignedSelectionPlanExtraQuestionType
    {

        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('question_type', $question));
        $former_assignment = $this->extra_questions->matching($criteria)->first();
        if ($former_assignment !== false) return null;

        $assignment = new AssignedSelectionPlanExtraQuestionType();
        $assignment->setOrder($this->getExtraQuestionMaxOrder() + 1);
        $assignment->setQuestionType($question);
        $assignment->setSelectionPlan($this);
        $this->extra_questions->add($assignment);

        return $assignment;
    }

    public function removeExtraQuestion(SummitSelectionPlanExtraQuestionType $question): void
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('question_type', $question));
        $former_assignment = $this->extra_questions->matching($criteria)->first();
        if ($former_assignment === false) return;

        $this->extra_questions->removeElement($former_assignment);
        self::resetOrderForSelectable($this->extra_questions);
        $former_assignment->clearSelectionPlan();
    }

    use OrderableChilds;

    /**
     * @param SummitSelectionPlanExtraQuestionType $question
     * @param int $new_order
     * @throws ValidationException
     */
    public function recalculateQuestionOrder(SummitSelectionPlanExtraQuestionType $question, int $new_order): void
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('question_type', $question));
        $assignment = $this->extra_questions->matching($criteria)->first();
        if ($assignment === false) return;
        self::recalculateOrderForSelectable($this->extra_questions, $assignment, $new_order);
    }

    /**
     * @return ArrayCollection|\Doctrine\Common\Collections\Collection|AssignedSelectionPlanExtraQuestionType[]
     */
    public function getExtraQuestions()
    {
        $criteria = Criteria::create();
        $criteria->orderBy(['order' => 'ASC']);
        return $this->extra_questions->matching($criteria);
    }

    /**
     * @return ArrayCollection|\Doctrine\Common\Collections\Collection
     */
    public function getMandatoryExtraQuestions()
    {
        $query = $this->createQuery(
            "SELECT aq from App\Models\Foundation\Summit\ExtraQuestions\AssignedSelectionPlanExtraQuestionType aq 
        JOIN aq.question_type q 
        JOIN aq.selection_plan sp    
        WHERE sp.id = :selection_plan_id and q.mandatory = :mandatory
        order by aq.order ASC");

        return $query
            ->setParameter('selection_plan_id', $this->getIdentifier())
            ->setParameter('mandatory', true)
            ->getResult();
    }

    /**
     * @return int
     */
    public function getSubmissionLimitFor(): int
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
    public function hasEventType(SummitEventType $type): bool
    {
        return $this->getEventTypeById($type->getId()) != null;
    }

    /**
     * @param int $eventTypeId
     * @return SummitEventType|null
     */
    public function getEventTypeById(int $eventTypeId): ?SummitEventType
    {
        //$criteria = Criteria::create();
        //$criteria->where(Criteria::expr()->eq('id', intval($eventTypeId)));
        $event_type = $this->event_types->filter(function ($e) use ($eventTypeId) {
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

    /*
     * @return String | null
     */
    public function getPresentationCreatorNotificationEmailTemplate(): string
    {
        return $this->presentation_creator_notification_email_template;
    }

    /**
     * @param string $presentation_creator_notification_email_template
     */
    public function setPresentationCreatorNotificationEmailTemplate(string $presentation_creator_notification_email_template): void
    {
        $this->presentation_creator_notification_email_template = $presentation_creator_notification_email_template;
    }

    /*
     * @return String | null
     */
    public function getPresentationModeratorNotificationEmailTemplate(): string
    {
        return $this->presentation_moderator_notification_email_template;
    }

    /**
     * @param string $presentation_moderator_notification_email_template
     */
    public function setPresentationModeratorNotificationEmailTemplate(string $presentation_moderator_notification_email_template): void
    {
        $this->presentation_moderator_notification_email_template = $presentation_moderator_notification_email_template;
    }

    /*
     * @return String | null
     */
    public function getPresentationSpeakerNotificationEmailTemplate(): string
    {
        return $this->presentation_speaker_notification_email_template;
    }

    /**
     * @param string $presentation_speaker_notification_email_template
     */
    public function setPresentationSpeakerNotificationEmailTemplate(string $presentation_speaker_notification_email_template): void
    {
        $this->presentation_speaker_notification_email_template = $presentation_speaker_notification_email_template;
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
    public function addSelectionList(SummitSelectedPresentationList $selection_list)
    {
        if ($this->selection_lists->contains($selection_list)) return;
        $this->selection_lists->add($selection_list);
        $selection_list->setSelectionPlan($this);
    }

    /**
     * @param SummitSelectedPresentationList $selection_list
     */
    public function removeSelectionList(SummitSelectedPresentationList $selection_list)
    {
        if (!$this->selection_lists->contains($selection_list)) return;
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
        string               $list_type,
        ?Member              $owner = null
    ): ?SummitSelectedPresentationList
    {
        if (!in_array($list_type, SummitSelectedPresentationList::ValidListTypes))
            throw new ValidationException(sprintf("List Type %s is not valid.", $list_type));

        $criteria = Criteria::create();
        $criteria->andWhere(Criteria::expr()->eq('list_type', $list_type));
        $criteria->andWhere(Criteria::expr()->eq('category', $track));

        if ($list_type == SummitSelectedPresentationList::Individual) {
            $criteria->andWhere(Criteria::expr()->eq('owner', $owner));
        }

        $list = $this->selection_lists->matching($criteria)->first();
        return $list === false ? null : $list;
    }

    /**
     * @param int $list_id
     * @return SummitSelectedPresentationList|null
     */
    public function getSelectionListById(int $list_id): ?SummitSelectedPresentationList
    {
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
    public function createTeamSelectionList(PresentationCategory $track): SummitSelectedPresentationList
    {
        $team_selection_list = $this->getSelectionListByTrackAndTypeAndOwner($track, SummitSelectedPresentationList::Group);
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
    public function createIndividualSelectionList(PresentationCategory $track, Member $member): SummitSelectedPresentationList
    {
        if (!is_null($member)) {
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

    /**
     * @return ?PresentationTrackChairRatingType
     */
    public function getTrackChairRatingTypeById(int $id): ?PresentationTrackChairRatingType
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', $id));
        $res = $this->track_chair_rating_types->matching($criteria)->first();
        return !$res ? null : $res;
    }

    /**
     * @return ?PresentationTrackChairRatingType
     */
    public function getTrackChairRatingTypeByName(string $name): ?PresentationTrackChairRatingType
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('name', trim($name)));
        $res = $this->track_chair_rating_types->matching($criteria)->first();
        return !$res ? null : $res;
    }

    /**
     * @return ArrayCollection|PresentationTrackChairRatingType[]
     */
    public function getTrackChairRatingTypes()
    {
        $criteria = Criteria::create();
        $criteria->orderBy(['order' => 'ASC']);
        return $this->track_chair_rating_types->matching($criteria);
    }

    /**
     * @return int
     */
    private function getTrackChairRatingTypeMaxOrder(): int
    {
        $criteria = Criteria::create();
        $criteria->orderBy(['order' => 'DESC']);
        $rating = $this->track_chair_rating_types->matching($criteria)->first();
        return $rating === false ? 0 : $rating->getOrder();
    }

    public function addTrackChairRatingType(PresentationTrackChairRatingType $ratingType): void
    {
        if ($this->track_chair_rating_types->contains($ratingType)) return;
        $ratingType->setOrder($this->getTrackChairRatingTypeMaxOrder() + 1);
        $ratingType->setSelectionPlan($this);
        $this->track_chair_rating_types->add($ratingType);
    }

    public function removeTrackChairRatingType(PresentationTrackChairRatingType $ratingType): void
    {
        if (!$this->track_chair_rating_types->contains($ratingType)) return;
        $this->track_chair_rating_types->removeElement($ratingType);
        $ratingType->clearSelectionPlan();
        self::resetOrderForSelectable($this->track_chair_rating_types);
    }

    public function clearTrackChairRatingType(): void
    {
        $this->track_chair_rating_types->clear();
    }

    /**
     * @param PresentationTrackChairRatingType $ratingType
     * @param $new_order
     * @throws ValidationException
     */
    public function recalculateTrackChairRatingTypeOrder(PresentationTrackChairRatingType $ratingType, $new_order)
    {
        self::recalculateOrderForSelectable($this->track_chair_rating_types, $ratingType, $new_order);
    }

    /**
     * @return DateTime
     */
    public function getSubmissionLockDownPresentationStatusDate(): ?DateTime
    {
        return $this->submission_lock_down_presentation_status_date;
    }

    /**
     * @param DateTime $submission_lock_down_presentation_status_date
     */
    public function setSubmissionLockDownPresentationStatusDate(?DateTime $submission_lock_down_presentation_status_date): void
    {
        $this->submission_lock_down_presentation_status_date = $this->convertDateFromTimeZone2UTC($submission_lock_down_presentation_status_date);
    }

    public function clearSubmissionLockDownPresentationStatusDate(): void
    {
        $this->submission_lock_down_presentation_status_date = null;
    }

    /**
     * @param SummitSelectionPlanExtraQuestionType $question
     * @return bool
     */
    public function isExtraQuestionAssigned(SummitSelectionPlanExtraQuestionType $question): bool
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('question_type', $question));
        return $this->extra_questions->matching($criteria)->count() > 0;
    }

    /**
     * @param SummitSelectionPlanExtraQuestionType $question
     * @return AssignedSelectionPlanExtraQuestionType|null
     */
    public function getAssignedExtraQuestion(SummitSelectionPlanExtraQuestionType $question): ?AssignedSelectionPlanExtraQuestionType
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('question_type', $question));
        $res = $this->extra_questions->matching($criteria)->first();
        return $res === false ? null : $res;
    }

    /**
     * @return ArrayCollection
     */
    public function getPresentationActionTypes(): ArrayCollection
    {
        return $this->allowed_presentation_action_types->map(function ($entity) {
            return $entity->getType();
        });
    }

    /**
     * @param PresentationActionType $type
     * @return PresentationActionType|null
     */
    public function getPresentationActionType(PresentationActionType $type): ?PresentationActionType
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('type', $type));
        $presentation_action_type_assignment = $this->allowed_presentation_action_types->matching($criteria)->first();
        if ($presentation_action_type_assignment === false ||
            !$presentation_action_type_assignment instanceof AllowedPresentationActionType) return null;
        return $presentation_action_type_assignment->getType();
    }

    /**
     * @param int $type_id
     * @return PresentationActionType|null
     */
    public function getPresentationActionTypeById(int $type_id): ?PresentationActionType
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('type_id', $type_id));
        $presentation_action_type_assignment = $this->allowed_presentation_action_types->matching($criteria)->first();
        if ($presentation_action_type_assignment === false ||
            !$presentation_action_type_assignment instanceof AllowedPresentationActionType) return null;
        return $presentation_action_type_assignment->getType();
    }

    /**
     * @param PresentationActionType $presentation_action_type
     * @return int
     */
    public function getPresentationActionTypeOrder(PresentationActionType $presentation_action_type): int
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('type', $presentation_action_type));
        $res = $this->allowed_presentation_action_types->matching($criteria)->first();
        return $res === false ? 0 : $res->getOrder();
    }

    /**
     * @return int
     */
    public function getPresentationActionTypesMaxOrder(): int
    {
        $criteria = Criteria::create();
        $criteria->orderBy(['order' => 'DESC']);
        $res = $this->allowed_presentation_action_types->matching($criteria)->first();
        return $res === false ? 0 : $res->getOrder();
    }

    /**
     * @param PresentationActionType $presentation_action_type
     */
    public function addPresentationActionType(PresentationActionType $presentation_action_type): void
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('type', $presentation_action_type));
        if ($this->allowed_presentation_action_types->matching($criteria)->count() > 0) return;
        $order = $this->getPresentationActionTypesMaxOrder();
        $allowed_presentation_action_type = new AllowedPresentationActionType($presentation_action_type, $this, $order + 1);
        $this->allowed_presentation_action_types->add($allowed_presentation_action_type);
    }

    /**
     * @param PresentationActionType $presentation_action_type
     */
    public function removePresentationActionType(PresentationActionType $presentation_action_type): void
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('type', $presentation_action_type));
        $presentation_action_type_assignment = $this->allowed_presentation_action_types->matching($criteria)->first();
        if ($presentation_action_type_assignment === false) return;
        $this->allowed_presentation_action_types->removeElement($presentation_action_type_assignment);
        self::resetOrderForSelectable($this->allowed_presentation_action_types, AllowedPresentationActionType::class);
    }

    /**
     * @param PresentationActionType $presentation_action_type
     * @param int $new_order
     * @throws ValidationException
     */
    public function recalculatePresentationActionTypeOrder(PresentationActionType $presentation_action_type, int $new_order): void
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('type', $presentation_action_type));
        $selection_plan_assignment = $this->allowed_presentation_action_types->matching($criteria)->first();
        if ($selection_plan_assignment === false) return;
        self::recalculateOrderForSelectable(
            $this->allowed_presentation_action_types, $selection_plan_assignment, $new_order, AllowedPresentationActionType::class);
    }

    public function clearPresentationActionTypes()
    {
        $this->allowed_presentation_action_types->clear();
    }

    /**
     * @param PresentationActionType $presentation_action_type
     * @return bool
     */
    public function isAllowedPresentationActionType(PresentationActionType $presentation_action_type): bool
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('type', $presentation_action_type));
        return $this->allowed_presentation_action_types->matching($criteria)->count() > 0;
    }

    /**
     * @param string $email
     * @return SelectionPlanAllowedMember|null
     * @throws ValidationException
     */
    public function addAllowedMember(string $email): ?SelectionPlanAllowedMember
    {
        if ($this->is_hidden)
            throw new ValidationException
            (
                sprintf
                (
                    "Members cannot be added to selection plan %s because it's hidden.",
                    $this->id
                )
            );

        if (empty($email)) return null;
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('email', trim($email)));
        if ($this->allowed_members->matching($criteria)->count() > 0)
            throw new ValidationException
            (
                sprintf
                (
                    "Email %s already allowed for selection plan %s.",
                    $email,
                    $this->id
                )
            );

        $allowed_member = new SelectionPlanAllowedMember($this, trim($email));
        $this->allowed_members->add($allowed_member);
        return $allowed_member;
    }


    /**
     * @param int $id
     * @return SelectionPlanAllowedMember|null
     */
    public function getAllowedMemberById(int $id): ?SelectionPlanAllowedMember
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', $id));
        $res = $this->allowed_members->matching($criteria)->first();
        return !$res ? null : $res;
    }

    /**
     * @param SelectionPlanAllowedMember $allowedMember
     */
    public function removeAllowedMember(SelectionPlanAllowedMember $allowedMember): void
    {
        if (!$this->allowed_members->contains($allowedMember)) return;
        $this->allowed_members->removeElement($allowedMember);
    }

    /**
     * @param string $email
     * @return bool
     */
    public function isAllowedMember(string $email): bool
    {
        if ($this->getType() === self::PublicType || $this->getType() === self::HiddenType) return true;
        return $this->containsMember($email);
    }

    /**
     * @param string $email
     * @return bool
     */
    public function containsMember(string $email): bool
    {
        return $this->allowed_members->filter(function($element) use($email){
            return $element->getEmail() === strtolower(trim($email));
        })->count() > 0;
    }

    /**
     * @param string $email
     */
    public function removeAllowedMemberByEmail(string $email): void
    {
        $res = $this->allowed_members->filter(function($element) use($email){
            return $element->getEmail() === strtolower(trim($email));
        })->first();

        if (!$res) return;
        $this->allowed_members->removeElement($res);
    }

    const PublicType = 'Public';
    const PrivateType = 'Private';
    const HiddenType = 'Hidden';

    /**
     * @return string
     */
    public function getType(): string
    {
        if ($this->is_hidden) return self::HiddenType;
        return $this->allowed_members->count() > 0 ? self::PrivateType : self::PublicType;
    }

    public function isPrivate(): bool{
        return $this->getType() === self::PrivateType;
    }

    /**
     * @param array $payload
     * @throws ValidationException
     */
    public function checkPresentationAllowedQuestions(array $payload): void
    {
        $allowed_fields = Presentation::getAllowedFields();
        foreach ($allowed_fields as $field) {
            Log::debug(sprintf("Selection Plan %s checking Presentation Field %s", $this->id, $field));

            if (isset($payload[$field]) && !$this->isAllowedPresentationQuestion($field)) {
                throw new ValidationException(sprintf("Field %s is not allowed on Selection Plan %s", $field, $this->name));
            }
        }
    }

    /**
     * @param array $payload
     * @return array
     * @throws ValidationException
     */
    public function curatePayloadByPresentationAllowedQuestions(array $payload): array
    {
        $allowed_fields = Presentation::getAllowedFields();
        foreach ($allowed_fields as $field) {
            Log::debug(sprintf("Selection Plan %s checking Presentation Field %s", $this->id, $field));

            if (isset($payload[$field]) && !$this->isAllowedPresentationQuestion($field)) {
                unset($payload[$field]);
            }
        }
        return $payload;
    }

    /**
     * @param $field1
     * @param $field2
     * @return bool
     */
    private static function areFieldsEqual($field1, $field2): bool
    {
        if (is_array($field1) && is_array($field2)) {
            if (count($field1) != count($field2)) return false;
            return array_diff($field1, $field2) === array_diff($field2, $field1);
        }
        return html_entity_decode($field1) == html_entity_decode($field1);
    }

    /**
     * @param array $payload
     * @param array $former_data
     * @throws ValidationException
     */
    public function checkPresentationAllowedEdtiableQuestions(array $payload, array $former_data): void
    {
        $allowed_fields = Presentation::getAllowedEditableFields();

        Log::debug
        (
            sprintf
            (
                "SelectionPlan::checkPresentationAllowedEdtiableQuestions payload %s former_data %s",
                json_encode($payload),
                json_encode($former_data)
            )
        );

        foreach ($allowed_fields as $field) {
            Log::debug(sprintf("SelectionPlan::checkPresentationAllowedEdtiableQuestions Selection Plan %s checking Presentation Field %s if its editable...", $this->id, $field));
            if (isset($payload[$field]) && isset($former_data[$field]) && !self::areFieldsEqual($payload[$field], $former_data[$field]) && !$this->isAllowedEditablePresentationQuestion($field)) {
                Log::debug(sprintf("SelectionPlan::checkPresentationAllowedEdtiableQuestions current field %s ( %s ).",$field, json_encode($payload[$field])));
                Log::debug(sprintf("SelectionPlan::checkPresentationAllowedEdtiableQuestions former field %s ( %s ).", $field, json_encode($former_data[$field])));
                throw new ValidationException(sprintf("Field %s is not allowed for edition on Selection Plan %s.", $field, $this->name));
            }
        }
    }

    /**
     * @throws ValidationException
     */
    public function seedAllowedPresentationQuestions(): void
    {
        foreach (Presentation::getAllowedFields() as $allowedField) {
            $this->addPresentationAllowedQuestion($allowedField);
        }
    }

    /**
     * @throws ValidationException
     */
    public function seedAllowedEditablePresentationQuestions(): void
    {
        foreach (Presentation::getAllowedEditableFields() as $allowedField) {
            $this->addPresentationAllowedEditableQuestion($allowedField);
        }
    }
}