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

use App\Jobs\CreateMUXURLSigningKeyForSummit;
use App\Models\Foundation\Summit\Events\RSVP\RSVPTemplate;
use App\Models\Foundation\Summit\Events\SummitEventTypeConstants;
use App\Models\Foundation\Summit\IPublishableEvent;
use App\Models\Foundation\Summit\ScheduleEntity;
use App\Models\Foundation\Summit\TimeDurationRestrictedEvent;
use App\Models\Utils\Traits\HasImageTrait;
use Cocur\Slugify\Slugify;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use libs\utils\CacheRegions;
use libs\utils\MUXUtils;
use models\exceptions\ValidationException;
use models\main\Company;
use models\main\File;
use models\main\Member;
use models\main\Tag;
use models\utils\One2ManyPropertyTrait;
use models\utils\SilverstripeBaseModel;
use Random\RandomException;

/**
 * @package models\summit
 */
#[ORM\Table(name: 'SummitEvent')]
#[ORM\Entity(repositoryClass: \App\Repositories\Summit\DoctrineSummitEventRepository::class)]
#[ORM\AssociationOverrides([new ORM\AssociationOverride(name: 'summit', inversedBy: 'events')])]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'ClassName', type: 'string')]
#[ORM\HasLifecycleCallbacks]
#[ORM\DiscriminatorMap(['SummitEvent' => 'SummitEvent', 'Presentation' => 'Presentation', 'SummitGroupEvent' => 'SummitGroupEvent', 'SummitEventWithFile' => 'SummitEventWithFile'])] // Class SummitEvent
class SummitEvent extends SilverstripeBaseModel implements IPublishableEvent
{
    /**
     *  minimun number of minutes that an event must last
     */
    const MIN_EVENT_MINUTES = 1;

    const JWT_TTL = 60 * 60 * 6; // secs

    const TTL_SKEW = 60; // secs

    use One2ManyPropertyTrait;

    use TimeDurationRestrictedEvent;

    use StreamableEventTrait;

    const ClassName = 'SummitEvent';

    protected $getIdMappings = [
        'getCreatedById' => 'created_by',
        'getUpdatedById' => 'updated_by',
    ];

    protected $hasPropertyMappings = [
        'hasCreatedBy' => 'created_by',
        'hasUpdatedBy' => 'updated_by',
    ];

    const FieldTitle = 'title';
    const FieldAbstract = 'description';
    const FieldSocialDescription = 'social_description';
    const FieldLevel = 'level';
    const FieldTrack = 'track_id';
    const FieldType = 'type_id';

    const AllowedEditableFields = [
        self::FieldTitle,
        self::FieldAbstract,
        self::FieldSocialDescription,
        self::FieldLevel,
        self::FieldTrack,
    ];

    const AllowedFields = [
        self::FieldAbstract,
        self::FieldSocialDescription,
        self::FieldLevel,
    ];

    /**
     * @var string
     */
    #[ORM\Column(name: 'Title', type: 'string')]
    protected $title;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Abstract', type: 'string')]
    protected $abstract;

