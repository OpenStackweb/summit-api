<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PaginatedSummitOrdersResponse',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/SummitOrder')
                )
            ]
        )
    ]
)]
class PaginatedSummitOrdersResponseSchema
{
}

#[OA\Schema(
    schema: 'PaginatedSummitAttendeeTicketsResponse',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/SummitAttendeeTicket')
                )
            ]
        )
    ]
)]
class PaginatedSummitAttendeeTicketsResponseSchema
{
}

#[OA\Schema(
    schema: 'PaginatedRefundRequestsResponse',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/SummitAttendeeTicketRefundRequest')
                )
            ]
        )
    ]
)]
class PaginatedRefundRequestsResponseSchema
{
}

#[OA\Schema(
    schema: 'ExtraQuestions',
    description: 'Extra questions for SummitOrder reservations',
    type: 'object',
    properties: [
        new OA\Property(property: 'question_id', type: 'integer'),
        new OA\Property(property: 'answer', type: 'string'),
    ]
)]
class ExtraQuestionsSchema
{
}

#[OA\Schema(
    schema: 'TicketRequest',
    description: 'TicketRequest for SummitOrder reservations',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'type_id', type: 'integer'),
        new OA\Property(property: 'promo_code', type: 'string'),
        new OA\Property(property: 'attendee_first_name', type: 'string'),
        new OA\Property(property: 'attendee_last_name', type: 'string'),
        new OA\Property(property: 'attendee_email', type: 'string'),
        new OA\Property(property: 'extra_questions', type: 'array', items: new OA\Items(ref: '#/components/schemas/ExtraQuestions'))
    ]
)]
class TicketRequestSchema
{
}

#[OA\Schema(
    schema: 'GetMyTicketsByOrderIdRequest',
    description: 'GetMyTicketsByOrderIdRequest for Ticket reservations list',
    type: 'object',
    properties: [
        new OA\Property(property: 'number', type: 'string'),
        new OA\Property(property: 'owner_email', type: 'string'),
        new OA\Property(property: 'order_id', type: 'integer'),
        new OA\Property(property: 'order_owner_id', type: 'integer'),
        new OA\Property(property: 'is_active', type: 'boolean'),
        new OA\Property(property: 'assigned_to', type: 'string', enum: ['Me', 'SomeoneElse', 'Nobody']),
        new OA\Property(property: 'owner_status', type: 'string', enum: ['Complete', 'Incomplete']),
        new OA\Property(property: 'badge_features_id', type: 'integer'),
        new OA\Property(property: 'final_amount', type: 'float'),
        new OA\Property(property: 'ticket_type_id', type: 'integer'),
        new OA\Property(property: 'promo_code', type: 'string'),
    ]
)]
class GetMyTicketsByOrderIdRequestSchema
{
}

#[OA\Schema(
    schema: 'ReserveOrderRequest',
    type: 'object',
    required: ['tickets'],
    properties: [
        new OA\Property(property: 'owner_first_name', type: 'string', maxLength: 255, description: 'Required if no current user'),
        new OA\Property(property: 'owner_last_name', type: 'string', maxLength: 255, description: 'Required if no current user'),
        new OA\Property(property: 'owner_email', type: 'string', format: 'email', maxLength: 255, description: 'Required if no current user'),
        new OA\Property(property: 'owner_company', type: 'string', maxLength: 255),
        new OA\Property(property: 'owner_company_id', type: 'integer'),
        new OA\Property(
            property: 'tickets',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/TicketRequest'),
            description: 'Array of ticket DTOs'
        ),
        new OA\Property(
            property: 'extra_questions',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/ExtraQuestions'),
            description: 'Array of extra question answers'
        ),
    ]
)]
class ReserveOrderRequestSchema
{
}


