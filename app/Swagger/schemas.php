<?php

namespace App\Swagger\schemas;

use App\Jobs\Emails\Schedule\RSVP\ReRSVPInviteEmail;
use App\Jobs\Emails\Schedule\RSVP\RSVPInviteEmail;
use App\Models\Foundation\Summit\Events\RSVP\RSVPInvitation;
use App\Security\RSVPInvitationsScopes;
use App\Security\SummitScopes;
use models\summit\RSVP;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Owner',
    type: 'object',
    properties: [
        new OA\Property(property: 'first_name', type: 'string'),
        new OA\Property(property: 'last_name', type: 'string'),
    ]
)]
class OwnerSchema
{
}

#[OA\Schema(
    schema: 'Ticket',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'number', type: 'string'),
        new OA\Property(property: 'owner', ref: '#/components/schemas/Owner'),
    ]
)]
class TicketSchema
{
}

#[OA\Schema(
    schema: 'Feature',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'description', type: 'string'),
    ]
)]
class FeatureSchema
{
}

#[OA\Schema(
    schema: 'ValidateBadgeResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(
            property: 'features',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/Feature')
        ),
        new OA\Property(property: 'ticket', ref: '#/components/schemas/Ticket'),
    ]
)]
class ValidateBadgeResponseSchema
{
}

#[OA\Schema(
    schema: 'PaginateDataSchemaResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'total', type: 'integer', example: 6),
        new OA\Property(property: 'per_page', type: 'integer', example: 5),
        new OA\Property(property: 'current_page', type: 'integer', example: 1),
        new OA\Property(property: 'last_page', type: 'integer', example: 2),
    ],
    description: 'Base pagination metadata'
)]
class PaginateDataSchemaResponseSchema
{
}

#[OA\Schema(
    schema: 'PaginatedRSVPInvitationsResponse',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/RSVPInvitation')
                )
            ]
        )
    ]
)]
class PaginatedRSVPInvitationsResponseSchema
{
}

#[OA\Schema(
    schema: 'PaginatedCSVRSVPInvitationsResponse',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/RSVPInvitationCSV')
                )
            ]
        )
    ]
)]
class PaginatedCSVRSVPInvitationsResponseSchema
{
}

#[OA\Schema(
    schema: 'RSVPInvitation',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', example: 1630500518),
        new OA\Property(property: 'last_edited', type: 'integer', example: 1630500518),
        new OA\Property(property: 'status', type: 'string', example: RSVPInvitation::Status_Pending, enum: RSVPInvitation::AllowedStatus),
        new OA\Property(property: 'is_accepted', type: 'boolean', example: false),
        new OA\Property(property: 'is_sent', type: 'boolean', example: false),
        new OA\Property(property: 'invitee', ref: '#/components/schemas/SummitAttendee'),
        new OA\Property(property: 'event', ref: '#/components/schemas/SummitEvent'),
    ]
)]
class RSVPInvitationSchema
{
}

#[OA\Schema(
    schema: 'RSVPInvitationCSV',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', example: 1630500518),
        new OA\Property(property: 'last_edited', type: 'integer', example: 1630500518),
        new OA\Property(property: 'status', type: 'string', example: RSVPInvitation::Status_Pending, enum: RSVPInvitation::AllowedStatus),
        new OA\Property(property: 'is_accepted', type: 'boolean', example: false),
        new OA\Property(property: 'is_sent', type: 'boolean', example: false),
        new OA\Property(property: 'invitee_id', type: 'integer', example: 123),
        new OA\Property(property: 'event_id', type: 'integer', example: 123),
    ]
)]
class RSVPInvitationCSVSchema
{
}

#[OA\Schema(
    schema: 'SummitAttendee',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'created', type: 'integer', example: 1630500518),
        new OA\Property(property: 'last_edited', type: 'integer', example: 1630500518),
        new OA\Property(property: 'first_name', type: 'string', example: 'John'),
        new OA\Property(property: 'last_name', type: 'string', example: 'Doe'),
        new OA\Property(property: 'status', type: 'string', example: 'Complete'),
    ]
)]
class SummitAttendeeSchema
{
}

#[OA\Schema(
    schema: 'SummitEvent',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'created', type: 'integer', example: 1630500518),
        new OA\Property(property: 'last_edited', type: 'integer', example: 1630500518),
        new OA\Property(property: 'title', type: 'string', example: 'This is a title'),
        new OA\Property(property: 'description', type: 'string', example: 'This is a Description'),
    ]
)]
class SummitEventSchema
{
}

