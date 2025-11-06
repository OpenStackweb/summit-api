<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'SummitAttendee',
    type: 'object',
    properties: [
        new OA\Property(property: 'summit_hall_checked_in', type: 'boolean'),
        new OA\Property(property: 'summit_hall_checked_in_date', type: 'integer', example: 1630500518),
        new OA\Property(property: 'summit_virtual_checked_in_date', type: 'integer', example: 1630500518),
        new OA\Property(property: 'shared_contact_info', type: 'boolean'),
        new OA\Property(property: 'member_id', type: 'integer'),
        new OA\Property(property: 'summit_id', type: 'integer'),
        new OA\Property(property: 'speaker_id', type: 'integer'),
        new OA\Property(property: 'first_name', type: 'string'),
        new OA\Property(property: 'last_name', type: 'string'),
        new OA\Property(property: 'email', type: 'string'),
        new OA\Property(property: 'company_id', type: 'integer'),
        new OA\Property(property: 'disclaimer_accepted_date', type: 'integer', example: 1630500518),
        new OA\Property(property: 'disclaimer_accepted', type: 'boolean'),
        new OA\Property(property: 'admin_notes', type: 'string'),
        new OA\Property(property: 'manager_id', type: 'integer'),
        new OA\Property(property: 'tickets', type: 'array', items: new OA\Items(type: ['integer', 'SummitAttendeeTicket'])),
        new OA\Property(property: 'extra_questions', type: 'array', items: new OA\Items(type: ['integer', 'SummitOrderExtraQuestionAnswer'])),
        new OA\Property(property: 'presentation_votes', type: 'array', items: new OA\Items(type: ['integer', 'PresentationAttendeeVote'])),
        new OA\Property(property: 'votes_count', type: 'integer'),
        new OA\Property(property: 'ticket_types', type: 'object', items: new OA\Items(new OA\Items(
            type: 'object',
            properties: [
                new OA\Property(property: 'type_id', type: 'integer'),
                new OA\Property(property: 'qty', type: 'integer'),
                new OA\Property(property: 'type_name', type: 'string'),
                ]
            ))),
        new OA\Property(property: 'allowed_access_levels', type: 'array', items: new OA\Items(type: 'integer')),
        new OA\Property(property: 'allowed_features', type: 'array', items: new OA\Items(type: ['integer', 'SummitBadgeFeatureType'])),
        new OA\Property(property: 'speaker', type: 'PresentationSpeaker'),
        new OA\Property(property: 'member', type: 'Member'),
        new OA\Property(property: 'tags', type: 'array', items: new OA\Items(type: ['Tag', 'integer'])),
        new OA\Property(property: 'company', type: 'Company'),
        new OA\Property(property: 'manager', type: 'SummitAttendee'),
    ]
)]
class SummitAttendee {}

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
class PaginatedSummitAttendeesResponse {}

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
class AttendeeRequest {}

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
class AddAttendeeTicketRequest {}

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
class ReassignAttendeeTicketRequest {}

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
class SendAttendeesEmailRequest {}
