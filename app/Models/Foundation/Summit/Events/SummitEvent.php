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

use App\Models\Foundation\Summit\Events\RSVP\RSVPTemplate;
use App\Events\SummitEventCreated;
use App\Events\SummitEventDeleted;
use App\Events\SummitEventUpdated;
use App\Models\Utils\Traits\HasImageTrait;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Illuminate\Support\Facades\Log;
use models\exceptions\ValidationException;
use models\main\Company;
use models\main\File;
use models\main\Member;
use models\main\Tag;
use models\utils\PreRemoveEventArgs;
use models\utils\SilverstripeBaseModel;
use Doctrine\Common\Collections\ArrayCollection;
use DateTime;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Config;
use Cocur\Slugify\Slugify;
use models\utils\One2ManyPropertyTrait;
use Doctrine\ORM\Mapping AS ORM;
/**
 * @ORM\Entity(repositoryClass="App\Repositories\Summit\DoctrineSummitEventRepository")
 * @ORM\AssociationOverrides({
 *     @ORM\AssociationOverride(
 *          name="summit",
 *          inversedBy="events"
 *     )
 * })
 * @ORM\Table(name="SummitEvent")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="ClassName", type="string")
 * @ORM\DiscriminatorMap({
 *     "SummitEvent" = "SummitEvent",
 *     "Presentation" = "Presentation",
 *     "SummitGroupEvent" = "SummitGroupEvent",
 *     "SummitEventWithFile" = "SummitEventWithFile"
 * })
 * @ORM\HasLifecycleCallbacks
 * Class SummitEvent
 * @package models\summit
 */
class SummitEvent extends SilverstripeBaseModel
{

    /**
     *  minimun number of minutes that an event must last
     */
    const MIN_EVENT_MINUTES = 1;

    use One2ManyPropertyTrait;

    const ClassName = 'SummitEvent';

    protected $getIdMappings = [
        'getCreatedById' => 'created_by',
        'getUpdatedById' => 'updated_by',
    ];

    protected $hasPropertyMappings = [
        'hasCreatedBy' => 'created_by',
        'hasUpdatedBy' => 'updated_by',
    ];

    /**
     * @ORM\Column(name="Title", type="string")
     * @var string
     */
    protected $title;

    /**
     * @ORM\Column(name="Abstract", type="string")
     * @var string
     */
    protected $abstract;

    /**
     * @ORM\Column(name="SocialSummary", type="string")
     * @var string
     */
    protected $social_summary;

    /**
     * @ORM\Column(name="Occupancy", type="string")
     * @var string
     */
    protected $occupancy;

    /**
     * @ORM\Column(name="Level", type="string")
     * @var string
     */
    protected $level;

    /**
     * @ORM\Column(name="StartDate", type="datetime")
     * @var \DateTime
     */
    protected $start_date;

    /**
     * @ORM\Column(name="EndDate", type="datetime")
     * @var \DateTime
     */
    protected $end_date;

    /**
     * @ORM\Column(name="Published", type="boolean")
     * @var bool
     */
    protected $published;

    /**
     * @ORM\Column(name="PublishedDate", type="datetime")
     * @var \DateTime
     */
    protected $published_date;

    /**
     * @ORM\Column(name="AllowFeedBack", type="boolean")
     * @var bool
     */
    protected $allow_feedback;

    /**
     * @ORM\Column(name="AvgFeedbackRate", type="float")
     * @var float
     */
    protected $avg_feedback;

    /**
     * @ORM\Column(name="HeadCount", type="integer")
     * @var int
     */
    protected $head_count;

    /**
     * @ORM\Column(name="RSVPMaxUserNumber", type="integer")
     * @var int
     */
    protected $rsvp_max_user_number;

    /**
     * @ORM\Column(name="RSVPMaxUserWaitListNumber", type="integer")
     * @var int
     */
    protected $rsvp_max_user_wait_list_number;

    /**
     * @ORM\ManyToOne(targetEntity="App\Models\Foundation\Summit\Events\RSVP\RSVPTemplate", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="RSVPTemplateID", referencedColumnName="ID", onDelete="SET NULL")
     * @var RSVPTemplate
     */
    protected $rsvp_template;

    /**
     * @ORM\OneToMany(targetEntity="models\summit\RSVP", mappedBy="event", cascade={"persist"}, fetch="EXTRA_LAZY")
     * @var RSVP[]
     */
    protected $rsvp;

    /**
     * @ORM\Column(name="RSVPLink", type="string")
     * @var string
     */
    protected $rsvp_link;

    /**
     * @ORM\Column(name="ExternalId", type="string")
     * @var string
     */
    protected $external_id;

