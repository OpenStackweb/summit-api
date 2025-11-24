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
