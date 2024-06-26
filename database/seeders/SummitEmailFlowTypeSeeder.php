<?php namespace Database\Seeders;
/**
 * Copyright 2020 OpenStack Foundation
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
use App\Jobs\Emails\InviteAttendeeTicketEditionMail;
use App\Jobs\Emails\PresentationSelections\PresentationCategoryChangeRequestCreatedEmail;
use App\Jobs\Emails\PresentationSelections\PresentationCategoryChangeRequestResolvedEmail;
use App\Jobs\Emails\PresentationSelections\SpeakerEmail;
use App\Jobs\Emails\PresentationSubmissions\ImportEventSpeakerEmail;
use App\Jobs\Emails\PresentationSubmissions\Invitations\InviteSubmissionEmail;
use App\Jobs\Emails\PresentationSubmissions\PresentationCreatorNotificationEmail;
use App\Jobs\Emails\PresentationSubmissions\PresentationModeratorNotificationEmail;
use App\Jobs\Emails\PresentationSubmissions\PresentationSpeakerNotificationEmail;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSpeakerSelectionProcessAcceptedAlternateEmail;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSpeakerSelectionProcessAcceptedOnlyEmail;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSpeakerSelectionProcessAcceptedRejectedEmail;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSpeakerSelectionProcessAlternateOnlyEmail;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSpeakerSelectionProcessAlternateRejectedEmail;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSpeakerSelectionProcessExcerptEmail;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSpeakerSelectionProcessRejectedOnlyEmail;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSubmitterSelectionProcessAcceptedAlternateEmail;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSubmitterSelectionProcessAcceptedOnlyEmail;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSubmitterSelectionProcessAcceptedRejectedEmail;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSubmitterSelectionProcessAlternateOnlyEmail;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSubmitterSelectionProcessAlternateRejectedEmail;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSubmitterSelectionProcessExcerptEmail;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSubmitterSelectionProcessRejectedOnlyEmail;
use App\Jobs\Emails\ProposedSchedule\SubmitForReviewEmail;
use App\Jobs\Emails\ProposedSchedule\UnsubmitForReviewEmail;
use App\Jobs\Emails\RegisteredMemberOrderPaidMail;
use App\Jobs\Emails\Registration\Attendees\GenericSummitAttendeeEmail;
use App\Jobs\Emails\Registration\Attendees\SummitAttendeeExcerptEmail;
use App\Jobs\Emails\Registration\Invitations\InvitationExcerptEmail;
use App\Jobs\Emails\Registration\Invitations\InviteSummitRegistrationEmail;
use App\Jobs\Emails\Registration\Invitations\ReInviteSummitRegistrationEmail;
use App\Jobs\Emails\Registration\PromoCodes\MemberPromoCodeEmail;
use App\Jobs\Emails\Registration\PromoCodes\SpeakerPromoCodeEMail;
use App\Jobs\Emails\Registration\PromoCodes\SponsorPromoCodeEmail;
use App\Jobs\Emails\Registration\Refunds\SummitOrderRefundAccepted;
use App\Jobs\Emails\Registration\Refunds\SummitOrderRefundRequestAdmin;
use App\Jobs\Emails\Registration\Refunds\SummitOrderRefundRequestOwner;
use App\Jobs\Emails\Registration\Refunds\SummitTicketRefundAccepted;
use App\Jobs\Emails\Registration\Refunds\SummitTicketRefundRejected;
use App\Jobs\Emails\Registration\Refunds\SummitTicketRefundRequestAdmin;
use App\Jobs\Emails\Registration\Refunds\SummitTicketRefundRequestOwner;
use App\Jobs\Emails\Registration\Reminders\SummitOrderReminderEmail;
use App\Jobs\Emails\Registration\Reminders\SummitTicketReminderEmail;
use App\Jobs\Emails\RevocationTicketEmail;
use App\Jobs\Emails\Schedule\RSVPRegularSeatMail;
use App\Jobs\Emails\Schedule\RSVPWaitListSeatMail;
use App\Jobs\Emails\Schedule\ShareEventEmail;
use App\Jobs\Emails\SummitAttendeeAllTicketsEditionEmail;
use App\Jobs\Emails\SummitAttendeeRegistrationIncompleteReminderEmail;
use App\Jobs\Emails\SummitAttendeeTicketEmail;
use App\Jobs\Emails\SummitAttendeeTicketRegenerateHashEmail;
use App\Jobs\Emails\UnregisteredMemberOrderPaidMail;
use App\Models\Foundation\Summit\EmailFlows\SummitEmailEventFlowType;
use App\Models\Foundation\Summit\EmailFlows\SummitEmailFlowType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use LaravelDoctrine\ORM\Facades\Registry;
use models\utils\SilverstripeBaseModel;

// registration
// bookable rooms
// schedule
// Presentation Submission
// Presentation Selections

/**
 * Class SummitEmailFlowTypeSeeder
 */
