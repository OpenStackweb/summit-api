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

use App\Models\Foundation\ExtraQuestions\ExtraQuestionAnswer;
use App\Models\Foundation\ExtraQuestions\ExtraQuestionType;
use App\Models\Foundation\Main\ExtraQuestions\ExtraQuestionAnswerHolder;
use App\Models\Foundation\Summit\Events\Presentations\TrackChairs\PresentationTrackChairScore;
use App\Models\Foundation\Summit\ExtraQuestions\SummitSelectionPlanExtraQuestionType;
use App\Models\Foundation\Main\OrderableChilds;
use App\Models\Utils\IStorageTypesConstants;
use App\Services\Filesystem\FileUploadStrategyFactory;
use Behat\Transliterator\Transliterator;
use App\Models\Foundation\Summit\Events\Presentations\TrackQuestions\TrackAnswer;
use App\Models\Foundation\Summit\SelectionPlan;
use Carbon\Carbon;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\ArrayCollection;
use App\Models\Foundation\Summit\Events\Presentations\TrackQuestions\TrackQuestionTemplate;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use models\exceptions\ValidationException;
use models\main\Member;
use Doctrine\ORM\Mapping AS ORM;
use models\utils\SilverstripeBaseModel;

/**
 * Class Presentation
 * @ORM\Entity
 * @ORM\Table(name="Presentation")
 * @ORM\HasLifecycleCallbacks
 * @package models\summit
 */
class Presentation extends SummitEvent
{
    use ExtraQuestionAnswerHolder;

    const ClassName = 'Presentation';

    /**
     * SELECTION STATUS (TRACK CHAIRS LIST)
     */
    const SelectionStatus_Accepted = 'accepted';
    const SelectionStatus_Unaccepted = 'unaccepted';
    const SelectionStatus_Alternate = 'alternate';

    /**
     * Defines the phase that a presentation has been created, but
     * no information has been saved to it.
     */
    const PHASE_NEW = 0;

    /**
     * Defines the phase where a presentation has been given a summary,
     * but no speakers have been added
     */
    const PHASE_SUMMARY = 1;

    /**
     * defines a phase where a presentation has a UPLOADS
     */
    const PHASE_UPLOADS = 2;

    /**
     * defines a phase where a presentation has a tags
     */
    const PHASE_TAGS = 3;

    /**
     * defines a phase where a presentation has a summary and speakers
     */
    const PHASE_SPEAKERS = 4;

    /**
     * Defines a phase where a presentation has been submitted successfully
     */
    const PHASE_COMPLETE = 5;

    /**
     *
     */
    const STATUS_RECEIVED = 'Received';

    const ClassNamePresentation = 'Presentation';

    const MaxAllowedLinks = 5;

    /**
     * @ORM\Column(name="Slug", type="string")
     * @var string
     */
    protected $slug;

    /**
     * @ORM\Column(name="Status", type="string")
     * @var string
     */
    protected $status;

    /**
     * @ORM\Column(name="Progress", type="integer")
     * @var int
     */
    protected $progress;

    /**
     * @ORM\Column(name="ProblemAddressed", type="string")
     * @var string
     */
    protected $problem_addressed;

    /**
     * @ORM\Column(name="AttendeesExpectedLearnt", type="string")
     * @var string
     */
    protected $attendees_expected_learnt;

    /**
     * @ORM\Column(name="ToRecord", type="boolean")
     * @var bool
     */
    protected $to_record;

    /**
     * @ORM\Column(name="AttendingMedia", type="boolean")
     * @var bool
     */
    protected $attending_media;

    /**
     * @ORM\Column(name="WillAllSpeakersAttend", type="boolean")
     * @var bool
     */
    protected $will_all_speakers_attend;

    /**
     * @ORM\Column(name="DisclaimerAcceptedDate", type="datetime")
     * @var \DateTime
     */
    protected $disclaimer_accepted_date;

    /**
     * @ORM\Column(name="CustomOrder", type="integer")
     * @var integer
     */
    protected $custom_order;

    /**
     * @ORM\ManyToOne(targetEntity="PresentationSpeaker", inversedBy="moderated_presentations", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="ModeratorID", referencedColumnName="ID", onDelete="SET NULL")
     * @var PresentationSpeaker
     */
    protected $moderator;

    /**
     * @ORM\ManyToOne(targetEntity="App\Models\Foundation\Summit\SelectionPlan", inversedBy="presentations", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="SelectionPlanID", referencedColumnName="ID")
     * @var SelectionPlan
     */
    protected $selection_plan;

    /**
     * @ORM\OneToMany(targetEntity="models\summit\PresentationMaterial", mappedBy="presentation", cascade={"persist", "remove"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @var PresentationMaterial[]
     */
    protected $materials;

    /**
     * @ORM\OneToMany(targetEntity="models\summit\SummitPresentationComment", mappedBy="presentation", cascade={"persist", "remove"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @var SummitPresentationComment[]
     */
    protected $comments;

    /**
     * @ORM\ManyToMany(targetEntity="models\summit\PresentationSpeaker", inversedBy="presentations" , fetch="EXTRA_LAZY", cascade={"persist"})
     * @ORM\JoinTable(name="Presentation_Speakers",
     *  joinColumns={
     *     @ORM\JoinColumn(name="PresentationID", referencedColumnName="ID", onDelete="CASCADE")
     * },
     * inverseJoinColumns={
     *      @ORM\JoinColumn(name="PresentationSpeakerID", referencedColumnName="ID",  onDelete="CASCADE")
     *
     * }
     * )
     */
    protected $speakers;

    /**
     * @ORM\OneToMany(targetEntity="models\summit\SummitSelectedPresentation", mappedBy="presentation", cascade={"persist", "remove"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @var SummitSelectedPresentation[]|ArrayCollection
     */
    protected $selected_presentations;

    /**
     * @ORM\OneToMany(targetEntity="App\Models\Foundation\Summit\Events\Presentations\TrackQuestions\TrackAnswer", mappedBy="presentation", cascade={"persist"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @var TrackAnswer[]
     */
    protected $answers;

    /**
     * @ORM\OneToMany(targetEntity="models\summit\PresentationTrackChairView", mappedBy="presentation", cascade={"persist","remove"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @var PresentationTrackChairView[]
     */
    private $track_chair_views;

    /**
     * @ORM\OneToMany(targetEntity="models\summit\PresentationVote", mappedBy="presentation", cascade={"persist","remove"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @var PresentationVote[]
     */
    private $votes;

    /**
     * @ORM\OneToMany(targetEntity="models\summit\PresentationAttendeeVote", mappedBy="presentation", cascade={"persist","remove"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @var PresentationAttendeeVote[]
     */
    private $attendees_votes;

    /**
     * @ORM\OneToMany(targetEntity="models\summit\SummitCategoryChange", mappedBy="presentation", cascade={"persist","remove"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @var SummitCategoryChange[]
     */
    private $category_changes_requests;

