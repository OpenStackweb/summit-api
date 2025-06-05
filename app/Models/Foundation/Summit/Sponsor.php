<?php namespace models\summit;
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

use App\Models\Foundation\ExtraQuestions\ExtraQuestionType;
use App\Models\Foundation\ExtraQuestions\ExtraQuestionTypeConstants;
use App\Models\Foundation\Main\IGroup;
use App\Models\Foundation\Main\IOrderable;
use App\Models\Foundation\Main\OrderableChilds;
use App\Models\Foundation\Summit\ExtraQuestions\SummitSponsorExtraQuestionType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use models\exceptions\ValidationException;
use models\main\Company;
use models\main\File;
use models\main\Member;
use models\utils\One2ManyPropertyTrait;
use models\utils\SilverstripeBaseModel;

/**
 * @package models\summit
 */
#[ORM\Table(name: 'Sponsor')]
#[ORM\Entity(repositoryClass: \App\Repositories\Summit\DoctrineSponsorRepository::class)]
#[ORM\AssociationOverrides([new ORM\AssociationOverride(name: 'summit', inversedBy: 'summit_sponsors')])]
class Sponsor extends SilverstripeBaseModel implements IOrderable
{
    use SummitOwned;

    use One2ManyPropertyTrait;

    use OrderableChilds;

    const MaxExtraQuestionCount = 5;

    protected $getIdMappings = [
        'getSideImageId' => 'side_image',
        'getHeaderImageId' => 'header_image',
        'getHeaderImageMobileId' => 'header_image_mobile',
        'getCarouselAdvertiseImageId' => 'carousel_advertise_image',
        'getFeaturedEventId' => 'featured_event',
        'getCompanyId' => 'company',
        'getSponsorshipId' => 'sponsorship',
    ];

    protected $hasPropertyMappings = [
        'hasSideImage' => 'side_image',
        'hasHeaderImage' => 'header_image',
        'hasHeaderImageMobile' => 'header_image_mobile',
        'hasCarouselAdvertiseImage' => 'carousel_advertise_image',
        'hasFeaturedEvent' => 'featured_event',
        'hasCompany' => 'company',
        'hasSponsorship' => 'sponsorship',
    ];

    /**
     * @var int
     */
    #[ORM\Column(name: '`CustomOrder`', type: 'integer')]
    private $order;

    /**
     * @var Company
     */
    #[ORM\JoinColumn(name: 'CompanyID', referencedColumnName: 'ID', onDelete: 'SET NULL')]
    #[ORM\ManyToOne(targetEntity: \models\main\Company::class)]
    protected $company;

