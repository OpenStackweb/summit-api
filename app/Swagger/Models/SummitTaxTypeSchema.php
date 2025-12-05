<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;


#[OA\Schema(
    schema: 'SummitTaxType',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', example: 1630500518),
        new OA\Property(property: 'last_edited', type: 'integer', example: 1630500518),
        new OA\Property(property: 'name', type: 'string', example: 'VAT'),
        new OA\Property(property: 'tax_id', type: 'string', example: 'VAT-001', nullable: true),
        new OA\Property(property: 'rate', type: 'number', format: 'float', example: 21.0),
        new OA\Property(property: 'summit_id', type: 'integer', example: 42),
        new OA\Property(
            property: 'ticket_types',
            type: 'array',
            items: new OA\Items(oneOf: [
                new OA\Schema(type: 'integer'),
                new OA\Schema(ref: '#/components/schemas/SummitTicketType')
            ]),
            example: [1, 2, 3],
            description: 'Array of ticket type IDs or its Model (only present when relations=ticket_types), expanded when expand includes ticket_types.'
        ),
    ]
)]
class SummitTaxTypeSchema
{
}
