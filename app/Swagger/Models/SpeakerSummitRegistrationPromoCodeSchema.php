<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;


#[OA\Schema(
    schema: 'SpeakerSummitRegistrationPromoCode',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', example: 1, format: "time_epoch"),
        new OA\Property(property: 'last_edited', type: 'integer', example: 1, format: "time_epoch"),
        new OA\Property(property: 'code', type: 'string'),
        new OA\Property(property: 'redeemed', type: 'boolean'),
        new OA\Property(property: 'email_sent', type: 'boolean'),
        new OA\Property(property: 'source', type: 'string'),
        new OA\Property(property: 'summit_id', type: 'integer'),
        new OA\Property(property: 'creator_id', type: 'integer'),
        new OA\Property(property: 'quantity_available', type: 'integer'),
        new OA\Property(property: 'quantity_used', type: 'integer'),
        new OA\Property(property: 'quantity_remaining', type: 'integer'),
        new OA\Property(property: 'valid_since_date', type: 'integer', format: "time_epoch"),
        new OA\Property(property: 'valid_until_date', type: 'integer', format: "time_epoch"),
        new OA\Property(property: 'class_name', type: 'string'),
        new OA\Property(property: 'description', type: 'string'),
        new OA\Property(property: 'notes', type: 'string'),
        new OA\Property(property: 'allows_to_delegate', type: 'boolean'),
        new OA\Property(property: 'allows_to_reassign', type: 'boolean'),
        // @TODO: add relations and expand of SummitRegistrationPromoCodeSerializer
        new OA\Property(property: 'type', type: 'string'),
        new OA\Property(property: 'speaker_id', type: 'integer'),
        new OA\Property(property: 'owner_name', type: 'string', description: 'Full name of the speaker owner of the promo code, only when expanded'),
        new OA\Property(property: 'owner_email', type: 'string', description: 'Email of the speaker owner of the promo code, only when expanded'),
        new OA\Property(property: 'speaker', oneOf: [
            new OA\Schema(ref: '#/components/schemas/AdminPresentationSpeaker'),
            new OA\Schema(ref: '#/components/schemas/SummitPresentationSpeaker')
        ], description: 'Full Speaker object, only when expanded'),
    ])
]
class SpeakerSummitRegistrationPromoCodeSchema
{
}
