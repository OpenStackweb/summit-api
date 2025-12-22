<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;


#[OA\Schema(
    schema: 'PresentationSpeakerSummitAssistanceConfirmationRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', example: 1, format: "time_epoch"),
        new OA\Property(property: 'last_edited', type: 'integer', example: 1, format: "time_epoch"),
        new OA\Property(property: 'on_site_phone', type: 'string'),
        new OA\Property(property: 'registered', type: 'boolean'),
        new OA\Property(property: 'is_confirmed', type: 'boolean'),
        new OA\Property(property: 'checked_in', type: 'boolean'),
        new OA\Property(property: 'summit_id', type: 'integer'),
        new OA\Property(property: 'speaker_email', type: 'string'),
        new OA\Property(property: 'speaker_full_name', type: 'string'),
        new OA\Property(property: 'speaker_id', type: 'integer'),
        new OA\Property(property: 'confirmation_date', type: 'integer', format: 'time_epoch'),
        new OA\Property(property: 'speaker', oneOf: [
            new OA\Schema(ref: '#/components/schemas/AdminPresentationSpeaker'),
            new OA\Schema(ref: '#/components/schemas/SummitPresentationSpeaker')
        ]),
    ])
]
class PresentationSpeakerSummitAssistanceConfirmationRequestSchema
{
}