#[OA\Schema(
    schema: 'UpdateMyOrderRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'owner_company', type: 'string', maxLength: 255),
        new OA\Property(property: 'billing_address_1', type: 'string', maxLength: 255),
        new OA\Property(property: 'billing_address_2', type: 'string', maxLength: 255),
        new OA\Property(property: 'billing_address_zip_code', type: 'string', maxLength: 255),
        new OA\Property(property: 'billing_address_city', type: 'string', maxLength: 255),
        new OA\Property(property: 'billing_address_state', type: 'string', maxLength: 255),
        new OA\Property(property: 'billing_address_country', type: 'string', maxLength: 2, description: 'ISO Alpha-2 country code'),
        new OA\Property(
            property: 'extra_questions',
            type: 'array',
            items: new OA\Items(type: 'object'),
            description: 'Array of extra question answers'
        ),
    ]
)]
class UpdateMyOrderRequestSchema
{
}

#[OA\Schema(
    schema: 'AssignAttendeeRequest',
    type: 'object',
    required: ['attendee_email'],
    properties: [
        new OA\Property(property: 'attendee_first_name', type: 'string', maxLength: 255),
        new OA\Property(property: 'attendee_last_name', type: 'string', maxLength: 255),
        new OA\Property(property: 'attendee_email', type: 'string', format: 'email', maxLength: 255),
        new OA\Property(property: 'attendee_company', type: 'string', maxLength: 255),
        new OA\Property(property: 'disclaimer_accepted', type: 'boolean'),
        new OA\Property(
            property: 'extra_questions',
            type: 'array',
            items: new OA\Items(type: 'object'),
            description: 'Array of extra question answers'
        ),
        new OA\Property(property: 'message', type: 'string', maxLength: 1024, description: 'Optional message to the attendee'),
    ]
)]
class AssignAttendeeRequestSchema
{
}

#[OA\Schema(
    schema: 'UpdateTicketRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'ticket_type_id', type: 'integer'),
        new OA\Property(property: 'badge_type_id', type: 'integer'),
        new OA\Property(property: 'attendee_first_name', type: 'string', maxLength: 255),
        new OA\Property(property: 'attendee_last_name', type: 'string', maxLength: 255),
        new OA\Property(property: 'attendee_email', type: 'string', format: 'email', maxLength: 255),
        new OA\Property(property: 'attendee_company', type: 'string', maxLength: 255),
        new OA\Property(property: 'attendee_company_id', type: 'integer'),
        new OA\Property(property: 'disclaimer_accepted', type: 'boolean'),
        new OA\Property(
            property: 'extra_questions',
            type: 'array',
            items: new OA\Items(type: 'object'),
            description: 'Array of extra question answers'
        ),
    ]
)]
class UpdateTicketRequestSchema
{
}

#[OA\Schema(
    schema: 'UpdateTicketByHashRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'attendee_first_name', type: 'string', maxLength: 255),
        new OA\Property(property: 'attendee_last_name', type: 'string', maxLength: 255),
        new OA\Property(property: 'attendee_company', type: 'string', maxLength: 255),
        new OA\Property(property: 'attendee_company_id', type: 'integer'),
        new OA\Property(property: 'disclaimer_accepted', type: 'boolean'),
        new OA\Property(property: 'share_contact_info', type: 'boolean'),
        new OA\Property(
            property: 'extra_questions',
            type: 'array',
            items: new OA\Items(type: 'object'),
            description: 'Array of extra question answers'
        ),
    ]
)]
class UpdateTicketByHashRequestSchema
{
}

#[OA\Schema(
    schema: 'UpdateTicketsByOrderHashRequest',
    type: 'object',
    required: ['tickets'],
    properties: [
        new OA\Property(
            property: 'tickets',
            type: 'array',
            items: new OA\Items(type: 'object'),
            description: 'Array of ticket DTOs to update'
        ),
    ]
)]
class UpdateTicketsByOrderHashRequestSchema
{
}

