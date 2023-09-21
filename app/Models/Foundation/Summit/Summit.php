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

use App\Jobs\CreateMUXPlaybackRestrictionForSummit;
use App\Models\Foundation\Main\IGroup;
use App\Models\Foundation\Main\OrderableChilds;
use App\Models\Foundation\Summit\EmailFlows\SummitEmailEventFlowType;
use App\Models\Foundation\Summit\Events\RSVP\RSVPTemplate;
use App\Models\Foundation\Summit\ExtraQuestions\SummitSelectionPlanExtraQuestionType;
use App\Models\Foundation\Summit\ISummitExternalScheduleFeedType;
use App\Models\Foundation\Summit\ISummitModality;
use App\Models\Foundation\Summit\Registration\IBuildDefaultPaymentGatewayProfileStrategy;
use App\Models\Foundation\Summit\Registration\ISummitExternalRegistrationFeedType;
use App\Models\Foundation\Summit\Registration\SummitRegistrationFeedMetadata;
use App\Models\Foundation\Summit\ScheduleEntity;
use App\Models\Foundation\Summit\SelectionPlan;
use App\Models\Foundation\Summit\Signs\SummitSign;
use App\Models\Foundation\Summit\Speakers\FeaturedSpeaker;
use App\Models\Foundation\Summit\TrackTagGroup;
use App\Models\Foundation\Summit\TrackTagGroupAllowedTag;
use App\Models\Utils\GetDefaultValueFromConfig;
use App\Models\Utils\TimeZoneEntity;
use App\Services\Apis\IPaymentGatewayAPI;
use Cocur\Slugify\Slugify;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use models\exceptions\ValidationException;
use models\main\Company;
use models\main\File;
use models\main\Member;
use models\main\PersonalCalendarShareInfo;
use models\main\SummitAdministratorPermissionGroup;
use models\main\Tag;
use models\utils\SilverstripeBaseModel;
use App\Models\Foundation\Summit\EmailFlows\SummitEmailEventFlow;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repositories\Summit\DoctrineSummitRepository")
 * @ORM\Table(name="Summit")
 * @ORM\HasLifecycleCallbacks
 * Class Summit
 * @package models\summit
 */
class Summit extends SilverstripeBaseModel
{

    use TimeZoneEntity;

    use GetDefaultValueFromConfig;

    /**
     * @ORM\Column(name="Title", type="string")
     * @var string
     */
    private $name;

    /**
     * @ORM\Column(name="TimeZoneLabel", type="string")
     * @var string
     */
    private $time_zone_label;

    /**
     * @ORM\Column(name="DateLabel", type="string")
     * @var string
     */
    private $dates_label;

    /**
     * @ORM\Column(name="Link", type="string")
     * @var string
     */
    private $link;

    /**
     * @ORM\Column(name="Slug", type="string")
     * @var string
     */
    private $slug;

    /**
     * @ORM\Column(name="RegistrationLink", type="string")
     * @var string
     */
    private $registration_link;

    /**
     * @ORM\Column(name="MaxSubmissionAllowedPerUser", type="integer")
     * @var int
     */
    private $max_submission_allowed_per_user;

    /**
     * @ORM\Column(name="SummitBeginDate", type="datetime")
     * @var \DateTime
     */
    private $begin_date;

    /**
     * @ORM\Column(name="SummitEndDate", type="datetime")
     * @var \DateTime
     */
    private $end_date;

    /**
     * @ORM\Column(name="ReAssignTicketTillDate", type="datetime")
     * @var \DateTime
     */
    private $reassign_ticket_till_date;

    /**
     * @ORM\Column(name="RegistrationAllowedRefundRequestTillDate", type="datetime")
     * @var \DateTime
     */
    private $registration_allowed_refund_request_till_date;

    /**
     * @ORM\Column(name="RegistrationDisclaimerContent", type="string")
     * @var string
     */
    private $registration_disclaimer_content;

    /**
     * @ORM\Column(name="RegistrationDisclaimerMandatory", type="boolean")
     * @var bool
     */
    private $registration_disclaimer_mandatory;

    /**
     * @ORM\Column(name="RegistrationSendQRAsImageAttachmentOnTicketEmail", type="boolean")
     * @var bool
     */
    private $registration_send_qr_as_image_attachment_on_ticket_email;

    /**
     * @ORM\Column(name="RegistrationSendTicketAsPDFAttachmentOnTicketEmail", type="boolean")
     * @var bool
     */
    private $registration_send_ticket_as_pdf_attachment_on_ticket_email;

    /**
     * @ORM\Column(name="RegistrationSendTicketEmailAutomatically", type="boolean")
     * @var bool
     */
    private $registration_send_ticket_email_automatically;

    /**
     * @ORM\Column(name="RegistrationSendOrderEmailAutomatically", type="boolean")
     * @var bool
     */
    private $registration_send_order_email_automatically;

    /**
     * @ORM\Column(name="RegistrationBeginDate", type="datetime")
     * @var \DateTime
     */
    private $registration_begin_date;

    /**
     * @ORM\Column(name="RegistrationEndDate", type="datetime")
     * @var \DateTime
     */
    private $registration_end_date;

    /**
     * @ORM\Column(name="Active", type="boolean")
     * @var bool
     */
    private $active;

    /**
     * @ORM\Column(name="AvailableOnApi", type="boolean")
     * @var bool
     */
    private $available_on_api;

    /**
     * @ORM\Column(name="ExternalEventId", type="string")
     * @var string
     */
    private $external_summit_id;

    /**
     * @ORM\Column(name="ScheduleDefaultStartDate", type="datetime")
     * @var \DateTime
     */
    private $schedule_default_start_date;

    /**
     * @ORM\ManyToOne(targetEntity="SummitType")
     * @ORM\JoinColumn(name="TypeID", referencedColumnName="ID")
     * @var SummitType
     */
    private $type;

    /**
     * @ORM\Column(name="StartShowingVenuesDate", type="datetime")
     */
    private $start_showing_venues_date;

    /**
     * @ORM\Column(name="TimeZoneIdentifier", type="string")
     * @var string
     */
    private $time_zone_id;

    /**
     * @ORM\Column(name="SecondaryRegistrationLink", type="string")
     * @var string
     */
    private $secondary_registration_link;

    /**
     * @ORM\Column(name="SecondaryRegistrationBtnText", type="string")
     * @var string
     */
    private $secondary_registration_label;

    /**
     * @ORM\Column(name="CalendarSyncName", type="string")
     * @var string
     */
    private $calendar_sync_name;

    /**
     * @ORM\Column(name="CalendarSyncDescription", type="string")
     * @var string
     */
    private $calendar_sync_desc;

    /**
     * @ORM\Column(name="MeetingRoomBookingStartTime", type="time", nullable=true)
     * @var DateTime
     */
    private $meeting_room_booking_start_time;

    /**
     * @ORM\Column(name="MeetingRoomBookingEndTime", type="time", nullable=true)
     * @var DateTime
     */
    private $meeting_room_booking_end_time;

    /**
     * @ORM\Column(name="MeetingRoomBookingSlotLength", type="integer", nullable=true)
     * @var int
     */
    private $meeting_room_booking_slot_length;

    /**
     * @ORM\Column(name="MeetingRoomBookingMaxAllowed", type="integer", nullable=true)
     * @var int
     */
    private $meeting_room_booking_max_allowed;

    /**
     * @ORM\Column(name="RegistrationReminderEmailsDaysInterval", type="integer", nullable=true)
     * @var int
     */
    private $registration_reminder_email_days_interval;

    /**
     * @ORM\Column(name="DefaultPageUrl", type="string", nullable=true)
     * @var string
     */
    private $default_page_url;

    /**
     * @ORM\Column(name="SpeakerConfirmationDefaultPageUrl", type="string", nullable=true)
     * @var string
     */
    private $speaker_confirmation_default_page_url;

    // schedule app

    /**
     * @ORM\Column(name="ScheduleDefaultPageUrl", type="string", nullable=true)
     * @var string
     */
    private $schedule_default_page_url;

    /**
     * @ORM\Column(name="ScheduleDefaultEventDetailUrl", type="string", nullable=true)
     * @var string
     */
    private $schedule_default_event_detail_url;

    /**
     * @ORM\Column(name="ScheduleOGSiteName", type="string", nullable=true)
     * @var string
     */
    private $schedule_og_site_name;

    /**
     * @ORM\Column(name="ScheduleOGImageUrl", type="string", nullable=true)
     * @var string
     */
    private $schedule_og_image_url;

    /**
     * @ORM\Column(name="ScheduleOGImageSecureUrl", type="string", nullable=true)
     * @var string
     */
    private $schedule_og_image_secure_url;

    /**
     * @ORM\Column(name="ScheduleOGImageWidth", type="integer", nullable=true)
     * @var int
     */
    private $schedule_og_image_width;

    /**
     * @ORM\Column(name="ScheduleOGImageHeight", type="integer", nullable=true)
     * @var int
     */
    private $schedule_og_image_height;

    /**
     * @ORM\Column(name="ScheduleFacebookAppId", type="string", nullable=true)
     * @var string
     */
    private $schedule_facebook_app_id;

    /**
     * @ORM\Column(name="ScheduleIOSAppName", type="string", nullable=true)
     * @var string
     */
    private $schedule_ios_app_name;

    /**
     * @ORM\Column(name="ScheduleIOSAppStoreId", type="string", nullable=true)
     * @var string
     */
    private $schedule_ios_app_store_id;

    /**
     * @ORM\Column(name="ScheduleIOSAppCustomSchema", type="string", nullable=true)
     * @var string
     */
    private $schedule_ios_app_custom_schema;

    /**
     * @ORM\Column(name="ScheduleAndroidAppName", type="string", nullable=true)
     * @var string
     */
    private $schedule_android_app_name;

    /**
     * @ORM\Column(name="ScheduleAndroidAppPackage", type="string", nullable=true)
     * @var string
     */
    private $schedule_android_app_package;

    /**
     * @ORM\Column(name="ScheduleAndroidAppCustomSchema", type="string", nullable=true)
     * @var string
     */
    private $schedule_android_custom_schema;

    /**
     * @ORM\Column(name="ScheduleTwitterAppName", type="string", nullable=true)
     * @var string
     */
    private $schedule_twitter_app_name;

    /**
     * @ORM\Column(name="ScheduleTwitterText", type="string", nullable=true)
     * @var string
     */
    private $schedule_twitter_text;

    /**
     * @ORM\OneToMany(targetEntity="SummitAbstractLocation", mappedBy="summit", cascade={"persist","remove"}, orphanRemoval=true)
     */
    private $locations;

    /**
     * @ORM\OneToMany(targetEntity="App\Models\Foundation\Summit\Signs\SummitSign", mappedBy="summit", cascade={"persist","remove"}, orphanRemoval=true)
     */
    private $signs;

    /**
     * @ORM\OneToMany(targetEntity="SummitBookableVenueRoomAttributeType", mappedBy="summit", cascade={"persist","remove"}, orphanRemoval=true)
     */
    private $meeting_booking_room_allowed_attributes;

    /**
     * @ORM\Column(name="BeginAllowBookingDate", type="datetime")
     * @var \DateTime
     */
    private $begin_allow_booking_date;

    /**
     * @ORM\Column(name="EndAllowBookingDate", type="datetime")
     * @var \DateTime
     */
    private $end_allow_booking_date;

    /**
     * @ORM\Column(name="RegistrationSlugPrefix", type="string")
     * @var string
     */
    private $registration_slug_prefix;

    /**
     * @ORM\Column(name="VirtualSiteUrl", type="string")
     * @var string
     */
    private $virtual_site_url;

    /**
     * @ORM\Column(name="VirtualSiteOAuth2ClientId", type="string")
     * @var string
     */
    private $virtual_site_oauth2_client_id;

    /**
     * @ORM\Column(name="MarketingSiteUrl", type="string")
     * @var string
     */
    private $marketing_site_url;

    /**
     * @ORM\Column(name="MarketingSiteOAuth2ClientId", type="string")
     * @var string
     */
    private $marketing_site_oauth2_client_id;

    /**
     * @ORM\Column(name="MarketingSiteOAuth2ClientScopes", type="string")
     * @var string
     */
    private $marketing_site_oauth2_client_scopes;

    /**
     * @return string
     */
    public function getMarketingSiteOauth2ClientScopes(): ?string
    {
        return $this->marketing_site_oauth2_client_scopes;
    }

    /**
     * @param string $marketing_site_oauth2_client_scopes
     */
    public function setMarketingSiteOauth2ClientScopes(string $marketing_site_oauth2_client_scopes): void
    {
        $this->marketing_site_oauth2_client_scopes = $marketing_site_oauth2_client_scopes;
    }

    /**
     * @ORM\Column(name="SupportEmail", type="string")
     * @var string
     */
    private $support_email;

    /**
     * @ORM\Column(name="RegistrationAllowUpdateAttendeeExtraQuestions", type="boolean")
     * @var bool
     */
    private $allow_update_attendee_extra_questions;

    /**
     * @ORM\Column(name="RegistrationAllowAutomaticReminderEmails", type="boolean")
     * @var bool
     */
    private $registration_allow_automatic_reminder_emails;

    /**
     * @ORM\OneToMany(targetEntity="SummitEvent", mappedBy="summit", cascade={"persist","remove"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     */
    private $events;

    /**
     * @ORM\OneToMany(targetEntity="App\Models\Foundation\Summit\Events\RSVP\RSVPTemplate", mappedBy="summit", cascade={"persist","remove"}, orphanRemoval=true)
     */
    private $rsvp_templates;

    /**
     * @ORM\OneToMany(targetEntity="SummitWIFIConnection", mappedBy="summit", cascade={"persist","remove"}, orphanRemoval=true)
     * @var SummitWIFIConnection[]
     */
    private $wifi_connections;

    /**
     * @ORM\OneToMany(targetEntity="App\Models\Foundation\Summit\TrackTagGroup", mappedBy="summit", cascade={"persist","remove"}, orphanRemoval=true)
     * @var TrackTagGroup[]
     */
    private $track_tag_groups;

    /**
     * @ORM\OneToMany(targetEntity="SummitRegistrationPromoCode", mappedBy="summit", cascade={"persist","remove"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @var SummitRegistrationPromoCode[]
     */
    private $promo_codes;

    /**
     * @ORM\OneToMany(targetEntity="PresentationSpeakerSummitAssistanceConfirmationRequest", mappedBy="summit", cascade={"persist","remove"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @var PresentationSpeakerSummitAssistanceConfirmationRequest[]
     */
    private $speaker_assistances;

    /**
     * @ORM\OneToMany(targetEntity="SummitAccessLevelType", mappedBy="summit", cascade={"persist","remove"}, orphanRemoval=true)
     * @var SummitAccessLevelType[]
     */
    private $badge_access_level_types;

    /**
     * @ORM\OneToMany(targetEntity="SummitBadgeFeatureType", mappedBy="summit", cascade={"persist","remove"}, orphanRemoval=true)
     * @var SummitBadgeFeatureType[]
     */
    private $badge_features_types;

    /**
     * @ORM\OneToMany(targetEntity="SummitBadgeType", mappedBy="summit", cascade={"persist","remove"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @var SummitBadgeType[]
     */
    private $badge_types;

    /**
     * @ORM\OneToMany(targetEntity="SummitBadgeViewType", mappedBy="summit", cascade={"persist","remove"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @var SummitBadgeViewType[]
     */
    private $badge_view_types;

    /**
     * @ORM\OneToMany(targetEntity="SummitOrder", mappedBy="summit", cascade={"persist","remove"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @var SummitOrder[]
     */
    private $orders;

    /**
     * @ORM\OneToMany(targetEntity="Sponsor", mappedBy="summit", cascade={"persist","remove"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @var Sponsor[]
     */
    private $summit_sponsors;

    /**
     * @ORM\OneToMany(targetEntity="SummitSponsorshipType", mappedBy="summit", cascade={"persist","remove"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @var SummitSponsorshipType[]
     */
    private $sponsorship_types;

    /**
     * @ORM\OneToMany(targetEntity="SummitTaxType", mappedBy="summit", cascade={"persist","remove"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @var SummitTaxType[]
     */
    private $tax_types;

    /**
     * @ORM\OneToMany(targetEntity="PaymentGatewayProfile", mappedBy="summit", cascade={"persist","remove"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @var PaymentGatewayProfile[]
     */
    private $payment_profiles;

    /**
     * @ORM\OneToMany(targetEntity="SummitOrderExtraQuestionType", mappedBy="summit", cascade={"persist","remove"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @var SummitOrderExtraQuestionType[]
     */
    private $order_extra_questions;

    /**
     * @ORM\OneToMany(targetEntity="SummitRefundPolicyType", mappedBy="summit", cascade={"persist","remove"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @var SummitRefundPolicyType[]
     */
    private $refund_policies;

    /**
     * @ORM\OneToMany(targetEntity="SummitMediaUploadType", mappedBy="summit", cascade={"persist","remove"}, orphanRemoval=true)
     * @var SummitMediaUploadType[]
     */
    private $media_upload_types;

    /**
     * @ORM\OneToMany(targetEntity="SummitScheduleConfig", mappedBy="summit", cascade={"persist","remove"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @var SummitScheduleConfig[]
     */
    private $schedule_settings;

    /**
     * @ORM\ManyToOne(targetEntity="models\main\File", cascade={"persist"})
     * @ORM\JoinColumn(name="LogoID", referencedColumnName="ID")
     * @var File
     */
    private $logo;

    /**
     * @ORM\Column(name="ApiFeedType", type="string")
     * @var string
     */
    private $api_feed_type;

    /**
     * @ORM\Column(name="ApiFeedUrl", type="string")
     * @var string
     */
    private $api_feed_url;

    /**
     * @ORM\Column(name="ApiFeedKey", type="string")
     * @var string
     */
    private $api_feed_key;

    /**
     * @ORM\Column(name="ExternalRegistrationFeedType", type="string")
     * @var string
     */
    private $external_registration_feed_type;

    /**
     * @ORM\Column(name="ExternalRegistrationFeedApiKey", type="string")
     * @var string
     */
    private $external_registration_feed_api_key;

    /**
     * @ORM\Column(name="ExternalRegistrationFeedLastIngestDate", type="datetime")
     * @var DateTime
     */
    private $external_registration_feed_last_ingest_date;

    /**
     * @ORM\Column(name="QRCodesEncKey", type="string")
     * @var string
     */
    private $qr_codes_enc_key;

    /**
     * @ORM\Column(name="MUXTokenId", type="string")
     * @var string
     */
    private $mux_token_id;

    /**
     * @ORM\Column(name="MUXTokenSecret", type="string")
     * @var string
     */
    private $mux_token_secret;

    /**
     * @ORM\Column(name="MUXPrivateKeyId", type="string")
     * @var string
     */
    private $mux_private_key_id;

    /**
     * @ORM\Column(name="MUXPrivateKey", type="string")
     * @var string
     */
    private $mux_private_key;

    /**
     * @ORM\Column(name="MUXPlaybackRestrictionId", type="string")
     * @var string
     */
    private $mux_playback_restriction_id;

    /**
     * @ORM\Column(name="MUXAllowedDomains", type="string")
     * @var string
     */
    private $mux_allowed_domains;

    /**
     * @ORM\OneToMany(targetEntity="models\summit\SummitEventType", mappedBy="summit",  cascade={"persist","remove"}, orphanRemoval=true)
     */
    private $event_types;

    /**
     * @ORM\OneToMany(targetEntity="models\summit\PresentationCategory", mappedBy="summit", cascade={"persist","remove"}, orphanRemoval=true)
     * @var PresentationCategory[]
     */
    private $presentation_categories;

    /**
     * @ORM\OneToMany(targetEntity="models\summit\SummitAttendee", mappedBy="summit", cascade={"persist","remove"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @var SummitAttendee[]
     */
    private $attendees;

    /**
     * @ORM\OneToMany(targetEntity="App\Models\Foundation\Summit\SelectionPlan", mappedBy="summit", cascade={"persist","remove"}, orphanRemoval=true)
     * @var SelectionPlan[]
     */
    private $selection_plans;

    /**
     * @ORM\OneToMany(targetEntity="models\summit\PresentationCategoryGroup", mappedBy="summit", cascade={"persist","remove"}, orphanRemoval=true)
     * @var PresentationCategoryGroup[]
     */
    private $category_groups;

    /**
     * @ORM\OneToMany(targetEntity="models\summit\SummitTicketType", mappedBy="summit", cascade={"persist","remove"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * var SummitTicketType[]
     */
    private $ticket_types;

    /**
     * @ORM\OneToMany(targetEntity="models\summit\SummitDocument", mappedBy="summit",  cascade={"persist","remove"}, orphanRemoval=true)
     */
    private $summit_documents;

    /**
     * @ORM\ManyToMany(targetEntity="models\summit\PresentationCategory", cascade={"persist"})
     * @ORM\JoinTable(name="Summit_ExcludedCategoriesForAcceptedPresentations",
     *      joinColumns={@ORM\JoinColumn(name="SummitID", referencedColumnName="ID")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="PresentationCategoryID", referencedColumnName="ID")}
     * )
     * @var PresentationCategory[]
     */
    private $excluded_categories_for_accepted_presentations;

    /**
     * @ORM\OneToMany(targetEntity="App\Models\Foundation\Summit\Speakers\FeaturedSpeaker", mappedBy="summit", cascade={"persist","remove"}, orphanRemoval=true)
     * @var FeaturedSpeaker[]
     */
    private $featured_speakers;

    /**
     * @ORM\ManyToMany(targetEntity="models\summit\PresentationCategory")
     * @ORM\JoinTable(name="Summit_ExcludedCategoriesForAlternatePresentations",
     *      joinColumns={@ORM\JoinColumn(name="SummitID", referencedColumnName="ID")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="PresentationCategoryID", referencedColumnName="ID")}
     * )
     * @var PresentationCategory[]
     */
    private $excluded_categories_for_alternate_presentations;

    /**
     * @ORM\ManyToMany(targetEntity="models\summit\PresentationCategory")
     * @ORM\JoinTable(name="Summit_ExcludedCategoriesForRejectedPresentations",
     *      joinColumns={@ORM\JoinColumn(name="SummitID", referencedColumnName="ID")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="PresentationCategoryID", referencedColumnName="ID")}
     * )
     * @var PresentationCategory[]
     */
    private $excluded_categories_for_rejected_presentations;