    /**
     * @ORM\ManyToOne(targetEntity="PresentationCategory", fetch="EXTRA_LAZY", cascade={"persist"})
     * @ORM\JoinColumn(name="CategoryID", referencedColumnName="ID", onDelete="SET NULL")
     * @var PresentationCategory
     */
    protected $category = null;

    /**
     * @ORM\ManyToOne(targetEntity="SummitEventType", fetch="EXTRA_LAZY", cascade={"persist"})
     * @ORM\JoinColumn(name="TypeID", referencedColumnName="ID", onDelete="SET NULL")
     * @var SummitEventType
     */
    protected $type;

    /**
     * @ORM\ManyToOne(targetEntity="SummitAbstractLocation", fetch="EXTRA_LAZY", cascade={"persist"})
     * @ORM\JoinColumn(name="LocationID", referencedColumnName="ID", onDelete="SET NULL")
     * @var SummitAbstractLocation
     */
    protected $location = null;

    /**
     * @ORM\ManyToMany(targetEntity="models\main\Company", inversedBy="sponsorships", fetch="EXTRA_LAZY")
     * @ORM\JoinTable(name="SummitEvent_Sponsors",
     *      joinColumns={@ORM\JoinColumn(name="SummitEventID", referencedColumnName="ID", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="CompanyID", referencedColumnName="ID", onDelete="CASCADE")}
     *      )
     */
    protected $sponsors;

    /**
     * @ORM\OneToMany(targetEntity="models\summit\SummitEventFeedback", mappedBy="event", cascade={"persist"}, fetch="EXTRA_LAZY")
     * @ORM\Cache("NONSTRICT_READ_WRITE")
     * @var SummitEventFeedback[]
     */
    protected $feedback;

    /**
     * @ORM\ManyToMany(targetEntity="models\main\Tag", cascade={"persist"}, inversedBy="events", fetch="EXTRA_LAZY")
     * @ORM\JoinTable(name="SummitEvent_Tags",
     *      joinColumns={@ORM\JoinColumn(name="SummitEventID", referencedColumnName="ID")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="TagID", referencedColumnName="ID")}
     *      )
     */
    protected $tags;

    /**
     * @ORM\OneToMany(targetEntity="models\summit\SummitEventAttendanceMetric", mappedBy="event", cascade={"persist","remove"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @var SummitEventAttendanceMetric[]
     */
    protected $attendance_metrics;

    /**
     * @ORM\Column(name="StreamingUrl", type="string")
     * @var string
     */
    protected $streaming_url;


    const STREAMING_TYPE_LIVE = 'LIVE';
    const STREAMING_TYPE_VOD = 'VOD';

    const ValidStreamingTypes = [self::STREAMING_TYPE_LIVE, self::STREAMING_TYPE_VOD];

    /**
     * @ORM\Column(name="StreamingType", type="string")
     * @var string
     */
    protected $streaming_type;

    /**
     * @ORM\Column(name="MuxPlaybackID", type="string")
     * @var string
     */
    protected $mux_playback_id;

    /**
     * @ORM\Column(name="MuxAssetID", type="string")
     * @var string
     */
    protected $mux_asset_id;

    /**
     * @ORM\Column(name="EtherpadLink", type="string")
     * @var string
     */
    protected $etherpad_link;

    /**
     * @ORM\Column(name="MeetingUrl", type="string")
     * @var string
     */
    protected $meeting_url;
    /**
     * @var PreRemoveEventArgs
     */
    protected $pre_remove_events;
    /**
     * @var PreUpdateEventArgs
     */
    protected $pre_update_args;

    /**
     * @ORM\ManyToOne(targetEntity="models\main\File", cascade={"persist"})
     * @ORM\JoinColumn(name="ImageID", referencedColumnName="ID")
     * @var File
     */
    protected $image;

    /**
     * @ORM\ManyToOne(targetEntity="models\main\Member", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="CreatedByID", referencedColumnName="ID", onDelete="SET NULL")
     * @var Member
     */
    protected $created_by = null;

    /**
     * @ORM\ManyToOne(targetEntity="models\main\Member", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="UpdatedByID", referencedColumnName="ID", onDelete="SET NULL")
     * @var Member
     */
    protected $updated_by = null;

    /**
     * @ORM\Column(name="ShowSponsors", type="boolean")
     * @var bool
     */
    protected $show_sponsors;

    /**
     * @ORM\Column(name="DurationInSeconds", type="integer")
     * @var int|null
     */
    protected $duration;

