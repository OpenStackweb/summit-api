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

use App\EntityPersisters\EntityEventPersister;
use App\Events\NewMember;
use App\Events\OrderDeleted;
use App\Events\PaymentSummitRegistrationOrderConfirmed;
use App\Events\PresentationActionTypeCreated;
use App\Events\RSVPCreated;
use App\Events\RSVPUpdated;
use App\Events\SummitOrderCanceled;
use App\Events\TicketUpdated;
use App\Factories\EntityEvents\FloorActionEntityEventFactory;
use App\Factories\EntityEvents\LocationActionEntityEventFactory;
use App\Factories\EntityEvents\LocationImageActionEntityEventFactory;
use App\Factories\EntityEvents\MyFavoritesAddEntityEventFactory;
use App\Factories\EntityEvents\MyFavoritesRemoveEntityEventFactory;
use App\Factories\EntityEvents\MyScheduleAddEntityEventFactory;
use App\Factories\EntityEvents\MyScheduleRemoveEntityEventFactory;
use App\Factories\EntityEvents\PresentationMaterialCreatedEntityEventFactory;
use App\Factories\EntityEvents\PresentationMaterialDeletedEntityEventFactory;
use App\Factories\EntityEvents\PresentationMaterialUpdatedEntityEventFactory;
use App\Factories\EntityEvents\PresentationSpeakerCreatedEntityEventFactory;
use App\Factories\EntityEvents\PresentationSpeakerDeletedEntityEventFactory;
use App\Factories\EntityEvents\PresentationSpeakerUpdatedEntityEventFactory;
use App\Factories\EntityEvents\SummitActionEntityEventFactory;
use App\Factories\EntityEvents\SummitEventCreatedEntityEventFactory;
use App\Factories\EntityEvents\SummitEventDeletedEntityEventFactory;
use App\Factories\EntityEvents\SummitEventTypeActionEntityEventFactory;
use App\Factories\EntityEvents\SummitEventUpdatedEntityEventFactory;
use App\Factories\EntityEvents\SummitTicketTypeActionEntityEventFactory;
use App\Factories\EntityEvents\TrackActionEntityEventFactory;
use App\Factories\EntityEvents\TrackGroupActionActionEntityEventFactory;
use App\Jobs\CompensatePromoCodes;
use App\Jobs\CompensateTickets;
use App\Jobs\MemberAssocSummitOrders;
use App\Jobs\ProcessSummitOrderPaymentConfirmation;
use App\Jobs\Emails\BookableRooms\BookableRoomReservationCreatedEmail;
use App\Jobs\Emails\BookableRooms\BookableRoomReservationPaymentConfirmedEmail;
use App\Jobs\Emails\BookableRooms\BookableRoomReservationRefundAcceptedEmail;
use App\Jobs\Emails\BookableRooms\BookableRoomReservationRefundRequestedAdminEmail;
use App\Jobs\Emails\BookableRooms\BookableRoomReservationRefundRequestedOwnerEmail;
use App\Jobs\Emails\BookableRooms\BookableRoomReservationCanceledEmail;
use App\Jobs\Emails\Schedule\RSVPRegularSeatMail;
use App\Jobs\Emails\Schedule\RSVPWaitListSeatMail;
use App\Jobs\SynchAllPresentationActions;
use App\Jobs\SynchPresentationActions;
use App\Jobs\UpdateIDPMemberInfo;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use LaravelDoctrine\ORM\Facades\EntityManager;
use models\summit\RSVP;
use models\summit\Summit;
use models\summit\SummitOrder;
use models\summit\SummitRoomReservation;

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
            EntityEventPersister::persist(MyScheduleAddEntityEventFactory::build($event));
        });

        Event::listen(\Illuminate\Mail\Events\MessageSending::class, function ($event) {
            $devEmail = env('DEV_EMAIL_TO');
            if (in_array(App::environment(), ['local', 'dev', 'testing']) && !empty($devEmail)) {
                $event->message->setTo(explode(",", $devEmail));
            }
            return true;
        });

        Event::listen(\App\Events\MyFavoritesAdd::class, function ($event) {
            EntityEventPersister::persist(MyFavoritesAddEntityEventFactory::build($event));
        });

        Event::listen(\App\Events\MyScheduleRemove::class, function ($event) {
            EntityEventPersister::persist(MyScheduleRemoveEntityEventFactory::build($event));
        });

        Event::listen(\App\Events\MyFavoritesRemove::class, function ($event) {
            EntityEventPersister::persist(MyFavoritesRemoveEntityEventFactory::build($event));
        });

        Event::listen(\App\Events\SummitEventCreated::class, function ($event) {
            EntityEventPersister::persist(SummitEventCreatedEntityEventFactory::build($event));

            SynchPresentationActions::dispatch($event->getSummitEvent()->getId())->delay(now()->addSecond(5));

        });

        Event::listen(\App\Events\SummitEventUpdated::class, function ($event) {
            EntityEventPersister::persist(SummitEventUpdatedEntityEventFactory::build($event));
            //AdminSummitEventActionSyncWorkRequestPersister::persist(SummitEventUpdatedCalendarSyncWorkRequestFactory::build($event));
        });

        Event::listen(\App\Events\SummitEventDeleted::class, function ($event) {
            EntityEventPersister::persist(SummitEventDeletedEntityEventFactory::build($event));

            /*$request = SummitEventDeletedCalendarSyncWorkRequestFactory::build($event);
            if(!is_null($request))
                AdminSummitEventActionSyncWorkRequestPersister::persist($request);*/
        });

        Event::listen(\App\Events\PresentationMaterialCreated::class, function ($event) {
            EntityEventPersister::persist(PresentationMaterialCreatedEntityEventFactory::build($event));
        });

        Event::listen(\App\Events\PresentationMaterialUpdated::class, function ($event) {
            EntityEventPersister::persist(PresentationMaterialUpdatedEntityEventFactory::build(($event)));
        });

        Event::listen(\App\Events\PresentationMaterialDeleted::class, function ($event) {
            EntityEventPersister::persist(PresentationMaterialDeletedEntityEventFactory::build($event));
        });

        Event::listen(\App\Events\PresentationSpeakerCreated::class, function ($event) {
            EntityEventPersister::persist_list(PresentationSpeakerCreatedEntityEventFactory::build($event));
        });

        Event::listen(\App\Events\PresentationSpeakerUpdated::class, function ($event) {
            EntityEventPersister::persist_list(PresentationSpeakerUpdatedEntityEventFactory::build($event));
        });

        Event::listen(\App\Events\PresentationSpeakerDeleted::class, function ($event) {
            EntityEventPersister::persist_list(PresentationSpeakerDeletedEntityEventFactory::build($event));
        });

        // event types

        Event::listen(\App\Events\SummitEventTypeInserted::class, function ($event) {
            EntityEventPersister::persist(SummitEventTypeActionEntityEventFactory::build($event, 'INSERT'));
        });

        Event::listen(\App\Events\SummitEventTypeUpdated::class, function ($event) {
            EntityEventPersister::persist(SummitEventTypeActionEntityEventFactory::build($event, 'UPDATE'));
        });

        Event::listen(\App\Events\SummitEventTypeDeleted::class, function ($event) {
            EntityEventPersister::persist(SummitEventTypeActionEntityEventFactory::build($event, 'DELETE'));
        });

        // tracks

        Event::listen(\App\Events\TrackInserted::class, function ($event) {
            EntityEventPersister::persist(TrackActionEntityEventFactory::build($event, 'INSERT'));
        });

        Event::listen(\App\Events\TrackUpdated::class, function ($event) {
            EntityEventPersister::persist(TrackActionEntityEventFactory::build($event, 'UPDATE'));
        });

        Event::listen(\App\Events\TrackDeleted::class, function ($event) {
            EntityEventPersister::persist(TrackActionEntityEventFactory::build($event, 'DELETE'));
        });

        // locations events


        Event::listen(\App\Events\SummitVenueRoomInserted::class, function ($event) {
            EntityEventPersister::persist(LocationActionEntityEventFactory::build($event, 'INSERT'));
        });

        Event::listen(\App\Events\SummitVenueRoomUpdated::class, function ($event) {
            EntityEventPersister::persist(LocationActionEntityEventFactory::build($event, 'UPDATE'));
            /*$published_events = $event->getRelatedEventIds();
            if(count($published_events) > 0){
                AdminSummitLocationActionSyncWorkRequestPersister::persist
                (
                    AdminSummitLocationActionSyncWorkRequestFactory::build($event, 'UPDATE')
                );
            }*/
        });

        Event::listen(\App\Events\SummitVenueRoomDeleted::class, function ($event) {
            EntityEventPersister::persist(LocationActionEntityEventFactory::build($event, 'DELETE'));
            /*$published_events = $event->getRelatedEventIds();
            if(count($published_events) > 0){
                AdminSummitLocationActionSyncWorkRequestPersister::persist
                (
                    AdminSummitLocationActionSyncWorkRequestFactory::build($event, 'REMOVE')
                );
            }*/
        });

        Event::listen(\App\Events\LocationInserted::class, function ($event) {
            EntityEventPersister::persist(LocationActionEntityEventFactory::build($event, 'INSERT'));
        });

        Event::listen(\App\Events\LocationUpdated::class, function ($event) {
            EntityEventPersister::persist(LocationActionEntityEventFactory::build($event, 'UPDATE'));
            /*$published_events = $event->getRelatedEventIds();
            if(count($published_events) > 0){
                AdminSummitLocationActionSyncWorkRequestPersister::persist
                (
                    AdminSummitLocationActionSyncWorkRequestFactory::build($event, 'UPDATE')
                );
            }*/
        });

        Event::listen(\App\Events\LocationDeleted::class, function ($event) {
            EntityEventPersister::persist(LocationActionEntityEventFactory::build($event, 'DELETE'));
            /*$published_events = $event->getRelatedEventIds();
            if(count($published_events) > 0){
                AdminSummitLocationActionSyncWorkRequestPersister::persist
                (
                    AdminSummitLocationActionSyncWorkRequestFactory::build($event, 'REMOVE')
                );
            }*/
        });

        Event::listen(\App\Events\FloorInserted::class, function ($event) {
            EntityEventPersister::persist(FloorActionEntityEventFactory::build($event, 'INSERT'));
        });

        Event::listen(\App\Events\FloorUpdated::class, function ($event) {
            EntityEventPersister::persist(FloorActionEntityEventFactory::build($event, 'UPDATE'));
        });

        Event::listen(\App\Events\FloorDeleted::class, function ($event) {
            EntityEventPersister::persist(FloorActionEntityEventFactory::build($event, 'DELETE'));
        });

        // location images

        Event::listen(\App\Events\LocationImageInserted::class, function ($event) {
            EntityEventPersister::persist(LocationImageActionEntityEventFactory::build($event, 'INSERT'));
        });

        Event::listen(\App\Events\LocationImageUpdated::class, function ($event) {
            EntityEventPersister::persist(LocationImageActionEntityEventFactory::build($event, 'UPDATE'));
        });

        Event::listen(\App\Events\LocationImageDeleted::class, function ($event) {
            EntityEventPersister::persist(LocationImageActionEntityEventFactory::build($event, 'DELETE'));
        });

        // ticket types

        Event::listen(\App\Events\SummitTicketTypeInserted::class, function ($event) {
            EntityEventPersister::persist(SummitTicketTypeActionEntityEventFactory::build($event, 'INSERT'));
        });

        Event::listen(\App\Events\SummitTicketTypeUpdated::class, function ($event) {
            EntityEventPersister::persist(SummitTicketTypeActionEntityEventFactory::build($event, 'UPDATE'));
        });

        Event::listen(\App\Events\SummitTicketTypeDeleted::class, function ($event) {
            EntityEventPersister::persist(SummitTicketTypeActionEntityEventFactory::build($event, 'DELETE'));
        });

        // track groups

        Event::listen(\App\Events\TrackGroupInserted::class, function ($event) {
            EntityEventPersister::persist(TrackGroupActionActionEntityEventFactory::build($event, 'INSERT'));
        });

        Event::listen(\App\Events\TrackGroupUpdated::class, function ($event) {
            EntityEventPersister::persist(TrackGroupActionActionEntityEventFactory::build($event, 'UPDATE'));
        });

        Event::listen(\App\Events\TrackGroupDeleted::class, function ($event) {
            EntityEventPersister::persist(TrackGroupActionActionEntityEventFactory::build($event, 'DELETE'));
        });

        // summits

        Event::listen(\App\Events\SummitUpdated::class, function ($event) {
            EntityEventPersister::persist(SummitActionEntityEventFactory::build($event, 'UPDATE'));
        });

        Event::listen(\App\Events\SummitDeleted::class, function ($event) {
            EntityEventPersister::persist(SummitActionEntityEventFactory::build($event, 'DELETE'));
        });

        // bookable rooms events

        Event::listen(\App\Events\BookableRoomReservationRefundAccepted::class, function ($event) {
            $repository = EntityManager::getRepository(SummitRoomReservation::class);
            $reservation = $repository->find($event->getReservationId());
            if (is_null($reservation) || !$reservation instanceof SummitRoomReservation) return;

            Mail::queue(new BookableRoomReservationRefundAcceptedEmail($reservation));

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

        Event::listen(PresentationActionTypeCreated::class, function ($event) {

            if (!$event instanceof PresentationActionTypeCreated) return;

            $summit = $event->getActionType()->getSummit();

            SynchAllPresentationActions::dispatch($summit->getId());
        });

        Event::listen(TicketUpdated::class, function ($event) {

            if (!$event instanceof TicketUpdated) return;

            // publish profile changes to the IDP
            UpdateIDPMemberInfo::dispatch($event->getAttendee());
        });
    }
}
