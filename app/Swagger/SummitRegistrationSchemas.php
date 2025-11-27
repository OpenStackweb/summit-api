<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

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
class PaginatedSummitTicketTypesResponseSchema
{
}