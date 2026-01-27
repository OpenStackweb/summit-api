<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;


#[OA\Schema(
    schema: 'SummitAttendeeTicket',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', example: 1, format: "time_epoch"),
        new OA\Property(property: 'last_edited', type: 'integer', example: 1, format: "time_epoch"),
        new OA\Property(property: 'number', type: 'string'),
        new OA\Property(property: 'status', type: 'string'),
        new OA\Property(property: 'external_order_id', type: 'string'),
        new OA\Property(property: 'external_attendee_id', type: 'string'),
        new OA\Property(property: 'bought_date', type: 'integer', format: "time_epoch"),
        new OA\Property(property: 'ticket_type_id', type: 'integer'),
        new OA\Property(property: 'owner_id', type: 'integer'),
        new OA\Property(property: 'order_id', type: 'integer'),
        new OA\Property(property: 'badge_id', type: 'integer'),
        new OA\Property(property: 'promo_code_id', type: 'integer'),
        new OA\Property(property: 'raw_cost', type: 'float'),
        new OA\Property(property: 'net_selling_cost', type: 'float'),
        new OA\Property(property: 'raw_cost_in_cents', type: 'integer'),
        new OA\Property(property: 'final_amount', type: 'float'),
        new OA\Property(property: 'final_amount_in_cents', type: 'integer'),
        new OA\Property(property: 'discount', type: 'float'),
        new OA\Property(property: 'discount_rate', type: 'float'),
        new OA\Property(property: 'discount_in_cents', type: 'integer'),
        new OA\Property(property: 'refunded_amount', type: 'float'),
        new OA\Property(property: 'refunded_amount_in_cents', type: 'integer'),
        new OA\Property(property: 'total_refunded_amount', type: 'float'),
        new OA\Property(property: 'total_refunded_amount_in_cents', type: 'integer'),
        new OA\Property(property: 'currency', type: 'string'),
        new OA\Property(property: 'currency_symbol', type: 'string'),
        new OA\Property(property: 'taxes_amount', type: 'float'),
        new OA\Property(property: 'taxes_amount_in_cents', type: 'integer'),
        new OA\Property(property: 'is_active', type: 'boolean'),
        new OA\Property(property: 'qr_code', type: 'string'),
        new OA\Property(property: 'badge_prints_count', type: 'integer', example: 16),
        new OA\Property(property: 'owner', ref:'#/components/schemas/SummitAttendee', description: 'An object of SummitAttendee, when expanded'),
        new OA\Property(property: 'order', ref:'#/components/schemas/SummitOrder', description: 'An object of SummitOrder, when expanded'),
        new OA\Property(property: 'ticket_type', ref:'#/components/schemas/SummitTicketType', description: 'An object of SummitTicketType, when expanded'),
        new OA\Property(property: 'badge', type: 'object', description: 'An object of SummitBadge, when expanded'),
        new OA\Property(property: 'promo_code', type: 'object', description: 'An object of SummitPromoCode, when expanded'),
        new OA\Property(property: 'refund_requests', type: 'object', description: 'An object of SummitRefundRequest, when expanded'),
        new OA\Property(property: 'applied_taxes', type: 'object', description: 'An object of SummitAppliedTax, when expanded'),
    ])
]
class SummitAttendeeTicketSchema
{
}
