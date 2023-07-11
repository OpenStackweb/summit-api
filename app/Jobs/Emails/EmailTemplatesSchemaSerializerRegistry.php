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
use App\Jobs\Emails\PresentationSubmissions\PresentationCreatorNotificationEmail;
use App\Jobs\Emails\PresentationSubmissions\PresentationModeratorNotificationEmail;
use App\Jobs\Emails\PresentationSubmissions\PresentationSpeakerNotificationEmail;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSpeakerSelectionProcessAcceptedOnlyEmail;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSpeakerSelectionProcessAcceptedRejectedEmail;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSpeakerSelectionProcessAlternateOnlyEmail;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSpeakerSelectionProcessAlternateRejectedEmail;
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
use App\Jobs\Emails\Registration\Refunds\SummitOrderRefundRequestAdmin;
use App\Jobs\Emails\Registration\Refunds\SummitOrderRefundRequestOwner;
use App\Jobs\Emails\Registration\Refunds\SummitTicketRefundRequestAdmin;
use App\Jobs\Emails\Registration\Refunds\SummitTicketRefundRequestOwner;
use App\Jobs\Emails\Registration\Reminders\SummitOrderReminderEmail;
use App\Jobs\Emails\Registration\Reminders\SummitTicketReminderEmail;
use App\Jobs\Emails\Schedule\ShareEventEmail;

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

        $this->registry[BookableRoomReservationCanceledEmail::EVENT_SLUG] = AbstractEmailJob::class;
        $this->registry[BookableRoomReservationCreatedEmail::EVENT_SLUG] = AbstractEmailJob::class;
        $this->registry[BookableRoomReservationPaymentConfirmedEmail::EVENT_SLUG] = AbstractEmailJob::class;
        $this->registry[BookableRoomReservationRefundAcceptedEmail::EVENT_SLUG] = AbstractEmailJob::class;
        $this->registry[BookableRoomReservationRefundRequestedAdminEmail::EVENT_SLUG] = AbstractEmailJob::class;
        $this->registry[BookableRoomReservationRefundRequestedOwnerEmail::EVENT_SLUG] = AbstractEmailJob::class;

        //Elections

        $this->registry[NominationEmail::EVENT_SLUG] = AbstractEmailJob::class;

        //Presentation Selections

        $this->registry[PresentationCategoryChangeRequestCreatedEmail::EVENT_SLUG] = AbstractEmailJob::class;

        //Presentation Submissions

        $this->registry[InviteSubmissionEmail::EVENT_SLUG] = AbstractEmailJob::class;
        $this->registry[PresentationSpeakerSelectionProcessAcceptedOnlyEmail::EVENT_SLUG] = AbstractEmailJob::class;
        $this->registry[PresentationSpeakerSelectionProcessAcceptedRejectedEmail::EVENT_SLUG] = AbstractEmailJob::class;
        $this->registry[PresentationSpeakerSelectionProcessAlternateOnlyEmail::EVENT_SLUG] = AbstractEmailJob::class;
        $this->registry[PresentationSpeakerSelectionProcessAlternateRejectedEmail::EVENT_SLUG] = AbstractEmailJob::class;
        $this->registry[ImportEventSpeakerEmail::EVENT_SLUG] = AbstractEmailJob::class;
        $this->registry[PresentationCreatorNotificationEmail::EVENT_SLUG] = AbstractEmailJob::class;
        $this->registry[PresentationModeratorNotificationEmail::EVENT_SLUG] = AbstractEmailJob::class;
        $this->registry[PresentationSpeakerNotificationEmail::EVENT_SLUG] = AbstractEmailJob::class;
        $this->registry[SpeakerCreationEmail::EVENT_SLUG] = AbstractEmailJob::class;
        $this->registry[SpeakerEditPermissionApprovedEmail::EVENT_SLUG] = AbstractEmailJob::class;
        $this->registry[SpeakerEditPermissionRejectedEmail::EVENT_SLUG] = AbstractEmailJob::class;
        $this->registry[SpeakerEditPermissionRequestedEmail::EVENT_SLUG] = AbstractEmailJob::class;

        //Proposed Schedule

        $this->registry[SubmitForReviewEmail::EVENT_SLUG] = AbstractEmailJob::class;
        $this->registry[UnsubmitForReviewEmail::EVENT_SLUG] = AbstractEmailJob::class;

        //Registration

        $this->registry[GenericSummitAttendeeEmail::EVENT_SLUG] = AbstractEmailJob::class;
        $this->registry[InviteAttendeeTicketEditionMail::EVENT_SLUG] = AbstractEmailJob::class;
        $this->registry[RevocationTicketEmail::EVENT_SLUG] = AbstractEmailJob::class;
        $this->registry[SummitAttendeeAllTicketsEditionEmail::EVENT_SLUG] = AbstractEmailJob::class;
        $this->registry[SummitAttendeeRegistrationIncompleteReminderEmail::EVENT_SLUG] = AbstractEmailJob::class;
        $this->registry[SummitAttendeeTicketEmail::EVENT_SLUG] = AbstractEmailJob::class;

        $this->registry[SuccessfulIIngestionEmail::EVENT_SLUG] = AbstractEmailJob::class;
        $this->registry[UnsuccessfulIIngestionEmail::EVENT_SLUG] = AbstractEmailJob::class;

        $this->registry[InviteSummitRegistrationEmail::EVENT_SLUG] = AbstractEmailJob::class;
        $this->registry[ReInviteSummitRegistrationEmail::EVENT_SLUG] = AbstractEmailJob::class;

        $this->registry[SummitOrderRefundRequestAdmin::EVENT_SLUG] = AbstractEmailJob::class;
        $this->registry[SummitOrderRefundRequestOwner::EVENT_SLUG] = AbstractEmailJob::class;
        $this->registry[SummitTicketRefundRequestAdmin::EVENT_SLUG] = AbstractEmailJob::class;
        $this->registry[SummitTicketRefundRequestOwner::EVENT_SLUG] = AbstractEmailJob::class;

        $this->registry[SummitOrderReminderEmail::EVENT_SLUG] = AbstractEmailJob::class;
        $this->registry[SummitTicketReminderEmail::EVENT_SLUG] = AbstractEmailJob::class;

        $this->registry[RegisteredMemberOrderPaidMail::EVENT_SLUG] = AbstractEmailJob::class;
        $this->registry[UnregisteredMemberOrderPaidMail::EVENT_SLUG] = AbstractEmailJob::class;

        //Schedule

        $this->registry[ShareEventEmail::EVENT_SLUG] = AbstractEmailJob::class;
    }

    /**
     * @param string $slug
     * @return mixed
     */
    public function serialize(string $slug)
    {
        if (!isset($this->registry[$slug]))
            throw new \InvalidArgumentException('Emails template schema builder not found for ' . $slug);

        $builder_class = $this->registry[$slug];

        return $builder_class::getEmailTemplateSchema();
    }
}