    /**
     * @ORM\OneToMany(targetEntity="models\summit\PresentationAction", mappedBy="presentation", cascade={"persist", "remove"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @var PresentationAction[]
     */
    private $actions;

    /**
     * @ORM\OneToMany(targetEntity="models\summit\PresentationExtraQuestionAnswer", mappedBy="presentation", cascade={"persist","remove"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @var PresentationExtraQuestionAnswer[]
     */
    private $extra_question_answers;

    /**
     * @ORM\OneToMany(targetEntity="App\Models\Foundation\Summit\Events\Presentations\TrackChairs\PresentationTrackChairScore", mappedBy="presentation", cascade={"persist","remove"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @var PresentationTrackChairScore[]
     */
    private $track_chairs_scores;

    /**
     * @return bool
     */
    public function isToRecord()
    {
        return $this->to_record;
    }

    /**
     * @param bool $to_record
     */
    public function setToRecord($to_record)
    {
        $this->to_record = $to_record;
    }

    /**
     * @return boolean
     */
    public function getToRecord()
    {
        return $this->to_record;
    }

    /**
     * Presentation constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->progress = self::PHASE_NEW;
        $this->materials = new ArrayCollection();
        $this->speakers = new ArrayCollection();
        $this->answers = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->track_chair_views = new ArrayCollection();
        $this->votes = new ArrayCollection();
        $this->category_changes_requests = new ArrayCollection();
        $this->selected_presentations = new ArrayCollection();
        $this->actions = new ArrayCollection();
        $this->extra_question_answers = new ArrayCollection();
        $this->attendees_votes = new ArrayCollection();
        $this->to_record = false;
        $this->attending_media = false;
        $this->will_all_speakers_attend = false;
        $this->disclaimer_accepted_date = null;
        $this->custom_order = 0;
        $this->track_chairs_scores = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function getProblemAddressed()
    {
        return $this->problem_addressed;
    }

    /**
     * @param string $problem_addressed
     */
    public function setProblemAddressed($problem_addressed)
    {
        $this->problem_addressed = $problem_addressed;
    }

    /**
     * @return string
     */
    public function getAttendeesExpectedLearnt()
    {
        return $this->attendees_expected_learnt;
    }

    /**
     * @param string $attendees_expected_learnt
     */
    public function setAttendeesExpectedLearnt($attendees_expected_learnt)
    {
        $this->attendees_expected_learnt = $attendees_expected_learnt;
    }

    /**
     * @return string
     */
    public function getClassName():string
    {
        return self::ClassNamePresentation;
    }

    /**
     * @return ArrayCollection|PresentationSpeaker[]
     */
    public function getSpeakers()
    {
        return $this->speakers;
    }

    /**
     * @param PresentationSpeaker $speaker
     */
    public function addSpeaker(PresentationSpeaker $speaker)
    {
        if ($this->speakers->contains($speaker)) return;
        $this->speakers->add($speaker);
        $speaker->addPresentation($this);
    }

    public function clearSpeakers()
    {
        $this->speakers->clear();
    }

    /**
     * @return int[]
     */
    public function getSpeakerIds()
    {
        return $this->speakers->map(function ($entity) {
            return $entity->getId();
        })->toArray();
    }

    /**
     * @return PresentationVideo[]
     */
    public function getVideos()
    {
        return $this->materials->filter(function ($element) {
            return $element instanceof PresentationVideo;
        });
    }

