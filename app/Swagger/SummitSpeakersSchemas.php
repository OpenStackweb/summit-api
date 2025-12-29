<?php

namespace App\Swagger\schemas;

use App\Jobs\Emails\PresentationSubmissions\Invitations\InviteSubmissionEmail;
use App\Jobs\Emails\PresentationSubmissions\Invitations\ReInviteSubmissionEmail;
use OpenApi\Attributes as OA;

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

#[OA\Schema(
    schema: 'SummitPresentationSpeakerRequestBody',
    type: 'object',
    properties: [
        new OA\Property(property: 'title', type: 'string', maxLength: 100),
        new OA\Property(property: 'first_name', type: 'string', maxLength: 100),
        new OA\Property(property: 'last_name', type: 'string', maxLength: 100),
        new OA\Property(property: 'bio', type: 'string'),
        new OA\Property(property: 'twitter', type: 'string', maxLength: 50),
        new OA\Property(property: 'irc', type: 'string', maxLength: 50),
        new OA\Property(property: 'member_id', type: 'integer'),
        new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 50),
        new OA\Property(property: 'on_site_phone', type: 'string', maxLength: 50),
        new OA\Property(property: 'registered', type: 'boolean'),
        new OA\Property(property: 'is_confirmed', type: 'boolean'),
        new OA\Property(property: 'checked_in', type: 'boolean'),
        new OA\Property(property: 'registration_code', type: 'string'),
        new OA\Property(property: 'available_for_bureau', type: 'boolean'),
        new OA\Property(property: 'funded_travel', type: 'boolean'),
        new OA\Property(property: 'willing_to_travel', type: 'boolean'),
        new OA\Property(property: 'willing_to_present_video', type: 'boolean'),
        new OA\Property(property: 'org_has_cloud', type: 'boolean'),
        new OA\Property(property: 'country', type: 'string'),
        new OA\Property(property: 'languages', type: 'array', items: new OA\Items(type: 'integer')),
        new OA\Property(property: 'areas_of_expertise', type: 'array', items: new OA\Items(type: 'string')),
        new OA\Property(property: 'travel_preferences', type: 'array', items: new OA\Items(type: 'string')),
        new OA\Property(property: 'organizational_roles', type: 'array', items: new OA\Items(type: 'integer')),
        new OA\Property(property: 'active_involvements', type: 'array', items: new OA\Items(type: 'integer')),
    ],
)]

class SummitPresentationSpeakerRequestBodySchema
{
}

#[OA\Schema(
    schema: 'SummitPresentationSpeakerUpdateCreateRequestBody',
    type: 'object',
    properties: [
        new OA\Property(property: 'title', type: 'string', maxLength: 100),
        new OA\Property(property: 'first_name', type: 'string', maxLength: 100),
        new OA\Property(property: 'last_name', type: 'string', maxLength: 100),
        new OA\Property(property: 'bio', type: 'string'),
        new OA\Property(property: 'notes', type: 'string'),
        new OA\Property(property: 'twitter', type: 'string', maxLength: 50),
        new OA\Property(property: 'irc', type: 'string', maxLength: 50),
        new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 50),
        new OA\Property(property: 'available_for_bureau', type: 'boolean'),
        new OA\Property(property: 'funded_travel', type: 'boolean'),
        new OA\Property(property: 'willing_to_travel', type: 'boolean'),
        new OA\Property(property: 'willing_to_present_video', type: 'boolean'),
        new OA\Property(property: 'org_has_cloud', type: 'boolean'),
        new OA\Property(property: 'country', type: 'string'),
        new OA\Property(property: 'languages', type: 'array', items: new OA\Items(type: 'integer')),
        new OA\Property(property: 'areas_of_expertise', type: 'array', items: new OA\Items(type: 'string')),
        new OA\Property(property: 'travel_preferences', type: 'array', items: new OA\Items(type: 'string')),
        new OA\Property(property: 'organizational_roles', type: 'array', items: new OA\Items(type: 'integer')),
        new OA\Property(property: 'active_involvements', type: 'array', items: new OA\Items(type: 'integer')),
        new OA\Property(property: 'company', type: 'string', maxLength: 255),
        new OA\Property(property: 'phone_number', type: 'string', maxLength: 255),
    ],
)]

class SummitPresentationSpeakerUpdateCreateRequestBodySchema
{
}
