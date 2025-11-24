<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;


#[OA\Schema(
    schema: 'SummitTicketType',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', example: 1),
        new OA\Property(property: 'last_edited', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'description', type: 'string'),
        new OA\Property(property: 'external_id', type: 'string'),
        new OA\Property(property: 'summit_id', type: 'integer'),
        new OA\Property(property: 'cost', type: 'float'),
        new OA\Property(property: 'currency', type: 'string'),
        new OA\Property(property: 'currency_symbol', type: 'string'),
        new OA\Property(property: 'quantity_2_sell', type: 'integer'),
        new OA\Property(property: 'max_quantity_per_order', type: 'integer'),
        new OA\Property(property: 'sales_start_date:datetime_epoch'),
        new OA\Property(property: 'sales_end_date:datetime_epoch'),
        new OA\Property(property: 'badge_type_id', type: 'integer', description: 'SummitBadgeType ID, or add ?expand=badge_type for full object'),
        new OA\Property(property: 'quantity_sold', type: 'integer'),
        new OA\Property(property: 'audience', type: 'string'),
        new OA\Property(property: 'allows_to_delegate', type: 'boolean'),
        new OA\Property(property: 'allows_to_reassign', type: 'boolean'),
        new OA\Property(
            property: 'applied_taxes',
            type: 'array',
            items: new OA\Items(type: 'integer'),
            example: [1, 2, 3],
            description: 'Array of SummitTaxType IDs or its Model (only present when relations=applied_taxes), expanded when expand includes applied_taxes.'
        ),
        new OA\Property(property: 'sub_type', type: 'string'),
    ])]
class SummitTicketTypeSchema
{
}