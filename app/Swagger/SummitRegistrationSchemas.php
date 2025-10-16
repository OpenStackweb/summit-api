<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

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
        new OA\Property(property: "allowed_ticket_types", type: "array", items: new OA\Items(type: ["integer", "SummitTicketType"]), nullable: true),
        new OA\Property(property: "tags", type: "array", items: new OA\Items(type: ["integer", "Tag"]), nullable: true)
    ]
)]
class SummitRegistrationInvitation
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
class PaginatedSummitRegistrationInvitationsResponse
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
class SummitRegistrationInvitationCreateRequest
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
class SummitRegistrationInvitationUpdateRequest
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
class SummitRegistrationInvitationCSVImportRequest
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
class SendRegistrationInvitationsRequest
{
}

