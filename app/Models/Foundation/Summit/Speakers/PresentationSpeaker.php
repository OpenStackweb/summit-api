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

use App\Models\Foundation\Summit\Speakers\PresentationSpeakerAssignment;
use App\Models\Foundation\Main\Language;
use App\Models\Foundation\Summit\ScheduleEntity;
use App\Models\Foundation\Summit\SelectionPlan;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use libs\utils\TextUtils;
use models\main\File;
use models\main\Member;
use models\utils\SilverstripeBaseModel;
use utils\Filter;

/**
 * @ORM\Entity
 * @ORM\Table(name="PresentationSpeaker")
 * @ORM\Entity(repositoryClass="App\Repositories\Summit\DoctrineSpeakerRepository")
 * @ORM\HasLifecycleCallbacks
 * Class PresentationSpeaker
 * @package models\summit
 */
class PresentationSpeaker extends SilverstripeBaseModel
{
    const RoleSpeaker = 'SPEAKER';
    const RoleModerator = 'MODERATOR';

    /**
     * @ORM\Column(name="FirstName", type="string")
     */
    private $first_name;

    /**
     * @ORM\Column(name="LastName", type="string")
     */
    private $last_name;

    /**
     * @ORM\Column(name="Title", type="string")
     */
    private $title;

    /**
     * @ORM\Column(name="Bio", type="string")
     */
    private $bio;

    /**
     * @ORM\Column(name="IRCHandle", type="string")
     */
    private $irc_handle;

    /**
     * @ORM\Column(name="TwitterName", type="string")
     */
    private $twitter_name;

    /**
     * @ORM\Column(name="CreatedFromAPI", type="boolean")
     */
    private $created_from_api;

    /**
     * @ORM\Column(name="AvailableForBureau", type="boolean")
     */
    private $available_for_bureau;

    /**
     * @ORM\Column(name="FundedTravel", type="boolean")
     */
    private $funded_travel;

    /**
     * @ORM\Column(name="WillingToTravel", type="boolean")
     */
    private $willing_to_travel;

    /**
     * @ORM\Column(name="Country", type="string")
     */
    private $country;

    /**
     * @ORM\Column(name="WillingToPresentVideo", type="boolean")
     */
    private $willing_to_present_video;

    /**
     * @ORM\Column(name="Notes", type="string")
     */
    private $notes;

    /**
     * @ORM\Column(name="OrgHasCloud", type="boolean")
     */
    private $org_has_cloud;

    /**
     * @ORM\Column(name="Company", type="string")
     */
    private $company;

    /**
     * @ORM\Column(name="PhoneNumber", type="string")
     */
    private $phone_number;

    /**
     * @ORM\ManyToOne(targetEntity="SpeakerRegistrationRequest", cascade={"persist","remove"}), orphanRemoval=true
     * @ORM\JoinColumn(name="RegistrationRequestID", referencedColumnName="ID")
     * @var SpeakerRegistrationRequest
     */
    private $registration_request;

    /**
     * @ORM\OneToMany(targetEntity="PresentationSpeakerSummitAssistanceConfirmationRequest", mappedBy="speaker", cascade={"persist","remove"}), orphanRemoval=true, fetch="EXTRA_LAZY")
     * @var PresentationSpeakerSummitAssistanceConfirmationRequest[]
     */
    private $summit_assistances;

    /**
     * @ORM\OneToMany(targetEntity="SpeakerSummitRegistrationPromoCode", mappedBy="speaker", cascade={"persist"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @var SpeakerSummitRegistrationPromoCode[]
     */
    private $promo_codes;

    /**
     * @ORM\OneToMany(targetEntity="SpeakerSummitRegistrationDiscountCode", mappedBy="speaker", cascade={"persist"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @var SpeakerSummitRegistrationDiscountCode[]
     */
    private $discount_codes;

    /**
     * @ORM\OneToMany(targetEntity="App\Models\Foundation\Summit\Speakers\PresentationSpeakerAssignment", mappedBy="speaker", cascade={"persist"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @var PresentationSpeakerAssignment[]
     */
    private $presentations;

    /**
     * @ORM\OneToMany(targetEntity="Presentation", mappedBy="moderator", cascade={"persist"})
     * @var Presentation[]
     */
    private $moderated_presentations;

    /**
     * @ORM\OneToMany(targetEntity="App\Models\Foundation\Summit\Speakers\SpeakerEditPermissionRequest", mappedBy="speaker", cascade={"persist"})
     * @var Presentation[]
     */
    private $granted_edit_permissions;

    /**
     * @ORM\ManyToOne(targetEntity="models\main\File", cascade={"persist"})
     * @ORM\JoinColumn(name="PhotoID", referencedColumnName="ID")
     * @var File
     */
    private $photo;

    /**
     * @ORM\ManyToOne(targetEntity="models\main\File", cascade={"persist"})
     * @ORM\JoinColumn(name="BigPhotoID", referencedColumnName="ID")
     * @var File
     */
    private $big_photo;

    /**
     * Owning side
     * @ORM\OneToOne(targetEntity="models\main\Member",inversedBy="speaker", cascade={"persist"})
     * @ORM\JoinColumn(name="MemberID", referencedColumnName="ID", nullable=true)
     * @var Member
     */
    private $member;

    /**
     * @ORM\OneToMany(targetEntity="SpeakerExpertise", mappedBy="speaker", cascade={"persist"}, orphanRemoval=true)
     * @var SpeakerExpertise[]
     */
    private $areas_of_expertise;

    /**
     * @ORM\OneToMany(targetEntity="SpeakerPresentationLink", mappedBy="speaker", cascade={"persist"}, orphanRemoval=true)
     * @var SpeakerPresentationLink[]
     */
    private $other_presentation_links;

    /**
     * @ORM\OneToMany(targetEntity="SpeakerTravelPreference", mappedBy="speaker", cascade={"persist"}, orphanRemoval=true)
     * @var SpeakerTravelPreference[]
     */
    private $travel_preferences;

    /**
     * @ORM\ManyToMany(targetEntity="App\Models\Foundation\Main\Language", cascade={"persist"})
     * @ORM\JoinTable(name="PresentationSpeaker_Languages",
     *      joinColumns={@ORM\JoinColumn(name="PresentationSpeakerID", referencedColumnName="ID")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="LanguageID", referencedColumnName="ID")}
     *      )
     * @var Language[]
     */
    private $languages;

    /**
     * @ORM\ManyToMany(targetEntity="SpeakerOrganizationalRole", cascade={"persist"})
     * @ORM\JoinTable(name="PresentationSpeaker_OrganizationalRoles",
     *      joinColumns={@ORM\JoinColumn(name="PresentationSpeakerID", referencedColumnName="ID")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="SpeakerOrganizationalRoleID", referencedColumnName="ID")}
     *      )
     * @var SpeakerOrganizationalRole[]
     */
    protected $organizational_roles;

    /**
     * @ORM\ManyToMany(targetEntity="SpeakerActiveInvolvement", cascade={"persist"})
     * @ORM\JoinTable(name="PresentationSpeaker_ActiveInvolvements",
     *      joinColumns={@ORM\JoinColumn(name="PresentationSpeakerID", referencedColumnName="ID")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="SpeakerActiveInvolvementID", referencedColumnName="ID")}
     *      )
     * @var SpeakerActiveInvolvement[]
     */
    protected $active_involvements;

    /**
     * @ORM\OneToMany(targetEntity="SpeakerAnnouncementSummitEmail", mappedBy="speaker", cascade={"persist"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @var SpeakerAnnouncementSummitEmail[]
     */
    private $announcement_summit_emails;