    /**
     * @var string
     */
    #[ORM\Column(name: 'SocialSummary', type: 'string')]
    protected $social_summary;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Occupancy', type: 'string')]
    protected $occupancy;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Level', type: 'string')]
    protected $level;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'StartDate', type: 'datetime')]
    protected $start_date;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'EndDate', type: 'datetime')]
    protected $end_date;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'Published', type: 'boolean')]
    protected $published;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'PublishedDate', type: 'datetime')]
    protected $published_date;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'AllowFeedBack', type: 'boolean')]
    protected $allow_feedback;

    /**
     * @var float
     */
    #[ORM\Column(name: 'AvgFeedbackRate', type: 'float')]
    protected $avg_feedback;

    /**
     * @var int
     */
    #[ORM\Column(name: 'HeadCount', type: 'integer')]
    protected $head_count;

    /**
     * @var int
     */
    #[ORM\Column(name: 'RSVPMaxUserNumber', type: 'integer')]
    protected $rsvp_max_user_number;

    /**
     * @var int
     */
    #[ORM\Column(name: 'RSVPMaxUserWaitListNumber', type: 'integer')]
    protected $rsvp_max_user_wait_list_number;

    /**
     * @var RSVPTemplate
     */
    #[ORM\JoinColumn(name: 'RSVPTemplateID', referencedColumnName: 'ID', onDelete: 'SET NULL')]
    #[ORM\ManyToOne(targetEntity: \App\Models\Foundation\Summit\Events\RSVP\RSVPTemplate::class, fetch: 'EXTRA_LAZY')]
    protected $rsvp_template;

    /**
     * @var RSVP[]
     */
    #[ORM\OneToMany(targetEntity: \models\summit\RSVP::class, mappedBy: 'event', cascade: ['persist'], fetch: 'EXTRA_LAZY')]
    protected $rsvp;

    /**
     * @var string
     */
    #[ORM\Column(name: 'RSVPLink', type: 'string')]
    protected $rsvp_link;

    /**
     * @var string
     */
    #[ORM\Column(name: 'ExternalId', type: 'string')]
    protected $external_id;

    /**
     * @var PresentationCategory
     */
    #[ORM\JoinColumn(name: 'CategoryID', referencedColumnName: 'ID', onDelete: 'SET NULL')]
    #[ORM\ManyToOne(targetEntity: \PresentationCategory::class, fetch: 'EXTRA_LAZY', cascade: ['persist'])]
    protected $category = null;

    /**
     * @var SummitEventType
     */
    #[ORM\JoinColumn(name: 'TypeID', referencedColumnName: 'ID', onDelete: 'SET NULL')]
    #[ORM\ManyToOne(targetEntity: \SummitEventType::class, fetch: 'EXTRA_LAZY', cascade: ['persist'])]
    protected $type;

    /**
     * @var SummitAbstractLocation
     */
    #[ORM\JoinColumn(name: 'LocationID', referencedColumnName: 'ID', onDelete: 'SET NULL')]
    #[ORM\ManyToOne(targetEntity: \SummitAbstractLocation::class, fetch: 'EXTRA_LAZY', cascade: ['persist'], inversedBy: 'events')]
    protected $location = null;

    #[ORM\JoinTable(name: 'SummitEvent_Sponsors')]
    #[ORM\JoinColumn(name: 'SummitEventID', referencedColumnName: 'ID', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'CompanyID', referencedColumnName: 'ID', onDelete: 'CASCADE')]
    #[ORM\ManyToMany(targetEntity: \models\main\Company::class, inversedBy: 'sponsorships', fetch: 'EXTRA_LAZY')]
    protected $sponsors;

    /**
     * @var SummitEventFeedback[]
     */
    #[ORM\OneToMany(targetEntity: \models\summit\SummitEventFeedback::class, mappedBy: 'event', cascade: ['persist'], fetch: 'EXTRA_LAZY')]
    #[ORM\Cache('NONSTRICT_READ_WRITE')]
    protected $feedback;

    #[ORM\JoinTable(name: 'SummitEvent_Tags')]
    #[ORM\JoinColumn(name: 'SummitEventID', referencedColumnName: 'ID')]
    #[ORM\InverseJoinColumn(name: 'TagID', referencedColumnName: 'ID')]
    #[ORM\ManyToMany(targetEntity: \models\main\Tag::class, cascade: ['persist'], inversedBy: 'events', fetch: 'EXTRA_LAZY')]
    protected $tags;

    /**
     * @var SummitEventAttendanceMetric[]
     */
    #[ORM\OneToMany(targetEntity: \models\summit\SummitEventAttendanceMetric::class, mappedBy: 'event', cascade: ['persist', 'remove'], orphanRemoval: true, fetch: 'EXTRA_LAZY')]
    protected $attendance_metrics;

    /**
     * @var string
     */
    #[ORM\Column(name: 'StreamingUrl', type: 'string')]
    protected $streaming_url;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'StreamIsSecure', type: 'boolean')]
    protected $stream_is_secure;


    const STREAMING_TYPE_LIVE = 'LIVE';
    const STREAMING_TYPE_VOD = 'VOD';

    const ValidStreamingTypes = [self::STREAMING_TYPE_LIVE, self::STREAMING_TYPE_VOD];

    /**
     * @var string
     */
    #[ORM\Column(name: 'StreamingType', type: 'string')]
    protected $streaming_type;

    /**
     * @var string
     */
    #[ORM\Column(name: 'MuxPlaybackID', type: 'string')]
    protected $mux_playback_id;

    /**
     * @var string
     */
    #[ORM\Column(name: 'MuxAssetID', type: 'string')]
    protected $mux_asset_id;

    /**
     * @var string
     */
    #[ORM\Column(name: 'EtherpadLink', type: 'string')]
    protected $etherpad_link;

    /**
     * @var string
     */
    #[ORM\Column(name: 'MeetingUrl', type: 'string')]
    protected $meeting_url;

    /**
     * @var File
     */
    #[ORM\JoinColumn(name: 'ImageID', referencedColumnName: 'ID')]
    #[ORM\ManyToOne(targetEntity: \models\main\File::class, cascade: ['persist'])]
    protected $image;

    /**
     * @var Member
     */
    #[ORM\JoinColumn(name: 'CreatedByID', referencedColumnName: 'ID', onDelete: 'SET NULL')]
    #[ORM\ManyToOne(targetEntity: \models\main\Member::class, fetch: 'EXTRA_LAZY', inversedBy: 'created_presentations')]
    protected $created_by = null;

    /**
     * @var Member
     */
    #[ORM\JoinColumn(name: 'UpdatedByID', referencedColumnName: 'ID', onDelete: 'SET NULL')]
    #[ORM\ManyToOne(targetEntity: \models\main\Member::class, fetch: 'EXTRA_LAZY')]
    protected $updated_by = null;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'ShowSponsors', type: 'boolean')]
    protected $show_sponsors;

    /**
     * @var int
     */
    #[ORM\Column(name: 'DurationInSeconds', type: 'integer')]
    protected $duration;

    #[ORM\JoinTable(name: 'SummitEvent_SummitTicketType')]
    #[ORM\JoinColumn(name: 'SummitEventID', referencedColumnName: 'ID')]
    #[ORM\InverseJoinColumn(name: 'SummitTicketTypeID', referencedColumnName: 'ID')]
    #[ORM\ManyToMany(targetEntity: \models\summit\SummitTicketType::class, cascade: ['persist'], fetch: 'EXTRA_LAZY')]
    protected $allowed_ticket_types;


    const SOURCE_ADMIN = 'Admin';
    const SOURCE_SUBMISSION = 'Submission';

    const ValidSubmissionSources = [self::SOURCE_SUBMISSION, self::SOURCE_ADMIN];

     /**
     * @var string
     */
    #[ORM\Column(name: 'SubmissionSource', type: 'string')]
    protected $submission_source;

     /**
     * @var string
     */
    #[ORM\Column(name: 'OverflowStreamingUrl', type: 'string')]
    protected $overflow_streaming_url;

     /**
     * @var bool
     */
    #[ORM\Column(name: 'OverflowStreamIsSecure', type: 'boolean')]
    protected $overflow_stream_is_secure;

    /**
     * @var string
     */
    #[ORM\Column(name: 'OverflowStreamKey', type: 'string')]
    protected $overflow_stream_key;

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
        $this->stream_is_secure = false;
        $this->overflow_stream_is_secure = false;
        $this->allowed_ticket_types = new ArrayCollection();
        $this->submission_source = SummitEvent::SOURCE_ADMIN;
    }

    use SummitOwned;

    /**
     * @return string
     */
    public function getTitle(): string
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
    public function getCategory():?PresentationCategory
    {
        return $this->category;
    }

    /**
     * @param PresentationCategory $category
     * @return $this
     */
    public function setCategory(PresentationCategory $category)
    {
        if (!$category->isLeaf())
            throw new ValidationException("Only leaf tracks can be assigned to activities.");

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

    public function hasCategory(): bool
    {
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
    public function getPublishedDate(): ?DateTime
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
    public function getType(): ?SummitEventType
    {
        return $this->type;
    }

    public function hasType(): bool
    {
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

    public function clearPublishingDates(): void
    {
        $this->start_date = null;
        $this->end_date = null;
    }

    /**
     * @return SummitAbstractLocation|null
     */
    public function getLocation(): ?SummitAbstractLocation
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
        if (!$this->type->isAllowsLocation())
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
     * @param SummitEventFeedback $feedback
     */
    public function removeFeedback(SummitEventFeedback $feedback)
    {
        if (!$this->feedback->contains($feedback)) return;
        $this->feedback->removeElement($feedback);
        $feedback->clearOwner();
        $feedback->clearEvent();
    }

    /**
     * @param int $feedback_id
     * @return SummitEventFeedback|null
     */
    public function getFeedbackById(int $feedback_id): ?SummitEventFeedback
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', $feedback_id));
        $feedback = $this->feedback->matching($criteria)->first();
        return $feedback === false ? null : $feedback;
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

        if ($this->type->isAllowsPublishingDates()) {

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
    public function isPublished(): bool
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
    public function getStartDate(): ?DateTime
    {
        $type = $this->type;
        return !is_null($type) && $type->isAllowsPublishingDates() ? $this->start_date : null;
    }

    /**
     * @param DateTime $value
     * @return $this
     * @throws ValidationException
     */
    public function setStartDate(DateTime $value): SummitEvent
    {
        Log::debug(sprintf("SummitEvent::setStartDate id %s value %s", $this->id, $value->getTimestamp()));
        if (!$this->type->isAllowsPublishingDates()) {
            throw new ValidationException("Type does not allows Publishing Period.");
        }
        $this->_setStartDate($value, $this->getSummit());
        return $this;
    }

    /**
     * @param DateTime $value
     * @throws ValidationException
     */
    public function setRawStartDate(DateTime $value)
    {
        if (!$this->type->isAllowsPublishingDates()) {
            throw new ValidationException("Type does not allows Publishing Period.");
        }
        $end_date = $this->getEndDate();
        if (!is_null($end_date)) {
            $this->duration = $end_date->getTimestamp() - $value->getTimestamp();
            $this->duration = $this->duration < 0 ? 0 : $this->duration;
        }
        $this->start_date = $value;
    }

    /**
     * @return \DateTime|null
     */
    public function getEndDate(): ?DateTime
    {
        $type = $this->type;
        return !is_null($type) && $type->isAllowsPublishingDates() ? $this->end_date : null;
    }

    /**
     * @param DateTime $value
     * @return $this
     * @throws ValidationException
     */
    public function setEndDate(DateTime $value): SummitEvent
    {
        Log::debug(sprintf("SummitEvent::setEndDate id %s value %s", $this->id, $value->getTimestamp()));
        if (!$this->type->isAllowsPublishingDates()) {
            throw new ValidationException("Type does not allows Publishing Period.");
        }
        $this->_setEndDate($value, $this->getSummit());
        return $this;
    }

    public function setRawEndDate(DateTime $value)
    {
        if (!$this->type->isAllowsPublishingDates()) {
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
     * @return string
     */
    public function getClassName(): string
    {
        return "SummitEvent";
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
    public function getLocationName(): string
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
    public function getOccupancy():?string
    {
        return $this->occupancy;
    }

    const OccupancyEmpty = 'EMPTY';
    const Occupancy25_Percent = '25%';
    const Occupancy50_Percent = '50%';
    const Occupancy75_Percent = '75%';
    const OccupancyFull = 'FULL';

    const OccupancyOverflow = 'OVERFLOW';

    const ValidOccupanciesValues = [
        self::OccupancyEmpty,
        self::Occupancy25_Percent,
        self::Occupancy50_Percent,
        self::Occupancy75_Percent,
        self::OccupancyFull,
        self::OccupancyOverflow,
    ];
    /**
     * @param string $occupancy
     */
    public function setOccupancy(string $occupancy)
    {
        $occupancy = trim($occupancy);
        if(empty($occupancy)) return;

        if(!in_array($occupancy, self::ValidOccupanciesValues))
            throw new ValidationException(sprintf("occupancy %s is not valid", $occupancy));
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
    public function getLocalStartDate():?DateTime
    {
        $summit = $this->getSummit();
        return $this->_getLocalStartDate($summit);
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
    public function getLocalEndDate():?DateTime
    {
        $summit = $this->getSummit();
        return $this->_getLocalEndDate($summit);
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

    public function getStreamDuration():int{
        // TODO: Implement getStreamDuration() method.
        return 0;
    }

    /**
     * @param string $streaming_url
     */
    public function setStreamingUrl(?string $streaming_url): void
    {
        $this->streaming_url = $streaming_url;
        $key = $this->getSecureStreamCacheKey();
        if(Cache::tags(sprintf('secure_streams_%s',$this->summit->getId()))->has($key))
            Cache::tags(sprintf('secure_streams_%s',$this->summit->getId()))->forget($key);
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
    public function getTotalAttendanceCount(): int
    {
        return $this->attendance_metrics->count();
    }

    public function getAttendance()
    {
        return $this->attendance_metrics;
    }

    /**
     * @return int
     */
    public function getCurrentAttendanceCount(): int
    {
        $criteria = Criteria::create();
        $criteria = $criteria->where(Criteria::expr()->isNull('outgress_date'));
        return $this->attendance_metrics->matching($criteria)->count();
    }

    public function getCurrentAttendance()
    {
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
    public function hasAccess(?Member $member): bool
    {
        Log::debug(sprintf("SummitEvent::hasAccess event %s member %s", $this->id, is_null($member) ? 'TBD': $member->getId()));
        $ttl = Config::get("cache_api_response.event_has_access_lifetime", 600);
        $cache_key = sprintf("event_has_access_%s", $member->getId());
        $res = Cache::tags(CacheRegions::getCacheRegionForSummitEvent($this->getId()))->get($cache_key);
        if(!is_null($res)) {
            Log::debug(sprintf("SummitEvent::hasAccess cache hit for member %s (%s) event %s res %b", $member->getId(), $member->getEmail(), $this->id, $res));
            return $res;
        }

        if (is_null($member)) {
            Log::debug("SummitEvent::hasAccess member is null");
            Cache::tags(CacheRegions::getCacheRegionForSummitEvent($this->getId()))->set($cache_key, false, $ttl);
            return false;
        }

        Log::debug(sprintf("SummitEvent::hasAccess member %s (%s) event %s.", $member->getId(), $member->getEmail(), $this->id));
        if ($this->summit->isPubliclyOpen()) {
            Log::debug(sprintf("SummitEvent::hasAccess member %s (%s) event %s summit is public open.", $member->getId(), $member->getEmail(), $this->id));
            Cache::tags(CacheRegions::getCacheRegionForSummitEvent($this->getId()))->set($cache_key, true, $ttl);
            return true;
        }

        if ($member->isAdmin() || $this->summit->isSummitAdmin($member) || $member->isTester()) {
            Log::debug(sprintf("SummitEvent::hasAccess member %s (%s) event %s is Super Admin/Admin/SummitAdmin or Tester.", $member->getId(), $member->getEmail(), $this->id));
            Cache::tags(CacheRegions::getCacheRegionForSummitEvent($this->getId()))->set($cache_key, true, $ttl);
            return true;
        }

        if ($member->hasPaidTicketOnSummit($this->summit)) {

            // check required access levels
            if ($this->category->getAllowedAccessLevels()->count() > 0) {
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
                foreach ($member->getPaidSummitTickets($this->summit) as $ticket) {
                    $ticketAccessLevelsIds = $ticket->getBadgeAccessLevelsIds();
                    Log::debug(sprintf("SummitEvent::hasAccess checking access levels for ticket %s ticket access levels (%s).", $ticket->getId(), implode(",", $ticketAccessLevelsIds)));
                    if (count(array_intersect($eventAccessLevelsIds, $ticketAccessLevelsIds))) return true;
                }
                Log::debug(sprintf("SummitEvent::hasAccess member %s (%s) event %s has no access.", $member->getId(), $member->getEmail(), $this->id));
                Cache::tags(CacheRegions::getCacheRegionForSummitEvent($this->getId()))->set($cache_key, false, $ttl);
                return false;
            }
            // check time
            $now = new DateTime("now", new \DateTimeZone("UTC"));

            Log::debug
            (
                sprintf
                (
                    "SummitEvent::hasAccess member %s (%s) event %s has a paid ticket with the proper access levels now %s event start time %s streaming type %s.",
                    $member->getId(),
                    $member->getEmail(),
                    $this->id,
                    $now->format("Y-m-d H:i:s"),
                    !is_null($this->start_date) ? $this->start_date->format("Y-m-d H:i:s") : "N/A",
                    $this->streaming_type
                )
            );

            if (!is_null($this->start_date) &&
                $this->start_date->getTimestamp() > $now->getTimestamp()
                && $this->streaming_type == self::STREAMING_TYPE_LIVE
            ) {
                Log::debug
                (
                    sprintf
                    (
                        "SummitEvent::hasAccess member %s (%s) event %s has not started yet (%s UTC).",
                        $member->getId(),
                        $member->getEmail(),
                        $this->id,
                        $this->start_date->format("Y-m-d H:i:s")
                    )
                );

                $ttl = $this->start_date->getTimestamp() - $now->getTimestamp();
                $skew_time = Config::get("cache_api_response.event_has_access_skewtime", 60);
                $ttl = $ttl > $skew_time ? $ttl - $skew_time : $ttl;
                Log::debug(sprintf("SummitEvent::hasAccess member %s (%s) event %s ttl %s res false.", $member->getId(), $member->getEmail(), $this->id, $ttl));
                Cache::tags(CacheRegions::getCacheRegionForSummitEvent($this->getId()))->set($cache_key, false, $ttl);
                return false;
            }
            Cache::tags(CacheRegions::getCacheRegionForSummitEvent($this->getId()))->set($cache_key, true, $ttl);
            return true;
        }

        Log::debug(sprintf("SummitEvent::hasAccess member %s (%s) event %s has no access.", $member->getId(), $member->getEmail(), $this->id));
        Cache::tags(CacheRegions::getCacheRegionForSummitEvent($this->getId()))->set($cache_key, false, $ttl);
        return false;
    }


    /**
     * @return bool
     */
    public function isMuxStream(): bool
    {
        if (empty($this->streaming_url)) return false;
        if (preg_match("/(.*\.mux\.com)/i", $this->streaming_url)) return true;
        return false;
    }

    /**
     * @return string|null
     */
    public function getStreamThumbnailUrl(): ?string
    {
        if ($this->isMuxStream()) {
            $matches = [];
            if (preg_match("/^(.*\.mux\.com)\/(.*)(\.m3u8)$/", $this->streaming_url, $matches)) {
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
    public function setLevel(string $level): void
    {
        if (!in_array($level, ISummitEventLevel::ValidLevels))
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

    public function getStreamingType(): ?string
    {
        return $this->streaming_type;
    }

    /**
     * @param string $streaming_type
     * @throws ValidationException
     */
    public function setStreamingType(string $streaming_type): void
    {
        if (!in_array($streaming_type, self::ValidStreamingTypes))
            throw new ValidationException(sprintf("%s is not a valid streaming type", $streaming_type));
        $this->streaming_type = $streaming_type;
        $key = $this->getSecureStreamCacheKey();
        if(Cache::tags(sprintf('secure_streams_%s',$this->summit->getId()))->has($key))
            Cache::tags(sprintf('secure_streams_%s',$this->summit->getId()))->forget($key);
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
    public function getDuration(): int
    {
        if (!$this->duration && !is_null($this->start_date) && !is_null(!is_null($this->end_date))) {
            $this->duration = $this->end_date->getTimestamp() - $this->start_date->getTimestamp();
        }
        return $this->duration;
    }

    /**
     * @param int $duration_in_seconds
     * @param bool $skipDatesSetting
     * @param Member|null $member
     * @throws ValidationException
     */
    public function setDuration(int $duration_in_seconds, bool $skipDatesSetting = false, ?Member $member = null): void
    {
        if (!$this->type->isAllowsPublishingDates()) {
            throw new ValidationException("Type does not allows Publishing Period.");
        }
        $this->_setDuration($this->getSummit(), $duration_in_seconds, $skipDatesSetting, $member);
    }

    /**
     * @return array|string[]
     */
    public static function getAllowedFields(): array{
        return SummitEvent::AllowedFields;
    }

    public static function getAllowedEditableFields(): array{
        return SummitEvent::AllowedEditableFields;
    }

    /**
     * @param string $type
     * @return bool
     */
    public static function isAllowedEditableField(string $type): bool
    {
        return in_array($type, SummitEvent::AllowedEditableFields);
    }

    /**
     * @param string $type
     * @return bool
     */
    public static function isAllowedField(string $type): bool
    {
      return in_array($type, SummitEvent::AllowedFields);
    }

    /**
     * @return array
     */
    public function getSnapshot():array{
        return [
            self::FieldTitle => $this->title,
            self::FieldLevel => $this->level,
            self::FieldAbstract => $this->abstract,
            self::FieldSocialDescription => $this->social_summary,
            self::FieldTrack => $this->getCategoryId(),
            self::FieldType => $this->getTypeId(),
        ];
    }

    public function getTrackTransitionTime():?int{
        $track = $this->getCategory();
        if ($track === null) return null;
        return $track->getProposedScheduleTransitionTime();
    }

    use ScheduleEntity;

    public function getSummitEventId(): int
    {
        return $this->id;
    }

    public function getSource(): string
    {
        return SummitEventTypeConstants::BLACKOUT_TIME_FINAL;
    }

    /**
     * @return bool
     */
    public function IsSecureStream(): bool
    {
        return $this->stream_is_secure;
    }

    public function getSecureStreamCacheKey():string{
        return sprintf("event_%s_secure_stream", $this->id);
    }

    public function getOverflowStreamCacheKey():string{
        return sprintf("event_%s_overflow_stream", $this->id);
    }

    /**
     * @param bool $stream_is_secure
     */
    public function setStreamIsSecure(bool $stream_is_secure): void
    {
        Log::debug(sprintf("SummitEvent::setStreamIsSecure summit %s event %s stream_is_secure %s", $this->summit->getId(), $this->id, $stream_is_secure));
        $this->stream_is_secure = $stream_is_secure;

        $key = $this->getSecureStreamCacheKey();
        if(Cache::tags(sprintf('secure_streams_%s',$this->summit->getId()))->has($key))
            Cache::tags(sprintf('secure_streams_%s',$this->summit->getId()))->forget($key);

        if($this->hasSummit() && $this->stream_is_secure && !$this->summit->hasMuxPrivateKey())
            CreateMUXURLSigningKeyForSummit::dispatch($this->summit->getId());
    }

    public function getAllowedTicketTypes(){
        return $this->allowed_ticket_types;
    }

    public function addAllowedTicketType(SummitTicketType $ticket_type){
        if($this->allowed_ticket_types->contains($ticket_type)) return;
        $this->allowed_ticket_types->add($ticket_type);
    }

    public function clearAllowedTicketTypes(){
        $this->allowed_ticket_types->clear();
    }

    public function isPublic():bool{
        return $this->allowed_ticket_types->isEmpty() && $this->type->isPublic();
    }

    public function getSubmissionSource(): string
    {
        return $this->submission_source;
    }

    /**
     * @param string $submission_source
     * @throws ValidationException
     */
    public function setSubmissionSource(string $submission_source): void
    {
        if (!in_array($submission_source, self::ValidSubmissionSources))
            throw new ValidationException(sprintf("%s is not a valid submission source.", $submission_source));
        $this->submission_source = $submission_source;
    }

    /**
     * @param PresentationType $type
     * @return void
     */
    public function promote2Presentation(PresentationType $type):void{
        try {
            $sql = <<<SQL
UPDATE `SummitEvent` SET `ClassName` = 'Presentation', TypeID = :type_id WHERE `SummitEvent`.`ID` = :id;
SQL;

            $stmt = $this->prepareRawSQL($sql,   [
                'id' => $this->getId(),
                'type_id' => $type->getId(),
            ]);

            $stmt->executeQuery();

            $sql = <<<SQL
INSERT INTO `Presentation` (`ID`, `Status`, `OtherTopic`, `Progress`, `Views`, `BeenEmailed`, `ProblemAddressed`, `AttendeesExpectedLearnt`, `Legacy`, `ToRecord`, `AttendingMedia`, `Slug`, `ModeratorID`, `SelectionPlanID`, `WillAllSpeakersAttend`, `DisclaimerAcceptedDate`, `CustomOrder`) 
VALUES (:id, NULL, NULL, '0', '0', '0', NULL, NULL, '0', '0', '0', NULL, NULL, NULL, '0', NULL, '0')
SQL;

            $stmt = $this->prepareRawSQL($sql,
                [
                    'id' => $this->getId(),
                ]);
            $stmt->executeQuery();
            $this->getEM()->flush();

        } catch (\Exception $ex) {

        }
    }

    /**
     * @return string|null
     */
    public function getOverflowStreamingUrl(): ?string
    {
        return $this->overflow_streaming_url;
    }

    /**
     * @return bool
     */
    public function getOverflowStreamIsSecure(): bool
    {
        return $this->overflow_stream_is_secure;
    }

    /**
     * @return string|null
     */
    public function getOverflowStreamKey(): ?string
    {
        return $this->overflow_stream_key;
    }

    /**
     * @param string $overflow_stream_key
     * @return void
     */
    public function setOverflowStreamKey(string $overflow_stream_key): void
    {
        $this->overflow_stream_key = $overflow_stream_key;
    }

    /**
     * @param string $overflow_streaming_url
     * @param bool $overflow_stream_is_secure
     * @return void
     */
    public function setOverflow(string $overflow_streaming_url,
                                bool $overflow_stream_is_secure): void
    {
        $this->overflow_streaming_url = $overflow_streaming_url;
        $this->overflow_stream_is_secure = $overflow_stream_is_secure;
        $this->occupancy = self::OccupancyOverflow;
    }

    public function clearOverflow(string $occupancy = self::OccupancyEmpty): void
    {
        $this->overflow_streaming_url = null;
        $this->overflow_stream_is_secure = false;
        $this->overflow_stream_key = null;
        $this->occupancy = $occupancy;
    }

    /**
     * @return string
     * @throws ValidationException
     */
    public function getOverflowUrl(): string
    {
        if ($this->occupancy != self::OccupancyOverflow)
            throw new ValidationException("To get the overflow url, occupancy must be OVERFLOW.");

        if (is_null($this->overflow_stream_key))
            throw new ValidationException("Overflow stream key is null.");

        return sprintf("%s%s?%s=%s",
            $this->summit->getMarketingSiteUrl(),
            config("overflow.path", "/a/overflow-player"),
            config("overflow.query_string_key","k"),
            $this->overflow_stream_key);
    }

    /**
     * @return string
     * @throws RandomException
     */
    public function generateOverflowKey(): string
    {
         $salt = random_bytes(16);
         return hash('sha256', $this->getId() . $salt . time());
    }

    /**
     * @return array
     */
    public function getRegularStreamingTokens(): array
    {
        if(empty($this->streaming_url)) return [];
        $cache_key = $this->getSecureStreamCacheKey();
        return $this->getStreamingTokens($cache_key, $this->streaming_url);
    }

    /**
     * @return array
     */
    public function getOverflowStreamingTokens(): array
    {
        if(empty($this->overflow_streaming_url)) return [];
        $cache_key = $this->getOverflowStreamCacheKey();
        return $this->getStreamingTokens($cache_key, $this->overflow_streaming_url);
    }

    public function isOnOverflow():bool{
        return $this->occupancy == self::OccupancyOverflow;
    }
}