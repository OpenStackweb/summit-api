<?php

namespace App\Swagger\Summit;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'AdminPresentationSpeaker',
    type: 'object',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/SummitPresentationSpeaker'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(property: 'email', type: 'string', example: 'john@example.com'),
                new OA\Property(property: 'summit_assistance', ref: '#/components/schemas/PresentationSpeakerSummitAssistanceConfirmationRequest', description: 'PresentationSpeakerSummitAssistanceConfirmationRequest full object when included in relations'),
                new OA\Property(property: 'registration_code', ref: '#/components/schemas/SpeakerSummitRegistrationPromoCode', description: 'SpeakerSummitRegistrationPromoCode full object when included in relations'),
                new OA\Property(property: 'all_presentations', type: 'array', items: new OA\Items(type: 'integer'), description: 'Presentations IDs when included in relations, full object when included in ?expand=presentations and relations'),
                new OA\Property(property: 'all_moderated_presentations', type: 'array', items: new OA\Items(type: 'integer'), description: 'Moderated Presentations IDs when included in relations, full object when included in ?expand=presentations and relations'),
                new OA\Property(property: 'summit_assistances', type: 'array', items: new OA\Items(type: 'object'), description: 'PresentationSpeakerSummitAssistanceConfirmationRequest full objects when included in relations'),
                new OA\Property(property: 'registration_codes', type: 'array', items: new OA\Items(type: 'integer'), description: 'SummitRegistrationPromoCode and SummitRegistrationPromoCode IDs when included in relations'),
                new OA\Property(property: 'affiliations', type: 'array', items: new OA\Items(type: 'object'), description: 'Affiliation objects when included in relations'),
            ]
        ),
    ],
)]
class AdminPresentationSpeakerSchema
{
}