    /**
     * SummitEvent constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->allow_feedback = false;
        $this->published = false;
        $this->show_sponsors = false;
        $this->avg_feedback = 0;
        $this->head_count = 0;
        $this->rsvp_max_user_number = 0;
        $this->rsvp_max_user_wait_list_number = 0;
        $this->streaming_type = SummitEvent::STREAMING_TYPE_LIVE;
        $this->duration = 0;

        $this->tags = new ArrayCollection();
        $this->feedback = new ArrayCollection();
        $this->sponsors = new ArrayCollection();
        $this->rsvp = new ArrayCollection();
        $this->attendance_metrics = new ArrayCollection();

    }

    use SummitOwned;

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getAllowFeedback()
    {
        return $this->allow_feedback;
    }

    /**
     * @return boolean
     */
    public function isAllowFeedback()
    {
        return $this->getAllowFeedback();
    }

    /**
     * @param bool $allow_feeback
     * @return $this
     */
    public function setAllowFeedBack($allow_feeback)
    {
        $this->allow_feedback = $allow_feeback;
        return $this;
    }

    /**
     * @return PresentationCategory
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param PresentationCategory $category
     * @return $this
     */
    public function setCategory(PresentationCategory $category)
    {
        $this->category = $category;
        return $this;
    }

    /**
     * @return int
     */
    public function getCategoryId()
    {
        try {
            return !is_null($this->category) ? $this->category->getId() : 0;
        } catch (\Exception $ex) {
            return 0;
        }
    }

    public function hasCategory():bool{
        return $this->getCategoryId() > 0;
    }

    /**
     * @return string
     */
    public function getAbstract()
    {
        return $this->abstract;
    }

    /**
     * @param string $abstract
     */
    public function setAbstract($abstract)
    {
        $this->abstract = $abstract;
    }

    /**
     * @return string
     */
    public function getSocialSummary()
    {
        return $this->social_summary;
    }

    /**
     * @param string $social_summary
     */
    public function setSocialSummary($social_summary)
    {
        $this->social_summary = $social_summary;
    }

    /**
     * @return DateTime|null
     */
    public function getPublishedDate():?DateTime
    {
        return $this->published_date;
    }

    /**
     * @param DateTime $published_date
     */
    public function setPublishedDate($published_date)
    {
        $this->published_date = $published_date;
    }

    /**
     * @return float
     */
    public function getAvgFeedbackRate()
    {
        return !is_null($this->avg_feedback) ? $this->avg_feedback : 0.0;
    }

    /**
     * @param float $avg_feedback
     */
    public function setAvgFeedbackRate($avg_feedback)
    {
        $this->avg_feedback = $avg_feedback;
    }

    /**
     * @return string
     */
    public function getRSVPLink()
    {
        if ($this->hasRSVPTemplate()) {

            $summit = $this->getSummit();
            $schedule_page = $summit->getSchedulePage();
            if (empty($schedule_page)) return '';
            $url = sprintf("%s%s/events/%s/%s/rsvp",
                Config::get("server.assets_base_url", 'https://www.openstack.org/'),
                $schedule_page,
                $this->getId(),
                $this->getSlug()
            );
            return $url;
        }
        return $this->rsvp_link;
    }

    /**
     * @param string $rsvp_link
     */
    public function setRSVPLink($rsvp_link)
    {
        $this->rsvp_link = $rsvp_link;
        $this->rsvp_template = null;
        $this->rsvp_max_user_wait_list_number = 0;
        $this->rsvp_max_user_number = 0;
    }

    /**
     * @return bool
     */
    public function hasRSVPTemplate()
    {
        return $this->getRSVPTemplateId() > 0;
    }

    /**
     * @return int
     */
    public function getRSVPTemplateId()
    {
        try {
            return !is_null($this->rsvp_template) ? $this->rsvp_template->getId() : 0;
        } catch (\Exception $ex) {
            return 0;
        }
    }

    public function getSlug()
    {
        $slugify = new Slugify();
        return $slugify->slugify($this->title);
    }

    /**
     * @return bool
     */
    public function hasRSVP()
    {
        return !empty($this->rsvp_link) || $this->hasRSVPTemplate();
    }

    /**
     * @return bool
     */
    public function isExternalRSVP()
    {
        return !empty($this->rsvp_link) && !$this->hasRSVPTemplate();
    }

    /**
     * @return int
     */
    public function getHeadCount()
    {
        return $this->head_count;
    }

    /**
     * @param int $head_count
     */
    public function setHeadCount($head_count)
    {
        $this->head_count = $head_count;
    }

    /**
     * @return int
     */
    public function getTypeId()
    {
        try {
            return !is_null($this->type) ? $this->type->getId() : 0;
        } catch (\Exception $ex) {
            return 0;
        }
    }

    /**
     * @return SummitEventType|null
     */
    public function getType()
    {
        return $this->type;
    }

