<?php namespace ModelSerializers;
/**
 * Copyright 2016 OpenStack Foundation
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

use App\ModelSerializers\Audit\SummitAttendeeBadgeAuditLogSerializer;
use App\ModelSerializers\Audit\SummitAuditLogSerializer;
use App\ModelSerializers\Audit\SummitEventAuditLogSerializer;
use App\ModelSerializers\CCLA\TeamSerializer;
use App\ModelSerializers\Elections\CandidateSerializer;
use App\ModelSerializers\Elections\ElectionSerializer;
use App\ModelSerializers\Elections\NominationSerializer;
use App\ModelSerializers\FileSerializer;
use App\ModelSerializers\IMemberSerializerTypes;
use App\ModelSerializers\ISummitAttendeeTicketSerializerTypes;
use App\ModelSerializers\ISummitOrderSerializerTypes;
use App\ModelSerializers\LanguageSerializer;
use App\ModelSerializers\Locations\SummitBookableVenueRoomAttributeTypeSerializer;
use App\ModelSerializers\Locations\SummitBookableVenueRoomAttributeValueSerializer;
use App\ModelSerializers\Locations\SummitBookableVenueRoomAvailableSlotSerializer;
use App\ModelSerializers\Locations\SummitBookableVenueRoomSerializer;
use App\ModelSerializers\Locations\SummitRoomReservationCSVSerializer;
use App\ModelSerializers\Locations\SummitRoomReservationSerializer;
use App\ModelSerializers\Marketplace\ApplianceSerializer;
use App\ModelSerializers\Marketplace\CloudServiceOfferedSerializer;
use App\ModelSerializers\Marketplace\ConfigurationManagementTypeSerializer;
use App\ModelSerializers\Marketplace\ConsultantClientSerializer;
use App\ModelSerializers\Marketplace\ConsultantSerializer;
use App\ModelSerializers\Marketplace\ConsultantServiceOfferedTypeSerializer;
use App\ModelSerializers\Marketplace\DataCenterLocationSerializer;
use App\ModelSerializers\Marketplace\DataCenterRegionSerializer;
use App\ModelSerializers\Marketplace\DistributionSerializer;
use App\ModelSerializers\Marketplace\GuestOSTypeSerializer;
use App\ModelSerializers\Marketplace\HyperVisorTypeSerializer;
use App\ModelSerializers\Marketplace\MarketPlaceReviewSerializer;
use App\ModelSerializers\Marketplace\OfficeSerializer;
use App\ModelSerializers\Marketplace\OpenStackImplementationApiCoverageSerializer;
use App\ModelSerializers\Marketplace\PricingSchemaTypeSerializer;
use App\ModelSerializers\Marketplace\PrivateCloudServiceSerializer;
use App\ModelSerializers\Marketplace\PublicCloudServiceSerializer;
use App\ModelSerializers\Marketplace\RegionalSupportSerializer;
use App\ModelSerializers\Marketplace\RegionSerializer;
use App\ModelSerializers\Marketplace\RemoteCloudServiceSerializer;
use App\ModelSerializers\Marketplace\ServiceOfferedTypeSerializer;
use App\ModelSerializers\Marketplace\SpokenLanguageSerializer;
use App\ModelSerializers\Marketplace\SupportChannelTypeSerializer;
use App\ModelSerializers\PushNotificationMessageSerializer;
use App\ModelSerializers\ResourceServer\ApiEndpointAuthzGroupSerializer;
use App\ModelSerializers\ResourceServer\ApiEndpointSerializer;
use App\ModelSerializers\ResourceServer\ApiScopeSerializer;
use App\ModelSerializers\ResourceServer\ApiSerializer;
use App\ModelSerializers\Software\OpenStackComponentSerializer;
use App\ModelSerializers\Software\OpenStackReleaseComponentSerializer;
use App\ModelSerializers\Software\OpenStackReleaseSerializer;
use App\ModelSerializers\Summit\AdminLawPayPaymentProfileSerializer;
use App\ModelSerializers\Summit\AdminStripePaymentProfileSerializer;
use App\ModelSerializers\Summit\AdminSummitAttendeeNoteCSVSerializer;
use App\ModelSerializers\Summit\AdminSummitAttendeeNoteSerializer;
use App\ModelSerializers\Summit\AdminSummitSerializer;
use App\ModelSerializers\Summit\AssignedSelectionPlanExtraQuestionTypeSerializer;
use App\ModelSerializers\Summit\LawPayPaymentProfileSerializer;
use App\ModelSerializers\Summit\PersonalCalendarShareInfoSerializer;
use App\ModelSerializers\Summit\Presentation\SummitPresentationCommentSerializer;
use App\ModelSerializers\Summit\Presentation\TrackQuestions\TrackAnswerSerializer;
use App\ModelSerializers\Summit\Presentation\TrackQuestions\TrackDropDownQuestionTemplateSerializer;
use App\ModelSerializers\Summit\Presentation\TrackQuestions\TrackLiteralContentQuestionTemplateSerializer;
use App\ModelSerializers\Summit\Presentation\TrackQuestions\TrackMultiValueQuestionTemplateSerializer;
use App\ModelSerializers\Summit\Presentation\TrackQuestions\TrackQuestionValueTemplateSerializer;
use App\ModelSerializers\Summit\Presentation\TrackQuestions\TrackSingleValueTemplateQuestionSerializer;
use App\ModelSerializers\Summit\ProposedSchedule\SummitProposedScheduleAllowedDaySerializer;
use App\ModelSerializers\Summit\ProposedSchedule\SummitProposedScheduleAllowedLocationSerializer;
use App\ModelSerializers\Summit\ProposedSchedule\SummitProposedScheduleLockSerializer;
use App\ModelSerializers\Summit\ProposedSchedule\SummitProposedScheduleSerializer;
use App\ModelSerializers\Summit\ProposedSchedule\SummitProposedScheduleSummitEventSerializer;
use App\ModelSerializers\Summit\Registration\Refunds\SummitAttendeeTicketRefundRequestSerializer;
use App\ModelSerializers\Summit\Registration\SponsorUserInfoGrantCSVSerializer;
use App\ModelSerializers\Summit\Registration\SummitAttendeeCSVSerializer;
use App\ModelSerializers\Summit\Registration\SummitAttendeeTicketCSVSerializer;
use App\ModelSerializers\Summit\Registration\SummitRegistrationFeedMetadataSerializer;
use App\ModelSerializers\Summit\RSVP\Templates\RSVPDropDownQuestionTemplateSerializer;
use App\ModelSerializers\Summit\RSVP\Templates\RSVPLiteralContentQuestionTemplateSerializer;
use App\ModelSerializers\Summit\RSVP\Templates\RSVPMultiValueQuestionTemplateSerializer;
use App\ModelSerializers\Summit\RSVP\Templates\RSVPQuestionValueTemplateSerializer;
use App\ModelSerializers\Summit\RSVP\Templates\RSVPSingleValueTemplateQuestionSerializer;
use App\ModelSerializers\Summit\RSVPTemplateSerializer;
use App\ModelSerializers\Summit\ScheduledSummitLocationBannerSerializer;
use App\ModelSerializers\Summit\SelectionPlanAllowedMemberSerializer;
use App\ModelSerializers\Summit\SponsorAdSerializer;
use App\ModelSerializers\Summit\SponsorBadgeScanCSVSerializer;
use App\ModelSerializers\Summit\SponsorBadgeScanSerializer;
use App\ModelSerializers\Summit\SponsorMaterialSerializer;
use App\ModelSerializers\Summit\SponsorSocialNetworkSerializer;
use App\ModelSerializers\Summit\SponsorUserInfoGrantSerializer;
use App\ModelSerializers\Summit\StripePaymentProfileSerializer;
use App\ModelSerializers\Summit\SummitAttendeeBadgeSerializer;
use App\ModelSerializers\Summit\SummitAttendeeNoteSerializer;
use App\ModelSerializers\Summit\SummitEmailEventFlowSerializer;
use App\ModelSerializers\Summit\SummitEventSecureStreamSerializer;
use App\ModelSerializers\Summit\SummitLocationBannerSerializer;
use App\ModelSerializers\Summit\SummitScheduleConfigSerializer;
use App\ModelSerializers\Summit\SummitSchedulePreFilterElementConfigSerializer;
use App\ModelSerializers\Summit\SummitSignSerializer;
use App\ModelSerializers\Summit\SummitSponsorshipTypeSerializer;
use App\ModelSerializers\Summit\TrackTagGroups\TrackTagGroupAllowedTagSerializer;
use App\ModelSerializers\Summit\TrackTagGroups\TrackTagGroupSerializer;
use App\ModelSerializers\SummitScheduleFilterElementConfigSerializer;
use Illuminate\Support\Facades\App;
use Libs\ModelSerializers\IModelSerializer;
use models\oauth2\IResourceServerContext;
use ModelSerializers\ChatTeams\ChatTeamInvitationSerializer;
use ModelSerializers\ChatTeams\ChatTeamMemberSerializer;
use ModelSerializers\ChatTeams\ChatTeamPushNotificationMessageSerializer;
use ModelSerializers\ChatTeams\ChatTeamSerializer;
use ModelSerializers\Locations\SummitAirportSerializer;
use ModelSerializers\Locations\SummitExternalLocationSerializer;
use ModelSerializers\Locations\SummitHotelSerializer;
use ModelSerializers\Locations\SummitLocationImageSerializer;
use ModelSerializers\Locations\SummitVenueFloorSerializer;
use ModelSerializers\Locations\SummitVenueRoomSerializer;
use ModelSerializers\Locations\SummitVenueSerializer;

/**
 * Class SerializerRegistry
 * @package ModelSerializers
 */