#[OA\Schema(
    schema: 'AddTicketRequest',
    type: 'object',
    required: ['ticket_type_id', 'ticket_qty'],
    properties: [
        new OA\Property(property: 'ticket_type_id', type: 'integer'),
        new OA\Property(property: 'ticket_qty', type: 'integer', minimum: 1),
        new OA\Property(property: 'promo_code', type: 'string'),
        new OA\Property(property: 'badge_type_id', type: 'integer'),
        new OA\Property(property: 'attendee_first_name', type: 'string', maxLength: 255),
        new OA\Property(property: 'attendee_last_name', type: 'string', maxLength: 255),
        new OA\Property(property: 'attendee_email', type: 'string', format: 'email', maxLength: 255),
        new OA\Property(property: 'attendee_company', type: 'string', maxLength: 255),
        new OA\Property(property: 'disclaimer_accepted', type: 'boolean'),
        new OA\Property(
            property: 'extra_questions',
            type: 'array',
            items: new OA\Items(type: 'object'),
            description: 'Array of extra question answers'
        ),
    ]
)]
class AddTicketRequestSchema
{
}

#[OA\Schema(
    schema: 'CreateOfflineOrderRequest',
    type: 'object',
    required: ['ticket_type_id', 'ticket_qty'],
    properties: [
        new OA\Property(property: 'owner_first_name', type: 'string', maxLength: 255, description: 'Required without owner_id'),
        new OA\Property(property: 'owner_last_name', type: 'string', maxLength: 255, description: 'Required without owner_id'),
        new OA\Property(property: 'owner_email', type: 'string', format: 'email', maxLength: 255, description: 'Required without owner_id'),
        new OA\Property(property: 'owner_id', type: 'integer', description: 'Required without owner names/email'),
        new OA\Property(property: 'owner_company', type: 'string', maxLength: 255),
        new OA\Property(property: 'ticket_type_id', type: 'integer'),
        new OA\Property(property: 'ticket_qty', type: 'integer', minimum: 1),
        new OA\Property(property: 'promo_code', type: 'string'),
        new OA\Property(property: 'billing_address_1', type: 'string', maxLength: 255),
        new OA\Property(property: 'billing_address_2', type: 'string', maxLength: 255),
        new OA\Property(property: 'billing_address_zip_code', type: 'string', maxLength: 255),
        new OA\Property(property: 'billing_address_city', type: 'string', maxLength: 255),
        new OA\Property(property: 'billing_address_state', type: 'string', maxLength: 255),
        new OA\Property(property: 'billing_address_country', type: 'string', maxLength: 2, description: 'ISO Alpha-2 country code'),
        new OA\Property(
            property: 'extra_questions',
            type: 'array',
            items: new OA\Items(type: 'object'),
            description: 'Array of extra question answers'
        ),
    ]
)]
class CreateOfflineOrderRequestSchema
{
}

#[OA\Schema(
    schema: 'UpdateOrderRequest',
    type: 'object',
    required: ['owner_company'],
    properties: [
        new OA\Property(property: 'owner_first_name', type: 'string', maxLength: 255, description: 'Required without owner_id'),
        new OA\Property(property: 'owner_last_name', type: 'string', maxLength: 255, description: 'Required without owner_id'),
        new OA\Property(property: 'owner_email', type: 'string', format: 'email', maxLength: 255, description: 'Required without owner_id'),
        new OA\Property(property: 'owner_id', type: 'integer', description: 'Required without owner names/email'),
        new OA\Property(property: 'owner_company', type: 'string', maxLength: 255),
        new OA\Property(property: 'billing_address_1', type: 'string', maxLength: 255),
        new OA\Property(property: 'billing_address_2', type: 'string', maxLength: 255),
        new OA\Property(property: 'billing_address_zip_code', type: 'string', maxLength: 255),
        new OA\Property(property: 'billing_address_city', type: 'string', maxLength: 255),
        new OA\Property(property: 'billing_address_state', type: 'string', maxLength: 255),
        new OA\Property(property: 'billing_address_country', type: 'string', maxLength: 2, description: 'ISO Alpha-2 country code'),
        new OA\Property(
            property: 'extra_questions',
            type: 'array',
            items: new OA\Items(type: 'object'),
            description: 'Array of extra question answers'
        ),
    ]
)]
class UpdateOrderRequestSchema
{
}