#[OA\Schema(
    schema: 'SendRSVPInvitationsRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'email_flow_event', type: 'string', example: RSVPInviteEmail::EVENT_SLUG, enum: [RSVPInviteEmail::EVENT_SLUG, ReRSVPInviteEmail::EVENT_SLUG]),
        new OA\Property(
            property: 'invitations_ids',
            type: 'array',
            items: new OA\Items(type: 'integer', example: 123),
            example: [1, 2, 3]
        ),
        new OA\Property(
            property: 'excluded_invitations_ids',
            type: 'array',
            items: new OA\Items(type: 'integer', example: 456),
            example: [4, 5]
        ),
        new OA\Property(property: 'test_email_recipient', type: 'string', example: 'test@test.com'),
        new OA\Property(property: 'outcome_email_recipient', type: 'string', example: 'result@test.com'),
    ]
)]
class SendRSVPInvitationsRequestSchema
{
}


#[OA\Schema(
    schema: 'ReSendRSVPConfirmationRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'test_email_recipient', type: 'string', example: 'test@test.com'),
        new OA\Property(property: 'outcome_email_recipient', type: 'string', example: 'result@test.com'),
    ]
)]
class ReSendRSVPConfirmationRequestSchema
{
}


#[OA\Schema(
    schema: 'BulkRSVPInvitationsRequest',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'attendees_ids',
            type: 'array',
            items: new OA\Items(type: 'integer', example: 123),
            example: [1, 2, 3]
        ),
        new OA\Property(
            property: 'excluded_attendees_ids',
            type: 'array',
            items: new OA\Items(type: 'integer', example: 456),
            example: [4, 5]
        ),
    ]
)]
class BulkRSVPInvitationsRequestSchema
{

}


#[
    OA\SecurityScheme(
    type: 'oauth2',
    securityScheme: 'summit_rsvp_oauth2',
    flows: [
        new OA\Flow(
            authorizationUrl: L5_SWAGGER_CONST_AUTH_URL,
            tokenUrl: L5_SWAGGER_CONST_TOKEN_URL,
            flow: 'authorizationCode',
            scopes: [
                SummitScopes::AddMyRSVP => 'RSVP',
                SummitScopes::DeleteMyRSVP => 'UnRSVP',
                SummitScopes::ReadAllSummitData => 'Read All Summit Data',
                SummitScopes::ReadSummitData => 'Read Summit Data',
                SummitScopes::WriteSummitData => 'Write Summit Data',
            ],
        ),
    ],
)
]
class RSVPAuthSchema
{
}


#[
    OA\SecurityScheme(
    type: 'oauth2',
    securityScheme: 'summit_rsvp_invitations_oauth2',
    flows: [
        new OA\Flow(
            authorizationUrl: L5_SWAGGER_CONST_AUTH_URL,
            tokenUrl: L5_SWAGGER_CONST_TOKEN_URL,
            flow: 'authorizationCode',
            scopes: [
                RSVPInvitationsScopes::Read => 'Read RSVP Invitations Data',
                RSVPInvitationsScopes::Write => 'Write RSVP Invitations Data',
                RSVPInvitationsScopes::Send => 'Send RSVP Invitations',
                SummitScopes::ReadAllSummitData => 'Read All Summit Data',
                SummitScopes::WriteSummitData => 'Write Summit Data',
            ],
        ),
    ],
)
]
class RSVPInvitationsAuthSchema
{
}

#[OA\Schema(
    schema: 'Member',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'created', type: 'integer', example: 1630500518),
        new OA\Property(property: 'last_edited', type: 'integer', example: 1630500518),
        new OA\Property(property: 'first_name', type: 'string', example: 'John'),
        new OA\Property(property: 'last_name', type: 'string', example: 'Doe'),
    ]
)]
class MemberSchema
{
}

