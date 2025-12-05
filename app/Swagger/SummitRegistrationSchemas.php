<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

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
                    items: new OA\Items(type: 'SummitAttendeeTicket')
                )
            ]
        )
    ]
)]
class PaginatedSummitAttendeeTicketsResponse {}

#[OA\Schema(
    schema: 'RefundTicketRequest',
    type: 'object',
    required: ['amount'],
    properties: [
        new OA\Property(property: 'amount', type: 'number', format: 'float', description: 'Amount to refund'),
        new OA\Property(property: 'notes', type: 'string', maxLength: 255, description: 'Refund notes'),
    ]
)]
class RefundTicketRequest {}

#[OA\Schema(
    schema: 'CreateBadgeRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'badge_type_id', type: 'integer'),
        new OA\Property(
            property: 'features',
            type: 'array',
            items: new OA\Items(type: 'integer')
        ),
    ]
)]
class CreateBadgeRequest {}

#[OA\Schema(
    schema: 'PrintBadgeRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'check_in', type: 'boolean'),
    ]
)]
class PrintBadgeRequest {}

#[OA\Schema(
    schema: 'IngestExternalTicketDataRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'email_to', type: 'string', format: 'email'),
    ]
)]
class IngestExternalTicketDataRequest {}

// Summit Badge Feature Types

#[OA\Schema(
    schema: 'SummitBadgeFeatureType',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Speaker Ribbon'),
        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Special ribbon indicating speaker status'),
        new OA\Property(property: 'template_content', type: 'string', nullable: true, example: '<div class="speaker-badge">{{name}}</div>'),
        new OA\Property(property: 'summit_id', type: 'integer', example: 42),
        new OA\Property(property: 'image', type: 'string', nullable: true, example: 'https://example.com/images/speaker-ribbon.png'),
    ]
)]
class SummitBadgeFeatureTypeSchema {}

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
class PaginatedSummitBadgeFeatureTypesResponseSchema {}

#[OA\Schema(
    schema: 'SummitBadgeFeatureTypeCreateRequest',
    type: 'object',
    required: ['name'],
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'Speaker Ribbon'),
        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Special ribbon for speakers'),
        new OA\Property(property: 'template_content', type: 'string', nullable: true, example: '<div>{{name}}</div>'),
    ]
)]
class SummitBadgeFeatureTypeCreateRequestSchema {}

#[OA\Schema(
    schema: 'SummitBadgeFeatureTypeUpdateRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'VIP Ribbon'),
        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'VIP attendee designation'),
        new OA\Property(property: 'template_content', type: 'string', nullable: true, example: '<div class="vip">{{name}}</div>'),
    ]
)]
class SummitBadgeFeatureTypeUpdateRequestSchema {}
