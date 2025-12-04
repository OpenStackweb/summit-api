<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;



#[OA\Schema(
    schema: 'SummitAttendee',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', example: 1),
        new OA\Property(property: 'last_edited', type: 'integer', example: 1),
        new OA\Property(property: 'summit_hall_checked_in', type: 'boolean'),
        new OA\Property(property: 'summit_hall_checked_in_date', type: 'integer', example: 1630500518),
        new OA\Property(property: 'summit_virtual_checked_in_date', type: 'integer', example: 1630500518),
        new OA\Property(property: 'shared_contact_info', type: 'boolean'),
        new OA\Property(property: 'member_id', type: 'integer'),
        new OA\Property(property: 'member', ref: '#/components/schemas/Member', description: 'Member full object when ?expand=member is used'),
        new OA\Property(property: 'summit_id', type: 'integer'),
        new OA\Property(property: 'speaker_id', type: 'integer', description: 'PresentationSpeaker ID, or full object when ?expand=speaker is used in the field speaker'),
        new OA\Property(property: 'first_name', type: 'string'),
        new OA\Property(property: 'last_name', type: 'string'),
        new OA\Property(property: 'email', type: 'string'),
        new OA\Property(property: 'company_id', type: 'integer', description: 'Company ID, or full object when ?expand=company is used in the field company'),
        new OA\Property(property: 'disclaimer_accepted_date', type: 'integer', example: 1630500518),
        new OA\Property(property: 'disclaimer_accepted', type: 'boolean'),
        new OA\Property(property: 'admin_notes', type: 'string'),
        new OA\Property(property: 'manager_id', type: 'integer', description: 'SummitAttendee ID, or full object when ?expand=manager is used in the field manager'),
        new OA\Property(property: 'tickets', type: 'array', items: new OA\Items(type: 'integer'), description: 'Array of SummitAttendeeTicket IDs, or full objects when ?expand=tickets is used'),
        new OA\Property(property: 'extra_questions', type: 'array', items: new OA\Items(type: 'integer'), description: 'Array of SummitOrderExtraQuestionAnswer IDs, or full objects when ?expand=extra_questions is used'),
        new OA\Property(property: 'presentation_votes', type: 'array', items: new OA\Items(type: 'integer'), description: 'Array of PresentationAttendeeVote IDs, or full objects when ?expand=presentation_votes is used'),
        new OA\Property(property: 'votes_count', type: 'integer'),
        new OA\Property(property: 'ticket_types', type: 'array', items: new OA\Items(
            type: 'object',
            properties: [
                new OA\Property(property: 'type_id', type: 'integer'),
                new OA\Property(property: 'qty', type: 'integer'),
                new OA\Property(property: 'type_name', type: 'string'),
                ]
            )),
        new OA\Property(property: 'allowed_access_levels', type: 'array', items: new OA\Items(type: 'integer')),
        new OA\Property(property: 'allowed_features', type: 'array', items: new OA\Items(type: 'integer'), description: 'Array of SummitBadgeFeatureType IDs, or full objects when ?expand=allowed_features is used'),
        new OA\Property(property: 'tags', type: 'array', items: new OA\Items(type: 'integer'), description: 'Array of Tag IDs, or full objects when ?expand=tags is used'),
    ]
)]
class SummitAttendeeSchema {}
