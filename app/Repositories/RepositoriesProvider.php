<?php namespace App\Repositories;
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

use App\Models\Foundation\Elections\Election;
use App\Models\Foundation\Elections\IElectionsRepository;
use App\Models\Foundation\Main\Language;
use App\Models\Foundation\Main\Repositories\IAuditLogRepository;
use App\Models\Foundation\Main\Repositories\ILanguageRepository;
use App\Models\Foundation\Main\Repositories\ILegalDocumentRepository;
use App\Models\Foundation\Main\Repositories\IProjectSponsorshipTypeRepository;
use App\Models\Foundation\Main\Repositories\ISponsoredProjectRepository;
use App\Models\Foundation\Main\Repositories\ISummitAdministratorPermissionGroupRepository;
use App\Models\Foundation\Main\Repositories\ISupportingCompanyRepository;
use App\Models\Foundation\Software\OpenStackRelease;
use App\Models\Foundation\Software\Repositories\IOpenStackReleaseRepository;
use App\Models\Foundation\Summit\Defaults\DefaultSummitEventType;
use App\Models\Foundation\Summit\DefaultTrackTagGroup;
use App\Models\Foundation\Summit\EmailFlows\SummitEmailEventFlow;
use App\Models\Foundation\Summit\Events\Presentations\TrackChairs\PresentationTrackChairRatingType;
use App\Models\Foundation\Summit\Events\Presentations\TrackChairs\PresentationTrackChairScoreType;
use App\Models\Foundation\Summit\Events\Presentations\TrackQuestions\TrackQuestionTemplate;
use App\Models\Foundation\Summit\Events\RSVP\RSVPTemplate;
use App\Models\Foundation\Summit\ExtraQuestions\SummitSelectionPlanExtraQuestionType;
use App\Models\Foundation\Summit\ExtraQuestions\SummitSponsorExtraQuestionType;
use App\Models\Foundation\Summit\Locations\Banners\SummitLocationBanner;
use App\Models\Foundation\Summit\ProposedSchedule\SummitProposedSchedule;
use App\Models\Foundation\Summit\ProposedSchedule\SummitProposedScheduleAllowedDay;
use App\Models\Foundation\Summit\ProposedSchedule\SummitProposedScheduleAllowedLocation;
use App\Models\Foundation\Summit\ProposedSchedule\SummitProposedScheduleLock;
use App\Models\Foundation\Summit\ProposedSchedule\SummitProposedScheduleSummitEvent;
use App\Models\Foundation\Summit\Registration\SummitRegistrationFeedMetadata;
use App\Models\Foundation\Summit\Repositories\IDefaultSummitEventTypeRepository;
use App\Models\Foundation\Summit\Repositories\IDefaultTrackTagGroupRepository;
use App\Models\Foundation\Summit\Repositories\IPaymentGatewayProfileRepository;
use App\Models\Foundation\Summit\Repositories\IPresentationActionTypeRepository;
use App\Models\Foundation\Summit\Repositories\IPresentationCategoryGroupRepository;
use App\Models\Foundation\Summit\Repositories\IPresentationMediaUploadRepository;
use App\Models\Foundation\Summit\Repositories\IPresentationSpeakerSummitAssistanceConfirmationRequestRepository;
use App\Models\Foundation\Summit\Repositories\IPresentationTrackChairRatingTypeRepository;
use App\Models\Foundation\Summit\Repositories\IPresentationTrackChairScoreTypeRepository;
use App\Models\Foundation\Summit\Repositories\IRSVPTemplateRepository;
use App\Models\Foundation\Summit\Repositories\ISelectionPlanRepository;
use App\Models\Foundation\Summit\Repositories\ISpeakerActiveInvolvementRepository;
use App\Models\Foundation\Summit\Repositories\ISpeakerEditPermissionRequestRepository;
use App\Models\Foundation\Summit\Repositories\ISpeakerOrganizationalRoleRepository;
use App\Models\Foundation\Summit\Repositories\ISpeakersRegistrationDiscountCodeRepository;
use App\Models\Foundation\Summit\Repositories\ISpeakersSummitRegistrationPromoCodeRepository;
use App\Models\Foundation\Summit\Repositories\ISponsorAdRepository;
use App\Models\Foundation\Summit\Repositories\ISponsorExtraQuestionTypeRepository;
use App\Models\Foundation\Summit\Repositories\ISponsorMaterialRepository;
use App\Models\Foundation\Summit\Repositories\ISponsorRepository;
use App\Models\Foundation\Summit\Repositories\ISponsorshipTypeRepository;
use App\Models\Foundation\Summit\Repositories\ISponsorSocialNetworkRepository;
use App\Models\Foundation\Summit\Repositories\ISummitAccessLevelTypeRepository;
use App\Models\Foundation\Summit\Repositories\ISummitAttendeeBadgePrintRepository;
use App\Models\Foundation\Summit\Repositories\ISummitAttendeeBadgePrintRuleRepository;
use App\Models\Foundation\Summit\Repositories\ISummitAttendeeBadgeRepository;
use App\Models\Foundation\Summit\Repositories\ISummitAttendeeNoteRepository;
use App\Models\Foundation\Summit\Repositories\ISummitBadgeFeatureTypeRepository;
use App\Models\Foundation\Summit\Repositories\ISummitBadgeTypeRepository;
use App\Models\Foundation\Summit\Repositories\ISummitBookableVenueRoomAttributeTypeRepository;
use App\Models\Foundation\Summit\Repositories\ISummitBookableVenueRoomAttributeValueRepository;
use App\Models\Foundation\Summit\Repositories\ISummitCategoryChangeRepository;
use App\Models\Foundation\Summit\Repositories\ISummitDocumentRepository;
use App\Models\Foundation\Summit\Repositories\ISummitEmailEventFlowRepository;
use App\Models\Foundation\Summit\Repositories\ISummitEventTypeRepository;
use App\Models\Foundation\Summit\Repositories\ISummitLocationBannerRepository;
use App\Models\Foundation\Summit\Repositories\ISummitLocationRepository;
use App\Models\Foundation\Summit\Repositories\ISummitMediaFileTypeRepository;
use App\Models\Foundation\Summit\Repositories\ISummitMediaUploadTypeRepository;
use App\Models\Foundation\Summit\Repositories\ISummitMetricRepository;
use App\Models\Foundation\Summit\Repositories\ISummitOrderExtraQuestionTypeRepository;
use App\Models\Foundation\Summit\Repositories\ISummitOrderRepository;
use App\Models\Foundation\Summit\Repositories\ISummitPresentationCommentRepository;
use App\Models\Foundation\Summit\Repositories\ISummitPresentationSpeakerAssignmentRepository;
use App\Models\Foundation\Summit\Repositories\ISummitProposedScheduleAllowedDayRepository;
use App\Models\Foundation\Summit\Repositories\ISummitProposedScheduleAllowedLocationRepository;
use App\Models\Foundation\Summit\Repositories\ISummitRefundPolicyTypeRepository;
use App\Models\Foundation\Summit\Repositories\ISummitRegistrationFeedMetadataRepository;
use App\Models\Foundation\Summit\Repositories\ISummitRegistrationInvitationRepository;
use App\Models\Foundation\Summit\Repositories\ISummitRoomReservationRepository;
use App\Models\Foundation\Summit\Repositories\ISummitScheduleConfigRepository;
use App\Models\Foundation\Summit\Repositories\ISummitSelectionPlanExtraQuestionTypeRepository;
use App\Models\Foundation\Summit\Repositories\ISummitSignRepository;
use App\Models\Foundation\Summit\Repositories\ISummitSponsorshipTypeRepository;
use App\Models\Foundation\Summit\Repositories\ISummitSubmissionInvitationRepository;
use App\Models\Foundation\Summit\Repositories\ISummitTaxTypeRepository;
use App\Models\Foundation\Summit\Repositories\ISummitTrackChairRepository;
use App\Models\Foundation\Summit\Repositories\ISummitTrackRepository;
use App\Models\Foundation\Summit\Repositories\ITrackQuestionTemplateRepository;
use App\Models\Foundation\Summit\Repositories\ITrackTagGroupAllowedTagsRepository;
use App\Models\Foundation\Summit\SelectionPlan;
use App\Models\Foundation\Summit\Signs\SummitSign;
use App\Models\Foundation\Summit\Speakers\PresentationSpeakerAssignment;
use App\Models\Foundation\Summit\Speakers\SpeakerEditPermissionRequest;
use App\Models\Foundation\Summit\TrackTagGroupAllowedTag;
use App\Models\ResourceServer\IApiRepository;
use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;
use LaravelDoctrine\ORM\Facades\EntityManager;
use models\main\AssetsSyncRequest;
use models\main\AuditLog;
use models\main\Company;
use models\main\File;
use models\main\Group;
use models\main\IOrganizationRepository;
use models\main\Organization;
use models\main\ProjectSponsorshipType;
use models\main\SponsoredProject;
use models\main\SummitAdministratorPermissionGroup;
use models\main\SupportingCompany;
use models\summit\ISponsorUserInfoGrantRepository;
use models\summit\ISummitBadgeViewTypeRepository;
use models\summit\ISummitProposedScheduleEventRepository;
use models\summit\ISummitProposedScheduleLockRepository;
use models\summit\ISummitProposedScheduleRepository;
use models\summit\ISummitRegistrationPromoCodeRepository;
use models\summit\ISummitTicketTypeRepository;
use models\summit\PaymentGatewayProfile;
use models\summit\PresentationActionType;
use models\summit\PresentationCategory;
use models\summit\PresentationCategoryGroup;
use models\summit\PresentationMediaUpload;
use models\summit\PresentationSpeakerSummitAssistanceConfirmationRequest;
use models\summit\SpeakerActiveInvolvement;
use models\summit\SpeakerOrganizationalRole;
use models\summit\SpeakerRegistrationRequest;
use models\summit\SpeakersRegistrationDiscountCode;
use models\summit\SpeakersSummitRegistrationPromoCode;
use models\summit\SpeakerSummitRegistrationPromoCode;
use models\summit\Sponsor;
use models\summit\SponsorAd;
use models\summit\SponsorMaterial;
use models\summit\SponsorshipType;
use models\summit\SponsorSocialNetwork;
use models\summit\SponsorUserInfoGrant;
use models\summit\SummitAbstractLocation;
use models\summit\SummitAccessLevelType;
use models\summit\SummitAttendeeBadge;
use models\summit\SummitAttendeeBadgePrint;
use models\summit\SummitAttendeeBadgePrintRule;
use models\summit\SummitAttendeeNote;
use models\summit\SummitBadgeFeatureType;
use models\summit\SummitBadgeType;
use models\summit\SummitBadgeViewType;
use models\summit\SummitBookableVenueRoomAttributeType;
use models\summit\SummitBookableVenueRoomAttributeValue;
use models\summit\SummitCategoryChange;
use models\summit\SummitDocument;
use models\summit\SummitEventType;
use models\summit\SummitMediaFileType;
use models\summit\SummitMediaUploadType;
use models\summit\SummitMetric;
use models\summit\SummitOrder;
use models\summit\SummitOrderExtraQuestionType;
use models\summit\SummitPresentationComment;
use models\summit\SummitRefundPolicyType;
use models\summit\SummitRegistrationInvitation;
use models\summit\SummitRegistrationPromoCode;
use models\summit\SummitRoomReservation;
use models\summit\SummitScheduleConfig;
use models\summit\SummitSponsorshipType;
use models\summit\SummitSubmissionInvitation;
use models\summit\SummitTaxType;
use models\summit\SummitTicketType;
use models\summit\SummitTrackChair;
use repositories\main\DoctrineLegalDocumentRepository;

