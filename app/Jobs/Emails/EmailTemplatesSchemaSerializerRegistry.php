<?php namespace App\Jobs\Emails;

/**
 * Copyright 2023 OpenStack Foundation
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

use App\Jobs\Emails\BookableRooms\AbstractBookableRoomReservationEmail;
use App\Jobs\Emails\BookableRooms\BookableRoomReservationCanceledEmail;
use App\Jobs\Emails\BookableRooms\BookableRoomReservationCreatedEmail;
use App\Jobs\Emails\BookableRooms\BookableRoomReservationPaymentConfirmedEmail;
use App\Jobs\Emails\BookableRooms\BookableRoomReservationRefundAcceptedEmail;
use App\Jobs\Emails\BookableRooms\BookableRoomReservationRefundRequestedAdminEmail;
use App\Jobs\Emails\BookableRooms\BookableRoomReservationRefundRequestedOwnerEmail;
use App\Jobs\Emails\Elections\NominationEmail;
use App\Jobs\Emails\PresentationSelections\PresentationCategoryChangeRequestCreatedEmail;
use App\Jobs\Emails\PresentationSubmissions\ImportEventSpeakerEmail;
use App\Jobs\Emails\PresentationSubmissions\Invitations\InviteSubmissionEmail;
use App\Jobs\Emails\PresentationSubmissions\Invitations\ReInviteSubmissionEmail;
use App\Jobs\Emails\PresentationSubmissions\PresentationCreatorNotificationEmail;
use App\Jobs\Emails\PresentationSubmissions\PresentationModeratorNotificationEmail;
use App\Jobs\Emails\PresentationSubmissions\PresentationSpeakerNotificationEmail;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSpeakerSelectionProcessAcceptedAlternateEmail;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSpeakerSelectionProcessAcceptedOnlyEmail;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSpeakerSelectionProcessAcceptedRejectedEmail;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSpeakerSelectionProcessAlternateOnlyEmail;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSpeakerSelectionProcessAlternateRejectedEmail;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSpeakerSelectionProcessEmail;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSpeakerSelectionProcessExcerptEmail;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSpeakerSelectionProcessRejectedOnlyEmail;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSubmitterSelectionProcessAcceptedAlternateEmail;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSubmitterSelectionProcessAcceptedOnlyEmail;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSubmitterSelectionProcessAcceptedRejectedEmail;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSubmitterSelectionProcessAlternateOnlyEmail;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSubmitterSelectionProcessAlternateRejectedEmail;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSubmitterSelectionProcessEmail;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSubmitterSelectionProcessExcerptEmail;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSubmitterSelectionProcessRejectedOnlyEmail;
use App\Jobs\Emails\PresentationSubmissions\SpeakerCreationEmail;
use App\Jobs\Emails\PresentationSubmissions\SpeakerEditPermissionApprovedEmail;
use App\Jobs\Emails\PresentationSubmissions\SpeakerEditPermissionRejectedEmail;
use App\Jobs\Emails\PresentationSubmissions\SpeakerEditPermissionRequestedEmail;
use App\Jobs\Emails\ProposedSchedule\SubmitForReviewEmail;
use App\Jobs\Emails\ProposedSchedule\UnsubmitForReviewEmail;
use App\Jobs\Emails\Registration\Attendees\GenericSummitAttendeeEmail;
use App\Jobs\Emails\Registration\ExternalIngestion\SuccessfulIIngestionEmail;
use App\Jobs\Emails\Registration\ExternalIngestion\UnsuccessfulIIngestionEmail;
use App\Jobs\Emails\Registration\Invitations\InviteSummitRegistrationEmail;
use App\Jobs\Emails\Registration\Invitations\ReInviteSummitRegistrationEmail;
use App\Jobs\Emails\Registration\MemberPromoCodeEmail;
use App\Jobs\Emails\Registration\PromoCodeEmail;
use App\Jobs\Emails\Registration\Refunds\SummitOrderRefundAccepted;
use App\Jobs\Emails\Registration\Refunds\SummitOrderRefundRequestAdmin;
use App\Jobs\Emails\Registration\Refunds\SummitOrderRefundRequestOwner;
use App\Jobs\Emails\Registration\Refunds\SummitTicketRefundAccepted;
use App\Jobs\Emails\Registration\Refunds\SummitTicketRefundRejected;
use App\Jobs\Emails\Registration\Refunds\SummitTicketRefundRequestAdmin;
use App\Jobs\Emails\Registration\Refunds\SummitTicketRefundRequestOwner;
use App\Jobs\Emails\Registration\Reminders\SummitOrderReminderEmail;
use App\Jobs\Emails\Registration\Reminders\SummitTicketReminderEmail;
use App\Jobs\Emails\Registration\SpeakerPromoCodeEMail;
use App\Jobs\Emails\Schedule\RSVPMail;
use App\Jobs\Emails\Schedule\RSVPRegularSeatMail;
use App\Jobs\Emails\Schedule\RSVPWaitListSeatMail;
use App\Jobs\Emails\Schedule\ShareEventEmail;
use Illuminate\Support\Facades\Log;

/**
 * Class EmailTemplatesSchemaSerializerRegistry
 * @package App\Jobs\Emails
 */
