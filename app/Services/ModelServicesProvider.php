<?php namespace services;
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

use App\Models\Foundation\Summit\Registration\BuildDefaultPaymentGatewayProfileStrategy;
use App\Models\Foundation\Summit\Registration\IBuildDefaultPaymentGatewayProfileStrategy;
use App\Services\Apis\ExternalRegistrationFeeds\ExternalRegistrationFeedFactory;
use App\Services\Apis\ExternalRegistrationFeeds\IExternalRegistrationFeedFactory;
use App\Services\Apis\ExternalScheduleFeeds\ExternalScheduleFeedFactory;
use App\Services\Apis\ExternalScheduleFeeds\IExternalScheduleFeedFactory;
use App\Services\FileSystem\IFileDownloadStrategy;
use App\Services\FileSystem\IFileUploadStrategy;
use App\Services\FileSystem\Swift\SwiftStorageFileDownloadStrategy;
use App\Services\FileSystem\Swift\SwiftStorageFileUploadStrategy;
use App\Services\Model\AttendeeService;
use App\Services\Model\IAttendeeService;
use App\Services\Model\IBadgeViewTypeService;
use App\Services\Model\ICompanyService;
use App\Services\Model\IElectionService;
use App\Services\Model\ILocationService;
use App\Services\Model\IMemberService;
use App\Services\Model\Imp\BadgeViewTypeService;
use App\Services\Model\Imp\CompanyService;
use App\Services\Model\Imp\ElectionService;
use App\Services\Model\Imp\PaymentGatewayProfileService;
use App\Services\Model\Imp\PresentationVideoMediaUploadProcessor;
use App\Services\Model\Imp\ProcessScheduleEntityLifeCycleEventService;
use App\Services\Model\Imp\RegistrationIngestionService;
use App\Services\Model\Imp\SelectionPlanOrderExtraQuestionTypeService;
use App\Services\Model\Imp\SponsoredProjectService;
use App\Services\Model\Imp\SponsorUserInfoGrantService;
use App\Services\Model\Imp\SponsorUserSyncService;
use App\Services\Model\Imp\SummitAdministratorPermissionGroupService;
use App\Services\Model\Imp\SummitDocumentService;
use App\Services\Model\Imp\SummitEmailEventFlowService;
use App\Services\Model\Imp\SummitMediaFileTypeService;
use App\Services\Model\Imp\SummitMediaUploadTypeService;
use App\Services\Model\Imp\SummitMetricService;
use App\Services\Model\Imp\SummitPresentationActionService;
use App\Services\Model\Imp\SummitPresentationActionTypeService;
use App\Services\Model\Imp\SummitProposedScheduleAllowedLocationService;
use App\Services\Model\Imp\SummitRegistrationInvitationService;
use App\Services\Model\Imp\SummitScheduleSettingsService;
use App\Services\Model\Imp\SummitSelectedPresentationListService;
use App\Services\Model\Imp\SummitSignService;
use App\Services\Model\Imp\SummitSponsorshipTypeService;
use App\Services\Model\Imp\SummitSubmissionInvitationService;
use App\Services\Model\Imp\TrackChairRankingService;
use App\Services\Model\Imp\TrackChairService;
use App\Services\Model\IOrganizationService;
use App\Services\Model\IPaymentGatewayProfileService;
use App\Services\Model\IPresentationCategoryGroupService;
use App\Services\Model\IPresentationVideoMediaUploadProcessor;
use App\Services\Model\IProcessScheduleEntityLifeCycleEventService;
use App\Services\Model\IRegistrationIngestionService;
use App\Services\Model\IRSVPTemplateService;
use App\Services\Model\IScheduleIngestionService;
use App\Services\Model\IScheduleService;
use App\Services\Model\ISelectionPlanExtraQuestionTypeService;
use App\Services\Model\ISponsoredProjectService;
use App\Services\Model\ISponsorshipTypeService;
use App\Services\Model\ISponsorUserInfoGrantService;
use App\Services\Model\ISponsorUserSyncService;
use App\Services\Model\ISummitAccessLevelTypeService;
use App\Services\Model\ISummitAdministratorPermissionGroupService;
use App\Services\Model\ISummitBadgeFeatureTypeService;
use App\Services\Model\ISummitBadgeTypeService;
use App\Services\Model\ISummitDocumentService;
use App\Services\Model\ISummitEmailEventFlowService;
use App\Services\Model\ISummitEventTypeService;
use App\Services\Model\ISummitMediaFileTypeService;
use App\Services\Model\ISummitMediaUploadTypeService;
use App\Services\Model\ISummitMetricService;
use App\Services\Model\ISummitOrderExtraQuestionTypeService;
use App\Services\Model\ISummitOrderService;
use App\Services\Model\ISummitPresentationActionService;
use App\Services\Model\ISummitPresentationActionTypeService;
use App\Services\Model\ISummitProposedScheduleAllowedLocationService;
use App\Services\Model\ISummitPushNotificationService;
use App\Services\Model\ISummitRefundPolicyTypeService;
use App\Services\Model\ISummitRegistrationInvitationService;
use App\Services\Model\ISummitScheduleSettingsService;
use App\Services\Model\ISummitSelectedPresentationListService;
use App\Services\Model\ISummitSelectionPlanService;
use App\Services\Model\ISummitSignService;
use App\Services\Model\ISummitSponsorshipTypeService;
use App\Services\Model\ISummitSubmissionInvitationService;
use App\Services\Model\ISummitTaxTypeService;
use App\Services\Model\ISummitTicketTypeService;
use App\Services\Model\ISummitTrackService;
use App\Services\Model\ISummitTrackTagGroupService;
use App\Services\Model\ITagService;
use App\Services\Model\ITrackChairRankingService;
use App\Services\Model\ITrackChairService;
use App\Services\Model\ITrackQuestionTemplateService;
use App\Services\Model\MemberService;
use App\Services\Model\OrganizationService;
use App\Services\Model\PresentationCategoryGroupService;
use App\Services\Model\RSVPTemplateService;
use App\Services\Model\ScheduleIngestionService;
use App\Services\Model\ScheduleService;
use App\Services\Model\SponsorshipTypeService;
use App\Services\Model\Strategies\PromoCodes\IPromoCodeStrategyFactory;
use App\Services\Model\Strategies\PromoCodes\PromoCodeStrategyFactory;
use App\Services\Model\Strategies\TicketFinder\ITicketFinderStrategyFactory;
use App\Services\Model\Strategies\TicketFinder\TicketFinderStrategyFactory;
use App\Services\Model\SummitAccessLevelTypeService;
use App\Services\Model\SummitBadgeFeatureTypeService;
use App\Services\Model\SummitBadgeTypeService;
use App\Services\Model\SummitLocationService;
use App\Services\Model\SummitOrderService;
use App\Services\Model\SummitPromoCodeService;
use App\Services\Model\SummitPushNotificationService;
use App\Services\Model\SummitSelectionPlanService;
use App\Services\Model\SummitTaxTypeService;
use App\Services\Model\SummitTicketTypeService;
use App\Services\Model\SummitTrackService;
use App\Services\Model\SummitTrackTagGroupService;
use App\Services\Model\TagService;
use App\Services\Model\TrackQuestionTemplateService;
use App\Services\SummitEventTypeService;
use App\Services\SummitOrderExtraQuestionTypeService;
use App\Services\SummitRefundPolicyTypeService;
use App\Services\SummitSponsorService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;
use services\model\ChatTeamService;
use services\model\IChatTeamService;
use services\model\IPresentationService;
use services\model\ISpeakerService;
use services\model\ISubmitterService;
use services\model\ISummitAttendeeBadgePrintService;
use services\model\ISummitPromoCodeService;
use services\model\ISummitService;
use services\model\ISummitSponsorService;
use services\model\PresentationService;
use services\model\SpeakerService;
use services\model\SubmitterService;
use services\model\SummitAttendeeBadgePrintService;
use services\model\SummitService;

