<?php

namespace App\Swagger\schemas;

use models\summit\SummitTicketType;
use OpenApi\Attributes as OA;


#[OA\Schema(
    schema: "SummitTicketType",
    required: [
        "id",
        "name",
        "description",
        "external_id",
        "summit_id",
        "cost",
        "currency",
        "currency_symbol",
        "quantity_2_sell",
        "max_quantity_per_order",
        "sales_start_date",
        "sales_end_date",
        "badge_type_id",
        "quantity_sold",
        "audience",
        "allows_to_delegate",
        "allows_to_reassign",
        "sub_type",
    ],
    properties: [
        new OA\Property(property: "id", type: "integer"),
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
        new OA\Property(property: "audience", type: "string", enum: SummitTicketType::AllowedAudience),
        new OA\Property(property: "allows_to_delegate", type: "boolean"),
        new OA\Property(property: "allows_to_reassign", type: "boolean"),
        new OA\Property(property: "applied_taxes", type: ["integer", "SummitTaxType"]),
        new OA\Property(property: "sub_type", type: "string", enum: SummitTicketType::AllowedSubTypes),
    ]
)]
class SummitTicketTypeSchema
{
}

#[OA\Schema(
    schema: "PaginatedSummitTicketTypesResponse",
    allOf: [
        new OA\Schema(ref: "#/components/schemas/PaginateDataSchemaResponse"),
        new OA\Schema(
            type: "object",
            properties: [
                new OA\Property(
                    property: "data",
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/SummitTicketType")
                )
            ]
        )
    ]
)]
class PaginatedSummitTicketTypesResponse
{
}
