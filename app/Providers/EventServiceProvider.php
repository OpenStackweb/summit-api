<?php namespace App\Providers;
/**
 * Copyright 2017 OpenStack Foundation
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

use App\Events\MemberUpdated;
use App\Events\NewMember;
use App\Events\OrderDeleted;
use App\Events\PaymentSummitRegistrationOrderConfirmed;
use App\Events\Registration\MemberDataUpdatedExternally;
use App\Events\RSVPCreated;
use App\Events\RSVPUpdated;
use App\Events\SummitOrderCanceled;
use App\Events\TicketUpdated;
use App\Jobs\CompensatePromoCodes;
use App\Jobs\CompensateTickets;
use App\Jobs\Emails\BookableRooms\BookableRoomReservationCanceledEmail;
use App\Jobs\Emails\BookableRooms\BookableRoomReservationCreatedEmail;
use App\Jobs\Emails\BookableRooms\BookableRoomReservationPaymentConfirmedEmail;
use App\Jobs\Emails\BookableRooms\BookableRoomReservationRefundAcceptedEmail;
use App\Jobs\Emails\BookableRooms\BookableRoomReservationRefundRequestedAdminEmail;
use App\Jobs\Emails\BookableRooms\BookableRoomReservationRefundRequestedOwnerEmail;
use App\Jobs\Emails\Schedule\RSVPRegularSeatMail;
use App\Jobs\Emails\Schedule\RSVPWaitListSeatMail;
use App\Jobs\MemberAssocSummitOrders;
use App\Jobs\ProcessScheduleEntityLifeCycleEvent;
use App\Jobs\ProcessSummitOrderPaymentConfirmation;
use App\Jobs\UpdateAttendeeInfo;
use App\Jobs\UpdateIDPMemberInfo;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use LaravelDoctrine\ORM\Facades\EntityManager;
use models\summit\RSVP;
use models\summit\Summit;
use models\summit\SummitOrder;
use models\summit\SummitRoomReservation;
use App\Events\ScheduleEntityLifeCycleEvent;
/**
 * Class EventServiceProvider
 * @package App\Providers
 */