#[OA\Schema(
    schema: 'CancelRefundRequestRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'notes', type: 'string', maxLength: 255),
    ]
)]
class CancelRefundRequestRequestSchema
{
}

#[OA\Schema(
    schema: 'ReInviteAttendeeRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'message', type: 'string', maxLength: 1024, description: 'Optional message to the attendee'),
    ]
)]
class ReInviteAttendeeRequestSchema
{
}

#[OA\Schema(
    schema: 'DelegateTicketRequest',
    type: 'object',
    required: ['attendee_first_name', 'attendee_last_name'],
    properties: [
        new OA\Property(property: 'attendee_first_name', type: 'string', maxLength: 255),
        new OA\Property(property: 'attendee_last_name', type: 'string', maxLength: 255),
        new OA\Property(property: 'attendee_email', type: 'string', format: 'email', maxLength: 255),
        new OA\Property(property: 'attendee_company', type: 'string', maxLength: 255),
        new OA\Property(property: 'attendee_company_id', type: 'integer'),
        new OA\Property(property: 'disclaimer_accepted', type: 'boolean'),
        new OA\Property(
            property: 'extra_questions',
            type: 'array',
            items: new OA\Items(type: 'object'),
            description: 'Array of extra question answers'
        ),
    ]
)]
class DelegateTicketRequestSchema
{
}

// Response Schemas - Generic Base Schemas

#[OA\Schema(
    schema: 'SummitOrder',
    description: 'Generic SummitOrder response - fields may vary based on context and serializer type',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'number', type: 'string'),
        new OA\Property(property: 'status', type: 'string', enum: ['Reserved', 'Paid', 'Cancelled', 'RefundRequested', 'Refunded', 'Confirmed', 'Error']),
        new OA\Property(property: 'hash', type: 'string'),
        new OA\Property(property: 'amount', type: 'number', format: 'float'),
        new OA\Property(property: 'currency', type: 'string'),
        new OA\Property(property: 'created', type: 'integer', description: 'Unix timestamp'),
        new OA\Property(property: 'owner_email', type: 'string'),
        new OA\Property(property: 'tickets', type: 'array', items: new OA\Items(type: 'object')),
    ]
)]
class SummitOrderSchema
{
}

#[OA\Schema(
    schema: 'SummitAttendeeTicket',
    description: 'Generic SummitAttendeeTicket response - fields may vary based on context and serializer type',
    type: 'object',
    required: [
        'id',
        'number',
        'status',
        'external_order_id',
        'external_attendee_id',
        'bought_date',
        'ticket_type_id',
        'owner_id',
        'order_id',
        'badge_id',
        'promo_code_id',
        'raw_cost',
        'net_selling_cost',
        'raw_cost_in_cents',
        'final_amount',
        'final_amount_in_cents',
        'discount',
        'discount_rate',
        'discount_in_cents',
        'refunded_amount',
        'refunded_amount_in_cents',
        'total_refunded_amount',
        'total_refunded_amount_in_cents',
        'currency',
        'currency_symbol',
        'taxes_amount',
        'taxes_amount_in_cents',
        'is_active',
        'qr_code',
        'owner_email',
        'created'
    ],
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'created', type: 'integer', description: 'Unix timestamp'),
        new OA\Property(property: 'last_edited', type: 'integer', description: 'Unix timestamp'),
        new OA\Property(property: 'number', type: 'string'),
        new OA\Property(property: 'status', type: 'string'),
        new OA\Property(property: 'external_order_id', type: 'string'),
        new OA\Property(property: 'external_attendee_id', type: 'string'),
        new OA\Property(property: 'bought_date', type: 'integer'),
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
        new OA\Property(property: 'owner_email', type: 'string'),
    ]
)]
class SummitAttendeeTicketSchema
{
}