    public function hasType():bool{
        return $this->getTypeId() > 0;
    }

    /**
     * @param SummitEventType $type
     * @return $this
     */
    public function setType(SummitEventType $type)
    {
        $this->type = $type;
        return $this;
    }

    public function clearLocation()
    {
        $this->location = null;
        return $this;
    }

    public function clearPublishingDates():void{
        $this->start_date = null;
        $this->end_date = null;
    }

    /**
     * @return SummitAbstractLocation|null
     */
    public function getLocation():?SummitAbstractLocation
    {
        return $this->location;
    }

    /**
     * @param SummitAbstractLocation $location
     * @return $this
     * @throws ValidationException
     */
    public function setLocation(SummitAbstractLocation $location)
    {
        if(!$this->type->isAllowsLocation())
            throw new ValidationException("Event Type does not allows Location.");
        $this->location = $location;
        return $this;
    }

    /**
     * @return array
     */
    public function getSponsorsIds()
    {
        return $this->sponsors->map(function ($entity) {
            return $entity->getId();
        })->toArray();
    }

    /**
     * @return Company[]
     */
    public function getSponsors()
    {
        return $this->sponsors;
    }

    /**
     * @param Company $sponsor
     */
    public function addSponsor(Company $sponsor)
    {
        $this->sponsors->add($sponsor);
    }

    public function clearSponsors()
    {
        $this->sponsors->clear();
    }

    public function addFeedBack(SummitEventFeedback $feedback)
    {
        $this->feedback->add($feedback);
        $feedback->setEvent($this);
    }

    /**
     * @return SummitEventFeedback[]
     */
    public function getFeedback()
    {
        $criteria = Criteria::create();
        $criteria = $criteria->orderBy(['created' => Criteria::DESC]);
        return $this->feedback->matching($criteria);
    }

    /**
     * @return ArrayCollection
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @param Tag $tag
     */
    public function addTag(Tag $tag)
    {
        if ($this->tags->contains($tag)) return;
        $this->tags->add($tag);
    }

    public function clearTags()
    {
        $this->tags->clear();
    }

    /**
     * @return void
     * @throws ValidationException
     */
    public function publish()
    {
        Log::debug(sprintf("SummitEvent::Publish id %s", $this->id));

        if ($this->isPublished())
            throw new ValidationException('Already published Summit Event.');

        $summit = $this->getSummit();

        if (is_null($summit))
            throw new ValidationException('To publish you must assign a summit.');

        if($this->type->isAllowsPublishingDates()) {

            $start_date = $this->getStartDate();
            $end_date = $this->getEndDate();

            if ((is_null($start_date) || is_null($end_date)))
                throw new ValidationException('To publish this event you must define a start/end datetime.');

            $timezone = $summit->getTimeZoneId();

            if (empty($timezone)) {
                throw new ValidationException('Invalid Summit TimeZone.');
            }

            Log::debug
            (
                sprintf
                (
                    "SummitEvent::Publish id %s start date %s end date %s",
                    $this->id,
                    $start_date->getTimestamp(),
                    $end_date->getTimestamp()
                )
            );

            if ($end_date->getTimestamp() < $start_date->getTimestamp())
                throw new ValidationException('start datetime must be lower or equal than end datetime.');
        }

        $this->published = true;
        $this->published_date = new DateTime();
    }

    /**
     * @return bool
     */
    public function isPublished():bool
    {
        return $this->getPublished();
    }

    /**
     * @return bool
     */
    public function getPublished()
    {
        return (bool)$this->published;
    }

    /**
     * @return \DateTime|null
     */
    public function getStartDate():?DateTime
    {
        $type = $this->type;
        return  !is_null($type) && $type->isAllowsPublishingDates() ? $this->start_date: null;
    }

    /**
     * @param DateTime $value
     * @return $this
     * @throws ValidationException
     */
    public function setStartDate(DateTime $value)
    {
        Log::debug(sprintf("SummitEvent::setStartDate id %s value %s", $this->id, $value->getTimestamp()));
        if(!$this->type->isAllowsPublishingDates()){
            throw new ValidationException("Type does not allows Publishing Period.");
        }
        $summit = $this->getSummit();
        if (!is_null($summit)) {
            $value = $summit->convertDateFromTimeZone2UTC($value);
        }
        $end_date = $this->getEndDate();
        if (!is_null($end_date)) {
            $this->duration = $end_date->getTimestamp() - $value->getTimestamp();
            $this->duration = $this->duration < 0 ? 0 : $this->duration;
        }

        $this->start_date = $value;
        Log::debug(sprintf("SummitEvent::setStartDate id %s start_date %s", $this->id, $this->start_date->getTimestamp()));
        return $this;
    }

