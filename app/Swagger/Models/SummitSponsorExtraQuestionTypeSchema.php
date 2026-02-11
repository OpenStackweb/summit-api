<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'SummitSponsorExtraQuestionType',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'sponsor_id', type: 'integer', example: 1),
        new OA\Property(property: 'type', type: 'string', example: 'TEXT', enum: ['TEXT', 'CHECKBOX', 'RADIO_BUTTON', 'DROP_DOWN']),
        new OA\Property(property: 'label', type: 'string', example: 'Question Label'),
        new OA\Property(property: 'mandatory', type: 'boolean', example: false),
        new OA\Property(property: 'order', type: 'integer', example: 1),
        new OA\Property(
            property: 'values',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/ExtraQuestionTypeValue'),
            nullable: true
        ),
    ]
)]
class SummitSponsorExtraQuestionTypeSchema {}
