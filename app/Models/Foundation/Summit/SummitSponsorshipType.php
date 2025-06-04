<?php namespace models\summit;
/*
 * Copyright 2022 OpenStack Foundation
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

use App\Models\Foundation\Main\IOrderable;
use App\Models\Utils\BaseEntity;
use Doctrine\ORM\Mapping as ORM;
use models\exceptions\ValidationException;
use models\main\File;
use models\utils\One2ManyPropertyTrait;

/**
 * @package models\summit
 */
#[ORM\Table(name: 'Summit_SponsorshipType')]
#[ORM\Entity(repositoryClass: \App\Repositories\Summit\DoctrineSummitSponsorshipTypeRepository::class)]
#[ORM\AssociationOverrides([new ORM\AssociationOverride(name: 'summit', inversedBy: 'sponsorship_types')])]
class SummitSponsorshipType extends BaseEntity implements IOrderable
{
    use SummitOwned;

    use One2ManyPropertyTrait;

    protected $getIdMappings = [
        'getBadgeImageId' => 'badge_image',
        'getTypeId' => 'type',
    ];

    protected $hasPropertyMappings = [
        'hasBadgeImage' => 'badge_image',
        'hasType' => 'type',
    ];

    const LobbyTemplate_BigImages = 'big-images';
    const LobbyTemplate_SmallImages = 'small-images';
    const LobbyTemplate_HorizontalImages = 'horizontal-images';
    const LobbyTemplate_CarouselImages = 'carousel';

    const ValidLobbyTemplates = [
        self::LobbyTemplate_BigImages,
        self::LobbyTemplate_SmallImages,
        self::LobbyTemplate_HorizontalImages,
        self::LobbyTemplate_CarouselImages,
    ];

    const ExpoHallTemplate_BigImages = 'big-images';
    const ExpoHallTemplate_MediumImages = 'medium-images';
    const ExpoHallTemplate_SmallImages = 'small-images';

    const ValidExpoHallTemplates = [
        self::ExpoHallTemplate_BigImages,
        self::ExpoHallTemplate_MediumImages,
        self::ExpoHallTemplate_SmallImages,
    ];

    const SponsorPageTemplate_BigHeader = 'big-header';
    const SponsorPageTemplate_SmallHeader = 'small-header';

    const ValidSponsorPageTemplates = [
        self::SponsorPageTemplate_BigHeader,
        self::SponsorPageTemplate_SmallHeader,
    ];

    const EventPageTemplate_BigImages = 'big-images';
    const EventPageTemplate_HorizontalImages = 'horizontal-images';
    const EventPageTemplate_SmallImages = 'small-images';

    const ValidEventPageTemplates = [
        self::EventPageTemplate_BigImages,
        self::EventPageTemplate_HorizontalImages,
        self::EventPageTemplate_SmallImages,
    ];

    /**
     * @var string
     */
    #[ORM\Column(name: 'WidgetTitle', type: 'string')]
    private $widget_title;

    /**
     * @var string
     */
    #[ORM\Column(name: 'LobbyTemplate', type: 'string')]
    private $lobby_template;

    /**
     * @var string
     */
    #[ORM\Column(name: 'ExpoHallTemplate', type: 'string')]
    private $expo_hall_template;

    /**
     * @var string
     */
    #[ORM\Column(name: 'SponsorPageTemplate', type: 'string')]
    private $sponsor_page_template;