    /**
     * @ORM\ManyToMany(targetEntity="models\summit\PresentationCategory")
     * @ORM\JoinTable(name="Summit_ExcludedTracksForUploadPresentationSlideDeck",
     *      joinColumns={@ORM\JoinColumn(name="SummitID", referencedColumnName="ID")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="PresentationCategoryID", referencedColumnName="ID")}
     * )
     * @var PresentationCategory[]
     */
    private $excluded_categories_for_upload_slide_decks;

    /**
     * @ORM\OneToMany(targetEntity="models\main\PersonalCalendarShareInfo", mappedBy="summit", cascade={"persist"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @var PersonalCalendarShareInfo[]
     */
    private $schedule_shareable_links;

    /**
     * @ORM\OneToMany(targetEntity="App\Models\Foundation\Summit\EmailFlows\SummitEmailEventFlow", mappedBy="summit", cascade={"persist","remove"}, orphanRemoval=true)
     * @var SummitEmailEventFlow[]
     */
    private $email_flows_events;

    /**
     * @ORM\OneToMany(targetEntity="models\summit\SummitRegistrationInvitation", mappedBy="summit", cascade={"persist","remove"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @var SummitRegistrationInvitation[]
     */
    private $registration_invitations;

    /**
     * @ORM\OneToMany(targetEntity="models\summit\SummitSubmissionInvitation", mappedBy="summit", cascade={"persist","remove"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @var SummitSubmissionInvitation[]
     */
    private $submission_invitations;

    /**
     * @ORM\ManyToMany(targetEntity="models\main\SummitAdministratorPermissionGroup",  mappedBy="summits"))
     * @var SummitAdministratorPermissionGroup[]
     */
    private $permission_groups;

    /**
     * @ORM\OneToMany(targetEntity="models\summit\SummitMetric", mappedBy="summit", cascade={"persist","remove"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @var SummitMetric[]
     */
    private $metrics;

    /**
     * @ORM\OneToMany(targetEntity="models\summit\SummitTrackChair", mappedBy="summit", cascade={"persist","remove"}, orphanRemoval=true)
     * @var SummitTrackChair[]
     */
    private $track_chairs;

    /**
     * @ORM\OneToMany(targetEntity="models\summit\PresentationActionType", mappedBy="summit", cascade={"persist","remove"}, orphanRemoval=true)
     * @var PresentationActionType[]
     */
    private $presentation_action_types;

    /**
     * @ORM\ManyToMany(targetEntity="models\main\Company", fetch="EXTRA_LAZY")
     * @ORM\JoinTable(name="Summit_RegistrationCompanies",
     *      joinColumns={@ORM\JoinColumn(name="SummitID", referencedColumnName="ID")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="CompanyID", referencedColumnName="ID")}
     * )
     * @var Company[]
     */
    private $registration_companies;

    /**
     * @ORM\OneToMany(targetEntity="models\summit\SpeakerAnnouncementSummitEmail", mappedBy="summit", cascade={"persist","remove"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @var SpeakerAnnouncementSummitEmail[]
     */
    private $speakers_announcement_emails;

    /**
     * @ORM\OneToMany(targetEntity="App\Models\Foundation\Summit\ExtraQuestions\SummitSelectionPlanExtraQuestionType", mappedBy="summit", cascade={"persist","remove"}, orphanRemoval=true)
     * @var SummitSelectionPlanExtraQuestionType[]
     */
    private $selection_plan_extra_questions;

    /**
     * @ORM\OneToMany(targetEntity="App\Models\Foundation\Summit\Registration\SummitRegistrationFeedMetadata", mappedBy="summit", cascade={"persist","remove"}, orphanRemoval=true)
     * @var SummitRegistrationFeedMetadata[]
     */
    private $registration_feed_metadata;

    /**
     * @return string
     */
    public function getDatesLabel()
    {
        return $this->dates_label;
    }

    /**
     * @param string $dates_label
     */
    public function setDatesLabel($dates_label)
    {
        $this->dates_label = $dates_label;
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
     * @return mixed
     */
    public function getWifiConnections()
    {
        return $this->wifi_connections;
    }

    /**
     * @param mixed $wifi_connections
     */
    public function setWifiConnections($wifi_connections)
    {
        $this->wifi_connections = $wifi_connections;
    }

    /**
     * @return string
     */
    public function getExternalSummitId(): ?string
    {
        return $this->external_summit_id;
    }

    /**
     * @param string $external_summit_id
     */
    public function setExternalSummitId($external_summit_id)
    {
        $this->external_summit_id = $external_summit_id;
    }

    /**
     * @return \DateTime
     */
    public function getScheduleDefaultStartDate()
    {
        return $this->schedule_default_start_date;
    }

    /**
     * @param \DateTime $schedule_default_start_date
     */
    public function setScheduleDefaultStartDate($schedule_default_start_date)
    {
        $this->schedule_default_start_date = $this->convertDateFromTimeZone2UTC($schedule_default_start_date);
    }

    /**
     * @param \DateTime $schedule_default_start_date
     */
    public function setRawScheduleDefaultStartDate($schedule_default_start_date)
    {
        $this->schedule_default_start_date = $schedule_default_start_date;
    }

    public function clearScheduleDefaultStartDate()
    {
        $this->schedule_default_start_date = null;
    }

    /**
     * @return DateTime|null
     */
    public function getBeginDate(): ?DateTime
    {
        return $this->begin_date;
    }

    /**
     * @param \DateTime $begin_date
     */
    public function setBeginDate($begin_date)
    {
        $this->begin_date = $this->convertDateFromTimeZone2UTC($begin_date);
    }

    public function setRawBeginDate($begin_date)
    {
        $this->begin_date = $begin_date;
    }

    /**
     * @return $this
     */
    public function clearBeginEndDates()
    {
        $this->begin_date = $this->end_date = null;
        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getEndDate(): ?\DateTime
    {
        return $this->end_date;
    }

    /**
     * @param \DateTime $end_date
     */
    public function setEndDate($end_date)
    {
        $this->end_date = $this->convertDateFromTimeZone2UTC($end_date);
    }

    public function setRawEndDate($end_date)
    {
        $this->end_date = $end_date;
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
     * @return \DateTime
     */
    public function getStartShowingVenuesDate()
    {
        return $this->start_showing_venues_date;
    }

    /**
     * @param \DateTime $start_showing_venues_date
     */
    public function setStartShowingVenuesDate($start_showing_venues_date)
    {
        $this->start_showing_venues_date = $this->convertDateFromTimeZone2UTC($start_showing_venues_date);
    }

    /**
     * @param \DateTime $start_showing_venues_date
     */
    public function setRawStartShowingVenuesDate($start_showing_venues_date)
    {
        $this->start_showing_venues_date = $start_showing_venues_date;
    }

    public function clearStartShowingVenuesDate()
    {
        $this->start_showing_venues_date = null;
    }

    public function clearReassignTicketTillDate()
    {
        $this->reassign_ticket_till_date = null;
    }

    /**
     * @return boolean
     */
    public function isAvailableOnApi()
    {
        return $this->available_on_api;
    }

    /**
     * @param boolean $available_on_api
     */
    public function setAvailableOnApi($available_on_api)
    {
        $this->available_on_api = $available_on_api;
    }

    /**
     * @return SummitType
     */
    public function getType()
    {
        try {
            return $this->type;
        } catch (\Exception $ex) {
            return null;
        }
    }

    /**
     * @param SummitType $type
     */
    public function setType($type)
    {
        $this->type = $type;
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
     * @return bool
     */
    public function hasType()
    {
        return $this->getTypeId() > 0;
    }

    /**
     * @return string
     */
    public function getSummitExternalId()
    {
        return $this->external_summit_id;
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->active;
    }


    const DefaultMaxSubmissionAllowedPerUser = 3;

    /**
     * Summit constructor.
     */
    public function __construct()
    {
        parent::__construct();
        // default values
        $this->active = false;
        $this->available_on_api = false;
        $this->max_submission_allowed_per_user = self::DefaultMaxSubmissionAllowedPerUser;
        $this->meeting_room_booking_slot_length = 60;
        $this->meeting_room_booking_max_allowed = 2;
        $this->locations = new ArrayCollection;
        $this->events = new ArrayCollection;
        $this->event_types = new ArrayCollection;
        $this->ticket_types = new ArrayCollection;
        $this->presentation_categories = new ArrayCollection;
        $this->category_groups = new ArrayCollection;
        $this->attendees = new ArrayCollection;
        $this->entity_events = new ArrayCollection;
        $this->wifi_connections = new ArrayCollection;
        $this->promo_codes = new ArrayCollection;
        $this->speaker_assistances = new ArrayCollection;
        $this->excluded_categories_for_accepted_presentations = new ArrayCollection;
        $this->excluded_categories_for_alternate_presentations = new ArrayCollection;
        $this->excluded_categories_for_rejected_presentations = new ArrayCollection;
        $this->excluded_categories_for_upload_slide_decks = new ArrayCollection;
        $this->rsvp_templates = new ArrayCollection;
        $this->track_tag_groups = new ArrayCollection;
        $this->notifications = new ArrayCollection;
        $this->selection_plans = new ArrayCollection;
        $this->meeting_booking_room_allowed_attributes = new ArrayCollection();
        $this->badge_access_level_types = new ArrayCollection();
        $this->tax_types = new ArrayCollection();
        $this->badge_features_types = new ArrayCollection();
        $this->badge_types = new ArrayCollection();
        $this->summit_sponsors = new ArrayCollection();
        $this->refund_policies = new ArrayCollection();
        $this->order_extra_questions = new ArrayCollection();
        $this->payment_profiles = new ArrayCollection();
        $this->orders = new ArrayCollection();
        $this->registration_disclaimer_mandatory = false;
        $this->reassign_ticket_till_date = null;
        $this->begin_date = null;
        $this->end_date = null;
        $this->registration_begin_date = null;
        $this->registration_end_date = null;
        $this->schedule_shareable_links = new ArrayCollection();
        $this->mark_as_deleted = false;
        $this->schedule_og_image_width = 0;
        $this->schedule_og_image_height = 0;
        $this->email_flows_events = new ArrayCollection();
        $this->summit_documents = new ArrayCollection();
        $this->registration_invitations = new ArrayCollection();
        $this->permission_groups = new ArrayCollection();
        $this->media_upload_types = new ArrayCollection();
        $this->featured_speakers = new ArrayCollection();
        $this->metrics = new ArrayCollection();
        $this->track_chairs = new ArrayCollection();
        $this->presentation_action_types = new ArrayCollection();
        $this->schedule_settings = new ArrayCollection();
        $this->registration_send_qr_as_image_attachment_on_ticket_email = false;
        $this->registration_send_ticket_as_pdf_attachment_on_ticket_email = false;
        $this->registration_send_ticket_email_automatically = true;
        $this->registration_allow_automatic_reminder_emails = true;
        $this->registration_send_order_email_automatically = true;
        $this->allow_update_attendee_extra_questions = false;
        $this->registration_companies = new ArrayCollection();
        $this->external_registration_feed_last_ingest_date = null;
        $this->speakers_announcement_emails = new ArrayCollection();
        $this->badge_view_types = new ArrayCollection();
        $this->registration_allowed_refund_request_till_date = null;
        $this->sponsorship_types = new ArrayCollection();
        $this->selection_plan_extra_questions = new ArrayCollection();
        $this->submission_invitations = new ArrayCollection();
        $this->signs = new ArrayCollection();
        $this->qr_codes_enc_key = null;
        $this->registration_feed_metadata = new ArrayCollection();
    }

    /**
     * @param int $assistance_id
     * @return PresentationSpeakerSummitAssistanceConfirmationRequest|null
     */
    public function getSpeakerAssistanceById($assistance_id)
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', intval($assistance_id)));
        $speaker_assistance = $this->speaker_assistances->matching($criteria)->first();
        return $speaker_assistance === false ? null : $speaker_assistance;
    }

    public function addSpeakerAssistance(PresentationSpeakerSummitAssistanceConfirmationRequest $assistanceConfirmationRequest)
    {
        if ($this->speaker_assistances->contains($assistanceConfirmationRequest)) return;
        $this->speaker_assistances->add($assistanceConfirmationRequest);
        $assistanceConfirmationRequest->setSummit($this);
    }

    /**
     * @return DateTime|null
     */
    public function getLocalBeginDate(): ?DateTime
    {
        return $this->convertDateFromUTC2TimeZone($this->begin_date);
    }


    /**
     * @return DateTime|null
     */
    public function getLocalBeginAllowBookingDate(): ?DateTime
    {
        if (is_null($this->begin_allow_booking_date)) return null;
        return $this->convertDateFromUTC2TimeZone($this->begin_allow_booking_date);
    }

    /**
     * @return DateTime|null
     */
    public function getLocalEndAllowBookingDate(): ?DateTime
    {
        if (is_null($this->end_allow_booking_date)) return null;
        return $this->convertDateFromUTC2TimeZone($this->end_allow_booking_date);
    }


    /**
     * @return DateTime
     */
    public function getLocalEndDate()
    {
        return $this->convertDateFromUTC2TimeZone($this->end_date);
    }

    /**
     * @param SummitAbstractLocation $location
     * @return $this
     */
    public function addLocation(SummitAbstractLocation $location)
    {
        if ($this->locations->contains($location)) return;
        $location->setOrder($this->getLocationMaxOrder() + 1);
        $this->locations->add($location);
        $location->setSummit($this);
        return $this;
    }

    /**
     * @return int
     */
    private function getLocationMaxOrder()
    {
        $criteria = Criteria::create();
        $criteria->orderBy(['order' => 'DESC']);
        $location = $this->locations->matching($criteria)->first();
        return $location === false ? 0 : $location->getOrder();
    }

    /**
     * @return ArrayCollection
     */
    public function getLocations()
    {
        return $this->locations;
    }

    /**
     * @return SummitVenue[]
     */
    public function getVenues()
    {
        return $this->locations->filter(function ($e) {
            return $e instanceof SummitVenue;
        });
    }

    /**
     * @return SummitVenue[]
     */
    public function getMainVenues()
    {
        return $this->locations->filter(function ($e) {
            return $e instanceof SummitVenue && $e->getIsMain();
        });
    }

    /**
     * @param string $name
     * @return SummitAbstractLocation|null
     */
    public function getLocationByName($name)
    {

        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('name', trim($name)));
        $location = $this->locations->matching($criteria)->first();
        return $location === false ? null : $location;
    }

    /**
     * @return SummitHotel[]
     */
    public function getHotels()
    {
        return $this->locations->filter(function ($e) {
            return $e instanceof SummitHotel;
        });
    }

    /**
     * @return SummitBookableVenueRoom[]
     */
    public function getBookableRooms()
    {
        return $this->locations->filter(function ($e) {
            return $e instanceof SummitBookableVenueRoom;
        });
    }


    /**
     * @return SummitAirport[]
     */
    public function getAirports()
    {
        return $this->locations->filter(function ($e) {
            return $e instanceof SummitAirport;
        });
    }

    /**
     * @return SummitExternalLocation[]
     */
    public function getExternalLocations()
    {
        return $this->locations->filter(function ($e) {
            return $e->getClassName() == 'SummitExternalLocation';
        });
    }

    /**
     * @param Summit $source_summit
     * @return $this
     */
    public function copyLocationsFrom(Summit $source_summit)
    {
        foreach($source_summit->getLocations() as $location) {
            $new_location = clone $location;
            $this->addLocation($new_location);
        }
    }

    /**
     * @return ArrayCollection
     */
    public function getEvents()
    {
        return $this->events;
    }

    /**
     * @param string $externalId
     * @return SummitEvent|null
     */
    public function getEventByExternalId(string $externalId): ?SummitEvent
    {

        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('external_id', trim($externalId)));
        $event = $this->events->matching($criteria)->first();
        return $event === false ? null : $event;
    }

    /**
     * @param int $id
     * @return SummitEvent|null
     */
    public function getEventById(int $id): ?SummitEvent
    {

        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', $id));
        $event = $this->events->matching($criteria)->first();
        return $event === false ? null : $event;
    }

    /**
     * @param SummitEvent $event
     */
    public function addEvent(SummitEvent $event)
    {
        if ($this->events->contains($event)) return;
        $this->events->add($event);
        $event->setSummit($this);
    }

    public function removeEvent(SummitEvent $event)
    {
        if (!$this->events->contains($event)) return;
        $this->events->removeElement($event);
    }

    /**
     * @return File
     */
    public function getLogo()
    {
        return $this->logo;
    }

    /**
     * @param File $logo
     */
    public function setLogo(File $logo): void
    {
        $this->logo = $logo;
    }

    public function clearLogo(): void
    {
        $this->logo = null;
    }

    /**
     * @return bool
     */
    public function hasLogo()
    {
        return $this->getLogoId() > 0;
    }

    /**
     * @return int
     */
    public function getLogoId()
    {
        try {
            return !is_null($this->logo) ? $this->logo->getId() : 0;
        } catch (\Exception $ex) {
            return 0;
        }
    }

    /**
     * @param int $location_id
     * @return SummitAbstractLocation
     */
    public function getLocation($location_id)
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', intval($location_id)));
        $location = $this->locations->matching($criteria)->first();
        return $location === false ? null : $location;
    }

    /**
     * @param SummitAbstractLocation $location
     * @return bool
     */
    static public function isPrimaryLocation(SummitAbstractLocation $location)
    {
        return ($location instanceof SummitVenue
            || $location instanceof SummitHotel
            || $location instanceof SummitAirport
            || $location instanceof SummitExternalLocation);
    }

    /**
     * @return ArrayCollection
     */
    public function getEventTypes()
    {
        return $this->event_types;
    }