final class SummitEmailFlowTypeSeeder extends Seeder
{
    public function run()
    {
        DB::setDefaultConnection("model");
        DB::table("SummitEmailFlowType")->delete();
        DB::table("SummitEmailEventFlowType")->delete();
        DB::table("SummitEmailEventFlow")->delete();

        self::seed();
    }

    public static function seed(){
        $em = Registry::getManager(SilverstripeBaseModel::EntityManager);
        // Registration Flow
        $flow = new SummitEmailFlowType();
        $flow->setName("Registration");

        self::createEventsTypes([
            [
                'name' => InviteAttendeeTicketEditionMail::EVENT_NAME,
                'slug' => InviteAttendeeTicketEditionMail::EVENT_SLUG,
                'default_email_template' => InviteAttendeeTicketEditionMail::DEFAULT_TEMPLATE
            ],
            [
                'name' => SummitAttendeeTicketRegenerateHashEmail::EVENT_NAME,
                'slug' => SummitAttendeeTicketRegenerateHashEmail::EVENT_SLUG,
                'default_email_template' => SummitAttendeeTicketRegenerateHashEmail::DEFAULT_TEMPLATE
            ],
            [
                'name' => RegisteredMemberOrderPaidMail::EVENT_NAME,
                'slug' => RegisteredMemberOrderPaidMail::EVENT_SLUG,
                'default_email_template' => RegisteredMemberOrderPaidMail::DEFAULT_TEMPLATE
            ],
            [
                'name' => RevocationTicketEmail::EVENT_NAME,
                'slug' => RevocationTicketEmail::EVENT_SLUG,
                'default_email_template' => RevocationTicketEmail::DEFAULT_TEMPLATE
            ],
            [
                'name' => SummitAttendeeTicketEmail::EVENT_NAME,
                'slug' => SummitAttendeeTicketEmail::EVENT_SLUG,
                'default_email_template' => SummitAttendeeTicketEmail::DEFAULT_TEMPLATE
            ],
            [
                'name' => UnregisteredMemberOrderPaidMail::EVENT_NAME,
                'slug' => UnregisteredMemberOrderPaidMail::EVENT_SLUG,
                'default_email_template' => UnregisteredMemberOrderPaidMail::DEFAULT_TEMPLATE
            ],
            // reminders
            [
                'name' => SummitOrderReminderEmail::EVENT_NAME,
                'slug' => SummitOrderReminderEmail::EVENT_SLUG,
                'default_email_template' => SummitOrderReminderEmail::DEFAULT_TEMPLATE
            ],
            [
                'name' => SummitTicketReminderEmail::EVENT_NAME,
                'slug' => SummitTicketReminderEmail::EVENT_SLUG,
                'default_email_template' => SummitTicketReminderEmail::DEFAULT_TEMPLATE
            ],
            [
                'name' => SummitAttendeeRegistrationIncompleteReminderEmail::EVENT_NAME,
                'slug' => SummitAttendeeRegistrationIncompleteReminderEmail::EVENT_SLUG,
                'default_email_template' => SummitAttendeeRegistrationIncompleteReminderEmail::DEFAULT_TEMPLATE
            ],
            // refunds
            [
                'name' => SummitOrderRefundAccepted::EVENT_NAME,
                'slug' => SummitOrderRefundAccepted::EVENT_SLUG,
                'default_email_template' => SummitOrderRefundAccepted::DEFAULT_TEMPLATE
            ],
            [
                'name' => SummitOrderRefundRequestAdmin::EVENT_NAME,
                'slug' => SummitOrderRefundRequestAdmin::EVENT_SLUG,
                'default_email_template' => SummitOrderRefundRequestAdmin::DEFAULT_TEMPLATE
            ],
            [
                'name' => SummitOrderRefundRequestOwner::EVENT_NAME,
                'slug' => SummitOrderRefundRequestOwner::EVENT_SLUG,
                'default_email_template' => SummitOrderRefundRequestOwner::DEFAULT_TEMPLATE
            ],
            [
                'name' => SummitTicketRefundAccepted::EVENT_NAME,
                'slug' => SummitTicketRefundAccepted::EVENT_SLUG,
                'default_email_template' => SummitTicketRefundAccepted::DEFAULT_TEMPLATE
            ],
            [
                'name' => SummitTicketRefundRejected::EVENT_NAME,
                'slug' => SummitTicketRefundRejected::EVENT_SLUG,
                'default_email_template' => SummitTicketRefundRejected::DEFAULT_TEMPLATE
            ],
            [
                'name' => SummitTicketRefundRequestAdmin::EVENT_NAME,
                'slug' => SummitTicketRefundRequestAdmin::EVENT_SLUG,
                'default_email_template' => SummitTicketRefundRequestAdmin::DEFAULT_TEMPLATE
            ],
            [
                'name' => SummitTicketRefundRequestOwner::EVENT_NAME,
                'slug' => SummitTicketRefundRequestOwner::EVENT_SLUG,
                'default_email_template' => SummitTicketRefundRequestOwner::DEFAULT_TEMPLATE
            ],
            [
                'name' => MemberPromoCodeEmail::EVENT_NAME,
                'slug' => MemberPromoCodeEmail::EVENT_SLUG,
                'default_email_template' => MemberPromoCodeEmail::DEFAULT_TEMPLATE
            ],
            [
                'name' => SpeakerPromoCodeEMail::EVENT_NAME,
                'slug' => SpeakerPromoCodeEMail::EVENT_SLUG,
                'default_email_template' => SpeakerPromoCodeEMail::DEFAULT_TEMPLATE
            ],
            [
                'name' => InviteSummitRegistrationEmail::EVENT_NAME,
                'slug' => InviteSummitRegistrationEmail::EVENT_SLUG,
                'default_email_template' => InviteSummitRegistrationEmail::DEFAULT_TEMPLATE
            ],
            [
                'name' => ReInviteSummitRegistrationEmail::EVENT_NAME,
                'slug' => ReInviteSummitRegistrationEmail::EVENT_SLUG,
                'default_email_template' => ReInviteSummitRegistrationEmail::DEFAULT_TEMPLATE
            ],
            [
                'name' => SummitAttendeeAllTicketsEditionEmail::EVENT_NAME,
                'slug' => SummitAttendeeAllTicketsEditionEmail::EVENT_SLUG,
                'default_email_template' => SummitAttendeeAllTicketsEditionEmail::DEFAULT_TEMPLATE
            ],
            [
                'name' => GenericSummitAttendeeEmail::EVENT_NAME,
                'slug' => GenericSummitAttendeeEmail::EVENT_SLUG,
                'default_email_template' => GenericSummitAttendeeEmail::DEFAULT_TEMPLATE
            ],
            [
                'name' => SponsorPromoCodeEmail::EVENT_NAME,
                'slug' => SponsorPromoCodeEmail::EVENT_SLUG,
                'default_email_template' => SponsorPromoCodeEmail::DEFAULT_TEMPLATE
            ],
        ], $flow);

        $em->persist($flow);

        // Bookable Rooms Flow
        $flow = new SummitEmailFlowType();
        $flow->setName("Bookable Rooms");

        self::createEventsTypes([
            [
                'name' => BookableRoomReservationCanceledEmail::EVENT_NAME,
                'slug' => BookableRoomReservationCanceledEmail::EVENT_SLUG,
                'default_email_template' => BookableRoomReservationCanceledEmail::DEFAULT_TEMPLATE
            ],
            [
                'name' => BookableRoomReservationCreatedEmail::EVENT_NAME,
                'slug' => BookableRoomReservationCreatedEmail::EVENT_SLUG,
                'default_email_template' => BookableRoomReservationCreatedEmail::DEFAULT_TEMPLATE
            ],
            [
                'name' => BookableRoomReservationPaymentConfirmedEmail::EVENT_NAME,
                'slug' => BookableRoomReservationPaymentConfirmedEmail::EVENT_SLUG,
                'default_email_template' => BookableRoomReservationPaymentConfirmedEmail::DEFAULT_TEMPLATE
            ],
            [
                'name' => BookableRoomReservationRefundAcceptedEmail::EVENT_NAME,
                'slug' => BookableRoomReservationRefundAcceptedEmail::EVENT_SLUG,
                'default_email_template' => BookableRoomReservationRefundAcceptedEmail::DEFAULT_TEMPLATE
            ],
            [
                'name' => BookableRoomReservationRefundRequestedAdminEmail::EVENT_NAME,
                'slug' => BookableRoomReservationRefundRequestedAdminEmail::EVENT_SLUG,
                'default_email_template' => BookableRoomReservationRefundRequestedAdminEmail::DEFAULT_TEMPLATE
            ],
            [
                'name' => BookableRoomReservationRefundRequestedOwnerEmail::EVENT_NAME,
                'slug' => BookableRoomReservationRefundRequestedOwnerEmail::EVENT_SLUG,
                'default_email_template' => BookableRoomReservationRefundRequestedOwnerEmail::DEFAULT_TEMPLATE
            ],
        ], $flow);


        $em->persist($flow);

        // Schedule Flow
        $flow = new SummitEmailFlowType();
        $flow->setName("Schedule");

        self::createEventsTypes([
            [
                'name' => RSVPRegularSeatMail::EVENT_NAME,
                'slug' => RSVPRegularSeatMail::EVENT_SLUG,
                'default_email_template' => RSVPRegularSeatMail::DEFAULT_TEMPLATE
            ],
            [
                'name' => RSVPWaitListSeatMail::EVENT_NAME,
                'slug' => RSVPWaitListSeatMail::EVENT_SLUG,
                'default_email_template' => RSVPWaitListSeatMail::DEFAULT_TEMPLATE
            ],
            [
                'name' => ShareEventEmail::EVENT_NAME,
                'slug' => ShareEventEmail::EVENT_SLUG,
                'default_email_template' => ShareEventEmail::DEFAULT_TEMPLATE
            ],
        ], $flow);

        $em->persist($flow);

        // Proposed Schedule Flow
        $flow = new SummitEmailFlowType();
        $flow->setName("Proposed Schedule");

        self::createEventsTypes([
            [
                'name' => SubmitForReviewEmail::EVENT_NAME,
                'slug' => SubmitForReviewEmail::EVENT_SLUG,
                'default_email_template' => SubmitForReviewEmail::DEFAULT_TEMPLATE
            ],
            [
                'name' => UnsubmitForReviewEmail::EVENT_NAME,
                'slug' => UnsubmitForReviewEmail::EVENT_SLUG,
                'default_email_template' => UnsubmitForReviewEmail::DEFAULT_TEMPLATE
            ]
        ], $flow);

        $em->persist($flow);

        // Presentation Submissions Flow
        $flow = new SummitEmailFlowType();
        $flow->setName("Presentation Submissions");

        self::createEventsTypes([
            [
                'name' => PresentationCreatorNotificationEmail::EVENT_NAME,
                'slug' => PresentationCreatorNotificationEmail::EVENT_SLUG,
                'default_email_template' => PresentationCreatorNotificationEmail::DEFAULT_TEMPLATE
            ],
            [
                'name' => PresentationSpeakerNotificationEmail::EVENT_NAME,
                'slug' => PresentationSpeakerNotificationEmail::EVENT_SLUG,
                'default_email_template' => PresentationSpeakerNotificationEmail::DEFAULT_TEMPLATE
            ],
            [
                'name' => PresentationModeratorNotificationEmail::EVENT_NAME,
                'slug' => PresentationModeratorNotificationEmail::EVENT_SLUG,
                'default_email_template' => PresentationModeratorNotificationEmail::DEFAULT_TEMPLATE
            ],
            [
                'name' => PresentationSpeakerSelectionProcessAcceptedAlternateEmail::EVENT_NAME,
                'slug' => PresentationSpeakerSelectionProcessAcceptedAlternateEmail::EVENT_SLUG,
                'default_email_template' => PresentationSpeakerSelectionProcessAcceptedAlternateEmail::DEFAULT_TEMPLATE
            ],
            [
                'name' => PresentationSpeakerSelectionProcessAcceptedOnlyEmail::EVENT_NAME,
                'slug' => PresentationSpeakerSelectionProcessAcceptedOnlyEmail::EVENT_SLUG,
                'default_email_template' => PresentationSpeakerSelectionProcessAcceptedOnlyEmail::DEFAULT_TEMPLATE
            ],
            [
                'name' => PresentationSpeakerSelectionProcessAcceptedRejectedEmail::EVENT_NAME,
                'slug' => PresentationSpeakerSelectionProcessAcceptedRejectedEmail::EVENT_SLUG,
                'default_email_template' => PresentationSpeakerSelectionProcessAcceptedRejectedEmail::DEFAULT_TEMPLATE
            ],
            [
                'name' => PresentationSpeakerSelectionProcessAlternateOnlyEmail::EVENT_NAME,
                'slug' => PresentationSpeakerSelectionProcessAlternateOnlyEmail::EVENT_SLUG,
                'default_email_template' => PresentationSpeakerSelectionProcessAlternateOnlyEmail::DEFAULT_TEMPLATE
            ],
            [
                'name' => PresentationSpeakerSelectionProcessAlternateRejectedEmail::EVENT_NAME,
                'slug' => PresentationSpeakerSelectionProcessAlternateRejectedEmail::EVENT_SLUG,
                'default_email_template' => PresentationSpeakerSelectionProcessAlternateRejectedEmail::DEFAULT_TEMPLATE
            ],
            [
                'name' => PresentationSpeakerSelectionProcessRejectedOnlyEmail::EVENT_NAME,
                'slug' => PresentationSpeakerSelectionProcessRejectedOnlyEmail::EVENT_SLUG,
                'default_email_template' => PresentationSpeakerSelectionProcessRejectedOnlyEmail::DEFAULT_TEMPLATE
            ],
            [
                'name' => ImportEventSpeakerEmail::EVENT_NAME,
                'slug' => ImportEventSpeakerEmail::EVENT_SLUG,
                'default_email_template' => ImportEventSpeakerEmail::DEFAULT_TEMPLATE
            ],
            [
                'name' => PresentationSpeakerSelectionProcessExcerptEmail::EVENT_NAME,
                'slug' => PresentationSpeakerSelectionProcessExcerptEmail::EVENT_SLUG,
                'default_email_template' => PresentationSpeakerSelectionProcessExcerptEmail::DEFAULT_TEMPLATE
            ],
            [
                'name' => InviteSubmissionEmail::EVENT_NAME,
                'slug' => InviteSubmissionEmail::EVENT_SLUG,
                'default_email_template' => InviteSubmissionEmail::DEFAULT_TEMPLATE
            ],
            [
                'name' => PresentationSubmitterSelectionProcessAcceptedAlternateEmail::EVENT_NAME,
                'slug' => PresentationSubmitterSelectionProcessAcceptedAlternateEmail::EVENT_SLUG,
                'default_email_template' => PresentationSubmitterSelectionProcessAcceptedAlternateEmail::DEFAULT_TEMPLATE
            ],
            [
                'name' => PresentationSubmitterSelectionProcessAcceptedOnlyEmail::EVENT_NAME,
                'slug' => PresentationSubmitterSelectionProcessAcceptedOnlyEmail::EVENT_SLUG,
                'default_email_template' => PresentationSubmitterSelectionProcessAcceptedOnlyEmail::DEFAULT_TEMPLATE
            ],
            [
                'name' => PresentationSubmitterSelectionProcessAcceptedRejectedEmail::EVENT_NAME,
                'slug' => PresentationSubmitterSelectionProcessAcceptedRejectedEmail::EVENT_SLUG,
                'default_email_template' => PresentationSubmitterSelectionProcessAcceptedRejectedEmail::DEFAULT_TEMPLATE
            ],
            [
                'name' => PresentationSubmitterSelectionProcessAlternateOnlyEmail::EVENT_NAME,
                'slug' => PresentationSubmitterSelectionProcessAlternateOnlyEmail::EVENT_SLUG,
                'default_email_template' => PresentationSubmitterSelectionProcessAlternateOnlyEmail::DEFAULT_TEMPLATE
            ],
            [
                'name' => PresentationSubmitterSelectionProcessAlternateRejectedEmail::EVENT_NAME,
                'slug' => PresentationSubmitterSelectionProcessAlternateRejectedEmail::EVENT_SLUG,
                'default_email_template' => PresentationSubmitterSelectionProcessAlternateRejectedEmail::DEFAULT_TEMPLATE
            ],
            [
                'name' => PresentationSubmitterSelectionProcessRejectedOnlyEmail::EVENT_NAME,
                'slug' => PresentationSubmitterSelectionProcessRejectedOnlyEmail::EVENT_SLUG,
                'default_email_template' => PresentationSubmitterSelectionProcessRejectedOnlyEmail::DEFAULT_TEMPLATE
            ],
            [
                'name' => PresentationSubmitterSelectionProcessExcerptEmail::EVENT_NAME,
                'slug' => PresentationSubmitterSelectionProcessExcerptEmail::EVENT_SLUG,
                'default_email_template' => PresentationSubmitterSelectionProcessExcerptEmail::DEFAULT_TEMPLATE
            ],
            [
                'name' => SummitAttendeeExcerptEmail::EVENT_NAME,
                'slug' => SummitAttendeeExcerptEmail::EVENT_SLUG,
                'default_email_template' => SummitAttendeeExcerptEmail::DEFAULT_TEMPLATE
            ],
            [
                'name' => InvitationExcerptEmail::EVENT_NAME,
                'slug' => InvitationExcerptEmail::EVENT_SLUG,
                'default_email_template' => InvitationExcerptEmail::DEFAULT_TEMPLATE
            ],

        ], $flow);

        $em->persist($flow);
        $em->flush();

        // Presentation Selection Flow

        $flow = new SummitEmailFlowType();
        $flow->setName("Presentation Selections");

        self::createEventsTypes([
            [
                'name' => PresentationCategoryChangeRequestCreatedEmail::EVENT_NAME,
                'slug' => PresentationCategoryChangeRequestCreatedEmail::EVENT_SLUG,
                'default_email_template' => PresentationCategoryChangeRequestCreatedEmail::DEFAULT_TEMPLATE
            ],
            [
                'name' => PresentationCategoryChangeRequestResolvedEmail::EVENT_NAME,
                'slug' => PresentationCategoryChangeRequestResolvedEmail::EVENT_SLUG,
                'default_email_template' => PresentationCategoryChangeRequestResolvedEmail::DEFAULT_TEMPLATE
            ],
            [
                'name' => SpeakerEmail::EVENT_NAME,
                'slug' => SpeakerEmail::EVENT_SLUG,
                'default_email_template' => SpeakerEmail::DEFAULT_TEMPLATE
            ],
        ], $flow);

        $em->persist($flow);
        $em->flush();
    }
    /**
     * @param array $payload
     * @param SummitEmailFlowType $flow
     */
    static public function createEventsTypes(array $payload , SummitEmailFlowType $flow){
        foreach($payload as $definition){
            $event_type = new SummitEmailEventFlowType();
            $event_type->setName($definition['name']);
            $event_type->setSlug($definition['slug']);
            $event_type->setDefaultEmailTemplate($definition['default_email_template']);
            $flow->addFlowEventType($event_type);
        }
    }
}