    /**
     * @return string|null
     */
    public function getFirstName():?string
    {
        $res = $this->first_name;
        if(empty($res) && $this->hasMember()){
            $res = $this->member->getFirstName();
        }
        return $res;
    }

    /**
     * @param string $first_name
     */
    public function setFirstName(string $first_name):void
    {
        $this->first_name = TextUtils::trim($first_name);
    }

    /**
     * @return string|null
     */
    public function getLastName():?string
    {
        $res = $this->last_name;
        if(empty($res) && $this->hasMember()){
            $res = $this->member->getLastName();
        }
        return $res;
    }

    /**
     * @param string $last_name
     */
    public function setLastName(string $last_name):void
    {
        $this->last_name = TextUtils::trim($last_name);
    }

    /**
     * @return string|null
     */
    public function getTitle():?string
    {
        return html_entity_decode($this->title);
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title):void
    {
        $this->title = TextUtils::trim($title);
    }

    /**
     * @return string|null
     */
    public function getBio():?string
    {
        return $this->bio;
    }

    /**
     * @param string $bio
     */
    public function setBio(string $bio):void
    {
        $this->bio = $bio;
    }

    /**
     * @return string|null
     */
    public function getIrcHandle():?string
    {
        return $this->irc_handle;
    }

    /**
     * @param string $irc_handle
     */
    public function setIrcHandle(string $irc_handle):void
    {
        $this->irc_handle = TextUtils::trim($irc_handle);
    }

    /**
     * @param string|null $username
     * @return string|null
     */
    private static function parseTwitterUsername(?string $username): ?string
    {
        if (empty($username)) return $username;
        if (preg_match('/https:\/\/twitter\.com\/(.*)/', $username, $matches)) {
            $username = '@' . $matches[count($matches) - 1];
        }
        if (strpos($username, '@') === false)
            $username = '@' . $username;
        return $username;
    }

    /**
     * @return string
     */
    public function getTwitterName():?string
    {
        return self::parseTwitterUsername($this->twitter_name);
    }

    /**
     * @param string $twitter_name
     */
    public function setTwitterName(string $twitter_name)
    {
        $this->twitter_name = self::parseTwitterUsername($twitter_name);
    }

    public function __construct()
    {
        parent::__construct();
        $this->available_for_bureau = false;
        $this->willing_to_present_video = false;
        $this->willing_to_travel = false;
        $this->funded_travel = false;
        $this->org_has_cloud = false;
        $this->created_from_api = true;
        $this->presentations = new ArrayCollection;
        $this->moderated_presentations = new ArrayCollection;
        $this->summit_assistances = new ArrayCollection;
        $this->promo_codes = new ArrayCollection;
        $this->discount_codes = new ArrayCollection;
        $this->areas_of_expertise = new ArrayCollection;
        $this->other_presentation_links = new ArrayCollection;
        $this->travel_preferences = new ArrayCollection;
        $this->languages = new ArrayCollection;
        $this->organizational_roles = new ArrayCollection;
        $this->active_involvements = new ArrayCollection;
        $this->announcement_summit_emails = new ArrayCollection;
        $this->granted_edit_permissions = new ArrayCollection;
    }

    /**
     * @return ArrayCollection
     */
    private function getAssignedPresentations(): ArrayCollection
    {
        return $this->presentations->map(function ($entity) {
            return $entity->getPresentation();
        });
    }

    /**
     * @param Presentation $presentation
     */
    public function addPresentation(Presentation $presentation){
        if ($this->hasPresentationAssigned($presentation)) return;
        $order = $presentation->getSpeakerAssignmentsMaxOrder();
        $presentation_assignment = new PresentationSpeakerAssignment($presentation, $this, $order + 1);
        $this->presentations->add($presentation_assignment);
    }

    public function clearPresentations(){
        $presentations = $this->getAssignedPresentations();
        foreach($presentations as $presentation){
            $presentation->removeSpeaker($this);
        }
        $this->presentations->clear();
    }

    /**
     * @param SummitRegistrationPromoCode $code
     * @return $this
     */
    public function addPromoCode(SummitRegistrationPromoCode $code): PresentationSpeaker
    {
        if ($code instanceof SpeakerSummitRegistrationPromoCode) {
            $this->promo_codes->add($code);
            $code->setSpeaker($this);
        }
        if ($code instanceof SpeakerSummitRegistrationDiscountCode) {
            $this->discount_codes->add($code);
            $code->setSpeaker($this);
        }
        return $this;
    }

    /**
     * @param SummitRegistrationPromoCode $code
     * @return $this
     */
    public function removePromoCode(SummitRegistrationPromoCode $code): PresentationSpeaker
    {
        if ($code instanceof SpeakerSummitRegistrationPromoCode) {
            $this->promo_codes->removeElement($code);
            $code->setSpeaker(null);
        }
        if ($code instanceof SpeakerSummitRegistrationDiscountCode) {
            $this->discount_codes->removeElement($code);
            $code->setSpeaker(null);
        }
        return $this;
    }

    /**
     * @return ArrayCollection|SummitRegistrationPromoCode[]
     */
    public function getPromoCodes()
    {
        return $this->promo_codes;
    }

    /**
     * @return ArrayCollection|SpeakerSummitRegistrationDiscountCode[]
     */
    public function getDiscountCodes()
    {
        return $this->discount_codes;
    }