#[OA\Schema(
    schema: 'SummitOrderReservation',
    description: 'SummitOrder with ReservationType serialization - used for order reservations',
    type: 'object',
    required: [
        'id',
        'created',
        'last_edited',
        'number',
        'status',
        'hash',
        'payment_method',
        'owner_first_name',
        'owner_last_name',
        'owner_email',
        'summit_id',
        'currency',
        'currency_symbol',
        'raw_amount',
        'raw_amount_in_cents',
        'amount',
        'amount_in_cents',
        'taxes_amount',
        'taxes_amount_in_cents',
        'discount_amount',
        'discount_rate',
        'discount_amount_in_cents',
        'payment_gateway_client_token',
        'payment_gateway_cart_id',
        'hash_creation_date',
        'refunded_amount',
        'refunded_amount_in_cents',
        'total_refunded_amount',
        'total_refunded_amount_in_cents',
        'credit_card_type',
        'credit_card_4number',
        'payment_info_type',
        'payment_info_details',
        'tickets'
    ],
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'created', type: 'integer', description: 'Unix timestamp'),
        new OA\Property(property: 'last_edited', type: 'integer', description: 'Unix timestamp'),
        new OA\Property(property: 'number', type: 'string'),
        new OA\Property(property: 'status', type: 'string', enum: ['Reserved', 'Paid', 'Cancelled', 'RefundRequested', 'Refunded', 'Confirmed', 'Error']),
        new OA\Property(property: 'hash', type: 'string'),
        new OA\Property(property: 'payment_method', type: 'string'),
        new OA\Property(property: 'owner_first_name', type: 'string'),
        new OA\Property(property: 'owner_last_name', type: 'string'),
        new OA\Property(property: 'owner_email', type: 'string'),
        new OA\Property(property: 'owner_company', type: ['string', 'Company']),
        new OA\Property(property: 'owner_company_id', type: 'integer'),
        new OA\Property(property: 'owner_id', type: 'integer'),
        new OA\Property(property: 'owner', type: 'Member'),
        new OA\Property(property: 'summit_id', type: 'integer'),
        new OA\Property(property: 'currency', type: 'string'),
        new OA\Property(property: 'currency_symbol', type: 'string'),
        new OA\Property(property: 'raw_amount', type: 'float'),
        new OA\Property(property: 'raw_amount_in_cents', type: 'integer'),
        new OA\Property(property: 'amount', type: 'number', format: 'float'),
        new OA\Property(property: 'amount_in_cents', type: 'integer'),
        new OA\Property(property: 'taxes_amount', type: 'number', format: 'float'),
        new OA\Property(property: 'taxes_amount_in_cents', type: 'integer'),
        new OA\Property(property: 'discount_amount', type: 'number', format: 'float'),
        new OA\Property(property: 'discount_rate', type: 'float'),
        new OA\Property(property: 'discount_amount_in_cents', type: 'integer'),
        new OA\Property(property: 'payment_gateway_client_token', type: 'string'),
        new OA\Property(property: 'payment_gateway_cart_id', type: 'string'),
        new OA\Property(property: 'hash_creation_date', type: 'integer', description: 'Unix timestamp'),
        new OA\Property(property: 'refunded_amount', type: 'number', format: 'float'),
        new OA\Property(property: 'refunded_amount_in_cents', type: 'integer', format: 'float'),
        new OA\Property(property: 'total_refunded_amount', type: 'float', format: 'float'),
        new OA\Property(property: 'total_refunded_amount_in_cents', type: 'integer', format: 'float'),
        new OA\Property(property: 'credit_card_type', type: 'string'),
        new OA\Property(property: 'credit_card_4number', type: 'string'),
        new OA\Property(property: 'payment_info_type', type: 'string'),
        new OA\Property(property: 'payment_info_details', type: 'string'),
        new OA\Property(property: 'tickets', type: 'array', items: new OA\Items(type: 'SummitAttendeeTicket')),
        new OA\Property(property: 'extra_questions', type: 'array', items: new OA\Items(ref: '#/components/schemas/ExtraQuestions')),
        new OA\Property(property: 'applied_taxes', type: 'array', items: new OA\Items(type: 'SummitTaxType')),

    ]
)]
class SummitOrderReservationSchema
{
}