/***
 * Class ModelServicesProvider
 * @package services
 */
final class ModelServicesProvider extends ServiceProvider
{
    protected $defer = true;

    public function boot()
    {
    }

    public function register()
    {
        App::when(SummitService::class)->needs(IFileDownloadStrategy::class)->give(SwiftStorageFileDownloadStrategy::class);
        App::when(SummitService::class)->needs(IFileUploadStrategy::class)->give(SwiftStorageFileUploadStrategy::class);

        App::when(SummitOrderService::class)->needs(IFileDownloadStrategy::class)->give(SwiftStorageFileDownloadStrategy::class);
        App::when(SummitOrderService::class)->needs(IFileUploadStrategy::class)->give(SwiftStorageFileUploadStrategy::class);

        App::when(SummitSelectionPlanService::class)->needs(IFileDownloadStrategy::class)->give(SwiftStorageFileDownloadStrategy::class);
        App::when(SummitSelectionPlanService::class)->needs(IFileUploadStrategy::class)->give(SwiftStorageFileUploadStrategy::class);

        // add bindings for service
        App::singleton(ISummitService::class, SummitService::class);

        App::singleton(ISpeakerService::class, SpeakerService::class);

        App::singleton(ISubmitterService::class, SubmitterService::class);

        App::singleton(IPresentationService::class, PresentationService::class);

        App::singleton(IChatTeamService::class, ChatTeamService::class);

        App::singleton
        (
            IAttendeeService::class,
            AttendeeService::class
        );

        App::singleton(
            IMemberService::class,
            MemberService::class
        );

        App::singleton
        (
            ISummitPromoCodeService::class,
            SummitPromoCodeService::class
        );

        App::singleton
        (
            ISummitEventTypeService::class,
            SummitEventTypeService::class
        );

        App::singleton
        (
            ISummitTrackService::class,
            SummitTrackService::class
        );

        App::singleton
        (
            ILocationService::class,
            SummitLocationService::class
        );

        App::singleton
        (
            IRSVPTemplateService::class,
            RSVPTemplateService::class
        );

        App::singleton
        (
            ISummitTicketTypeService::class,
            SummitTicketTypeService::class
        );

        App::singleton
        (
            IPresentationCategoryGroupService::class,
            PresentationCategoryGroupService::class
        );

        App::singleton(
            ISummitPushNotificationService::class,
            SummitPushNotificationService::class
        );

        App::singleton(
            ISummitSelectionPlanService::class,
            SummitSelectionPlanService::class
        );

        App::singleton(
            IOrganizationService::class,
            OrganizationService::class
        );

        App::singleton(
            ICompanyService::class,
            CompanyService::class
        );

        App::singleton(
            ISummitTrackTagGroupService::class,
            SummitTrackTagGroupService::class
        );

        App::singleton(
            ITrackQuestionTemplateService::class,
            TrackQuestionTemplateService::class
        );

        App::singleton(
            ITagService::class,
            TagService::class
        );

        App::singleton(
            IExternalScheduleFeedFactory::class,
            ExternalScheduleFeedFactory::class
        );

        App::singleton(
            IScheduleIngestionService::class,
            ScheduleIngestionService::class
        );

        App::singleton
        (
            ISummitAccessLevelTypeService::class,
            SummitAccessLevelTypeService::class
        );

        App::singleton
        (
            ISummitTaxTypeService::class,
            SummitTaxTypeService::class
        );

        App::singleton
        (
            ISummitBadgeFeatureTypeService::class,
            SummitBadgeFeatureTypeService::class
        );

        App::singleton
        (
            ISummitBadgeTypeService::class,
            SummitBadgeTypeService::class
        );

        App::singleton(
            ISummitSponsorService::class,
            SummitSponsorService::class
        );

        App::singleton(
            ISummitRefundPolicyTypeService::class,
            SummitRefundPolicyTypeService::class
        );

        App::singleton(
            ISummitOrderExtraQuestionTypeService::class,
            SummitOrderExtraQuestionTypeService::class
        );

        App::singleton(
            ISponsorshipTypeService::class,
            SponsorshipTypeService::class
        );

        App::singleton(ISummitOrderService::class, SummitOrderService::class);

        App::singleton(ISponsorUserInfoGrantService::class,
            SponsorUserInfoGrantService::class);

        App::singleton(
            IRegistrationIngestionService::class,
            RegistrationIngestionService::class
        );

        App::singleton(
            IExternalRegistrationFeedFactory::class,
            ExternalRegistrationFeedFactory::class
        );

        App::singleton(
            IPaymentGatewayProfileService::class,
            PaymentGatewayProfileService::class
        );

        App::singleton(
            IBuildDefaultPaymentGatewayProfileStrategy::class,
            BuildDefaultPaymentGatewayProfileStrategy::class
        );

        App::singleton(
            ISummitEmailEventFlowService::class,
            SummitEmailEventFlowService::class
        );

        App::singleton(
            ISummitDocumentService::class,
            SummitDocumentService::class
        );

        App::singleton
        (
            ISummitRegistrationInvitationService::class,
            SummitRegistrationInvitationService::class
        );

        App::singleton
        (
            ISummitAdministratorPermissionGroupService::class,
            SummitAdministratorPermissionGroupService::class
        );

        App::singleton
        (
            ISummitMediaFileTypeService::class,
            SummitMediaFileTypeService::class
        );

        App::singleton
        (
            ISummitMediaUploadTypeService::class,
            SummitMediaUploadTypeService::class
        );

        App::singleton
        (
            IPresentationVideoMediaUploadProcessor::class,
            PresentationVideoMediaUploadProcessor::class
        );

        App::singleton
        (
            ISummitMetricService::class,
            SummitMetricService::class
        );

        App::singleton
        (
            ISponsoredProjectService::class,
            SponsoredProjectService::class
        );

        App::singleton
        (
            ISummitSelectedPresentationListService::class,
            SummitSelectedPresentationListService::class
        );

        App::singleton
        (
            ITrackChairService::class,
            TrackChairService::class
        );

        App::singleton(
            ISummitPresentationActionTypeService::class,
            SummitPresentationActionTypeService::class
        );

        App::singleton(
            ISummitPresentationActionService::class,
            SummitPresentationActionService::class
        );

        App::singleton(
            ISelectionPlanExtraQuestionTypeService::class,
            SelectionPlanOrderExtraQuestionTypeService::class
        );

        App::singleton(
            IElectionService::class,
            ElectionService::class
        );

        App::singleton(
            ISummitScheduleSettingsService::class,
            SummitScheduleSettingsService::class
        );

        App::singleton(
            ITrackChairRankingService::class,
            TrackChairRankingService::class
        );

        App::singleton(
            IBadgeViewTypeService::class,
            BadgeViewTypeService::class,
        );

        App::singleton(
            ISummitSponsorshipTypeService::class,
            SummitSponsorshipTypeService::class
        );

        App::singleton(
            IProcessScheduleEntityLifeCycleEventService::class,
            ProcessScheduleEntityLifeCycleEventService::class,
        );

        App::singleton(
            IScheduleService::class,
            ScheduleService::class,
        );

        App::singleton(
            ISummitSubmissionInvitationService::class,
            SummitSubmissionInvitationService::class,
        );

        App::singleton(
            ISummitSignService::class,
            SummitSignService::class,
        );

        App::singleton(

            ISummitProposedScheduleAllowedLocationService::class,
            SummitProposedScheduleAllowedLocationService::class
        );

        App::singleton(
            ITicketFinderStrategyFactory::class,
            TicketFinderStrategyFactory::class
        );
        App::singleton(

            IPromoCodeStrategyFactory::class,
            PromoCodeStrategyFactory::class,
        );

        App::singleton(
            ISummitAttendeeBadgePrintService::class,
            SummitAttendeeBadgePrintService::class
        );

        App::singleton(
            ISponsorUserSyncService::class,
            SponsorUserSyncService::class
        );
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            ISummitService::class,
            ISpeakerService::class,
            IPresentationService::class,
            IChatTeamService::class,
            IAttendeeService::class,
            IMemberService::class,
            ISummitPromoCodeService::class,
            ISummitEventTypeService::class,
            ISummitTrackService::class,
            ILocationService::class,
            IRSVPTemplateService::class,
            ISummitTicketTypeService::class,
            IPresentationCategoryGroupService::class,
            ISummitPushNotificationService::class,
            ISummitSelectionPlanService::class,
            IOrganizationService::class,
            ICompanyService::class,
            ISummitTrackTagGroupService::class,
            ITrackQuestionTemplateService::class,
            ITagService::class,
            IExternalScheduleFeedFactory::class,
            IScheduleIngestionService::class,
            ISummitAccessLevelTypeService::class,
            ISummitTaxTypeService::class,
            ISummitBadgeFeatureTypeService::class,
            ISummitBadgeTypeService::class,
            ISummitSponsorService::class,
            ISummitRefundPolicyTypeService::class,
            ISummitOrderExtraQuestionTypeService::class,
            ISponsorshipTypeService::class,
            ISummitOrderService::class,
            IRegistrationIngestionService::class,
            IExternalRegistrationFeedFactory::class,
            IPaymentGatewayProfileService::class,
            IBuildDefaultPaymentGatewayProfileStrategy::class,
            ISummitEmailEventFlowService::class,
            ISummitDocumentService::class,
            ISummitRegistrationInvitationService::class,
            ISummitAdministratorPermissionGroupService::class,
            ISummitMediaFileTypeService::class,
            ISummitMediaUploadTypeService::class,
            IPresentationVideoMediaUploadProcessor::class,
            ISummitMetricService::class,
            ISummitSelectedPresentationListService::class,
            ITrackChairService::class,
            ISummitPresentationActionTypeService::class,
            ISummitPresentationActionService::class,
            ISelectionPlanExtraQuestionTypeService::class,
            IElectionService::class,
            ISummitScheduleSettingsService::class,
            IBadgeViewTypeService::class,
            ISummitSponsorshipTypeService::class,
            IProcessScheduleEntityLifeCycleEventService::class,
            IScheduleService::class,
            ISummitSubmissionInvitationService::class,
            ISummitSignService::class,
            ISummitProposedScheduleAllowedLocationService::class,
            ITicketFinderStrategyFactory::class,
            IPromoCodeStrategyFactory::class,
            ISummitAttendeeBadgePrintService::class,
            ISponsorUserSyncService::class
        ];
    }
}