    /**
     * @var string
     */
    #[ORM\Column(name: 'EventPageTemplate', type: 'string')]
    private $event_page_template;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'SponsorPageShouldUseDisqusWidget', type: 'boolean')]
    private $sponsor_page_use_disqus_widget;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'SponsorPageShouldUseLiveEventWidget', type: 'boolean')]
    private $sponsor_page_use_live_event_widget;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'SponsorPageShouldUseScheduleWidget', type: 'boolean')]
    private $sponsor_page_use_schedule_widget;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'ShouldDisplayOnExpoHallPage', type: 'boolean')]
    private $should_display_on_expo_hall_page;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'ShouldDisplayOnLobbyPage', type: 'boolean')]
    private $should_display_on_lobby_page;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'SponsorPageShouldUseBannerWidget', type: 'boolean')]
    private $sponsor_page_use_banner_widget;

    /**
     * @var int
     */
    #[ORM\Column(name: '`CustomOrder`', type: 'integer')]
    private $order;

    /**
     * @var SponsorshipType
     */
    #[ORM\JoinColumn(name: 'SponsorshipTypeID', referencedColumnName: 'ID', onDelete: 'SET NULL')]
    #[ORM\ManyToOne(targetEntity: \models\summit\SponsorshipType::class)]
    private $type;

    /**
     * @var File
     */
    #[ORM\JoinColumn(name: 'BadgeImageID', referencedColumnName: 'ID')]
    #[ORM\ManyToOne(targetEntity: \models\main\File::class, cascade: ['persist'])]
    private $badge_image;

    /**
     * @var string
     */
    #[ORM\Column(name: 'BadgeImageAltText', type: 'string')]
    private $badge_image_alt_text;

    #[ORM\Column(name: 'IsPublic', type: 'boolean')]
    private $is_public;

    public function __construct()
    {
        $this->order = 1;
        $this->sponsor_page_use_disqus_widget = true;
        $this->sponsor_page_use_live_event_widget = true;
        $this->sponsor_page_use_schedule_widget = true;
        $this->sponsor_page_use_banner_widget = true;
        $this->should_display_on_expo_hall_page = true;
        $this->should_display_on_lobby_page = true;
        $this->is_public = false;
    }

    /**
     * @return int
     */
    public function getOrder(): int
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
     * @return string
     */
    public function getWidgetTitle(): ?string
    {
        return $this->widget_title;
    }

    /**
     * @param string $widget_title
     */
    public function setWidgetTitle(string $widget_title): void
    {
        $this->widget_title = $widget_title;
    }

    /**
     * @return string
     */
    public function getLobbyTemplate(): ?string
    {
        return $this->lobby_template;
    }

    /**
     * @param string $lobby_template
     * @throws ValidationException
     */
    public function setLobbyTemplate(?string $lobby_template): void
    {
        if(empty($lobby_template))
        {
            $this->lobby_template = null;
            return;
        }
        if(!in_array($lobby_template, self::ValidLobbyTemplates))
            throw new ValidationException(sprintf("%s is not a valid lobby template.", $lobby_template));

        $this->lobby_template = $lobby_template;
    }

    /**
     * @return string
     */
    public function getExpoHallTemplate(): ?string
    {
        return $this->expo_hall_template;
    }

    /**
     * @param string $expo_hall_template
     */
    public function setExpoHallTemplate(?string $expo_hall_template): void
    {
        if(empty($expo_hall_template)){
            $this->expo_hall_template = null;
            return;
        }

        if(!in_array($expo_hall_template, self::ValidExpoHallTemplates))
            throw new ValidationException(sprintf("%s is not a valid expo hall template.", $expo_hall_template));

        $this->expo_hall_template = $expo_hall_template;
    }

    /**
     * @return string
     */
    public function getSponsorPageTemplate(): ?string
    {
        return $this->sponsor_page_template;
    }

    /**
     * @param string $sponsor_page_template
     */
    public function setSponsorPageTemplate(?string $sponsor_page_template): void
    {
        if(empty($sponsor_page_template)){
            $this->sponsor_page_template = null;
            return;
        }

        if(!in_array($sponsor_page_template, self::ValidSponsorPageTemplates))
            throw new ValidationException(sprintf("%s is not a valid sponsor template.", $sponsor_page_template));

        $this->sponsor_page_template = $sponsor_page_template;
    }

    /**
     * @return string
     */
    public function getEventPageTemplate(): ?string
    {
        return $this->event_page_template;
    }

    /**
     * @param string $event_page_template
     */
    public function setEventPageTemplate(?string $event_page_template): void
    {
        if(empty($event_page_template)){
            $this->event_page_template = null;
            return;
        }

        if(!in_array($event_page_template, self::ValidEventPageTemplates))
            throw new ValidationException(sprintf("%s is not a valid event page template.", $event_page_template));

        $this->event_page_template = $event_page_template;
    }

    /**
     * @return bool
     */
    public function isSponsorPageUseDisqusWidget(): bool
    {
        return $this->sponsor_page_use_disqus_widget;
    }

    /**
     * @param bool $sponsor_page_use_disqus_widget
     */
    public function setSponsorPageUseDisqusWidget(bool $sponsor_page_use_disqus_widget): void
    {
        $this->sponsor_page_use_disqus_widget = $sponsor_page_use_disqus_widget;
    }

    /**
     * @return bool
     */
    public function isSponsorPageUseLiveEventWidget(): bool
    {
        return $this->sponsor_page_use_live_event_widget;
    }

    /**
     * @param bool $sponsor_page_use_live_event_widget
     */
    public function setSponsorPageUseLiveEventWidget(bool $sponsor_page_use_live_event_widget): void
    {
        $this->sponsor_page_use_live_event_widget = $sponsor_page_use_live_event_widget;
    }

    /**
     * @return bool
     */
    public function isSponsorPageUseScheduleWidget(): bool
    {
        return $this->sponsor_page_use_schedule_widget;
    }

    /**
     * @param bool $sponsor_page_use_schedule_widget
     */
    public function setSponsorPageUseScheduleWidget(bool $sponsor_page_use_schedule_widget): void
    {
        $this->sponsor_page_use_schedule_widget = $sponsor_page_use_schedule_widget;
    }

    /**
     * @return bool
     */
    public function isSponsorPageUseBannerWidget(): bool
    {
        return $this->sponsor_page_use_banner_widget;
    }

    /**
     * @param bool $sponsor_page_use_banner_widget
     */
    public function setSponsorPageUseBannerWidget(bool $sponsor_page_use_banner_widget): void
    {
        $this->sponsor_page_use_banner_widget = $sponsor_page_use_banner_widget;
    }

    /**
     * @return SponsorshipType
     */
    public function getType(): SponsorshipType
    {
        return $this->type;
    }

    /**
     * @param SponsorshipType $type
     */
    public function setType(SponsorshipType $type): void
    {
        $this->type = $type;
    }

    /**
     * @return File
     */
    public function getBadgeImage(): ?File
    {
        return $this->badge_image;
    }

    /**
     * @return string|null
     */
    public function getBadgeImageUrl(): ?string
    {
        if ($this->hasBadgeImage())
            return $this->badge_image->getUrl();
        return null;
    }

    public function ClearBadgeImage():void{
        $this->badge_image = null;
    }

    /**
     * @param File $badge_image
     */
    public function setBadgeImage(File $badge_image): void
    {
        $this->badge_image = $badge_image;
    }

    /**
     * @return string
     */
    public function getBadgeImageAltText(): ?string
    {
        return $this->badge_image_alt_text;
    }

    /**
     * @param string $badge_image_alt_text
     */
    public function setBadgeImageAltText(string $badge_image_alt_text): void
    {
        $this->badge_image_alt_text = $badge_image_alt_text;
    }

    /**
     * @return bool
     */
    public function isShouldDisplayOnExpoHallPage(): bool
    {
        return $this->should_display_on_expo_hall_page;
    }

    /**
     * @param bool $should_display_on_expo_hall_page
     */
    public function setShouldDisplayOnExpoHallPage(bool $should_display_on_expo_hall_page): void
    {
        $this->should_display_on_expo_hall_page = $should_display_on_expo_hall_page;
    }

    /**
     * @return bool
     */
    public function isShouldDisplayOnLobbyPage(): bool
    {
        return $this->should_display_on_lobby_page;
    }

    /**
     * @param bool $should_display_on_lobby_page
     */
    public function setShouldDisplayOnLobbyPage(bool $should_display_on_lobby_page): void
    {
        $this->should_display_on_lobby_page = $should_display_on_lobby_page;
    }

}