#[OA\Schema(
    schema: 'CheckoutOrderRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'billing_address_1', type: 'string', maxLength: 255),
        new OA\Property(property: 'billing_address_2', type: 'string', maxLength: 255),
        new OA\Property(property: 'billing_address_zip_code', type: 'string', maxLength: 255),
        new OA\Property(property: 'billing_address_city', type: 'string', maxLength: 255),
        new OA\Property(property: 'billing_address_state', type: 'string', maxLength: 255),
        new OA\Property(property: 'billing_address_country', type: 'string', maxLength: 2, description: 'ISO Alpha-2 country code'),
        new OA\Property(property: 'payment_method_id', type: 'string'),
    ]
)]
class CheckoutOrderRequestSchema
{
}

#[OA\Schema(
    schema: 'SummitOrderCheckout',
    description: 'SummitOrder with CheckOutType serialization - used after checkout/payment',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'created', type: 'integer', description: 'Unix timestamp'),
        new OA\Property(property: 'last_edited', type: 'integer', description: 'Unix timestamp'),
        new OA\Property(property: 'number', type: 'string'),
        new OA\Property(property: 'status', type: 'string', enum: ['Reserved', 'Paid', 'Cancelled', 'RefundRequested', 'Refunded', 'Confirmed', 'Error']),
        new OA\Property(property: 'owner_first_name', type: 'string'),
        new OA\Property(property: 'owner_last_name', type: 'string'),
        new OA\Property(property: 'owner_email', type: 'string'),
        new OA\Property(property: 'owner_company', type: ['string', 'Company']),
        new OA\Property(property: 'owner_company_id', type: 'integer'),
        new OA\Property(property: 'owner_id', type: 'integer'),
        new OA\Property(property: 'summit_id', type: 'integer'),
        new OA\Property(property: 'currency', type: 'string'),
        new OA\Property(property: 'extra_questions', type: 'array', items: new OA\Items(oneOf: [ new OA\Schema(type: 'integer'), new OA\Schema(ref: '#/components/schemas/ExtraQuestions')])),
        new OA\Property(property: 'tickets', type: 'array', items: new OA\Items(oneOf: [ new OA\Schema(type: 'integer'), new OA\Schema(ref: '#/components/schemas/SummitAttendeeTicket')])),
        new OA\Property(property: 'owner', type: 'Member'),
    ]
)]
class SummitOrderCheckoutSchema
{
}

#[OA\Schema(
    schema: 'SummitAttendeeTicketBase',
    description: 'Base SummitAttendeeTicket response - fields may vary based on context and serializer type',
    type: 'object',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/SummitAttendeeTicket'),
    ],
    properties: [
        new OA\Property(property: 'ticket_type', type: 'array', items: new OA\Items(type: 'SummitTicketType')),
        new OA\Property(property: 'badge', type: 'array', items: new OA\Items(type: 'SummitAttendeeBadge')),
        new OA\Property(property: 'promo_code', type: 'array', items: new OA\Items(type: 'SummitRegistrationPromoCode')),
        new OA\Property(property: 'owner', type: 'array', items: new OA\Items(type: 'SummitAttendee')),
        new OA\Property(property: 'refund_requests', type: 'array', items: new OA\Items(type: ['integer', 'SummitAttendeeTicketRefundRequest'])),
        new OA\Property(property: 'applied_taxes', type: 'array', items: new OA\Items(type: 'integer')),
        new OA\Property(property: 'order', type: 'array', items: new OA\Items(type: 'SummitOrder')),
    ]
)]
class SummitAttendeeTicketBaseSchema
{
}