    /**
     * @param DateTime $value
     * @throws ValidationException
     */
    public function setRawStartDate(DateTime $value){
        if(!$this->type->isAllowsPublishingDates()){
            throw new ValidationException("Type does not allows Publishing Period.");
        }
        $end_date = $this->getEndDate();
        if (!is_null($end_date)) {
            $this->duration = $end_date->getTimestamp() - $value->getTimestamp();
        }
        $this->start_date = $value;
    }

    /**
     * @return \DateTime|null
     */
    public function getEndDate():?DateTime
    {
        $type = $this->type;
        return  !is_null($type) && $type->isAllowsPublishingDates() ? $this->end_date: null;
    }

    /**
     * @param DateTime $value
     * @return $this
     * @throws ValidationException
     */
    public function setEndDate(DateTime $value)
    {
        Log::debug(sprintf("SummitEvent::setEndDate id %s value %s", $this->id, $value->getTimestamp()));

        if(!$this->type->isAllowsPublishingDates()){
            throw new ValidationException("Type does not allows Publishing Period.");
        }
        $summit = $this->getSummit();
        if (!is_null($summit)) {
            $value = $summit->convertDateFromTimeZone2UTC($value);
        }
        $start_date = $this->getStartDate();
        if (!is_null($start_date)) {
            $this->duration = $value->getTimestamp() - $start_date->getTimestamp();
        }
        $this->end_date = $value;

        Log::debug(sprintf("SummitEvent::setEndDate id %s end_date %s", $this->id, $this->end_date->getTimestamp()));
        return $this;
    }

    public function setRawEndDate(DateTime $value){
        if(!$this->type->isAllowsPublishingDates()){
            throw new ValidationException("Type does not allows Publishing Period.");
        }
        $this->end_date = $value;
    }
    /**
     * @return void
     */
    public function unPublish()
    {
        $this->published = false;
        $this->published_date = null;
    }

    /**
     * @ORM\PreRemove:
     */
    public function deleting($args)
    {
        $this->pre_remove_events = new PreRemoveEventArgs
        (
            [
                'id' => $this->id,
                'class_name' => $this->getClassName(),
                'summit' => $this->summit,
                'published' => $this->isPublished(),
            ]
        );
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        return "SummitEvent";
    }

    /**
     * @ORM\preRemove:
     */
    public function deleted($args)
    {
        if (is_null($this->summit)) return;
        if ($this->summit->isDeleting()) return;
        Event::dispatch(new SummitEventDeleted(null, $this->pre_remove_events));
        $this->pre_remove_events = null;
    }

    /**
     * @ORM\PreUpdate:
     */
    public function updating(PreUpdateEventArgs $args)
    {
        $this->pre_update_args = $args;
    }

    /**
     * @ORM\PostUpdate:
     */
    public function updated($args)
    {
        Event::dispatch(new SummitEventUpdated($this, $this->pre_update_args));
        $this->pre_update_args = null;
    }

    // events

    /**
     * @ORM\PostPersist
     */
    public function inserted($args)
    {
        Event::dispatch(new SummitEventCreated($this, $args));
    }

    /**
     * @return ArrayCollection
     */
    public function getRsvp()
    {
        return $this->rsvp;
    }

    /**
     * @param ArrayCollection $rsvp
     */
    public function setRsvp($rsvp)
    {
        $this->rsvp = $rsvp;
    }

    /**
     * @return string
     */
    public function getLocationName()
    {
        return $this->hasLocation() ? $this->location->getName() : 'TBD';
    }

    /**
     * @return bool
     */
    public function hasLocation()
    {
        return $this->getLocationId() > 0;
    }

    /**
     * @return int
     */
    public function getLocationId()
    {
        try {
            return !is_null($this->location) ? $this->location->getId() : 0;
        } catch (\Exception $ex) {
            return 0;
        }
    }

    /**
     * @return int
     */
    public function getRSVPMaxUserNumber()
    {
        return $this->rsvp_max_user_number;
    }

    /**
     * @param int $rsvp_max_user_number
     */
    public function setRSVPMaxUserNumber($rsvp_max_user_number)
    {
        $this->rsvp_max_user_number = $rsvp_max_user_number;
    }

    /**
     * @return mixed
     */
    public function getRSVPMaxUserWaitListNumber()
    {
        return $this->rsvp_max_user_wait_list_number;
    }

    /**
     * @param mixed $rsvp_max_user_wait_list_number
     */
    public function setRSVPMaxUserWaitListNumber($rsvp_max_user_wait_list_number)
    {
        $this->rsvp_max_user_wait_list_number = $rsvp_max_user_wait_list_number;
    }

