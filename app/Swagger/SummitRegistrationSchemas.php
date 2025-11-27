<?php

namespace App\Swagger\schemas;

use models\summit\SummitTicketType;
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

#[OA\Schema(
    schema: "SummitTicketTypeAddRequest",
    type: "object",
    required: ["name"],
    properties: [
        new OA\Property(property: "name", type: "string", example: "General Admission"),
        new OA\Property(property: "description", type: "string", example: "Standard ticket for conference access"),
        new OA\Property(property: "external_id", type: "string", maxLength: 255, example: "ext-123"),
        new OA\Property(property: "cost", type: "number", format: "float", minimum: 0, example: 99.99),
        new OA\Property(property: "currency", type: "string", description: "Required when cost is provided. ISO currency code.", example: "USD"),
        new OA\Property(property: "quantity_2_sell", type: "integer", minimum: 0, example: 100),
        new OA\Property(property: "max_quantity_per_order", type: "integer", minimum: 0, example: 10),
        new OA\Property(property: "sales_start_date", type: "integer", description: "Unix timestamp", example: 1640995200, nullable: true),
        new OA\Property(property: "sales_end_date", type: "integer", description: "Unix timestamp (must be after sales_start_date)", example: 1641081600, nullable: true),
        new OA\Property(property: "badge_type_id", type: "integer", example: 1),
        new OA\Property(property: "audience", type: "string", enum: SummitTicketType::AllowedAudience, example: SummitTicketType::Audience_All),
        new OA\Property(property: "allows_to_delegate", type: "boolean", example: true),
        new OA\Property(property: "allows_to_reassign", type: "boolean", example: true),
    ]
)]
class SummitTicketTypeAddRequestSchema
{
}

#[OA\Schema(
    schema: "SummitTicketTypeUpdateRequest",
    type: "object",
    properties: [
        new OA\Property(property: "name", type: "string", example: "General Admission"),
        new OA\Property(property: "description", type: "string", example: "Standard ticket for conference access"),
        new OA\Property(property: "external_id", type: "string", maxLength: 255, example: "ext-123"),
        new OA\Property(property: "cost", type: "number", format: "float", minimum: 0, example: 99.99),
        new OA\Property(property: "currency", type: "string", description: "ISO currency code", example: "USD"),
        new OA\Property(property: "quantity_2_sell", type: "integer", minimum: 0, example: 100),
        new OA\Property(property: "max_quantity_per_order", type: "integer", minimum: 0, example: 10),
        new OA\Property(property: "sales_start_date", type: "integer", description: "Unix timestamp", example: 1640995200, nullable: true),
        new OA\Property(property: "sales_end_date", type: "integer", description: "Unix timestamp (must be after sales_start_date)", example: 1641081600, nullable: true),
        new OA\Property(property: "badge_type_id", type: "integer", example: 1),
        new OA\Property(property: "audience", type: "string", enum: SummitTicketType::AllowedAudience, example: SummitTicketType::Audience_All),
        new OA\Property(property: "allows_to_delegate", type: "boolean", example: true),
        new OA\Property(property: "allows_to_reassign", type: "boolean", example: true),
    ]
)]
class SummitTicketTypeUpdateRequestSchema
{
}