final class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'Illuminate\Database\Events\QueryExecuted' => [
            'App\Listeners\QueryExecutedListener',
        ],
    ];

    /**
     * Register any other events for your application.
     * @return void
     */
    public function boot()
    {
        parent::boot();

        Event::listen(\App\Events\MyScheduleAdd::class, function ($event) {

        });

        Event::listen(\App\Events\MyFavoritesAdd::class, function ($event) {
        });

        Event::listen(\App\Events\MyScheduleRemove::class, function ($event) {
        });

        Event::listen(\App\Events\MyFavoritesRemove::class, function ($event) {
        });


        // bookable rooms events

        Event::listen(\Illuminate\Mail\Events\MessageSending::class, function ($event) {
            $devEmail = env('DEV_EMAIL_TO');
            if (in_array(App::environment(), ['local', 'dev', 'testing']) && !empty($devEmail)) {
                $event->message->setTo(explode(",", $devEmail));
            }
            return true;
        });

        Event::listen(\App\Events\BookableRoomReservationRefundAccepted::class, function ($event) {
            $repository = EntityManager::getRepository(SummitRoomReservation::class);
            $reservation = $repository->find($event->getReservationId());
            if (is_null($reservation) || !$reservation instanceof SummitRoomReservation) return;

            BookableRoomReservationRefundAcceptedEmail::dispatch($reservation);

        });

        Event::listen(\App\Events\CreatedBookableRoomReservation::class, function ($event) {
            $repository = EntityManager::getRepository(SummitRoomReservation::class);
            $reservation = $repository->find($event->getReservationId());
            if (is_null($reservation) || !$reservation instanceof SummitRoomReservation) return;

            BookableRoomReservationCreatedEmail::dispatch($reservation);
        });

        Event::listen(\App\Events\PaymentBookableRoomReservationConfirmed::class, function ($event) {
            $repository = EntityManager::getRepository(SummitRoomReservation::class);
            $reservation = $repository->find($event->getReservationId());
            if (is_null($reservation) || !$reservation instanceof SummitRoomReservation) return;

            BookableRoomReservationPaymentConfirmedEmail::dispatch($reservation);
        });

        Event::listen(\App\Events\RequestedBookableRoomReservationRefund::class, function ($event) {
            $repository = EntityManager::getRepository(SummitRoomReservation::class);
            $reservation = $repository->find($event->getReservationId());
            if (is_null($reservation) || !$reservation instanceof SummitRoomReservation) return;

            BookableRoomReservationRefundRequestedAdminEmail::dispatch($reservation);
            BookableRoomReservationRefundRequestedOwnerEmail::dispatch($reservation);
        });

        Event::listen(\App\Events\BookableRoomReservationCanceled::class, function ($event) {
            $repository = EntityManager::getRepository(SummitRoomReservation::class);
            $reservation = $repository->find($event->getReservationId());
            if (is_null($reservation) || !$reservation instanceof SummitRoomReservation) return;
            BookableRoomReservationCanceledEmail::dispatch($reservation);
        });

        // registration

        Event::listen(SummitOrderCanceled::class, function ($event) {
            if (!$event instanceof SummitOrderCanceled) return;

            $repository = EntityManager::getRepository(SummitOrder::class);
            $order = $repository->find($event->getOrderId());
            if (is_null($order) || !$order instanceof SummitOrder) return;

            Log::debug(sprintf("EventServiceProvider::SummitOrderCanceled order id %s", $order->getId()));
            /*
             * removed for now
             * if($event->shouldSendEmail())
                Mail::queue(new SummitOrderCanceledEmail($order));
            */
            // compensate tickets types qty

            foreach ($event->getTicketsToReturn() as $ticket_type_id => $qty) {
                Log::debug(sprintf("EventServiceProvider::SummitOrderCanceled: firing CompensateTickets ticket_type_id %s qty %s", $ticket_type_id, $qty));
                CompensateTickets::dispatch($ticket_type_id, $qty);
            }
            // compensate promo codes usages

            foreach ($event->getPromoCodesToReturn() as $code => $qty) {
                Log::debug(sprintf("EventServiceProvider::SummitOrderCanceled: firing CompensatePromoCodes code %s qty %s", $code, $qty));
                CompensatePromoCodes::dispatch($order->getSummit(), $code, $qty);
            }
        });

        Event::listen(OrderDeleted::class, function ($event) {
            if (!$event instanceof OrderDeleted) return;

            // compensate tickets types qty

            Log::debug(sprintf("EventServiceProvider::OrderDeleted id %s", $event->getOrderId()));

            $repository = EntityManager::getRepository(Summit::class);
            $summit = $repository->find($event->getSummitId());
            if (is_null($summit) || !$summit instanceof Summit) return;

            foreach ($event->getTicketsToReturn() as $ticket_type_id => $qty) {
                Log::debug(sprintf("EventServiceProvider::OrderDeleted: firing CompensateTickets ticket_type_id %s qty %s", $ticket_type_id, $qty));
                CompensateTickets::dispatch($ticket_type_id, $qty);
            }
            // compensate promo codes usages

            foreach ($event->getPromoCodesToReturn() as $code => $qty) {
                Log::debug(sprintf("EventServiceProvider::OrderDeleted: firing CompensatePromoCodes code %s qty %s", $code, $qty));
                CompensatePromoCodes::dispatch($summit, $code, $qty);
            }
        });

        Event::listen(PaymentSummitRegistrationOrderConfirmed::class, function ($event) {
            if (!$event instanceof PaymentSummitRegistrationOrderConfirmed) return;
            $order_id = $event->getOrderId();
            Log::debug(sprintf("EventServiceProvider::PaymentSummitRegistrationOrderConfirmed: firing ProcessSummitOrderPaymentConfirmation for order id %s", $order_id));
            ProcessSummitOrderPaymentConfirmation::dispatch($order_id);
        });

        Event::listen(NewMember::class, function ($event) {
            if (!$event instanceof NewMember) return;
            Log::debug(sprintf("EventServiceProvider::NewMember - firing NewMemberAssocSummitOrders member id %s", $event->getMemberId()));
            MemberAssocSummitOrders::dispatch($event->getMemberId());
        });

        Event::listen(MemberDataUpdatedExternally::class, function($event){
            if (!$event instanceof MemberDataUpdatedExternally) return;
            Log::debug(sprintf("EventServiceProvider::MemberDataUpdatedExternally - firing UpdateAttendeeInfo member id %s", $event->getMemberId()));
            UpdateAttendeeInfo::dispatch($event->getMemberId());
        });

        Event::listen(MemberUpdated::class, function ($event) {
            if (!$event instanceof MemberUpdated) return;
            Log::debug(sprintf("EventServiceProvider::MemberUpdated - firing NewMemberAssocSummitOrders member id %s", $event->getMemberId()));

            UpdateIDPMemberInfo::dispatch(
                $event->getEmail(),
                $event->getFirstName(),
                $event->getLastName(),
                $event->getCompany()
            );
        });

        Event::listen(RSVPCreated::class, function ($event) {
            if (!$event instanceof RSVPCreated) return;

            $rsvp_id = $event->getRsvpId();

            $rsvp_repository = EntityManager::getRepository(RSVP::class);

            $rsvp = $rsvp_repository->find($rsvp_id);
            if (is_null($rsvp) || !$rsvp instanceof RSVP) return;

            if ($rsvp->getSeatType() == RSVP::SeatTypeRegular)
                RSVPRegularSeatMail::dispatch($rsvp);

            if ($rsvp->getSeatType() == RSVP::SeatTypeWaitList)
                RSVPWaitListSeatMail::dispatch($rsvp);
        });

        Event::listen(RSVPUpdated::class, function ($event) {
            if (!$event instanceof RSVPUpdated) return;

            $rsvp_id = $event->getRsvpId();

            $rsvp_repository = EntityManager::getRepository(RSVP::class);

            $rsvp = $rsvp_repository->find($rsvp_id);
            if (is_null($rsvp) || !$rsvp instanceof RSVP) return;

            if ($rsvp->getSeatType() == RSVP::SeatTypeRegular)
                RSVPRegularSeatMail::dispatch($rsvp);

            if ($rsvp->getSeatType() == RSVP::SeatTypeWaitList)
                RSVPWaitListSeatMail::dispatch($rsvp);
        });

        Event::listen(TicketUpdated::class, function ($event) {

            if (!$event instanceof TicketUpdated) return;
            // publish profile changes to the IDP
            $attendee = $event->getAttendee();
            UpdateIDPMemberInfo::dispatch($attendee->getEmail(),
                $attendee->getFirstName(),
                $attendee->getSurname(),
                $attendee->getCompanyName());
        });

        Event::listen(ScheduleEntityLifeCycleEvent::class, function($event){
            if(!$event instanceof ScheduleEntityLifeCycleEvent) return;

            Log::debug(sprintf("ScheduleEntityLifeCycleEvent event %s", $event));

            ProcessScheduleEntityLifeCycleEvent::dispatch
            (
                $event->entity_operator,
                $event->summit_id,
                $event->entity_id,
                $event->entity_type
            );
        });
    }
}