    /**
     * @param int $material_id
     * @return PresentationMaterial|null
     */
    public function getMaterial($material_id)
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', intval($material_id)));
        $material = $this->materials->matching($criteria)->first();
        return $material === false ? null : $material;
    }

    /**
     * @param PresentationVideo $video
     * @return $this
     */
    public function addVideo(PresentationVideo $video)
    {
        $this->materials->add($video);
        $video->setPresentation($this);
        $video->setOrder($this->getMaterialsMaxOrder() + 1);
        return $this;
    }

    public function getVideosWithExternalUrls(){
        return $this->materials->filter(function ($element) {
            return $element instanceof PresentationVideo && !empty($element->getExternalUrl());
        });
    }

    /**
     * @return bool
     */
    public function hasVideos()
    {
        return count($this->getVideos()) > 0;
    }

    /**
     * @param int $video_id
     * @return PresentationVideo
     */
    public function getVideoBy($video_id)
    {
        $res = $this->materials
            ->filter(function ($element) use ($video_id) {
                return $element instanceof PresentationVideo && $element->getId() == $video_id;
            })
            ->first();
        return $res === false ? null : $res;
    }

    /**
     * @param int $slide_id
     * @return PresentationSlide
     */
    public function getSlideBy($slide_id)
    {
        $res = $this->materials
            ->filter(function ($element) use ($slide_id) {
                return $element instanceof PresentationSlide && $element->getId() == $slide_id;
            })
            ->first();
        return $res === false ? null : $res;
    }

    /**
     * @param int $link_id
     * @return PresentationLink
     */
    public function getLinkBy($link_id)
    {
        $res = $this->materials
            ->filter(function ($element) use ($link_id) {
                return $element instanceof PresentationLink && $element->getId() == $link_id;
            })
            ->first();
        return $res === false ? null : $res;
    }

    /**
     * @param int $mediaUploadId
     * @return PresentationMediaUpload
     */
    public function getMediaUploadBy($mediaUploadId)
    {
        $res = $this->materials
            ->filter(function ($element) use ($mediaUploadId) {
                return $element instanceof PresentationMediaUpload && $element->getId() == $mediaUploadId;
            })
            ->first();
        return $res === false ? null : $res;
    }


    /**
     * @param PresentationVideo $video
     */
    public function removeVideo(PresentationVideo $video)
    {
        $this->materials->removeElement($video);
        $video->unsetPresentation();
    }

    /**
     * @param PresentationSlide $slide
     */
    public function removeSlide(PresentationSlide $slide)
    {
        $this->materials->removeElement($slide);
        $slide->unsetPresentation();
    }

    /**
     * @param PresentationLink $link
     */
    public function removeLink(PresentationLink $link)
    {
        $this->materials->removeElement($link);
        $link->unsetPresentation();
    }

    /**
     * @param PresentationMediaUpload $mediaUpload
     */
    public function removeMediaUpload(PresentationMediaUpload $mediaUpload)
    {
        $this->materials->removeElement($mediaUpload);
        $mediaUpload->unsetPresentation();
    }

    /**
     * @param PresentationSpeaker $speaker
     */
    public function removeSpeaker(PresentationSpeaker $speaker)
    {
        if (!$this->speakers->contains($speaker)) return;
        $this->speakers->removeElement($speaker);
    }

    /**
     * @param PresentationSpeaker $speaker
     * @return bool
     */
    public function isSpeaker(PresentationSpeaker $speaker)
    {
        return $this->speakers->contains($speaker);
    }

    /**
     * @return PresentationSlide[]
     */
    public function getSlides()
    {
        return $this->materials->filter(function ($element) {
            return $element instanceof PresentationSlide;
        });
    }

    /**
     * @param PresentationSlide $slide
     * @return $this
     */
    public function addSlide(PresentationSlide $slide)
    {
        $this->materials->add($slide);
        $slide->setPresentation($this);
        $slide->setOrder($this->getMaterialsMaxOrder() + 1);
        return $this;
    }

    /**
     * @return ArrayCollection|PresentationMediaUpload[]
     */
    public function getMediaUploads()
    {
        return $this->materials->filter(function ($element) {
            return $element instanceof PresentationMediaUpload;
        });
    }

    /**
     * @param SummitMediaUploadType $media_upload_type
     * @return int
     */
    public function getMediaUploadsCountByType(SummitMediaUploadType $media_upload_type): int
    {
        $res = $this->materials->filter(function ($element) use ($media_upload_type) {
            return $element instanceof PresentationMediaUpload && $element->getMediaUploadTypeId() == $media_upload_type->getId();
        });
        return $res->count();
    }

    /**
     * @param PresentationMediaUpload $mediaUpload
     * @return $this
     */
    public function addMediaUpload(PresentationMediaUpload $mediaUpload)
    {
        $this->materials->add($mediaUpload);
        $mediaUpload->setPresentation($this);
        $mediaUpload->setOrder($this->getMaterialsMaxOrder() + 1);
        return $this;
    }

    /**
     * @return int
     */
    protected function getMaterialsMaxOrder()
    {
        $criteria = Criteria::create();
        $criteria->orderBy(['order' => 'DESC']);
        $material = $this->materials->matching($criteria)->first();
        return $material === false ? 0 : $material->getOrder();
    }

    /**
     * @return bool
     */
    public function hasSlides()
    {
        return count($this->getSlides()) > 0;
    }

    /**
     * @return PresentationLink[]
     */
    public function getLinks()
    {
        return $this->materials->filter(function ($element) {
            return $element instanceof PresentationLink;
        });
    }

    /**
     * @return bool
     */
    public function hasLinks()
    {
        return count($this->getLinks()) > 0;
    }

    /**
     * @param PresentationLink $link
     * @return $this
     */
    public function addLink(PresentationLink $link)
    {
        $this->materials->add($link);
        $link->setPresentation($this);
        $link->setOrder($this->getMaterialsMaxOrder() + 1);
        return $this;
    }

    /**
     * @return int
     */
    public function getModeratorId()
    {
        try {
            return !is_null($this->moderator) ? $this->moderator->getId() : 0;
        } catch (\Exception $ex) {
            return 0;
        }
    }

    public function hasModerator(): bool
    {
        return $this->getModeratorId() > 0;
    }

    /**
     * @return bool
     */
    public function hasSelectionPlan():bool{
        return $this->getSelectionPlanId() > 0;
    }

    /**
     * @return int
     */
    public function getSelectionPlanId()
    {
        try {
            return !is_null($this->selection_plan) ? $this->selection_plan->getId() : 0;
        } catch (\Exception $ex) {
            return 0;
        }
    }


    /**
     * @return PresentationSpeaker
     */
    public function getModerator()
    {
        return $this->moderator;
    }

    /**
     * @param PresentationSpeaker $moderator
     */
    public function setModerator(PresentationSpeaker $moderator)
    {
        $this->moderator = $moderator;
    }

    public function unsetModerator()
    {
        $this->moderator = null;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return string
     */
    public function getStatusNice()
    {
        if ($this->isPublished())
            return 'Accepted';
        return $this->status;
    }

    /**
     * @return string
     */
    public function getProgressNice()
    {
        switch ($this->progress) {
            case self::PHASE_NEW:
                return 'NEW';
                break;
            case self::PHASE_SUMMARY:
                return 'SUMMARY';
                break;
            case self::PHASE_UPLOADS:
                return 'UPLOADS';
                break;
            case self::PHASE_TAGS:
                return 'TAGS';
                break;
            case self::PHASE_SPEAKERS:
                return 'SPEAKERS';
                break;
            case self::PHASE_COMPLETE:
                return 'COMPLETE';
                break;
            default:
                return 'NEW';
                break;
        }
    }

    /**
     * @return mixed
     */
    public function getProgress()
    {
        return $this->progress;
    }

    /**
     * @return bool
     */
    public function isCompleted(): bool
    {
        return $this->progress == Presentation::PHASE_COMPLETE;
    }

    /**
     * @param int $progress
     */
    public function setProgress(int $progress)
    {
        if ($this->progress < $progress)
            $this->progress = $progress;
    }

    /**
     * @return PresentationMaterial[]
     */
    public function getMaterials()
    {
        return $this->materials;
    }

    /**
     * @param PresentationMaterial[] $materials
     */
    public function setMaterials($materials)
    {
        $this->materials = $materials;
    }

    /**
     * @return SummitSelectedPresentation[]
     */
    public function getSelectedPresentations()
    {
        return $this->selected_presentations;
    }

    /**
     * @param SummitSelectedPresentation[] $selected_presentations
     */
    public function setSelectedPresentations($selected_presentations)
    {
        $this->selected_presentations = $selected_presentations;
    }

    /**
     * @return bool
     */
    public function getAttendingMedia()
    {
        return $this->attending_media;
    }

    /**
     * @param bool $attending_media
     */
    public function setAttendingMedia($attending_media)
    {
        $this->attending_media = $attending_media;
    }

    /**
     * @return string
     * @throws ValidationException
     */
    public function getSelectionStatus()
    {

        $session_sel = $this->createQuery("SELECT sp from models\summit\SummitSelectedPresentation sp 
            JOIN sp.list l
            JOIN sp.presentation p
            WHERE p.id = :presentation_id 
            AND sp.collection = :collection
            AND l.list_type = :list_type
            AND l.list_class = :list_class")
            ->setParameter('presentation_id', $this->id)
            ->setParameter('collection', SummitSelectedPresentation::CollectionSelected)
            ->setParameter('list_type', SummitSelectedPresentationList::Group)
            ->setParameter('list_class', SummitSelectedPresentationList::Session)->getResult();

        // Error out if a talk has more than one selection
        if (count($session_sel) > 1) {
            throw new ValidationException(sprintf('presentation %s has more than 1 (one) selection.', $this->id));
        }

        $selection = null;
        if (count($session_sel) == 1) {
            $selection = $session_sel[0];
        }

        if (!$selection) {
            return Presentation::SelectionStatus_Unaccepted;
        }
        if ($selection->getOrder() <= $this->getCategory()->getSessionCount()) {
            return Presentation::SelectionStatus_Accepted;
        }

        return Presentation::SelectionStatus_Alternate;
    }

    public function getRank(): ?int
    {
        $session_sel = $this->createQuery("SELECT sp from models\summit\SummitSelectedPresentation sp 
            JOIN sp.list l
            JOIN sp.presentation p
            WHERE p.id = :presentation_id 
            AND sp.collection = :collection
            AND l.list_type = :list_type
            AND l.list_class = :list_class")
            ->setParameter('presentation_id', $this->id)
            ->setParameter('collection', SummitSelectedPresentation::CollectionSelected)
            ->setParameter('list_type', SummitSelectedPresentationList::Group)
            ->setParameter('list_class', SummitSelectedPresentationList::Session)->getResult();

        // Error out if a talk has more than one selection
        if (count($session_sel) > 1) {
            throw new ValidationException(sprintf('presentation %s has more than 1 (one) selection.', $this->id));
        }

        $selection = null;

        if (count($session_sel) == 1) {
            $selection = $session_sel[0];
        }

        if (!$selection) {
            return null;
        }

        return $selection->getOrder();
    }

    /**
     * @return SelectionPlan|null
     */
    public function getSelectionPlan():?SelectionPlan
    {
        return $this->selection_plan;
    }

    /**
     * @param SelectionPlan $selection_plan
     */
    public function setSelectionPlan($selection_plan)
    {
        $oldSelectionPlan = $this->selection_plan;
        // if selection plan changes
        if(!is_null($oldSelectionPlan) && $oldSelectionPlan->getId() != $selection_plan->getId()){
            // then clear all selections so far
            $this->selected_presentations->clear();
        }
        $this->selection_plan = $selection_plan;
    }

    public function clearSelectionPlan()
    {
        $this->selection_plan = null;
    }

    /**
     * @deprecated
     * @return Member
     */
    public function getCreator()
    {
        return $this->created_by;
    }

    /**
     * @deprecated moved to created by attribute
     * @param Member $creator
     */
    public function setCreator(Member $creator)
    {
        $this->created_by = $creator;
    }

    /**
     * @deprecated
     * @return TrackAnswer[]
     */
    public function getAnswers()
    {
        return $this->answers;
    }

    /**
     * @deprecated
     * @param TrackAnswer[] $answers
     */
    public function setAnswers($answers)
    {
        $this->answers = $answers;
    }

    /**
     * @deprecated
     * @param TrackAnswer $answer
     */
    public function addAnswer(TrackAnswer $answer)
    {
        $this->answers->add($answer);
        $answer->setPresentation($this);
    }

    /**
     * @param string $link
     * @return PresentationLink|null
     */
    public function findLink($link)
    {
        $links = $this->getLinks();

        foreach ($links as $entity) {
            if ($entity->getLink() == $link)
                return $entity;
        }
        return null;
    }

    public function clearLinks()
    {
        $links = $this->getLinks();

        foreach ($links as $link) {
            $this->materials->removeElement($link);
            $link->clearPresentation();
        }
    }

    /**
     * @deprecated
     * @param TrackQuestionTemplate $question
     * @return TrackAnswer|null
     */
    public function getTrackExtraQuestionAnswer(TrackQuestionTemplate $question)
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('question', $question));
        $res = $this->answers->matching($criteria)->first();
        return $res === false ? null : $res;
    }

    /**
     * @return SummitPresentationComment[]
     */
    public function getPublicComments()
    {
        return $this->comments->filter(function ($element) {
            return $element instanceof SummitPresentationComment && $element->isPublic();
        });
    }

    /**
     * @return int
     */
    public function getCommentsCount():int{
        return $this->comments->count();
    }


    /**
     * @return ArrayCollection|SummitPresentationComment[]
     */
    public function getComments(){
        return $this->comments;
    }

    /**
     * @deprecated
     * @return int
     */
    public function getCreatorId()
    {
        try {
            if (is_null($this->created_by)) return 0;
            return $this->created_by->getId();
        } catch (\Exception $ex) {
            return 0;
        }
    }

    /**
     * @param PresentationSpeaker $speaker
     * @return bool
     */
    public function canEdit(PresentationSpeaker $speaker):bool
    {
        if ($this->getCreatedById() == $speaker->getMemberId()) return true;
        if ($this->getModeratorId() == $speaker->getId()) return true;
        if ($this->isSpeaker($speaker)) return true;
        return false;
    }

    /**
     * @param Member $member
     * @return bool
     */
    public function memberCanEdit(Member $member):bool{
        if ($this->getCreatedById() == $member->getId()) return true;
        if(!$member->hasSpeaker()) return false;
        $speaker = $member->getSpeaker();
        if ($this->getModeratorId() == $speaker->getId()) return true;
        if ($this->isSpeaker($speaker)) return true;
        return false;
    }


    /**
     * @return bool
     */
    public function fulfilMediaUploadsConditions():bool{

        $type = $this->type;
        if (!$type instanceof PresentationType) return false;

        $summitMediaUploadCount = $type->getMandatoryAllowedMediaUploadTypesCount();
        if($summitMediaUploadCount === 0) return true;
        if($summitMediaUploadCount > $this->getMediaUploadsMandatoryCount()) return false;
        return true;
    }

    /**
     * @return bool
     */
    public function fulfilSpeakersConditions(): bool
    {
        $type = $this->type;
        if (!$type instanceof PresentationType) return false;

        if ($type->isUseModerator()) {
            $count = $this->getModeratorId() > 0 ? 1 : 0;
            $max = $type->getMaxModerators();
            $min = $type->getMinModerators();
            if ($type->isModeratorMandatory() && $min > $count) return false;
            if ($count > $max) return false;
        }

        if ($type->isUseSpeakers()) {
            $count = $this->speakers->count();
            $max = $type->getMaxSpeakers();
            $min = $type->getMinSpeakers();
            if ($type->isAreSpeakersMandatory() && $min > $count) return false;
            if ($count > $max) return false;
        }

        return true;
    }

    /**
     * @param PresentationMaterial $material
     * @param int $new_order
     * @throws ValidationException
     */
    public function recalculateMaterialOrder(PresentationMaterial $material, $new_order)
    {
        self::recalculateOrderForSelectable($this->materials, $material, $new_order);
    }

    use OrderableChilds;

    /**
     * @return bool
     */
    public function isSubmitted(): bool
    {
        return $this->progress == Presentation::PHASE_COMPLETE && $this->status == Presentation::STATUS_RECEIVED;
    }

    /**
     * @return string
     */
    public function getSlug(): ?string
    {
        return $this->slug;
    }

    protected static $default_replacements = [
        '/\s/' => '-', // remove whitespace
        '/_/' => '-', // underscores to dashes
        '/[^A-Za-z0-9+.\-]+/' => '', // remove non-ASCII chars, only allow alphanumeric plus dash and dot
        '/[\-]{2,}/' => '-', // remove duplicate dashes
        '/^[\.\-_]+/' => '', // Remove all leading dots, dashes or underscores
    ];

    public function generateSlug(): void
    {
        if (empty($this->title)) return;
        $this->slug = trim(Transliterator::utf8ToAscii($this->title));

        foreach (self::$default_replacements as $regex => $replace) {
            $this->slug = preg_replace($regex, $replace, $this->slug);
        }
    }

    /**
     * @param string $title
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;
        $this->generateSlug();
        return $this;
    }
    /**
     * Gets a link to edit this presentation
     *
     * @return  string
     */
    public function getEditLink(): string
    {
        if(!$this->hasSelectionPlan()) return '#';

        return sprintf
        (
            "%s/app/%s/%s/presentations/%s/summary",
            Config::get('cfp.base_url'),
            $this->summit->getRawSlug(),
            $this->selection_plan->getId(),
            $this->id
        );
    }

    /**
     * @return int
     */
    public function getMediaUploadsMandatoryCount(): int
    {
        return $this->materials->filter(function ($element) {
            return $element instanceof PresentationMediaUpload && $element->isMandatory();
        })->count();
    }

    /**
     * @return array
     */
    public function getMandatoryMediaUploadsCountByType(): array {
        $res = array();
        foreach ($this->materials as $element) {
            if ($element instanceof PresentationMediaUpload && $element->isMandatory()) {
                if (array_key_exists($element->getMediaUploadTypeId(), $res)) {
                    $res[$element->getMediaUploadTypeId()]++;
                } else {
                    $res[$element->getMediaUploadTypeId()] = 1;
                }
            }
        }
        return $res;
    }

    /**
     * @param Member $viewer
     */
    public function addTrackChairView(Member $viewer)
    {
        if($this->viewedBy($viewer)) return;
        $view = PresentationTrackChairView::build($viewer, $this);
        $this->track_chair_views->add($view);
    }

    /**
     * @param Member $commenter
     * @param string $body
     * @param bool $is_public
     * @return SummitPresentationComment
     */
    public function addTrackChairComment(Member $commenter, string $body, $is_public = true)
    {
        $comment = SummitPresentationComment::createComment
        (
            $commenter,
            $this,
            $body,
            $is_public
        );

        $this->comments->add($comment);
        return $comment;
    }

    /**
     * @param Member $commenter
     * @param string $body
     * @return SummitPresentationComment
     */
    public function addTrackChairNotification(Member $commenter, string $body)
    {
        $comment = SummitPresentationComment::createNotification
        (
            $commenter,
            $this,
            $body
        );

        $this->comments->add($comment);
        return $comment;
    }

    public function hasViews(): bool
    {
        return $this->track_chair_views->count() > 0;
    }

    /**
     * @return ArrayCollection|PresentationTrackChairView[]
     */
    public function getViewers()
    {
        return $this->track_chair_views;
    }

    /**
     * @return array
     */
    public function getMemberViewers(){
        return array_map(function ($view) {
            return $view->getViewer();
        }, $this->track_chair_views->getValues());
    }

    public function getViewsCount(): int
    {
        return $this->track_chair_views->count();
    }

    public function clearViews():void{
        $this->track_chair_views->clear();
    }

    public function viewedBy(Member $member):bool{
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('viewer', $member));
        return $this->track_chair_views->matching($criteria)->count() > 0;
    }

    /**
     * @param string $list_class
     * @return bool
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function isGroupSelected($list_class = SummitSelectedPresentationList::Session): bool
    {
        return $this->createQuery("SELECT COUNT(sp.id) from models\summit\SummitSelectedPresentation sp 
            JOIN sp.list l
            JOIN sp.presentation p
            WHERE p.id = :presentation_id 
            AND sp.collection = :collection
            AND l.list_type = :list_type
            AND l.list_class = :list_class")
                ->setParameter('presentation_id', $this->id)
                ->setParameter('collection', SummitSelectedPresentation::CollectionSelected)
                ->setParameter('list_type', SummitSelectedPresentationList::Group)
                ->setParameter('list_class', $list_class)->getSingleScalarResult() > 0;
    }

    /**
     * @param Member $member
     * @param string $list_class
     * @return SummitSelectedPresentation|null
     */
    public function getSelectionByMemberAndListClass(Member $member, $list_class = SummitSelectedPresentationList::Session): ?SummitSelectedPresentation
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('member', $member));
        $selection = $this->selected_presentations->matching($criteria)->filter(function ($election) use ($list_class) {
            $list = $election->getList();
            return $list->getListClass() === $list_class;
        })->first();

        return $selection === false ? null : $selection;
    }

    /**
     * @param Member $member
     * @param string $list_class
     * @return string|null
     */
    public function getSelectionCurrentSelectionType(Member $member, $list_class = SummitSelectedPresentationList::Session): ?string
    {
        $selection = $this->getSelectionByMemberAndListClass($member, $list_class);
        if (is_null($selection)) return null;
        return $selection->getCollection();
    }

    /**
     * @param string $collection_type
     * @return array|Member[]
     */
    public function getSelectionMembers(string $collection_type): array
    {

        $selections = $this->selected_presentations->filter(function ($selection) use ($collection_type) {
            $list = $selection->getList();
            return $list->getListType() === SummitSelectedPresentationList::Individual
                && $selection->getCollection() == $collection_type;
        })->getValues();

        if (!count($selections)) return [];

        return array_map(function ($selection) {
            return $selection->getMember();
        }, $selections);
    }

    /**
     * @param Member $member
     * @param string $collection_type
     * @return bool
     */
    public function hasMemberSelectionFor(Member $member, string $collection_type):bool{
        return $this->selected_presentations->filter(function ($selection) use ($collection_type, $member) {
            $list = $selection->getList();
            return $list->getListType() === SummitSelectedPresentationList::Individual
                && $selection->getCollection() == $collection_type && $selection->getMemberId() == $member->getId();
        })->count() > 0;
    }

    /**
     * @param string $collection_type
     * @return int
     */
    public function getSelectionMembersCount(string $collection_type): int{
        return $this->selected_presentations->filter(function ($selection) use ($collection_type) {
            $list = $selection->getList();
            return $list->getListType() === SummitSelectedPresentationList::Individual
                && $selection->getCollection() == $collection_type;
        })->count();
    }

    /**
     * @return array|Member[]
     */
    public function getSelectors()
    {
        return $this->getSelectionMembers(SummitSelectedPresentation::CollectionSelected);
    }

    /**
     * @return int
     */
    public function getSelectorsCount():int{
        return $this->getSelectionMembersCount(SummitSelectedPresentation::CollectionSelected);
    }

    public function isSelectedByAnyone():bool{
        return $this->getSelectorsCount() > 0;
    }

    /**
     * @return array|Member[]
     */
    public function getLikers()
    {
        return $this->getSelectionMembers(SummitSelectedPresentation::CollectionMaybe);
    }

    /**
     * @return int
     */
    public function getLikersCount():int{
        return $this->getSelectionMembersCount(SummitSelectedPresentation::CollectionMaybe);
    }

    /**
     * @return array|Member[]
     */
    public function getPassers()
    {
        return $this->getSelectionMembers(SummitSelectedPresentation::CollectionPass);
    }

    /**
     * @return int
     */
    public function getPassersCount():int{
        return $this->getSelectionMembersCount(SummitSelectedPresentation::CollectionPass);
    }

    /**
     * @return int
     */
    public function getVotesTotalPoints(): int
    {
        $res =  $this->createQuery("SELECT SUM(v.vote) from models\summit\PresentationVote v 
            JOIN v.presentation p
            WHERE p.id = :presentation_id")
            ->setParameter('presentation_id', $this->id)->getSingleScalarResult();
        return is_null($res) ? 0: intval($res);
    }

    /**
     * @return int
     */
    public function getVotesCount(): int
    {

        $res = $this->createQuery("SELECT COUNT(v.id) from models\summit\PresentationVote v 
            JOIN v.presentation p
            WHERE p.id = :presentation_id")
            ->setParameter('presentation_id', $this->id)->getSingleScalarResult();
        return is_null($res) ? 0: intval($res);
    }

    /**
     * @return float
     */
    public function getVotesAverage(): float
    {
        $res = $this->createQuery("SELECT AVG(v.vote) from models\summit\PresentationVote v 
            JOIN v.presentation p
            WHERE p.id = :presentation_id")
            ->setParameter('presentation_id', $this->id)->getSingleScalarResult();
        return is_null($res) ? 0: floatval($res);
    }

    /**
     * @return float
     */
    public function getPopularityScore():float
    {

        $weight_select = floatval(Config::get("track_chairs.weight_select"));
        $weight_maybe = floatval(Config::get("track_chairs.weight_maybe"));
        $weight_pass = floatval(Config::get("track_chairs.weight_pass"));

        return (
            ($this->getSelectorsCount() * $weight_select) +
            ($this->getLikersCount() * $weight_maybe) +
            ($this->getPassersCount()* $weight_pass)
        );
    }

    public function clearCategoryChangeRequests():void{
        $this->category_changes_requests->clear();
    }

    /**
     * @return ArrayCollection|SummitCategoryChange[]
     */
    public function getCategoryChangeRequests(){
        return  $this->category_changes_requests;
    }

    /**
     * @return ArrayCollection|SummitCategoryChange[]
     */
    public function getPendingCategoryChangeRequests(){
        $criteria = Criteria::create();
        $criteria->andWhere(Criteria::expr()->eq('status', ISummitCategoryChangeStatus::Pending));
        return $this->category_changes_requests->matching($criteria);
    }

    public function getPendingCategoryChangeRequestsCount():int{
        return $this->getPendingCategoryChangeRequests()->count();
    }

    /**
     * @param Member $requester
     * @param PresentationCategory $newCategory
     * @return SummitCategoryChange
     * @throws ValidationException
     */
    public function addCategoryChangeRequest
    (
        Member $requester,
        PresentationCategory $newCategory
    ):SummitCategoryChange
    {
        $criteria = Criteria::create();
        $criteria->andWhere(Criteria::expr()->eq('new_category', $newCategory));
        $criteria->andWhere(Criteria::expr()->eq('status', ISummitCategoryChangeStatus::Pending));

        if($this->category_changes_requests->matching($criteria)->count() > 0)
            throw new ValidationException(sprintf("There is already a pending category change request for this category."));

        if($this->category->getId() == $newCategory->getId())
            throw new ValidationException("This presentation already belongs to %s.", $newCategory->getTitle());

        $request = SummitCategoryChange::create($this, $requester, $newCategory);

        $this->category_changes_requests->add($request);

        return $request;
    }

    /**
     * @param int $id
     * @return SummitCategoryChange|null
     */
    public function getCategoryChangeRequest(int $id):?SummitCategoryChange{
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', $id));
        $res = $this->category_changes_requests->matching($criteria)->first();
        return $res === false ? null : $res;
    }

    /**
     * @param Member $member
     * @return int
     * @throws ValidationException
     */
    public function getRemainingSelectionsForMember(Member $member):int{
        $list = $this->category->getSelectionListByTypeAndOwner(SummitSelectedPresentationList::Individual, $member);
        if(is_null($list) || !$list instanceof SummitSelectedPresentationList) return $this->category->getTrackChairAvailableSlots();
        return $list->getAvailableSlots();
    }

    /**
     * @param PresentationActionType $type
     * @return bool
     */
    public function hasActionByType(PresentationActionType $type):bool{
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('type', $type));
        return $this->actions->matching($criteria)->count() > 0;
    }

    /**
     * @param PresentationActionType $type
     * @return PresentationAction|null
     */
    public function getActionByType(PresentationActionType $type):?PresentationAction {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('type', $type));
        $res = $this->actions->matching($criteria)->first();
        return $res === false ? null : $res;
    }

    /**
     * @param int $id
     * @return PresentationAction|null
     */
    public function getActionById(int $id):?PresentationAction {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', $id));
        $res = $this->actions->matching($criteria)->first();
        return $res === false ? null : $res;
    }

    /**
     * @param bool $complete
     * @param PresentationActionType $type
     * @return PresentationAction|null
     */
    public function setCompletionByType(bool $complete, PresentationActionType $type):?PresentationAction{
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('type', $type));
        $res = $this->actions->matching($criteria)->first();
        if($res === false) return null;
        $res->setIsCompleted($complete);
        return $res;
    }

    public function initializeActions():void {
        Log::debug(sprintf("Presentation::initializeActions presentation %s", $this->id));
        foreach ($this->summit->getPresentationActionTypes() as $presentationActionType){
            if(!$this->hasActionByType($presentationActionType)){
                // create it
                Log::debug
                (
                    sprintf
                    (
                        "Presentation::initializeActions creating new presentation action for type %s",
                        $presentationActionType->getLabel()
                    )
                );
                $action = new PresentationAction();
                $action->setType($presentationActionType);
                $action->setPresentation($this);
                $this->actions->add($action);
            }
        }
    }

    /**
     * @param $a
     * @param $b
     * @return int
     */
    private function actionSort($a,$b) {
        $o1 = $a->getType()->getOrder();
        $o2 = $b->getType()->getOrder();
        if ($o1 == $o2) {
            return 0;
        }
        return ($o1 < $o2) ? -1 : 1;
    }

    /**
     * @return array|PresentationAction[]
     */
    public function getPresentationActions(){
        // ordered by type order
        $array = $this->actions->toArray();
        usort($array, [ $this , 'actionSort' ]);
        return $array;
    }

    /**
     * @return bool
     */
    public function isWillAllSpeakersAttend(): bool
    {
        return $this->will_all_speakers_attend;
    }

    /**
     * @param bool $will_all_speakers_attend
     */
    public function setWillAllSpeakersAttend(bool $will_all_speakers_attend): void
    {
        $this->will_all_speakers_attend = $will_all_speakers_attend;
    }

    /**
     * @return PresentationExtraQuestionAnswer[]
     */
    public function getExtraQuestionAnswers()
    {
        $selection_plan = $this->selection_plan;
        if(is_null($selection_plan)) return [];
        $res = [];
        foreach ($this->extra_question_answers as $answer){
            if($selection_plan->isExtraQuestionAssigned($answer->getQuestion())){
                $res[] = $answer;
            }
        }
        return $res;
    }

    /**
     * @param SummitSelectionPlanExtraQuestionType $question
     * @return PresentationExtraQuestionAnswer|null
     */
    public function getExtraQuestionAnswerByQuestion(SummitSelectionPlanExtraQuestionType $question):?PresentationExtraQuestionAnswer{
        $answer = $this->extra_question_answers->matching(
            Criteria::create()
                ->where(Criteria::expr()->eq("question", $question))
        )->first();
        return $answer ? $answer : null;
    }


    public function clearExtraQuestionAnswers():void
    {
        $selection_plan = $this->selection_plan;
        if(is_null($selection_plan)) return;
        // only clear the ones assigned to selection plan
        $to_remove = [];
        foreach ($this->extra_question_answers as $answer){
            if($selection_plan->isExtraQuestionAssigned($answer->getQuestion())){
                $to_remove[] = $answer;
            }
        }
        // clear answers
        foreach($to_remove as $a){
            $this->extra_question_answers->removeElement($a);
        }

    }

    /**
     * @param ExtraQuestionAnswer $answer
     */
    public function addExtraQuestionAnswer(ExtraQuestionAnswer $answer){
        if(!$answer instanceof PresentationExtraQuestionAnswer) return;
        if($this->extra_question_answers->contains($answer)) return;
        $this->extra_question_answers->add($answer);
        $answer->setPresentation($this);
    }

    /**
     * @param ExtraQuestionAnswer $answer
     */
    public function removeExtraQuestionAnswer(ExtraQuestionAnswer $answer){
        if(!$answer instanceof PresentationExtraQuestionAnswer) return;
        if(!$this->extra_question_answers->contains($answer)) return;
        $this->extra_question_answers->removeElement($answer);
        $answer->clearPresentation();
    }

    /**
     * @return \DateTime
     */
    public function getDisclaimerAcceptedDate(): ?\DateTime
    {
        return $this->disclaimer_accepted_date;
    }

    /**
     * @return bool
     */
    public function isDisclaimerAccepted():bool{
        return !is_null($this->disclaimer_accepted_date);
    }

    /**
     * @param \DateTime $disclaimer_accepted_date
     */
    public function setDisclaimerAcceptedDate(\DateTime $disclaimer_accepted_date): void
    {
        $this->disclaimer_accepted_date = $disclaimer_accepted_date;
    }


    public function clearMediaUploads():void{
        $mediaUploads = $this->getMediaUploads();

        if ($mediaUploads->count()) {
            Log::debug("Presentation::clearMediaUploads processing media uploads");
            $private_paths = [];
            $public_paths = [];

            foreach ($mediaUploads as $mediaUpload) {

                $mediaUploadType = $mediaUpload->getMediaUploadType();
                $strategy = FileUploadStrategyFactory::build($mediaUploadType->getPrivateStorageType());

                if (!is_null($strategy)) {
                    $privatePath  = $mediaUpload->getPath(IStorageTypesConstants::PrivateType);
                    if(!isset($private_paths[$privatePath]))
                        $private_paths[$privatePath] = $strategy;
                    Log::debug(sprintf("Presentation::clearMediaUploads marking as deleted %s/%s ", $privatePath, $mediaUpload->getFilename()));
                    $strategy->markAsDeleted($privatePath, $mediaUpload->getFilename());
                }

                $strategy = FileUploadStrategyFactory::build($mediaUploadType->getPublicStorageType());

                if (!is_null($strategy)) {
                    $publicPath  = $mediaUpload->getPath(IStorageTypesConstants::PublicType);
                    if(!isset($public_paths[$publicPath]))
                        $public_paths[$publicPath] = $strategy;
                    Log::debug(sprintf("Presentation::clearMediaUploads marking as deleted %s/%s ", $publicPath, $mediaUpload->getFilename()));
                    $strategy->markAsDeleted($publicPath, $mediaUpload->getFilename());
                }
            }

            foreach($private_paths as $path => $strategy){
                Log::debug(sprintf("Presentation::clearMediaUploads marking as deleted path ( private) %s.", $path));
                $strategy->markAsDeleted($path);
            }

            foreach($public_paths as $path => $strategy){
                Log::debug(sprintf("Presentation::clearMediaUploads as deleted path ( public ) %s.", $path));
                $strategy->markAsDeleted($path);
            }
        }
    }

    /**
     * @return int
     */
    public function getCustomOrder(): int
    {
        return $this->custom_order;
    }

    /**
     * @param int $custom_order
     */
    public function setCustomOrder(int $custom_order): void
    {
        $this->custom_order = $custom_order;
    }

    /**
     * @param int|null $begin_voting_date
     * @param int|null $end_voting_date
     * @return ArrayCollection|\Doctrine\Common\Collections\Collection|PresentationAttendeeVote[]
     */
    private function getVotesRange(?int $begin_voting_date = null, ?int $end_voting_date = null) {
        $criteria = null;

        if ($begin_voting_date != null) {
            $begin_voting_date = Carbon::createFromTimestamp($begin_voting_date, new \DateTimeZone(SilverstripeBaseModel::DefaultTimeZone));
            $criteria = Criteria::create();
            $criteria->where(Criteria::expr()->gte('created', $begin_voting_date));
        }
        if ($end_voting_date != null) {
            $end_voting_date = Carbon::createFromTimestamp($end_voting_date, new \DateTimeZone(SilverstripeBaseModel::DefaultTimeZone));
            $expr = Criteria::expr()->lte('created', $end_voting_date);
            if ($criteria == null) {
                $criteria = Criteria::create();
                $criteria->where($expr);
            } else {
                $criteria->andWhere($expr);
            }
        }
        return $criteria != null ? $this->attendees_votes->matching($criteria) : $this->attendees_votes;
    }

    /**
     * @return ArrayCollection|SummitAttendee[]
     */
    public function getVoters($begin_voting_date = null, $end_voting_date = null): ArrayCollection {
        return $this->getVotesRange($begin_voting_date, $end_voting_date)->map(function ($attendeeVote) {
            return $attendeeVote->getVoter();
        });
    }

    /**
     * @return int
     */
    public function getAttendeeVotesCount($begin_voting_date = null, $end_voting_date = null): int {
        return $this->getVotesRange($begin_voting_date, $end_voting_date)->count();
    }

    /**
     * @param SummitAttendee $attendee
     * @return PresentationAttendeeVote
     * @throws ValidationException
     */
    public function castAttendeeVote(SummitAttendee $attendee):PresentationAttendeeVote{

        Log::debug(sprintf("Presentation::castAttendeeVote attendee %s presentation %s", $attendee->getId(), $this->getId()));
        // check that member did not vote yet...
        if($this->attendees_votes->matching(Criteria::create()
            ->where(Criteria::expr()->eq("voter", $attendee)))->count() > 0)
            throw new ValidationException(sprintf("Attendee %s already vote on presentation %s", $attendee->getEmail(), $this->id));

        Log::debug("Presentation::castAttendeeVote creating vote");
        $vote = new PresentationAttendeeVote($attendee, $this);

        $this->attendees_votes->add($vote);

        return $vote;
    }

    /**
     * @param SummitAttendee $attendee
     * @throws ValidationException
     */
    public function unCastAttendeeVote(SummitAttendee $attendee):void{

        $vote = $this->attendees_votes->matching(Criteria::create()
            ->where(Criteria::expr()->eq("voter", $attendee)))->first();

        if(!$vote)
            throw new ValidationException(sprintf("Vote not found."));

        $this->attendees_votes->removeElement($vote);
        $attendee->removePresentationVote($vote);
        $vote->clearVoter();
        $vote->clearPresentation();
    }

    /**
     * @param PresentationCategory $category
     * @return $this
     */
    public function setCategory(PresentationCategory $category)
    {
        // check if we change the category
        $oldCategory = $this->category;
        if(!is_null($oldCategory) && $oldCategory->getId() != $category->getId()){
            // then we need to clear up all selections ( individual / team)
            $this->selected_presentations->clear();
        }
        return parent::setCategory($category);
    }

    public function addTrackChairScore(PresentationTrackChairScore $score):void{
        if($this->track_chairs_scores->contains($score)) return;
        $this->track_chairs_scores->add($score);
        $score->setPresentation($this);
    }

    public function removeTrackChairScore(PresentationTrackChairScore $score):void{
        if(!$this->track_chairs_scores->contains($score)) return;
        $this->track_chairs_scores->removeElement($score);
        $score->clearPresentation();
    }


    public function getTrackChairAvgScoresPerRakingType():array{

        Log::debug(sprintf("Presentation::getTrackChairAvgScoresPerRakingType presentation %s", $this->getId()));

        $query = <<<SQL
SELECT 
AVG(PresentationTrackChairScoreType.Score) avg_score, 
PresentationTrackChairRatingType.ID AS ranking_type_id
FROM PresentationTrackChairScore
INNER JOIN PresentationTrackChairScoreType on PresentationTrackChairScoreType.ID = PresentationTrackChairScore.TypeID
INNER JOIN PresentationTrackChairRatingType on PresentationTrackChairRatingType.ID = PresentationTrackChairScoreType.TypeID
WHERE PresentationTrackChairScore.PresentationID = :presentation_id
GROUP BY PresentationTrackChairRatingType.ID
SQL;

        try{
            $stmt = $this->prepareRawSQL($query);
            $stmt->execute(
                [
                    'presentation_id' => $this->getId(),
                ]
            );
            $res = $stmt->fetchAll();
            $res = count($res) > 0 ? $res : [];
            return $res;
        }
        catch (\Exception $ex){
            Log::error($ex);
        }
        return [];
    }
    /**
     * @return float
     */
    public function getTrackChairAvgScore():float{

        Log::debug(sprintf("Presentation::getTrackChairAvgScore presentation %s", $this->getId()));

        $query = <<<SQL
     SELECT AVG(Score) FROM (select PresentationTrackChairScore.TrackChairID,
       PresentationTrackChairScore.PresentationID,
       SUM(PresentationTrackChairScoreType.Score * PresentationTrackChairRatingType.Weight) As Score
       from PresentationTrackChairScore
inner join PresentationTrackChairScoreType on PresentationTrackChairScoreType.ID = PresentationTrackChairScore.TypeID
inner join PresentationTrackChairRatingType on PresentationTrackChairRatingType.ID = PresentationTrackChairScoreType.TypeID
WHERE PresentationID = :presentation_id
GROUP BY TrackChairID,PresentationID ) AS Presentation_Scores
SQL;
        try{
            $stmt = $this->prepareRawSQL($query);
            $stmt->execute(
                [
                    'presentation_id' => $this->getId(),
                ]
            );
            $res = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            $score = count($res) > 0 ? $res[0] : 0;
            return floatval($score);
        }
        catch (\Exception $ex){
            Log::error($ex);
        }
        return 0.0;
    }

    /**
     * @param SummitTrackChair $trackChair
     * @return float
     */
    public function getTrackChairScoreFor(SummitTrackChair $trackChair):float{

        Log::debug
        (
            sprintf
            (
                "Presentation::getTrackChairScoreFor presentation %s track chair %s",
                        $this->getId(),
                        $trackChair->getId()
            )
        );

        $query = <<<SQL
select
       SUM(PresentationTrackChairScoreType.Score * PresentationTrackChairRatingType.Weight) As Score
       from PresentationTrackChairScore
inner join PresentationTrackChairScoreType on PresentationTrackChairScoreType.ID = PresentationTrackChairScore.TypeID
inner join PresentationTrackChairRatingType on PresentationTrackChairRatingType.ID = PresentationTrackChairScoreType.TypeID
WHERE PresentationID = :presentation_id and TrackChairID = :track_chair_id
GROUP BY TrackChairID,PresentationID

SQL;
        try{
            $stmt = $this->prepareRawSQL($query);
            $stmt->execute(
                [
                    'presentation_id' => $this->getId(),
                    'track_chair_id' => $trackChair->getId()
                ]
            );
            $res = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            $score = count($res) > 0 ? $res[0] : 0;
            return floatval($score);
        }
        catch (\Exception $ex){
            Log::error($ex);
        }
        return 0.0;
    }

    /**
     * @param SummitTrackChair $trackChair
     * @return PresentationTrackChairScore[]
     */
    public function getTrackChairScoresBy(SummitTrackChair $trackChair){
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('reviewer', $trackChair));
        return $this->track_chairs_scores->matching($criteria);
    }

    /**
     * @return ExtraQuestionType[] | ArrayCollection
     */
    public function getExtraQuestions()
    {
        return $this->selection_plan->getExtraQuestions()->map(function ($a){ return $a->getQuestionType();});
    }

    /**
     * @param int $questionId
     * @return ExtraQuestionType|null
     */
    public function getQuestionById(int $questionId): ?ExtraQuestionType
    {
        return $this->selection_plan->getExtraQuestionById($questionId);
    }

    /**
     * @return bool
     */
    public function canChangeAnswerValue(): bool
    {
        return true;
    }

    public function buildExtraQuestionAnswer(): ExtraQuestionAnswer
    {
        return new PresentationExtraQuestionAnswer();
    }
}