    /**
     * @return string
     */
    public function getOccupancy()
    {
        return $this->occupancy;
    }

    /**
     * @param string $occupancy
     */
    public function setOccupancy($occupancy)
    {
        $this->occupancy = $occupancy;
    }

    /**
     * @return string
     */
    public function getExternalId(): ?string
    {
        return $this->external_id;
    }

    /**
     * @param string $external_id
     */
    public function setExternalId(string $external_id): void
    {
        $this->external_id = $external_id;
    }

    /**
     * @return string
     * @throws ValidationException
     */
    public function getCurrentRSVPSubmissionSeatType(): string
    {

        if (!$this->hasRSVPTemplate())
            throw new ValidationException(sprintf("Event %s has not RSVP configured.", $this->id));

        if (!$this->getRSVPTemplate()->isEnabled()) {
            throw new ValidationException(sprintf("Event %s has not RSVP configured.", $this->id));
        }

        $count_regular = $this->getRSVPSeatTypeCount(RSVP::SeatTypeRegular);
        if ($count_regular < intval($this->rsvp_max_user_number)) return RSVP::SeatTypeRegular;
        $count_wait = $this->getRSVPSeatTypeCount(RSVP::SeatTypeWaitList);
        if ($count_wait < intval($this->rsvp_max_user_wait_list_number)) return RSVP::SeatTypeWaitList;
        throw new ValidationException(sprintf("Event %s is Full.", $this->id));
    }

    /**
     * @return RSVPTemplate
     */
    public function getRSVPTemplate()
    {
        return $this->rsvp_template;
    }

    /**
     * @param RSVPTemplate $rsvp_template
     */
    public function setRSVPTemplate(RSVPTemplate $rsvp_template)
    {
        $this->rsvp_template = $rsvp_template;
        $this->rsvp_link = '';
    }

    /**
     * @param string $seat_type
     * @return int
     */
    public function getRSVPSeatTypeCount(string $seat_type): int
    {
        $criteria = Criteria::create();
        $criteria = $criteria->where(Criteria::expr()->eq('seat_type', $seat_type));
        return $this->rsvp->matching($criteria)->count();
    }

    /**
     * @param string $seat_type
     * @return bool
     */
    public function couldAddSeatType(string $seat_type): bool
    {
        switch ($seat_type) {
            case RSVP::SeatTypeRegular:
            {
                $count_regular = $this->getRSVPSeatTypeCount(RSVP::SeatTypeRegular);
                return $count_regular < intval($this->rsvp_max_user_number);
            }
            case RSVP::SeatTypeWaitList:
            {
                $count_wait = $this->getRSVPSeatTypeCount(RSVP::SeatTypeWaitList);
                return $count_wait < intval($this->rsvp_max_user_wait_list_number);
            }
        }
        return false;
    }

    public function getRSVPRegularCount(): ?int
    {
        return $this->getRSVPSeatTypeCount(RSVP::SeatTypeRegular);
    }

    public function getRSVPWaitCount(): ?int
    {
        return $this->getRSVPSeatTypeCount(RSVP::SeatTypeWaitList);
    }

    /**
     * @param RSVP $rsvp
     * @throws ValidationException
     */
    public function addRSVPSubmission(RSVP $rsvp)
    {
        if (!$this->hasRSVPTemplate()) {
            throw new ValidationException(sprintf("Event %s has not RSVP configured.", $this->id));
        }

        if (!$this->getRSVPTemplate()->isEnabled()) {
            throw new ValidationException(sprintf("Event %s has not RSVP configured.", $this->id));
        }

        if ($this->rsvp->contains($rsvp)) return;
        $this->rsvp->add($rsvp);
        $rsvp->setEvent($this);
    }

    /**
     * @param RSVP $rsvp
     */
    public function removeRSVPSubmission(RSVP $rsvp)
    {
        if (!$this->rsvp->contains($rsvp)) return;
        $this->rsvp->removeElement($rsvp);
        $rsvp->clearEvent();
    }

    /**
     * @return string
     */
    public function getDateNice(): string
    {
        $start_date = $this->getStartDateNice();
        $end_date = $this->getEndDateNice();
        $date_nice = '';

        if ($start_date == 'TBD' || $end_date == 'TBD') return $start_date;

        $date_nice = date('l, F j, g:ia', strtotime($start_date)) . '-' . date('g:ia', strtotime($end_date));
        return $date_nice;
    }

    /**
     * @return string
     */
    public function getStartDateNice(): string
    {
        $start_date = $this->getLocalStartDate();
        if (empty($start_date)) return 'TBD';
        return $start_date->format("Y-m-d H:i:s");
    }