final class EmailTemplatesSchemaSerializerRegistry
{
    /**
     * @var EmailTemplatesSchemaSerializerRegistry
     */
    private static $instance;

    /**
     * @return EmailTemplatesSchemaSerializerRegistry
     */
    public static function getInstance()
    {
        if (!is_object(self::$instance)) {
            self::$instance = new EmailTemplatesSchemaSerializerRegistry();
        }
        return self::$instance;
    }

    private $registry = [];

    private function __construct()
    {
        //Bookable Rooms

        $this->registry[BookableRoomReservationCanceledEmail::EVENT_SLUG] = AbstractBookableRoomReservationEmail::class;
        $this->registry[BookableRoomReservationCreatedEmail::EVENT_SLUG] = AbstractBookableRoomReservationEmail::class;
        $this->registry[BookableRoomReservationPaymentConfirmedEmail::EVENT_SLUG] = AbstractBookableRoomReservationEmail::class;
        $this->registry[BookableRoomReservationRefundAcceptedEmail::EVENT_SLUG] = AbstractBookableRoomReservationEmail::class;
        $this->registry[BookableRoomReservationRefundRequestedAdminEmail::EVENT_SLUG] = AbstractBookableRoomReservationEmail::class;
        $this->registry[BookableRoomReservationRefundRequestedOwnerEmail::EVENT_SLUG] = AbstractBookableRoomReservationEmail::class;

        //Elections

        $this->registry[NominationEmail::EVENT_SLUG] = NominationEmail::class;

        //Presentation Selections

        $this->registry[PresentationCategoryChangeRequestCreatedEmail::EVENT_SLUG] = PresentationCategoryChangeRequestCreatedEmail::class;

        //Presentation Submissions

        $this->registry[InviteSubmissionEmail::EVENT_SLUG] = InviteSubmissionEmail::class;
        $this->registry[ReInviteSubmissionEmail::EVENT_SLUG] = InviteSubmissionEmail::class;

        $this->registry[PresentationSpeakerSelectionProcessAcceptedAlternateEmail::EVENT_SLUG] = PresentationSpeakerSelectionProcessAcceptedAlternateEmail::class;
        $this->registry[PresentationSpeakerSelectionProcessAcceptedOnlyEmail::EVENT_SLUG] = PresentationSpeakerSelectionProcessAcceptedOnlyEmail::class;
        $this->registry[PresentationSpeakerSelectionProcessAcceptedRejectedEmail::EVENT_SLUG] = PresentationSpeakerSelectionProcessAcceptedRejectedEmail::class;
        $this->registry[PresentationSpeakerSelectionProcessAlternateOnlyEmail::EVENT_SLUG] = PresentationSpeakerSelectionProcessAlternateOnlyEmail::class;
        $this->registry[PresentationSpeakerSelectionProcessAlternateRejectedEmail::EVENT_SLUG] = PresentationSpeakerSelectionProcessAlternateRejectedEmail::class;
        $this->registry[PresentationSpeakerSelectionProcessRejectedOnlyEmail::EVENT_SLUG] = PresentationSpeakerSelectionProcessEmail::class;
        $this->registry[PresentationSubmitterSelectionProcessAcceptedAlternateEmail::EVENT_SLUG] = PresentationSubmitterSelectionProcessEmail::class;
        $this->registry[PresentationSubmitterSelectionProcessAcceptedOnlyEmail::EVENT_SLUG] = PresentationSubmitterSelectionProcessEmail::class;
        $this->registry[PresentationSubmitterSelectionProcessAcceptedRejectedEmail::EVENT_SLUG] = PresentationSubmitterSelectionProcessEmail::class;
        $this->registry[PresentationSubmitterSelectionProcessAlternateOnlyEmail::EVENT_SLUG] = PresentationSubmitterSelectionProcessEmail::class;
        $this->registry[PresentationSubmitterSelectionProcessAlternateRejectedEmail::EVENT_SLUG] = PresentationSubmitterSelectionProcessEmail::class;
        $this->registry[PresentationSubmitterSelectionProcessRejectedOnlyEmail::EVENT_SLUG] = PresentationSubmitterSelectionProcessEmail::class;
        $this->registry[PresentationSpeakerSelectionProcessExcerptEmail::EVENT_SLUG] = PresentationSpeakerSelectionProcessExcerptEmail::class;
        $this->registry[PresentationSubmitterSelectionProcessExcerptEmail::EVENT_SLUG] = PresentationSubmitterSelectionProcessExcerptEmail::class;

        $this->registry[ImportEventSpeakerEmail::EVENT_SLUG] = ImportEventSpeakerEmail::class;
        $this->registry[PresentationCreatorNotificationEmail::EVENT_SLUG] = PresentationCreatorNotificationEmail::class;
        $this->registry[PresentationModeratorNotificationEmail::EVENT_SLUG] = PresentationModeratorNotificationEmail::class;
        $this->registry[PresentationSpeakerNotificationEmail::EVENT_SLUG] = PresentationSpeakerNotificationEmail::class;
        $this->registry[SpeakerCreationEmail::EVENT_SLUG] = SpeakerCreationEmail::class;
        $this->registry[SpeakerEditPermissionApprovedEmail::EVENT_SLUG] = SpeakerEditPermissionApprovedEmail::class;
        $this->registry[SpeakerEditPermissionRejectedEmail::EVENT_SLUG] = SpeakerEditPermissionRejectedEmail::class;
        $this->registry[SpeakerEditPermissionRequestedEmail::EVENT_SLUG] = SpeakerEditPermissionRequestedEmail::class;

        //Proposed Schedule

        $this->registry[SubmitForReviewEmail::EVENT_SLUG] = SubmitForReviewEmail::class;
        $this->registry[UnsubmitForReviewEmail::EVENT_SLUG] = UnsubmitForReviewEmail::class;

        //Registration

        $this->registry[GenericSummitAttendeeEmail::EVENT_SLUG] = GenericSummitAttendeeEmail::class;
        $this->registry[InviteAttendeeTicketEditionMail::EVENT_SLUG] = InviteAttendeeTicketEditionMail::class;
        $this->registry[RevocationTicketEmail::EVENT_SLUG] = RevocationTicketEmail::class;
        $this->registry[SummitAttendeeAllTicketsEditionEmail::EVENT_SLUG] = SummitAttendeeAllTicketsEditionEmail::class;
        $this->registry[SummitAttendeeRegistrationIncompleteReminderEmail::EVENT_SLUG] = SummitAttendeeRegistrationIncompleteReminderEmail::class;
        $this->registry[SummitAttendeeTicketEmail::EVENT_SLUG] = SummitAttendeeTicketEmail::class;
        $this->registry[SummitAttendeeTicketRegenerateHashEmail::EVENT_SLUG] = InviteAttendeeTicketEditionMail::class;

        $this->registry[SuccessfulIIngestionEmail::EVENT_SLUG] = SuccessfulIIngestionEmail::class;
        $this->registry[UnsuccessfulIIngestionEmail::EVENT_SLUG] = UnsuccessfulIIngestionEmail::class;

        $this->registry[InviteSummitRegistrationEmail::EVENT_SLUG] = InviteSummitRegistrationEmail::class;
        $this->registry[ReInviteSummitRegistrationEmail::EVENT_SLUG] = ReInviteSummitRegistrationEmail::class;

        $this->registry[SummitOrderRefundAccepted::EVENT_SLUG] = SummitOrderRefundRequestOwner::class;
        $this->registry[SummitOrderRefundRequestAdmin::EVENT_SLUG] = SummitOrderRefundRequestAdmin::class;
        $this->registry[SummitOrderRefundRequestOwner::EVENT_SLUG] = SummitOrderRefundRequestOwner::class;
        $this->registry[SummitTicketRefundAccepted::EVENT_SLUG] = SummitTicketRefundRequestOwner::class;
        $this->registry[SummitTicketRefundRejected::EVENT_SLUG] = SummitTicketRefundRequestOwner::class;
        $this->registry[SummitTicketRefundRequestAdmin::EVENT_SLUG] = SummitTicketRefundRequestAdmin::class;
        $this->registry[SummitTicketRefundRequestOwner::EVENT_SLUG] = SummitTicketRefundRequestOwner::class;

        $this->registry[SummitOrderReminderEmail::EVENT_SLUG] = SummitOrderReminderEmail::class;
        $this->registry[SummitTicketReminderEmail::EVENT_SLUG] = SummitTicketReminderEmail::class;

        $this->registry[MemberPromoCodeEmail::EVENT_SLUG] = PromoCodeEmail::class;
        $this->registry[RegisteredMemberOrderPaidMail::EVENT_SLUG] = RegisteredMemberOrderPaidMail::class;
        $this->registry[SpeakerPromoCodeEMail::EVENT_SLUG] = PromoCodeEmail::class;
        $this->registry[UnregisteredMemberOrderPaidMail::EVENT_SLUG] = UnregisteredMemberOrderPaidMail::class;

        //Schedule

        $this->registry[RSVPRegularSeatMail::EVENT_SLUG] = RSVPMail::class;
        $this->registry[RSVPWaitListSeatMail::EVENT_SLUG] = RSVPMail::class;
        $this->registry[ShareEventEmail::EVENT_SLUG] = ShareEventEmail::class;
    }

    /**
     * @param string $slug
     * @return mixed
     */
    public function serialize(string $slug)
    {
        if (!isset($this->registry[$slug])) {
            Log::warning('Emails template schema builder not found for ' . $slug);
            return [];
        }

        $builder_class = $this->registry[$slug];

        return $builder_class::getEmailTemplateSchema();
    }
}