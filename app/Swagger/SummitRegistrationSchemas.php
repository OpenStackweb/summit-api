<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PaginatedSummitTaxTypesResponse',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/SummitTaxType')
                )
            ]
        )
    ]
)]
class PaginatedSummitTaxTypesResponseSchema
{
}

#[OA\Schema(
    schema: 'SummitTaxTypeCreateRequest',
    type: 'object',
    required: ['name', 'rate'],
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'VAT'),
        new OA\Property(property: 'tax_id', type: 'string', example: 'VAT-001'),
        new OA\Property(property: 'rate', type: 'number', format: 'float', example: 21.0, description: 'Rate must be greater than 0'),
    ]
)]
class SummitTaxTypeCreateRequestSchema
{
}

#[OA\Schema(
    schema: 'SummitTaxTypeUpdateRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'VAT'),
        new OA\Property(property: 'tax_id', type: 'string', example: 'VAT-001'),
        new OA\Property(property: 'rate', type: 'number', format: 'float', example: 21.0, description: 'Rate must be greater than 0'),
    ]
)]
class SummitTaxTypeUpdateRequestSchema
{
}

// Badge Types

#[OA\Schema(
    schema: 'PaginatedSummitAttendeesResponse',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/SummitAttendee')
                )
            ]
        )
    ]
)]
class PaginatedSummitAttendeesResponseSchema
{
}

#[OA\Schema(
    schema: 'AttendeeRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'shared_contact_info', type: 'boolean'),
        new OA\Property(property: 'summit_hall_checked_in', type: 'boolean'),
        new OA\Property(property: 'disclaimer_accepted', type: 'boolean'),
        new OA\Property(property: 'first_name', type: 'string', maxLength: 255),
        new OA\Property(property: 'surname', type: 'string', maxLength: 255),
        new OA\Property(property: 'company', type: 'string', maxLength: 255),
        new OA\Property(property: 'email', type: 'string', maxLength: 255),
        new OA\Property(property: 'member_id', type: 'integer'),
        new OA\Property(property: 'admin_notes', type: 'string', maxLength: 1024),
        new OA\Property(property: 'tags', type: 'array', items: new OA\Items(type: 'string')),
        new OA\Property(property: 'manager_id', type: 'integer'),
        new OA\Property(
            property: 'extra_questions',
            type: 'array',
            items: new OA\Items(
                type: 'object',
                properties: [
                    new OA\Property(property: 'question_id', type: 'integer'),
                    new OA\Property(property: 'answer', type: 'string')
                ]
            )
        ),
    ]
)]
class AttendeeRequestSchema
{
}

#[OA\Schema(
    schema: 'AddAttendeeTicketRequest',
    type: 'object',
    required: ['ticket_type_id'],
    properties: [
        new OA\Property(property: 'ticket_type_id', type: 'integer'),
        new OA\Property(property: 'promo_code', type: 'string'),
        new OA\Property(property: 'external_order_id', type: 'string'),
        new OA\Property(property: 'external_attendee_id', type: 'string'),
    ]
)]
class AddAttendeeTicketRequestSchema
{
}

#[OA\Schema(
    schema: 'ReassignAttendeeTicketRequest',
    type: 'object',
    required: ['attendee_email'],
    properties: [
        new OA\Property(property: 'attendee_first_name', type: 'string', maxLength: 255),
        new OA\Property(property: 'attendee_last_name', type: 'string', maxLength: 255),
        new OA\Property(property: 'attendee_email', type: 'string', maxLength: 255),
        new OA\Property(property: 'attendee_company', type: 'string', maxLength: 255),
        new OA\Property(
            property: 'extra_questions',
            type: 'array',
            items: new OA\Items(
                type: 'object',
                properties: [
                    new OA\Property(property: 'question_id', type: 'integer'),
                    new OA\Property(property: 'answer', type: 'string')
                ]
            )
        ),
    ]
)]
class ReassignAttendeeTicketRequestSchema
{
}