    /**
     * @return DateTime|null
     */
    public function getLocalStartDate()
    {
        if (!empty($this->start_date)) {
            $value = clone $this->start_date;
            $summit = $this->getSummit();
            if (!is_null($summit)) {
                $res = $summit->convertDateFromUTC2TimeZone($value);
            }
            return $res;
        }
        return null;
    }

    /**
     * @return string
     */
    public function getEndDateNice(): string
    {
        $end_date = $this->getLocalEndDate();
        if (empty($end_date)) return 'TBD';
        return $end_date->format("Y-m-d H:i:s");
    }

    /**
     * @return DateTime|null
     */
    public function getLocalEndDate()
    {
        if (!empty($this->end_date)) {
            $value = clone $this->end_date;
            $summit = $this->getSummit();
            if (!is_null($summit)) {
                $res = $summit->convertDateFromUTC2TimeZone($value);
            }
            return $res;
        }
        return null;
    }

    /**
     * @return bool
     */
    public function isLive(): bool
    {
        return !empty($this->streaming_url);
    }

    /**
     * @return string
     */
    public function getStreamingUrl(): ?string
    {
        return $this->streaming_url;
    }

    /**
     * @param string $streaming_url
     */
    public function setStreamingUrl(?string $streaming_url): void
    {
        $this->streaming_url = $streaming_url;
    }

    /**
     * @return string
     */
    public function getEtherpadLink(): ?string
    {
        return $this->etherpad_link;
    }

    /**
     * @param string $etherpad_link
     */
    public function setEtherpadLink(?string $etherpad_link): void
    {
        $this->etherpad_link = $etherpad_link;
    }

    /**
     * @return string
     */
    public function getMeetingUrl(): ?string
    {
        return $this->meeting_url;
    }

    /**
     * @param string $meeting_url
     */
    public function setMeetingUrl(string $meeting_url): void
    {
        $this->meeting_url = $meeting_url;
    }

    /**
     * @return int
     */
    public function getTotalAttendanceCount():int{
        return $this->attendance_metrics->count();
    }

    public function getAttendance(){
        return $this->attendance_metrics;
    }

    /**
     * @return int
     */
    public function getCurrentAttendanceCount():int{
        $criteria = Criteria::create();
        $criteria = $criteria->where(Criteria::expr()->isNull('outgress_date'));
        return $this->attendance_metrics->matching($criteria)->count();
    }

    public function getCurrentAttendance(){
        $criteria = Criteria::create();
        $criteria = $criteria->where(Criteria::expr()->isNull('outgress_date'));
        $criteria = $criteria->orderBy(['created' => Criteria::DESC]);
        return $this->attendance_metrics->matching($criteria)->toArray();
    }

    use HasImageTrait;

    /**
     * @param Member|null $member
     * @return bool
     */
    public function hasAccess(?Member $member):bool{

        if(is_null($member)) {
            Log::debug("SummitEvent::hasAccess member is null");
            return false;
        }

        Log::debug(sprintf("SummitEvent::hasAccess member %s (%s) event %s.",$member->getId(), $member->getEmail(), $this->id));
        if($this->summit->isPubliclyOpen()) {
            Log::debug(sprintf("SummitEvent::hasAccess member %s (%s) event %s summit is public open.",$member->getId(), $member->getEmail(), $this->id));
            return true;
        }

        if($member->isAdmin() || $this->summit->isSummitAdmin($member) || $member->isTester()){
            Log::debug(sprintf("SummitEvent::hasAccess member %s (%s) event %s is SuperAdmnin/Admin/SummitAdmin or Tester.",$member->getId(), $member->getEmail(), $this->id));
            return true;
        }

        if($member->hasPaidTicketOnSummit($this->summit)){
            if($this->category->getAllowedAccessLevels()->count() > 0){
                $eventAccessLevelsIds = $this->category->getAllowedAccessLevelsIds();
                Log::debug
                (
                    sprintf
                    (
                        "SummitEvent::hasAccess member %s (%s) event %s has set access levels event access levels (%s).",
                        $member->getId(),
                        $member->getEmail(),
                        $this->id,
                        implode(",", $eventAccessLevelsIds)
                    )
                );
                // for each ticket check if we have the required access levels
                foreach($member->getPaidSummitTickets($this->summit) as $ticket){
                    $ticketAccessLevelsIds = $ticket->getBadgeAccessLevelsIds();
                    Log::debug(sprintf("SummitEvent::hasAccess checking access levels for ticket %s ticket access levels (%s).", $ticket->getId(), implode(",", $ticketAccessLevelsIds)));
                    if(count(array_intersect($eventAccessLevelsIds, $ticketAccessLevelsIds))) return true;
                }
                Log::debug(sprintf("SummitEvent::hasAccess member %s (%s) event %s has no access.",$member->getId(), $member->getEmail(), $this->id));
                return false;
            }
            Log::debug(sprintf("SummitEvent::hasAccess member %s (%s) event %s has a paid ticket.",$member->getId(), $member->getEmail(), $this->id));
            return true;
        }
        Log::debug(sprintf("SummitEvent::hasAccess member %s (%s) event %s has no access.",$member->getId(), $member->getEmail(), $this->id));

        return false;
    }

