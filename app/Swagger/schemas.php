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
class OwnerSchema {}

#[OA\Schema(
    schema: 'Ticket',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'number', type: 'string'),
        new OA\Property(property: 'owner', ref: '#/components/schemas/Owner'),
    ]
)]
class TicketSchema {}

#[OA\Schema(
    schema: 'Feature',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'description', type: 'string'),
    ]
)]
class FeatureSchema {}

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
class ValidateBadgeResponseSchema {}

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
class PaginateDataSchemaResponseSchema {}

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
class PaginatedRSVPInvitationsResponseSchema {}

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
class PaginatedCSVRSVPInvitationsResponseSchema {}

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
class RSVPInvitationSchema {}


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
        new OA\Property(property: 'event_id',  type: 'integer', example: 123),
    ]
)]
class RSVPInvitationCSVSchema {}

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
class SummitAttendeeSchema {}

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
class SummitEventSchema {}

#[OA\Schema(
    schema: 'SendRSVPInvitationsRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'email_flow_event', type: 'string', example: RSVPInviteEmail::EVENT_SLUG, enum:[RSVPInviteEmail::EVENT_SLUG, ReRSVPInviteEmail::EVENT_SLUG]),
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
class SendRSVPInvitationsRequestSchema {}


#[OA\Schema(
    schema: 'ReSendRSVPConfirmationRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'test_email_recipient', type: 'string', example: 'test@test.com'),
        new OA\Property(property: 'outcome_email_recipient', type: 'string', example: 'result@test.com'),
    ]
)]
class ReSendRSVPConfirmationRequestSchema {}


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
class BulkRSVPInvitationsRequestSchema{

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
class RSVPAuthSchema{}


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
class RSVPInvitationsAuthSchema{}

#[OA\Schema(
    schema: 'Member',
    type: 'object',
    required: ['id', 'created', 'last_edited', 'first_name', 'last_name', 'bio', 'gender', 'github_user', 'linked_in', 'irc', 'twitter', 'state', 'country', 'active', 'email_verified', 'pic', 'membership_type', 'candidate_profile_id', 'company', ],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', example: 1630500518),
        new OA\Property(property: 'last_edited', type: 'integer', example: 1630500518),
        new OA\Property(property: 'first_name', type: 'string', example: 'John'),
        new OA\Property(property: 'last_name', type: 'string', example: 'Doe'),
        new OA\Property(property: 'bio', type: 'string'),
        new OA\Property(property: 'gender', type: 'string'),
        new OA\Property(property: 'github_user', type: 'string'),
        new OA\Property(property: 'linked_in', type: 'string'),
        new OA\Property(property: 'irc', type: 'string'),
        new OA\Property(property: 'twitter', type: 'string'),
        new OA\Property(property: 'state', type: 'string'),
        new OA\Property(property: 'country', type: 'string'),
        new OA\Property(property: 'active', type: 'boolean', example: true),
        new OA\Property(property: 'email_verified', type: 'boolean', example: true),
        new OA\Property(property: 'pic', type: 'string'),
        new OA\Property(property: 'membership_type', type: 'string', example: 'Foundation'),
        new OA\Property(property: 'candidate_profile_id', type: 'integer', example: 1465),
        new OA\Property(property: 'company', type: 'string'),
        new OA\Property(property: 'speaker_id', type: 'integer', example: 187),
        new OA\Property(property: 'attendee_id', type: 'integer', example: 1287),
        new OA\Property(property: 'groups_events', type: 'array', items: new OA\Items(type: 'GroupEvent')),
        new OA\Property(property: 'groups', type: 'array', items: new OA\Items(type: 'integer')),
        new OA\Property(property: 'affiliations', type: 'array', items: new OA\Items(type: 'Organization')),
        new OA\Property(property: 'all_affiliations', type: 'array', items: new OA\Items(type: 'Affiliation')),
        new OA\Property(property: 'ccla_teams', type: 'array', items: new OA\Items(type: 'Team')),
        new OA\Property(property: 'election_applications', type: 'array', items: new OA\Items(type: 'Nomination')),
        new OA\Property(property: 'candidate_profile', type: 'array', items: new OA\Items(type: 'Candidate')),
        new OA\Property(property: 'election_nominations', type: 'array', items: new OA\Items(type: 'Nomination')),
        new OA\Property(property: 'team_memberships', type: 'array', items: new OA\Items(type: 'ChatTeamMember')),
        new OA\Property(property: 'sponsor_memberships', type: 'array', items: new OA\Items(type: 'Sponsor')),
        new OA\Property(property: 'favorite_summit_events', type: 'array', items: new OA\Items(type: 'integer')),
        new OA\Property(property: 'schedule_summit_events', type: 'array', items: new OA\Items(type: 'integer')),
        new OA\Property(property: 'summit_tickets', type: 'array', items: new OA\Items(type: 'SummitAttendeeTicket')),
        new OA\Property(property: 'schedule_shareable_link', type: 'array', items: new OA\Items(type: 'PersonalCalendarShareInfo')),
        new OA\Property(property: 'legal_agreements', type: 'array', items: new OA\Items(type: 'integer')),
        new OA\Property(property: 'track_chairs', type: 'array', items: new OA\Items(type: 'integer')),
        new OA\Property(property: 'summit_permission_groups', type: 'array', items: new OA\Items(type: 'integer')),
        new OA\Property(property: 'speaker', type: 'array', items: new OA\Items(type: 'PresentationSpeaker')),
        new OA\Property(property: 'attendee', type: 'array', items: new OA\Items(type: 'SummitAttendee')),
        new OA\Property(property: 'feedback', type: 'array', items: new OA\Items(type: 'SummitEventFeedback')),
        new OA\Property(property: 'rsvp', type: 'array', items: new OA\Items(type: 'RSVP')),
        new OA\Property(property: 'rsvp_invitations', type: 'array', items: new OA\Items(type: 'RSVPInvitation')),
        new OA\Property(property: 'email', type: 'string', example: 'user@example.com'),
        new OA\Property(property: 'second_email', type: 'string', example: 'user@example.com'),
        new OA\Property(property: 'third_email', type: 'string', example: 'user@example.com'),
        new OA\Property(property: 'user_external_id', type: 'integer'),
    ]
)]
class MemberSchema {}

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
class RSVPSchema {}