/**
 * Class RepositoriesProvider
 * @package repositories
 */
final class RepositoriesProvider extends ServiceProvider
{
    protected $defer = false;

    public function boot()
    {
    }

    public function register()
    {

        App::singleton(
            IApiRepository::class,
            function () {
                return EntityManager::getRepository(\App\Models\ResourceServer\Api::class);
            });

        App::singleton(
            'App\Models\ResourceServer\IApiEndpointRepository',
            function () {
                return EntityManager::getRepository(\App\Models\ResourceServer\ApiEndpoint::class);
            });

        App::singleton(
            'App\Models\ResourceServer\IEndpointRateLimitByIPRepository',
            function () {
                return EntityManager::getRepository(\App\Models\ResourceServer\EndPointRateLimitByIP::class);
            });

        App::singleton(
            'models\summit\ISummitRepository',
            function () {
                return EntityManager::getRepository(\models\summit\Summit::class);
            });

        App::singleton(
            'models\summit\IEventFeedbackRepository',
            function () {
                return EntityManager::getRepository(\models\summit\SummitEventFeedback::class);
            });

        App::singleton(
            'models\summit\ISpeakerRepository',
            function () {
                return EntityManager::getRepository(\models\summit\PresentationSpeaker::class);
            });

        App::singleton(
            'models\summit\ISummitEventRepository',
            function () {
                return EntityManager::getRepository(\models\summit\SummitEvent::class);
            });

        App::singleton(
            'models\summit\ISummitEntityEventRepository',
            function () {
                return EntityManager::getRepository(\models\summit\SummitEntityEvent::class);
            });


        App::singleton(
            'models\main\IMemberRepository',
            function () {
                return EntityManager::getRepository(\models\main\Member::class);
            });

        App::singleton(
            'models\summit\ISummitAttendeeRepository',
            function () {
                return EntityManager::getRepository(\models\summit\SummitAttendee::class);
            });

        App::singleton(
            'models\summit\ISummitAttendeeTicketRepository',
            function () {
                return EntityManager::getRepository(\models\summit\SummitAttendeeTicket::class);
            });

        App::singleton(
            'models\summit\ISummitNotificationRepository',
            function () {
                return EntityManager::getRepository(\models\summit\SummitPushNotification::class);
            });

        App::singleton(
            'models\main\ITagRepository',
            function () {
                return EntityManager::getRepository(\models\main\Tag::class);
            });

        App::singleton(
            'models\main\IChatTeamRepository',
            function () {
                return EntityManager::getRepository(\models\main\ChatTeam::class);
            });

        App::singleton(
            'models\main\IChatTeamInvitationRepository',
            function () {
                return EntityManager::getRepository(\models\main\ChatTeamInvitation::class);
            });

        App::singleton(
            'models\main\IChatTeamPushNotificationMessageRepository',
            function () {
                return EntityManager::getRepository(\models\main\ChatTeamPushNotificationMessage::class);
            });

        App::singleton(
            'models\summit\IRSVPRepository',
            function () {
                return EntityManager::getRepository(\models\summit\RSVP::class);
            });

        App::singleton(
            'models\summit\IAbstractCalendarSyncWorkRequestRepository',
            function () {
                return EntityManager::getRepository(\models\summit\CalendarSync\WorkQueue\AbstractCalendarSyncWorkRequest::class);
            });

        App::singleton(
            'models\summit\ICalendarSyncInfoRepository',
            function () {
                return EntityManager::getRepository(\models\summit\CalendarSync\CalendarSyncInfo::class);
            });

        App::singleton(
            'models\summit\IScheduleCalendarSyncInfoRepository',
            function () {
                return EntityManager::getRepository(\models\summit\CalendarSync\ScheduleCalendarSyncInfo::class);
            });

        // Marketplace

        App::singleton(
            'App\Models\Foundation\Marketplace\IApplianceRepository',
            function () {
                return EntityManager::getRepository(\App\Models\Foundation\Marketplace\Appliance::class);
            });

        App::singleton(
            'App\Models\Foundation\Marketplace\IDistributionRepository',
            function () {
                return EntityManager::getRepository(\App\Models\Foundation\Marketplace\Distribution::class);
            });

        App::singleton(
            'App\Models\Foundation\Marketplace\IConsultantRepository',
            function () {
                return EntityManager::getRepository(\App\Models\Foundation\Marketplace\Consultant::class);
            });

        App::singleton(
            'App\Models\Foundation\Marketplace\IPrivateCloudServiceRepository',
            function () {
                return EntityManager::getRepository(\App\Models\Foundation\Marketplace\PrivateCloudService::class);
            });

        App::singleton(
            'App\Models\Foundation\Marketplace\IPublicCloudServiceRepository',
            function () {
                return EntityManager::getRepository(\App\Models\Foundation\Marketplace\PublicCloudService::class);
            });

        App::singleton(
            'App\Models\Foundation\Marketplace\IRemoteCloudServiceRepository',
            function () {
                return EntityManager::getRepository(\App\Models\Foundation\Marketplace\RemoteCloudService::class);
            });

        App::singleton(
            'models\main\IFolderRepository',
            function () {
                return EntityManager::getRepository(File::class);
            });

        App::singleton(
            'models\main\IAssetsSyncRequestRepository',
            function () {
                return EntityManager::getRepository(AssetsSyncRequest::class);
            });

        App::singleton(
            'models\main\ICompanyRepository',
            function () {
                return EntityManager::getRepository(Company::class);
            });

        App::singleton(
            'models\main\IGroupRepository',
            function () {
                return EntityManager::getRepository(Group::class);
            });

        App::singleton(
            'models\summit\ISpeakerRegistrationRequestRepository',
            function () {
                return EntityManager::getRepository(SpeakerRegistrationRequest::class);
            });

        App::singleton(
            'models\summit\ISpeakerSummitRegistrationPromoCodeRepository',
            function () {
                return EntityManager::getRepository(SpeakerSummitRegistrationPromoCode::class);
            });

        App::singleton(
            ISummitTicketTypeRepository::class,
            function () {
                return EntityManager::getRepository(SummitTicketType::class);
            });

        App::singleton(
            IOrganizationRepository::class,
            function () {
                return EntityManager::getRepository(Organization::class);
            });

        App::singleton(
            ISummitRegistrationPromoCodeRepository::class,
            function () {
                return EntityManager::getRepository(SummitRegistrationPromoCode::class);
            }
        );

        App::singleton(
            ISpeakersSummitRegistrationPromoCodeRepository::class,
            function () {
                return EntityManager::getRepository(SpeakersSummitRegistrationPromoCode::class);
            }
        );

        App::singleton(
            ISpeakersRegistrationDiscountCodeRepository::class,
            function () {
                return EntityManager::getRepository(SpeakersRegistrationDiscountCode::class);
            }
        );

        App::singleton(
            IPresentationSpeakerSummitAssistanceConfirmationRequestRepository::class,
            function () {
                return EntityManager::getRepository(PresentationSpeakerSummitAssistanceConfirmationRequest::class);
            }
        );

        App::singleton(
            ISummitEventTypeRepository::class,
            function () {
                return EntityManager::getRepository(SummitEventType::class);
            }
        );

        App::singleton(
            IDefaultSummitEventTypeRepository::class,
            function () {
                return EntityManager::getRepository(DefaultSummitEventType::class);
            }
        );

        App::singleton(
            ISummitTrackRepository::class,
            function () {
                return EntityManager::getRepository(PresentationCategory::class);
            }
        );

        App::singleton(
            IRSVPTemplateRepository::class,
            function () {
                return EntityManager::getRepository(RSVPTemplate::class);
            }
        );

        App::singleton(
            ISummitLocationRepository::class,
            function () {
                return EntityManager::getRepository(SummitAbstractLocation::class);
            }
        );

        App::singleton(
            ISummitLocationBannerRepository::class,
            function () {
                return EntityManager::getRepository(SummitLocationBanner::class);
            }
        );

        App::singleton(
            IPresentationCategoryGroupRepository::class,
            function () {
                return EntityManager::getRepository(PresentationCategoryGroup::class);
            }
        );

        App::singleton(
            ISelectionPlanRepository::class,
            function () {
                return EntityManager::getRepository(SelectionPlan::class);
            }
        );

        App::singleton(
            ITrackTagGroupAllowedTagsRepository::class,
            function () {
                return EntityManager::getRepository(TrackTagGroupAllowedTag::class);
            }
        );

        App::singleton(
            IDefaultTrackTagGroupRepository::class,
            function () {
                return EntityManager::getRepository(DefaultTrackTagGroup::class);
            }
        );


        App::singleton(
            ITrackQuestionTemplateRepository::class,
            function () {
                return EntityManager::getRepository(TrackQuestionTemplate::class);
            }
        );

        App::singleton(
            ILanguageRepository::class,
            function () {
                return EntityManager::getRepository(Language::class);
            }
        );

        App::singleton(
            ISpeakerOrganizationalRoleRepository::class,
            function () {
                return EntityManager::getRepository(SpeakerOrganizationalRole::class);
            }
        );

        App::singleton(
            ISpeakerActiveInvolvementRepository::class,
            function () {
                return EntityManager::getRepository(SpeakerActiveInvolvement::class);
            }
        );

        App::singleton(
            ISpeakerEditPermissionRequestRepository::class,
            function () {
                return EntityManager::getRepository(SpeakerEditPermissionRequest::class);
            }
        );

        App::singleton(
            ISummitRoomReservationRepository::class,
            function () {
                return EntityManager::getRepository(SummitRoomReservation::class);
            }
        );

        App::singleton(
            ISummitBookableVenueRoomAttributeTypeRepository::class,
            function () {
                return EntityManager::getRepository(SummitBookableVenueRoomAttributeType::class);
            }
        );

        App::singleton(
            ISummitBookableVenueRoomAttributeValueRepository::class,
            function () {
                return EntityManager::getRepository(SummitBookableVenueRoomAttributeValue::class);
            }
        );

        App::singleton(
            ISummitAccessLevelTypeRepository::class,
            function () {
                return EntityManager::getRepository(SummitAccessLevelType::class);
            }
        );

        App::singleton(
            ISummitTaxTypeRepository::class,
            function () {
                return EntityManager::getRepository(SummitTaxType::class);
            }
        );

        App::singleton(
            ISummitBadgeFeatureTypeRepository::class,
            function () {
                return EntityManager::getRepository(SummitBadgeFeatureType::class);
            }
        );

        App::singleton(
            ISummitBadgeTypeRepository::class,
            function () {
                return EntityManager::getRepository(SummitBadgeType::class);
            }
        );

        App::singleton(
            ISponsorRepository::class,
            function () {
                return EntityManager::getRepository(Sponsor::class);
            }
        );

        App::singleton(
            ISponsorshipTypeRepository::class,
            function () {
                return EntityManager::getRepository(SponsorshipType::class);
            }
        );

        App::singleton(
            ISummitRefundPolicyTypeRepository::class,
            function () {
                return EntityManager::getRepository(SummitRefundPolicyType::class);
            }
        );

        App::singleton(
            ISummitOrderExtraQuestionTypeRepository::class,
            function () {
                return EntityManager::getRepository(SummitOrderExtraQuestionType::class);
            }
        );

        App::singleton(
            ISummitOrderRepository::class,
            function () {
                return EntityManager::getRepository(SummitOrder::class);
            }
        );

        App::singleton(
            ISummitAttendeeBadgeRepository::class,
            function () {
                return EntityManager::getRepository(SummitAttendeeBadge::class);
            }
        );

        App::singleton(
            ISponsorUserInfoGrantRepository::class,
            function () {
                return EntityManager::getRepository(SponsorUserInfoGrant::class);
            }
        );

        App::singleton(
            ISummitAttendeeBadgePrintRuleRepository::class,
            function () {
                return EntityManager::getRepository(SummitAttendeeBadgePrintRule::class);
            }
        );

        App::singleton(
            IPaymentGatewayProfileRepository::class,
            function () {
                return EntityManager::getRepository(PaymentGatewayProfile::class);
            }
        );

        App::singleton(
            ISummitEmailEventFlowRepository::class,
            function () {
                return EntityManager::getRepository(SummitEmailEventFlow::class);
            }
        );

        App::singleton(
            ISummitDocumentRepository::class,
            function () {
                return EntityManager::getRepository(SummitDocument::class);
            }
        );

        App::singleton(
            ISummitRegistrationInvitationRepository::class,
            function () {
                return EntityManager::getRepository(SummitRegistrationInvitation::class);
            }
        );

        App::singleton(
            ISummitAdministratorPermissionGroupRepository::class,
            function () {
                return EntityManager::getRepository(SummitAdministratorPermissionGroup::class);
            }
        );

        App::singleton(
            ISummitMediaFileTypeRepository::class,
            function () {
                return EntityManager::getRepository(SummitMediaFileType::class);
            }
        );

        App::singleton(
            ISummitMediaUploadTypeRepository::class,
            function () {
                return EntityManager::getRepository(SummitMediaUploadType::class);
            }
        );

        App::singleton(
            ISummitMetricRepository::class,
            function () {
                return EntityManager::getRepository(SummitMetric::class);
            }
        );

        App::singleton(
            ISponsoredProjectRepository::class,
            function () {
                return EntityManager::getRepository(SponsoredProject::class);
            }
        );

        App::singleton(
            IProjectSponsorshipTypeRepository::class,
            function () {
                return EntityManager::getRepository(ProjectSponsorshipType::class);
            }
        );

        App::singleton(
            ISupportingCompanyRepository::class,
            function () {
                return EntityManager::getRepository(SupportingCompany::class);
            }
        );

        App::singleton(
            ILegalDocumentRepository::class,
            DoctrineLegalDocumentRepository::class
        );

        App::singleton(
            ISummitTrackChairRepository::class,
            function () {
                return EntityManager::getRepository(SummitTrackChair::class);
            }
        );

        App::singleton(
            ISummitCategoryChangeRepository::class,
            function () {
                return EntityManager::getRepository(SummitCategoryChange::class);
            }
        );

        App::singleton(
            IPresentationActionTypeRepository::class,
            function () {
                return EntityManager::getRepository(PresentationActionType::class);
            }
        );

        App::singleton(
            ISummitSelectionPlanExtraQuestionTypeRepository::class,
            function () {
                return EntityManager::getRepository(SummitSelectionPlanExtraQuestionType::class);
            }
        );

        App::singleton(
            IElectionsRepository::class,
            function () {
                return EntityManager::getRepository(Election::class);
            }
        );

        App::singleton(
            ISummitScheduleConfigRepository::class,
            function () {
                return EntityManager::getRepository(SummitScheduleConfig::class);
            }
        );

        App::singleton(
            IPresentationMediaUploadRepository::class,
            function () {
                return EntityManager::getRepository(PresentationMediaUpload::class);
            }
        );

        App::singleton(
            IOpenStackReleaseRepository::class,
            function () {
                return EntityManager::getRepository(OpenStackRelease::class);
            }
        );

        App::singleton(
            IPresentationTrackChairRatingTypeRepository::class,
            function () {
                return EntityManager::getRepository(PresentationTrackChairRatingType::class);
            }
        );

        App::singleton(
            IPresentationTrackChairScoreTypeRepository::class,
            function () {
                return EntityManager::getRepository(PresentationTrackChairScoreType::class);
            }
        );

        App::singleton(
            ISummitBadgeViewTypeRepository::class,
            function () {
                return EntityManager::getRepository(SummitBadgeViewType::class);
            }
        );

        App::singleton(
            ISponsorAdRepository::class,
            function(){
                return EntityManager::getRepository(SponsorAd::class);
            });

        App::singleton(
            ISponsorAdRepository::class,
            function(){
                return EntityManager::getRepository(SponsorAd::class);
            });

        App::singleton(
            ISponsorMaterialRepository::class,
            function(){
                return EntityManager::getRepository(SponsorMaterial::class);
            });

        App::singleton(
            ISponsorSocialNetworkRepository::class,
            function(){
                return EntityManager::getRepository(SponsorSocialNetwork::class);
            });

        App::singleton(
            ISponsorExtraQuestionTypeRepository::class,
            function(){
                return EntityManager::getRepository(SummitSponsorExtraQuestionType::class);
            });

        App::singleton(
            ISummitSponsorshipTypeRepository::class,
            function(){
                return EntityManager::getRepository(SummitSponsorshipType::class);
            }
        );

        App::singleton(
            IAuditLogRepository::class,
            function () {
                return EntityManager::getRepository(AuditLog::class);
            });

        App::singleton(
            ISummitPresentationCommentRepository::class,
            function(){
                return EntityManager::getRepository(SummitPresentationComment::class);
            }
        );

        App::singleton(
            ISummitProposedScheduleRepository::class,
            function(){
                return EntityManager::getRepository(SummitProposedSchedule::class);
            }
        );

        App::singleton(
             ISummitProposedScheduleEventRepository::class,
             function(){
                 return EntityManager::getRepository(SummitProposedScheduleSummitEvent::class);
             }
        );

        App::singleton(
            ISummitProposedScheduleLockRepository::class,
            function(){
                return EntityManager::getRepository(SummitProposedScheduleLock::class);
            }
        );
      
        App::singleton(
            ISummitSubmissionInvitationRepository::class,
            function(){
                return EntityManager::getRepository(SummitSubmissionInvitation::class);
            }
        );

        App::singleton(
            ISummitPresentationSpeakerAssignmentRepository::class,
            function(){
                return EntityManager::getRepository(PresentationSpeakerAssignment::class);
            }
        );

        App::singleton(
            ISummitSignRepository::class,
            function(){
                return EntityManager::getRepository(SummitSign::class);
            }
        );

        App::singleton(
            ISummitProposedScheduleAllowedLocationRepository::class,
            function(){
                return EntityManager::getRepository(SummitProposedScheduleAllowedLocation::class);
            }
        );

        App::singleton(
            ISummitProposedScheduleAllowedDayRepository::class,
            function (){
                return EntityManager::getRepository(SummitProposedScheduleAllowedDay::class);
            }
        );

        App::singleton(
            ISummitRegistrationFeedMetadataRepository::class,
            function (){
                return EntityManager::getRepository(SummitRegistrationFeedMetadata::class);
            }
        );

        App::singleton(
            ISummitAttendeeBadgePrintRepository::class,
            function (){
                return EntityManager::getRepository(SummitAttendeeBadgePrint::class);
            }
        );

        App::singleton(
            ISummitAttendeeNoteRepository::class,
            function (){
                return EntityManager::getRepository(SummitAttendeeNote::class);
            }
        );
    }
}