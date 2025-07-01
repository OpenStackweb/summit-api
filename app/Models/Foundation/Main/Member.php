<?php namespace models\main;
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

use App\libs\Utils\PunnyCodeHelper;
use App\Models\Foundation\Elections\Candidate;
use App\Models\Foundation\Elections\Election;
use App\Models\Foundation\Elections\Nomination;
use App\Models\Foundation\Main\IGroup;
use App\Models\Foundation\Main\Strategies\MemberSummitStrategyFactory;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Illuminate\Support\Facades\Config;
use LaravelDoctrine\ORM\Facades\EntityManager;
use models\summit\Presentation;
use models\summit\SummitMetric;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Models\Foundation\Main\CCLA\Team;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use models\exceptions\ValidationException;
use models\oauth2\IResourceServerContext;
use models\summit\CalendarSync\CalendarSyncInfo;
use models\summit\CalendarSync\ScheduleCalendarSyncInfo;
use models\summit\IOrderConstants;
use models\summit\PresentationSpeaker;
use models\summit\RSVP;
use models\summit\Sponsor;
use models\summit\Summit;
use models\summit\SummitAttendeeTicket;
use models\summit\SummitEvent;
use models\summit\SummitEventFeedback;
use models\summit\SummitOrder;
use models\summit\SummitRoomReservation;
use models\summit\SummitSelectedPresentation;
use models\summit\SummitSelectedPresentationList;
use models\summit\SummitTrackChair;
use models\utils\SilverstripeBaseModel;
use Doctrine\ORM\Mapping as ORM;
use utils\Filter;

/**
 * @package models\main
 */
#[ORM\Table(name: '`Member`')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: \App\Repositories\Summit\DoctrineMemberRepository::class)] // Class Member
class Member extends SilverstripeBaseModel
{

    const MembershipTypeFoundation = 'Foundation';

    const MembershipTypeCommunity = 'Community';

    const MembershipTypeNone = 'None';

    Const MemberShipType_IndividualMember = 'Individual';

    /**
     * @var string
     */
    #[ORM\Column(name: 'FirstName', type: 'string')]
    private $first_name;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Bio', type: 'string')]
    private $bio;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Surname', type: 'string')]
    private $last_name;

    /**
     * @var string
     */
    #[ORM\Column(name: 'GitHubUser', type: 'string')]
    private $github_user;

    /**
     * @var string
     */
    #[ORM\Column(name: 'MembershipType', type: 'string')]
    private $membership_type;

    /**
     * @var SummitEventFeedback[]
     */
    #[ORM\OneToMany(targetEntity: \models\summit\SummitEventFeedback::class, mappedBy: 'owner', cascade: ['persist'], orphanRemoval: true, fetch: 'EXTRA_LAZY')]
    private $feedback;

    /**
     * @var Affiliation[]
     */
    #[ORM\OneToMany(targetEntity: \Affiliation::class, mappedBy: 'owner', cascade: ['persist'], orphanRemoval: true, fetch: 'EXTRA_LAZY')]
    private $affiliations;