#[OA\Schema(
    schema: 'PaginatedMembersResponse',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/Member')
                )
            ]
        )
    ]
)]
class PaginatedMembersResponse {}

#[OA\Schema(
    schema: 'PaginatedMemberCompaniesResponse',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'company', type: 'string', example: 'Acme Corp')
                        ]
                    )
                )
            ]
        )
    ]
)]
class PaginatedMemberCompaniesResponse {}

#[OA\Schema(
    schema: 'PaginatedAffiliationsResponse',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/Affiliation')
                )
            ]
        )
    ]
)]
class PaginatedAffiliationsResponse {}

#[OA\Schema(
    schema: 'Affiliation',
    type: 'object',
    required: ['id', 'created', 'last_edited', 'is_current', 'owner_id'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', example: 1634567890),
        new OA\Property(property: 'last_edited', type: 'integer', example: 1634567890),
        new OA\Property(property: 'start_date', type: 'integer', example: 1634567890),
        new OA\Property(property: 'end_date', type: 'integer', example: 1634567890),
        new OA\Property(property: 'job_title', type: 'string', example: 'Software Engineer'),
        new OA\Property(property: 'is_current', type: 'boolean', example: true),
        new OA\Property(property: 'owner_id', type: 'integer', example: 1),
        new OA\Property(property: 'organization_id', type: 'integer', example: 1),
    ]
)]
class Affiliation {}

#[OA\Schema(
    schema: 'MemberUpdateRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'projects', type: 'array', items: new OA\Items(type: 'string')),
        new OA\Property(property: 'other_project', type: 'string', maxLength: 100),
        new OA\Property(property: 'display_on_site', type: 'boolean'),
        new OA\Property(property: 'subscribed_to_newsletter', type: 'boolean'),
        new OA\Property(property: 'shirt_size', type: 'string', enum: ['Small', 'Medium', 'Large', 'XL', 'XXL']),
        new OA\Property(property: 'food_preference', type: 'array', items: new OA\Items(type: 'string')),
        new OA\Property(property: 'other_food_preference', type: 'string', maxLength: 100),
    ]
)]
class MemberUpdateRequest {}

#[OA\Schema(
    schema: 'AffiliationRequest',
    type: 'object',
    required: ['is_current', 'start_date'],
    properties: [
        new OA\Property(property: 'is_current', type: 'boolean', example: true),
        new OA\Property(property: 'start_date', type: 'integer', example: 1634567890),
        new OA\Property(property: 'end_date', type: 'integer', example: 1634567890),
        new OA\Property(property: 'organization_id', type: 'integer', example: 1),
        new OA\Property(property: 'organization_name', type: 'string', maxLength: 255),
        new OA\Property(property: 'job_title', type: 'string', maxLength: 255),
    ]
)]
class AffiliationRequest {}

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
class PaginatedRSVPsResponseSchema {}

#[OA\Schema(
    schema: 'RSVPInvitationRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'invitee_ids',    type: 'array',
            items: new OA\Items(type: 'integer', example: 123),
            example: [1, 2, 3]
        ),
    ]
)]
class RSVPInvitationRequestSchema {}

#[OA\Schema(
    schema: 'RSVPUpdateRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'seat_type', type: 'string', example: RSVP::SeatTypeRegular,  enum: RSVP::ValidSeatTypes),
        new OA\Property(property: 'status', type: 'string', example: RSVP::Status_Active, enum: RSVP::AllowedStatus),
    ]
)]
class RSVPUpdateRequestSchema_{

}


#[OA\Schema(
    schema: 'RSVPAdminAddRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'attendee_id', type: 'integer', example: 123),
        new OA\Property(property: 'seet_type', type: 'string', example: RSVP::SeatTypeRegular),

    ]
)]
class RSVPAdminAddRequestSchema {}
