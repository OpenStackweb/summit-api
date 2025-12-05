<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;


#[OA\Schema(
    schema: 'SummitTaxType',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', example: 1),
        new OA\Property(property: 'last_edited', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'tax_id', type: 'string'),
        new OA\Property(property: 'rate', type: 'number'),
        new OA\Property(property: 'summit_id', type: 'integer'),
        new OA\Property(
            property: 'ticket_types',
            type: 'array',
            items: new OA\Items(oneOf: [
                new OA\Schema(type: 'integer'),
                new OA\Schema(ref: '#/components/schemas/SummitTicketType')
            ]),
            description: 'Array of SummitTicketType IDs when in ?relations=ticket_types, use ?expand=ticket_types to get full objects'
        ),


    ])
]
class SummitTaxTypeSchema
{
}