    /**
     * @var SummitEvent
     */
    #[ORM\JoinColumn(name: 'FeaturedEventID', referencedColumnName: 'ID', onDelete: 'SET NULL')]
    #[ORM\ManyToOne(targetEntity: \models\summit\SummitEvent::class)]
    protected $featured_event;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'IsPublished', type: 'boolean')]
    protected $is_published;

    /**
     * @var SummitSponsorshipType
     */
    #[ORM\JoinColumn(name: 'SummitSponsorshipTypeID', referencedColumnName: 'ID', onDelete: 'SET NULL')]
    #[ORM\ManyToOne(targetEntity: \SummitSponsorshipType::class)]
    protected $sponsorship;

    /**
     * @var SponsorUserInfoGrant[]
     */
    #[ORM\OneToMany(targetEntity: \SponsorUserInfoGrant::class, mappedBy: 'sponsor', cascade: ['persist'], orphanRemoval: true)]
    protected $user_info_grants;

    /**
     * @var SponsorAd[]
     */
    #[ORM\OneToMany(targetEntity: \SponsorAd::class, mappedBy: 'sponsor', cascade: ['persist'], orphanRemoval: true)]
    protected $ads;

    /**
     * @var SponsorMaterial[]
     */
    #[ORM\OneToMany(targetEntity: \SponsorMaterial::class, mappedBy: 'sponsor', cascade: ['persist'], orphanRemoval: true)]
    protected $materials;

    /**
     * @var SponsorSocialNetwork[]
     */
    #[ORM\OneToMany(targetEntity: \SponsorSocialNetwork::class, mappedBy: 'sponsor', cascade: ['persist'], orphanRemoval: true)]
    protected $social_networks;

    /**
     * @var Member[]
     */
    #[ORM\JoinTable(name: 'Sponsor_Users')]
    #[ORM\JoinColumn(name: 'SponsorID', referencedColumnName: 'ID')]
    #[ORM\InverseJoinColumn(name: 'MemberID', referencedColumnName: 'ID')]
    #[ORM\ManyToMany(targetEntity: \models\main\Member::class, inversedBy: 'sponsor_memberships')]
    protected $members;

    /**
     * @var File
     */
    #[ORM\JoinColumn(name: 'SideImageID', referencedColumnName: 'ID')]
    #[ORM\ManyToOne(targetEntity: \models\main\File::class, cascade: ['persist'])]
    protected $side_image;

    /**
     * @var File
     */
    #[ORM\JoinColumn(name: 'HeaderImageID', referencedColumnName: 'ID')]
    #[ORM\ManyToOne(targetEntity: \models\main\File::class, cascade: ['persist'])]
    protected $header_image;

    /**
     * @var File
     */
    #[ORM\JoinColumn(name: 'HeaderImageMobileID', referencedColumnName: 'ID')]
    #[ORM\ManyToOne(targetEntity: \models\main\File::class, cascade: ['persist'])]
    protected $header_image_mobile;

    /**
     * @var File
     */
    #[ORM\JoinColumn(name: 'CarouselAdvertiseImageID', referencedColumnName: 'ID')]
    #[ORM\ManyToOne(targetEntity: \models\main\File::class, cascade: ['persist'])]
    protected $carousel_advertise_image;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Marquee', type: 'string')]
    private $marquee;

    /**
     * @var string
     */
    #[ORM\Column(name: 'HeaderImageAltText', type: 'string')]
    private $header_image_alt_text;

    /**
     * @var string
     */
    #[ORM\Column(name: 'SideImageAltText', type: 'string')]
    private $side_image_alt_text;

    /**
     * @var string
     */
    #[ORM\Column(name: 'HeaderImageMobileAltText', type: 'string')]
    private $header_image_mobile_alt_text;

    /**
     * @var string
     */
    #[ORM\Column(name: 'CarouselAdvertiseImageAltText', type: 'string')]
    private $carousel_advertise_image_alt_text;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Intro', type: 'string')]
    private $intro;

    /**
     * @var string
     */
    #[ORM\Column(name: 'ExternalLink', type: 'string')]
    private $external_link;

    /**
     * @var string
     */
    #[ORM\Column(name: 'VideoLink', type: 'string')]
    private $video_link;

    /**
     * @var string
     */
    #[ORM\Column(name: 'ChatLink', type: 'string')]
    private $chat_link;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'ShowLogoInEventPage', type: 'boolean')]
    private $show_logo_in_event_page;

    /**
     * @var SummitSponsorExtraQuestionType[]
     */
    #[ORM\OneToMany(targetEntity: \App\Models\Foundation\Summit\ExtraQuestions\SummitSponsorExtraQuestionType::class, mappedBy: 'sponsor', cascade: ['persist', 'remove'], orphanRemoval: true, fetch: 'EXTRA_LAZY')]
    private $extra_questions;

    /**
     * @var SummitLeadReportSetting
     */
    #[ORM\OneToOne(targetEntity: \models\summit\SummitLeadReportSetting::class, mappedBy: 'sponsor', cascade: ['persist', 'remove'], orphanRemoval: true, fetch: 'EXTRA_LAZY')]
    private $lead_report_setting;

    /**
     * Sponsor constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->members = new ArrayCollection();
        $this->user_info_grants = new ArrayCollection();
        $this->materials = new ArrayCollection();
        $this->social_networks = new ArrayCollection();
        $this->ads = new ArrayCollection();
        $this->is_published = true;
        $this->show_logo_in_event_page = true;
        $this->extra_questions = new ArrayCollection;
    }

    public static function getAllowedQuestionTypes(): array
    {
        return [
            ExtraQuestionTypeConstants::CheckBoxQuestionType,
            ExtraQuestionTypeConstants::CheckBoxListQuestionType,
            ExtraQuestionTypeConstants::RadioButtonListQuestionType,
            ExtraQuestionTypeConstants::ComboBoxQuestionType,
            ExtraQuestionTypeConstants::TextQuestionType,
        ];
    }

    /**
     * @return int
     */
    public function getOrder(): ?int
    {
        return $this->order;
    }

    /**
     * @param int $order
     */
    public function setOrder($order): void
    {
        $this->order = $order;
    }

    /**
     * @return Company
     */
    public function getCompany(): ?Company
    {
        return $this->company;
    }

    /**
     * @param Company $company
     */
    public function setCompany(Company $company): void
    {
        $this->company = $company;
    }

    /**
     * @return SummitSponsorshipType
     */
    public function getSponsorship(): ?SummitSponsorshipType
    {
        return $this->sponsorship;
    }

    /**
     * @param SummitSponsorshipType $sponsorship
     */
    public function setSponsorship(SummitSponsorshipType $sponsorship): void
    {
        $this->sponsorship = $sponsorship;
    }

    /**
     * @return Member[]
     */
    public function getMembers()
    {
        return $this->members;
    }

    /**
     * @param SponsorUserInfoGrant $grant
     */
    public function addUserInfoGrant(SponsorUserInfoGrant $grant)
    {
        if ($this->user_info_grants->contains($grant)) return;
        $this->user_info_grants->add($grant);
        $grant->setSponsor($this);
    }

    public function getUserInfoGrants()
    {
        return $this->user_info_grants;
    }

    /**
     * @param int $grant_id
     * @return SponsorUserInfoGrant|null
     */
    public function getUserInfoGrantById(int $grant_id): ?SponsorUserInfoGrant
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', $grant_id));
        $grant = $this->user_info_grants->matching($criteria)->first();
        return $grant === false ? null : $grant;
    }

    /**
     * @param Member $member
     * @return bool
     */
    public function hasGrant(Member $member): bool
    {
        return !is_null($this->getGrant($member));
    }

    /**
     * @param Member $member
     * @return SponsorUserInfoGrant|null
     */
    public function getGrant(Member $member): ?SponsorUserInfoGrant
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('allowed_user', $member));
        $grant = $this->user_info_grants->matching($criteria)->first();
        return $grant === false ? null : $grant;
    }

    /**
     * @param Member $user
     * @throws ValidationException
     */
    public function addUser(Member $user)
    {
        if ($this->members->contains($user)) return;
        if (!$user->isSponsorUser()) {
            throw new ValidationException
            (
                sprintf
                (
                    "Member %s does not belong to group %s",
                    $user->getId(),
                    IGroup::Sponsors
                )
            );
        }

        if($user->hasSponsorMembershipsFor($this->getSummit()))
            throw new ValidationException
            (
                sprintf
                (
                    "Member %s already belongs to an sponsor for summit %s",
                    $user->getId(),
                    $this->getSummit()->getId()
                )
            );

        $this->members->add($user);
    }

    /**
     * @param Member $user
     */
    public function removeUser(Member $user)
    {
        if (!$this->members->contains($user)) return;
        $this->members->removeElement($user);
    }

    /**
     * @return bool
     */
    public function isPublished(): bool
    {
        return $this->is_published;
    }

    /**
     * @param bool $is_published
     */
    public function setIsPublished(bool $is_published): void
    {
        $this->is_published = $is_published;
    }

    /**
     * @return string
     */
    public function getMarquee(): ?string
    {
        return $this->marquee;
    }

    /**
     * @param string $marquee
     */
    public function setMarquee(string $marquee): void
    {
        $this->marquee = $marquee;
    }

    /**
     * @return string|null
     */
    public function getIntro(): ?string
    {
        return $this->intro;
    }

    /**
     * @param string $intro
     */
    public function setIntro(string $intro): void
    {
        $this->intro = $intro;
    }

    /**
     * @return string|null
     */
    public function getExternalLink(): ?string
    {
        return $this->external_link;
    }

    /**
     * @param string $external_link
     */
    public function setExternalLink(string $external_link): void
    {
        $this->external_link = $external_link;
    }

    /**
     * @return string|null
     */
    public function getVideoLink(): ?string
    {
        return $this->video_link;
    }

    /**
     * @param string $video_link
     */
    public function setVideoLink(string $video_link): void
    {
        $this->video_link = $video_link;
    }

    /**
     * @return string|null
     */
    public function getChatLink(): ?string
    {
        return $this->chat_link;
    }

    /**
     * @param string $chat_link
     */
    public function setChatLink(string $chat_link): void
    {
        $this->chat_link = $chat_link;
    }

    public function getSideImageUrl(): ?string
    {
        if ($this->hasSideImage())
            return $this->side_image->getUrl();
        return null;
    }

    public function getHeaderImageUrl(): ?string
    {
        if ($this->hasHeaderImage())
            return $this->header_image->getUrl();
        return null;
    }

    public function getHeaderImageMobileUrl(): ?string
    {
        if ($this->hasHeaderImageMobile())
            return $this->header_image_mobile->getUrl();
        return null;
    }

    public function getCarouselAdvertiseImageUrl(): ?string
    {
        if ($this->hasCarouselAdvertiseImage())
            return $this->carousel_advertise_image->getUrl();
        return null;
    }

    /**
     * @param File $side_image
     */
    public function setSideImage(File $side_image): void
    {
        $this->side_image = $side_image;
    }

    public function clearSideImage(): void
    {
        $this->side_image = null;
    }

    /**
     * @param File $header_image
     */
    public function setHeaderImage(File $header_image): void
    {
        $this->header_image = $header_image;
    }

    public function clearHeaderImage(): void
    {
        $this->header_image = null;
    }

    /**
     * @param File $header_image_mobile
     */
    public function setHeaderImageMobile(File $header_image_mobile): void
    {
        $this->header_image_mobile = $header_image_mobile;
    }

    public function clearHeaderImageMobile(): void
    {
        $this->header_image_mobile = null;
    }

    /**
     * @param File $carousel_advertise_image
     */
    public function setCarouselAdvertiseImage(File $carousel_advertise_image): void
    {
        $this->carousel_advertise_image = $carousel_advertise_image;
    }

    public function clearCarouselAdvertiseImage(): void
    {
        $this->carousel_advertise_image = null;
    }

    use OrderableChilds;

    /**
     * @return int
     */
    private function getAdMaxOrder(): int
    {
        $criteria = Criteria::create();
        $criteria->orderBy(['order' => 'DESC']);
        $ad = $this->ads->matching($criteria)->first();
        $res = $ad === false ? 0 : $ad->getOrder();
        return is_null($res) ? 0 : $res;
    }

    /**
     * @param SponsorAd $ad
     * @param int $new_order
     * @throws ValidationException
     */
    public function recalculateAdOrder(SponsorAd $ad, int $new_order)
    {
        self::recalculateOrderForSelectable($this->ads, $ad, $new_order);
    }

    /**
     * @param int $ad_id
     * @return SponsorAd|null
     */
    public function getAdById(int $ad_id): ?SponsorAd
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', $ad_id));
        $ad = $this->ads->matching($criteria)->first();
        return $ad === false ? null : $ad;
    }

    /**
     * @param SponsorAd $ad
     */
    public function addAd(SponsorAd $ad): void
    {
        if ($this->ads->contains($ad)) return;
        $ad->setOrder($this->getAdMaxOrder() + 1);
        $this->ads->add($ad);
        $ad->setSponsor($this);
    }

    /**
     * @param SponsorAd $ad
     */
    public function removeAd(SponsorAd $ad): void
    {
        if (!$this->ads->contains($ad)) return;
        $this->ads->removeElement($ad);
        $ad->clearSponsor();
        self::resetOrderForSelectable($this->ads);
    }

    public function clearAds(): void
    {
        $this->ads->clear();
    }

    public function getAds()
    {
        return $this->ads;
    }

    /**
     * @param int $material_id
     * @return SponsorMaterial|null
     */
    public function getMaterialById(int $material_id): ?SponsorMaterial
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', $material_id));
        $material = $this->materials->matching($criteria)->first();
        return $material === false ? null : $material;
    }

    /**
     * @param string $name
     * @return SponsorMaterial|null
     */
    public function getMaterialByName(string $name): ?SponsorMaterial
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('name', trim($name)));
        $material = $this->materials->matching($criteria)->first();
        return $material === false ? null : $material;
    }

    public function getMaterials()
    {
        return $this->materials;
    }

    /**
     * @param SponsorMaterial $material
     * @param int $new_order
     * @throws ValidationException
     */
    public function recalculateMaterialOrder(SponsorMaterial $material, int $new_order)
    {
        self::recalculateOrderForSelectable($this->materials, $material, $new_order);
    }

    /**
     * @return int
     */
    private function getMaterialMaxOrder(): int
    {
        $criteria = Criteria::create();
        $criteria->orderBy(['order' => 'DESC']);
        $material = $this->materials->matching($criteria)->first();
        $res = $material === false ? 0 : $material->getOrder();
        return is_null($res) ? 0 : $res;
    }

    /**
     * @param SponsorMaterial $material
     */
    public function addMaterial(SponsorMaterial $material): void
    {
        if ($this->materials->contains($material)) return;
        $material->setOrder($this->getMaterialMaxOrder() + 1);
        $this->materials->add($material);
        $material->setSponsor($this);
    }

    /**
     * @param SponsorMaterial $material
     */
    public function removeMaterial(SponsorMaterial $material): void
    {
        if (!$this->materials->contains($material)) return;
        $this->materials->removeElement($material);
        $material->clearSponsor();
        self::resetOrderForSelectable($this->materials);
    }

    /**
     * @param int $social_network_id
     * @return SponsorSocialNetwork|null
     */
    public function getSocialNetworkById(int $social_network_id): ?SponsorSocialNetwork
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', $social_network_id));
        $social_network = $this->social_networks->matching($criteria)->first();
        return $social_network === false ? null : $social_network;
    }

    /**
     * @param SponsorSocialNetwork $social_network
     */
    public function addSocialNetwork(SponsorSocialNetwork $social_network): void
    {
        if ($this->social_networks->contains($social_network)) return;
        $this->social_networks->add($social_network);
        $social_network->setSponsor($this);
    }

    /**
     * @param SponsorSocialNetwork $social_network
     */
    public function removeSocialNetwork(SponsorSocialNetwork $social_network): void
    {
        if (!$this->social_networks->contains($social_network)) return;
        $this->social_networks->removeElement($social_network);
        $social_network->clearSponsor();
    }

    public function getSocialNetworks()
    {
        return $this->social_networks;
    }

    /**
     * @return SummitEvent|null
     */
    public function getFeaturedEvent(): ?SummitEvent
    {
        return $this->featured_event;
    }

    public function clearFeaturedEvent():void{
        $this->featured_event = null;
    }

    /**
     * @param SummitEvent $featured_event
     */
    public function setFeaturedEvent(SummitEvent $featured_event): void
    {
        $this->featured_event = $featured_event;
    }

    /**
     * @return string
     */
    public function getHeaderImageAltText(): ?string
    {
        return $this->header_image_alt_text;
    }

    /**
     * @param string $header_image_alt_text
     */
    public function setHeaderImageAltText(string $header_image_alt_text): void
    {
        $this->header_image_alt_text = $header_image_alt_text;
    }

    /**
     * @return string
     */
    public function getSideImageAltText(): ?string
    {
        return $this->side_image_alt_text;
    }

    /**
     * @param string $side_image_alt_text
     */
    public function setSideImageAltText(string $side_image_alt_text): void
    {
        $this->side_image_alt_text = $side_image_alt_text;
    }

    /**
     * @return string
     */
    public function getHeaderImageMobileAltText(): ?string
    {
        return $this->header_image_mobile_alt_text;
    }

    /**
     * @param string $header_image_mobile_alt_text
     */
    public function setHeaderImageMobileAltText(string $header_image_mobile_alt_text): void
    {
        $this->header_image_mobile_alt_text = $header_image_mobile_alt_text;
    }

    /**
     * @return string
     */
    public function getCarouselAdvertiseImageAltText(): ?string
    {
        return $this->carousel_advertise_image_alt_text;
    }

    /**
     * @param string $carousel_advertise_image_alt_text
     */
    public function setCarouselAdvertiseImageAltText(string $carousel_advertise_image_alt_text): void
    {
        $this->carousel_advertise_image_alt_text = $carousel_advertise_image_alt_text;
    }

    /**
     * @return bool
     */
    public function isShowLogoInEventPage(): bool
    {
        return $this->show_logo_in_event_page;
    }

    /**
     * @param bool $show_logo_in_event_page
     */
    public function setShowLogoInEventPage(bool $show_logo_in_event_page): void
    {
        $this->show_logo_in_event_page = $show_logo_in_event_page;
    }

    /**
     * @return SummitSponsorExtraQuestionType[]
     */
    public function getExtraQuestions()
    {
        $criteria = Criteria::create();
        $criteria->orderBy(['order' => 'ASC']);
        return $this->extra_questions->matching($criteria);
    }

    /**
     * @param int $extra_question_id
     * @return SponsorSocialNetwork|null
     */
    public function getExtraQuestionById(int $extra_question_id): ?SummitSponsorExtraQuestionType
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', $extra_question_id));
        $extra_questions = $this->extra_questions->matching($criteria)->first();
        return $extra_questions === false ? null : $extra_questions;
    }

    /**
     * @param ExtraQuestionType $extra_question
     * @throws ValidationException
     */
    public function addExtraQuestion(ExtraQuestionType $extra_question): void
    {
        if ($this->extra_questions->count() >= self::MaxExtraQuestionCount) {
            throw new ValidationException(sprintf('Sponsor %s cannot have more than %s extra questions.',
                $this->id, self::MaxExtraQuestionCount));
        }
        if ($this->extra_questions->contains($extra_question)) return;
        $extra_question->setOrder($this->getSponsorExtraQuestionMaxOrder() + 1);
        $this->extra_questions->add($extra_question);
        $extra_question->setSponsor($this);
    }

    /**
     * @return int
     */
    private function getSponsorExtraQuestionMaxOrder(): int
    {
        $criteria = Criteria::create();
        $criteria->orderBy(['order' => 'DESC']);
        $question = $this->extra_questions->matching($criteria)->first();
        return $question === false ? 0 : $question->getOrder();
    }

    public function clearExtraQuestions()
    {
        $this->extra_questions->clear();
    }

    /**
     * @param SummitSponsorExtraQuestionType $extra_question
     */
    public function removeExtraQuestion(SummitSponsorExtraQuestionType $extra_question)
    {
        if (!$this->extra_questions->contains($extra_question)) return;
        $this->extra_questions->removeElement($extra_question);
        $extra_question->clearSponsor();
    }

    /**
     * @param SummitSponsorExtraQuestionType $question
     * @param int $new_order
     * @throws ValidationException
     */
    public function recalculateQuestionOrder(SummitSponsorExtraQuestionType $question, int $new_order)
    {
        self::recalculateOrderForSelectable($this->extra_questions, $question, $new_order);
    }

    /**
     * @return SummitLeadReportSetting
     */
    public function getLeadReportSetting(): SummitLeadReportSetting
    {
        return $this->lead_report_setting ?? $this->summit->getLeadReportSettingFor($this);
    }

    /**
     * @param SummitLeadReportSetting $lead_report_setting
     */
    public function setLeadReportSetting(SummitLeadReportSetting $lead_report_setting): void
    {
        $lead_report_setting->setSponsor($this);
        $this->lead_report_setting = $lead_report_setting;
    }

    /**
     * @return void
     */
    public function clearLeadReportSetting()
    {
        if (is_null($this->lead_report_setting)) return;
        $this->lead_report_setting->clearSponsor();
        $this->lead_report_setting = null;
    }
}