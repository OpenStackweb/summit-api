<?php
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
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Foundation\Summit\EmailFlows\SummitEmailFlowType;
use App\Jobs\Emails\InviteAttendeeTicketEditionMail;
use App\Models\Foundation\Summit\EmailFlows\SummitEmailEventFlowType;
use LaravelDoctrine\ORM\Facades\Registry;
use models\utils\SilverstripeBaseModel;
// registration
use App\Jobs\Emails\SummitAttendeeTicketRegenerateHashEmail;
use App\Jobs\Emails\RegisteredMemberOrderPaidMail;
use App\Jobs\Emails\RevocationTicketEmail;
use App\Jobs\Emails\SummitAttendeeTicketEmail;
use App\Jobs\Emails\UnregisteredMemberOrderPaidMail;
use App\Jobs\Emails\Registration\Reminders\SummitOrderReminderEmail;
use App\Jobs\Emails\Registration\Reminders\SummitTicketReminderEmail;
use App\Jobs\Emails\Registration\Refunds\SummitOrderRefundAccepted;
use App\Jobs\Emails\Registration\Refunds\SummitOrderRefundRequestAdmin;
use App\Jobs\Emails\Registration\Refunds\SummitOrderRefundRequestOwner;
use App\Jobs\Emails\Registration\Refunds\SummitTicketRefundAccepted;
use App\Jobs\Emails\Registration\Refunds\SummitTicketRefundRequestAdmin;
use App\Jobs\Emails\Registration\Refunds\SummitTicketRefundRequestOwner;
use App\Jobs\Emails\Registration\MemberPromoCodeEmail;
use App\Jobs\Emails\Registration\SpeakerPromoCodeEMail;
use App\Jobs\Emails\Registration\Invitations\InviteSummitRegistrationEmail;
use App\Jobs\Emails\Registration\Invitations\ReInviteSummitRegistrationEmail;
// bookable rooms
use App\Jobs\Emails\BookableRooms\BookableRoomReservationCanceledEmail;
use App\Jobs\Emails\BookableRooms\BookableRoomReservationCreatedEmail;
use App\Jobs\Emails\BookableRooms\BookableRoomReservationPaymentConfirmedEmail;
use App\Jobs\Emails\BookableRooms\BookableRoomReservationRefundAcceptedEmail;
use App\Jobs\Emails\BookableRooms\BookableRoomReservationRefundRequestedAdminEmail;
use App\Jobs\Emails\BookableRooms\BookableRoomReservationRefundRequestedOwnerEmail;
// schedule
use App\Jobs\Emails\Schedule\RSVPRegularSeatMail;
use App\Jobs\Emails\Schedule\RSVPWaitListSeatMail;
use App\Jobs\Emails\Schedule\ShareEventEmail;
// Presentation Submission
use App\Jobs\Emails\PresentationSubmissions\PresentationCreatorNotificationEmail;
use App\Jobs\Emails\PresentationSubmissions\PresentationSpeakerNotificationEmail;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSpeakerSelectionProcessAcceptedAlternateEmail;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSpeakerSelectionProcessAcceptedOnlyEmail;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSpeakerSelectionProcessAcceptedRejectedEmail;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSpeakerSelectionProcessAlternateOnlyEmail;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSpeakerSelectionProcessAlternateRejectedEmail;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSpeakerSelectionProcessRejectedEmail;
/**
 * Class SummitEmailFlowTypeSeeder
 */
final class SummitEmailFlowTypeSeeder extends Seeder
{
    public function run()
    {
        $em = Registry::getManager(SilverstripeBaseModel::EntityManager);

        DB::setDefaultConnection("model");
        DB::table("SummitEmailFlowType")->delete();
        DB::table("SummitEmailEventFlowType")->delete();
        DB::table("SummitEmailEventFlow")->delete();

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
        ], $flow);

        $em->persist($flow);

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
                'name' => PresentationSpeakerSelectionProcessRejectedEmail::EVENT_NAME,
                'slug' => PresentationSpeakerSelectionProcessRejectedEmail::EVENT_SLUG,
                'default_email_template' => PresentationSpeakerSelectionProcessRejectedEmail::DEFAULT_TEMPLATE
            ],

        ], $flow);

        $em->persist($flow);
        $em->flush();
    }

    /**
     * @param array $payload
     * @param SummitEmailFlowType $flow
     */
    static private function createEventsTypes(array $payload , SummitEmailFlowType $flow){
        foreach($payload as $definition){
            $event_type = new SummitEmailEventFlowType();
            $event_type->setName($definition['name']);
            $event_type->setSlug($definition['slug']);
            $event_type->setDefaultEmailTemplate($definition['default_email_template']);
            $flow->addFlowEventType($event_type);
        }
    }
}