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
class PaginatedSummitAttendeeTicketsResponse
{
}

#[OA\Schema(
    schema: 'RefundTicketRequest',
    type: 'object',
    required: ['amount'],
    properties: [
        new OA\Property(property: 'amount', type: 'number', format: 'float', description: 'Amount to refund'),
        new OA\Property(property: 'notes', type: 'string', maxLength: 255, description: 'Refund notes'),
    ]
)]
class RefundTicketRequest
{
}

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
class CreateBadgeRequest
{
}

#[OA\Schema(
    schema: 'PrintBadgeRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'check_in', type: 'boolean'),
    ]
)]
class PrintBadgeRequest
{
}

#[OA\Schema(
    schema: 'IngestExternalTicketDataRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'email_to', type: 'string', format: 'email'),
    ]
)]
class IngestExternalTicketDataRequest
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
