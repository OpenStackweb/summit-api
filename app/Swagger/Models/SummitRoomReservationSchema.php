<?php

namespace App\Swagger\Models;

use OpenApi\Attributes as OA;

/**
 * Schema for SummitRoomReservation model
 */
#[OA\Schema(
    schema: 'SummitRoomReservation',
    type: 'object',
    description: 'Summit room reservation',
    properties: [
        new OA\Property(property: 'id', type: 'integer', description: 'Unique identifier'),
        new OA\Property(property: 'created', type: 'integer', format: 'int64', description: 'Creation timestamp (epoch)'),
        new OA\Property(property: 'last_edited', type: 'integer', format: 'int64', description: 'Last modification timestamp (epoch)'),
        new OA\Property(property: 'room_id', type: 'integer', description: 'ID of the booked room'),
        new OA\Property(property: 'owner_id', type: 'integer', description: 'ID of the reservation owner'),
        new OA\Property(property: 'summit_id', type: 'integer', description: 'ID of the summit'),
        new OA\Property(property: 'amount', type: 'number', format: 'float', description: 'Total reservation amount'),
        new OA\Property(property: 'refunded_amount', type: 'number', format: 'float', description: 'Refunded amount'),
        new OA\Property(property: 'currency', type: 'string', description: 'Currency code (e.g., USD)'),
        new OA\Property(property: 'status', type: 'string', description: 'Reservation status (Reserved, Error, Paid, RequestedRefund, Refunded, Canceled)'),
        new OA\Property(property: 'payment_gateway_cart_id', type: 'string', description: 'Payment gateway cart ID'),
        new OA\Property(property: 'payment_gateway_client_token', type: 'string', description: 'Payment gateway client token'),
        new OA\Property(property: 'start_datetime', type: 'integer', format: 'int64', description: 'Reservation start timestamp (epoch)'),
        new OA\Property(property: 'end_datetime', type: 'integer', format: 'int64', description: 'Reservation end timestamp (epoch)'),
        new OA\Property(property: 'local_start_datetime', type: 'string', format: 'date-time', description: 'Local start datetime'),
        new OA\Property(property: 'local_end_datetime', type: 'string', format: 'date-time', description: 'Local end datetime'),
        new OA\Property(property: 'approved_payment_date', type: 'integer', format: 'int64', nullable: true, description: 'Approved payment timestamp (epoch)'),
        new OA\Property(property: 'last_error', type: 'string', nullable: true, description: 'Last error message'),
        new OA\Property(
            property: 'owner',
            ref: '#/components/schemas/Member',
            nullable: true,
            description: 'Reservation owner'
        ),
        new OA\Property(
            property: 'room',
            ref: '#/components/schemas/SummitBookableVenueRoom',
            nullable: true,
            description: 'Booked room'
        ),
    ]
)]
class SummitRoomReservationSchema {}
