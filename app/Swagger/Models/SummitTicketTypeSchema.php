<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "SummitTicketType",
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', example: 1),
        new OA\Property(property: 'last_edited', type: 'integer', example: 1),
        new OA\Property(property: "name", type: "string"),
        new OA\Property(property: "description", type: "string"),
        new OA\Property(property: "external_id", type: "string"),
        new OA\Property(property: "summit_id", type: "integer"),
        new OA\Property(property: "cost", type: "number", format: "float"),
        new OA\Property(property: "currency", type: "string"),
        new OA\Property(property: "currency_symbol", type: "string"),
        new OA\Property(property: "quantity_2_sell", type: "integer"),
        new OA\Property(property: "max_quantity_per_order", type: "integer"),
        new OA\Property(property: "sales_start_date", type: "integer", description: "Unix timestamp"),
        new OA\Property(property: "sales_end_date", type: "integer", description: "Unix timestamp"),
        new OA\Property(property: "badge_type_id", type: "integer"),
        new OA\Property(property: "quantity_sold", type: "integer"),
        new OA\Property(property: "audience", type: "string", enum: ["All", "WithInvitation", "WithoutInvitation"]),
        new OA\Property(property: "allows_to_delegate", type: "boolean"),
        new OA\Property(property: "allows_to_reassign", type: "boolean"),
        new OA\Property(property: "applied_taxes", type: "array", items: new OA\Items(oneOf: [
            new OA\Schema(type: 'integer'),
            new OA\Schema(ref: '#/components/schemas/SummitTaxType')
        ]), description: "Array of SummitTaxType IDs when in ?relations=applied_taxes, use ?expand=applied_taxes to get full objects"),
        new OA\Property(property: "sub_type", type: "string", enum: ["Regular", "PrePaid"]),
        new OA\Property(
            property: "badge_type",
            type: 'object',
            description: "SummitBadgeType object when ?expand=badge_type"
        ),
    ]
)]
class SummitTicketTypeSchema
{
}
