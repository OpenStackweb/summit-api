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

// Summit Registration Invitation Schemas

#[OA\Schema(
    schema: "SummitRegistrationInvitation",
    type: "object",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "created", type: "integer", description: "Unix timestamp", example: 1640995200),
        new OA\Property(property: "last_edited", type: "integer", description: "Unix timestamp", example: 1640995200),
        new OA\Property(property: "email", type: "string"),
        new OA\Property(property: "first_name", type: "string"),
        new OA\Property(property: "last_name", type: "string"),
        new OA\Property(property: "summit_id", type: "integer"),
        new OA\Property(property: "is_accepted", type: "boolean"),
        new OA\Property(property: "is_sent", type: "boolean"),
        new OA\Property(property: "action_date", type: "integer", nullable: true),
        new OA\Property(property: "acceptance_criteria", type: "string", enum: ["ANY_TICKET_TYPE", "ALL_TICKET_TYPES"]),
        new OA\Property(property: "status", type: "string", enum: ["Pending", "Accepted", "Rejected"]),
        new OA\Property(property: "allowed_ticket_types", type: "array", items: new OA\Items(type: "integer"), description: "Array of SummitTicketType IDs, full object when expanded", nullable: true),
        new OA\Property(property: "tags", type: "array", items: new OA\Items(type: ["integer", "string"]), description: "Array of Tag IDs or names when expanded", nullable: true),
    ]
)]
class SummitRegistrationInvitationSchema
{
}

#[OA\Schema(
    schema: "PaginatedSummitRegistrationInvitationsResponse",
    allOf: [
        new OA\Schema(ref: "#/components/schemas/PaginateDataSchemaResponse"),
        new OA\Schema(
            properties: [
                new OA\Property(
                    property: "data",
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/SummitRegistrationInvitation")
                )
            ]
        )
    ]
)]
class PaginatedSummitRegistrationInvitationsResponseSchema
{
}

#[OA\Schema(
    schema: "SummitRegistrationInvitationCreateRequest",
    type: "object",
    required: ["email", "first_name", "last_name", "acceptance_criteria"],
    properties: [
        new OA\Property(property: "email", type: "string", format: "email", maxLength: 255),
        new OA\Property(property: "first_name", type: "string", maxLength: 255),
        new OA\Property(property: "last_name", type: "string", maxLength: 255),
        new OA\Property(property: "allowed_ticket_types", type: "array", items: new OA\Items(type: "integer")),
        new OA\Property(property: "tags", type: "array", items: new OA\Items(type: "string")),
        new OA\Property(property: "acceptance_criteria", type: "string", enum: ["ANY_TICKET_TYPE", "ALL_TICKET_TYPES"]),
        new OA\Property(property: "status", type: "string", enum: ["Pending", "Accepted", "Rejected"])
    ]
)]
class SummitRegistrationInvitationCreateRequestSchema
{
}

#[OA\Schema(
    schema: "SummitRegistrationInvitationUpdateRequest",
    type: "object",
    properties: [
        new OA\Property(property: "email", type: "string", format: "email", maxLength: 255),
        new OA\Property(property: "first_name", type: "string", maxLength: 255),
        new OA\Property(property: "last_name", type: "string", maxLength: 255),
        new OA\Property(property: "allowed_ticket_types", type: "array", items: new OA\Items(type: "integer")),
        new OA\Property(property: "tags", type: "array", items: new OA\Items(type: "string")),
        new OA\Property(property: "is_accepted", type: "boolean"),
        new OA\Property(property: "acceptance_criteria", type: "string", enum: ["ANY_TICKET_TYPE", "ALL_TICKET_TYPES"]),
        new OA\Property(property: "status", type: "string", enum: ["Pending", "Accepted", "Rejected"])
    ]
)]
class SummitRegistrationInvitationUpdateRequestSchema
{
}

#[OA\Schema(
    schema: "SummitRegistrationInvitationCSVImportRequest",
    type: "object",
    required: ["file"],
    properties: [
        new OA\Property(property: "file", type: "string", format: "binary"),
        new OA\Property(property: "acceptance_criteria", type: "string", enum: ["ANY_TICKET_TYPE", "ALL_TICKET_TYPES"])
    ]
)]
class SummitRegistrationInvitationCSVImportRequestSchema
{
}

#[OA\Schema(
    schema: "SendRegistrationInvitationsRequest",
    type: "object",
    required: ["email_flow_event"],
    properties: [
        new OA\Property(property: "email_flow_event", type: "string", enum: ["SUMMIT_REGISTRATION_INVITE", "SUMMIT_REGISTRATION_REINVITE"]),
        new OA\Property(property: "invitations_ids", type: "array", items: new OA\Items(type: "integer")),
        new OA\Property(property: "excluded_invitations_ids", type: "array", items: new OA\Items(type: "integer")),
        new OA\Property(property: "test_email_recipient", type: "string", format: "email"),
        new OA\Property(property: "outcome_email_recipient", type: "string", format: "email")
    ]
)]
class SendRegistrationInvitationsRequestSchema
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