    /**
     * @param Summit $summit
     * @return SpeakerSummitRegistrationPromoCode|null
     */
    public function getPromoCodeFor(Summit $summit): ?SpeakerSummitRegistrationPromoCode
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('summit', $summit));
        $res = $this->promo_codes->matching($criteria)->first();
        return $res === false ? null : $res;
    }

    /**
     * @param Summit $summit
     * @return SpeakerSummitRegistrationDiscountCode|null
     */
    public function getDiscountCodeFor(Summit $summit): ?SpeakerSummitRegistrationDiscountCode
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('summit', $summit));
        $res = $this->discount_codes->matching($criteria)->first();
        return $res === false ? null : $res;
    }

    /**
     * @param null|int $summit_id
     * @param bool|true $published_ones
     * @return Presentation[]
     */
    public function presentations($summit_id, $published_ones = true)
    {
        return $this->presentations
            ->filter(function ($p) use ($published_ones, $summit_id) {
                $current_presentation = $p->getPresentation();
                $res = $published_ones ? $current_presentation->isPublished() : true;
                $res &= is_null($summit_id) || $current_presentation->getSummit()->getId() == $summit_id;
                return $res;
            });
    }

    const ROLE_SPEAKER = 'ROLE_SPEAKER';
    const ROLE_CREATOR = 'ROLE_CREATOR';
    const ROLE_MODERATOR ='ROLE_MODERATOR';

    /**
     * @param SelectionPlan $selectionPlan
     * @param string $role
     * @return array
     */
    public function getPresentationsBySelectionPlanAndRole(SelectionPlan $selectionPlan, $role)
    {

        if ($role == self::ROLE_SPEAKER) {
            $res = $this->presentations->filter(function (Presentation $presentation) use ($selectionPlan) {
                if ($presentation->getSelectionPlanId() != $selectionPlan->getId()) return false;
                if ($presentation->getSummit()->getId() != $selectionPlan->getSummitId()) return false;
                if ($presentation->getModeratorId() == $this->getId()) return false;
                if ($presentation->getCreatorId() == $this->getMemberId()) return false;
                return true;
            });
            return $res->toArray();
        }

        if ($role == self::ROLE_CREATOR) {
            return $selectionPlan->getSummit()->getCreatedPresentations($this, $selectionPlan);
        }

        if ($role == self::ROLE_MODERATOR) {
            return $selectionPlan->getSummit()->getModeratedPresentationsBy($this, $selectionPlan);
        }

        return [];
    }

    /**
     * @param Summit $summit
     * @param string $role
     * @return array
     */
    public function getPresentationsBySummitAndRole(Summit $summit, $role)
    {

        if ($role == self::ROLE_SPEAKER) {
            $res = $this->presentations->filter(function (Presentation $presentation) use ($summit) {
                if ($presentation->getSummit()->getId() != $summit->getId()) return false;
                if ($presentation->getModeratorId() == $this->getId()) return false;
                if ($presentation->getCreatorId() == $this->getMemberId()) return false;
                return true;
            });
            return $res->toArray();
        }

        if ($role == self::ROLE_CREATOR) {
            return $summit->getCreatedPresentations($this);
        }

        if ($role == self::ROLE_MODERATOR) {
            return $summit->getModeratedPresentationsBy($this);
        }

        return [];
    }

    /**
     * @param Summit $summit
     * @param string $role
     * @param bool $include_sub_roles
     * @param array $excluded_tracks
     * @return bool
     */
    public function hasPublishedRegularPresentations
    (
        Summit $summit,
        $role = PresentationSpeaker::RoleSpeaker,
        $include_sub_roles = false,
        array $excluded_tracks = []
    )
    {
        return count($this->getPublishedRegularPresentations($summit, $role, $include_sub_roles, $excluded_tracks)) > 0;
    }


    /**
     * @param Summit $summit
     * @param string $role
     * @param bool $include_sub_roles
     * @param array $excluded_tracks
     * @return array
     */
    public function getPublishedRegularPresentations
    (
        Summit $summit,
        $role = PresentationSpeaker::RoleSpeaker,
        $include_sub_roles = false,
        array $excluded_tracks = []
    )
    {
        $list = $this->getPublishedPresentationsByType
        (
            $summit,
            $role,
            [IPresentationType::Keynotes, IPresentationType::Panel, IPresentationType::Presentation],
            true,
            $excluded_tracks
        );

        if ($include_sub_roles && $role == PresentationSpeaker::RoleModerator) {
            $presentations = $this->getPublishedPresentationsByType
            (
                $summit,
                PresentationSpeaker::RoleSpeaker,
                [IPresentationType::Keynotes, IPresentationType::Panel, IPresentationType::Presentation],
                true,
                $excluded_tracks
            );
            if ($presentations) {
                foreach ($presentations as $speaker_presentation)
                    $list[] = $speaker_presentation;
            }
        }

        return $list;
    }

    /**
     * @param Summit $summit
     * @param string $role
     * @param bool $include_sub_roles
     * @param array $excluded_tracks
     * @return array
     */
    public function getPublishedRegularPresentationIds
    (
        Summit $summit,
        string $role = PresentationSpeaker::RoleSpeaker,
        bool   $include_sub_roles = false,
        array  $excluded_tracks = []
    )
    {
        $ids = [];
        $alternatePresentations = $this->getPublishedRegularPresentations($summit, $role, $include_sub_roles, $excluded_tracks);
        foreach ($alternatePresentations as $p) {
            $ids[] = intval($p->getId());
        }
        return $ids;
    }

    /**
     * @param Summit $summit
     * @param string $role
     * @param bool $include_sub_roles
     * @param array $excluded_tracks
     * @return bool
     */
    public function hasPublishedLightningPresentations
    (
        Summit $summit,
        $role = PresentationSpeaker::RoleSpeaker,
        $include_sub_roles = false,
        array $excluded_tracks = []
    )
    {
        return count($this->getPublishedLightningPresentations
            (
                $summit,
                $role,
                $include_sub_roles,
                $excluded_tracks
            )) > 0;
    }

    /**
     * @param Summit $summit
     * @param string $role
     * @param bool $include_sub_roles
     * @param array $excluded_tracks
     * @return array
     */
    public function getPublishedLightningPresentations
    (
        Summit $summit,
        $role = PresentationSpeaker::RoleSpeaker,
        $include_sub_roles = false,
        array $excluded_tracks = []
    )
    {
        $list = $this->getPublishedPresentationsByType($summit, $role, [IPresentationType::LightingTalks], true , $excluded_tracks);

        if($include_sub_roles && $role == PresentationSpeaker::RoleModerator){
            $presentations = $this->getPublishedPresentationsByType($summit, PresentationSpeaker::RoleSpeaker, [IPresentationType::LightingTalks], true, $excluded_tracks) ;
            if($presentations) {
                foreach ($presentations as $speaker_presentation) {
                    $list[] = $speaker_presentation;
                }
            }
        }

        return $list;
    }

    /**
     * @param Summit $summit
     * @param string $role
     * @param bool $exclude_privates_tracks
     * @param array $excluded_tracks
     * @param Filter|null $filter
     * @return bool
     */
    public function hasAcceptedPresentations
    (
        Summit  $summit,
        string  $role = PresentationSpeaker::RoleSpeaker,
        bool    $exclude_privates_tracks = true,
        array   $excluded_tracks = [],
        ?Filter $filter = null
    )
    {
        return count($this->getAcceptedPresentations($summit, $role, $exclude_privates_tracks, $excluded_tracks, $filter)) > 0;
    }

    /**
     * @param Summit $summit
     * @param string $role
     * @param bool $exclude_privates_tracks
     * @param array $excluded_tracks
     * @param Filter|null $filter
     * @return int|mixed|string
     */
    public function getAcceptedPresentations
    (
        Summit  $summit,
        string  $role = PresentationSpeaker::RoleSpeaker,
        bool    $exclude_privates_tracks = true,
        array   $excluded_tracks = [],
        ?Filter $filter = null
    )
    {
        $private_tracks = [];

        if ($exclude_privates_tracks) {
            $private_track_groups = $this->createQuery("SELECT pg from models\summit\PrivatePresentationCategoryGroup pg 
            JOIN pg.summit s
            WHERE s.id = :summit_id")
                ->setParameter('summit_id', $summit->getId())
                ->getResult();

            foreach ($private_track_groups as $private_track_group) {
                $current_private_tracks = $private_track_group->getCategories();
                if (count($current_private_tracks) == 0) continue;
                $private_tracks = array_merge($private_tracks, array_values($current_private_tracks));
            }
        }

        if (count($private_tracks) > 0) {
            $excluded_tracks = array_merge($excluded_tracks, $private_tracks);
        }

        $exclude_category_dql = '';
        if (count($excluded_tracks) > 0) {
            $exclude_category_dql = ' AND p.category NOT IN (:exclude_tracks)';
        }
        $extraWhere = '';
        if(!is_null($filter)){
            if($filter->hasFilter("presentations_selection_plan_id"))
            {
                $extraWhere .= " AND sel_p.id IN (:selection_plan_id)";
            }
            if($filter->hasFilter("presentations_track_id"))
            {
                $extraWhere .= " AND cat.id IN (:track_id)";
            }
            if($filter->hasFilter("presentations_type_id"))
            {
                $extraWhere .= " AND cat.id IN (:type_id)";
            }
        }
        if($role == PresentationSpeaker::RoleSpeaker) {
            $query = $this->createQuery(sprintf("SELECT p from models\summit\Presentation p 
            JOIN p.summit s
            JOIN p.speakers sp_presentation 
            JOIN sp_presentation.speaker sp
            LEFT JOIN p.selection_plan sel_p
            JOIN p.type t
            JOIN p.category cat
            LEFT JOIN p.selected_presentations ssp 
            LEFT JOIN ssp.list sspl 
            WHERE s.id = :summit_id 
            AND sp.id = :speaker_id
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
            " . $exclude_category_dql . $extraWhere,
                SummitSelectedPresentation::CollectionSelected,
                SummitSelectedPresentationList::Group,
                SummitSelectedPresentationList::Session
            ));
        } else {
            $query = $this->createQuery(sprintf(
                "SELECT p from models\summit\Presentation p 
            JOIN p.summit s
            LEFT JOIN p.selection_plan sel_p 
            JOIN p.type t
            JOIN p.category cat
            JOIN p.moderator m
            LEFT JOIN p.selected_presentations ssp 
            LEFT JOIN ssp.list sspl 
            WHERE 
            s.id = :summit_id 
            AND m.id = :speaker_id
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
            " . $exclude_category_dql . $extraWhere,
                SummitSelectedPresentation::CollectionSelected,
                SummitSelectedPresentationList::Group,
                SummitSelectedPresentationList::Session
            ));
        }

        $query = $query
            ->setParameter('summit_id', $summit->getId())
            ->setParameter('speaker_id', $this->id);

        if (!is_null($filter)) {
            if ($filter->hasFilter("presentations_selection_plan_id")) {
                $v = [];
                foreach ($filter->getFilter("presentations_selection_plan_id") as $f) {
                    if (is_array($f->getValue())) {
                        foreach ($f->getValue() as $iv) {
                            $v[] = $iv;
                        }
                    } else
                        $v[] = $f->getValue();
                }
                $query = $query->setParameter("selection_plan_id", $v);
            }
            if($filter->hasFilter("presentations_track_id"))
            {
                $v = [];
                foreach ($filter->getFilter("presentations_track_id") as $f) {
                    if (is_array($f->getValue())) {
                        foreach ($f->getValue() as $iv) {
                            $v[] = $iv;
                        }
                    }
                    else
                        $v[] = $f->getValue();
                }
                $query = $query->setParameter("track_id", $v);
            }
            if($filter->hasFilter("presentations_type_id"))
            {
                $v = [];
                foreach($filter->getFilter("presentations_type_id") as $f){
                    if(is_array($f->getValue())){
                        foreach ($f->getValue() as $iv){
                            $v[] = $iv;
                        }
                    } else
                        $v[] = $f->getValue();
                }
                $query = $query->setParameter("type_id", $v);
            }
        }

        if (count($excluded_tracks) > 0) {
            $query = $query->setParameter('exclude_tracks', $excluded_tracks);
        }

        return $query->getResult();
    }

    /**
     * @param Summit $summit
     * @param string $role
     * @param bool $include_sub_roles
     * @param array $excluded_tracks
     * @param Filter|null $filter
     * @return array
     */
    public function getAcceptedPresentationIds
    (
        Summit  $summit,
        string  $role = PresentationSpeaker::RoleSpeaker,
        bool    $include_sub_roles = false,
        array   $excluded_tracks = [],
        ?Filter $filter = null
    )
    {
        $ids = [];
        $acceptedPresentations = $this->getAcceptedPresentations($summit, $role, $include_sub_roles, $excluded_tracks, $filter);
        foreach ($acceptedPresentations as $p) {
            $ids[] = intval($p->getId());
        }
        return $ids;
    }

    /**
     * @param Summit $summit
     * @param string $role
     * @param false $include_sub_roles
     * @param array $excluded_tracks
     * @param false $published_ones
     * @param Filter|null $filter
     * @return bool
     */
    public function hasAlternatePresentations
    (
        Summit $summit,
        $role                  = PresentationSpeaker::RoleSpeaker,
        $include_sub_roles     = false,
        array $excluded_tracks = [],
        $published_ones = false,
        ?Filter $filter = null
    )
    {
        return count($this->getAlternatePresentations($summit, $role, $include_sub_roles, $excluded_tracks, $published_ones, $filter)) > 0;
    }

    /**
     * @param Summit $summit
     * @param string $role
     * @param bool $include_sub_roles
     * @param array $excluded_tracks
     * @param bool $published_ones
     * @param Filter $filter
     * @return array
     */
    public function getAlternatePresentations
    (
        Summit $summit,
        $role = PresentationSpeaker::RoleSpeaker,
        $include_sub_roles = false,
        array $excluded_tracks = [],
        $published_ones = false,
        ?Filter $filter = null
    )
    {
        $alternate_presentations = [];

        $exclude_category_dql = '';
        if(count($excluded_tracks) > 0){
            $exclude_category_dql = ' AND cat NOT IN (:exclude_tracks)';
        }

        $extraWhere = '';
        if (!is_null($filter)) {
            if ($filter->hasFilter("presentations_selection_plan_id")) {
                $extraWhere .= " AND sel_p.id IN (:selection_plan_id)";
            }
            if($filter->hasFilter("presentations_track_id"))
            {
                $extraWhere .= " AND cat.id IN (:track_id)";
            }
            if($filter->hasFilter("presentations_type_id"))
            {
                $extraWhere .= " AND cat.id IN (:type_id)";
            }
        }

        if ($role == PresentationSpeaker::RoleSpeaker) {
            $query = $this->createQuery("SELECT p from models\summit\Presentation p 
            JOIN p.summit s
            JOIN p.speakers sp_presentation 
            JOIN sp_presentation.speaker sp
            LEFT JOIN p.selection_plan sel_p
            JOIN p.type t
            JOIN p.category cat
            WHERE s.id = :summit_id 
            AND p.published = :published
            AND sp.id = :speaker_id" . $exclude_category_dql . $extraWhere);
        } else {
            $query = $this->createQuery("SELECT p from models\summit\Presentation p 
            JOIN p.summit s
            LEFT JOIN p.selection_plan sel_p
            JOIN p.type t
            JOIN p.category cat
            JOIN p.moderator m 
            WHERE s.id = :summit_id 
            AND p.published = :published
            AND m.id = :speaker_id" . $exclude_category_dql . $extraWhere);
        }

        $query
            ->setParameter('summit_id', $summit->getId())
            ->setParameter('speaker_id', $this->id)
            ->setParameter('published', $published_ones ? 1 : 0);

        if (count($excluded_tracks) > 0) {
            $query->setParameter('exclude_tracks', $excluded_tracks);
        }

        if (!is_null($filter)){
            if ($filter->hasFilter("presentations_selection_plan_id"))
            {
                $v = [];
                foreach ($filter->getFilter("presentations_selection_plan_id") as $f) {
                    if (is_array($f->getValue())) {
                        foreach ($f->getValue() as $iv) {
                            $v[] = $iv;
                        }
                    } else
                        $v[] = $f->getValue();
                }
                $query = $query->setParameter("selection_plan_id", $v);
            }
            if ($filter->hasFilter("presentations_track_id"))
            {
                $v = [];
                foreach ($filter->getFilter("presentations_track_id") as $f) {
                    if (is_array($f->getValue())) {
                        foreach ($f->getValue() as $iv) {
                            $v[] = $iv;
                        }
                    } else
                        $v[] = $f->getValue();
                }
                $query = $query->setParameter("track_id", $v);
            }
            if ($filter->hasFilter("presentations_type_id"))
            {
                $v = [];
                foreach ($filter->getFilter("presentations_type_id") as $f) {
                    if (is_array($f->getValue())) {
                        foreach ($f->getValue() as $iv) {
                            $v[] = $iv;
                        }
                    } else
                        $v[] = $f->getValue();
                }
                $query = $query->setParameter("type_id", $v);
            }
        }

        $presentations = $query->getResult();

        foreach ($presentations as $p) {
            if ($p->getSelectionStatus() == Presentation::SelectionStatus_Alternate) {
                $alternate_presentations[] = $p;
            }
        }

        // if role is moderator, add also the ones that belongs to role speaker ( if $include_sub_roles is true)
        if ($include_sub_roles && $role == PresentationSpeaker::RoleModerator) {
            $presentations = $this->getAlternatePresentations($summit, PresentationSpeaker::RoleSpeaker, $include_sub_roles, $excluded_tracks);
            if ($presentations) {
                foreach ($presentations as $speaker_presentation)
                    $alternate_presentations[] = $speaker_presentation;
            }
        }

        return $alternate_presentations;
    }

    /**
     * @param Summit $summit
     * @param string $role
     * @param bool $include_sub_roles
     * @param array $excluded_tracks
     * @param bool $published_ones
     * @param Filter $filter
     * @return array
     */
    public function getAlternatePresentationIds
    (
        Summit  $summit,
        string  $role = PresentationSpeaker::RoleSpeaker,
        bool    $include_sub_roles = false,
        array   $excluded_tracks = [],
        bool    $published_ones = false,
        ?Filter $filter = null
    )
    {
        $ids = [];
        $alternatePresentations = $this->getAlternatePresentations($summit, $role, $include_sub_roles, $excluded_tracks, $published_ones, $filter);
        foreach ($alternatePresentations as $p) {
            $ids[] = intval($p->getId());
        }
        return $ids;
    }

    /**
     * @param Summit $summit
     * @param string $role
     * @param bool $include_sub_roles
     * @param array $excluded_tracks
     * @param Filter $filter
     * @return bool
     */
    public function hasRejectedPresentations
    (
        Summit $summit,
        $role = PresentationSpeaker::RoleSpeaker,
        $include_sub_roles = false,
        array $excluded_tracks = [],
        ?Filter $filter = null
    )
    {
        return count($this->getRejectedPresentations($summit, $role, $include_sub_roles, $excluded_tracks, $filter)) > 0;
    }

    /**
     * @param Summit $summit
     * @param string $role
     * @param bool $include_sub_roles
     * @param array $excluded_tracks
     * @param Filter $filter
     * @return array
     */
    public function getRejectedPresentations
    (
        Summit $summit,
        $role = PresentationSpeaker::RoleSpeaker,
        $include_sub_roles = false,
        array $excluded_tracks = [],
        ?Filter $filter = null
    )
    {
        $list = $this->getUnacceptedPresentations($summit, $role, true, $excluded_tracks, $filter);
        if ($include_sub_roles && $role == PresentationSpeaker::RoleModerator) {
            $presentations = $this->getUnacceptedPresentations($summit, PresentationSpeaker::RoleSpeaker, true, $excluded_tracks, $filter);
            if ($presentations) {
                foreach ($presentations as $speaker_presentation) {
                    $list[] = $speaker_presentation;
                }
            }
        }
        return $list;
    }

    /**
     * @param Summit $summit
     * @param string $role
     * @param bool $include_sub_roles
     * @param array $excluded_tracks
     * @param Filter|null $filter
     * @return array
     */
    public function getRejectedPresentationIds
    (
        Summit  $summit,
        string  $role = PresentationSpeaker::RoleSpeaker,
        bool    $include_sub_roles = false,
        array   $excluded_tracks = [],
        ?Filter $filter = null
    )
    {
        $ids = [];
        $rejectedPresentations = $this->getRejectedPresentations($summit, $role, $include_sub_roles, $excluded_tracks, $filter);
        foreach ($rejectedPresentations as $p) {
            $ids[] = intval($p->getId());
        }
        return $ids;
    }

    /**
     * @param Summit $summit
     * @param string $role
     * @param bool $exclude_privates_tracks
     * @param array $excluded_tracks
     * @param Filter $filter
     * @return array
     */
    public function getUnacceptedPresentations
    (
        Summit $summit,
        $role = PresentationSpeaker::RoleSpeaker,
        $exclude_privates_tracks = true,
        array $excluded_tracks = [],
        ?Filter $filter = null
    )
    {
        $unaccepted_presentations = [];
        $private_tracks = [];

        if ($exclude_privates_tracks) {
            $private_track_groups = $this->createQuery("SELECT pg from models\summit\PrivatePresentationCategoryGroup pg 
            JOIN pg.summit s
            WHERE s.id = :summit_id")
                ->setParameter('summit_id', $summit->getId())
                ->getResult();

            foreach ($private_track_groups as $private_track_group) {
                $current_private_tracks = $private_track_group->getCategories();
                if (count($current_private_tracks) == 0) continue;
                $private_tracks = array_merge($private_tracks, array_values($current_private_tracks));
            }
        }

        if (count($private_tracks) > 0) {
            $excluded_tracks = array_merge($excluded_tracks, $private_tracks);
        }

        $exclude_category_dql = '';
        if (count($excluded_tracks) > 0) {
            $exclude_category_dql = ' AND cat NOT IN (:exclude_tracks)';
        }

        $extraWhere = '';
        if (!is_null($filter)) {
            if ($filter->hasFilter("presentations_selection_plan_id")) {
                $extraWhere .= " AND sel_p.id IN (:selection_plan_id)";
            }
            if ($filter->hasFilter("presentations_track_id")) {
                $extraWhere .= " AND cat.id IN (:track_id)";
            }
            if ($filter->hasFilter("presentations_type_id")) {
                $extraWhere .= " AND cat.id IN (:type_id)";
            }
        }

        if ($role == PresentationSpeaker::RoleSpeaker) {
            $query = $this->createQuery("SELECT p from models\summit\Presentation p 
            JOIN p.summit s
            JOIN p.speakers sp_presentation 
            JOIN sp_presentation.speaker sp
            LEFT JOIN p.selection_plan sel_p
            JOIN p.type t
            JOIN p.category cat
            WHERE s.id = :summit_id 
            AND p.published = 0
            AND sp.id = :speaker_id" . $exclude_category_dql . $extraWhere);
        } else {
            $query = $this->createQuery("SELECT p from models\summit\Presentation p 
            JOIN p.summit s
            LEFT JOIN p.selection_plan sel_p
            JOIN p.type t
            JOIN p.category cat
            JOIN p.moderator m 
            WHERE s.id = :summit_id 
            AND p.published = 0
            AND m.id = :speaker_id" . $exclude_category_dql . $extraWhere);
        }

        $query
            ->setParameter('summit_id', $summit->getId())
            ->setParameter('speaker_id', $this->id);

        if (count($excluded_tracks) > 0) {
            $query->setParameter('exclude_tracks', $excluded_tracks);
        }

        if (!is_null($filter)) {
            if ($filter->hasFilter("presentations_selection_plan_id")) {
                $v = [];
                foreach ($filter->getFilter("presentations_selection_plan_id") as $f) {
                    if (is_array($f->getValue())) {
                        foreach ($f->getValue() as $iv) {
                            $v[] = $iv;
                        }
                    } else
                        $v[] = $f->getValue();
                }
                $query = $query->setParameter("selection_plan_id", $v);
            }
            if ($filter->hasFilter("presentations_track_id")) {
                $v = [];
                foreach ($filter->getFilter("presentations_track_id") as $f) {
                    if (is_array($f->getValue())) {
                        foreach ($f->getValue() as $iv) {
                            $v[] = $iv;
                        }
                    } else
                        $v[] = $f->getValue();
                }
                $query = $query->setParameter("track_id", $v);
            }
            if ($filter->hasFilter("presentations_type_id")) {
                $v = [];
                foreach ($filter->getFilter("presentations_type_id") as $f) {
                    if (is_array($f->getValue())) {
                        foreach ($f->getValue() as $iv) {
                            $v[] = $iv;
                        }
                    } else
                        $v[] = $f->getValue();
                }
                $query = $query->setParameter("type_id", $v);
            }
        }

        $presentations = $query->getResult();

        foreach ($presentations as $p) {
            if ($p->getSelectionStatus() == Presentation::SelectionStatus_Unaccepted) {
                $unaccepted_presentations[] = $p;
            }
        }

        return $unaccepted_presentations;
    }


    /**
     * @param Summit $summit
     * @param string $role
     * @param array $types_slugs
     * @param bool $exclude_privates_tracks
     * @param array $excluded_tracks
     * @return array
     */
    public function getPublishedPresentationsByType
    (
        Summit $summit,
        $role                    = PresentationSpeaker::RoleSpeaker,
        array $types_slugs       = [IPresentationType::Keynotes, IPresentationType::Panel, IPresentationType::Presentation, IPresentationType::LightingTalks],
        $exclude_privates_tracks = true,
        array $excluded_tracks   = []
    )
    {
        $query = $this->createQuery("SELECT pt from models\summit\PresentationType pt JOIN pt.summit s
        WHERE s.id = :summit_id and pt.type IN (:types) ");
        $types = $query
            ->setParameter('summit_id', $summit->getIdentifier())
            ->setParameter('types', $types_slugs)
            ->getResult();

        if (count($types) == 0) return [];

        $private_tracks = [];
        $exclude_privates_tracks = boolval($exclude_privates_tracks);

        if ($exclude_privates_tracks) {

            $query = $this->createQuery("SELECT ppcg from models\summit\PrivatePresentationCategoryGroup ppcg JOIN ppcg.summit s 
            WHERE s.id = :summit_id");
            $private_track_groups = $query
                ->setParameter('summit_id', $summit->getIdentifier())
                ->getResult();

            foreach ($private_track_groups as $private_track_group) {
                $current_private_tracks = $private_track_group->getCategories();
                if ($current_private_tracks->count() == 0) continue;
                $private_tracks = array_merge($private_tracks, array_values($current_private_tracks));
            }
        }

        if (count($private_tracks) > 0) {
            $excluded_tracks = array_merge($excluded_tracks, $private_tracks);
        }

        $exclude_category_dql = '';
        if (count($excluded_tracks) > 0) {
            $exclude_category_dql = ' and p.category NOT IN (:exclude_tracks)';
        }

        if ($role == PresentationSpeaker::RoleSpeaker) {
            $query = $this->createQuery("SELECT p from models\summit\Presentation p 
            JOIN p.summit s
            JOIN p.speakers sp_presentation 
            JOIN sp_presentation.speaker sp
            WHERE s.id = :summit_id 
            and sp.id = :speaker_id
            and p.published = 1 and p.type IN (:types)" . $exclude_category_dql);
        } else {
            $query = $this->createQuery("SELECT p from models\summit\Presentation p 
            JOIN p.summit s
            JOIN p.moderator m 
            WHERE s.id = :summit_id 
            and m.id = :speaker_id
            and p.published = 1 and p.type IN (:types)" . $exclude_category_dql);
        }

        $query
            ->setParameter('summit_id', $summit->getId())
            ->setParameter('types', $types)
            ->setParameter('speaker_id', $this->id);

        if (count($excluded_tracks) > 0) {
            $query->setParameter('exclude_tracks', $excluded_tracks);
        }

        return $query->getResult();
    }


    /**
     * @param null|int $summit_id
     * @param bool|true $published_ones
     * @return Presentation[]
     */
    public function moderated_presentations($summit_id, $published_ones = true)
    {

        return $this->moderated_presentations
            ->filter(function ($p) use ($published_ones, $summit_id) {
                $res = $published_ones ? $p->isPublished() : true;
                $res &= is_null($summit_id) ? true : $p->getSummit()->getId() == $summit_id;
                return $res;
            });
    }

    /**
     * @param int $presentation_id
     * @return Presentation
     */
    public function getPresentation($presentation_id)
    {
        return $this->presentations->get($presentation_id);
    }

    /**
     * @param null $summit_id
     * @param bool|true $published_ones
     * @return array
     */
    public function getPresentationIds($summit_id, $published_ones = true)
    {
        $ids = [];
        foreach ($this->presentations($summit_id, $published_ones) as $p) {
            $ids[] = intval($p->getId());
        }
        return $ids;
    }

    /**
     * @param bool|true $published_ones
     * @return array
     */
    public function getAllPresentationIds($published_ones = true)
    {
        $ids = [];
        foreach ($this->presentations(null, $published_ones) as $p) {
            $ids[] = intval($p->getId());
        }
        return $ids;
    }

    /**
     * @param null $summit_id
     * @param bool|true $published_ones
     * @return array
     */
    public function getPresentations($summit_id, $published_ones = true)
    {
        return $this->presentations($summit_id, $published_ones)->map(function ($entity) {
            return $entity;
        })->toArray();
    }

    /**
     * @param bool|true $published_ones
     * @return array
     */
    public function getAllPresentations($published_ones = true)
    {
        return $this->presentations(null, $published_ones)->map(function ($entity) {
            return $entity;
        })->toArray();
    }


    /**
     * @param null $summit_id
     * @param bool|true $published_ones
     * @return array
     */
    public function getModeratedPresentationIds($summit_id, $published_ones = true)
    {
        $ids = [];
        foreach ($this->moderated_presentations($summit_id, $published_ones) as $p) {
            $ids[] = intval($p->getId());
        }
        return $ids;
    }

    /**
     * @param bool|true $published_ones
     * @return array
     */
    public function getAllModeratedPresentationIds($published_ones = true)
    {
        $ids = [];
        foreach ($this->moderated_presentations(null, $published_ones) as $p) {
            $ids[] = intval($p->getId());
        }
        return $ids;
    }

    /**
     * @param null $summit_id
     * @param bool|true $published_ones
     * @return array
     */
    public function getModeratedPresentations($summit_id, $published_ones = true)
    {
        return $this->moderated_presentations($summit_id, $published_ones)->map(function ($entity) {
            return $entity;
        })->toArray();
    }

    /**
     * @param bool|true $published_ones
     * @return array
     */
    public function getAllModeratedPresentations($published_ones = true)
    {
        return $this->moderated_presentations(null, $published_ones)->map(function ($entity) {
            return $entity;
        })->toArray();
    }

    /**
     * @param Presentation $presentation
     * @return int|null
     */
    public function getPresentationAssignmentOrder(Presentation $presentation): ?int
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('presentation', $presentation));
        $res = $this->presentations->matching($criteria)->first();
        return $res === false ? null : $res->getOrder();
    }

    /**
     * @param Presentation $presentation
     * @return bool
     */
    public function hasPresentationAssigned(Presentation $presentation): bool
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('presentation', $presentation));
        return $this->presentations->matching($criteria)->count() > 0;
    }

    /**
     * @return File|null
     */
    public function getPhoto(): ?File
    {
        try {
            return $this->photo;
        } catch (EntityNotFoundException $ex) {
            Log::warning($ex);
            return null;
        } catch (\Exception $ex) {
            Log::warning($ex);
            return null;
        }
    }

    /**
     * @param File $photo
     */
    public function setPhoto(File $photo)
    {
        $this->photo = $photo;
    }

    /**
     * @return Member
     */
    public function getMember()
    {
        return $this->member;
    }

    /**
     * @param Member $member
     */
    public function setMember(Member $member)
    {
        $this->member = $member;
        $member->setSpeaker($this);
    }

    /**
     * @return bool
     */
    public function hasMember()
    {
        return $this->getMemberId() > 0;
    }

    /**
     * @return int
     */
    public function getMemberId()
    {
        try {
            if (is_null($this->member)) return 0;
            return $this->member->getId();
        } catch (\Exception $ex) {
            return 0;
        }
    }

    /**
     * @return SpeakerRegistrationRequest
     */
    public function getRegistrationRequest()
    {
        return $this->registration_request;
    }

    /**
     * @param SpeakerRegistrationRequest $registration_request
     */
    public function setRegistrationRequest($registration_request)
    {
        $this->registration_request = $registration_request;
        $registration_request->setSpeaker($this);
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
        if (empty($fullname) && $this->hasMember()) {
            $fullname = $this->member->getFullName();
        }

        return $fullname;
    }

    /**
     * @return PresentationSpeakerSummitAssistanceConfirmationRequest[]
     */
    public function getSummitAssistances()
    {
        return $this->summit_assistances;
    }

    /**
     * @param PresentationSpeakerSummitAssistanceConfirmationRequest $assistance
     * @return $this
     */
    public function addSummitAssistance(PresentationSpeakerSummitAssistanceConfirmationRequest $assistance)
    {
        if ($this->summit_assistances->contains($assistance)) return $this;
        $this->summit_assistances->add($assistance);
        $assistance->setSpeaker($this);
        return $this;
    }

    /**
     * @param Summit $summit
     * @return PresentationSpeakerSummitAssistanceConfirmationRequest
     */
    public function getAssistanceFor(Summit $summit)
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('summit', $summit));
        $res = $this->summit_assistances->matching($criteria)->first();
        return $res === false ? null : $res;
    }

    /**
     * @param Summit $summit
     * @return bool
     */
    public function hasAssistanceFor(Summit $summit)
    {
        return $this->getAssistanceFor($summit) != null;
    }

    /**
     * @return mixed
     */
    public function getCreatedFromApi()
    {
        return $this->created_from_api;
    }

    /**
     * @param mixed $created_from_api
     */
    public function setCreatedFromApi($created_from_api)
    {
        $this->created_from_api = $created_from_api;
    }

    /**
     * @param Summit $summit
     * @return PresentationSpeakerSummitAssistanceConfirmationRequest
     */
    public function buildAssistanceFor(Summit $summit)
    {
        $request = new PresentationSpeakerSummitAssistanceConfirmationRequest;
        $request->setSummit($summit);
        $request->setSpeaker($this);
        return $request;
    }

    /**
     * @param Summit $summit
     * @return bool
     */
    public function isSpeakerOfSummit(Summit $summit)
    {

        $query = <<<SQL
SELECT DISTINCT Summit.* FROM SummitEvent 
INNER JOIN Summit ON Summit.ID = SummitEvent.SummitID
INNER JOIN Presentation ON Presentation.ID = SummitEvent.ID
WHERE
SummitEvent.Published = 1
AND (
	EXISTS ( 
		SELECT Presentation_Speakers.ID FROM Presentation_Speakers 
		WHERE Presentation_Speakers.PresentationID = Presentation.ID AND
		Presentation_Speakers.PresentationSpeakerID = :speaker_id
	) OR
    Presentation.ModeratorID = :speaker_id
)
AND Summit.ID = :summit_id;
SQL;

        $rsm = new ResultSetMappingBuilder($this->getEM());
        $rsm->addRootEntityFromClassMetadata(\models\summit\Summit::class, 's');

        // build rsm here
        $native_query = $this->getEM()->createNativeQuery($query, $rsm);


        $native_query->setParameter("speaker_id", $this->id);
        $native_query->setParameter("summit_id", $summit->getId());

        $summits = $native_query->getResult();

        return count($summits) > 0;
    }

    /**
     * @return Summit[]
     */
    public function getRelatedSummits()
    {

        $query = <<<SQL
SELECT DISTINCT Summit.* FROM Presentation_Speakers 
INNER JOIN Presentation ON Presentation.ID = Presentation_Speakers.PresentationID
INNER JOIN SummitEvent ON SummitEvent.ID = Presentation.ID
INNER JOIN Summit ON Summit.ID = SummitEvent.SummitID
WHERE SummitEvent.Published = 1 AND 
( Presentation_Speakers.PresentationSpeakerID = :speaker_id OR  Presentation.ModeratorID = :speaker_id )
SQL;

        $rsm = new ResultSetMappingBuilder($this->getEM());
        $rsm->addRootEntityFromClassMetadata(\models\summit\Summit::class, 's');

        // build rsm here
        $native_query = $this->getEM()->createNativeQuery($query, $rsm);

        $native_query->setParameter("speaker_id", $this->id);

        $summits = $native_query->getResult();
        if (count($summits) == 0) {
            $assistance = $this->getLatestAssistance();
            if (!$assistance) return [];
            return [$assistance->getSummit()];
        }
        return $summits;
    }

    /*
    * @return null|string
    */
    public function getEmail()
    {
        try {
            if (!is_null($this->member)) {
                return $this->member->getEmail();
            }
        } catch (\Exception $ex) {
            Log::warning($ex);
        }
        try {
            if (!is_null($this->registration_request)) {
                return $this->registration_request->getEmail();
            }
        } catch (\Exception $ex) {
            Log::warning($ex);
        }
        return null;
    }

    /**
     * @return bool
     */
    public function hasRegistrationRequest()
    {
        return $this->getRegistrationRequestId() > 0;
    }

    /**
     * @return int
     */
    public function getRegistrationRequestId()
    {
        try {
            if (is_null($this->registration_request)) return 0;
            return $this->registration_request->getId();
        } catch (\Exception $ex) {
            return 0;
        }
    }

    /**
     * @return PresentationSpeakerSummitAssistanceConfirmationRequest|null
     */
    public function getLatestAssistance()
    {
        return !is_null($this->summit_assistances) ? $this->summit_assistances->last() : null;
    }

    /**
     * @return bool
     */
    public function isAvailableForBureau()
    {
        return $this->available_for_bureau;
    }

    /**
     * @param bool $available_for_bureau
     */
    public function setAvailableForBureau($available_for_bureau)
    {
        $this->available_for_bureau = $available_for_bureau;
    }

    /**
     * @return bool
     */
    public function isFundedTravel()
    {
        return $this->funded_travel;
    }

    /**
     * @param bool $funded_travel
     */
    public function setFundedTravel($funded_travel)
    {
        $this->funded_travel = $funded_travel;
    }

    /**
     * @return bool
     */
    public function isWillingToTravel()
    {
        return $this->willing_to_travel;
    }

    /**
     * @param bool $willing_to_travel
     */
    public function setWillingToTravel($willing_to_travel)
    {
        $this->willing_to_travel = $willing_to_travel;
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
     * @return bool
     */
    public function isWillingToPresentVideo()
    {
        return $this->willing_to_present_video;
    }

    /**
     * @param bool $willing_to_present_video
     */
    public function setWillingToPresentVideo($willing_to_present_video)
    {
        $this->willing_to_present_video = $willing_to_present_video;
    }

    /**
     * @return string
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * @param string $notes
     */
    public function setNotes($notes)
    {
        $this->notes = $notes;
    }

    /**
     * @return bool
     */
    public function isOrgHasCloud()
    {
        return $this->org_has_cloud;
    }

    /**
     * @param bool $org_has_cloud
     */
    public function setOrgHasCloud($org_has_cloud)
    {
        $this->org_has_cloud = $org_has_cloud;
    }

    /**
     * @return SpeakerExpertise[]
     */
    public function getAreasOfExpertise()
    {
        return $this->areas_of_expertise;
    }

    public function clearAreasOfExpertise()
    {
        $this->areas_of_expertise->clear();
    }

    /**
     * @param SpeakerExpertise $area_of_expertise
     */
    public function addAreaOfExpertise(SpeakerExpertise $area_of_expertise)
    {
        $this->areas_of_expertise->add($area_of_expertise);
        $area_of_expertise->setSpeaker($this);
    }

    /**
     * @return SpeakerPresentationLink[]
     */
    public function getOtherPresentationLinks()
    {
        return $this->other_presentation_links;
    }

    /**
     * @param SpeakerPresentationLink $link
     */
    public function addOtherPresentationLink(SpeakerPresentationLink $link)
    {
        $this->other_presentation_links->add($link);
        $link->setSpeaker($this);
    }


    public function clearOtherPresentationLinks()
    {
        $this->other_presentation_links->clear();
    }

    /**
     * @return SpeakerTravelPreference[]
     */
    public function getTravelPreferences()
    {
        return $this->travel_preferences;
    }

    /**
     * @param SpeakerTravelPreference $travel_preference
     */
    public function addTravelPreference(SpeakerTravelPreference $travel_preference)
    {
        $this->travel_preferences->add($travel_preference);
        $travel_preference->setSpeaker($this);
    }

    public function clearTravelPreferences()
    {
        $this->travel_preferences->clear();
    }

    /**
     * @return Language[]
     */
    public function getLanguages()
    {
        return $this->languages;
    }

    /**
     * @param Language $language
     */
    public function addLanguage(Language $language)
    {
        if ($this->languages->contains($language)) return;
        $this->languages->add($language);
    }

    /**
     *
     */
    public function clearLanguages()
    {
        $this->languages->clear();
    }

    /**
     * @return SpeakerOrganizationalRole[]
     */
    public function getOrganizationalRoles()
    {
        return $this->organizational_roles;
    }

    public function clearOrganizationalRoles()
    {
        $this->organizational_roles->clear();
    }

    public function addOrganizationalRole(SpeakerOrganizationalRole $role)
    {
        $this->organizational_roles->add($role);
    }

    /**
     * @return SpeakerActiveInvolvement[]
     */
    public function getActiveInvolvements()
    {
        return $this->active_involvements;
    }

    public function clearActiveInvolvements()
    {
        $this->active_involvements->clear();
    }

    /**
     * @param SpeakerActiveInvolvement $active_involvement
     */
    public function addActiveInvolvement(SpeakerActiveInvolvement $active_involvement)
    {
        $this->active_involvements->add($active_involvement);
    }

    /**
     * @param Presentation $presentation
     */
    public function addModeratedPresentation(Presentation $presentation)
    {
        $this->moderated_presentations->add($presentation);
        $presentation->setModerator($this);
    }

    /**
     * @param Summit $summit
     * @return bool
     */
    public function isModeratorFor(Summit $summit)
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('summit', $summit));
        return $this->moderated_presentations->matching($criteria)->count() > 0;
    }

    /**
     * @param Summit $summit
     * @return bool
     */
    public function announcementEmailAlreadySent(Summit $summit)
    {
        $email_type = $this->getAnnouncementEmailTypeSent($summit);
        return !is_null($email_type) && $email_type !== SpeakerAnnouncementSummitEmail::TypeNone;
    }

    /**
     * @param Summit $summit
     * @return string|null
     */
    public function getAnnouncementEmailTypeSent(Summit $summit)
    {
        $criteria = Criteria::create();

        $criteria
            ->where(Criteria::expr()->eq('summit', $summit))
            ->andWhere(Criteria::expr()->notIn('type', [
                SpeakerAnnouncementSummitEmail::TypeCreateMembership,
                SpeakerAnnouncementSummitEmail::TypeSecondBreakoutRegister,
                SpeakerAnnouncementSummitEmail::TypeSecondBreakoutReminder,
            ]));

        $email = $this->announcement_summit_emails->matching($criteria)->first();

        return $email ? $email->getType() : null;
    }

    /**
     * @param Summit $summit
     * @param string $type
     * @return bool
     */
    public function hasAnnouncementEmailTypeSent(Summit $summit, string $type): bool
    {
        $criteria = Criteria::create();

        $criteria
            ->where(Criteria::expr()->eq('summit', $summit))
            ->andWhere(Criteria::expr()->eq('type', $type));

        return $this->announcement_summit_emails->matching($criteria)->count() > 0;
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
    public function getBigProfilePhotoUrl(): ?string
    {
        $default_pic = Config::get("app.default_profile_image", null);
        try {
            $photoUrl = null;
            if ($this->hasBigPhoto() && $photo = $this->getBigPhoto()) {
                $photoUrl = $photo->getUrl();
            }
            if (empty($photoUrl) && $this->hasMember() && $this->member->hasPhoto() && $photo = $this->member->getPhoto()) {
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
     * @return string|null
     */
    public function getProfilePhotoUrl(): ?string
    {
        $default_pic = Config::get("app.default_profile_image", null);
        try {
            $photoUrl = null;
            if ($this->hasPhoto() && $photo = $this->getPhoto()) {
                $photoUrl = $photo->getUrl();
            }
            if (empty($photoUrl) && $this->hasMember() && $this->member->hasPhoto() && $photo = $this->member->getPhoto()) {
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
        $url .= md5(strtolower(trim($this->getEmail())));
        return $url;
    }

    /**
     * @param Member $member
     * @return bool
     */
    public function canBeEditedBy(Member $member): bool
    {
        Log::debug(sprintf("PresentationSpeaker::canBeEditedBy member %s speaker member id %s", $member->getId(), $this->getMemberId()));
        if ($member->isAdmin()) return true;
        if ($member->isSummitAdmin()) return true;
        if ($this->getMemberId() == $member->getId()) return true;
        $criteria = Criteria::create();
        $criteria
            ->where(Criteria::expr()->eq('requested_by', $member))
            ->andWhere(Criteria::expr()->eq('approved', true));
        return $this->granted_edit_permissions->matching($criteria)->count() > 0;
    }

    /**
     * @return bool
     */
    public function hasBigPhoto()
    {
        return $this->getBigPhotoId() > 0;
    }

    /**
     * @return int
     */
    public function getBigPhotoId()
    {
        try {
            if (is_null($this->big_photo)) return 0;
            return $this->big_photo->getId();
        } catch (\Exception $ex) {
            Log::warning($ex);
            return 0;
        }
    }

    public function getBigPhoto(): ?File
    {
        try {
            return $this->big_photo;
        } catch (EntityNotFoundException $ex) {
            Log::warning($ex);
            return null;
        } catch (\Exception $ex) {
            Log::warning($ex);
            return null;
        }
    }

    /**
     * @param File $big_photo
     */
    public function setBigPhoto(File $big_photo): void
    {
        $this->big_photo = $big_photo;
    }

    public function clearBigPhoto(): void
    {
        $this->big_photo = null;
    }

    public function clearPhoto(): void
    {
        $this->photo = null;
    }

    /**
     * @return string|null
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
     * @return mixed
     */
    public function getPhoneNumber()
    {
        return $this->phone_number;
    }

    /**
     * @param mixed $phoner_number
     */
    public function setPhoneNumber($phone_number): void
    {
        $this->phone_number = $phone_number;
    }

    public function addAnnouncementSummitEmail(SpeakerAnnouncementSummitEmail $announcementSummitEmail)
    {
        if ($this->announcement_summit_emails->contains($announcementSummitEmail)) return;
        $this->announcement_summit_emails->add($announcementSummitEmail);
        $announcementSummitEmail->setSpeaker($this);
    }

    public function removeAnnouncementSummitEmail(SpeakerAnnouncementSummitEmail $announcementSummitEmail)
    {
        if (!$this->announcement_summit_emails->contains($announcementSummitEmail)) return;
        $this->announcement_summit_emails->removeElement($announcementSummitEmail);
        $announcementSummitEmail->clearSpeaker();
    }

    use ScheduleEntity;
}