    /**
     * @param int $event_type_id
     * @return SummitEventType|null
     */
    public function getEventType($event_type_id)
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', intval($event_type_id)));
        $event_type = $this->event_types->matching($criteria)->first();
        return $event_type === false ? null : $event_type;
    }

    /**
     * @param string $type
     * @return bool
     */
    public function hasEventType($type)
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('type', $type));
        return $this->event_types->matching($criteria)->count() > 0;
    }

    /**
     * @param string $type
     * @return SummitEventType|null
     */
    public function getEventTypeByType($type)
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('type', $type));
        $event_type = $this->event_types->matching($criteria)->first();
        return $event_type === false ? null : $event_type;
    }

    /**
     * @param int $wifi_connection_id
     * @return SummitWIFIConnection|null
     */
    public function getWifiConnection($wifi_connection_id)
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', intval($wifi_connection_id)));
        $wifi_conn = $this->wifi_connections->matching($criteria)->first();
        return $wifi_conn === false ? null : $wifi_conn;
    }

    /**
     * @return ArrayCollection|SummitTicketType[]
     */
    public function getTicketTypes()
    {
        return $this->ticket_types;
    }

    /**
     * @param string $ticket_type_external_id
     * @return SummitTicketType|null
     */
    public function getTicketTypeByExternalId(string $ticket_type_external_id): ?SummitTicketType
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('external_id', $ticket_type_external_id));
        $ticket_type = $this->ticket_types->matching($criteria)->first();
        return $ticket_type === false ? null : $ticket_type;
    }

    /**
     * @param string $ticket_type_audience
     * @return ArrayCollection|\Doctrine\Common\Collections\Collection
     */
    public function getTicketTypesByAudience(string $ticket_type_audience)
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('audience', $ticket_type_audience));
        return $this->ticket_types->matching($criteria);
    }

    /**
     * @param string $ticket_type_external_id
     * @return SummitOrderExtraQuestionType|null
     */
    public function getExtraQuestionTypeByExternalId(string $extra_question_type_external_id): ?SummitOrderExtraQuestionType
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('external_id', $extra_question_type_external_id));
        $question_type = $this->order_extra_questions->matching($criteria)->first();
        return $question_type === false ? null : $question_type;
    }

    /**
     * @param int $event_id
     * @return null|SummitEvent
     */
    public function getScheduleEvent($event_id)
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('published', 1));
        $criteria->andWhere(Criteria::expr()->eq('id', intval($event_id)));
        $event = $this->events->matching($criteria)->first();
        return $event === false ? null : $event;
    }

    /**
     * @param int $event_id
     * @return bool
     */
    public function isEventOnSchedule($event_id)
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('published', 1));
        $criteria->andWhere(Criteria::expr()->eq('id', intval($event_id)));
        return $this->events->matching($criteria)->count() > 0;
    }

    public function getScheduleEvents()
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('published', 1));
        $criteria->orderBy(["start_date" => Criteria::ASC, "end_date" => Criteria::ASC]);
        return $this->events->matching($criteria);
    }

    /**
     * @return Presentation[]
     */
    public function getPresentations()
    {
        $query = $this->createQuery("SELECT p from models\summit\Presentation p JOIN p.summit s WHERE s.id = :summit_id");
        return $query->setParameter('summit_id', $this->getIdentifier())->getResult();
    }

    public function getPublishedPresentations()
    {
        $query = $this->createQuery("SELECT p from models\summit\Presentation p JOIN p.summit s WHERE s.id = :summit_id and p.published = 1");
        return $query->setParameter('summit_id', $this->getIdentifier())->getResult();
    }

    public function getPublishedEvents()
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('published', 1));
        return $this->events->matching($criteria);
    }

    /**
     * @param PresentationSpeaker $speaker
     * @param SelectionPlan|null $selectionPlan
     * @return array
     */
    public function getModeratedPresentationsBy(PresentationSpeaker $speaker, SelectionPlan $selectionPlan = null)
    {
        $selection_plan_cond = "";
        if (!is_null($selectionPlan)) {
            $selection_plan_cond = " and sp.id = :selection_plan_id";
        }

        $query = $this->createQuery("SELECT p from models\summit\Presentation p 
        JOIN p.summit s
        JOIN p.moderator m 
        JOIN p.selection_plan sp
        WHERE s.id = :summit_id and m.id = :moderator_id" . $selection_plan_cond);

        $query = $query
            ->setParameter('summit_id', $this->getIdentifier())
            ->setParameter('moderator_id', $speaker->getIdentifier());

        if (!is_null($selectionPlan)) {
            $query = $query->setParameter('selection_plan_id', $selectionPlan->getIdentifier());
        }
        return $query->getResult();
    }

    /**
     * @param PresentationSpeaker $speaker
     * @param SelectionPlan|null $selectionPlan
     * @return array
     */
    public function getCreatedPresentations(PresentationSpeaker $speaker, SelectionPlan $selectionPlan = null)
    {
        $selection_plan_cond = "";

        if (!is_null($selectionPlan)) {
            $selection_plan_cond = " and sp.id = :selection_plan_id";
        }

        $query = $this->createQuery("SELECT p from models\summit\Presentation p 
        JOIN p.summit s
        JOIN p.created_by c 
        JOIN p.selection_plan sp
        WHERE s.id = :summit_id and c.id = :creator_id" . $selection_plan_cond);

        $query = $query
            ->setParameter('summit_id', $this->getIdentifier())
            ->setParameter('creator_id', $speaker->getMemberId());

        if (!is_null($selectionPlan)) {
            $query = $query->setParameter('selection_plan_id', $selectionPlan->getIdentifier());
        }

        return $query->getResult();
    }

    /**
     * @param int $event_id
     * @return null|SummitEvent
     */
    public function getEvent($event_id)
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', intval($event_id)));
        $event = $this->events->matching($criteria)->first();
        return $event === false ? null : $event;
    }


    /**
     * @return PresentationCategory[]
     */
    public function getPresentationCategories()
    {
        $criteria = Criteria::create();
        $criteria->orderBy(['order' => 'ASC']);
        return $this->presentation_categories->matching($criteria);
    }

    /**
     * @return int
     */
    private function getTrackMaxOrder(): int
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->isNull('parent'));
        $criteria->orderBy(['order' => 'DESC']);
        $track = $this->presentation_categories->matching($criteria)->first();
        return $track === false ? 0 : $track->getOrder();
    }

    /**
     * @param PresentationCategory $track
     * @param int $new_order
     * @throws ValidationException
     */
    public function recalculateTrackOrder(PresentationCategory $track, $new_order)
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->isNull('parent'));
        self::recalculateOrderForSelectable($this->presentation_categories->matching($criteria), $track, $new_order);
    }

    /**
     * @param PresentationCategory $track
     * @return $this
     */
    public function addPresentationCategory(PresentationCategory $track)
    {
        $this->presentation_categories->add($track);
        $track->setOrder($this->getTrackMaxOrder() + 1);

        $track->setSummit($this);
        return $this;
    }

    /**
     * @param int $category_id
     * @return PresentationCategory
     */
    public function getPresentationCategory($category_id)
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', intval($category_id)));
        $category = $this->presentation_categories->matching($criteria)->first();
        return $category === false ? null : $category;
    }

    /**
     * @param string $category_title
     * @return PresentationCategory
     */
    public function getPresentationCategoryByTitle($category_title)
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('title', trim($category_title)));
        $category = $this->presentation_categories->matching($criteria)->first();
        return $category === false ? null : $category;
    }

    /**
     * @param string $category_code
     * @return PresentationCategory
     */
    public function getPresentationCategoryByCode($category_code)
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('code', trim($category_code)));
        $category = $this->presentation_categories->matching($criteria)->first();
        return $category === false ? null : $category;
    }

    /**
     * @return PresentationCategoryGroup[]
     */
    public function getCategoryGroups()
    {
        return $this->category_groups;
    }

    /**
     * @param int $group_id
     * @return null|PresentationCategoryGroup
     */
    public function getCategoryGroupById($group_id)
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', intval($group_id)));
        $group = $this->category_groups->matching($criteria)->first();
        return $group === false ? null : $group;
    }

    /**
     * @param string $name
     * @return null|PresentationCategoryGroup
     */
    public function getCategoryGroupByName($name)
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('name', trim($name)));
        $group = $this->category_groups->matching($criteria)->first();
        return $group === false ? null : $group;
    }

    /**
     * @param PresentationCategoryGroup $track_group
     */
    public function addCategoryGroup(PresentationCategoryGroup $track_group)
    {
        if ($this->category_groups->contains($track_group)) return;
        $this->category_groups->add($track_group);
        $track_group->setSummit($this);
    }

    /**
     * @param PresentationCategoryGroup $track_group
     */
    public function removeCategoryGroup(PresentationCategoryGroup $track_group)
    {
        if (!$this->category_groups->contains($track_group)) return;
        $this->category_groups->removeElement($track_group);
        $track_group->clearSummit();
    }

    /**
     * @param int $member_id
     * @return SummitAttendee|null
     */
    public function getAttendeeByMemberId($member_id): ?SummitAttendee
    {
        $builder = $this->createQueryBuilder();
        $members = $builder
            ->select('a')
            ->from('models\summit\SummitAttendee', 'a')
            ->join('a.member', 'm')
            ->join('a.summit', 's')
            ->where('s.id = :summit_id and m.id = :member_id')
            ->setParameter('summit_id', $this->getId())
            ->setParameter('member_id', intval($member_id))
            ->getQuery()->getResult();
        return count($members) > 0 ? $members[0] : null;
    }

    /**
     * @param Member $member
     * @return SummitAttendee|null
     */
    public function getAttendeeByMember(Member $member): ?SummitAttendee
    {
        return $this->getAttendeeByMemberId($member->getId());
    }

    /**
     * @param SummitAttendee $attendee
     */
    public function addAttendee(SummitAttendee $attendee)
    {
        if ($this->attendees->contains($attendee)) return;
        $this->attendees->add($attendee);
        $attendee->setSummit($this);
    }

    /**
     * @param int $attendee_id
     * @return SummitAttendee|null
     */
    public function getAttendeeById($attendee_id): ?SummitAttendee
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', intval($attendee_id)));
        $attendee = $this->attendees->matching($criteria)->first();
        return $attendee === false ? null : $attendee;
    }

    /**
     * @param string $email
     * @return SummitAttendee|null
     */
    public function getAttendeeByEmail(string $email): ?SummitAttendee
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('email', strtolower(trim($email))));
        $attendee = $this->attendees->matching($criteria)->first();
        return $attendee === false ? null : $attendee;
    }


    /**
     * @ORM\OneToMany(targetEntity="models\summit\SummitEntityEvent", mappedBy="summit", cascade={"persist"}, orphanRemoval=true)
     * @var SummitEntityEvent[]
     */
    private $entity_events;

    /**
     * @ORM\OneToMany(targetEntity="models\summit\SummitPushNotification", mappedBy="summit", cascade={"persist"}, orphanRemoval=true)
     * @var SummitPushNotification[]
     */
    private $notifications;

    /**
     * @param SummitEvent $summit_event
     * @return bool
     */
    public function isEventInsideSummitDuration(SummitEvent $summit_event)
    {
        return $this->isTimeFrameInsideSummitDuration($summit_event->getLocalStartDate(), $summit_event->getLocalEndDate());
    }

    /**
     * @param DateTime $start_date
     * @param DateTime $end_date
     * @return bool
     */
    public function isTimeFrameInsideSummitDuration(DateTime $start_date, DateTime $end_date)
    {
        $summit_start_date = $this->getLocalBeginDate();
        $summit_end_date = $this->getLocalEndDate();

        return $start_date >= $summit_start_date && $start_date <= $summit_end_date &&
            $end_date <= $summit_end_date && $end_date >= $start_date;
    }

    /**
     * @param DateTime $start_date
     * @param DateTime $end_date
     * @return bool
     */
    public function isTimeFrameOnBookingPeriod(DateTime $start_date, DateTime $end_date): bool
    {
        if (is_null($this->begin_allow_booking_date)) return false;
        if (is_null($this->end_allow_booking_date)) return false;

        return $start_date >= $this->convertDateFromUTC2TimeZone($this->begin_allow_booking_date) && $start_date <= $this->convertDateFromUTC2TimeZone($this->end_allow_booking_date) &&
            $end_date <= $this->convertDateFromUTC2TimeZone($this->end_allow_booking_date) && $end_date >= $start_date;
    }

    /**
     * @param bool $filter_published_events
     * @return \Doctrine\ORM\QueryBuilder
     */
    private function buildModeratorsQuery($filter_published_events = true)
    {
        $query = $this->createQueryBuilder()
            ->select('distinct ps')
            ->from('models\summit\PresentationSpeaker', 'ps')
            ->join('ps.moderated_presentations', 'p')
            ->join('p.summit', 's')
            ->where("s.id = :summit_id");
        if ($filter_published_events)
            $query = $query->andWhere("p.published = 1");
        return $query->setParameter('summit_id', $this->getId());
    }

    /**
     * @param bool $filter_published_events
     * @return \Doctrine\ORM\QueryBuilder
     */
    private function buildSpeakersQuery($filter_published_events = true)
    {
        $query = $this->createQueryBuilder()
            ->select('distinct ps')
            ->from('models\summit\PresentationSpeaker', 'ps')
            ->join('ps.presentations', 'ps_p')
            ->join('ps_p.presentation', 'p')
            ->join('p.summit', 's')
            ->where("s.id = :summit_id");

        if ($filter_published_events)
            $query = $query->andWhere("p.published = 1");
        return $query->setParameter('summit_id', $this->getId());
    }

    /**
     * @return \Doctrine\ORM\QueryBuilder
     */
    private function buildSpeakerSummitAttendanceQuery()
    {
        return $this->createQueryBuilder()
            ->select('distinct ps')
            ->from('models\summit\PresentationSpeaker', 'ps')
            ->join('ps.summit_assistances', 'a')
            ->join('a.summit', 's')
            ->where("s.id = :summit_id")
            ->setParameter('summit_id', $this->getId());
    }

    /**
     * @return PresentationSpeaker[]
     */
    public function getSpeakers()
    {
        // moderators
        $moderators = $this->buildModeratorsQuery()->getQuery()->getResult();
        // get moderators ids to exclude from speakers
        $moderators_ids = array();
        foreach ($moderators as $m) {
            $moderators_ids[] = $m->getId();
        }

        // speakers
        $sbuilder = $this->buildSpeakersQuery();

        if (count($moderators_ids) > 0) {
            $moderators_ids = implode(', ', $moderators_ids);
            $sbuilder = $sbuilder->andWhere("ps.id not in ({$moderators_ids})");
        }

        $speakers = $sbuilder->getQuery()->getResult();

        return array_merge($speakers, $moderators);
    }

    /**
     * @param Member $member
     * @return PresentationSpeaker|null
     */
    public function getSpeakerByMember(Member $member)
    {
        return $this->getSpeakerByMemberId($member->getId());
    }

    /**`
     * @param int $member_id
     * @param bool $filter_published_events
     * @return PresentationSpeaker|null
     */
    public function getSpeakerByMemberId($member_id, $filter_published_events = true)
    {
        // moderators
        $moderator = $this->buildModeratorsQuery($filter_published_events)
            ->join('ps.member', 'mb')
            ->andWhere('mb.id = :member_id')
            ->setParameter('member_id', $member_id)
            ->getQuery()->getOneOrNullResult();

        if (!is_null($moderator)) return $moderator;

        // speakers
        $speaker = $this->buildSpeakersQuery($filter_published_events)
            ->join('ps.member', 'mb')
            ->andWhere('mb.id = :member_id')
            ->setParameter('member_id', $member_id)
            ->getQuery()->getOneOrNullResult();

        if (!is_null($speaker)) return $speaker;

        // assistance
        $speaker = $this->buildSpeakerSummitAttendanceQuery()
            ->join('ps.member', 'mb')
            ->andWhere('mb.id = :member_id')
            ->setParameter('member_id', $member_id)
            ->getQuery()->getOneOrNullResult();

        if (!is_null($speaker)) return $speaker;

        return null;
    }

    /**
     * @param int $speaker_id
     * @param bool $filter_published_events
     * @return PresentationSpeaker|null
     */
    public function getSpeaker($speaker_id, $filter_published_events = true)
    {
        Log::debug(sprintf("Summit::getSpeaker speaker_id %s", $speaker_id));

        // moderators
        $moderator = $this->buildModeratorsQuery($filter_published_events)
            ->andWhere('ps.id = :speaker_id')
            ->setParameter('speaker_id', $speaker_id)
            ->getQuery()->getOneOrNullResult();

        if (!is_null($moderator)) return $moderator;

        // speakers
        $speaker = $this->buildSpeakersQuery($filter_published_events)
            ->andWhere('ps.id = :speaker_id')
            ->setParameter('speaker_id', $speaker_id)
            ->getQuery()->getOneOrNullResult();

        if (!is_null($speaker))
            return $speaker;

        // attendance
        $speaker = $this->buildSpeakerSummitAttendanceQuery()
            ->andWhere('ps.id = :speaker_id')
            ->setParameter('speaker_id', $speaker_id)
            ->getQuery()->getOneOrNullResult();

        if (!is_null($speaker))
            return $speaker;

        return null;
    }

    /**
     * @return Company[]
     */
    public function getEventSponsors()
    {
        $builder = $this->createQueryBuilder();
        return $builder
            ->select('distinct c')
            ->from('models\main\Company', 'c')
            ->join('c.sponsorships', 'sp')
            ->join('sp.summit', 's')
            ->where('s.id = :summit_id and sp.published = 1')
            ->setParameter('summit_id', $this->getId())->getQuery()->getResult();
    }

    /**
     * @return string
     */
    public function getMainPage()
    {
        $path = $this->getSchedulePage();
        if (empty($path)) return '';
        $paths = explode("/", $path);
        array_pop($paths);
        return join("/", $paths);
    }

    /**
     * @return string
     */
    public function getSchedulePage()
    {
        $paths = [];
        try {
            $sql = <<<SQL
    SELECT URLSegment,ParentID FROM SiteTree
    INNER JOIN
    SummitPage ON SummitPage.ID = SiteTree.ID 
    WHERE SummitID = :summit_id AND ClassName = 'SummitAppSchedPage';
SQL;
            $stmt = $this->prepareRawSQL($sql);
            $stmt->execute(['summit_id' => $this->id]);
            $res = $stmt->fetchAll();
            if (count($res) == 0) return '';
            $segment = $res[0]['URLSegment'];
            $parent_id = intval($res[0]['ParentID']);

            $paths[] = $segment;
            do {
                $sql = <<<SQL
    SELECT URLSegment,ParentID FROM SiteTree
    WHERE ID = :parent_id;
SQL;
                $stmt = $this->prepareRawSQL($sql);
                $stmt->execute(['parent_id' => $parent_id]);
                $res = $stmt->fetchAll();
                if (count($res) == 0) break;
                $segment = $res[0]['URLSegment'];
                $parent_id = intval($res[0]['ParentID']);
                $paths[] = $segment;
            } while ($parent_id > 0);

        } catch (\Exception $ex) {
            return '';
        }
        return join("/", array_reverse($paths));
    }

    /**
     * @param SummitEvent $summit_event
     * @param Member|null $member
     * @return bool
     * @throws ValidationException
     */
    static public function allowToSee(SummitEvent $summit_event, Member $member = null)
    {

        $event_type = $summit_event->getType();

        if (is_null($event_type))
            throw new ValidationException(sprintf("event type is null for event id %s", $summit_event->getId()));

        if (!$event_type->isPrivate()) return true;

        if (is_null($member)) return false;

        if ($member->isAdmin()) return true;

        // i am logged, check if i have permissions
        if ($summit_event instanceof SummitGroupEvent) {

            $member_groups_code = [];
            $event_groups_code = [];

            foreach ($member->getGroups() as $member_group) {
                $member_groups_code[] = $member_group->getCode();
            }

            foreach ($summit_event->getGroups() as $event_group) {
                $event_groups_code[] = $event_group->getCode();
            }

            return count(array_intersect($event_groups_code, $member_groups_code)) > 0;
        }
        return true;
    }

    /**
     * @param Member $member
     * @return SummitGroupEvent[]
     */
    public function getGroupEventsFor(Member $member)
    {
        $builder = $this->createQueryBuilder()
            ->select('distinct eg')
            ->from('models\summit\SummitGroupEvent', 'eg')
            ->join('eg.groups', 'g')
            ->join('eg.summit', 's')
            ->where("s.id = :summit_id and eg.published = 1")
            ->setParameter('summit_id', $this->getId());

        if (!$member->isAdmin()) {
            $groups_ids = $member->getGroupsIds();
            if (count($groups_ids) == 0) return [];
            $groups_ids = implode(",", $groups_ids);
            $builder->andWhere("g.id in ({$groups_ids})");
        }

        return $builder->getQuery()->getResult();
    }

    /**
     * @param string $suffix
     * @return string
     */
    public function getSlug($suffix = '-summit')
    {
        $res = $this->name;

        if (!is_null($this->begin_date)) {
            $res .= $this->begin_date->format('Y') . $suffix;
        }

        return strtolower(preg_replace('/[^a-zA-Z0-9\-]/', '', $res));
    }


    public function generateRegistrationSlugPrefix(): void
    {
        if (empty($this->registration_slug_prefix)) {
            $this->registration_slug_prefix = $this->getSlug("");
        }
    }

    /**
     * @param string $registration_slug_prefix
     */
    public function setRegistrationSlugPrefix(string $registration_slug_prefix){
        $this->registration_slug_prefix = $registration_slug_prefix;
    }

    /**
     * @return string
     */
    public function getRegistrationSlugPrefix(): string
    {
        $this->generateRegistrationSlugPrefix();
        return $this->registration_slug_prefix;
    }

    /**
     * @return string
     */
    public function getOrderQRPrefix(): string
    {
        return strtoupper('ORDER_' . $this->getRegistrationSlugPrefix());
    }

    /**
     * @return string
     */
    public function getTicketQRPrefix(): string
    {
        return strtoupper('TICKET_' . $this->getRegistrationSlugPrefix());
    }

    /**
     * @return string
     */
    public function getBadgeQRPrefix(): string
    {
        return strtoupper('BADGE_' . $this->getRegistrationSlugPrefix());
    }

    /**
     * @return int
     */
    public function getPresentationVotesCount()
    {
        try {
            $sql = <<<SQL
            SELECT COUNT(DISTINCT(Vote.ID)) AS votes_count
            FROM PresentationVote AS Vote
            INNER JOIN SummitEvent AS E ON E.ID = Vote.PresentationID
            WHERE E.SummitID = :summit_id
SQL;
            $stmt = $this->prepareRawSQL($sql);
            $stmt->execute(['summit_id' => $this->id]);
            $res = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            return count($res) > 0 ? $res[0] : 0;
        } catch (\Exception $ex) {

        }
        return 0;
    }

    /**
     * @return int
     */
    public function getPresentationVotersCount()
    {
        try {
            $sql = <<<SQL
                SELECT COUNT(DISTINCT(Vote.MemberID)) AS voter_count
            FROM PresentationVote AS Vote
            INNER JOIN SummitEvent AS E ON E.ID = Vote.PresentationID
            WHERE E.SummitID = :summit_id
SQL;
            $stmt = $this->prepareRawSQL($sql);
            $stmt->execute(['summit_id' => $this->id]);
            $res = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            return count($res) > 0 ? $res[0] : 0;
        } catch (\Exception $ex) {

        }
        return 0;
    }

    /**
     * @return int
     */
    public function getAttendeesCount()
    {
        return $this->attendees->count();
    }

    public function getAttendees()
    {
        return $this->attendees;
    }

    /**
     * @return int
     */
    public function getPaidTicketsCount(): int
    {
        return $this->geTicketsCountByStatus(IOrderConstants::PaidStatus);
    }

    /**
     * @param string $status
     * @return int
     */
    public function geTicketsCountByStatus(string $status): int
    {
        try {
            $sql = <<<SQL
           SELECT count(SummitAttendeeTicket.ID) AS TICKET_COUNT from SummitAttendeeTicket
INNER JOIN SummitOrder ON SummitOrder.ID = SummitAttendeeTicket.OrderID
WHERE SummitOrder.SummitID = :summit_id AND SummitAttendeeTicket.Status = ':status'
SQL;
            $stmt = $this->prepareRawSQL($sql);
            $stmt->execute([
                'summit_id' => $this->id,
                'status' => $status
            ]);
            $res = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            return count($res) > 0 ? $res[0] : 0;
        } catch (\Exception $ex) {

        }
        return 0;
    }

    /**
     * @return int
     */
    public function getSpeakersCount()
    {
        return count($this->getSpeakers());
    }

    /**
     * @return int
     */
    public function getPresentationsSubmittedCount()
    {

        try {
            $sql = <<<SQL
            SELECT COUNT(DISTINCT(SummitEvent.ID))
            FROM SummitEvent
            INNER JOIN Presentation ON Presentation.ID = SummitEvent.ID
            WHERE SummitEvent.SummitID = :summit_id AND Presentation.Status = :status
SQL;
            $stmt = $this->prepareRawSQL($sql);
            $stmt->execute(['summit_id' => $this->id, 'status' => Presentation::STATUS_RECEIVED]);
            $res = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            return count($res) > 0 ? $res[0] : 0;
        } catch (\Exception $ex) {

        }
        return 0;
    }

    /**
     * @return int
     */
    public function getPublishedEventsCount()
    {
        try {
            $sql = <<<SQL
            SELECT COUNT(DISTINCT(SummitEvent.ID))
            FROM SummitEvent
            WHERE SummitEvent.SummitID = :summit_id AND SummitEvent.Published = 1
SQL;
            $stmt = $this->prepareRawSQL($sql);
            $stmt->execute(['summit_id' => $this->id]);
            $res = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            return count($res) > 0 ? $res[0] : 0;
        } catch (\Exception $ex) {

        }
        return 0;
    }

    // speakers emails info

    /**
     * @param strign $type
     * @return int
     */
    public function getSpeakerAnnouncementEmailCount(string $type): int
    {
        try {
            $sql = <<<SQL
            SELECT COUNT(DISTINCT(SpeakerAnnouncementSummitEmail.ID))
            FROM SpeakerAnnouncementSummitEmail
            WHERE SpeakerAnnouncementSummitEmail.SummitID = :summit_id AND SpeakerAnnouncementSummitEmail.AnnouncementEmailTypeSent = :type
SQL;
            $stmt = $this->prepareRawSQL($sql);
            $stmt->execute(['summit_id' => $this->id, 'type' => $type]);
            $res = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            return count($res) > 0 ? $res[0] : 0;
        } catch (\Exception $ex) {

        }
        return 0;
    }

    /**
     * @return int
     */
    public function getSpeakerAnnouncementEmailAcceptedCount(): int
    {
        return $this->getSpeakerAnnouncementEmailCount('ACCEPTED');
    }

    /**
     * @return int
     */
    public function getSpeakerAnnouncementEmailRejectedCount(): int
    {
        return $this->getSpeakerAnnouncementEmailCount('REJECTED');
    }

    /**
     * @return int
     */
    public function getSpeakerAnnouncementEmailAlternateCount(): int
    {
        return $this->getSpeakerAnnouncementEmailCount('ALTERNATE');
    }

    /**
     * @return int
     */
    public function getSpeakerAnnouncementEmailAcceptedAlternateCount(): int
    {
        return $this->getSpeakerAnnouncementEmailCount('ACCEPTED_ALTERNATE');
    }

    /**
     * @return int
     */
    public function getSpeakerAnnouncementEmailAcceptedRejectedCount(): int
    {
        return $this->getSpeakerAnnouncementEmailCount('ACCEPTED_REJECTED');
    }

    /**
     * @return int
     */
    public function getSpeakerAnnouncementEmailAlternateRejectedCount(): int
    {
        return $this->getSpeakerAnnouncementEmailCount('ALTERNATE_REJECTED');
    }

    /**
     * @param SummitRegistrationPromoCode $promo_code
     */
    public function addPromoCode(SummitRegistrationPromoCode $promo_code)
    {
        $this->promo_codes->add($promo_code);
        $promo_code->setSummit($this);
    }

    /**
     * @param string $code
     * @return SummitRegistrationPromoCode|null
     */
    public function getPromoCodeByCode($code)
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('code', trim($code)));
        $promo_code = $this->promo_codes->matching($criteria)->first();
        return $promo_code === false ? null : $promo_code;
    }

    /**
     * @param int $promo_code_id
     * @return SummitRegistrationPromoCode|null
     */
    public function getPromoCodeById($promo_code_id)
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', $promo_code_id));
        $promo_code = $this->promo_codes->matching($criteria)->first();
        return $promo_code === false ? null : $promo_code;
    }

    /**
     * @param SummitRegistrationPromoCode $promo_code
     * @return $this
     */
    public function removePromoCode(SummitRegistrationPromoCode $promo_code)
    {
        $this->promo_codes->removeElement($promo_code);
        $promo_code->clearSummit();
        return $this;
    }

    /**
     * @param SummitEventType $event_type
     * @return $this
     */
    public function removeEventType(SummitEventType $event_type)
    {
        $this->event_types->removeElement($event_type);
        $event_type->clearSummit();
        return $this;
    }

    /**
     * @return PresentationCategory[]
     */
    public function getExcludedCategoriesForAcceptedPresentations()
    {
        return $this->excluded_categories_for_accepted_presentations->toArray();
    }

    /**
     * @return PresentationCategory[]
     */
    public function getExcludedCategoriesForAlternatePresentations()
    {
        return $this->excluded_categories_for_alternate_presentations->toArray();
    }

    /**
     * @return PresentationCategory[]
     */
    public function getExcludedCategoriesForRejectedPresentations()
    {
        return $this->excluded_categories_for_rejected_presentations->toArray();
    }

    /**
     * @return PresentationCategory[]
     */
    public function getExcludedCategoriesForUploadSlideDecks()
    {
        return $this->excluded_categories_for_upload_slide_decks->toArray();
    }

    /**
     * @param SummitEventType $event_type
     * @return $this
     */
    public function addEventType(SummitEventType $event_type)
    {
        $this->event_types->add($event_type);
        $event_type->setSummit($this);
        return $this;
    }

    /**
     * @param SummitTicketType $ticket_type
     * @return $this
     */
    public function addTicketType(SummitTicketType $ticket_type)
    {
        $this->ticket_types->add($ticket_type);
        $ticket_type->setSummit($this);
        return $this;
    }

    /**
     * @return int
     */
    public function getTicketTypesCount(): int
    {
        return $this->ticket_types->count();
    }

    /**
     * @return bool
     */
    public function hasTicketTypes(): bool
    {
        return $this->getTicketTypesCount() > 0;
    }


    /**
     * @return null|string
     * @throws ValidationException
     */
    public function getDefaultTicketTypeCurrency(): ?string
    {
        $default_currency = null;
        foreach ($this->ticket_types as $ticket_type) {
            $ticket_type_currency = $ticket_type->getCurrency();
            if (empty($ticket_type_currency)) continue;
            if (empty($default_currency)) {
                $default_currency = $ticket_type_currency;
                continue;
            }
            if ($ticket_type_currency != $default_currency)
                throw new ValidationException(sprintf("All ticket types for summit %s should have same currency.", $this->getId()));
        }
        return $default_currency;
    }

    /**
     * @param SummitTicketType $ticket_type
     * @return $this
     */
    public function removeTicketType(SummitTicketType $ticket_type)
    {
        $this->ticket_types->removeElement($ticket_type);
        $ticket_type->clearSummit();
        return $this;
    }

    /**
     * @param string $name
     * @return SummitTicketType|null
     */
    public function getTicketTypeByName($name)
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('name', trim($name)));
        $res = $this->ticket_types->matching($criteria)->first();
        return $res === false ? null : $res;
    }


    /**
     * @param int $id
     * @return SummitTicketType|null
     */
    public function getTicketTypeById($id)
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', intval($id)));
        $res = $this->ticket_types->matching($criteria)->first();
        return $res === false ? null : $res;
    }

    /**
     * @param int $rsvp_template_id
     * @return RSVPTemplate|null
     */
    public function getRSVPTemplateById($rsvp_template_id)
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', intval($rsvp_template_id)));
        $rsvp_template = $this->rsvp_templates->matching($criteria)->first();
        return $rsvp_template === false ? null : $rsvp_template;
    }

    /**
     * @param string $rsvp_template_title
     * @return RSVPTemplate|null
     */
    public function getRSVPTemplateByTitle($rsvp_template_title)
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('title', trim($rsvp_template_title)));
        $rsvp_template = $this->rsvp_templates->matching($criteria)->first();
        return $rsvp_template === false ? null : $rsvp_template;
    }

    /**
     * @param RSVPTemplate $template
     * @return $this
     */
    public function addRSVPTemplate(RSVPTemplate $template)
    {
        if ($this->rsvp_templates->contains($template)) return;
        $this->rsvp_templates->add($template);
        $template->setSummit($this);
        return $this;
    }

    /**
     * @param RSVPTemplate $template
     * @return $this
     */
    public function removeRSVPTemplate(RSVPTemplate $template)
    {
        if (!$this->rsvp_templates->contains($template)) return;
        $this->rsvp_templates->removeElement($template);
        $template->clearSummit();
        return $this;
    }

    use OrderableChilds;

    /**
     * @param SummitAbstractLocation $location
     * @param int $new_order
     * @throws ValidationException
     */
    public function recalculateLocationOrder(SummitAbstractLocation $location, $new_order)
    {

        $criteria = Criteria::create();
        $criteria->orderBy(['order' => 'ASC']);
        $filtered_locations = [];

        foreach ($this->locations->matching($criteria)->toArray() as $l) {
            if (Summit::isPrimaryLocation($l))
                $filtered_locations[] = $l;
        }

        self::recalculateOrderForCollection($filtered_locations, $location, $new_order);
    }

    /**
     * @return int[]
     */
    public function getScheduleEventsIds(): array
    {
        $query = <<<SQL
SELECT e.id  
FROM  models\summit\SummitEvent e
WHERE 
e.published = 1
AND e.summit = :summit
SQL;

        $native_query = $this->getEM()->createQuery($query);

        $native_query->setParameter("summit", $this);

        return $native_query->getResult();
    }

    /**
     * @param SummitAbstractLocation $location
     * @return int[]
     */
    public function getScheduleEventsIdsPerLocation(SummitAbstractLocation $location)
    {
        $query = <<<SQL
SELECT e.id  
FROM  models\summit\SummitEvent e
WHERE 
e.published = 1
AND e.summit = :summit
AND e.location = :location
SQL;

        $native_query = $this->getEM()->createQuery($query);

        $native_query->setParameter("summit", $this);
        $native_query->setParameter("location", $location);

        $res = $native_query->getResult();

        return $res;
    }

    /**
     * @param SummitAbstractLocation $location
     * @return $this
     */
    public function removeLocation(SummitAbstractLocation $location)
    {
        $this->locations->removeElement($location);
        $location->clearSummit();
        return $this;
    }

    /**
     * @param string $calendar_sync_name
     */
    public function setCalendarSyncName($calendar_sync_name)
    {
        $this->calendar_sync_name = $calendar_sync_name;
    }

    /**
     * @param string $calendar_sync_desc
     */
    public function setCalendarSyncDesc($calendar_sync_desc)
    {
        $this->calendar_sync_desc = $calendar_sync_desc;
    }

    /**
     * @return string
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * @param string $link
     */
    public function setLink($link)
    {
        $this->link = $link;
    }

    /**
     * @return string
     */
    public function getRegistrationLink()
    {
        return $this->registration_link;
    }

    /**
     * @param string $registration_link
     */
    public function setRegistrationLink($registration_link)
    {
        $this->registration_link = $registration_link;
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
     * @param SummitPushNotification $notification
     * @return $this
     */
    public function addNotification(SummitPushNotification $notification)
    {
        $this->notifications->add($notification);
        $notification->setSummit($this);
        return $this;
    }

    /**
     * @return string
     */
    public function getTimeZoneId()
    {
        return $this->time_zone_id;
    }

    /**
     * @param string $time_zone_id
     */
    public function setTimeZoneId($time_zone_id)
    {
        $this->time_zone_id = $time_zone_id;
    }

    /**
     * @return string
     */
    public function getSecondaryRegistrationLink()
    {
        return $this->secondary_registration_link;
    }

    /**
     * @param string $secondary_registration_link
     */
    public function setSecondaryRegistrationLink($secondary_registration_link)
    {
        $this->secondary_registration_link = $secondary_registration_link;
    }

    /**
     * @return string
     */
    public function getSecondaryRegistrationLabel()
    {
        return $this->secondary_registration_label;
    }

    /**
     * @param string $secondary_registration_label
     */
    public function setSecondaryRegistrationLabel($secondary_registration_label)
    {
        $this->secondary_registration_label = $secondary_registration_label;
    }

    /**
     * @param int $notification_id
     * @return SummitPushNotification|null
     */
    public function getNotificationById($notification_id)
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', intval($notification_id)));
        $notification = $this->notifications->matching($criteria)->first();
        return $notification === false ? null : $notification;
    }

    /**
     * @param SummitPushNotification $notification
     * @return $this
     */
    public function removeNotification(SummitPushNotification $notification)
    {
        $this->notifications->removeElement($notification);
        $notification->clearSummit();
        return $this;
    }

    /**
     * @return string
     */
    public function getCalendarSyncName()
    {
        return $this->calendar_sync_name;
    }

    /**
     * @return string
     */
    public function getCalendarSyncDesc()
    {
        return $this->calendar_sync_desc;
    }

    /**
     * @return DateTime
     */
    public function getRegistrationBeginDate(): ?DateTime
    {
        return $this->registration_begin_date;
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function isRegistrationPeriodOpen(): bool
    {
        return $this->isDateOnRegistrationPeriod(new \DateTime('now', new \DateTimeZone('UTC')));
    }

    /**
     * @param DateTime $date
     * @return bool
     */
    public function isDateOnRegistrationPeriod(DateTime $date): bool
    {
        if (!is_null($this->registration_begin_date) && !is_null($this->registration_end_date)) {
            return $date >= $this->registration_begin_date && $date <= $this->registration_end_date;
        }
        return false;
    }

    public function isRegistrationPeriodDefined(): bool
    {
        return !is_null($this->registration_begin_date) && !is_null($this->registration_end_date);
    }

    /**
     * @param DateTime $registration_begin_date
     */
    public function setRegistrationBeginDate(DateTime $registration_begin_date)
    {
        $this->registration_begin_date = $this->convertDateFromTimeZone2UTC($registration_begin_date);
    }

    public function setRawRegistrationBeginDate(DateTime $registration_begin_date)
    {
        $this->registration_begin_date = $registration_begin_date;
    }

    public function setRawRegistrationEndDate(DateTime $registration_end_date)
    {
        $this->registration_end_date = $registration_end_date;
    }

    /**
     * @return $this
     */
    public function clearRegistrationDates()
    {
        $this->registration_begin_date = $this->registration_end_date = null;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getRegistrationEndDate(): ?DateTime
    {
        return $this->registration_end_date;
    }

    /**
     * @param DateTime $registration_end_date
     */
    public function setRegistrationEndDate(DateTime $registration_end_date)
    {
        $this->registration_end_date = $this->convertDateFromTimeZone2UTC($registration_end_date);
    }

    /**
     * @return SelectionPlan[]
     */
    public function getSelectionPlans()
    {
        return $this->selection_plans;
    }

    /**
     * @param string $name
     * @return null|SelectionPlan
     */
    public function getSelectionPlanByName($name)
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('name', trim($name)));
        $selection_plan = $this->selection_plans->matching($criteria)->first();
        return $selection_plan === false ? null : $selection_plan;
    }

    /**
     * @param string $status
     * @return null|SelectionPlan
     */
    public function getCurrentSelectionPlanByStatus($status)
    {
        $now_utc = new \DateTime('now', new \DateTimeZone('UTC'));
        $criteria = Criteria::create();
        switch (strtoupper($status)) {
            case SelectionPlan::STATUS_SUBMISSION:
                {
                    $criteria->where(Criteria::expr()->lte('submission_begin_date', $now_utc))->andWhere(Criteria::expr()->gte('submission_end_date', $now_utc));
                }
                break;
            case SelectionPlan::STATUS_VOTING:
                {
                    $criteria->where(Criteria::expr()->lte('voting_begin_date', $now_utc))->andWhere(Criteria::expr()->gte('voting_end_date', $now_utc));
                }
                break;
            case SelectionPlan::STATUS_SELECTION:
                {
                    $criteria->where(Criteria::expr()->lte('selection_begin_date', $now_utc))->andWhere(Criteria::expr()->gte('selection_end_date', $now_utc));
                }
                break;
        }
        $selection_plan = $this->selection_plans->matching($criteria)->first();
        return $selection_plan === false ? null : $selection_plan;
    }

    /**
     * @param int $id
     * @return null|SelectionPlan
     */
    public function getSelectionPlanById($id): ?SelectionPlan
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', intval($id)));
        $selection_plan = $this->selection_plans->matching($criteria)->first();
        return $selection_plan === false ? null : $selection_plan;
    }

    /**
     * @param SelectionPlan $selection_plan
     * @return $this
     */
    public function addSelectionPlan(SelectionPlan $selection_plan)
    {
        $this->selection_plans->add($selection_plan);
        $selection_plan->setSummit($this);
        return $this;
    }

    /**
     * @param SelectionPlan $selection_plan
     * @return $this
     */
    public function removeSelectionSelectionPlan(SelectionPlan $selection_plan)
    {
        $this->selection_plans->removeElement($selection_plan);
        $selection_plan->clearSummit();
        return $this;
    }

    /**
     * @return SelectionPlan[]
     */
    public function getActiveSelectionPlans()
    {
        return $this->selection_plans->filter(function ($e) {
            return $e->IsEnabled();
        })->toArray();
    }


    const STAGE_UNSTARTED = -1;
    const STAGE_OPEN = 0;
    const STAGE_FINISHED = 1;

    /**
     * @param Tag $tag
     * @return TrackTagGroup|null
     */
    public function getTrackTagGroupForTag(Tag $tag)
    {
        $query = <<<SQL
SELECT tg  
FROM  App\Models\Foundation\Summit\TrackTagGroup tg
JOIN tg.allowed_tags t
WHERE 
tg.summit = :summit
AND t.tag = :tag
SQL;

        $native_query = $this->getEM()->createQuery($query);

        $native_query->setParameter("summit", $this);
        $native_query->setParameter("tag", $tag);

        $res = $native_query->getResult();
        return count($res) > 0 ? $res[0] : null;
    }

    /**
     * @param string $tag_value
     * @return bool
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function isTagValueAllowedOnTrackTagGroups($tag_value)
    {
        $query = <<<SQL
SELECT COUNT(tg.id) 
FROM  App\Models\Foundation\Summit\TrackTagGroup tg
JOIN tg.allowed_tags t
JOIN t.tag tag
WHERE 
tg.summit = :summit
AND tag.tag = :tag_value
SQL;

        $native_query = $this->getEM()->createQuery($query);

        $native_query->setParameter("summit", $this);
        $native_query->setParameter("tag_value", $tag_value);

        $res = $native_query->getSingleScalarResult();
        return $res > 0;
    }

    /**
     * @param string $tag_value
     * @return null|TrackTagGroupAllowedTag
     */
    public function getAllowedTagOnTagTrackGroup($tag_value)
    {
        $query = <<<SQL
SELECT allowed_tag 
FROM   App\Models\Foundation\Summit\TrackTagGroupAllowedTag allowed_tag
JOIN allowed_tag.track_tag_group tg
JOIN allowed_tag.tag tag
WHERE 
tg.summit = :summit
AND tag.tag = :tag_value
SQL;

        $native_query = $this->getEM()->createQuery($query);

        $native_query->setParameter("summit", $this);
        $native_query->setParameter("tag_value", $tag_value);

        $res = $native_query->getResult();
        return count($res) > 0 ? $res[0] : null;
    }

    /**
     * @param int $tag_id
     * @return TrackTagGroup|null
     */
    public function getTrackTagGroupForTagId($tag_id)
    {
        $query = <<<SQL
SELECT tg  
FROM  App\Models\Foundation\Summit\TrackTagGroup tg
JOIN tg.allowed_tags tgs
JOIN tgs.tag t
WHERE 
tg.summit = :summit
AND t.id = :tag_id
SQL;

        $native_query = $this->getEM()->createQuery($query);

        $native_query->setParameter("summit", $this);
        $native_query->setParameter("tag_id", $tag_id);

        $res = $native_query->getResult();
        return count($res) > 0 ? $res[0] : null;
    }

    /**
     * @param string $name
     * @return null|TrackTagGroup
     */
    public function getTrackTagGroupByName($name)
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('name', $name));
        $track_tag_group = $this->track_tag_groups->matching($criteria)->first();
        return !$track_tag_group ? null : $track_tag_group;
    }

    /**
     * @return TrackTagGroup[]|ArrayCollection
     */
    public function getTrackTagGroups()
    {
        return $this->track_tag_groups;
    }

    /**
     * @param string $label
     * @return null|TrackTagGroup
     */
    public function getTrackTagGroupByLabel($label)
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('label', $label));
        $track_tag_group = $this->track_tag_groups->matching($criteria)->first();
        return !$track_tag_group ? null : $track_tag_group;
    }

    /**
     * @param TrackTagGroup $track_tag_group
     * @return $this
     */
    public function addTrackTagGroup(TrackTagGroup $track_tag_group)
    {
        if ($this->track_tag_groups->contains($track_tag_group)) return $this;
        $track_tag_group->setOrder($this->getTrackTagGroupMaxOrder() + 1);
        $this->track_tag_groups->add($track_tag_group);
        $track_tag_group->setSummit($this);
        return $this;
    }

    /**
     * @param TrackTagGroup $track_tag_group
     * @return $this
     */
    public function removeTrackTagGroup(TrackTagGroup $track_tag_group)
    {
        if (!$this->track_tag_groups->contains($track_tag_group)) return $this;
        $this->track_tag_groups->removeElement($track_tag_group);
        return $this;
    }

    /**
     * @return int
     */
    private function getTrackTagGroupMaxOrder()
    {
        $criteria = Criteria::create();
        $criteria->orderBy(['order' => 'DESC']);
        $group = $this->track_tag_groups->matching($criteria)->first();
        return $group === false ? 0 : $group->getOrder();
    }

    /**
     * @param TrackTagGroup $track_tag_group
     * @param int $new_order
     * @throws ValidationException
     */
    public function recalculateTrackTagGroupOrder(TrackTagGroup $track_tag_group, $new_order)
    {
        self::recalculateOrderForSelectable($this->track_tag_groups, $track_tag_group, $new_order);
    }

    /**
     * @param int $track_tag_group_id
     * @return TrackTagGroup|null
     */
    public function getTrackTagGroup($track_tag_group_id)
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', intval($track_tag_group_id)));
        $track_tag_group = $this->track_tag_groups->matching($criteria)->first();
        return $track_tag_group === false ? null : $track_tag_group;
    }

    /**
     * @return string|null
     */
    public function getRawSlug(): ?string
    {
        return $this->slug;
    }

    /**
     * @param string $slug
     */
    public function setRawSlug(string $slug): void
    {
        $slugify = new Slugify();
        $this->slug = $slugify->slugify($slug);
    }

    /**
     * @return DateTime
     */
    public function getMeetingRoomBookingStartTime(): ?DateTime
    {
        return $this->meeting_room_booking_start_time;
    }

    /**
     * @param DateTime $meeting_room_booking_start_time
     */
    public function setMeetingRoomBookingStartTime(DateTime $meeting_room_booking_start_time): void
    {
        $this->meeting_room_booking_start_time = $meeting_room_booking_start_time;
    }

    /**
     * @return DateTime
     */
    public function getMeetingRoomBookingEndTime(): ?DateTime
    {
        return $this->meeting_room_booking_end_time;
    }

    /**
     * @param DateTime $meeting_room_booking_end_time
     */
    public function setMeetingRoomBookingEndTime(DateTime $meeting_room_booking_end_time): void
    {
        $this->meeting_room_booking_end_time = $meeting_room_booking_end_time;
    }

    /**
     * @return int
     */
    public function getMeetingRoomBookingSlotLength(): int
    {
        return $this->meeting_room_booking_slot_length;
    }

    /**
     * @param int $meeting_room_booking_slot_length
     */
    public function setMeetingRoomBookingSlotLength(int $meeting_room_booking_slot_length): void
    {
        if ($meeting_room_booking_slot_length <= 0)
            throw new ValidationException("meeting_room_booking_slot_length should be greather than zero");

        if ($this->meeting_room_booking_slot_length != $meeting_room_booking_slot_length) {
            // only allow to change if we dont have any reservation
            $sql = <<<SQL
select COUNT(SummitRoomReservation.ID) from SummitRoomReservation
INNER JOIN SummitBookableVenueRoom S on SummitRoomReservation.RoomID = S.ID
INNER JOIN SummitVenueRoom R ON R.ID = S.ID
INNER JOIN SummitVenue V ON V.ID = R.VenueID
INNER JOIN SummitAbstractLocation L on V.ID = L.ID
WHERE L.SummitID = :summit_id AND (
    SummitRoomReservation.Status = 'Reserved' OR
        SummitRoomReservation.Status = 'Paid');
SQL;

            $stmt = $this->prepareRawSQL($sql);
            $stmt->execute(
                [
                    'summit_id' => $this->getId(),
                ]
            );
            $res = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            $reservation_count = count($res) > 0 ? $res[0] : 0;
            if ($reservation_count > 0) {
                throw new ValidationException("summit already has reservations with that slot len!");
            }
        }

        $this->meeting_room_booking_slot_length = $meeting_room_booking_slot_length;
    }

    /**
     * @return int
     */
    public function getMeetingRoomBookingMaxAllowed(): int
    {
        return $this->meeting_room_booking_max_allowed;
    }

    /**
     * @param int $meeting_room_booking_max_allowed
     */
    public function setMeetingRoomBookingMaxAllowed(int $meeting_room_booking_max_allowed): void
    {
        $this->meeting_room_booking_max_allowed = $meeting_room_booking_max_allowed;
    }

    /**
     * @return mixed
     */
    public function getMeetingBookingRoomAllowedAttributes()
    {
        return $this->meeting_booking_room_allowed_attributes;
    }

    /**
     * @param SummitBookableVenueRoomAttributeType $type
     */
    public function addMeetingBookingRoomAllowedAttribute(SummitBookableVenueRoomAttributeType $type)
    {
        if ($this->meeting_booking_room_allowed_attributes->contains($type)) return;
        $this->meeting_booking_room_allowed_attributes->add($type);
        $type->setSummit($this);
    }

    /**
     * @param SummitBookableVenueRoomAttributeType $type
     */
    public function removeMeetingBookingRoomAllowedAttribute(SummitBookableVenueRoomAttributeType $type)
    {
        if (!$this->meeting_booking_room_allowed_attributes->contains($type)) return;
        $this->meeting_booking_room_allowed_attributes->removeElement($type);
    }

    /**
     * @param int $id
     * @return SummitBookableVenueRoomAttributeType|null
     */
    public function getBookableAttributeTypeById(int $id): ?SummitBookableVenueRoomAttributeType
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', intval($id)));
        $attr = $this->meeting_booking_room_allowed_attributes->matching($criteria)->first();
        return $attr === false ? null : $attr;
    }

    /**
     * @param string $type
     * @return SummitBookableVenueRoomAttributeType|null
     */
    public function getBookableAttributeTypeByTypeName(string $type): ?SummitBookableVenueRoomAttributeType
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('type', trim($type)));
        $attr = $this->meeting_booking_room_allowed_attributes->matching($criteria)->first();
        return $attr === false ? null : $attr;
    }

    public function getMaxReservationsPerDay(): int
    {
        $interval = $this->meeting_room_booking_end_time->diff($this->meeting_room_booking_start_time);
        $minutes = $interval->days * 24 * 60;
        $minutes += $interval->h * 60;
        $minutes += $interval->i;
        return intval($minutes / $this->meeting_room_booking_slot_length);
    }

    /**
     * @param int $id
     * @return SummitBookableVenueRoomAttributeValue|null
     */
    public function getMeetingBookingRoomAllowedAttributeValueById(int $id): ?SummitBookableVenueRoomAttributeValue
    {
        foreach ($this->meeting_booking_room_allowed_attributes as $attribute_type) {
            $value = $attribute_type->getValueById($id);
            if (!is_null($value)) return $value;
        }
        return null;
    }


    /**
     * @return string|null
     */
    public function getApiFeedType(): ?string
    {
        return $this->api_feed_type;
    }

    /**
     * @param string $api_feed_type
     * @throws ValidationException
     */
    public function setApiFeedType(string $api_feed_type): void
    {
        if (!empty($api_feed_type) && !in_array($api_feed_type, ISummitExternalScheduleFeedType::ValidFeedTypes))
            throw new ValidationException(sprintf("feed type %s is not valid!", $api_feed_type));
        $this->api_feed_type = $api_feed_type;
    }

    /**
     * @return string|null
     */
    public function getApiFeedUrl(): ?string
    {
        return $this->api_feed_url;
    }

    /**
     * @param string $api_feed_url
     */
    public function setApiFeedUrl(string $api_feed_url): void
    {
        $this->api_feed_url = $api_feed_url;
    }

    /**
     * @return string|null
     */
    public function getApiFeedKey(): ?string
    {
        return $this->api_feed_key;
    }

    /**
     * @param string $api_feed_key
     */
    public function setApiFeedKey(string $api_feed_key): void
    {
        $this->api_feed_key = $api_feed_key;
    }

    /**
     * @return DateTime
     */
    public function getBeginAllowBookingDate(): ?DateTime
    {
        return $this->begin_allow_booking_date;
    }

    /**
     * @param DateTime $begin_allow_booking_date
     */
    public function setBeginAllowBookingDate(DateTime $begin_allow_booking_date): void
    {
        $this->begin_allow_booking_date = $this->convertDateFromTimeZone2UTC($begin_allow_booking_date);
    }

    /**
     * @param DateTime $begin_allow_booking_date
     */
    public function setRawBeginAllowBookingDate(DateTime $begin_allow_booking_date): void
    {
        $this->begin_allow_booking_date = $begin_allow_booking_date;
    }

    /*
     * @return SummitAccessLevelType[]
     */
    public function getBadgeAccessLevelTypes()
    {
        return $this->badge_access_level_types;
    }

    /**
     * @param SummitAccessLevelType $access_level
     */
    public function addBadgeAccessLevelType(SummitAccessLevelType $access_level): void
    {
        if ($this->badge_access_level_types->contains($access_level)) return;
        $this->badge_access_level_types->add($access_level);
        $access_level->setSummit($this);
    }

    /**
     * @param SummitAccessLevelType $access_level
     */
    public function removeBadgeAccessLevelType(SummitAccessLevelType $access_level): void
    {
        if (!$this->badge_access_level_types->contains($access_level)) return;
        $this->badge_access_level_types->removeElement($access_level);
        $access_level->clearSummit();
    }

    /**
     * @param int $levelId
     * @return SummitAccessLevelType|null
     */
    public function getBadgeAccessLevelTypeById(int $levelId): ?SummitAccessLevelType
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', intval($levelId)));
        $attr = $this->badge_access_level_types->matching($criteria)->first();
        return $attr === false ? null : $attr;
    }


    public function getDefaultBadgeAccessLevelTypes()
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('is_default', true));
        return $this->badge_access_level_types->matching($criteria);
    }

    /**
     * @param string $level_name
     * @return SummitAccessLevelType|null
     */
    public function getBadgeAccessLevelTypeByName(string $level_name): ?SummitAccessLevelType
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('name', trim($level_name)));
        $attr = $this->badge_access_level_types->matching($criteria)->first();
        return $attr === false ? null : $attr;
    }

    /**
     * @return SummitTaxType[]
     */
    public function getTaxTypes()
    {
        return $this->tax_types;
    }

    /**
     * @param SummitTaxType $tax_type
     */
    public function addTaxType(SummitTaxType $tax_type): void
    {
        if ($this->tax_types->contains($tax_type)) return;
        $this->tax_types->add($tax_type);
        $tax_type->setSummit($this);
    }

    /**
     * @param SummitTaxType $tax_type
     */
    public function removeTaxType(SummitTaxType $tax_type): void
    {
        if (!$this->tax_types->contains($tax_type)) return;
        $this->tax_types->removeElement($tax_type);
        $tax_type->clearSummit();
    }

    /**
     * @param int $tax_id
     * @return SummitTaxType|null
     */
    public function getTaxTypeById(int $tax_id): ?SummitTaxType
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', intval($tax_id)));
        $attr = $this->tax_types->matching($criteria)->first();
        return $attr === false ? null : $attr;
    }

    /**
     * @param string $tax_name
     * @return SummitTaxType|null
     */
    public function getTaxTypeByName(string $tax_name): ?SummitTaxType
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('name', trim($tax_name)));
        $attr = $this->tax_types->matching($criteria)->first();
        return $attr === false ? null : $attr;
    }

    /**
     * @return SummitBadgeFeatureType[]|ArrayCollection
     */
    public function getBadgeFeaturesTypes()
    {
        return $this->badge_features_types;
    }

    /**
     * @param SummitBadgeFeatureType $feature_type
     */
    public function addFeatureType(SummitBadgeFeatureType $feature_type): void
    {
        if ($this->badge_features_types->contains($feature_type)) return;
        $this->badge_features_types->add($feature_type);
        $feature_type->setSummit($this);
    }

    /**
     * @param SummitBadgeFeatureType $feature_type
     */
    public function removeFeatureType(SummitBadgeFeatureType $feature_type): void
    {
        if (!$this->badge_features_types->contains($feature_type)) return;
        $this->badge_features_types->removeElement($feature_type);
        $feature_type->clearSummit();
    }


    /**
     * @param int $feature_id
     * @return SummitBadgeFeatureType|null
     */
    public function getFeatureTypeById(int $feature_id): ?SummitBadgeFeatureType
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', intval($feature_id)));
        $feature = $this->badge_features_types->matching($criteria)->first();
        return $feature === false ? null : $feature;
    }

    /**
     * @param string $feature_name
     * @return SummitBadgeFeatureType|null
     */
    public function getFeatureTypeByName(string $feature_name): ?SummitBadgeFeatureType
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('name', trim($feature_name)));
        $feature = $this->badge_features_types->matching($criteria)->first();
        return $feature === false ? null : $feature;
    }

    /**
     * @return ArrayCollection|SummitBadgeType[]
     */
    public function getBadgeTypes()
    {
        return $this->badge_types;
    }

    /**
     * @param SummitBadgeType $badge_type
     */
    public function addBadgeType(SummitBadgeType $badge_type)
    {
        if ($this->badge_types->contains($badge_type)) return;
        $this->badge_types->add($badge_type);
        $badge_type->setSummit($this);
    }

    /**
     * @param SummitBadgeType $badge_type
     */
    public function removeBadgeType(SummitBadgeType $badge_type): void
    {
        if (!$this->badge_types->contains($badge_type)) return;
        $this->badge_types->removeElement($badge_type);
        $badge_type->clearSummit();
    }

    /**
     * @param int $badge_type_id
     * @return SummitBadgeType|null
     */
    public function getBadgeTypeById(int $badge_type_id): ?SummitBadgeType
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', intval($badge_type_id)));
        $badge_type = $this->badge_types->matching($criteria)->first();
        return $badge_type === false ? null : $badge_type;
    }

    /**
     * @param string $badge_type_name
     * @return SummitBadgeType|null
     */
    public function getBadgeTypeByName(string $badge_type_name): ?SummitBadgeType
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('name', trim($badge_type_name)));
        $badge_type = $this->badge_types->matching($criteria)->first();
        return $badge_type === false ? null : $badge_type;
    }

    /**
     * @return bool
     */
    public function hasDefaultBadgeType(): bool
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('default', true));
        return $this->badge_types->matching($criteria)->count() > 0;
    }

    /**
     * @return bool
     */
    public function getDefaultBadgeType(): ?SummitBadgeType
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('default', true));
        $badge_type = $this->badge_types->matching($criteria)->first();
        return $badge_type === false ? null : $badge_type;
    }

    /**
     * @return SummitTicketType|null
     */
    public function getFirstDefaultTicketType():?SummitTicketType
    {
        $defaultBadgeType = $this->getDefaultBadgeType();
        if (is_null($defaultBadgeType))
            return null;

        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('badge_type', $defaultBadgeType));
        $res =  $this->ticket_types->matching($criteria)->first();
        return $res == false ? null : $res;
    }
    /**
     * @return DateTime
     */
    public function getEndAllowBookingDate(): ?DateTime
    {
        return $this->end_allow_booking_date;
    }

    /**
     * @param DateTime $end_allow_booking_date
     */
    public function setEndAllowBookingDate(DateTime $end_allow_booking_date): void
    {
        $this->end_allow_booking_date = $this->convertDateFromTimeZone2UTC($end_allow_booking_date);
    }

    /**
     * @param DateTime $end_allow_booking_date
     */
    public function setRawEndAllowBookingDate(DateTime $end_allow_booking_date): void
    {
        $this->end_allow_booking_date = $end_allow_booking_date;
    }

    public function clearAllowBookingDates(): void
    {
        $this->begin_allow_booking_date = $this->end_allow_booking_date = null;
    }

    /**
     * @return bool
     */
    public function isBookingPeriodOpen(): bool
    {
        $now_utc = new \DateTime('now', new \DateTimeZone('UTC'));
        if (!is_null($this->begin_allow_booking_date) && !is_null($this->end_allow_booking_date)) {
            return $now_utc >= $this->begin_allow_booking_date && $now_utc <= $this->end_allow_booking_date;
        }

        return false;
    }

    public function isBookingPeriodEnded():bool{
        $now_utc = new \DateTime('now', new \DateTimeZone('UTC'));
        if (!is_null($this->end_allow_booking_date)) {
            return $now_utc > $this->end_allow_booking_date;
        }

        return true;
    }

    public function getMonthYear(): ?string
    {
        if (is_null($this->end_date)) return "";
        return $this->convertDateFromUTC2TimeZone($this->end_date)->format("M Y");
    }

    /**
     * @return string
     */
    public function getLogoUrl(): ?string
    {
        $logoUrl = null;
        if ($this->hasLogo() && $logo = $this->getLogo()) {
            $logoUrl = $logo->getUrl();
        }
        return $logoUrl;
    }

    public function getReassignTicketTillDate(): ?DateTime
    {
        return $this->reassign_ticket_till_date;
    }

    public function getReassignTicketTillDateLocal(): ?DateTime
    {
        return $this->convertDateFromUTC2TimeZone($this->reassign_ticket_till_date);
    }


    /**
     * @return bool
     */
    public function hasReassignTicketLimit(): bool
    {
        return !is_null($this->reassign_ticket_till_date);
    }

    /**
     * @param DateTime $reassign_ticket_till_date
     */
    public function setReassignTicketTillDate(DateTime $reassign_ticket_till_date): void
    {
        $this->reassign_ticket_till_date = $this->convertDateFromTimeZone2UTC($reassign_ticket_till_date);
    }

    /**
     * @param DateTime $reassign_ticket_till_date
     */
    public function setRawReassignTicketTillDate(DateTime $reassign_ticket_till_date): void
    {
        $this->reassign_ticket_till_date = $reassign_ticket_till_date;
    }

    /**
     * @return string
     */
    public function getRegistrationDisclaimerContent(): ?string
    {
        return $this->registration_disclaimer_content;
    }

    /**
     * @param string $registration_disclaimer_content
     */
    public function setRegistrationDisclaimerContent(string $registration_disclaimer_content): void
    {
        $this->registration_disclaimer_content = $registration_disclaimer_content;
    }

    /**
     * @return bool
     */
    public function isRegistrationDisclaimerMandatory(): bool
    {
        return $this->registration_disclaimer_mandatory;
    }

    /**
     * @param bool $registration_disclaimer_mandatory
     */
    public function setRegistrationDisclaimerMandatory(bool $registration_disclaimer_mandatory): void
    {
        $this->registration_disclaimer_mandatory = $registration_disclaimer_mandatory;
    }

    /**
     * @return array
     */
    public function getSupportedCurrencies(): array
    {
        return SummitTicketType::AllowedCurrencies;
    }

    /**
     * @return Sponsor[]
     */
    public function getSummitSponsors()
    {
        return $this->summit_sponsors;
    }

    public function getPublishedSummitSponsors()
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('is_published', true));
        return $this->summit_sponsors->matching($criteria);
    }

    /**
     * @param Company $company
     * @return Sponsor|null
     */
    public function getSummitSponsorByCompany(Company $company): ?Sponsor
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('company', $company));
        $sponsor = $this->summit_sponsors->matching($criteria)->first();
        return $sponsor === false ? null : $sponsor;
    }

    /**
     * @param int $id
     * @return Sponsor|null
     */
    public function getSummitSponsorById(int $id): ?Sponsor
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', intval($id)));
        $sponsor = $this->summit_sponsors->matching($criteria)->first();
        return $sponsor === false ? null : $sponsor;
    }

    /**
     * @param int $id
     * @return Sponsor|null
     */
    public function getSummitSponsorshipTypeById(int $id): ?SummitSponsorshipType
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', intval($id)));
        $sponsorship = $this->sponsorship_types->matching($criteria)->first();
        return $sponsorship === false ? null : $sponsorship;
    }

    /**
     * @param SponsorshipType $type
     * @return SummitSponsorshipType|null
     */
    public function getSummitSponsorshipTypeByType(SponsorshipType $type): ?SummitSponsorshipType
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('type', $type));
        $sponsorship = $this->sponsorship_types->matching($criteria)->first();
        return $sponsorship === false ? null : $sponsorship;
    }

    /**
     * @param Sponsor $sponsor
     */
    public function addSummitSponsor(Sponsor $sponsor)
    {
        if ($this->summit_sponsors->contains($sponsor)) return;
        $sponsor->setOrder($this->getSummitSponsorMaxOrder() + 1);
        $this->summit_sponsors->add($sponsor);
        $sponsor->setSummit($this);
    }

    /**
     * @return int
     */
    private function getSummitSponsorMaxOrder()
    {
        $criteria = Criteria::create();
        $criteria->orderBy(['order' => 'DESC']);
        $sponsor = $this->summit_sponsors->matching($criteria)->first();
        $res = $sponsor === false ? 0 : $sponsor->getOrder();
        return is_null($res) ? 0 : $res;
    }

    /**
     * @param Sponsor $sponsor
     */
    public function removeSummitSponsor(Sponsor $sponsor)
    {
        if (!$this->summit_sponsors->contains($sponsor)) return;
        $this->summit_sponsors->removeElement($sponsor);
        $sponsor->clearSummit();
    }

    /**
     * @return SummitRefundPolicyType[]
     */
    public function getRefundPolicies(): array
    {
        return $this->refund_policies;
    }

    /**
     * @param SummitRefundPolicyType $policy
     */
    public function addRefundPolicy(SummitRefundPolicyType $policy)
    {
        if ($this->refund_policies->contains($policy)) return;
        $this->refund_policies->add($policy);
        $policy->setSummit($this);
    }

    /**
     * @param SummitRefundPolicyType $policy
     */
    public function removeRefundPolicy(SummitRefundPolicyType $policy)
    {
        if (!$this->refund_policies->contains($policy)) return;
        $this->refund_policies->removeElement($policy);
        $policy->clearSummit();
    }

    /**
     * @param string $policy_name
     * @return SummitBadgeType|null
     */
    public function getRefundPolicyByName(string $policy_name): ?SummitRefundPolicyType
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('name', trim($policy_name)));
        $policy = $this->refund_policies->matching($criteria)->first();
        return $policy === false ? null : $policy;
    }

    /**
     * @param int $until_x_days_before_event_starts
     * @return SummitBadgeType|null
     */
    public function getRefundPolicyByDays(int $until_x_days_before_event_starts): ?SummitRefundPolicyType
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('until_x_days_before_event_starts', intval($until_x_days_before_event_starts)));
        $policy = $this->refund_policies->matching($criteria)->first();
        return $policy === false ? null : $policy;
    }

    /**
     * @param int $performed_n_days_before_event_starts
     * @return SummitRefundPolicyType|null
     */
    public function getRefundPolicyForRefundRequest(int $performed_n_days_before_event_starts): ?SummitRefundPolicyType
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->lte('until_x_days_before_event_starts', intval($performed_n_days_before_event_starts)));
        $criteria->orderBy(['until_x_days_before_event_starts' => 'DESC']);
        $policy = $this->refund_policies->matching($criteria)->first();
        return $policy === false ? null : $policy;
    }

    /**
     * @param int $id
     * @return SummitBadgeType|null
     */
    public function getRefundPolicyById(int $id): ?SummitRefundPolicyType
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', intval($id)));
        $policy = $this->refund_policies->matching($criteria)->first();
        return $policy === false ? null : $policy;
    }

    /**
     * @return SummitOrderExtraQuestionType[]
     */
    public function getOrderExtraQuestions()
    {
        return $this->order_extra_questions;
    }

    /**
     * @param string $usage
     * @return ArrayCollection|\Doctrine\Common\Collections\Collection
     */
    public function getMandatoryOrderExtraQuestionsByUsage(string $usage)
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('mandatory', true));
        $criteria->andWhere(Criteria::expr()->in('usage', [$usage, SummitOrderExtraQuestionTypeConstants::BothQuestionUsage]));
        return $this->order_extra_questions->matching($criteria);
    }

    /**
     * @param string $usage
     * @return array
     */
    public function getMainOrderExtraQuestionsByUsage(string $usage): array
    {
        $dql = <<<DQL
SELECT q from models\summit\SummitOrderExtraQuestionType q 
JOIN q.summit s 
WHERE s.id = :summit_id
AND ( q.usage = :usage1 OR q.usage = :usage2 )
AND not exists (select r from App\Models\Foundation\Main\ExtraQuestions\SubQuestionRule r where r.sub_question = q)
DQL;

        $query = $this->createQuery($dql);
        return $query
            ->setParameter('summit_id', $this->getIdentifier())
            ->setParameter('usage1', $usage)
            ->setParameter('usage2', SummitOrderExtraQuestionTypeConstants::BothQuestionUsage)
            ->getResult();
    }

    /**
     * @param string $usage
     * @return ArrayCollection|\Doctrine\Common\Collections\Collection
     */
    public function getOrderExtraQuestionsByUsage(string $usage)
    {
        $criteria = Criteria::create();
        $criteria->andWhere(Criteria::expr()->in('usage', [$usage, SummitOrderExtraQuestionTypeConstants::BothQuestionUsage]));
        return $this->order_extra_questions->matching($criteria);
    }

    /**
     * @param int $question_id
     * @return SummitOrderExtraQuestionType|null
     */
    public function getOrderExtraQuestionById(int $question_id): ?SummitOrderExtraQuestionType
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', $question_id));
        $question = $this->order_extra_questions->matching($criteria)->first();
        return $question === false ? null : $question;
    }

    /**
     * @param string $name
     * @return SummitOrderExtraQuestionType|null
     */
    public function getOrderExtraQuestionByName(string $name): ?SummitOrderExtraQuestionType
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('name', trim($name)));
        $question = $this->order_extra_questions->matching($criteria)->first();
        return $question === false ? null : $question;
    }

    /**
     * @param string $label
     * @return SummitOrderExtraQuestionType|null
     */
    public function getOrderExtraQuestionByLabel(string $label): ?SummitOrderExtraQuestionType
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('label', trim($label)));
        $question = $this->order_extra_questions->matching($criteria)->first();
        return $question === false ? null : $question;
    }

    /**
     * @return int
     */
    private function getOrderExtraQuestionMaxOrder(): int
    {
        $criteria = Criteria::create();
        $criteria->orderBy(['order' => 'DESC']);
        $question = $this->order_extra_questions->matching($criteria)->first();
        return $question === false ? 0 : $question->getOrder();
    }

    /**
     * @param SummitOrderExtraQuestionType $extra_question
     */
    public function addOrderExtraQuestion(SummitOrderExtraQuestionType $extra_question)
    {

        if ($this->order_extra_questions->contains($extra_question)) return;
        $extra_question->setOrder($this->getOrderExtraQuestionMaxOrder() + 1);
        $this->order_extra_questions->add($extra_question);
        $extra_question->setSummit($this);
    }

    /**
     * @param SummitOrderExtraQuestionType $extra_question
     */
    public function removeOrderExtraQuestion(SummitOrderExtraQuestionType $extra_question)
    {

        if (!$this->order_extra_questions->contains($extra_question)) return;
        $this->order_extra_questions->removeElement($extra_question);
        $extra_question->clearSummit();
    }

    /**
     * @param SummitOrderExtraQuestionType $question
     * @param int $new_order
     * @throws ValidationException
     */
    public function recalculateQuestionOrder(SummitOrderExtraQuestionType $question, $new_order)
    {
        self::recalculateOrderForSelectable($this->order_extra_questions, $question, $new_order);
    }

    /**
     * @param Sponsor $sponsor
     * @param int $new_order
     * @throws ValidationException
     */
    public function recalculateSummitSponsorOrder(Sponsor $sponsor, $new_order)
    {
        self::recalculateOrderForSelectable($this->summit_sponsors, $sponsor, $new_order);
    }

    /**
     * @return SummitOrder[]
     */
    public function getOrders()
    {
        return $this->orders;
    }

    /**
     * @param int $id
     * @return SummitOrder|null
     */
    public function getOrderById(int $id): ?SummitOrder
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', intval($id)));
        $order = $this->orders->matching($criteria)->first();
        return $order === false ? null : $order;
    }

    /**
     * @param SummitOrder $order
     */
    public function addOrder(SummitOrder $order)
    {
        if ($this->orders->contains($order)) return;
        $this->orders->add($order);
        $order->setSummit($this);
    }

    /**
     * @param SummitOrder $order
     */
    public function removeOrder(SummitOrder $order)
    {
        if (!$this->orders->contains($order)) return;
        $this->orders->removeElement($order);
        $order->clearSummit();
    }

    /**
     * @param string $number
     * @return bool
     */
    public function existOrderNumber(string $number): bool
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('number', trim($number)));
        return $this->orders->matching($criteria)->count() > 0;
    }

    /**
     * @var bool
     */
    private $mark_as_deleted;

    public function markAsDeleted()
    {
        $this->mark_as_deleted = true;
    }

    /**
     * @return bool
     */
    public function isDeleting(): bool
    {
        return is_null($this->mark_as_deleted) ? false : $this->mark_as_deleted;
    }

    /**
     * @return string
     */
    public function getQRRegistryFieldDelimiter(): string
    {
        return IQREntity::QRRegistryFieldDelimiterChar;
    }

    /**
     * @return int
     */
    public function getRegistrationReminderEmailDaysInterval(): ?int
    {
        $days_interval = $this->registration_reminder_email_days_interval;

        if (is_null($days_interval)) {
            $days_interval = intval(Config::get('registration.reminder_email_days_interval', 0));
        }

        return $days_interval;
    }

    /**
     * @param int $registration_reminder_email_days_interval
     */
    public function setRegistrationReminderEmailDaysInterval(int $registration_reminder_email_days_interval): void
    {
        $this->registration_reminder_email_days_interval = $registration_reminder_email_days_interval;
    }

    /**
     * @return bool
     */
    public function isEnded(): bool
    {
        $utc_now = new \DateTime('now', new \DateTimeZone('UTC'));
        $end_date = $this->getEndDate();
        if (is_null($end_date)) return false;
        return $end_date < $utc_now;
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function isOpen(): bool
    {
        return $this->dayIsOnSummitPeriod(new DateTime('now', new \DateTimeZone('UTC')), false);
    }

    /**
     * @param DateTime $day
     * @param bool $omit_time_check
     * @return bool
     */
    public function dayIsOnSummitPeriod(\DateTime $day, $omit_time_check = true): bool
    {
        if (is_null($this->begin_date)) return false;
        if (is_null($this->end_date)) return false;

        $dt = clone $day;
        $dt = $dt->setTimezone(new \DateTimeZone('UTC'));

        if ($omit_time_check)
            $dt = $dt->setTime(0, 0, 0,0);

        $dt = $dt->getTimestamp();

        $bd = clone $this->begin_date;

        if ($omit_time_check)
            $bd = $bd->setTime(0, 0, 0, 0);

        $bd = $bd->getTimestamp();

        $ed = clone $this->end_date;

        if ($omit_time_check)
            $ed = $ed->setTime(0, 0, 0, 0);

        $ed = $ed->getTimestamp();

        Log::debug(sprintf("Summit::dayIsOnSummitPeriod bd %s ed %s dt %s", $bd, $ed, $dt));
        return $bd <= $dt && $dt <= $ed;
    }

    /*
     * @return string
     */
    public function getExternalRegistrationFeedType(): ?string
    {
        return $this->external_registration_feed_type;
    }

    /**
     * @return string
     */
    public function getExternalRegistrationFeedApiKey(): ?string
    {
        return $this->external_registration_feed_api_key;
    }

    /**
     * @param string $external_registration_feed_type
     * @throws ValidationException
     */
    public function setExternalRegistrationFeedType(string $external_registration_feed_type): void
    {
        if (!empty($external_registration_feed_type) && !in_array($external_registration_feed_type, ISummitExternalRegistrationFeedType::ValidFeedTypes))
            throw new ValidationException(sprintf("feed type %s is not valid!", $external_registration_feed_type));
        $this->external_registration_feed_type = $external_registration_feed_type;
    }

    /**
     * @param string $external_registration_feed_api_key
     */
    public function setExternalRegistrationFeedApiKey(string $external_registration_feed_api_key): void
    {
        $this->external_registration_feed_api_key = $external_registration_feed_api_key;
    }

    // schedule

    /**
     * @return string
     */
    public function getScheduleDefaultPageUrl(): ?string
    {
        return $this->schedule_default_page_url;
    }

    /**
     * @param string $schedule_default_page_url
     */
    public function setScheduleDefaultPageUrl(string $schedule_default_page_url): void
    {
        $this->schedule_default_page_url = $schedule_default_page_url;
    }

    /**
     * @return string
     */
    public function getScheduleDefaultEventDetailUrl(): ?string
    {
        return $this->schedule_default_event_detail_url;
    }

    /**
     * @param string $schedule_default_event_detail_url
     * @throws ValidationException
     */
    public function setScheduleDefaultEventDetailUrl(string $schedule_default_event_detail_url): void
    {
        if (!empty($schedule_default_event_detail_url) && !str_contains($schedule_default_event_detail_url, ':event_id')) {
            throw new ValidationException("Property schedule_default_event_detail_url must contains at least replacement variable :event_id.");
        }

        $this->schedule_default_event_detail_url = $schedule_default_event_detail_url;
    }

    /**
     * @return string
     */
    public function getScheduleOgSiteName(): ?string
    {
        return self::_get($this->schedule_og_site_name, "schedule.og_site_name");
    }

    /**
     * @param string $schedule_og_site_name
     */
    public function setScheduleOgSiteName(string $schedule_og_site_name): void
    {
        $this->schedule_og_site_name = $schedule_og_site_name;
    }

    /**
     * @return string
     */
    public function getScheduleOgImageUrl(): ?string
    {
        return self::_get($this->schedule_og_image_url, "schedule.og_image_url");
    }

    /**
     * @param string $schedule_og_image_url
     */
    public function setScheduleOgImageUrl(string $schedule_og_image_url): void
    {
        $this->schedule_og_image_url = $schedule_og_image_url;
    }

    /**
     * @return string
     */
    public function getScheduleOgImageSecureUrl(): ?string
    {
        return self::_get($this->schedule_og_image_secure_url, "schedule.og_image_secure_url");
    }

    /**
     * @param string $schedule_og_image_secure_url
     */
    public function setScheduleOgImageSecureUrl(string $schedule_og_image_secure_url): void
    {
        $this->schedule_og_image_secure_url = $schedule_og_image_secure_url;
    }

    /**
     * @return int
     */
    public function getScheduleOgImageWidth(): int
    {
        return self::_get($this->schedule_og_image_width, "schedule.og_image_width");
    }

    /**
     * @param int $schedule_og_image_width
     */
    public function setScheduleOgImageWidth(int $schedule_og_image_width): void
    {
        $this->schedule_og_image_width = $schedule_og_image_width;
    }

    /**
     * @return int
     */
    public function getScheduleOgImageHeight(): int
    {
        return self::_get($this->schedule_og_image_height, "schedule.og_image_height");
    }

    /**
     * @param int $schedule_og_image_height
     */
    public function setScheduleOgImageHeight(int $schedule_og_image_height): void
    {
        $this->schedule_og_image_height = $schedule_og_image_height;
    }

    /**
     * @return string
     */
    public function getScheduleFacebookAppId(): ?string
    {
        return self::_get($this->schedule_facebook_app_id, "schedule.facebook_app_id");
    }

    /**
     * @param string $schedule_facebook_app_id
     */
    public function setScheduleFacebookAppId(string $schedule_facebook_app_id): void
    {
        $this->schedule_facebook_app_id = $schedule_facebook_app_id;
    }

    /**
     * @return string
     */
    public function getScheduleIosAppName(): ?string
    {
        return self::_get($this->schedule_ios_app_name, "schedule.ios_app_name");
    }

    /**
     * @param string $schedule_ios_app_name
     */
    public function setScheduleIosAppName(string $schedule_ios_app_name): void
    {
        $this->schedule_ios_app_name = $schedule_ios_app_name;
    }

    /**
     * @return string
     */
    public function getScheduleIosAppStoreId(): ?string
    {
        return self::_get($this->schedule_ios_app_store_id, "schedule.ios_app_store_id");
    }

    /**
     * @param string $schedule_ios_app_store_id
     */
    public function setScheduleIosAppStoreId(string $schedule_ios_app_store_id): void
    {
        $this->schedule_ios_app_store_id = $schedule_ios_app_store_id;
    }

    /**
     * @return string
     */
    public function getScheduleIosAppCustomSchema(): ?string
    {
        return self::_get($this->schedule_ios_app_custom_schema, "schedule.ios_app_custom_schema");
    }

    /**
     * @param string $schedule_ios_app_custom_schema
     */
    public function setScheduleIosAppCustomSchema(string $schedule_ios_app_custom_schema): void
    {
        $this->schedule_ios_app_custom_schema = $schedule_ios_app_custom_schema;
    }

    /**
     * @return string
     */
    public function getScheduleAndroidAppName(): ?string
    {
        return self::_get($this->schedule_android_app_name, "schedule.android_app_name");
    }

    /**
     * @param string $schedule_android_app_name
     */
    public function setScheduleAndroidAppName(string $schedule_android_app_name): void
    {
        $this->schedule_android_app_name = $schedule_android_app_name;
    }

    /**
     * @return string
     */
    public function getScheduleAndroidAppPackage(): ?string
    {
        return self::_get($this->schedule_android_app_package, "schedule.android_app_package");
    }

    /**
     * @param string $schedule_android_app_package
     */
    public function setScheduleAndroidAppPackage(string $schedule_android_app_package): void
    {
        $this->schedule_android_app_package = $schedule_android_app_package;
    }

    /**
     * @return string
     */
    public function getScheduleAndroidCustomSchema(): ?string
    {
        return self::_get($this->schedule_android_custom_schema, "schedule.android_custom_schema");
    }

    /**
     * @param string $schedule_android_custom_schema
     */
    public function setScheduleAndroidCustomSchema(string $schedule_android_custom_schema): void
    {
        $this->schedule_android_custom_schema = $schedule_android_custom_schema;
    }

    /**
     * @return string
     */
    public function getScheduleTwitterAppName(): ?string
    {
        return self::_get($this->schedule_twitter_app_name, "schedule.twitter_app_name");
    }

    /**
     * @param string $schedule_twitter_app_name
     */
    public function setScheduleTwitterAppName(string $schedule_twitter_app_name): void
    {
        $this->schedule_twitter_app_name = $schedule_twitter_app_name;
    }

    /**
     * @return string
     */
    public function getScheduleTwitterText(): ?string
    {
        return self::_get($this->schedule_twitter_text, "schedule.twitter_text");
    }

    /**
     * @param string $schedule_twitter_text
     */
    public function setScheduleTwitterText(string $schedule_twitter_text): void
    {
        $this->schedule_twitter_text = $schedule_twitter_text;
    }

    /**
     * @param string $sid
     * @return PersonalCalendarShareInfo|null
     */
    public function getScheduleShareableLinkById(string $sid): ?PersonalCalendarShareInfo
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('cid', trim($sid)));
        $criteria->andWhere(Criteria::expr()->eq('revoked', 0));
        $link = $this->schedule_shareable_links->matching($criteria)->first();
        return $link === false ? null : $link;
    }

    /**
     * @param PersonalCalendarShareInfo $link
     */
    public function addScheduleShareableLink(PersonalCalendarShareInfo $link)
    {
        if ($this->schedule_shareable_links->contains($link)) return;
        $this->schedule_shareable_links->add($link);
        $link->setSummit($this);
    }

    /**
     * @param PersonalCalendarShareInfo $link
     */
    public function removeScheduleShareableLink(PersonalCalendarShareInfo $link)
    {
        if (!$this->schedule_shareable_links->contains($link)) return;
        $this->schedule_shareable_links->removeElement($link);
        $link->clearSummit();
    }

    /**
     * @param PaymentGatewayProfile $payment_profile
     */
    public function addPaymentProfile(PaymentGatewayProfile $payment_profile)
    {
        if ($this->payment_profiles->contains($payment_profile))
            return;
        $this->payment_profiles->add($payment_profile);
        $payment_profile->setSummit($this);
    }

    /**
     * @param PaymentGatewayProfile $payment_profile
     */
    public function removePaymentProfile(PaymentGatewayProfile $payment_profile)
    {
        if (!$this->payment_profiles->contains($payment_profile))
            return;
        $this->payment_profiles->removeElement($payment_profile);
        $payment_profile->clearSummit();
    }

    /**
     * @param string $application_type
     * @param IBuildDefaultPaymentGatewayProfileStrategy|null $build_default_payment_gateway_profile_strategy
     * @return IPaymentGatewayAPI|null
     * @throws ValidationException
     */
    public function getPaymentGateWayPerApp
    (
        string                                      $application_type,
        ?IBuildDefaultPaymentGatewayProfileStrategy $build_default_payment_gateway_profile_strategy = null
    ): ?IPaymentGatewayAPI
    {

        Log::debug(sprintf("Summit::getPaymentGateWayPerApp id %s application_type %s", $this->id, $application_type));

        if (!in_array($application_type, IPaymentConstants::ValidApplicationTypes))
            throw new ValidationException(sprintf("Application Type %s is not valid.", $application_type));

        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('active', true));
        $criteria->andWhere(Criteria::expr()->eq('application_type', trim($application_type)));
        $payment_profile = $this->payment_profiles->matching($criteria)->first();

        if (!$payment_profile && !is_null($build_default_payment_gateway_profile_strategy)) {
            // try to build default one
            Log::debug(sprintf("Summit::getPaymentGateWayPerApp id %s application_type %s trying to get default settings", $this->id, $application_type));
            $payment_profile = $build_default_payment_gateway_profile_strategy->build($application_type);
        }

        if (!$payment_profile) return null;

        return $payment_profile->buildPaymentGatewayApi();
    }


    /**
     * @return ArrayCollection|\Doctrine\Common\Collections\Collection
     */
    public function getActivePaymentGateWayProfiles()
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('active', true));
        return $this->payment_profiles->matching($criteria);
    }

    /**
     * @param string $application_type
     * @return PaymentGatewayProfile|null
     * @throws ValidationException
     */
    public function getPaymentGateWayProfilePerApp(string $application_type): ?PaymentGatewayProfile
    {

        if (!in_array($application_type, IPaymentConstants::ValidApplicationTypes))
            throw new ValidationException(sprintf("Application Type %s is not valid.", $application_type));

        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('active', true));
        $criteria->andWhere(Criteria::expr()->eq('application_type', trim($application_type)));

        $payment_profile = $this->payment_profiles->matching($criteria)->first();
        return (!$payment_profile) ? null : $payment_profile;
    }

    /**
     * @param int $profileId
     * @return PaymentGatewayProfile|null
     */
    public function getPaymentProfileById(int $profileId): ?PaymentGatewayProfile
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', intval($profileId)));
        $profile = $this->payment_profiles->matching($criteria)->first();
        return $profile === false ? null : $profile;
    }

    public function addEmailEventFlow(SummitEmailEventFlow $email_event_flow)
    {
        if ($this->email_flows_events->contains($email_event_flow)) return;
        $this->email_flows_events->add($email_event_flow);
        $email_event_flow->setSummit($this);
    }

    public function removeEmailEventFlow(SummitEmailEventFlow $email_event_flow)
    {
        if (!$this->email_flows_events->contains($email_event_flow)) return;
        $this->email_flows_events->removeElement($email_event_flow);
    }

    public function clearEmailEventFlow()
    {
        $this->email_flows_events->clear();
    }

    /**
     * @param string $eventSlug
     * @return string|null
     */
    public function getEmailIdentifierPerEmailEventFlowSlug(string $eventSlug): ?string
    {
        Log::debug(sprintf("Summit::getEmailIdentifierPerEmailEventFlowSlug id %s slug %s", $this->id, $eventSlug));
        // first check if we have an override

        $identifier = null;
        try {
            $sql = <<<SQL
           SELECT SummitEmailEventFlow.EmailTemplateIdentifier AS Template 
           FROM `SummitEmailEventFlow` 
           inner join SummitEmailEventFlowType ON SummitEmailEventFlowType.ID = SummitEmailEventFlowTypeID 
           where SummitID = :summit_id 
           AND SummitEmailEventFlowType.Slug = :slug LIMIT 0,1;
SQL;
            $stmt = $this->prepareRawSQL($sql);
            $stmt->execute([
                'summit_id' => $this->id,
                'slug' => trim($eventSlug)
            ]);

            $res = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            $identifier = count($res) > 0 ? $res[0] : null;
        } catch (\Exception $ex) {
            $identifier = null;
        }


        if (!empty($identifier)) {
            Log::debug
            (
                sprintf
                (
                    "Summit::getEmailIdentifierPerEmailEventFlowSlug id %s slug %s got override by template %s",
                    $this->id,
                    $eventSlug,
                    $identifier
                )
            );
            return $identifier;
        }

        Log::debug(sprintf("Summit::getEmailIdentifierPerEmailEventFlowSlug id %s slug %s trying to get default one", $this->id, $eventSlug));
        // then check default
        $email_event_type = $this->createQueryBuilder()
            ->select('distinct eft')
            ->from('App\Models\Foundation\Summit\EmailFlows\SummitEmailEventFlowType', 'eft')
            ->where("eft.slug = :slug")
            ->setParameter('slug', trim($eventSlug))
            ->setMaxResults(1)
            ->setCacheable(false)
            ->getQuery()
            ->setCacheable(false)
            ->useQueryCache(false)
            ->getOneOrNullResult();

        if (!is_null($email_event_type) && $email_event_type instanceof SummitEmailEventFlowType)
            return $email_event_type->getDefaultEmailTemplate();

        return null;
    }

    /**
     * @param string $eventSlug
     * @return string|null
     */
    public function getEmailRecipientPerEmailEventFlowSlug(string $eventSlug): ?string
    {
        Log::debug(sprintf("Summit::getEmailRecipientPerEmailEventFlowSlug id %s slug %s", $this->id, $eventSlug));

        try {
            $sql = <<<SQL
           SELECT SummitEmailEventFlow.EmailRecipients AS Template 
           FROM `SummitEmailEventFlow` 
           inner join SummitEmailEventFlowType ON SummitEmailEventFlowType.ID = SummitEmailEventFlowTypeID 
           where SummitID = :summit_id 
           AND SummitEmailEventFlowType.Slug = :slug LIMIT 0,1;
SQL;
            $stmt = $this->prepareRawSQL($sql);
            $stmt->execute([
                'summit_id' => $this->id,
                'slug' => trim($eventSlug)
            ]);

            $res = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            $recipients = count($res) > 0 ? $res[0] : null;
            if (!empty($recipients)) {
                Log::debug(
                    "Summit::getEmailRecipientPerEmailEventFlowSlug id {$this->id} slug {$eventSlug} " .
                    "got override by template {$recipients}");
                return $recipients;
            }
            return null;
        } catch (\Exception $ex) {
            return null;
        }
    }

    /**
     * @param SummitEmailEventFlowType $type
     * @return SummitEmailEventFlow|null
     */
    public function getEmailEventByType(SummitEmailEventFlowType $type): ?SummitEmailEventFlow
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('event_type', $type));
        $event = $this->email_flows_events->matching($criteria)->first();
        return $event === false ? null : $event;
    }

    public function getEmailEventById(int $id): ?SummitEmailEventFlow
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', $id));
        $event = $this->email_flows_events->matching($criteria)->first();
        return $event === false ? null : $event;
    }

    /**
     * @return array|SummitEmailEventFlow[]
     */
    public function getAllEmailFlowsEvents()
    {
        return $this->seedDefaultEmailFlowEvents();
    }

    public function seedDefaultEmailFlowEvents()
    {
        $builder = $this->createQueryBuilder()
            ->select('distinct ft')
            ->from('App\Models\Foundation\Summit\EmailFlows\SummitEmailFlowType', 'ft')
            ->orderBy("ft.id");

        $res = $builder->getQuery()->getResult();
        $list = [];
        foreach ($res as $flow_type) {
            foreach ($flow_type->getEventTypes() as $event_type) {
                // check if we have an override
                $email_event_flow = $this->getEmailEventByType($event_type);
                if (is_null($email_event_flow)) {
                    $email_event_flow = new SummitEmailEventFlow();
                    $email_event_flow->setEventType($event_type);
                    $email_event_flow->setEmailTemplateIdentifier($event_type->getDefaultEmailTemplate());
                    $this->addEmailEventFlow($email_event_flow);
                }
                $list[] = $email_event_flow;
            }
        }
        return $list;
    }

    /**
     * this define the default access level types
     * that need to be seeded when a new show is created
     */
    public function seedDefaultAccessLevelTypes(): void
    {

        $defaults = [
            [
                'name' => SummitAccessLevelType::CHAT,
                'description' => 'Enables Chat Feature.'
            ],
            [
                'name' => SummitAccessLevelType::IN_PERSON,
                'description' => 'Allows in person show access.'
            ],
            [
                'name' => SummitAccessLevelType::VIRTUAL,
                'description' => 'Allows virtual show access.'
            ]
        ];

        foreach ($defaults as $default) {
            if (!is_null($this->getBadgeAccessLevelTypeByName($default['name']))) continue;
            $a = new SummitAccessLevelType();
            $a->setName($default['name']);
            $a->setDescription($default['description']);
            $a->setIsDefault(true);
            $this->addBadgeAccessLevelType($a);
        }
    }

    /**
     * @return string
     */
    public function getDefaultPageUrl(): ?string
    {
        return $this->default_page_url;
    }

    /**
     * @param string $default_page_url
     */
    public function setDefaultPageUrl(string $default_page_url): void
    {
        $this->default_page_url = $default_page_url;
    }

    /**
     * @return string
     */
    public function getSpeakerConfirmationDefaultPageUrl(): ?string
    {
        return $this->speaker_confirmation_default_page_url;
    }

    /**
     * @param string $speaker_confirmation_default_page_url
     */
    public function setSpeakerConfirmationDefaultPageUrl(string $speaker_confirmation_default_page_url): void
    {
        $this->speaker_confirmation_default_page_url = $speaker_confirmation_default_page_url;
    }

    public function getSummitDocuments()
    {
        return $this->summit_documents;
    }

    /**
     * @param SummitDocument $doc
     */
    public function addSummitDocument(SummitDocument $doc)
    {
        if ($this->summit_documents->contains($doc)) return;
        $this->summit_documents->add($doc);
        $doc->setSummit($this);
    }

    /**
     * @param SummitDocument $doc
     */
    public function removeSummitDocument(SummitDocument $doc)
    {
        if (!$this->summit_documents->contains($doc)) return;
        $this->summit_documents->removeElement($doc);
        $doc->clearSummit();
    }

    public function clearSummitDocuments()
    {
        $this->summit_documents->clear();
    }

    /**
     * @param int $document_id
     * @return SummitDocument|null
     */
    public function getSummitDocumentById(int $document_id): ?SummitDocument
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', intval($document_id)));
        $document = $this->summit_documents->matching($criteria)->first();
        return $document === false ? null : $document;
    }

    /**
     * @param string $name
     * @return SummitDocument|null
     */
    public function getSummitDocumentByName(string $name): ?SummitDocument
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('name', trim($name)));
        $document = $this->summit_documents->matching($criteria)->first();
        return $document === false ? null : $document;
    }

    /**
     * @param string $label
     * @return SummitDocument|null
     */
    public function getSummitDocumentByLabel(string $label): ?SummitDocument
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('label', trim($label)));
        $document = $this->summit_documents->matching($criteria)->first();
        return $document === false ? null : $document;
    }

    /**
     * @param string $email
     * @return SummitRegistrationInvitation|null
     */
    public function getSummitRegistrationInvitationByEmail(string $email): ?SummitRegistrationInvitation
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('email', strtolower(trim($email))));
        $invitation = $this->registration_invitations->matching($criteria)->first();
        return $invitation === false ? null : $invitation;
    }

    /**
     * @param int $invitation_id
     * @return SummitRegistrationInvitation|null
     */
    public function getSummitRegistrationInvitationById(int $invitation_id): ?SummitRegistrationInvitation
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', intval($invitation_id)));
        $invitation = $this->registration_invitations->matching($criteria)->first();
        return $invitation === false ? null : $invitation;
    }

    /**
     * @param string $hash
     * @return SummitRegistrationInvitation|null
     */
    public function getSummitRegistrationInvitationByHash(string $hash): ?SummitRegistrationInvitation
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('hash', trim($hash)));
        $invitation = $this->registration_invitations->matching($criteria)->first();
        return $invitation === false ? null : $invitation;
    }

    /**
     * @param string $email
     * @param SummitTicketType $ticketType
     * @return bool
     */
    public function canBuyRegistrationTicketByType(string $email, SummitTicketType $ticketType): bool
    {

        if ($ticketType->getSummitId() != $this->id) {
            Log::debug
            (
                sprintf
                (
                    "Summit::canBuyRegistrationTicketByType ticket type %s does not belongs to summit %s.",
                    $ticketType->getId(),
                    $this->id
                )
            );
            return false;
        }

        $audience = $ticketType->getAudience();

        if ($audience === SummitTicketType::Audience_All) {
            // anyone can buy
            Log::debug
            (
                sprintf
                (
                    "Summit::canBuyRegistrationTicketByType ticket type %s summit %s audience All.",
                    $ticketType->getId(),
                    $this->id
                )
            );
            return true;
        }

        $invitation = $this->getSummitRegistrationInvitationByEmail($email);

        if (is_null($invitation)) {
            // we dont have invitation
            Log::debug
            (
                sprintf
                (
                    "Summit::canBuyRegistrationTicketByType invitation for email %s and summit %s does not exists. checking ticket type %s audience %s",
                    $email,
                    $this->id,
                    $ticketType->getId(),
                    $audience
                )
            );
            // we can only buy all or without invitation
            return $audience == SummitTicketType::Audience_Without_Invitation;
        }

        // we have an invitation
        Log::debug
        (
            sprintf
            (
                "Summit::canBuyRegistrationTicketByType invitation for email %s and summit %s exists and accepted status is %b. checking ticket type %s audience %s",
                $email,
                $this->id,
                $invitation->isAccepted(),
                $ticketType->getId(),
                $audience
            )
        );

        return $invitation->isTicketTypeAllowed($ticketType->getId());
    }

    /**
     * @param SummitRegistrationInvitation $invitation
     */
    public function addRegistrationInvitation(SummitRegistrationInvitation $invitation)
    {
        if ($this->registration_invitations->contains($invitation)) return;
        $this->registration_invitations->add($invitation);
        $invitation->setSummit($this);
    }

    /**
     * @param SummitRegistrationInvitation $invitation
     */
    public function removeRegistrationInvitation(SummitRegistrationInvitation $invitation)
    {
        if (!$this->registration_invitations->contains($invitation)) return;
        $this->registration_invitations->removeElement($invitation);
        $invitation->clearSummit();
    }

    /**
     * @return ArrayCollection|SummitRegistrationInvitation[]
     */
    public function getRegistrationInvitations()
    {
        return $this->registration_invitations;
    }

    public function clearRegistrationInvitations(): void
    {
        $this->registration_invitations->clear();
    }

    /**
     * @param SummitSubmissionInvitation $invitation
     */
    public function addSubmissionInvitation(SummitSubmissionInvitation $invitation)
    {
        if ($this->submission_invitations->contains($invitation)) return;
        $this->submission_invitations->add($invitation);
        $invitation->setSummit($this);
    }

    /**
     * @param SummitSubmissionInvitation $invitation
     */
    public function removeSubmissionInvitation(SummitSubmissionInvitation $invitation)
    {
        if (!$this->submission_invitations->contains($invitation)) return;
        $this->submission_invitations->removeElement($invitation);
        $invitation->clearSummit();
    }

    /**
     * @return ArrayCollection|SummitSubmissionInvitation[]
     */
    public function SubmissionInvitation()
    {
        return $this->submission_invitations;
    }

    public function clearSubmissionInvitations(): void
    {
        $this->submission_invitations->clear();
    }

    /**
     * @param string $email
     * @return SummitRegistrationInvitation|null
     */
    public function getSubmissionInvitationByEmail(string $email): ?SummitSubmissionInvitation
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('email', strtolower(trim($email))));
        $invitation = $this->submission_invitations->matching($criteria)->first();
        return $invitation === false ? null : $invitation;
    }

    /**
     * @param int $invitation_id
     * @return SummitSubmissionInvitation|null
     */
    public function getSubmissionInvitationById(int $invitation_id): ?SummitSubmissionInvitation
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', intval($invitation_id)));
        $invitation = $this->submission_invitations->matching($criteria)->first();
        return $invitation === false ? null : $invitation;
    }

    /**
     * @param SummitAdministratorPermissionGroup $group
     */
    public function add2SummitAdministratorPermissionGroup(SummitAdministratorPermissionGroup $group)
    {
        if ($this->permission_groups->contains($group)) return;
        $this->permission_groups->add($group);
    }

    public function removeFromSummitAdministratorPermissionGroup(SummitAdministratorPermissionGroup $group)
    {
        if (!$this->permission_groups->contains($group)) return;
        $this->permission_groups->removeElement($group);
    }

    public function getSummitAdministratorPermissionGroup()
    {
        return $this->permission_groups;
    }

    /**
     * @param SummitMediaUploadType $type
     */
    public function addMediaUploadType(SummitMediaUploadType $type)
    {
        if ($this->media_upload_types->contains($type)) return;
        $this->media_upload_types->add($type);
        $type->setSummit($this);
    }

    /**
     * @param SummitMediaUploadType $type
     */
    public function removeMediaUploadType(SummitMediaUploadType $type)
    {
        if (!$this->media_upload_types->contains($type)) return;
        $this->media_upload_types->removeElement($type);
        $type->clearSummit();
    }

    public function clearMediaUploadType()
    {
        $this->media_upload_types->clear();
    }

    public function getMediaUploadTypes()
    {
        return $this->media_upload_types;
    }

    /**
     * @param int $id
     * @return SummitMediaUploadType|null
     */
    public function getMediaUploadTypeById(int $id): ?SummitMediaUploadType
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', intval($id)));
        $type = $this->media_upload_types->matching($criteria)->first();
        return $type === false ? null : $type;
    }


    public function getMediaUploadsMandatoryCount(): int
    {
        try {
            $sql = <<<SQL
            SELECT COUNT(SummitMediaUploadType.ID) AS QTY
            FROM SummitMediaUploadType
            WHERE 
            SummitMediaUploadType.SummitID = :summit_id 
            AND SummitMediaUploadType.IsMandatory = 1
SQL;
            $stmt = $this->prepareRawSQL($sql);
            $stmt->execute([
                'summit_id' => $this->id,
            ]);
            $res = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            $count = count($res) > 0 ? $res[0] : 0;
        } catch (\Exception $ex) {
            $count = 0;
        }

        return $count;
    }

    /**
     * @param string $name
     * @return SummitMediaUploadType|null
     */
    public function getMediaUploadTypeByName(string $name): ?SummitMediaUploadType
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('name', trim($name)));
        $type = $this->media_upload_types->matching($criteria)->first();
        return $type === false ? null : $type;
    }

    /**
     * @return string|null
     */
    public function getVirtualSiteUrl(): ?string
    {
        return $this->virtual_site_url;
    }

    /**
     * @param string $virtual_site_url
     */
    public function setVirtualSiteUrl(?string $virtual_site_url): void
    {
        $this->virtual_site_url = $virtual_site_url;
    }

    /**
     * @return string|null
     */
    public function getMarketingSiteUrl(): ?string
    {
        return $this->marketing_site_url;
    }

    /**
     * @param string $marketing_site_url
     */
    public function setMarketingSiteUrl(?string $marketing_site_url): void
    {
        $this->marketing_site_url = $marketing_site_url;
    }

    /**
     * @return string
     */
    public function getVirtualSiteOAuth2ClientId(): ?string
    {
        return $this->virtual_site_oauth2_client_id;
    }

    /**
     * @param string $virtual_site_oauth2_client_id
     */
    public function setVirtualSiteOAuth2ClientId(?string $virtual_site_oauth2_client_id): void
    {
        $this->virtual_site_oauth2_client_id = $virtual_site_oauth2_client_id;
    }

    /**
     * @return string
     */
    public function getMarketingSiteOAuth2ClientId(): ?string
    {
        return $this->marketing_site_oauth2_client_id;
    }

    /**
     * @param string $marketing_site_oauth2_client_id
     */
    public function setMarketingSiteOAuth2ClientId(?string $marketing_site_oauth2_client_id): void
    {
        $this->marketing_site_oauth2_client_id = $marketing_site_oauth2_client_id;
    }

    /**
     * @return string|null
     */
    public function getSupportEmail(): ?string
    {
        return $this->support_email;
    }

    /**
     * @param string $support_email
     */
    public function setSupportEmail(?string $support_email): void
    {
        $this->support_email = $support_email;
    }

    /**
     * @return ArrayCollection|FeaturedSpeaker[]
     */
    public function getFeaturesSpeakers()
    {
        return $this->featured_speakers;
    }

    /**
     * @return ArrayCollection|FeaturedSpeaker[]
     */
    public function getOrderedFeaturedSpeakers()
    {
        $criteria = Criteria::create();
        $criteria->orderBy(['order' => 'ASC']);
        return $this->featured_speakers->matching($criteria);
    }

    public function clearFeaturedSpeakers()
    {
        $this->featured_speakers->clear();
    }

    /**
     * @param PresentationSpeaker $speaker
     * @return bool
     */
    public function isFeaturedSpeaker(PresentationSpeaker $speaker): bool
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('speaker', $speaker));
        return $this->featured_speakers->matching($criteria)->count() > 0;
    }

    /**
     * @param PresentationSpeaker $speaker
     * @return FeaturedSpeaker | null
     */
    public function getFeatureSpeaker(PresentationSpeaker $speaker): ?FeaturedSpeaker
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('speaker', $speaker));
        $res = $this->featured_speakers->matching($criteria)->first();
        return !$res ? null : $res;
    }

    /**
     * @return int
     */
    private function getFeaturedSpeakerMaxOrder(): int
    {
        $criteria = Criteria::create();
        $criteria->orderBy(['order' => 'DESC']);
        $speaker = $this->featured_speakers->matching($criteria)->first();
        return $speaker === false ? 0 : $speaker->getOrder();
    }

    /**
     * @param PresentationSpeaker $speaker
     * @return FeaturedSpeaker|null
     */
    public function addFeaturedSpeaker(PresentationSpeaker $speaker): ?FeaturedSpeaker
    {
        if ($this->isFeaturedSpeaker($speaker)) return null;
        $featureSpeaker = new FeaturedSpeaker($this, $speaker, $this->getFeaturedSpeakerMaxOrder() + 1);
        $this->featured_speakers->add($featureSpeaker);
        return $featureSpeaker;
    }

    /**
     * @param PresentationSpeaker $speaker
     */
    public function removeFeaturedSpeaker(PresentationSpeaker $speaker)
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('speaker', $speaker));
        $featured = $this->featured_speakers->matching($criteria)->first();
        if (!$featured) return;
        $this->featured_speakers->removeElement($featured);
        $featured->clearSummit();
        self::resetOrderForSelectable($this->featured_speakers);
    }

    /**
     * @param FeaturedSpeaker $speaker
     * @param int $new_order
     * @throws ValidationException
     */
    public function recalculateFeaturedSpeakerOrder(FeaturedSpeaker $speaker, $new_order)
    {
        self::recalculateOrderForSelectable($this->featured_speakers, $speaker, $new_order);
    }

    /**
     * @return array|DateTime[]
     */
    public function getSummitDays(): array
    {
        $beginDate = $this->getLocalBeginDate();
        $endDate = $this->getLocalEndDate();
        if (is_null($beginDate)) return [];
        if (is_null($endDate)) return [];
        $beginDate = $beginDate->setTime(0, 0, 0);
        $endDate = $endDate->setTime(0, 0, 0);
        $res = [];
        $res[] = clone $beginDate;
        while ($beginDate < $endDate) {
            $res[] = clone($beginDate->modify('+1 day'));
        }
        return $res;
    }

    /**
     * @return array|DateTime[]
     */
    public function getSummitDaysWithEvents(): array
    {
        $days = $this->getSummitDays();
        $list = [];
        foreach ($days as $day) {
            $begin = clone($day);
            $begin = $begin->setTime(0, 0, 0)->setTimezone(new \DateTimeZone('UTC'));
            $end = clone($day);
            $end = $end->setTime(23, 59, 59)->setTimezone(new \DateTimeZone('UTC'));
            $count = 0;
            try {
                $sql = <<<SQL
            SELECT COUNT(SummitEvent.ID) AS QTY
            FROM SummitEvent
            WHERE 
            SummitEvent.SummitID = :summit_id 
            AND SummitEvent.Published = 1
            AND (SummitEvent.StartDate <= :end and SummitEvent.EndDate >= :begin)
SQL;
                $stmt = $this->prepareRawSQL($sql);
                $stmt->execute([
                    'summit_id' => $this->id,
                    'begin' => $begin->format("Y-m-d H:i:s"),
                    'end' => $end->format('Y-m-d H:i:s'),
                ]);
                $res = $stmt->fetchAll(\PDO::FETCH_COLUMN);
                $count = count($res) > 0 ? $res[0] : 0;
            } catch (\Exception $ex) {
                Log::debug($ex);
                $count = 0;
            }
            if ($count > 0) {
                $list[] = $day;
            }
        }

        return $list;
    }

    /**
     * @return SummitMetric[]
     */
    public function getMetrics(): array
    {
        return $this->metrics;
    }

    /**
     * @param SummitMetric $metric
     */
    public function addMetric(SummitMetric $metric)
    {
        if ($this->metrics->contains($metric)) return;
        $this->metrics->add($metric);
        $metric->setSummit($this);
    }

    public function clearMetrics():void{
        $this->metrics->clear();
    }

    public function isPubliclyOpen(): bool
    {
        return $this->ticket_types->count() == 0;
    }

    /**
     * @return SummitTrackChair[]
     */
    public function getTrackChairs(): array
    {
        return $this->track_chairs;
    }

    /**
     * @param Member $member
     * @return SummitTrackChair|null
     */
    public function getTrackChairByMember(Member $member): ?SummitTrackChair
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('member', $member));
        $res = $this->track_chairs->matching($criteria)->first();
        return !$res ? null : $res;
    }

    /**
     * @param int $id
     * @return SummitTrackChair|null
     */
    public function getTrackChair(int $id): ?SummitTrackChair
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', $id));
        $res = $this->track_chairs->matching($criteria)->first();
        return !$res ? null : $res;
    }

    /**
     * @param Member $member
     * @param array|PresentationCategory[] $categories
     * @return SummitTrackChair|null
     * @throws ValidationException
     */
    public function addTrackChair(Member $member, array $categories): ?SummitTrackChair
    {

        if (!$member->isOnGroup(IGroup::TrackChairs)) {
            throw new ValidationException(sprintf("Member %s does not belong to group %s", $member->getId(), IGroup::TrackChairs));
        }

        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('member', $member));
        $trackChair = $this->track_chairs->matching($criteria)->first();

        if (!$trackChair) {
            $trackChair = new SummitTrackChair();
            $member->addTrackChair($trackChair);
            $trackChair->setSummit($this);
        }

        foreach ($categories as $category) {

            if (!$category->isChairVisible())
                throw new ValidationException
                (
                    sprintf("Category %s is not visible for track chairs.", $category->getId())
                );

            if ($trackChair->isCategoryAllowed($category))
                throw new ValidationException
                (
                    sprintf("Category %s is already allowed for member %s", $category->getId(), $member->getId())
                );

            $trackChair->addCategory($category);
        }

        if ($this->track_chairs->contains($trackChair)) return null;
        $this->track_chairs->add($trackChair);

        return $trackChair;
    }

    /**
     * @param SummitTrackChair $trackChair
     */
    public function removeTrackChair(SummitTrackChair $trackChair)
    {
        if (!$this->track_chairs->contains($trackChair)) return;
        $this->track_chairs->removeElement($trackChair);
    }

    /**
     * @param Member $member
     * @param PresentationCategory|null $category
     * @return bool
     */
    public function isTrackChair(Member $member, PresentationCategory $category = null): bool
    {
        if ($member->isAdmin()) return true;
        if ($this->isTrackChairAdmin($member)) return true;
        if ($this->isSummitAdmin($member)) return true;

        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('member', $member));
        $isOnGroup = $member->isOnGroup(IGroup::TrackChairs);
        $isTrackChair = $this->track_chairs->matching($criteria)->count() > 0;
        if (!is_null($category)) {
            $isTrackChair = $this->track_chairs->matching($criteria)->filter(function ($track_chair) use ($category) {
                    return $track_chair->isCategoryAllowed($category);
                })->count() > 0;
        }

        return ($isOnGroup && $isTrackChair);
    }

    public function hasPermissionOnSummit(Member $member): bool
    {
        return $this->permission_groups->filter(function ($group) use ($member) {
                if (!$group instanceof SummitAdministratorPermissionGroup) return false;
                return $group->hasMember($member);
            })->count() > 0;
    }

    /**
     * @param Member $member
     * @return bool
     */
    public function isTrackChairAdmin(Member $member): bool
    {
        if ($member->isAdmin()) return true;
        if ($this->isSummitAdmin($member)) return true;
        return $this->hasPermissionOnSummit($member) && $member->isOnGroup(IGroup::TrackChairsAdmins);
    }

    /**
     * @param Member $member
     * @return bool
     */
    public function isSummitAdmin(Member $member): bool
    {
        if ($member->isAdmin()) return true;
        return $this->hasPermissionOnSummit($member) && $member->isOnGroup(IGroup::SummitAdministrators);
    }

    /**
     * @param PresentationActionType $presentation_action_type
     * @return $this
     */
    public function addPresentationActionType(PresentationActionType $presentation_action_type)
    {
        if ($this->presentation_action_types->contains($presentation_action_type)) return $this;
        $this->presentation_action_types->add($presentation_action_type);
        $presentation_action_type->setSummit($this);
        return $this;
    }

    /**
     * @return ArrayCollection|\Doctrine\Common\Collections\Collection
     */
    public function getPresentationActionTypes()
    {
        return $this->presentation_action_types;
    }

    /**
     * @param PresentationActionType $presentation_action_type
     * @return $this
     */
    public function removePresentationActionType(PresentationActionType $presentation_action_type)
    {
        if (!$this->presentation_action_types->contains($presentation_action_type)) return $this;
        $this->presentation_action_types->removeElement($presentation_action_type);
        $presentation_action_type->clearSummit();
        return $this;
    }

    /**
     * @param int $action_id
     * @return PresentationActionType|null
     */
    public function getPresentationActionTypeById(int $action_id): ?PresentationActionType
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', $action_id));
        $action = $this->presentation_action_types->matching($criteria)->first();
        return $action === false ? null : $action;
    }

    /**
     * @param string $label
     * @return PresentationActionType|null
     */
    public function getPresentationActionTypeByLabel(string $label): ?PresentationActionType
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('label', trim($label)));
        $action = $this->presentation_action_types->matching($criteria)->first();
        return $action === false ? null : $action;
    }

    public function synchAllAttendeesStatus(): void
    {
        foreach ($this->attendees as $attendee) {
            $attendee->updateStatus();
        }
    }

    /**
     * @return bool
     */
    public function isRegistrationSendQrAsImageAttachmentOnTicketEmail(): bool
    {
        return $this->registration_send_qr_as_image_attachment_on_ticket_email;
    }

    /**
     * @param bool $registration_send_qr_as_image_attachment_on_ticket_email
     */
    public function setRegistrationSendQrAsImageAttachmentOnTicketEmail(bool $registration_send_qr_as_image_attachment_on_ticket_email): void
    {
        $this->registration_send_qr_as_image_attachment_on_ticket_email = $registration_send_qr_as_image_attachment_on_ticket_email;
    }

    /**
     * @return bool
     */
    public function isRegistrationSendTicketAsPdfAttachmentOnTicketEmail(): bool
    {
        return $this->registration_send_ticket_as_pdf_attachment_on_ticket_email;
    }

    /**
     * @param bool $registration_send_ticket_as_pdf_attachment_on_ticket_email
     */
    public function setRegistrationSendTicketAsPdfAttachmentOnTicketEmail(bool $registration_send_ticket_as_pdf_attachment_on_ticket_email): void
    {
        $this->registration_send_ticket_as_pdf_attachment_on_ticket_email = $registration_send_ticket_as_pdf_attachment_on_ticket_email;
    }

    /**
     * @return bool
     */
    public function isRegistrationSendTicketEmailAutomatically(): bool
    {
        return $this->registration_send_ticket_email_automatically;
    }

    /**
     * @param bool $registration_send_ticket_email_automatically
     */
    public function setRegistrationSendTicketEmailAutomatically(bool $registration_send_ticket_email_automatically): void
    {
        $this->registration_send_ticket_email_automatically = $registration_send_ticket_email_automatically;
    }

    /**
     * @return bool
     */
    public function isRegistrationSendOrderEmailAutomatically(): bool
    {
        return $this->registration_send_order_email_automatically;
    }

    /**
     * @param bool $registration_send_order_email_automatically
     */
    public function setRegistrationSendOrderEmailAutomatically(bool $registration_send_order_email_automatically): void
    {
        $this->registration_send_order_email_automatically = $registration_send_order_email_automatically;
    }

    /**
     * @return string
     */
    public function getModality(): string
    {
        $has_virtual = false;
        $has_in_person = false;

        foreach ($this->badge_types as $badge_type) {
            foreach ($badge_type->getAccessLevels() as $accessLevel) {
                if ($accessLevel->getName() == SummitAccessLevelType::VIRTUAL)
                    $has_virtual = true;
                if ($accessLevel->getName() == SummitAccessLevelType::IN_PERSON)
                    $has_in_person = true;
            }
        }

        if ($has_virtual && $has_in_person)
            return ISummitModality::Hybrid;
        if ($has_virtual)
            return ISummitModality::Virtual;
        if ($has_in_person)
            return ISummitModality::InPerson;
        return ISummitModality::None;
    }

    /**
     * @return bool
     */
    public function isAllowUpdateAttendeeExtraQuestions(): bool
    {
        return $this->allow_update_attendee_extra_questions;
    }

    /**
     * @param bool $allow_update_attendee_extra_questions
     */
    public function setAllowUpdateAttendeeExtraQuestions(bool $allow_update_attendee_extra_questions): void
    {
        $this->allow_update_attendee_extra_questions = $allow_update_attendee_extra_questions;
    }

    /**
     * @return bool
     */
    public function isRegistrationAllowAutomaticReminderEmails(): bool
    {
        return $this->registration_allow_automatic_reminder_emails;
    }

    /**
     * @param bool $registration_allow_automatic_reminder_emails
     */
    public function setAllowAutomaticReminderEmails(bool $registration_allow_automatic_reminder_emails): void
    {
        $this->registration_allow_automatic_reminder_emails = $registration_allow_automatic_reminder_emails;
    }

    /**
     * @return string|null
     */
    public function getTimeZoneLabel(): ?string
    {
        return $this->time_zone_label;
    }

    /**
     * @param string $time_zone_label
     */
    public function setTimeZoneLabel(string $time_zone_label): void
    {
        $this->time_zone_label = $time_zone_label;
    }

    /**
     * @return ArrayCollection|SummitScheduleConfig[]
     */
    public function getScheduleSettings()
    {
        return $this->schedule_settings;
    }

    /**
     * @return ArrayCollection|SummitScheduleConfig[]
     */
    public function getEnableScheduleSettings()
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('is_enabled', true));
        return $this->schedule_settings->matching($criteria);
    }

    /**
     * @param SummitScheduleConfig $scheduleConfig
     * @throws ValidationException
     */
    public function addScheduleSetting(SummitScheduleConfig $scheduleConfig)
    {
        if ($this->schedule_settings->contains($scheduleConfig)) return;
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('key', trim($scheduleConfig->getKey())));
        if ($this->schedule_settings->matching($criteria)->count() > 0)
            throw new ValidationException(sprintf("Key %s already exists", $scheduleConfig->getKey()));
        $this->schedule_settings->add($scheduleConfig);
        $scheduleConfig->setSummit($this);
    }

    public function removeScheduleSetting(SummitScheduleConfig $scheduleConfig)
    {
        if (!$this->schedule_settings->contains($scheduleConfig)) return;
        $this->schedule_settings->removeElement($scheduleConfig);
        $scheduleConfig->clearFilters();
        $scheduleConfig->clearSummit();
    }

    /**
     * @param int $id
     * @return SummitScheduleConfig|null
     */
    public function getScheduleSettingById(int $id): ?SummitScheduleConfig
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', intval($id)));
        $res = $this->schedule_settings->matching($criteria)->first();
        return $res === false ? null : $res;
    }

    /**
     * @return Company[]
     */
    public function getRegistrationCompanies()
    {
        return $this->registration_companies;
    }

    /**
     * @param int $id
     * @return Company|null
     */
    public function getRegistrationCompanyById(int $id): ?Company
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', $id));
        $res = $this->registration_companies->matching($criteria)->first();
        return $res === false ? null : $res;
    }

    /**
     * @param string $name
     * @return Company|null
     */
    public function getRegistrationCompanyByName(string $name): ?Company
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('name', trim($name)));
        $res = $this->registration_companies->matching($criteria)->first();
        return $res === false ? null : $res;
    }

    /**
     * @param Company $registrationCompany
     */
    public function addRegistrationCompany(Company $registrationCompany)
    {
        if ($this->registration_companies->contains($registrationCompany)) return;
        $this->registration_companies->add($registrationCompany);
    }

    /**
     * @param Company $registrationCompany
     */
    public function removeRegistrationCompany(Company $registrationCompany)
    {
        if (!$this->registration_companies->contains($registrationCompany)) return;
        $this->registration_companies->removeElement($registrationCompany);
    }

    /*
     * @return DateTime
     */
    public function getExternalRegistrationFeedLastIngestDate(): ?DateTime
    {
        return $this->external_registration_feed_last_ingest_date;
    }

    /**
     * @throws \Exception
     */
    public function markExternalRegistrationFeedLastIngestDate(): void
    {
        $utcNow = new DateTime('now', new \DateTimeZone('UTC'));
        // subtract skew time
        $utcNow->sub(new \DateInterval('PT15M'));
        $this->external_registration_feed_last_ingest_date = $utcNow;
    }

    public function addAnnouncementSummitEmail(SpeakerAnnouncementSummitEmail $announcementSummitEmail)
    {
        if ($this->speakers_announcement_emails->contains($announcementSummitEmail)) return;
        $this->speakers_announcement_emails->add($announcementSummitEmail);
        $announcementSummitEmail->setSummit($this);
    }

    public function removeAnnouncementSummitEmail(SpeakerAnnouncementSummitEmail $announcementSummitEmail)
    {
        if (!$this->speakers_announcement_emails->contains($announcementSummitEmail)) return;
        $this->speakers_announcement_emails->removeElement($announcementSummitEmail);
        $announcementSummitEmail->clearSummit();
    }

    public function isInviteOnlyRegistration(): bool
    {
        $hasAll = $this->getTicketTypesByAudience(SummitTicketType::Audience_All)->count() > 0;
        $hasWithInvitation = $this->getTicketTypesByAudience(SummitTicketType::Audience_With_Invitation)->count() > 0;
        $hasWithoutInvitation = $this->getTicketTypesByAudience(SummitTicketType::Audience_Without_Invitation)->count() > 0;
        if ($hasWithInvitation && !$hasAll && !$hasWithoutInvitation) return true;
        return false;
    }

    /**
     * @param SummitTicketType $ticketType
     * @param string $emailOwner
     * @return int
     */
    public function getTicketCountByTypeAndOwnerEmail(SummitTicketType $ticketType, string $emailOwner): int
    {
        try {
            $sql = <<<SQL
            SELECT COUNT(DISTINCT(T.ID)) AS ticket_count
            FROM SummitAttendeeTicket AS T
            INNER JOIN SummitAttendee AS A ON A.ID = T.OwnerID   
            LEFT JOIN Member AS M ON M.ID = A.MemberID    
            INNER JOIN SummitTicketType AS TT ON TT.ID = T.TicketTypeID
            INNER JOIN SummitOrder AS O ON O.ID = T.OrderID
            WHERE 
                  O.SummitID = :summit_id 
                  AND TT.ID = :type_id
                  AND ( A.Email = :email OR M.Email = :email )
SQL;
            $stmt = $this->prepareRawSQL($sql);
            $stmt->execute([
                'summit_id' => $this->id,
                'type_id' => $ticketType->getId(),
                'email' => strtolower(trim($emailOwner))
            ]);
            $res = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            return count($res) > 0 ? intval($res[0]) : 0;
        } catch (\Exception $ex) {
            return 0;
        }
    }

    public function getBadgeViewTypes(){
        return $this->badge_view_types;
    }

    /**
     * @return SummitBadgeViewType|null
     */
    public function getDefaultBadgeViewType():?SummitBadgeViewType{
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('default', true));
        $res = $this->badge_view_types->matching($criteria)->first();
        return $res === false ? null : $res;
    }

    /**
     * @param string $name
     * @return SummitBadgeViewType|null
     */
    public function getBadgeViewTypeByName(string $name):?SummitBadgeViewType{
        $res = $this->badge_view_types->filter(function($e) use($name){
            return $e instanceof SummitBadgeViewType && strtolower($e->getName()) == strtolower(trim($name));
        })->first();
        return $res === false ? null : $res;
    }

    /**
     * @param int $id
     * @return SummitBadgeViewType|null
     */
    public function getBadgeViewTypeById(int $id):?SummitBadgeViewType{
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', $id));
        $res = $this->badge_view_types->matching($criteria)->first();
        return $res === false ? null : $res;
    }

    /**
     * @param SummitBadgeViewType $viewType
     */
    public function addBadgeViewType(SummitBadgeViewType $viewType){
        if($this->badge_view_types->contains($viewType)) return;
        if(!is_null($this->getBadgeViewTypeByName($viewType->getName()))) return;
        $this->badge_view_types->add($viewType);
        $viewType->setSummit($this);
    }

    /**
     * @param SummitBadgeViewType $viewType
     */
    public function removeBadgeViewType(SummitBadgeViewType $viewType){
        if(!$this->badge_view_types->contains($viewType)) return;
        $this->badge_view_types->removeElement($viewType);
        $viewType->clearSummit();;
    }

    /**
     * @return DateTime
     */
    public function getRegistrationAllowedRefundRequestTillDate(): ?DateTime
    {
        return $this->registration_allowed_refund_request_till_date;
    }

    /**
     * @param DateTime $registration_allowed_refund_request_till_date
     */
    public function setRegistrationAllowedRefundRequestTillDate(?DateTime $registration_allowed_refund_request_till_date): void
    {
        $this->registration_allowed_refund_request_till_date =  $this->convertDateFromTimeZone2UTC($registration_allowed_refund_request_till_date);
    }

    public function clearRegistrationAllowedRefundRequestTillDate():void{
        $this->registration_allowed_refund_request_till_date = null;
    }

    /**
     * @param Member $member
     * @return bool
     * @throws \Exception
     */
    public function canEmitRefundRequests(Member $member):bool{
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        return !is_null($this->registration_allowed_refund_request_till_date)
            && $this->registration_allowed_refund_request_till_date >= $now;
    }

    use SummitRegistrationStats;

    /**
     * @return int
     */
    private function getSponsorShipTypeMaxOrder(): int
    {
        $criteria = Criteria::create();
        $criteria->orderBy(['order' => 'DESC']);
        $type = $this->sponsorship_types->matching($criteria)->first();
        return $type === false ? 0 : $type->getOrder();
    }

    /**
     * @param SummitSponsorshipType $type
     * @param int $new_order
     * @throws ValidationException
     */
    public function recalculateSponsorShipTypeOrder(SummitSponsorshipType $type, $new_order)
    {
        self::recalculateOrderForSelectable($this->sponsorship_types, $type, $new_order);
    }

    /**
     * @param SummitSponsorshipType $type
     */
    public function addSponsorshipType(SummitSponsorshipType $type){
        if($this->sponsorship_types->contains($type)) return;
        $type->setOrder($this->getSponsorShipTypeMaxOrder() + 1);
        $this->sponsorship_types->add($type);
        $type->setSummit($this);
    }

    /**
     * @param SummitSponsorshipType $type
     */
    public function removeSponsorshipType(SummitSponsorshipType $type){
        if(!$this->sponsorship_types->contains($type)) return;
        $this->sponsorship_types->removeElement($type);
        $type->clearSummit();
        self::resetOrderForSelectable($this->sponsorship_types);
    }

    use ScheduleEntity;

    /**
     * @param int $question_id
     * @return SummitSelectionPlanExtraQuestionType|null
     */
    public function getSelectionPlanExtraQuestionById(int $question_id): ?SummitSelectionPlanExtraQuestionType
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', $question_id));
        $question = $this->selection_plan_extra_questions->matching($criteria)->first();
        return $question === false ? null : $question;
    }

    /**
     * @param string $name
     * @return SummitSelectionPlanExtraQuestionType|null
     */
    public function getSelectionPlanExtraQuestionByName(string $name): ?SummitSelectionPlanExtraQuestionType
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('name', trim($name)));
        $question = $this->selection_plan_extra_questions->matching($criteria)->first();
        return $question === false ? null : $question;
    }

    /**
     * @param string $label
     * @return SummitSelectionPlanExtraQuestionType|null
     */
    public function getSelectionPlanExtraQuestionByLabel(string $label): ?SummitSelectionPlanExtraQuestionType
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('label', trim($label)));
        $question = $this->selection_plan_extra_questions->matching($criteria)->first();
        return $question === false ? null : $question;
    }

    /**
     * @param SummitSelectionPlanExtraQuestionType $question
     */
    public function addSelectionPlanExtraQuestion(SummitSelectionPlanExtraQuestionType $question)
    {
        if ($this->selection_plan_extra_questions->contains($question)) return;
        $this->selection_plan_extra_questions->add($question);
        $question->setSummit($this);
    }

    public function removeSelectionPlanExtraQuestion(SummitSelectionPlanExtraQuestionType $question)
    {
        if (!$this->selection_plan_extra_questions->contains($question)) return;
        $this->selection_plan_extra_questions->removeElement($question);
        $question->clearSummit();
    }

    public function parseDateTime(int $epoch):?Datetime
    {
        $datetime = new \DateTime("@$epoch");
        $datetime->setTimezone($this->getTimeZone());
        return $datetime;
    }

    /**
     * @return ArrayCollection
     */
    public function getSigns(): ArrayCollection
    {
        return $this->signs;
    }

    /**
     * @param int $sign_id
     * @return SummitSign|null
     */
    public function getSignById(int $sign_id):?SummitSign{
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', $sign_id));
        $sign = $this->signs->matching($criteria)->first();
        return $sign === false ? null : $sign;
    }

    /**
     * @param int $location_id
     * @return SummitSign|null
     */
    public function getSignByLocationId(int $location_id):?SummitSign{
        $location = $this->getLocation($location_id);
        if(is_null($location)) return null;
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('location', $location));
        $sign = $this->signs->matching($criteria)->first();
        return $sign === false ? null : $sign;
    }

    /**
     * @param SummitSign $sign
     * @return void
     */
    public function addSign(SummitSign $sign):void{
        if($this->signs->contains($sign)) return;
        $this->signs->add($sign);
        $sign->setSummit($this);
    }

    /**
     * @param PresentationCategory $track
     * @return bool
     */
    public function hasRelatedActivities(PresentationCategory $track): bool{
        $criteria = new Criteria();
        return $this->events->matching
            (
                $criteria->where($criteria->expr()->eq('category', $track)
            ))->count() > 0;
    }

    /**
     * @return string|null
     */
    public function getQRCodesEncKey():?string {
        return $this->qr_codes_enc_key;
    }
    public function hasQRCodesEncKey():bool{
        return !empty($this->qr_codes_enc_key);
    }

    /**
     * @param string $qr_codes_enc_key
     */
    public function setQRCodesEncKey(string $qr_codes_enc_key) {
        $this->qr_codes_enc_key = $qr_codes_enc_key;
    }

    public function clearQRCodesEncKey() {
        $this->qr_codes_enc_key = null;
    }

    /**
     * @return string
     */
    public function getMuxTokenId(): ?string
    {
        return $this->mux_token_id;
    }

    /**
     * @param string $mux_token_id
     */
    public function setMuxTokenId(string $mux_token_id): void
    {
        $this->mux_token_id = $mux_token_id;
    }

    /**
     * @return string
     */
    public function getMuxTokenSecret(): ?string
    {
        return $this->mux_token_secret;
    }

    /**
     * @param string $mux_token_secret
     */
    public function setMuxTokenSecret(string $mux_token_secret): void
    {
        $this->mux_token_secret = $mux_token_secret;
    }

    /**
     * @return string
     */
    public function getMuxPrivateKeyId(): ?string
    {
        return $this->mux_private_key_id;
    }

    /**
     * @param string $mux_private_key_id
     */
    public function setMuxPrivateKeyId(string $mux_private_key_id): void
    {
        $this->mux_private_key_id = $mux_private_key_id;
    }

    /**
     * @return string
     */
    public function getMuxPrivateKey(): ?string
    {
        return $this->mux_private_key;
    }

    /**
     * @param string $mux_private_key
     */
    public function setMuxPrivateKey(string $mux_private_key): void
    {
        $this->mux_private_key = $mux_private_key;
    }

    public function hasMuxPrivateKey():bool{
        return !empty($this->mux_private_key);
    }

    /**
     * @return string
     */
    public function getMuxPlaybackRestrictionId(): ?string
    {
        return $this->mux_playback_restriction_id;
    }

    /**
     * @param string $mux_playback_restriction_id
     */
    public function setMuxPlaybackRestrictionId(string $mux_playback_restriction_id): void
    {
        $this->mux_playback_restriction_id = $mux_playback_restriction_id;

        Cache::tags(sprintf('secure_streams_%s', $this->id))->flush();
    }

    public function clearMuxPlaybackRestrictionId(): void
    {
        $this->mux_playback_restriction_id = null;

        Cache::tags(sprintf('secure_streams_%s', $this->id))->flush();
    }

    /**
     * @return string
     */
    public function getMuxAllowedDomains(): array
    {
        if(empty($this->mux_allowed_domains)) return [];
        return explode('|', $this->mux_allowed_domains);
    }

    /**
     * @param array $mux_allowed_domains
     */
    public function setMuxAllowedDomains(array $mux_allowed_domains): void
    {
        Log::debug
        (
            sprintf
            (
                "Summit::setMuxAllowedDomains summit %s mux_allowed_domains %s",
                $this->getId(),
                json_encode($mux_allowed_domains)
            )
        );

        $this->mux_allowed_domains = implode('|', $mux_allowed_domains);
        if(!empty($this->mux_token_id) && !empty($this->mux_token_secret))
            CreateMUXPlaybackRestrictionForSummit::dispatch($this->id);
    }

    public function getRegistrationFeedMetadata():array{
        $res = [];
        foreach($this->registration_feed_metadata as $metadata){
            $res[$metadata->getKey()] = $metadata->getValue();
        }
        return $res;
    }

    /**
     * @param string $key
     * @param string $value
     * @return SummitRegistrationFeedMetadata
     * @throws ValidationException
     */
    public function addRegistrationFeedMetadata(string $key, string $value):SummitRegistrationFeedMetadata{
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('key', trim($key)));
        if($this->registration_feed_metadata->matching($criteria)->count()){
            throw new ValidationException(sprintf("key %s already exists.", $key));
        }
        $metadata = new SummitRegistrationFeedMetadata($key, $value);
        $this->registration_feed_metadata->add($metadata);
        $metadata->setSummit($this);
        return $metadata;
    }

    /**
     * @param int $metadata_id
     * @return SummitRegistrationFeedMetadata|null
     */
    public function getRegistrationFeedMetadataById(int $metadata_id):?SummitRegistrationFeedMetadata{
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', $metadata_id));
        $res = $this->registration_feed_metadata->matching($criteria)->first();
        return $res ? $res : null;
    }

    /**
     * @param string $key
     * @return SummitRegistrationFeedMetadata|null
     */
    public function getRegistrationFeedMetadataByKey(string $key):?SummitRegistrationFeedMetadata{
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('key', trim($key)));
        $res = $this->registration_feed_metadata->matching($criteria)->first();
        return $res ? $res : null;
    }

    /**
     * @param SummitRegistrationFeedMetadata $metadata
     * @return void
     */
    public function removeRegistrationFeedMetadata(SummitRegistrationFeedMetadata $metadata):void{
        if(!$this->registration_feed_metadata->contains($metadata)) return;
        $this->registration_feed_metadata->removeElement($metadata);
        $metadata->clearSummit();
    }

}