#[OA\Schema(
    schema: 'RSVP',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'created', type: 'integer', example: 1630500518),
        new OA\Property(property: 'last_edited', type: 'integer', example: 1630500518),
        new OA\Property(property: 'seat_type', type: 'string', example: RSVP::SeatTypeRegular, enum: RSVP::ValidSeatTypes),
        new OA\Property(property: 'status', type: 'string', example: RSVP::Status_Active, enum: RSVP::AllowedStatus),
        new OA\Property(property: 'action_source', type: 'string', example: RSVP::ActionSource_Schedule, enum: RSVP::Valid_ActionSources),
        new OA\Property(property: 'owner', ref: '#/components/schemas/Member'),
        new OA\Property(property: 'event', ref: '#/components/schemas/SummitEvent'),
    ]
)]
class RSVPSchema
{
}

#[OA\Schema(
    schema: 'PaginatedRSVPsResponse',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/RSVP')
                )
            ]
        )
    ]
)]
class PaginatedRSVPsResponseSchema
{
}

#[OA\Schema(
    schema: 'RSVPInvitationRequest',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'invitee_ids',
            type: 'array',
            items: new OA\Items(type: 'integer', example: 123),
            example: [1, 2, 3]
        ),
    ]
)]
class RSVPInvitationRequestSchema
{
}

#[OA\Schema(
    schema: 'RSVPUpdateRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'seat_type', type: 'string', example: RSVP::SeatTypeRegular, enum: RSVP::ValidSeatTypes),
        new OA\Property(property: 'status', type: 'string', example: RSVP::Status_Active, enum: RSVP::AllowedStatus),
    ]
)]
class RSVPUpdateRequestSchema_
{

}

#[OA\Schema(
    schema: 'RSVPAdminAddRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'attendee_id', type: 'integer', example: 123),
        new OA\Property(property: 'seat_type', type: 'string', example: RSVP::SeatTypeRegular),

    ]
)]
class RSVPAdminAddRequestSchema
{
}

// Legal Documents

#[OA\Schema(
    schema: 'LegalDocument',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'title', type: 'string', example: 'Privacy Policy'),
        new OA\Property(property: 'slug', type: 'string', example: 'privacy-policy'),
        new OA\Property(property: 'content', type: 'string', example: 'This privacy policy describes how we handle your data...'),
    ]
)]
class LegalDocumentSchema
{
}

#[OA\Schema(
    schema: 'ChunkedFileUploadProgressResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'done', type: 'number', format: 'float', example: 45.5, description: 'Upload progress percentage (0-100)'),
    ]
)]
class ChunkedFileUploadProgressResponseSchema
{
}

#[OA\Schema(
    schema: 'ChunkedFileUploadCompleteResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'path', type: 'string', example: 'upload/image-jpeg/2025-09-30/', description: 'Directory path where the file was saved'),
        new OA\Property(property: 'name', type: 'string', example: 'myfile_abc123def456.jpg', description: 'Generated filename with timestamp hash'),
        new OA\Property(property: 'mime_type', type: 'string', example: 'image-jpeg', description: 'MIME type of the uploaded file (slashes replaced with hyphens)'),
    ]
)]
class ChunkedFileUploadCompleteResponseSchema
{
}

#[OA\Schema(
    schema: 'ChunkedFileUploadRequest',
    type: 'object',
    required: ['file'],
    properties: [
        new OA\Property(
            property: 'file',
            type: 'string',
            format: 'binary',
            description: 'File to upload (can be a chunk of a larger file)'
        ),
        new OA\Property(
            property: 'resumableChunkNumber',
            type: 'integer',
            description: 'Current chunk number (for resumable.js library)',
            example: 1
        ),
        new OA\Property(
            property: 'resumableTotalChunks',
            type: 'integer',
            description: 'Total number of chunks (for resumable.js library)',
            example: 5
        ),
        new OA\Property(
            property: 'resumableIdentifier',
            type: 'string',
            description: 'Unique identifier for the file upload session (for resumable.js library)',
            example: '12345-myfile-jpg'
        ),
    ]
)]
class ChunkedFileUploadRequestSchema
{
}

#[OA\Schema(
    schema: 'PaginatedOrganizationsResponse',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/Organization')
                )
            ]
        )
    ]
)]
class PaginatedOrganizationsResponseSchema
{
}

#[OA\Schema(
    schema: 'OrganizationCreateRequest',
    required: ['name'],
    type: 'object',
    properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'OpenStack Foundation'),
    ]
)]
class OrganizationCreateRequestSchema
{
}
// User Stories


#[OA\Schema(
    schema: 'PaginatedUserStoriesResponse',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/UserStory')
                )
            ]
        )
    ]
)]
class PaginatedUserStoriesResponseSchema {}