#[OA\Schema(
    schema: 'SendAttendeesEmailRequest',
    type: 'object',
    required: ['email_flow_event'],
    properties: [
        new OA\Property(
            property: 'email_flow_event',
            type: 'string',
            enum: ['SUMMIT_ATTENDEE_TICKET_REGENERATE_HASH', 'SUMMIT_ATTENDEE_INVITE_TICKET_EDITION', 'SUMMIT_ATTENDEE_ALL_TICKETS_EDITION', 'SUMMIT_ATTENDEE_REGISTRATION_INCOMPLETE_REMINDER', 'SUMMIT_ATTENDEE_GENERIC']
        ),
        new OA\Property(
            property: 'attendees_ids',
            type: 'array',
            items: new OA\Items(type: 'integer')
        ),
        new OA\Property(
            property: 'excluded_attendees_ids',
            type: 'array',
            items: new OA\Items(type: 'integer')
        ),
        new OA\Property(property: 'test_email_recipient', type: 'string', format: 'email'),
        new OA\Property(property: 'outcome_email_recipient', type: 'string', format: 'email'),
    ]
)]
class SendAttendeesEmailRequestSchema
{
}

// Summit Badge Types

#[OA\Schema(
    schema: "PaginatedSummitBadgeTypesResponse",
    description: "Paginated list of summit badge types",
    allOf: [
        new OA\Schema(ref: "#/components/schemas/PaginateDataSchemaResponse"),
        new OA\Schema(
            properties: [
                new OA\Property(
                    property: "data",
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/SummitBadgeType")
                )
            ]
        )
    ]
)]
class PaginatedSummitBadgeTypesResponseSchema
{
}

#[OA\Schema(
    schema: "SummitBadgeTypeCreateRequest",
    description: "Request to create a summit badge type",
    required: ["name", "description", "is_default"],
    type: "object",
    properties: [
        new OA\Property(property: "name", type: "string", example: "Attendee"),
        new OA\Property(property: "description", type: "string", example: "Standard attendee badge"),
        new OA\Property(property: "template_content", type: "string", nullable: true, example: "Badge template content"),
        new OA\Property(property: "is_default", type: "boolean", example: false),
    ]
)]
class SummitBadgeTypeCreateRequestSchema
{
}

#[OA\Schema(
    schema: "SummitBadgeTypeUpdateRequest",
    description: "Request to update a summit badge type",
    type: "object",
    properties: [
        new OA\Property(property: "name", type: "string", nullable: true, example: "Attendee"),
        new OA\Property(property: "description", type: "string", nullable: true, example: "Standard attendee badge"),
        new OA\Property(property: "template_content", type: "string", nullable: true, example: "Badge template content"),
        new OA\Property(property: "is_default", type: "boolean", nullable: true, example: false),
    ]
)]
class SummitBadgeTypeUpdateRequestSchema
{
}

// Summit Badge Feature Types

#[OA\Schema(
    schema: 'PaginatedSummitBadgeFeatureTypesResponse',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/SummitBadgeFeatureType')
                )
            ]
        )
    ]
)]
class PaginatedSummitBadgeFeatureTypesResponseSchema
{
}

#[OA\Schema(
    schema: 'SummitBadgeFeatureTypeCreateRequest',
    type: 'object',
    required: ['name'],
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'Speaker Ribbon'),
        new OA\Property(property: 'description', type: 'string', example: 'Special ribbon for speakers'),
        new OA\Property(property: 'template_content', type: 'string', example: '<div>{{name}}</div>'),
    ]
)]
class SummitBadgeFeatureTypeCreateRequestSchema
{
}

#[OA\Schema(
    schema: 'SummitBadgeFeatureTypeUpdateRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'VIP Ribbon'),
        new OA\Property(property: 'description', type: 'string', example: 'VIP attendee designation'),
        new OA\Property(property: 'template_content', type: 'string', example: '<div class="vip">{{name}}</div>'),
    ]
)]
class SummitBadgeFeatureTypeUpdateRequestSchema
{
}