    /**
     * @return bool
     */
    public function isMuxStream():bool{
        if(empty($this->streaming_url)) return false;
        if (preg_match("/(.*\.mux\.com)/i", $this->streaming_url)) return true;
        return false;
    }

    /**
     * @return string|null
     */
    public function getStreamThumbnailUrl():?string{
        if($this->isMuxStream()){
            $matches = [];
            if(preg_match("/^(.*\.mux\.com)\/(.*)(\.m3u8)$/",$this->streaming_url, $matches)){
                return sprintf("https://image.mux.com/%s/thumbnail.jpg", $matches[2]);
            }
        }
        return null;
    }

    /**
     * @return string
     */
    public function getMuxAssetId(): ?string
    {
        return $this->mux_asset_id;
    }

    /**
     * @param string $mux_asset_id
     */
    public function setMuxAssetId(string $mux_asset_id): void
    {
        $this->mux_asset_id = $mux_asset_id;
    }

    /**
     * @return string
     */
    public function getMuxPlaybackId(): ?string
    {
        return $this->mux_playback_id;
    }

    /**
     * @param string $mux_playback_id
     */
    public function setMuxPlaybackId(string $mux_playback_id): void
    {
        $this->mux_playback_id = $mux_playback_id;
    }

    /**
     * @return string
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * @param string $level
     * @throws ValidationException
     */
    public function setLevel(string $level):void
    {
        if(!in_array($level, ISummitEventLevel::ValidLevels))
            throw new ValidationException(sprintf("Level %s is invalid.", $level));
        $this->level = $level;
    }

    /**
     * @return Member|null
     */
    public function getCreatedBy(): ?Member
    {
        return $this->created_by;
    }

    /**
     * @param Member $created_by
     */
    public function setCreatedBy(Member $created_by): void
    {
        $this->created_by = $created_by;
    }

    /**
     * @return Member
     */
    public function getUpdatedBy(): ?Member
    {
        return $this->updated_by;
    }

    /**
     * @param Member $updated_by
     */
    public function setUpdatedBy(Member $updated_by): void
    {
        $this->updated_by = $updated_by;
    }

    public function getStreamingType():?string{
        return $this->streaming_type;
    }

    /**
     * @param string $streaming_type
     * @throws ValidationException
     */
    public function setStreamingType(string $streaming_type):void{
        if(!in_array($streaming_type, self::ValidStreamingTypes))
            throw new ValidationException(sprintf("%s is not a valid streaming type", $streaming_type));
        $this->streaming_type = $streaming_type;
    }

    /**
     * @return bool
     */
    public function isShowSponsors(): bool
    {
        return $this->show_sponsors;
    }

    /**
     * @param bool $show_sponsors
     */
    public function setShowSponsors(bool $show_sponsors): void
    {
        $this->show_sponsors = $show_sponsors;
    }

    /**
     * @return int
     */
    public function getDuration(): ?int
    {
        if(!$this->duration && !is_null($this->start_date) && !is_null(!is_null($this->end_date))){
            $this->duration = $this->end_date->getTimestamp() - $this->start_date->getTimestamp();
        }
        return $this->duration;
    }

    /**
     * @param int $duration_in_seconds
     * @throws ValidationException
     */
    public function setDuration(int $duration_in_seconds): void
    {
        if(!$this->type->isAllowsPublishingDates()){
            throw new ValidationException("Type does not allows Publishing Period.");
        }

        if($duration_in_seconds <= 0 ){
            throw new ValidationException('Duration should be greater than zero.');
        }

        if($duration_in_seconds < (self::MIN_EVENT_MINUTES * 60)){
            throw new ValidationException(sprintf('Duration should be greater than %s.',self::MIN_EVENT_MINUTES));
        }

        $this->duration = $duration_in_seconds;
        $start_date = $this->getStartDate();
        if (!is_null($start_date)) {

            $start_date = clone $start_date;
            $value = $start_date->add(new \DateInterval('PT'.$duration_in_seconds.'S'));
            $summit = $this->getSummit();

            if(!is_null($summit)){
                $value = $summit->convertDateFromUTC2TimeZone($value);
            }

            $this->setEndDate($value);
        }
    }
}