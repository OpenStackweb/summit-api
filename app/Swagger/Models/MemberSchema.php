<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;



#[OA\Schema(
    schema: 'Member',
    type: 'object',
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
        new OA\Property(property: 'groups_events', type: 'array', items: new OA\Items(type: 'object'), description: 'GroupEvent object, only available if ?expand=groups_events is provided'),
        new OA\Property(property: 'groups', type: 'array', items: new OA\Items(type: 'integer')),
        new OA\Property(property: 'affiliations', type: 'array', items: new OA\Items(ref: '#/components/schemas/Organization'), description: 'Organization object, only available if ?expand=affiliations is provided'),
        new OA\Property(property: 'all_affiliations', type: 'array', items: new OA\Items(ref: '#/components/schemas/Affiliation'), description: 'Affiliation object, only available if ?expand=all_affiliations is provided'),
        new OA\Property(property: 'ccla_teams', type: 'array', items: new OA\Items(type: 'object'), description: 'CCLA Team object, only available if ?expand=ccla_teams is provided'),
        new OA\Property(property: 'election_applications', type: 'array', items: new OA\Items(type: 'object'), description: 'Nomination object, only available if ?expand=election_applications is provided'),
        new OA\Property(property: 'election_nominations', type: 'array', items: new OA\Items(type: 'object'), description: 'Nomination object, only available if ?expand=election_nominations is provided'),
        new OA\Property(property: 'candidate_profile', type: 'array', items: new OA\Items(type: 'object'), description: 'Candidate object, only available if ?expand=candidate_profile is provided'),
        new OA\Property(property: 'team_memberships', type: 'array', items: new OA\Items(type: 'object'), description: 'ChatTeamMember object, only available if ?expand=team_memberships is provided'),
        new OA\Property(property: 'sponsor_memberships', type: 'array', items: new OA\Items(type: 'object'), description: 'Sponsor object, only available if ?expand=sponsor_memberships is provided'),
        new OA\Property(property: 'favorite_summit_events', type: 'array', items: new OA\Items(type: 'integer')),
        new OA\Property(property: 'schedule_summit_events', type: 'array', items: new OA\Items(type: 'integer')),
        new OA\Property(property: 'summit_tickets', type: 'array', items: new OA\Items(type: 'object'), description: 'SummitAttendeeTicket object, only available if ?expand=sponsor_memberships is provided'),
        new OA\Property(property: 'schedule_shareable_link', type: 'array', items: new OA\Items(type: 'object'), description: 'PersonalCalendarShareInfo object, only available if ?expand=schedule_shareable_link is provided'),
        new OA\Property(property: 'legal_agreements', type: 'array', items: new OA\Items(type: 'integer')),
        new OA\Property(property: 'track_chairs', type: 'array', items: new OA\Items(type: 'integer')),
        new OA\Property(property: 'summit_permission_groups', type: 'array', items: new OA\Items(type: 'integer')),
        new OA\Property(property: 'speaker', type: 'array', items: new OA\Items(type: 'object'), description: 'PresentationSpeaker object, only available if ?expand=speaker is provided'),
        new OA\Property(property: 'attendee', type: 'array', items: new OA\Items(ref: '#/components/schemas/SummitAttendee'), description: 'only available if ?expand=attendee is provided'),
        new OA\Property(property: 'feedback', type: 'array', items: new OA\Items(type: 'object'), description: 'SummitEventFeedback object, only available if ?expand=feedback is provided'),
        new OA\Property(property: 'rsvp', type: 'array', items: new OA\Items(ref: '#/components/schemas/RSVP'), description: 'only available if ?expand=rsvp is provided'),
        new OA\Property(property: 'rsvp_invitations', type: 'array', items: new OA\Items(ref: '#/components/schemas/RSVPInvitation'), description: 'only available if ?expand=rsvp_invitations is provided'),
        new OA\Property(property: 'email', type: 'string', example: 'user@example.com'),
        new OA\Property(property: 'second_email', type: 'string', example: 'user@example.com'),
        new OA\Property(property: 'third_email', type: 'string', example: 'user@example.com'),
        new OA\Property(property: 'user_external_id', type: 'integer'),
    ]
)]
class MemberSchema {}
