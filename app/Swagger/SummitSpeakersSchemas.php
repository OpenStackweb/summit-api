<?php

namespace App\Swagger\schemas;

use App\Jobs\Emails\PresentationSubmissions\Invitations\InviteSubmissionEmail;
use App\Jobs\Emails\PresentationSubmissions\Invitations\ReInviteSubmissionEmail;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'SummitSubmissionInvitation',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', description: 'Unix timestamp', example: 1640995200),
        new OA\Property(property: 'last_edited', type: 'integer', description: 'Unix timestamp', example: 1640995200),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'speaker@example.com'),
        new OA\Property(property: 'first_name', type: 'string', example: 'John'),
        new OA\Property(property: 'last_name', type: 'string', example: 'Doe'),
        new OA\Property(property: 'summit_id', type: 'integer', example: 1, description: 'Summit ID'),
        new OA\Property(property: 'is_sent', type: 'boolean', example: false),
        new OA\Property(property: 'sent_date', type: 'integer', description: 'Unix timestamp', example: 1640995200, nullable: true),
        new OA\Property(
            property: 'tags',
            type: 'array',
            items: new OA\Items(type: ['integer', 'string']),
            example: [1, 2, 3],
            description: 'Array of Tag IDs or names (when expanded) associated with the invitation',
        )
    ]
)]
class SummitSubmissionInvitationSchema {}

#[OA\Schema(
    schema: 'PaginatedSummitSubmissionInvitationsResponse',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/SummitSubmissionInvitation')
                )
            ]
        )
    ]
)]
class PaginatedSummitSubmissionInvitationsResponseSchema {}

#[OA\Schema(
    schema: 'SummitSubmissionInvitationCSV',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'speaker@example.com'),
        new OA\Property(property: 'first_name', type: 'string', example: 'John'),
        new OA\Property(property: 'last_name', type: 'string', example: 'Doe'),
        new OA\Property(property: 'speaker_id', type: 'integer', nullable: true, example: 123),
        new OA\Property(property: 'summit_id', type: 'integer', example: 1),
        new OA\Property(property: 'is_sent', type: 'boolean', example: false),
        new OA\Property(property: 'sent_date', type: 'integer', description: 'Unix timestamp', nullable: true, example: 1640995200),
        new OA\Property(property: 'tags', type: 'string', example: 'tag1,tag2,tag3')
    ]
)]
class SummitSubmissionInvitationCSVSchema {}

#[OA\Schema(
    schema: 'SummitSubmissionInvitationCreateRequest',
    type: 'object',
    required: ['email', 'first_name', 'last_name'],
    properties: [
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'speaker@example.com'),
        new OA\Property(property: 'first_name', type: 'string', example: 'John'),
        new OA\Property(property: 'last_name', type: 'string', example: 'Doe'),
        new OA\Property(
            property: 'tags',
            type: 'array',
            items: new OA\Items(type: 'string'),
            example: ['tag1', 'tag2'],
            nullable: true
        )
    ]
)]
class SummitSubmissionInvitationCreateRequestSchema {}

#[OA\Schema(
    schema: 'SummitSubmissionInvitationUpdateRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'speaker@example.com', nullable: true),
        new OA\Property(property: 'first_name', type: 'string', example: 'John', nullable: true),
        new OA\Property(property: 'last_name', type: 'string', example: 'Doe', nullable: true),
        new OA\Property(
            property: 'tags',
            type: 'array',
            items: new OA\Items(type: 'string'),
            example: ['tag1', 'tag2'],
            nullable: true
        )
    ]
)]
class SummitSubmissionInvitationUpdateRequestSchema {}

#[OA\Schema(
    schema: 'SendSummitSubmissionInvitationsRequest',
    type: 'object',
    required: ['email_flow_event'],
    properties: [
        new OA\Property(
            property: 'email_flow_event',
            type: 'string',
            enum: [InviteSubmissionEmail::EVENT_SLUG, ReInviteSubmissionEmail::EVENT_SLUG],
            example: InviteSubmissionEmail::EVENT_SLUG
        ),
        new OA\Property(property: 'selection_plan_id', type: 'integer', example: 1, nullable: true),
        new OA\Property(
            property: 'invitations_ids',
            type: 'array',
            items: new OA\Items(type: 'integer'),
            example: [1, 2, 3],
            nullable: true
        ),
        new OA\Property(
            property: 'excluded_invitations_ids',
            type: 'array',
            items: new OA\Items(type: 'integer'),
            example: [4, 5],
            nullable: true
        )
    ]
)]
class SendSummitSubmissionInvitationsRequestSchema {}

//

#[OA\Schema(
    schema: 'SpeakerActiveInvolvement',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', format: 'int64', example: 1633024800),
        new OA\Property(property: 'last_edited', type: 'integer', format: 'int64', example: 1633024800),
        new OA\Property(property: 'involvement', type: 'string', example: 'Active Contributor'),
        new OA\Property(property: 'is_default', type: 'boolean', example: true),
    ]
)]
class SpeakerActiveInvolvementSchema
{
}

#[OA\Schema(
    schema: 'SpeakerActiveInvolvementsResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'total', type: 'integer', example: 5),
        new OA\Property(property: 'per_page', type: 'integer', example: 5),
        new OA\Property(property: 'current_page', type: 'integer', example: 1),
        new OA\Property(property: 'last_page', type: 'integer', example: 1),
        new OA\Property(
            property: 'data',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/SpeakerActiveInvolvement')
        ),
    ]
)]
class SpeakerActiveInvolvementsResponseSchema
{
}

#[OA\Schema(
    schema: 'SpeakerOrganizationalRole',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', format: 'int64', example: 1633024800),
        new OA\Property(property: 'last_edited', type: 'integer', format: 'int64', example: 1633024800),
        new OA\Property(property: 'role', type: 'string', example: 'Developer'),
        new OA\Property(property: 'is_default', type: 'boolean', example: true),
    ]
)]
class SpeakerOrganizationalRoleSchema
{
}

#[OA\Schema(
    schema: 'SpeakerOrganizationalRolesResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'total', type: 'integer', example: 8),
        new OA\Property(property: 'per_page', type: 'integer', example: 8),
        new OA\Property(property: 'current_page', type: 'integer', example: 1),
        new OA\Property(property: 'last_page', type: 'integer', example: 1),
        new OA\Property(
            property: 'data',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/SpeakerOrganizationalRole')
        ),
    ]
)]
class SpeakerOrganizationalRolesResponseSchema
{
}