#[OA\Schema(
    schema: 'SummitAttendeeTicketPublic',
    description: 'SummitAttendeeTicket with PublicEdition serialization - public ticket access',
    type: 'object',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/SummitAttendeeTicketBase'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(property: 'order_extra_questions', type: 'array', items: new OA\Items(type: 'SummitOrderExtraQuestionType')),
            ]
        ),
    ],
    properties: []
)]
class SummitAttendeeTicketPublicSchema
{
}

#[OA\Schema(
    schema: 'SummitAttendeeTicketPrivate',
    ref: '#/components/schemas/SummitAttendeeTicketBase',
)]
class SummitAttendeeTicketPrivateSchema
{
}


//@TODO Matu Checkear a partir de aqui si es necesario separar mas los schemas de ticket segun el contexto

#[OA\Schema(
    schema: 'SummitAttendeeTicketAdmin',
    description: 'SummitAttendeeTicket with AdminType serialization - full admin access',
    type: 'object',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/SummitAttendeeTicket'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(property: 'owner', type: 'array', items: new OA\Items(type: 'SummitAttendee')),
                new OA\Property(property: 'order', type: 'array', items: new OA\Items(type: 'SummitOrder')),
            ]
        ),
    ],

)]
class SummitAttendeeTicketAdminSchema
{
}

#[OA\Schema(
    schema: 'SummitAttendeeTicketGuest',
    description: 'SummitAttendeeTicket with Guest edition serialization',
    type: 'object',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/SummitAttendeeTicketBase'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(property: 'edit_link', type: 'array', items: new OA\Items(type: 'string')),
            ]
        ),
    ],

)]
class SummitAttendeeTicketGuestSchema
{
}

#[OA\Schema(
    schema: 'SummitAttendeeTicketRefundRequest',
    description: 'Refund request for a ticket',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'created', type: 'integer', description: 'Unix timestamp'),
        new OA\Property(property: 'last_edited', type: 'integer', description: 'Unix timestamp'),
        new OA\Property(property: 'status', type: 'string', enum: ['Requested', 'Approved', 'Rejected']),
        new OA\Property(property: 'refunded_amount', type: 'number', format: 'float'),
        new OA\Property(property: 'refunded_amount_in_cents', type: 'integer'),
        new OA\Property(property: 'taxes_refunded_amount', type: 'number', format: 'float'),
        new OA\Property(property: 'taxes_refunded_amount_in_cents', type: 'integer'),
        new OA\Property(property: 'total_refunded_amount', type: 'number', format: 'float'),
        new OA\Property(property: 'total_refunded_amount_in_cents', type: 'integer'),
        new OA\Property(property: 'notes', type: 'string'),
        new OA\Property(property: 'payment_gateway_result', type: 'string'),
        new OA\Property(property: 'action_date', type: 'integer', description: 'Unix timestamp'),
        new OA\Property(property: 'requested_by_id', type: 'integer'),
        new OA\Property(property: 'action_by_id', type: 'integer'),
        new OA\Property(property: 'ticket_id', type: 'integer'),
        new OA\Property(property: 'requested_by', type: 'Member', description: 'Expandable - Member who requested'),
        new OA\Property(property: 'action_by', type: 'Member', description: 'Expandable - Member who approved/rejected'),
        new OA\Property(property: 'ticket', type: 'SummitTicket', description: 'Expandable - Ticket'),
        new OA\Property(property: 'refunded_taxes', type: 'array', items: new OA\Items(type: 'object'), description: 'Expandable - List of tax refunds'),
    ]
)]
class SummitAttendeeTicketRefundRequestSchema
{
}