final class SerializerRegistry
{
    /**
     * @var SerializerRegistry
     */
    private static $instance;

    /**
     * @var IResourceServerContext
     */
    private $resource_server_context;

    const SerializerType_Public = 'PUBLIC';
    const SerializerType_Private = 'PRIVATE';
    const SerializerType_Admin = 'ADMIN';
    const SerializerType_Admin_Voteable = 'ADMIN_VOTEABLE';
    const SerializerType_Admin_Voteable_CSV = "ADMIN_VOTEABLE_CSV";
    const SerializerType_CSV = 'CSV';
    const SerializerType_Admin_Registration_Stats = 'ADMIN_REG_STATS';

    private function __clone()
    {
    }

    /**
     * @return SerializerRegistry
     */
    public static function getInstance()
    {
        if (!is_object(self::$instance)) {
            self::$instance = new SerializerRegistry();
        }
        return self::$instance;
    }

    private $registry = [];

    private function __construct()
    {
        $this->resource_server_context = App::make(IResourceServerContext::class);
        // resource server config
        $this->registry['Api'] = ApiSerializer::class;
        $this->registry['ApiEndpoint'] = ApiEndpointSerializer::class;
        $this->registry['ApiScope'] = ApiScopeSerializer::class;
        $this->registry['ApiEndpointAuthzGroup'] = ApiEndpointAuthzGroupSerializer::class;

        //Audit log

        $this->registry['SummitAuditLog'] = SummitAuditLogSerializer::class;
        $this->registry['SummitEventAuditLog'] = SummitEventAuditLogSerializer::class;
        $this->registry['SummitAttendeeBadgeAuditLog'] = SummitAttendeeBadgeAuditLogSerializer::class;

        // elections

        $this->registry['Election'] = ElectionSerializer::class;
        $this->registry['Candidate'] = CandidateSerializer::class;
        $this->registry['Nomination'] = NominationSerializer::class;

        // extra questions base
        $this->registry['ExtraQuestionTypeValue'] = ExtraQuestionTypeValueSerializer::class;
        $this->registry['SubQuestionRule'] = SubQuestionRuleSerializer::class;
        // metrics

        $this->registry['SummitMetric'] = SummitMetricSerializer::class;
        $this->registry['SummitSponsorMetric'] = SummitSponsorMetricSerializer::class;
        $this->registry['SummitEventAttendanceMetric'] = SummitEventAttendanceMetricSerializer::class;

        // stripe
        $this->registry['StripePaymentProfile'] = [
            self::SerializerType_Public => StripePaymentProfileSerializer::class,
            self::SerializerType_Private => AdminStripePaymentProfileSerializer::class,
        ];

        // law pay

        $this->registry['LawPayPaymentProfile'] = [
            self::SerializerType_Public => LawPayPaymentProfileSerializer::class,
            self::SerializerType_Private => AdminLawPayPaymentProfileSerializer::class,
        ];

        $this->registry['SummitAdministratorPermissionGroup'] = SummitAdministratorPermissionGroupSerializer::class;

        $this->registry['Summit'] =
            [
                self::SerializerType_Public => SummitSerializer::class,
                self::SerializerType_Private => AdminSummitSerializer::class,
                self::SerializerType_Admin_Registration_Stats => SummitRegistrationStatsSerializer::class,
                SummitQREncKeySerializer::SerializerType => SummitQREncKeySerializer::class
            ];

        $this->registry['SummitScheduleConfig'] = [
            self::SerializerType_Public => SummitScheduleConfigSerializer::class,
            self::SerializerType_Private => AdminSummitScheduleConfigSerializer::class,
        ];

        $this->registry['SummitScheduleFilterElementConfig'] = [
            self::SerializerType_Public => SummitScheduleFilterElementConfigSerializer::class,
            self::SerializerType_Private => AdminSummitScheduleFilterElementConfigSerializer::class
        ];

        $this->registry['SummitSchedulePreFilterElementConfig'] = [
            self::SerializerType_Public => SummitSchedulePreFilterElementConfigSerializer::class,
            self::SerializerType_Private => AdminSummitSchedulePreFilterElementConfigSerializer::class
        ];

        $this->registry['SummitDocument'] = SummitDocumentSerializer::class;
        $this->registry['SummitEmailEventFlow'] = SummitEmailEventFlowSerializer::class;
        $this->registry['SelectionPlan'] = SelectionPlanSerializer::class;
        $this->registry['SummitSelectionPlanExtraQuestionType'] = SummitSelectionPlanExtraQuestionTypeSerializer::class;
        $this->registry['AssignedSelectionPlanExtraQuestionType'] = AssignedSelectionPlanExtraQuestionTypeSerializer::class;
        $this->registry['SelectionPlanAllowedMember'] = SelectionPlanAllowedMemberSerializer::class;
        
        $this->registry['SummitWIFIConnection'] = SummitWIFIConnectionSerializer::class;
        $this->registry['SummitType'] = SummitTypeSerializer::class;
        $this->registry['SummitEventType'] = SummitEventTypeSerializer::class;
        $this->registry['PresentationType'] = PresentationTypeSerializer::class;
        $this->registry['SummitTicketType'] = SummitTicketTypeSerializer::class;
        $this->registry['SummitTicketTypePrePaid'] = SummitTicketTypePrePaidSerializer::class;
        $this->registry['SummitTicketTypeWithPromo'] = SummitTicketTypeWithPromoSerializer::class;
        $this->registry['PresentationCategory'] = PresentationCategorySerializer::class;
        $this->registry['PresentationCategoryGroup'] = PresentationCategoryGroupSerializer::class;
        $this->registry['PrivatePresentationCategoryGroup'] = PrivatePresentationCategoryGroupSerializer::class;
        $this->registry['Tag'] = TagSerializer::class;
        $this->registry['Language'] = LanguageSerializer::class;
        $this->registry['PresentationExtraQuestionAnswer'] = PresentationExtraQuestionAnswerSerializer::class;
        // track questions
        $this->registry['TrackAnswer'] = TrackAnswerSerializer::class;
        $this->registry['TrackQuestionValueTemplate'] = TrackQuestionValueTemplateSerializer::class;
        $this->registry['TrackTextBoxQuestionTemplate'] = TrackSingleValueTemplateQuestionSerializer::class;
        $this->registry['TrackCheckBoxQuestionTemplate'] = TrackSingleValueTemplateQuestionSerializer::class;
        $this->registry['TrackDropDownQuestionTemplate'] = TrackDropDownQuestionTemplateSerializer::class;
        $this->registry['TrackCheckBoxListQuestionTemplate'] = TrackMultiValueQuestionTemplateSerializer::class;
        $this->registry['TrackRadioButtonListQuestionTemplate'] = TrackMultiValueQuestionTemplateSerializer::class;
        $this->registry['TrackLiteralContentQuestionTemplate'] = TrackLiteralContentQuestionTemplateSerializer::class;

        // signs

        $this->registry['SummitSign'] = SummitSignSerializer::class;

        // events

        $this->registry['SummitEvent'] = [
            self::SerializerType_Public => SummitEventSerializer::class,
            self::SerializerType_Private => AdminSummitEventSerializer::class,
            self::SerializerType_CSV => AdminSummitEventCSVSerializer::class,
            IPresentationSerializerTypes::SecureStream => SummitEventSecureStreamSerializer::class,
        ];

        $this->registry['SummitEventWithFile'] = [
            self::SerializerType_Public => SummitEventWithFileSerializer::class,
            self::SerializerType_Private => AdminSummitEventWithFileSerializer::class,
            self::SerializerType_CSV => AdminSummitEventWithFileCSVSerializer::class,
            IPresentationSerializerTypes::SecureStream => SummitEventSecureStreamSerializer::class,
        ];

        $this->registry['SummitGroupEvent'] = [
            self::SerializerType_Public => SummitGroupEventSerializer::class,
            self::SerializerType_Private => SummitGroupEventSerializer::class,
            self::SerializerType_CSV => SummitGroupEventSerializer::class,
            IPresentationSerializerTypes::SecureStream => SummitEventSecureStreamSerializer::class,
        ];

        $this->registry['Presentation'] = [
            self::SerializerType_Public => PresentationSerializer::class,
            self::SerializerType_Private => AdminPresentationSerializer::class,
            self::SerializerType_CSV => AdminPresentationCSVSerializer::class,
            self::SerializerType_Admin_Voteable => AdminVoteablePresentationSerializer::class,
            self::SerializerType_Admin_Voteable_CSV => AdminVoteablePresentationCSVSerializer::class,
            IPresentationSerializerTypes::TrackChairs => TrackChairPresentationSerializer::class,
            IPresentationSerializerTypes::TrackChairs_CSV => TrackChairPresentationCSVSerializer::class,
            IPresentationSerializerTypes::SpeakerEmails => SpeakerPresentationEmailSerializer::class,
            IPresentationSerializerTypes::SubmitterEmails => SpeakerPresentationEmailSerializer::class,
            IPresentationSerializerTypes::SecureStream => SummitEventSecureStreamSerializer::class,
        ];

        $this->registry['PresentationAttendeeVote'] = PresentationAttendeeVoteSerializer::class;
        $this->registry['TrackTagGroup'] = TrackTagGroupSerializer::class;
        $this->registry['SummitCategoryChange'] = SummitCategoryChangeSerializer::class;

        $this->registry['PresentationActionType'] = PresentationActionTypeSerializer::class;
        $this->registry['PresentationAction'] = PresentationActionSerializer::class;

        // track chairs
        $this->registry['PresentationTrackChairView'] = PresentationTrackChairViewSerializer::class;
        $this->registry['SummitSelectedPresentationList'] = SummitSelectedPresentationListSerializer::class;
        $this->registry['SummitSelectedPresentation'] = SummitSelectedPresentationSerializer::class;

        $this->registry['SummitTrackChair'] = [
            self::SerializerType_Public => SummitTrackChairSerializer::class,
            self::SerializerType_Private => AdminSummitTrackChairSerializer::class,
            self::SerializerType_CSV => SummitTrackChairCSVSerializer::class
        ];

        $this->registry['PresentationTrackChairRatingType'] = PresentationTrackChairRatingTypeSerializer::class;
        $this->registry['PresentationTrackChairScoreType'] = PresentationTrackChairScoreTypeSerializer::class;
        $this->registry['PresentationTrackChairScore'] = PresentationTrackChairScoreSerializer::class;

        $this->registry['SummitPresentationComment'] = SummitPresentationCommentSerializer::class;
        $this->registry['SummitMediaFileType'] = SummitMediaFileTypeSerializer::class;
        $this->registry['SummitMediaUploadType'] = SummitMediaUploadTypeSerializer::class;
        $this->registry['PresentationVideo'] = PresentationVideoSerializer::class;
        $this->registry['PresentationSlide'] = PresentationSlideSerializer::class;
        $this->registry['PresentationLink'] = PresentationLinkSerializer::class;

        $this->registry['PresentationMediaUpload'] = [
            self::SerializerType_Public => PresentationMediaUploadSerializer::class,
            self::SerializerType_Private => AdminPresentationMediaUploadSerializer::class
        ];

        // Company

        $this->registry['Company'] = CompanySerializer::class;
        $this->registry['SponsoredProject'] = SponsoredProjectSerializer::class;
        $this->registry['ProjectSponsorshipType'] = ProjectSponsorshipTypeSerializer::class;
        $this->registry['SupportingCompany'] = SupportingCompanySerializer::class;

        $this->registry['PresentationSpeaker'] =
            [
                self::SerializerType_Public => PresentationSpeakerSerializer::class,
                self::SerializerType_Private => AdminPresentationSpeakerSerializer::class,
                self::SerializerType_Admin => AdminPresentationSpeakerSerializer::class,
                self::SerializerType_CSV => AdminPresentationSpeakerCSVSerializer::class
            ];

        $this->registry['SpeakerEditPermissionRequest'] = SpeakerEditPermissionRequestSerializer::class;

        // RSVP
        $this->registry['RSVP'] = RSVPSerializer::class;
        $this->registry['RSVPAnswer'] = RSVPAnswerSerializer::class;
        $this->registry['RSVPTemplate'] = RSVPTemplateSerializer::class;
        $this->registry['RSVPQuestionValueTemplate'] = RSVPQuestionValueTemplateSerializer::class;

        $this->registry['RSVPSingleValueTemplateQuestion'] = RSVPSingleValueTemplateQuestionSerializer::class;
        $this->registry['RSVPTextBoxQuestionTemplate'] = RSVPSingleValueTemplateQuestionSerializer::class;
        $this->registry['RSVPTextAreaQuestionTemplate'] = RSVPSingleValueTemplateQuestionSerializer::class;
        $this->registry['RSVPLiteralContentQuestionTemplate'] = RSVPLiteralContentQuestionTemplateSerializer::class;
        $this->registry['RSVPMemberEmailQuestionTemplate'] = RSVPSingleValueTemplateQuestionSerializer::class;
        $this->registry['RSVPMemberFirstNameQuestionTemplate'] = RSVPSingleValueTemplateQuestionSerializer::class;
        $this->registry['RSVPMemberLastNameQuestionTemplate'] = RSVPSingleValueTemplateQuestionSerializer::class;

        $this->registry['RSVPCheckBoxListQuestionTemplate'] = RSVPMultiValueQuestionTemplateSerializer::class;
        $this->registry['RSVPRadioButtonListQuestionTemplate'] = RSVPMultiValueQuestionTemplateSerializer::class;
        $this->registry['RSVPDropDownQuestionTemplate'] = RSVPDropDownQuestionTemplateSerializer::class;

        $this->registry['SpeakerExpertise'] = SpeakerExpertiseSerializer::class;
        $this->registry['SpeakerTravelPreference'] = SpeakerTravelPreferenceSerializer::class;
        $this->registry['SpeakerPresentationLink'] = SpeakerPresentationLinkSerializer::class;
        $this->registry['SpeakerActiveInvolvement'] = SpeakerActiveInvolvementSerializer::class;
        $this->registry['SpeakerOrganizationalRole'] = SpeakerOrganizationalRoleSerializer::class;

        $this->registry['SummitEventFeedback'] = SummitEventFeedbackSerializer::class;
        $this->registry['SummitMemberSchedule'] = SummitMemberScheduleSerializer::class;
        $this->registry['SummitMemberFavorite'] = SummitMemberFavoriteSerializer::class;
        $this->registry['SummitEntityEvent'] = SummitEntityEventSerializer::class;
        $this->registry['SummitScheduleEmptySpot'] = SummitScheduleEmptySpotSerializer::class;

        // promo codes

        $this->registry['SummitRegistrationPromoCode'] = [
            self::SerializerType_Public => SummitRegistrationPromoCodeSerializer::class,
            self::SerializerType_CSV => SummitRegistrationPromoCodeCSVSerializer::class,
        ];

        $this->registry['SummitRegistrationDiscountCode'] = [
            self::SerializerType_Public => SummitRegistrationDiscountCodeSerializer::class,
            self::SerializerType_CSV => SummitRegistrationDiscountCodeCSVSerializer::class,
        ];

        $this->registry['MemberSummitRegistrationPromoCode'] = [
            self::SerializerType_Public => MemberSummitRegistrationPromoCodeSerializer::class,
            self::SerializerType_CSV => MemberSummitRegistrationPromoCodeCSVSerializer::class,
        ];

        $this->registry['MemberSummitRegistrationDiscountCode'] = [
            self::SerializerType_Public => MemberSummitRegistrationDiscountCodeSerializer::class,
            self::SerializerType_CSV => MemberSummitRegistrationDiscountCodeCSVSerializer::class,
        ];

        $this->registry['SpeakerSummitRegistrationPromoCode'] = [
            self::SerializerType_Public => SpeakerSummitRegistrationPromoCodeSerializer::class,
            self::SerializerType_CSV => SpeakerSummitRegistrationPromoCodeCSVSerializer::class,
        ];

        $this->registry['SpeakerSummitRegistrationDiscountCode'] = [
            self::SerializerType_Public => SpeakerSummitRegistrationDiscountCodeSerializer::class,
            self::SerializerType_CSV => SpeakerSummitRegistrationDiscountCodeCSVSerializer::class,
        ];

        $this->registry['SponsorSummitRegistrationPromoCode'] = [
            self::SerializerType_Public => SponsorSummitRegistrationPromoCodeSerializer::class,
            self::SerializerType_CSV => SponsorSummitRegistrationPromoCodeCSVSerializer::class,
        ];

        $this->registry['SponsorSummitRegistrationDiscountCode'] = [
            self::SerializerType_Public => SponsorSummitRegistrationDiscountCodeSerializer::class,
            self::SerializerType_CSV => SponsorSummitRegistrationDiscountCodeCSVSerializer::class,
        ];

        $this->registry['SpeakersSummitRegistrationPromoCode'] = [
            self::SerializerType_Public => SpeakersSummitRegistrationPromoCodeSerializer::class,
            self::SerializerType_CSV => SpeakersSummitRegistrationPromoCodeCSVSerializer::class,
        ];

        $this->registry['SpeakersRegistrationDiscountCode'] = [
            self::SerializerType_Public => SpeakersRegistrationDiscountCodeSerializer::class,
            self::SerializerType_CSV => SpeakersRegistrationDiscountCodeCSVSerializer::class,
        ];


        $this->registry['PresentationSpeakerSummitAssistanceConfirmationRequest'] = PresentationSpeakerSummitAssistanceConfirmationRequestSerializer::class;
        $this->registry['SummitRegistrationDiscountCodeTicketTypeRule'] = SummitRegistrationDiscountCodeTicketTypeRuleSerializer::class;

        $this->registry['AssignedPromoCodeSpeaker'] = AssignedPromoCodeSpeakerSerializer::class;
        $this->registry['SummitRegistrationFeedMetadata'] = SummitRegistrationFeedMetadataSerializer::class;

        // submission invitations
        $this->registry['SummitSubmissionInvitation'] = [
            self::SerializerType_Public => SummitSubmissionInvitationSerializer::class,
            self::SerializerType_CSV => SummitSubmissionInvitationCSVSerializer::class,
        ];

        // registration

        $this->registry['SummitRegistrationInvitation'] =
            [
                self::SerializerType_Public => SummitRegistrationInvitationSerializer::class,
                self::SerializerType_CSV => SummitRegistrationInvitationCSVSerializer::class,
            ];

        $this->registry['SummitAccessLevelType'] = SummitAccessLevelTypeSerializer::class;
        $this->registry['SummitTaxType'] = SummitTaxTypeSerializer::class;
        $this->registry['SummitBadgeType'] = SummitBadgeTypeSerializer::class;
        $this->registry['SummitBadgeFeatureType'] = SummitBadgeFeatureTypeSerializer::class;
        $this->registry['SummitRefundPolicyType'] = SummitRefundPolicyTypeSerializer::class;
        $this->registry['SummitOrderExtraQuestionType'] = SummitOrderExtraQuestionTypeSerializer::class;

        $this->registry['SummitBadgeViewType'] = SummitBadgeViewTypeSerializer::class;

        // orders

        $this->registry['SummitOrder'] = [
            self::SerializerType_Public => SummitOrderBaseSerializer::class,
            ISummitOrderSerializerTypes::CheckOutType => SummitOrderBaseSerializer::class,
            ISummitOrderSerializerTypes::ReservationType => SummitOrderReservationSerializer::class,
            ISummitOrderSerializerTypes::AdminType => SummitOrderAdminSerializer::class,
            ISummitOrderSerializerTypes::OwnType => SummitOrderOwnSerializer::class,
        ];

        $this->registry['SummitOrderExtraQuestionAnswer'] = SummitOrderExtraQuestionAnswerSerializer::class;

        $this->registry['SummitAttendee'] = [
            self::SerializerType_Public => SummitAttendeeSerializer::class,
            self::SerializerType_Private => SummitAttendeeAdminSerializer::class,
            self::SerializerType_CSV => SummitAttendeeCSVSerializer::class,
        ];

        $this->registry['SummitAttendeeTicket'] = [
            self::SerializerType_Public => BaseSummitAttendeeTicketSerializer::class,
            ISummitAttendeeTicketSerializerTypes::AdminType => SummitAttendeeTicketSerializer::class,
            ISummitAttendeeTicketSerializerTypes::PublicEdition => PublicEditionSummitAttendeeTicketSerializer::class,
            ISummitAttendeeTicketSerializerTypes::GuestEdition => GuestEditionSummitAttendeeTicketSerializer::class,
            self::SerializerType_CSV => SummitAttendeeTicketCSVSerializer::class,
        ];

        $this->registry['SummitAttendeeTicketRefundRequest'] = SummitAttendeeTicketRefundRequestSerializer::class;

        $this->registry['SummitAttendeeBadge'] = SummitAttendeeBadgeSerializer::class;
        $this->registry['SummitAttendeeBadgePrint'] = [
            self::SerializerType_Public => SummitAttendeeBadgePrintSerializer::class,
            self::SerializerType_CSV => SummitAttendeeBadgePrintCSVSerializer::class,
        ];

        $this->registry['SummitAttendeeNote'] = [
            self::SerializerType_Public => SummitAttendeeNoteSerializer::class,
            self::SerializerType_CSV => AdminSummitAttendeeNoteCSVSerializer::class,
            self::SerializerType_Private => AdminSummitAttendeeNoteSerializer::class,
        ];

        $this->registry['SponsorBadgeScan'] = [
            self::SerializerType_Public => SponsorBadgeScanSerializer::class,
            self::SerializerType_CSV => SponsorBadgeScanCSVSerializer::class,
        ];

        $this->registry['SponsorUserInfoGrant'] = [
            self::SerializerType_Public => SponsorUserInfoGrantSerializer::class,
            self::SerializerType_CSV => SponsorUserInfoGrantCSVSerializer::class,
        ];

        $this->registry['SummitAttendeeTicketTax'] = SummitAttendeeTicketTaxSerializer::class;

        // summit sponsors

        $this->registry['SponsorshipType'] = SponsorshipTypeSerializer::class;
        $this->registry['SummitSponsorshipType'] = SummitSponsorshipTypeSerializer::class;
        $this->registry['Sponsor'] = SponsorSerializer::class;
        $this->registry['SponsorAd'] = SponsorAdSerializer::class;
        $this->registry['SponsorMaterial'] = SponsorMaterialSerializer::class;
        $this->registry['SponsorSocialNetwork'] = SponsorSocialNetworkSerializer::class;

        // locations

        $this->registry['SummitVenue'] = SummitVenueSerializer::class;
        $this->registry['SummitVenueRoom'] = SummitVenueRoomSerializer::class;
        $this->registry['SummitVenueFloor'] = SummitVenueFloorSerializer::class;
        $this->registry['SummitExternalLocation'] = SummitExternalLocationSerializer::class;
        $this->registry['SummitHotel'] = SummitHotelSerializer::class;
        $this->registry['SummitAirport'] = SummitAirportSerializer::class;
        $this->registry['SummitLocationImage'] = SummitLocationImageSerializer::class;
        $this->registry['SummitLocationBanner'] = SummitLocationBannerSerializer::class;
        $this->registry['ScheduledSummitLocationBanner'] = ScheduledSummitLocationBannerSerializer::class;
        $this->registry['SummitBookableVenueRoom'] = SummitBookableVenueRoomSerializer::class;
        $this->registry['SummitBookableVenueRoomAttributeType'] = SummitBookableVenueRoomAttributeTypeSerializer::class;
        $this->registry['SummitBookableVenueRoomAttributeValue'] = SummitBookableVenueRoomAttributeValueSerializer::class;
        $this->registry['SummitBookableVenueRoomAvailableSlot'] = SummitBookableVenueRoomAvailableSlotSerializer::class;
        $this->registry['SummitRoomReservation'] = [
            self::SerializerType_Public => SummitRoomReservationSerializer::class,
            self::SerializerType_CSV => SummitRoomReservationCSVSerializer::class,
        ];

        // track tag groups
        $this->registry['TrackTagGroup'] = TrackTagGroupSerializer::class;
        $this->registry['TrackTagGroupAllowedTag'] = TrackTagGroupAllowedTagSerializer::class;

        $this->registry['PersonalCalendarShareInfo'] = PersonalCalendarShareInfoSerializer::class;

        // member
        $this->registry['Member'] = [
            self::SerializerType_Public => PublicMemberSerializer::class,
            self::SerializerType_Private => OwnMemberSerializer::class,
            self::SerializerType_Admin => AdminMemberSerializer::class,
            IMemberSerializerTypes::Submitter => SubmitterMemberSerializer::class,
            IMemberSerializerTypes::SubmitterCSV => SubmitterMemberCSVSerializer::class,
        ];

        $this->registry['LegalAgreement'] = LegalAgreementSerializer::class;
        $this->registry['LegalDocument'] = LegalDocumentSerializer::class;

        $this->registry['Group'] = GroupSerializer::class;
        $this->registry['Affiliation'] = AffiliationSerializer::class;
        $this->registry['Organization'] = OrganizationSerializer::class;
        // push notification
        $this->registry['PushNotificationMessage'] = PushNotificationMessageSerializer::class;
        $this->registry['SummitPushNotification'] = SummitPushNotificationSerializer::class;

        // teams
        $this->registry['ChatTeam'] = ChatTeamSerializer::class;
        $this->registry['ChatTeamMember'] = ChatTeamMemberSerializer::class;
        $this->registry['ChatTeamInvitation'] = ChatTeamInvitationSerializer::class;
        $this->registry['ChatTeamPushNotificationMessage'] = ChatTeamPushNotificationMessageSerializer::class;

        // marketplace

        $this->registry['Appliance'] = ApplianceSerializer::class;
        $this->registry["Distribution"] = DistributionSerializer::class;
        $this->registry['MarketPlaceReview'] = MarketPlaceReviewSerializer::class;
        $this->registry['OpenStackImplementationApiCoverage'] = OpenStackImplementationApiCoverageSerializer::class;
        $this->registry['GuestOSType'] = GuestOSTypeSerializer::class;
        $this->registry['HyperVisorType'] = HyperVisorTypeSerializer::class;
        $this->registry['Region'] = RegionSerializer::class;
        $this->registry['RegionalSupport'] = RegionalSupportSerializer::class;
        $this->registry['SupportChannelType'] = SupportChannelTypeSerializer::class;
        $this->registry['Office'] = OfficeSerializer::class;
        $this->registry['Consultant'] = ConsultantSerializer::class;
        $this->registry['ConsultantClient'] = ConsultantClientSerializer::class;
        $this->registry['SpokenLanguage'] = SpokenLanguageSerializer::class;
        $this->registry['ConfigurationManagementType'] = ConfigurationManagementTypeSerializer::class;
        $this->registry['ServiceOfferedType'] = ServiceOfferedTypeSerializer::class;
        $this->registry['ConsultantServiceOfferedType'] = ConsultantServiceOfferedTypeSerializer::class;
        $this->registry['DataCenterLocation'] = DataCenterLocationSerializer::class;
        $this->registry['DataCenterRegion'] = DataCenterRegionSerializer::class;
        $this->registry['PricingSchemaType'] = PricingSchemaTypeSerializer::class;
        $this->registry['PrivateCloudService'] = PrivateCloudServiceSerializer::class;
        $this->registry['PublicCloudService'] = PublicCloudServiceSerializer::class;
        $this->registry['RemoteCloudService'] = RemoteCloudServiceSerializer::class;
        $this->registry['CloudServiceOffered'] = CloudServiceOfferedSerializer::class;
        // software

        $this->registry['OpenStackComponent'] = OpenStackComponentSerializer::class;
        $this->registry['OpenStackReleaseComponent'] = OpenStackReleaseComponentSerializer::class;
        $this->registry['OpenStackRelease'] = OpenStackReleaseSerializer::class;

        // ccla

        $this->registry['Team'] = TeamSerializer::class;

        $this->registry['File'] = FileSerializer::class;

        // proposed schedule
        $this->registry['SummitProposedSchedule'] = SummitProposedScheduleSerializer::class;
        $this->registry['SummitProposedScheduleSummitEvent'] = SummitProposedScheduleSummitEventSerializer::class;
        $this->registry['SummitProposedScheduleAllowedLocation'] = SummitProposedScheduleAllowedLocationSerializer::class;
        $this->registry['SummitProposedScheduleAllowedDay'] = SummitProposedScheduleAllowedDaySerializer::class;

        // proposed schedule lock
        $this->registry['SummitProposedScheduleLock'] = SummitProposedScheduleLockSerializer::class;

    }

    /**
     * @param object $object
     * @param string $type
     * @return IModelSerializer
     */
    public function getSerializer($object, $type = self::SerializerType_Public)
    {
        if (is_null($object)) return null;
        $reflect = new \ReflectionClass($object);
        $class = $reflect->getShortName();
        if (!isset($this->registry[$class]))
            throw new \InvalidArgumentException('Serializer not found for ' . $class);

        $serializer_class = $this->registry[$class];

        if (is_array($serializer_class)) {
            if (!isset($serializer_class[$type])) {
                $type = self::SerializerType_Public;
            }

            if (!isset($serializer_class[$type])) {
                throw new \InvalidArgumentException(sprintf('Serializer not found for %s , type %s', $class, $type));
            }

            $serializer_class = $serializer_class[$type];
        }

        return new $serializer_class($object, $this->resource_server_context);
    }
}