    /**
     * @var LegalAgreement[]
     */
    #[ORM\OneToMany(targetEntity: \LegalAgreement::class, mappedBy: 'owner', cascade: ['persist'], orphanRemoval: true, fetch: 'EXTRA_LAZY')]
    protected $legal_agreements;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'Active', type: 'boolean')]
    private $active;

    /**
     * @var string
     */
    #[ORM\Column(name: 'LinkedInProfile', type: 'string')]
    private $linked_in_profile;

    /**
     * @var string
     */
    #[ORM\Column(name: 'IRCHandle', type: 'string')]
    private $irc_handle;

    /**
     * @var string
     */
    #[ORM\Column(name: 'TwitterName', type: 'string')]
    private $twitter_handle;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Gender', type: 'string')]
    private $gender;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Projects', type: 'string')]
    private $projects;

    /**
     * @var string
     */
    #[ORM\Column(name: 'OtherProject', type: 'string')]
    private $other_project;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'DisplayOnSite', type: 'boolean')]
    private $display_on_site;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'SubscribedToNewsletter', type: 'boolean')]
    private $subscribed_to_newsletter;

    /**
     * @var string
     */
    #[ORM\Column(name: 'ShirtSize', type: 'string')]
    private $shirt_size;

    /**
     * @var string
     */
    #[ORM\Column(name: 'FoodPreference', type: 'string')]
    private $food_preference;

    /**
     * @var string
     */
    #[ORM\Column(name: 'OtherFood', type: 'string')]
    private $other_food_preference;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Country', type: 'string')]
    private $country;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Email', type: 'string')]
    private $email;

    /**
     * @var string
     */
    #[ORM\Column(name: 'SecondEmail', type: 'string')]
    private $second_email;

    /**
     * @var string
     */
    #[ORM\Column(name: 'ThirdEmail', type: 'string')]
    private $third_email;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'EmailVerified', type: 'boolean')]
    private $email_verified;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'EmailVerifiedDate', type: 'datetime')]
    private $email_verified_date;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'IndividualMemberJoinDate', type: 'datetime')]
    private $individual_member_join_date;

    /**
     *
     * @var int|null
     */
    #[ORM\Column(name: 'ExternalUserId', type: 'integer')]
    private $user_external_id;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'ResignDate', type: 'datetime')]
    protected $resign_date;

    /**
     * @var File
     */
    #[ORM\JoinColumn(name: 'PhotoID', referencedColumnName: 'ID')]
    #[ORM\ManyToOne(targetEntity: \models\main\File::class)]
    private $photo;

    /**
     * @var string
     */
    #[ORM\Column(name: 'State', type: 'string')]
    private $state;

    /**
     * @var string
     */
    #[ORM\Column(name: 'ExternalPic', type: 'string')]
    private $external_pic;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'PublicProfileShowPhoto', options: ['default' => 0], type: 'boolean')]
    private $public_profile_show_photo;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'PublicProfileShowFullName', options: ['default' => 0], type: 'boolean')]
    private $public_profile_show_fullname;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'PublicProfileShowEmail', options: ['default' => 0], type: 'boolean')]
    private $public_profile_show_email;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'PublicProfileAllowChatWithMe', options: ['default' => 0], type: 'boolean')]
    private $public_profile_allow_chat_with_me;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'PublicProfileShowSocialMediaInfo', options: ['default' => 0], type: 'boolean')]
    private $public_profile_show_social_media_info;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'PublicProfileShowBio', options: ['default' => 0], type: 'boolean')]
    private $public_profile_show_bio;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'PublicProfileShowTelephoneNumber', options: ['default' => 0], type: 'boolean')]
    private $public_profile_show_telephone_number;

    /**
     * @var SummitMemberSchedule[]
     */
    #[ORM\OneToMany(targetEntity: \SummitMemberSchedule::class, mappedBy: 'member', cascade: ['persist'], orphanRemoval: true, fetch: 'EXTRA_LAZY')]
    private $schedule;

    /**
     * @var ScheduleCalendarSyncInfo[]
     */
    #[ORM\OneToMany(targetEntity: \models\summit\CalendarSync\ScheduleCalendarSyncInfo::class, mappedBy: 'member', cascade: ['persist'], orphanRemoval: true, fetch: 'EXTRA_LAZY')]
    private $schedule_sync_info;

    /**
     * @var CalendarSyncInfo[]
     */
    #[ORM\OneToMany(targetEntity: \models\summit\CalendarSync\CalendarSyncInfo::class, mappedBy: 'owner', cascade: ['persist'], orphanRemoval: true, fetch: 'EXTRA_LAZY')]
    private $calendars_sync;

    /**
     * @var RSVP[]
     */
    #[ORM\OneToMany(targetEntity: \models\summit\RSVP::class, mappedBy: 'owner', cascade: ['persist'], fetch: 'EXTRA_LAZY')]
    private $rsvp;

    /**
     * @var Sponsor[]
     */
    #[ORM\ManyToMany(targetEntity: \models\summit\Sponsor::class, mappedBy: 'members', fetch: 'EXTRA_LAZY')]
    private $sponsor_memberships;

    /**
     * @var Group[]
     */
    #[ORM\JoinTable(name: 'Group_Members')]
    #[ORM\JoinColumn(name: 'MemberID', referencedColumnName: 'ID')]
    #[ORM\InverseJoinColumn(name: 'GroupID', referencedColumnName: 'ID')]
    #[ORM\ManyToMany(targetEntity: \models\main\Group::class, inversedBy: 'members', cascade: ['persist'], fetch: 'EXTRA_LAZY')]
    private $groups;

    /**
     * @var Team[]
     */
    #[ORM\JoinTable(name: 'Team_Members')]
    #[ORM\JoinColumn(name: 'MemberID', referencedColumnName: 'ID')]
    #[ORM\InverseJoinColumn(name: 'TeamID', referencedColumnName: 'ID')]
    #[ORM\ManyToMany(targetEntity: \Models\Foundation\Main\CCLA\Team::class, inversedBy: 'members', fetch: 'EXTRA_LAZY')]
    private $ccla_teams;

    /**
     * @var ChatTeamMember[]
     */
    #[ORM\OneToMany(targetEntity: \ChatTeamMember::class, mappedBy: 'member', cascade: ['persist'], orphanRemoval: true, fetch: 'EXTRA_LAZY')]
    private $team_memberships;

    /**
     * @var SummitMemberFavorite[]
     */
    #[ORM\OneToMany(targetEntity: \SummitMemberFavorite::class, mappedBy: 'member', cascade: ['persist'], orphanRemoval: true, fetch: 'EXTRA_LAZY')]
    private $favorites;

    /**
     * @var SummitRoomReservation[]
     */
    #[ORM\OneToMany(targetEntity: \models\summit\SummitRoomReservation::class, mappedBy: 'owner', cascade: ['persist'], orphanRemoval: true, fetch: 'EXTRA_LAZY')]
    private $reservations;

    /**
     * @var PresentationSpeaker
     */
    #[ORM\OneToOne(targetEntity: \models\summit\PresentationSpeaker::class, mappedBy: 'member', cascade: ['persist', 'remove'], orphanRemoval: true, fetch: 'EXTRA_LAZY')]
    private $speaker;

    /**
     * @var PersonalCalendarShareInfo[]
     */
    #[ORM\OneToMany(targetEntity: \models\main\PersonalCalendarShareInfo::class, mappedBy: 'owner', cascade: ['persist'], orphanRemoval: true, fetch: 'EXTRA_LAZY')]
    private $schedule_shareable_links;

    /**
     * @var SummitOrder[]
     */
    #[ORM\OneToMany(targetEntity: \models\summit\SummitOrder::class, mappedBy: 'owner', cascade: ['persist'], fetch: 'EXTRA_LAZY')]
    private $summit_registration_orders;

    /**
     * @var SummitAdministratorPermissionGroup[]
     */
    #[ORM\ManyToMany(targetEntity: \models\main\SummitAdministratorPermissionGroup::class, mappedBy: 'members', fetch: 'EXTRA_LAZY')]
    private $summit_permission_groups;

    /**
     * @var SummitMetric[]
     */
    #[ORM\OneToMany(targetEntity: \models\summit\SummitMetric::class, mappedBy: 'member', cascade: ['persist', 'remove'], orphanRemoval: true, fetch: 'EXTRA_LAZY')]
    protected $summit_attendance_metrics;

    /**
     * @var SummitTrackChair[]
     */
    #[ORM\OneToMany(targetEntity: \models\summit\SummitTrackChair::class, mappedBy: 'member', cascade: ['persist', 'remove'], orphanRemoval: true, fetch: 'EXTRA_LAZY')]
    private $track_chairs;

    /**
     * @var Nomination[]
     */
    #[ORM\OneToMany(targetEntity: \App\Models\Foundation\Elections\Nomination::class, mappedBy: 'candidate', cascade: ['persist'], orphanRemoval: true, fetch: 'EXTRA_LAZY')]
    private $election_applications;

    /**
     * @var Nomination[]
     */
    #[ORM\OneToMany(targetEntity: \App\Models\Foundation\Elections\Nomination::class, mappedBy: 'nominator', cascade: ['persist'], orphanRemoval: true, fetch: 'EXTRA_LAZY')]
    private $election_nominations;

    /**
     * @var Candidate[]
     */
    #[ORM\OneToMany(targetEntity: \App\Models\Foundation\Elections\Candidate::class, mappedBy: 'member', cascade: ['persist'], orphanRemoval: true, fetch: 'EXTRA_LAZY')]
    private $candidate_profiles;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Company', nullable: true, type: 'string')]
    private $company;

    /**
     * @var Presentation[]
     */
    #[ORM\OneToMany(targetEntity: \models\summit\Presentation::class, mappedBy: 'created_by', cascade: ['persist'], fetch: 'EXTRA_LAZY')]
    private $created_presentations;

    /**
     * Member constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->active = false;
        $this->email_verified = false;
        $this->feedback = new ArrayCollection();
        $this->groups = new ArrayCollection();
        $this->ccla_teams = new ArrayCollection();
        $this->affiliations = new ArrayCollection();
        $this->team_memberships = new ArrayCollection();
        $this->favorites = new ArrayCollection();
        $this->schedule = new ArrayCollection();
        $this->rsvp = new ArrayCollection();
        $this->calendars_sync = new ArrayCollection();
        $this->schedule_sync_info = new ArrayCollection();
        $this->reservations = new ArrayCollection();
        $this->sponsor_memberships = new ArrayCollection();
        $this->summit_registration_orders = new ArrayCollection();
        $this->user_external_id = 0;
        $this->membership_type = self::MembershipTypeNone;
        $this->schedule_shareable_links = new ArrayCollection();
        $this->summit_permission_groups = new ArrayCollection();
        $this->summit_attendance_metrics = new ArrayCollection();
        $this->legal_agreements = new ArrayCollection();
        $this->track_chairs = new ArrayCollection();
        $this->election_applications = new ArrayCollection();
        $this->election_nominations = new ArrayCollection();
        $this->candidate_profiles = new  ArrayCollection();
        $this->subscribed_to_newsletter = false;
        $this->display_on_site = false;
        $this->created_presentations = new ArrayCollection();

        // user profile settings
        $this->public_profile_show_photo = false;
        $this->public_profile_show_email = false;
        $this->public_profile_show_fullname = true;
        $this->public_profile_allow_chat_with_me = false;
        $this->public_profile_show_social_media_info = false;
        $this->public_profile_show_bio = true;
        $this->public_profile_show_telephone_number = false;
        $this->individual_member_join_date = null;
    }

    /**
     * @return Affiliation[]
     */
    public function getAffiliations()
    {
        return $this->affiliations;
    }

    /**
     * @return ArrayCollection|LegalAgreement[]
     */
    public function getLegalAgreements()
    {
        return $this->legal_agreements;
    }

    /**
     * @param LegalAgreement $legalAgreement
     */
    public function addLegalAgreement(LegalAgreement $legalAgreement)
    {
        if ($this->legal_agreements->contains($legalAgreement)) return;
        $this->legal_agreements->add($legalAgreement);
        $legalAgreement->setOwner($this);
    }

    /**
     * @return Affiliation[]
     */
    public function getCurrentAffiliations()
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq("is_current", true))
            ->andWhere(Criteria::expr()->eq("end_date", null))
            ->orderBy([
                "start_date" => Criteria::ASC,
            ]);

        return $this->affiliations->matching($criteria);
    }

    /**
     * @param string $orgName
     * @return Affiliation|null
     */
    public function getAffiliationByOrgName(string $orgName): ?Affiliation
    {
        $res = $this->affiliations->filter(function ($e) use ($orgName) {
            return $e->getOrganization()->getName() == trim($orgName) && $e->isCurrent();
        })->first();
        return $res ? $res : null;
    }

    /**
     * @return ArrayCollection|\Doctrine\Common\Collections\Collection
     */
    public function getAllAffiliations()
    {
        $criteria = Criteria::create()
            ->orderBy([
                "start_date" => Criteria::ASC,
                "end_date" => Criteria::ASC,
            ]);
        return $this->affiliations->matching($criteria);
    }

    /**
     * @return Group[]
     */
    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * @return ChatTeamMember[]
     */
    public function getTeamMemberships()
    {
        return $this->team_memberships;
    }

    /**
     * @param ChatTeamMember[] $team_memberships
     */
    public function setTeamMemberships($team_memberships)
    {
        $this->team_memberships = $team_memberships;
    }

    /**
     * @param mixed $groups
     */
    public function setGroups($groups)
    {
        $this->groups = $groups;
    }

    /**
     * @return ArrayCollection|SummitMemberFavorite[]
     */
    public function getFavoritesSummitEvents()
    {
        return $this->favorites;
    }

    /**
     * @param SummitMemberFavorite[] $favorites
     */
    public function setFavoritesSummitEvents($favorites)
    {
        $this->favorites = $favorites;
    }

    /**
     * @return string
     */
    public function getBio()
    {
        return $this->bio;
    }

    /**
     * @return string
     */
    public function getLinkedInProfile()
    {
        return $this->linked_in_profile;
    }

    /**
     * @return string
     */
    public function getIrcHandle()
    {
        return $this->irc_handle;
    }

    /**
     * @return string
     */
    public function getTwitterHandle()
    {
        return $this->twitter_handle;
    }

    /**
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param string $state
     */
    public function setState($state)
    {
        $this->state = $state;
    }

    /**
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @param string $country
     */
    public function setCountry($country)
    {
        $this->country = $country;
    }

    /**
     * @return string
     */
    public function getSecondEmail()
    {
        return PunnyCodeHelper::decodeEmail($this->second_email);
    }

    /**
     * @param string $second_email
     */
    public function setSecondEmail($second_email)
    {
        $this->second_email = PunnyCodeHelper::encodeEmail($second_email);
    }

    /**
     * @return string
     */
    public function getThirdEmail()
    {
        return PunnyCodeHelper::decodeEmail($this->third_email);
    }

    /**
     * @param string $third_email
     */
    public function setThirdEmail($third_email)
    {
        $this->third_email = PunnyCodeHelper::encodeEmail($third_email);
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return PunnyCodeHelper::decodeEmail($this->email);
    }

    /**
     * @param string $email
     */
    public function setEmail($email)
    {
        Log::debug(sprintf("Member::setEmail %s (%s)", $email, $this->id));
        $this->email = PunnyCodeHelper::encodeEmail($email);
    }

    /**
     * @return string
     */
    public function getGitHubUser()
    {
        return $this->github_user;
    }

    /**
     * @return bool
     */
    public function isEmailVerified()
    {
        return $this->email_verified;
    }

    /**
     * @return bool
     */
    public function getEmailVerified()
    {
        return $this->email_verified;
    }

    /**
     * @param bool $email_verified
     */
    public function setEmailVerified($email_verified)
    {
        $this->email_verified = $email_verified;
    }

    /**
     * @return \DateTime
     */
    public function getEmailVerifiedDate()
    {
        return $this->email_verified_date;
    }

    /**
     * @param \DateTime $email_verified_date
     */
    public function setEmailVerifiedDate($email_verified_date)
    {
        $this->email_verified_date = $email_verified_date;
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * @return bool
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * @param bool $active
     */
    public function setActive($active)
    {
        $this->active = $active;
    }

    /**
     * @return string
     */
    public function getGender()
    {
        return $this->gender;
    }

    /**
     * @return string|null
     */
    public function getLastName(): ?string
    {
        return $this->last_name;
    }

    /**
     * @return string|null
     */
    public function getFirstName(): ?string
    {
        return $this->first_name;
    }

    /**
     * @return File
     */
    public function getPhoto()
    {
        return $this->photo;
    }

    /**
     * @param File $photo
     */
    public function setPhoto(File $photo)
    {
        $this->photo = $photo;
    }

    /**
     * @return SummitEventFeedback[]
     */
    public function getFeedback()
    {
        return $this->feedback;
    }

    /**
     * @param Summit $summit
     * @return SummitEventFeedback[]
     */
    public function getFeedbackBySummit(Summit $summit)
    {
        return $this->createQueryBuilder()
            ->select('distinct f')
            ->from('models\summit\SummitEventFeedback', 'f')
            ->join('f.event', 'e')
            ->join('f.owner', 'o')
            ->join('e.summit', 's')
            ->where('s.id = :summit_id and o.id = :owner_id and e.published = 1')
            ->setParameter('summit_id', $summit->getId())
            ->setParameter('owner_id', $this->getId())
            ->getQuery()->getResult();
    }

    /**
     * @param SummitEvent $event
     * @return SummitEventFeedback|null
     */
    public function getFeedbackByEvent(SummitEvent $event): ?SummitEventFeedback
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('event', $event));
        $feedback = $this->feedback->matching($criteria)->first();
        return $feedback === false ? null : $feedback;
    }

    /**
     * @param SummitEventFeedback $feedback
     */
    public function addFeedback(SummitEventFeedback $feedback)
    {
        if ($this->feedback->contains($feedback)) return;
        $this->feedback->add($feedback);
        $feedback->setOwner($this);
    }

    /**
     * @param SummitEventFeedback $feedback
     */
    public function removeFeedback(SummitEventFeedback $feedback)
    {
        if (!$this->feedback->contains($feedback)) return;
        $this->feedback->removeElement($feedback);
        $feedback->clearOwner();
    }

    /**
     * @param bool $skip_external
     * @return bool
     */
    public function isAdmin($skip_external = false): bool
    {
        // admin or super admin
        Log::debug(sprintf("Member::isAdmin id %s email %s", $this->id, $this->email));
        $superAdminGroup = $this->getGroupByCode(IGroup::SuperAdmins);
        if (!is_null($superAdminGroup)) {
            Log::debug(sprintf("Member::isAdmin has Super Admin Group On DB"));
            return true;
        }

        $adminGroup = $this->getGroupByCode(IGroup::Administrators);
        if (!is_null($adminGroup)) {
            Log::debug(sprintf("Member::isAdmin has Admin Group On DB"));
            return true;
        }

        if (!$skip_external) {
            Log::debug(sprintf("Member::isAdmin check on external"));
            if ($this->isOnExternalGroup(IGroup::SuperAdmins))
                return true;

            if ($this->isOnExternalGroup(IGroup::Administrators))
                return true;
        }

        return false;
    }

    public function isSummitAdmin(): bool
    {
        $summitAdminGroup = $this->getGroupByCode(IGroup::SummitAdministrators);
        if (!is_null($summitAdminGroup))
            return true;
        if ($this->isOnExternalGroup(IGroup::SummitAdministrators))
            return true;
        return false;
    }

    public function isRegistrationAdmin(): bool
    {
        $summitAdminGroup = $this->getGroupByCode(IGroup::SummitRegistrationAdmins);
        if (!is_null($summitAdminGroup))
            return true;
        if ($this->isOnExternalGroup(IGroup::SummitRegistrationAdmins))
            return true;
        return false;
    }


    public function isTester(): bool
    {
        $summitAdminGroup = $this->getGroupByCode(IGroup::Testers);
        if (!is_null($summitAdminGroup))
            return true;
        if ($this->isOnExternalGroup(IGroup::Testers))
            return true;
        return false;
    }

    public function isTrackChairAdmin(): bool
    {
        $summitAdminGroup = $this->getGroupByCode(IGroup::TrackChairsAdmins);
        if (!is_null($summitAdminGroup))
            return true;
        if ($this->isOnExternalGroup(IGroup::TrackChairsAdmins))
            return true;
        return false;
    }


    public function isSponsorUser(): bool
    {
        if($this->belongsToGroup(IGroup::Sponsors))
            return true;
        if ($this->isOnExternalGroup(IGroup::Sponsors))
            return true;
        return false;
    }



    /**
     * @param string $code
     * @return bool
     */
    public function isOnExternalGroup(string $code): bool
    {
        Log::debug(sprintf("Member::isOnExternalGroup id %s code %s", $this->id, $code));
        $resource_server_ctx = App::make(IResourceServerContext::class);
        if ($resource_server_ctx instanceof IResourceServerContext) {
            foreach ($resource_server_ctx->getCurrentUserGroups() as $group) {
                Log::debug(sprintf("Member::isOnExternalGroup id %s code %s external group %s", $this->id, $code, $group['slug']));
                if
                (
                    isset($group['slug']) &&
                    trim($group['slug']) == trim($code)
                )
                    return true;
            }
        }
        return false;
    }

    /**
     * @param $code
     * @param bool $skip_external
     * @return bool
     */
    public function isOnGroup(string $code, $skip_external = false)
    {
        Log::debug(sprintf("Member::isOnGroup member %s group code %s", $this->id, $code));
        if ($this->isAdmin($skip_external)) {
            Log::debug(sprintf("Member::isOnGroup member %s group code %s isAdmin true", $this->id, $code));
            return true;
        }
        $group = $this->getGroupByCode($code);
        if (!is_null($group)) {
            Log::debug(sprintf("Member::isOnGroup member %s group code %s belongs to group", $this->id, $code));
            return true;
        }
        if (!$skip_external) {
            Log::debug(sprintf("Member::isOnGroup member %s group code %s check external ones", $this->id, $code));
            return $this->isOnExternalGroup($code);
        }
        return false;
    }

    /**
     * @param string $code
     * @return Group|null
     */
    public function getGroupByCode(string $code): ?Group
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('code', trim($code)));
        $res = $this->groups->matching($criteria)->first();
        return $res === false ? null : $res;
    }

    /**
     * @param string $code
     * @return bool
     */
    public function belongsToGroup(string $code): bool
    {
        try {
            $sql = <<<SQL
SELECT COUNT(MemberID)
FROM Group_Members 
INNER JOIN `Group` ON `Group`.ID = Group_Members.GroupID
WHERE MemberID = :member_id AND `Group`.Code = :code
SQL;

            $stmt = $this->prepareRawSQL($sql, [
                'member_id' => $this->getId(),
                'code' => trim($code),
            ]);
            $res = $stmt->executeQuery();
            $res = $res->fetchFirstColumn();
            return intval($res[0]) > 0;
        } catch (\Exception $ex) {

        }
        return false;

    }

    /**
     * @return int[]
     */
    public function getGroupsIds()
    {
        $ids = [];
        foreach ($this->getGroups() as $g) {
            $ids[] = intval($g->getId());
        }
        return $ids;
    }

    public function getCCLATeamsIds()
    {
        $ids = [];
        foreach ($this->getCCLATeams() as $t) {
            $ids[] = intval($t->getId());
        }
        return $ids;
    }

    /**
     * @return Team[]
     */
    public function getCCLATeams()
    {
        return $this->ccla_teams->toArray();
    }

    /**
     * @return string[]
     */
    public function getGroupsCodes()
    {
        $codes = [];
        foreach ($this->getGroups() as $g) {
            $codes[] = $g->getCode();
        }
        // from IDP
        $resource_server_ctx = App::make(IResourceServerContext::class);
        if ($resource_server_ctx instanceof IResourceServerContext) {
            foreach ($resource_server_ctx->getCurrentUserGroups() as $group) {
                if (isset($group['slug']))
                    $codes[] = trim($group['slug']);
            }
        }
        return $codes;
    }

    /**
     * @param SummitEvent $event
     * @throws ValidationException
     */
    public function addFavoriteSummitEvent(SummitEvent $event)
    {
        if ($this->isOnFavorite($event))
            throw new ValidationException
            (
                sprintf('Event %s already belongs to member %s favorites.', $event->getId(), $this->getId())
            );
        if (!$event->isPublished())
            throw new ValidationException
            (
                sprintf('Event %s is not published', $event->getId())
            );

        $favorite = new SummitMemberFavorite();

        $favorite->setMember($this);
        $favorite->setEvent($event);
        $this->favorites->add($favorite);
    }

    /**
     * @param SummitEvent $event
     * @return bool
     */
    public function isOnFavorite(SummitEvent $event)
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('event', $event));
        return $this->favorites->matching($criteria)->count() > 0;
    }

    /**
     * @param SummitEvent $event
     * @throws ValidationException
     */
    public function removeFavoriteSummitEvent(SummitEvent $event)
    {
        $favorite = $this->getFavoriteByEvent($event);

        if (is_null($favorite))
            throw new ValidationException
            (
                sprintf('Event %s does not belongs to member %s favorite.', $event->getId(), $this->getId())
            );
        $this->favorites->removeElement($favorite);
        $favorite->clearOwner();
    }

    /**
     * @param Summit $summit
     * @return int[]
     */
    public function getFavoritesEventsIds(Summit $summit)
    {
        $sql = <<<SQL
SELECT SummitEventID 
FROM Member_FavoriteSummitEvents 
INNER JOIN SummitEvent ON SummitEvent.ID = Member_FavoriteSummitEvents.SummitEventID
WHERE MemberID = :member_id AND SummitEvent.Published = 1 AND SummitEvent.SummitID = :summit_id
SQL;

        $stmt = $this->prepareRawSQL($sql,[
            'member_id' => $this->getId(),
            'summit_id' => $summit->getId(),
        ]);
        $res = $stmt->executeQuery();
        return $res->fetchFirstColumn();
    }

    /**
     * @param SummitEvent $event
     * @throws ValidationException
     */
    public function add2Schedule(SummitEvent $event)
    {
        if ($this->isOnSchedule($event))
            throw new ValidationException
            (
                sprintf('Event %s already belongs to member %s schedule.', $event->getId(), $this->getId())
            );

        if (!$event->isPublished())
            throw new ValidationException
            (
                sprintf('Event %s is not published', $event->getId())
            );

        $schedule = new SummitMemberSchedule();

        $schedule->setMember($this);
        $schedule->setEvent($event);
        $this->schedule->add($schedule);
    }

    /**
     * @param ScheduleCalendarSyncInfo $sync_info
     */
    public function add2ScheduleSyncInfo(ScheduleCalendarSyncInfo $sync_info)
    {
        $sync_info->setMember($this);
        $this->schedule_sync_info->add($sync_info);
    }

    public function removeFromSchedule(SummitEvent $event)
    {
        $schedule = $this->getScheduleByEvent($event);

        if (is_null($schedule))
            throw new ValidationException
            (
                sprintf('Event %s does not belongs to member %s schedule.', $event->getId(), $this->getId())
            );
        $this->schedule->removeElement($schedule);
        $schedule->clearOwner();
    }

    public function removeFromScheduleSyncInfo(ScheduleCalendarSyncInfo $sync_info)
    {
        $this->schedule_sync_info->removeElement($sync_info);
        $sync_info->clearOwner();
    }

    /**
     * @param CalendarSyncInfo $calendar_sync_info
     * @param int $event_id
     * @return bool
     */
    public function isEventSynchronized(CalendarSyncInfo $calendar_sync_info, $event_id)
    {

        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('summit_event_id', $event_id));
        $criteria->andWhere(Criteria::expr()->eq('calendar_sync_info', $calendar_sync_info));
        return $this->schedule_sync_info->matching($criteria)->count() > 0;
    }

    /**
     * @param SummitEvent $event
     * @return bool
     */
    public function isOnSchedule(SummitEvent $event)
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('event', $event));
        return $this->schedule->matching($criteria)->count() > 0;
    }

    /**
     * @param SummitEvent $event
     * @return null| SummitMemberSchedule
     */
    public function getScheduleByEvent(SummitEvent $event)
    {

        try {
            $query = $this->createQuery("SELECT s from models\main\SummitMemberSchedule s 
        JOIN s.member a 
        JOIN s.event e    
        WHERE a.id = :member_id and e.id = :event_id
        ");
            return $query
                ->setParameter('member_id', $this->getIdentifier())
                ->setParameter('event_id', $event->getIdentifier())
                ->getSingleResult();
        } catch (NoResultException $ex1) {
            return null;
        } catch (NonUniqueResultException $ex2) {
            // should never happen
            return null;
        }
    }

    /**
     * @param int $summit_event_id
     * @param CalendarSyncInfo $calendar_sync_info
     * @return ScheduleCalendarSyncInfo|null
     */
    public function getScheduleSyncInfoByEvent($summit_event_id, CalendarSyncInfo $calendar_sync_info)
    {
        try {
            $criteria = Criteria::create();
            $criteria->where(Criteria::expr()->eq('summit_event_id', $summit_event_id));
            $criteria->andWhere(Criteria::expr()->eq('calendar_sync_info', $calendar_sync_info));
            $res = $this->schedule_sync_info->matching($criteria)->first();
            return $res === false ? null : $res;
        } catch (NoResultException $ex1) {
            return null;
        } catch (NonUniqueResultException $ex2) {
            // should never happen
            return null;
        }
    }

    /**
     * @param SummitEvent $event
     * @return SummitMemberFavorite|null
     */
    public function getFavoriteByEvent(SummitEvent $event)
    {
        try {
            $query = $this->createQuery("SELECT f from models\main\SummitMemberFavorite f 
        JOIN f.member a 
        JOIN f.event e    
        WHERE a.id = :member_id and e.id = :event_id
        ");
            return $query
                ->setParameter('member_id', $this->getIdentifier())
                ->setParameter('event_id', $event->getIdentifier())
                ->getSingleResult();
        } catch (NoResultException $ex1) {
            return null;
        } catch (NonUniqueResultException $ex2) {
            // should never happen
            return null;
        }
    }

    /**
     * @param Summit $summit
     * @return int[]
     */
    public function getScheduledEventsIds(Summit $summit)
    {
        $sql = <<<SQL
SELECT SummitEventID 
FROM Member_Schedule 
INNER JOIN SummitEvent ON SummitEvent.ID = Member_Schedule.SummitEventID
WHERE MemberID = :member_id AND SummitEvent.Published = 1 AND SummitEvent.SummitID = :summit_id
SQL;

        $stmt = $this->prepareRawSQL($sql,[
            'member_id' => $this->getId(),
            'summit_id' => $summit->getId(),
        ]);
        $res = $stmt->executeQuery();
        return $res->fetchFirstColumn();
    }

    /**
     * @param int $event_id
     * @return null|RSVP
     */
    public function getRsvpByEvent($event_id)
    {
        $builder = $this->createQueryBuilder();
        $rsvp = $builder
            ->select('r')
            ->from('models\summit\RSVP', 'r')
            ->join('r.owner', 'o')
            ->join('r.event', 'e')
            ->where('o.id = :owner_id and e.id = :event_id')
            ->setParameter('owner_id', $this->getId())
            ->setParameter('event_id', intval($event_id))
            ->getQuery()->getResult();

        return count($rsvp) > 0 ? $rsvp[0] : null;
    }

    /**
     * @param Summit $summit
     * @return null|RSVP[]
     */
    public function getRsvpBySummit(Summit $summit)
    {
        $builder = $this->createQueryBuilder();
        $res = $builder
            ->select('r')
            ->from('models\summit\RSVP', 'r')
            ->join('r.owner', 'o')
            ->join('r.event', 'e')
            ->join('e.summit', 's')
            ->where('o.id = :owner_id and s.id = :summit_id')
            ->setParameter('owner_id', $this->getId())
            ->setParameter('summit_id', $summit->getId())
            ->getQuery()->getResult();

        return $res;
    }

    /**
     * @param Summit $summit
     * @return SummitMemberSchedule[]
     */
    public function getScheduleBySummit(Summit $summit)
    {

        $query = $this->createQuery("SELECT s from models\main\SummitMemberSchedule s
        JOIN s.member m
        JOIN s.event e 
        JOIN e.summit su WHERE su.id = :summit_id and m.id = :member_id and e.published = 1 ");

        return $query
            ->setParameter('member_id', $this->getId())
            ->setParameter('summit_id', $summit->getId())
            ->getResult();
    }

    /**
     * @param Summit $summit
     * @return SummitMemberFavorite[]
     */
    public function getFavoritesSummitEventsBySummit(Summit $summit)
    {
        $query = $this->createQuery("SELECT f from models\main\SummitMemberFavorite f
        JOIN f.member m
        JOIN f.event e 
        JOIN e.summit su WHERE su.id = :summit_id and m.id = :member_id and e.published = 1 ");

        return $query
            ->setParameter('member_id', $this->getId())
            ->setParameter('summit_id', $summit->getId())
            ->getResult();
    }

    /**
     * @param Summit $summit
     * @return array|null
     */
    public function getSyncInfoBy(Summit $summit)
    {
        try {
            $criteria = Criteria::create();
            $criteria->where(Criteria::expr()->eq('summit', $summit));
            $criteria->andWhere(Criteria::expr()->eq('revoked', false));
            $res = $this->calendars_sync->matching($criteria)->first();
            return $res == false ? null : $res;
        } catch (NoResultException $ex1) {
            return null;
        } catch (NonUniqueResultException $ex2) {
            // should never happen
            return null;
        }
    }

    /**
     * @param Summit $summit
     * @return bool
     */
    public function hasSyncInfoFor(Summit $summit)
    {
        return !is_null($this->getSyncInfoBy($summit));
    }

    /**
     * @param CalendarSyncInfo $calendar_sync_info
     */
    public function removeFromCalendarSyncInfo(CalendarSyncInfo $calendar_sync_info)
    {
        $this->calendars_sync->removeElement($calendar_sync_info);
        $calendar_sync_info->clearOwner();
    }

    /**
     * @param int $affiliation_id
     * @return Affiliation|null
     */
    public function getAffiliationById($affiliation_id)
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', intval($affiliation_id)));

        $affiliation = $this->affiliations->matching($criteria)->first();

        return $affiliation ? $affiliation : null;
    }

    /**
     * @param Affiliation $affiliation
     * @return $this
     */
    public function removeAffiliation(Affiliation $affiliation)
    {
        if ($this->affiliations->contains($affiliation)) {
            $this->affiliations->removeElement($affiliation);
            $affiliation->clearOwner();
        }
        return $this;
    }

    /**
     * @param Affiliation $affiliation
     * @return $this
     */
    public function addAffiliation(Affiliation $affiliation)
    {
        if (!$this->affiliations->contains($affiliation)) {
            $this->affiliations->add($affiliation);
            $affiliation->setOwner($this);
        }
        return $this;
    }

    /**
     * @param int $rsvp_id
     * @return RSVP|null
     */
    public function getRsvpById($rsvp_id)
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', $rsvp_id));

        $rsvp = $this->rsvp->matching($criteria)->first();

        return $rsvp ? $rsvp : null;
    }

    /**
     * @param RSVP $rsvp
     * @return $this
     */
    public function removeRsvp(RSVP $rsvp)
    {
        $this->rsvp->removeElement($rsvp);
        return $this;
    }

    /**
     * @return string
     */
    public function getFullName(): ?string
    {
        $fullname = $this->first_name;
        if (!empty($this->last_name)) {
            if (!empty($fullname)) $fullname .= ' ';
            $fullname .= $this->last_name;
        }
        return $fullname;
    }

    /**
     * @return bool
     */
    public function hasPhoto()
    {
        return $this->getPhotoId() > 0;
    }

    /**
     * @return int
     */
    public function getPhotoId()
    {
        try {
            if (is_null($this->photo)) return 0;
            return $this->photo->getId();
        } catch (\Exception $ex) {
            return 0;
        }
    }

    /**
     * @return string|null
     */
    public function getProfilePhotoUrl(): ?string
    {
        $default_pic = Config::get("app.default_profile_image", null);
        try {

            $photoUrl = null;

            if (!empty($this->external_pic)) {
                $photoUrl = $this->external_pic;
            }

            if (empty($photoUrl) && $this->hasPhoto() && $photo = $this->getPhoto()) {
                $photoUrl = $photo->getUrl();
            }

            if (empty($photoUrl) && !empty($default_pic))
                $photoUrl = $default_pic;

            if (empty($photoUrl))
                $photoUrl = $this->getGravatarUrl();

            return $photoUrl;
        } catch (\Exception $ex) {
            Log::warning($ex);
        }
        if (!empty($default_pic))
            return $default_pic;
        return $this->getGravatarUrl();
    }

    /**
     * Get either a Gravatar URL or complete image tag for a specified email address.
     */
    private function getGravatarUrl(): string
    {
        $url = 'https://www.gravatar.com/avatar/';
        $url .= md5(strtolower(trim($this->email)));
        return $url;
    }

    /**
     * @param SummitRoomReservation $reservation
     * @return $this
     */
    public function addReservation(SummitRoomReservation $reservation)
    {
        if ($this->reservations->contains($reservation)) return $this;
        $this->reservations->add($reservation);
        $reservation->setOwner($this);
        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getReservations()
    {
        return $this->reservations;
    }

    /**
     * @param Summit $summit
     * @return int
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function getReservationsCountBySummit(Summit $summit): int
    {
        $query = $this->createQuery("SELECT count(rv.id) from models\summit\SummitRoomReservation rv
        JOIN rv.owner o 
        JOIN rv.room r 
        JOIN r.venue v 
        JOIN v.summit s 
        WHERE s.id = :summit_id AND o.id = :owner_id and rv.status not in (:status)");
        return $query
            ->setParameter('summit_id', $summit->getId())
            ->setParameter('owner_id', $this->getId())
            ->setParameter('status', [
                SummitRoomReservation::RequestedRefundStatus,
                SummitRoomReservation::RefundedStatus,
                SummitRoomReservation::Canceled
            ])
            ->getSingleScalarResult();
    }

    /**
     * @param Summit $summit
     * @return SummitRoomReservation[]
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function getReservationsBySummit(Summit $summit)
    {
        $query = $this->createQuery("SELECT rv from models\summit\SummitRoomReservation rv
        JOIN rv.owner o 
        JOIN rv.room r 
        JOIN r.venue v 
        JOIN v.summit s 
        WHERE s.id = :summit_id AND o.id = :owner_id");
        return $query
            ->setParameter('summit_id', $summit->getId())
            ->setParameter('owner_id', $this->getId())
            ->getResult();
    }

    /**
     * @param int $reservation_id
     * @return SummitRoomReservation
     */
    public function getReservationById(int $reservation_id): ?SummitRoomReservation
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq("id", $reservation_id));

        return $this->reservations->matching($criteria)->first();
    }

    /**
     * @param string|null $first_name
     */
    public function setFirstName(?string $first_name): void
    {
        Log::debug(sprintf("Member::setFirstName %s (%s)", $first_name, $this->id));
        $resource_server_ctx = App::make(IResourceServerContext::class);
        if ($resource_server_ctx->getCurrentUserEmail() === $this->email) {
            // if this member is current user , then update it also on auth context to avoid unwanted overwrites
            $resource_server_ctx->updateAuthContextVar(IResourceServerContext::UserFirstName, $first_name);
        }
        $this->first_name = $first_name;
    }

    /**
     * @param string|null $bio
     */
    public function setBio(?string $bio): void
    {
        $this->bio = $bio;
    }

    /**
     * @param string|null $last_name
     */
    public function setLastName(?string $last_name): void
    {
        Log::debug(sprintf("Member::setLastName %s (%s)", $last_name, $this->id));
        $resource_server_ctx = App::make(IResourceServerContext::class);
        if ($resource_server_ctx->getCurrentUserEmail() === $this->email) {
            // if this member is current user , then update it also on auth context to avoid unwanted overwrites
            $resource_server_ctx->updateAuthContextVar(IResourceServerContext::UserLastName, $last_name);
        }
        $this->last_name = $last_name;
    }

    /**
     * @return bool
     */
    public function hasSpeaker()
    {
        return $this->getSpeakerId() > 0;
    }

    /**
     * @return PresentationSpeaker|null
     */
    public function getSpeaker(): ?PresentationSpeaker
    {
        return $this->speaker;
    }

    /**
     * @return int
     */
    public function getSpeakerId()
    {
        try {
            if (is_null($this->speaker)) return 0;
            return $this->speaker->getId();
        } catch (\Exception $ex) {
            return 0;
        }
    }

    public function setSpeaker(PresentationSpeaker $speaker)
    {
        $this->speaker = $speaker;
    }

    public function clearSpeaker()
    {
        $this->speaker = null;
    }

    /**
     * @return int|null
     */
    public function getUserExternalId(): ?int
    {
        return $this->user_external_id;
    }

    /**
     * @param int $user_external_id
     */
    public function setUserExternalId(int $user_external_id): void
    {
        $this->user_external_id = $user_external_id;
    }

    /**
     * @return Sponsor[]
     */
    public function getSponsorMemberships()
    {
        return $this->sponsor_memberships;
    }

    /**
     * @return Sponsor[]
     */
    public function getLastNSponsorMemberships($last_n = 2)
    {
        $criteria = Criteria::create()
            ->orderBy(['id' => Criteria::DESC])
            ->setMaxResults($last_n);
        return $this->sponsor_memberships->matching($criteria);
    }

    /**
     * @return Sponsor[]
     * @throws \Exception
     */
    public function getActiveSummitsSponsorMemberships()
    {
        $dql = <<<DQL
SELECT sp 
FROM models\summit\Sponsor sp
JOIN sp.members m
JOIN sp.summit s 
WHERE m.id = :member_id
AND s.end_date >= :now
ORDER BY s.begin_date ASC
DQL;

        $query = $this->createQuery($dql);
        return $query
            ->setParameter('member_id', $this->getId())
            ->setParameter('now', new \DateTime('now', new \DateTimeZone('UTC')))
            ->getResult();
    }

    /**
     * @return array
     */
    public function getSponsorMembershipIds(Summit $summit): array
    {
        $sql = <<<SQL
SELECT DISTINCT(SponsorID)
FROM Sponsor_Users
INNER JOIN Sponsor ON Sponsor.ID = Sponsor_Users.SponsorID
WHERE MemberID = :member_id AND Sponsor.SummitID = :summit_id
SQL;

        $stmt = $this->prepareRawSQL($sql,  [
            'member_id' => $this->getId(),
            'summit_id' => $summit->getId(),
        ]);
        $res = $stmt->executeQuery();
        return $res->fetchFirstColumn();
    }

    public function hasSponsorMembershipsFor(Summit $summit, Sponsor $sponsor = null): bool
    {
        try {
            if(!$this->isSponsorUser()) return false;
        $sql = <<<SQL
SELECT COUNT(Sponsor_Users.SponsorID)
FROM Sponsor_Users
INNER JOIN Sponsor ON Sponsor.ID = Sponsor_Users.SponsorID
WHERE 
    MemberID = :member_id 
    AND Sponsor.SummitID = :summit_id
SQL;

        $params =   [
            'member_id' => $this->getId(),
            'summit_id' => $summit->getId(),
        ];

        if(!is_null($sponsor)) {
            $sql .= " AND Sponsor.ID = :sponsor_id";
            $params['sponsor_id'] = $sponsor->getId();
        }

        $stmt = $this->prepareRawSQL($sql, $params);
        $res = $stmt->executeQuery();
        $res = $res->fetchFirstColumn();
        return intval($res[0]) > 0;
        } catch (\Exception $ex) {
            return false;
        }
    }


    /**
     * @return ArrayCollection|SummitOrder[]
     */
    public function getSummitRegistrationOrders()
    {
        return $this->summit_registration_orders;
    }

    /**
     * @param Summit $summit
     * @return bool
     */
    public function hasPaidRegistrationOrderForSummit(Summit $summit): bool
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('summit', $summit))
            ->andWhere(Criteria::expr()->eq('status', IOrderConstants::PaidStatus));
        return $this->summit_registration_orders->matching($criteria)->count() > 0;
    }

    public function getPadRegistrationOrdersForSummit(Summit $summit)
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('summit', $summit))
            ->andWhere(Criteria::expr()->eq('status', IOrderConstants::PaidStatus));
        return $this->summit_registration_orders->matching($criteria);
    }

    /**
     * @param int $order_id
     * @return SummitOrder|null
     */
    public function getSummitRegistrationOrderById(int $order_id): ?SummitOrder
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq("id", $order_id));
        $order = $this->summit_registration_orders->matching($criteria)->first();

        return $order === false ? null : $order;
    }

    /**
     * @param SummitOrder $summit_order
     */
    public function addSummitRegistrationOrder(SummitOrder $summit_order)
    {
        if ($this->summit_registration_orders->contains($summit_order)) return;
        $this->summit_registration_orders->add($summit_order);
        $summit_order->setOwner($this);
    }

    /**
     * @param Summit $summit
     * @return Sponsor|null
     */
    public function getSponsorBySummit(Summit $summit): ?Sponsor
    {
        $sponsor = $this->sponsor_memberships->filter(function ($entity) use ($summit) {
            return $entity->getSummitId() == $summit->getId();
        })->first();

        return $sponsor === false ? null : $sponsor;
    }

    /**
     * @return string|null
     */
    public function getMembershipType(): ?string
    {
        return $this->membership_type;
    }


    public function clearGroups():void{
        $this->groups->clear();
    }
    /**
     * @param Group $group
     */
    public function add2Group(Group $group)
    {
        if ($this->groups->contains($group)) return;
        $this->groups->add($group);
    }

    public function removeFromGroup(Group $group)
    {
        if (!$this->groups->contains($group)) return;
        $this->groups->removeElement($group);
        //$group->removeMember($this);
    }

    /**
     * @param PersonalCalendarShareInfo $link
     */
    public function addScheduleShareableLink(PersonalCalendarShareInfo $link)
    {
        if ($this->schedule_shareable_links->contains($link)) return;
        $this->schedule_shareable_links->add($link);
        $link->setOwner($this);
    }

    /**
     * @param PersonalCalendarShareInfo $link
     */
    public function removeScheduleShareableLink(PersonalCalendarShareInfo $link)
    {
        if (!$this->schedule_shareable_links->contains($link)) return;
        $this->schedule_shareable_links->removeElement($link);
        $link->clearOwner();
    }

    /**
     * @param Summit $summit
     * @return PersonalCalendarShareInfo|null
     */
    public function getScheduleShareableLinkBy(Summit $summit): ?PersonalCalendarShareInfo
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('summit', $summit));
        $criteria->andWhere(Criteria::expr()->eq('revoked', false));
        $link = $this->schedule_shareable_links->matching($criteria)->first();
        return $link === false ? null : $link;
    }

    /**
     * @param Summit $summit
     * @return PersonalCalendarShareInfo|null
     * @throws \Exception
     */
    public function createScheduleShareableLink(Summit $summit): ?PersonalCalendarShareInfo
    {
        $former_link = $this->getScheduleShareableLinkBy($summit);

        if (!is_null($former_link)) {
            return $former_link;
        }

        $link = new PersonalCalendarShareInfo();
        $summit->addScheduleShareableLink($link);
        $this->addScheduleShareableLink($link);
        $link->generateCid();
        return $link;
    }

    /**
     * @param SummitAdministratorPermissionGroup $group
     */
    public function add2SummitAdministratorPermissionGroup(SummitAdministratorPermissionGroup $group)
    {
        if ($this->summit_permission_groups->contains($group)) return;
        $this->summit_permission_groups->add($group);
    }

    public function removeFromSummitAdministratorPermissionGroup(SummitAdministratorPermissionGroup $group)
    {
        if (!$this->summit_permission_groups->contains($group)) return;
        $this->summit_permission_groups->removeElement($group);
    }

    public function getSummitAdministratorPermissionGroup()
    {
        return $this->summit_permission_groups;
    }

    /**
     * @return array
     */
    public function getAllAllowedSummitsIds(): array
    {
        return MemberSummitStrategyFactory::getMemberSummitStrategy($this)
            ->getAllAllowedSummitIds();
    }

    /**
     * @return bool
     */
    public function hasAllowedSummits(): bool
    {
        return count($this->getAllAllowedSummitsIds()) > 0;
    }

    /**
     * @param Summit $summit
     * @return bool
     */
    public function hasPaidTicketOnSummit(Summit $summit): bool
    {
        return count($this->getPaidSummitTicketsIds($summit)) > 0;
    }

    /**
     * @param Summit $summit
     * @param string $groupSlug
     * @return bool
     */
    public function hasPermissionForOnGroup(Summit $summit, string $groupSlug): bool
    {
        if (!SummitAdministratorPermissionGroup::isValidGroup($groupSlug)) return false;

        $sql = <<<SQL
SELECT DISTINCT(SummitAdministratorPermissionGroup_Summits.SummitID) 
FROM SummitAdministratorPermissionGroup_Members 
INNER JOIN SummitAdministratorPermissionGroup_Summits ON 
SummitAdministratorPermissionGroup_Summits.SummitAdministratorPermissionGroupID = SummitAdministratorPermissionGroup_Members.SummitAdministratorPermissionGroupID
WHERE SummitAdministratorPermissionGroup_Members.MemberID = :member_id
AND 
SummitAdministratorPermissionGroup_Summits.SummitID = :summit_id
SQL;

        $stmt = $this->prepareRawSQL($sql,
            [
                'member_id' => $this->getId(),
                'summit_id' => $summit->getId()
            ]
        );
        $res = $stmt->executeQuery();
        $allowed_summits = $res->fetchFirstColumn();
        return count($allowed_summits) > 0 && $this->isOnGroup($groupSlug);
    }

    /**
     * @param Summit $summit
     * @return bool
     */
    public function hasPermissionFor(Summit $summit): bool
    {
        $sql = <<<SQL
SELECT DISTINCT(SummitAdministratorPermissionGroup_Summits.SummitID) 
FROM SummitAdministratorPermissionGroup_Members 
INNER JOIN SummitAdministratorPermissionGroup_Summits ON 
SummitAdministratorPermissionGroup_Summits.SummitAdministratorPermissionGroupID = SummitAdministratorPermissionGroup_Members.SummitAdministratorPermissionGroupID
WHERE SummitAdministratorPermissionGroup_Members.MemberID = :member_id
AND 
SummitAdministratorPermissionGroup_Summits.SummitID = :summit_id
SQL;

        $stmt = $this->prepareRawSQL($sql,
            [
                'member_id' => $this->getId(),
                'summit_id' => $summit->getId()
            ]
        );
        $res = $stmt->executeQuery();
        $allowed_summits = $res->fetchFirstColumn();
        return count($allowed_summits) > 0;
    }

    /**
     * @param Summit $summit
     * @return int[]
     */
    public function getPaidSummitTicketsIds(Summit $summit)
    {
        $sql = <<<SQL
SELECT SummitAttendeeTicket.ID 
FROM SummitAttendeeTicket FORCE INDEX (IDX_SummitAttendeeTicket_Owner_Status_Active)
INNER JOIN SummitAttendee FORCE INDEX (IDX_SummitAttendee_Summit_Email) ON SummitAttendee.ID = SummitAttendeeTicket.OwnerID
WHERE 
SummitAttendee.Email = :member_email AND 
SummitAttendee.SummitID = :summit_id AND 
SummitAttendeeTicket.OwnerID = SummitAttendee.ID AND
SummitAttendeeTicket.Status = :ticket_status AND 
SummitAttendeeTicket.IsActive = 1
SQL;

        $stmt = $this->prepareRawSQL($sql,  [
            'member_email' => $this->email,
            'ticket_status' => IOrderConstants::PaidStatus,
            'summit_id' => $summit->getId(),
        ]);
        $res = $stmt->executeQuery();
        $res = $res->fetchFirstColumn();
        if(count($res) > 0) return $res;

        $sql = <<<SQL
SELECT SummitAttendeeTicket.ID 
FROM SummitAttendeeTicket FORCE INDEX (IDX_SummitAttendeeTicket_Owner_Status_Active)
INNER JOIN SummitAttendee FORCE INDEX (IDX_SummitAttendee_Summit_Member) ON SummitAttendee.ID = SummitAttendeeTicket.OwnerID
WHERE 
SummitAttendee.MemberID = :member_id AND 
SummitAttendee.SummitID = :summit_id AND 
SummitAttendeeTicket.OwnerID = SummitAttendee.ID AND
SummitAttendeeTicket.Status = :ticket_status AND 
SummitAttendeeTicket.IsActive = 1
SQL;

        $stmt = $this->prepareRawSQL($sql,  [
            'member_id' => $this->getId(),
            'ticket_status' => IOrderConstants::PaidStatus,
            'summit_id' => $summit->getId(),
        ]);
        $res = $stmt->executeQuery();
        return $res->fetchFirstColumn();
    }

    /**
     * @param Summit $summit
     * @return bool
     */
    public function hasSummitAccess(Summit $summit): bool
    {
        return count($this->getPaidSummitTicketsIds($summit)) > 0;
    }

    /**
     * @param Summit $summit
     * @return SummitAttendeeTicket[]
     */
    public function getPaidSummitTickets(Summit $summit):array
    {
        return $this->getPaidSummitTicketsBySummitId($summit->getId());
    }

    /**
     * @param int $summit_id
     * @return SummitAttendeeTicket[]
     */
    public function getPaidSummitTicketsBySummitId(int $summit_id): array
    {

        $sql = <<<SQL
SELECT DISTINCT T.* 
FROM SummitAttendeeTicket T FORCE INDEX (IDX_SummitAttendeeTicket_Owner_Status_Active) 
WHERE 
    T.Status = :TICKET_STATUS
    AND T.IsActive = 1 
    AND T.OwnerID IN 
    ( 
        SELECT SummitAttendee.ID FROM SummitAttendee FORCE INDEX(IDX_SummitAttendee_SummitID_MemberID_Email) 
        LEFT JOIN Member ON Member.ID = SummitAttendee.MemberID 
        WHERE SummitAttendee.SummitID = :SUMMIT_ID 
        AND 
        ( 
            SummitAttendee.MemberID = :MEMBER_ID OR 
            SummitAttendee.Email = :MEMBER_EMAIL
        ) 
    )
SQL;

        $bindings = [
            'TICKET_STATUS' => IOrderConstants::PaidStatus,
            'SUMMIT_ID' => $summit_id,
            'MEMBER_ID' => $this->id,
            'MEMBER_EMAIL' => $this->email,
        ];

        $rsm = new ResultSetMappingBuilder($this->getEM());
        $rsm->addRootEntityFromClassMetadata(SummitAttendeeTicket::class, 'T');

        // build rsm here
        $native_query = $this->getEM()->createNativeQuery($sql, $rsm);

        foreach ($bindings as $k => $v)
            $native_query->setParameter($k, $v);

        return $native_query->getResult();
    }

    /**
     * @return string
     */
    public function getExternalPic(): ?string
    {
        return $this->external_pic;
    }

    /**
     * @param string $external_pic
     */
    public function setExternalPic(string $external_pic): void
    {
        $this->external_pic = $external_pic;
    }


    public function resignFoundationMembership()
    {
        // Remove member from Foundation group
        foreach ($this->groups as $g) {
            if ($g->getCode() === IGroup::FoundationMembers) {
                $this->removeFromGroup($g);
                break;
            }
        }

        // Remove Member's Legal Agreements
        $this->legal_agreements->clear();
        $this->membership_type = self::MembershipTypeCommunity;
        $this->resign_date = new \DateTime('now', new \DateTimeZone(self::DefaultTimeZone));
    }

    public function resignMembership()
    {
        // Remove Member's Legal Agreements
        $this->legal_agreements->clear();
        $this->affiliations->clear();
        $this->groups->clear();
        $this->membership_type = self::MembershipTypeNone;
        $this->resign_date = new \DateTime('now', new \DateTimeZone(self::DefaultTimeZone));
    }

    public function signFoundationMembership(LegalDocument $document)
    {
        if (!$this->isFoundationMember()) {
            // Set up member with legal agreement for becoming an OpenStack Foundation Member
            $legalAgreement = new LegalAgreement();
            $legalAgreement->setOwner($this);
            $legalAgreement->setDocument($document);
            $this->legal_agreements->add($legalAgreement);
            $this->membership_type = self::MembershipTypeFoundation;
            $this->resign_date = null;
        }
    }

    public function isFoundationMember()
    {
        return $this->belongsToGroup(IGroup::FoundationMembers) && $this->legal_agreements->count() > 0;
    }

    /**
     * @param SummitTrackChair $trackChair
     */
    public function addTrackChair(SummitTrackChair $trackChair)
    {
        if ($this->track_chairs->contains($trackChair)) return;
        $this->track_chairs->add($trackChair);
        $trackChair->setMember($this);
    }

    /**
     * @param SummitTrackChair $trackChair
     */
    public function removeTrackChair(SummitTrackChair $trackChair)
    {
        if (!$this->track_chairs->contains($trackChair)) return;
        $this->track_chairs->removeElement($trackChair);
        $trackChair->clearMember();
    }

    /**
     * @return ArrayCollection|SummitTrackChair[]
     */
    public function getTrackChairs()
    {
        return $this->track_chairs;
    }

    /**
     * @param Nomination $application
     */
    public function addElectionApplication(Nomination $application)
    {
        if ($this->election_applications->contains($application)) return;
        $this->election_applications->add($application);
    }

    /**
     * @return Candidate[]|ArrayCollection
     */
    public function getElectionApplications()
    {
        return $this->election_applications;
    }

    /**
     * @return array|mixed[]
     */
    public function getLatestElectionApplications()
    {
        $election_repository = EntityManager::getRepository(Election::class);
        $currentElection = $election_repository->getCurrent();
        if (is_null($currentElection)) return [];
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq("election", $currentElection));
        return $this->election_applications->matching($criteria)->toArray();
    }

    /**
     * @return array|mixed[]
     */
    public function getLatestElectionNominations()
    {
        $election_repository = EntityManager::getRepository(Election::class);
        $currentElection = $election_repository->getCurrent();
        if (is_null($currentElection)) return [];
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq("election", $currentElection));
        return $this->election_nominations->matching($criteria)->toArray();
    }

    /**
     * @param Member $candidate
     * @param Election $election
     * @return Nomination
     * @throws ValidationException
     */
    public function nominateCandidate(Member $candidate, Election $election): Nomination
    {

        if (!$this->isFoundationMember())
            throw new ValidationException("You are not a valid Voter.");

        if (!$election->isNominationsOpen())
            throw new ValidationException("Nomination Period is closed for election.");

        if (!$candidate->isFoundationMember())
            throw new ValidationException("Candidate is not valid.");

        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq("candidate", $candidate))
            ->andWhere(Criteria::expr()->eq("election", $election));

        if ($this->election_nominations->matching($criteria)->count() > 0) {
            throw new ValidationException(sprintf("You have already nominated %s.", $candidate->getFullName()));
        }

        // check max nominations
        if ($election->getNominationCountFor($candidate) >= Election::NominationLimit) {
            throw new ValidationException(sprintf("That's all the nominations that are required to appear on the election ballot. You may want to nominate someone else who you think would be a good candidate."));
        }

        $newNomination = new Nomination($this, $candidate, $election);
        $this->election_nominations->add($newNomination);
        $election->addNomination($newNomination);
        $candidate->addElectionApplication($newNomination);

        // check if exist a candidate profile for proposed candidate on current election

        if (!$election->isCandidate($candidate)) {
            $election->createCandidancy($candidate);
        }

        return $newNomination;
    }

    /**
     * @param Election $election
     * @return ArrayCollection|\Doctrine\Common\Collections\Collection
     */
    public function getElectionNominationsFor(Election $election)
    {

        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq("election", $election));

        return $this->election_nominations->matching($criteria);
    }

    /**
     * @param Election $election
     * @return int
     */
    public function getElectionNominationsCountFor(Election $election): int
    {

        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq("election", $election));

        return $this->election_nominations->matching($criteria)->count();
    }

    /**
     * @param Election $election
     * @return int
     */
    public function getElectionApplicationsCountFor(Election $election): int
    {
        try {
            $sql = <<<SQL
            SELECT COUNT(DISTINCT(C.ID)) AS qty
            FROM CandidateNomination AS C
            WHERE C.ElectionID = :election_id AND 
                  C.CandidateID = :candidate_id
SQL;
            $stmt = $this->prepareRawSQL($sql, [
                'election_id' => $election->getId(),
                'candidate_id' => $this->id
            ]);
            $res = $stmt->executeQuery();
            $res = $res->fetchFirstColumn();
            return count($res) > 0 ? $res[0] : 0;
        } catch (\Exception $ex) {
            Log::warning($ex);
        }
        return 0;
    }

    /**
     * @param Candidate $candidate
     */
    public function addCandidateProfile(Candidate $candidate): void
    {
        if ($this->candidate_profiles->contains($candidate)) return;
        $this->candidate_profiles->add($candidate);
        $candidate->setMember($this);
    }

    /**
     * @return Candidate[]|ArrayCollection
     */
    public function getCandidateProfiles()
    {
        return $this->candidate_profiles;
    }

    /**
     * @return int
     */
    public function getLatestCandidateProfileId(): int
    {
        $res = $this->getLatestCandidateProfile();
        return $res ? $res->getId() : 0;
    }

    /**
     * @return Candidate|null
     */
    public function getLatestCandidateProfile(): ?Candidate
    {
        $election_repository = EntityManager::getRepository(Election::class);
        $currentElection = $election_repository->getCurrent();
        if (is_null($currentElection)) return null;
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq("election", $currentElection));
        $res = $this->candidate_profiles->matching($criteria)->first();
        return $res === false ? null : $res;
    }

    /**
     * @return bool
     */
    public function hasLatestCandidateProfile(): bool
    {
        $election_repository = EntityManager::getRepository(Election::class);
        $currentElection = $election_repository->getCurrent();
        if (is_null($currentElection)) return false;
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq("election", $currentElection));
        return $this->candidate_profiles->matching($criteria)->count() > 0;
    }

    /**
     * @return array|string[]
     */
    public function getProjects(): array
    {
        if (empty($this->projects)) return [];
        return explode(',', $this->projects);
    }

    /**
     * @param array|string[] $projects
     */
    public function setProjects(array $projects): void
    {
        $this->projects = implode(',', $projects);
    }

    /**
     * @return string|null
     */
    public function getOtherProject(): ?string
    {
        return $this->other_project;
    }

    /**
     * @param string $other_project
     */
    public function setOtherProject(string $other_project): void
    {
        $this->other_project = $other_project;
    }

    /**
     * @return bool
     */
    public function isDisplayOnSite(): bool
    {
        return $this->display_on_site;
    }

    /**
     * @param bool $display_on_site
     */
    public function setDisplayOnSite(bool $display_on_site): void
    {
        $this->display_on_site = $display_on_site;
    }

    /**
     * @return bool
     */
    public function isSubscribedToNewsletter(): bool
    {
        return $this->subscribed_to_newsletter;
    }

    /**
     * @param bool $subscribed_to_newsletter
     */
    public function setSubscribedToNewsletter(bool $subscribed_to_newsletter): void
    {
        $this->subscribed_to_newsletter = $subscribed_to_newsletter;
    }

    /**
     * @return string|null
     */
    public function getShirtSize(): ?string
    {
        return $this->shirt_size;
    }

    /**
     * @param string $shirt_size
     * @throws ValidationException
     */
    public function setShirtSize(string $shirt_size): void
    {
        if (!in_array($shirt_size, self::AllowedShirtSizes))
            throw new ValidationException(sprintf("shirt_size %s is not valid one.", $shirt_size));
        $this->shirt_size = $shirt_size;
    }

    /**
     * @return array|string[]
     */
    public function getFoodPreference(): array
    {
        if (empty($this->food_preference)) return [];
        return explode(',', $this->food_preference);
    }

    /**
     * @param array $food_preference
     * @throws ValidationException
     */
    public function setFoodPreference(array $food_preference): void
    {
        foreach ($food_preference as $food) {
            if (!in_array($food, self::AllowedFoodPreferences))
                throw new ValidationException(sprintf("food_preference %s is not valid one.", $food));
        }
        $this->food_preference = implode(',', $food_preference);
    }

    /**
     * @return string|null
     */
    public function getOtherFoodPreference(): ?string
    {
        return $this->other_food_preference;
    }

    /**
     * @param string $other_food_preference
     */
    public function setOtherFoodPreference(string $other_food_preference): void
    {
        $this->other_food_preference = $other_food_preference;
    }

    const FoodPreference_Vegan = 'Vegan';
    const FoodPreference_Vegetarian = 'Vegetarian';
    const FoodPreference_Gluten = 'Gluten';
    const FoodPreference_Peanut = 'Peanut';

    const AllowedFoodPreferences = [
        self::FoodPreference_Vegan,
        self::FoodPreference_Vegetarian,
        self::FoodPreference_Gluten,
        self::FoodPreference_Peanut,
    ];

    const ShirtSize_ExtraSmall = 'Extra Small';
    const ShirtSize_Small = 'Small';
    const ShirtSize_Medium = 'Medium';
    const ShirtSize_Large = 'Large';
    const ShirtSize_XL = 'XL';
    const ShirtSize_XXL = 'XXL';
    const ShirtSize_WSmall = 'WS';
    const ShirtSize_WMedium = 'WM';
    const ShirtSize_WLarge = 'WL';
    const ShirtSize_WXL = 'WXL';
    const ShirtSize_WXXL = 'WXXL';

    const AllowedShirtSizes = [
        self::ShirtSize_ExtraSmall,
        self::ShirtSize_Small,
        self::ShirtSize_Medium,
        self::ShirtSize_Large,
        self::ShirtSize_XL,
        self::ShirtSize_XXL,
        self::ShirtSize_WSmall,
        self::ShirtSize_WMedium,
        self::ShirtSize_WLarge,
        self::ShirtSize_WXL,
        self::ShirtSize_WXXL,
    ];

    /**
     * @return string
     */
    public function getCompany(): ?string
    {
        return $this->company;
    }

    /**
     * @param string $company
     */
    public function setCompany(string $company): void
    {
        $this->company = $company;
    }

    /**
     * @param string $github_user
     */
    public function setGithubUser(string $github_user): void
    {
        $this->github_user = $github_user;
    }

    /**
     * @param string $linked_in_profile
     */
    public function setLinkedInProfile(string $linked_in_profile): void
    {
        $this->linked_in_profile = $linked_in_profile;
    }

    /**
     * @param string $irc_handle
     */
    public function setIrcHandle(string $irc_handle): void
    {
        $this->irc_handle = $irc_handle;
    }

    /**
     * @param string $twitter_handle
     */
    public function setTwitterHandle(string $twitter_handle): void
    {
        $this->twitter_handle = $twitter_handle;
    }

    /**
     * @param string $gender
     */
    public function setGender(string $gender): void
    {
        $this->gender = $gender;
    }

    /**
     * @param Summit $summit
     * @param bool $exclude_privates_tracks
     * @param array $excluded_tracks
     * @param Filter|null $filter
     * @return int|mixed|string
     */
    public function getAcceptedPresentations
    (
        Summit  $summit,
        ?Filter $filter = null
    )
    {
        $extraWhere = '';
        if (!is_null($filter)) {
            if ($filter->hasFilter("presentations_selection_plan_id")) {
                $extraWhere .= " AND sel_p.id IN (:selection_plan_id)";
            }
            if ($filter->hasFilter("presentations_track_id")) {
                $extraWhere .= " AND cat.id IN (:track_id)";
            }
            if ($filter->hasFilter("presentations_type_id")) {
                $extraWhere .= " AND t.id IN (:type_id)";
            }
            if($filter->hasFilter("has_media_upload_with_type"))
            {
                $extraWhere .= " AND EXISTS (
                    SELECT pmu_12.id 
                    FROM models\summit\PresentationMediaUpload pmu_12
                    JOIN pmu_12.media_upload_type mut_12
                    JOIN pmu_12.presentation p__12
                    WHERE p.id = p__12.id AND mut_12.id IN (:media_upload_type_id)
                )";
            }
            if($filter->hasFilter("has_not_media_upload_with_type"))
            {
                $extraWhere .= " AND NOT EXISTS (
                    SELECT pmu_12.id 
                    FROM models\summit\PresentationMediaUpload pmu_12
                    JOIN pmu_12.media_upload_type mut_12
                    JOIN pmu_12.presentation p__12
                    WHERE p.id = p__12.id AND mut_12.id IN (:media_upload_type_id)
                )";
            }
            if($filter->hasFilter("is_speaker")){
                $value = to_boolean($filter->getValue("is_speaker")[0]);
                if($value)
                    $extraWhere .=  'AND (
                                 EXISTS (
                                    SELECT __p61.id FROM models\summit\Presentation __p61 
                                    JOIN __p61.created_by __c61 WITH __c61 = :member_id 
                                    JOIN __p61.speakers __pspk61 
                                    JOIN __pspk61.speaker __spk61 WITH __spk61.member = :member_id 
                                    WHERE __p61.summit = :summit_id
                                 ) 
                                 OR 
                                 EXISTS (
                                    SELECT __p62.id FROM models\summit\Presentation __p62 
                                    JOIN __p62.created_by __c62 WITH __c62 = :member_id
                                    JOIN __p62.moderator __md62 WITH __md62.member = :member_id 
                                    WHERE __p62.summit = :summit_id
                                 ))';
                else
                    $extraWhere .= ' AND (
                                NOT EXISTS (
                                    SELECT __p61.id FROM models\summit\Presentation __p61 
                                    JOIN __p61.created_by __c61 WITH __c61 = :member_id 
                                    JOIN __p61.speakers __pspk61
                                    JOIN __pspk61.speaker __spk61 WITH __spk61.member = :member_id 
                                    WHERE __p61.summit = :summit_id
                                ) 
                                AND  
                                NOT EXISTS (
                                    SELECT __p62.id FROM models\summit\Presentation __p62 
                                    JOIN __p62.created_by __c62 WITH __c62 = :member_id 
                                    JOIN __p62.moderator __md62 WITH __md62.member = :member_id 
                                    WHERE __p62.summit = :summit_id
                                ))';
            }
        }
        $query = $this->createQuery(sprintf("
            SELECT DISTINCT p from models\summit\Presentation p 
            JOIN p.summit s 
            LEFT JOIN p.speakers a_spk 
            LEFT JOIN p.moderator mod
            LEFT JOIN a_spk.speaker spk 
            JOIN p.created_by cb 
            LEFT JOIN p.selection_plan sel_p  
            LEFT JOIN p.materials m 
            LEFT JOIN models\summit\PresentationMediaUpload pmu WITH pmu.id = m.id 
            LEFT JOIN pmu.media_upload_type mut
            JOIN p.type t
            JOIN p.category cat 
            LEFT JOIN p.selected_presentations ssp 
            LEFT JOIN ssp.list sspl 
            WHERE s.id = :summit_id 
            AND cb = :submitter_id
            AND 
            (
                ( 
                    ssp.order is not null AND
                    ssp.order <= cat.session_count AND
                    ssp.collection = '%s' AND
                    sspl.list_type = '%s' AND sspl.list_class = '%s'
                )
                OR p.published = 1 
            )
            " . $extraWhere,
            SummitSelectedPresentation::CollectionSelected,
            SummitSelectedPresentationList::Group,
            SummitSelectedPresentationList::Session
        ));

        $query = $query
            ->setParameter('summit_id', $summit->getId())
            ->setParameter('submitter_id', $this->id);

        if (!is_null($filter)) {
            if ($filter->hasFilter("presentations_selection_plan_id")) {
                $v = $filter->getValue("presentations_selection_plan_id");
                $query = $query->setParameter("selection_plan_id", $v);
            }
            if ($filter->hasFilter("presentations_track_id")) {
                $v = $filter->getValue("presentations_track_id");
                $query = $query->setParameter("track_id", $v);
            }
            if ($filter->hasFilter("presentations_type_id")) {
                $v = $filter->getValue("presentations_type_id");
                $query = $query->setParameter("type_id", $v);
            }
            if($filter->hasFilter("has_media_upload_with_type"))
            {
                $v = $filter->getValue("has_media_upload_with_type");
                $query = $query->setParameter("media_upload_type_id", $v);
            }
            if($filter->hasFilter("has_not_media_upload_with_type"))
            {
                $v = $filter->getValue("has_not_media_upload_with_type");
                $query = $query->setParameter("media_upload_type_id", $v);
            }
            if($filter->hasFilter("is_speaker")){
               $query->setParameter("member_id", $this->id);
            }
        }

        Log::debug
        (
            sprintf
            (
                "Member::getAcceptedPresentations id %s query %s",
                $this->id,
                $query->getDQL()
            )
        );

        return $query->getResult();
    }

    /**
     * @param Summit $summit
     * @param Filter|null $filter
     * @return array
     */
    public function getAcceptedPresentationIds
    (
        Summit  $summit,
        ?Filter $filter = null
    )
    {
        $ids = [];
        $acceptedPresentations = $this->getAcceptedPresentations($summit, $filter);
        foreach ($acceptedPresentations as $p) {
            $ids[] = intval($p->getId());
        }
        return $ids;
    }

    /**
     * @param Summit $summit
     * @param Filter|null $filter
     * @return bool
     */
    public function hasAcceptedPresentations
    (
        Summit  $summit,
        ?Filter $filter = null
    ):bool
    {
        return count($this->getAcceptedPresentations($summit, $filter)) > 0;
    }

    /**
     * @param Summit $summit
     * @param Filter|null $filter
     * @return int
     */
    public function getAcceptedPresentationsCount
    (
        Summit  $summit,
        ?Filter $filter = null
    ):int
    {
        return count($this->getAcceptedPresentations($summit, $filter));
    }


    /**
     * @param Summit $summit
     * @param Filter|null $filter
     * @return bool
     */
    public function hasAlternatePresentations
    (
        Summit  $summit,
        ?Filter $filter = null
    ):bool
    {
        return count($this->getAlternatePresentations($summit, $filter)) > 0;
    }


    /**
     * @param Summit $summit
     * @param Filter|null $filter
     * @return int
     */
    public function getAlternatePresentationsCount
    (
        Summit  $summit,
        ?Filter $filter = null
    ):int
    {
        return count($this->getAlternatePresentations($summit, $filter));
    }

    /**
     * @param Summit $summit
     * @param Filter|null $filter
     * @return array
     */
    public function getAlternatePresentations
    (
        Summit  $summit,
        ?Filter $filter = null
    )
    {
        $alternate_presentations = [];

        $extraWhere = '';
        if (!is_null($filter)) {
            if ($filter->hasFilter("presentations_selection_plan_id")) {
                $extraWhere .= " AND sel_p.id IN (:selection_plan_id)";
            }
            if ($filter->hasFilter("presentations_track_id")) {
                $extraWhere .= " AND cat.id IN (:track_id)";
            }
            if ($filter->hasFilter("presentations_type_id")) {
                $extraWhere .= " AND t.id IN (:type_id)";
            }
            if($filter->hasFilter("has_media_upload_with_type"))
            {
                $extraWhere .= " AND EXISTS (
                    SELECT pmu_12.id 
                    FROM models\summit\PresentationMediaUpload pmu_12
                    JOIN pmu_12.media_upload_type mut_12
                    JOIN pmu_12.presentation p__12
                    WHERE p.id = p__12.id AND mut_12.id IN (:media_upload_type_id)
                )";
            }
            if($filter->hasFilter("has_not_media_upload_with_type"))
            {
                $extraWhere .= " AND NOT EXISTS (
                    SELECT pmu_12.id 
                    FROM models\summit\PresentationMediaUpload pmu_12
                    JOIN pmu_12.media_upload_type mut_12
                    JOIN pmu_12.presentation p__12
                    WHERE p.id = p__12.id AND mut_12.id IN (:media_upload_type_id)
                )";
            }
            if($filter->hasFilter("is_speaker")){
                $value = to_boolean($filter->getValue("is_speaker")[0]);
                if($value)
                    $extraWhere .= " AND ( spk.member = :member_id OR mod.member = :member_id)";
                else
                    $extraWhere .= " AND ( (spk.member <> :member_id OR spk.member IS NULL) AND (mod.member <> :member_id OR mod.member IS NULL) )";
            }
        }

        $query = $this->createQuery("
        SELECT DISTINCT p from models\summit\Presentation p 
        JOIN p.summit s 
        LEFT JOIN p.moderator mod
        LEFT JOIN p.speakers a_spk 
        LEFT JOIN a_spk.speaker spk 
        JOIN p.created_by cb 
        JOIN p.selection_plan sel_p 
        LEFT JOIN p.materials m 
        LEFT JOIN models\summit\PresentationMediaUpload pmu WITH pmu.id = m.id 
        LEFT JOIN pmu.media_upload_type mut 
        JOIN p.type t 
        JOIN p.category cat 
        WHERE s.id = :summit_id 
        AND cb.id = :submitter_id" . $extraWhere);

        $query
            ->setParameter('summit_id', $summit->getId())
            ->setParameter('submitter_id', $this->id);

        if (!is_null($filter)) {
            if ($filter->hasFilter("presentations_selection_plan_id")) {
                $v = $filter->getValue("presentations_selection_plan_id");
                $query = $query->setParameter("selection_plan_id", $v);
            }
            if ($filter->hasFilter("presentations_track_id")) {
                $v = $filter->getValue("presentations_track_id");
                $query = $query->setParameter("track_id", $v);
            }
            if ($filter->hasFilter("presentations_type_id")) {
                $v = $filter->getValue("presentations_type_id");
                $query = $query->setParameter("type_id", $v);
            }
            if($filter->hasFilter("has_media_upload_with_type"))
            {
                $v = $filter->getValue("has_media_upload_with_type");
                $query = $query->setParameter("media_upload_type_id", $v);
            }
            if($filter->hasFilter("has_not_media_upload_with_type"))
            {
                $v = $filter->getValue("has_not_media_upload_with_type");
                $query = $query->setParameter("media_upload_type_id", $v);
            }
            if($filter->hasFilter("is_speaker")){
                $query->setParameter("member_id", $this->id);
            }
        }

        $presentations = $query->getResult();

        Log::debug
        (
            sprintf
            (
                "Member::getAlternatePresentations id %s query %s",
                $this->id,
                $query->getDQL()
            )
        );

        foreach ($presentations as $p) {
            if ($p->getSelectionStatus() == Presentation::SelectionStatus_Alternate) {
                $alternate_presentations[] = $p;
            }
        }

        return $alternate_presentations;
    }

    /**
     * @param Summit $summit
     * @param Filter|null $filter
     * @return array
     */
    public function getAlternatePresentationIds
    (
        Summit  $summit,
        ?Filter $filter = null
    )
    {
        $ids = [];
        $alternatePresentations = $this->getAlternatePresentations($summit, $filter);
        foreach ($alternatePresentations as $p) {
            $ids[] = intval($p->getId());
        }
        return $ids;
    }

    /**
     * @param Summit $summit ,
     * @param Filter|null $filter
     * @return bool
     */
    public function hasRejectedPresentations
    (
        Summit  $summit,
        ?Filter $filter = null
    ):bool
    {
        return count($this->getRejectedPresentations($summit, $filter)) > 0;
    }

    /**
     * @param Summit $summit ,
     * @param Filter|null $filter
     * @return int
     */
    public function getRejectedPresentationsCount
    (
        Summit  $summit,
        ?Filter $filter = null
    ):int
    {
        return count($this->getRejectedPresentations($summit, $filter));
    }

    /**
     * @param Summit $summit ,
     * @param Filter|null $filter
     * @return array
     */
    public function getRejectedPresentations
    (
        Summit  $summit,
        ?Filter $filter = null
    )
    {
        $rejected_presentations = [];

        $extraWhere = '';
        if (!is_null($filter)) {
            if ($filter->hasFilter("presentations_selection_plan_id")) {
                $extraWhere .= " AND sel_p.id IN (:selection_plan_id)";
            }
            if ($filter->hasFilter("presentations_track_id")) {
                $extraWhere .= " AND cat.id IN (:track_id)";
            }
            if ($filter->hasFilter("presentations_type_id")) {
                $extraWhere .= " AND t.id IN (:type_id)";
            }
            if($filter->hasFilter("has_media_upload_with_type"))
            {
                $extraWhere .= " AND EXISTS (
                    SELECT pmu_12.id 
                    FROM models\summit\PresentationMediaUpload pmu_12
                    JOIN pmu_12.media_upload_type mut_12
                    JOIN pmu_12.presentation p__12
                    WHERE p.id = p__12.id AND mut_12.id IN (:media_upload_type_id)
                )";
            }
            if($filter->hasFilter("has_not_media_upload_with_type"))
            {
                $extraWhere .= " AND NOT EXISTS (
                    SELECT pmu_12.id 
                    FROM models\summit\PresentationMediaUpload pmu_12
                    JOIN pmu_12.media_upload_type mut_12
                    JOIN pmu_12.presentation p__12
                    WHERE p.id = p__12.id AND mut_12.id IN (:media_upload_type_id)
                )";
            }
            if($filter->hasFilter("is_speaker")){
                $value = to_boolean($filter->getValue("is_speaker")[0]);
                if($value)
                    $extraWhere .= " AND ( spk.member = :member_id OR mod.member = :member_id)";
                else
                    $extraWhere .= " AND ( (spk.member <> :member_id OR spk.member IS NULL) AND (mod.member <> :member_id OR mod.member IS NULL) )";
            }
        }

        $query = $this->createQuery("SELECT DISTINCT p from models\summit\Presentation p 
            JOIN p.summit s
            LEFT JOIN p.moderator mod 
            LEFT JOIN p.speakers a_spk 
            LEFT JOIN a_spk.speaker spk 
            LEFT JOIN p.materials m 
            LEFT JOIN models\summit\PresentationMediaUpload pmu WITH pmu.id = m.id 
            LEFT JOIN pmu.media_upload_type mut 
            LEFT JOIN p.selection_plan sel_p 
            JOIN p.type t 
            JOIN p.category cat 
            JOIN p.created_by cb 
            WHERE s.id = :summit_id 
            AND p.published = 0 
            AND cb.id = :submitter_id " . $extraWhere);

        $query
            ->setParameter('summit_id', $summit->getId())
            ->setParameter('submitter_id', $this->id);

        if (!is_null($filter)) {
            if ($filter->hasFilter("presentations_selection_plan_id")) {
                $v = $filter->getValue("presentations_selection_plan_id");
                $query = $query->setParameter("selection_plan_id", $v);
            }
            if ($filter->hasFilter("presentations_track_id")) {
                $v = $filter->getValue("presentations_track_id");
                $query = $query->setParameter("track_id", $v);
            }
            if ($filter->hasFilter("presentations_type_id")) {
                $v = $filter->getValue("presentations_type_id");
                $query = $query->setParameter("type_id", $v);
            }
            if($filter->hasFilter("has_media_upload_with_type"))
            {
                $v = $filter->getValue("has_media_upload_with_type");
                $query = $query->setParameter("media_upload_type_id", $v);
            }
            if($filter->hasFilter("has_not_media_upload_with_type"))
            {
                $v = $filter->getValue("has_not_media_upload_with_type");
                $query = $query->setParameter("media_upload_type_id", $v);
            }
            if($filter->hasFilter("is_speaker")){
                $query->setParameter("member_id", $this->id);
            }
        }

        $presentations = $query->getResult();

        Log::debug
        (
            sprintf
            (
                "Member::getRejectedPresentations id %s query %s",
                $this->id,
                $query->getDQL()
            )
        );

        foreach ($presentations as $p) {
            if ($p->getSelectionStatus() == Presentation::SelectionStatus_Unaccepted) {
                $rejected_presentations[] = $p;
            }
        }

        return $rejected_presentations;
    }

    /**
     * @param Summit $summit
     * @param Filter|null $filter
     * @return array
     */
    public function getRejectedPresentationIds
    (
        Summit  $summit,
        ?Filter $filter = null
    )
    {
        $ids = [];
        $rejected_presentations = $this->getRejectedPresentations($summit, $filter);
        foreach ($rejected_presentations as $p) {
            $ids[] = intval($p->getId());
        }
        return $ids;
    }

    public function isSummitAllowed(Summit $summit): bool
    {
        if ($this->isAdmin()) return true;

        return MemberSummitStrategyFactory::getMemberSummitStrategy($this)
            ->isSummitAllowed($summit);
    }

    /**
     * @param Summit $summit
     * @param Sponsor|null $sponsor
     * @return bool
     */
    public function isAuthzFor(Summit $summit, Sponsor $sponsor = null):bool{
        if($this->isAdmin()) return true;
        // authz check
        if ($this->isSummitAdmin() && $this->isSummitAllowed($summit))
            return true;
        if ($this->isSponsorUser() && !is_null($sponsor) && $this->hasSponsorMembershipsFor($summit, $sponsor))
            return true;
        return false;
    }

    public function isPublicProfileShowPhoto(): bool
    {
        return $this->public_profile_show_photo;
    }

    public function setPublicProfileShowPhoto(bool $public_profile_show_photo): void
    {
        $this->public_profile_show_photo = $public_profile_show_photo;
    }

    public function isPublicProfileShowFullname(): bool
    {
        return $this->public_profile_show_fullname;
    }

    public function setPublicProfileShowFullname(bool $public_profile_show_fullname): void
    {
        $this->public_profile_show_fullname = $public_profile_show_fullname;
    }

    public function isPublicProfileShowEmail(): bool
    {
        return $this->public_profile_show_email;
    }

    public function setPublicProfileShowEmail(bool $public_profile_show_email): void
    {
        $this->public_profile_show_email = $public_profile_show_email;
    }

    public function isPublicProfileAllowChatWithMe(): bool
    {
        return $this->public_profile_allow_chat_with_me;
    }

    public function setPublicProfileAllowChatWithMe(bool $public_profile_allow_chat_with_me): void
    {
        $this->public_profile_allow_chat_with_me = $public_profile_allow_chat_with_me;
    }

    public function isPublicProfileShowSocialMediaInfo(): bool
    {
        return $this->public_profile_show_social_media_info;
    }

    public function setPublicProfileShowSocialMediaInfo(bool $public_profile_show_social_media_info): void
    {
        $this->public_profile_show_social_media_info = $public_profile_show_social_media_info;
    }

    public function isPublicProfileShowBio(): bool
    {
        return $this->public_profile_show_bio;
    }

    public function setPublicProfileShowBio(bool $public_profile_show_bio): void
    {
        $this->public_profile_show_bio = $public_profile_show_bio;
    }

    public function isPublicProfileShowTelephoneNumber(): bool
    {
        return $this->public_profile_show_telephone_number;
    }

    public function setPublicProfileShowTelephoneNumber(bool $public_profile_show_telephone_number): void
    {
        $this->public_profile_show_telephone_number = $public_profile_show_telephone_number;
    }

    public function signIndividualMembership():void{
        $this->individual_member_join_date =  new \DateTime('now', new \DateTimeZone("UTC"));
        $this->membership_type = self::MemberShipType_IndividualMember;
    }

    public function isIndividualMember(): bool
    {
        return $this->membership_type === self::MemberShipType_IndividualMember;
    }

    public function getIndividualMemberJoinDate(): ?\DateTime
    {
        return $this->individual_member_join_date;
    }
}