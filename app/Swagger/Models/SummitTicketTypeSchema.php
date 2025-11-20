<?php 
namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "SummitTicketType",
    type: "object",
    properties: [
        new OA\Property(
            property: "id",
            type: "integer",
            format: "int64",
            example: 1
        ),
        new OA\Property(
            property: "name",
            type: "string",
            description: "Ticket type name",
            example: "General Admission"
        ),
        new OA\Property(
            property: "description",
            type: "string",
            nullable: true,
            description: "Ticket type description",
            example: "General admission ticket for the summit"
        ),
        new OA\Property(
            property: "external_id",
            type: "string",
            nullable: true,
            description: "External identifier",
            example: "EXT-001"
        ),
        new OA\Property(
            property: "summit_id",
            type: "integer",
            format: "int64",
            example: 1
        ),
        new OA\Property(
            property: "cost",
            type: "number",
            format: "float",
            description: "Ticket cost",
            example: 299.99
        ),
        new OA\Property(
            property: "currency",
            type: "string",
            description: "Currency code",
            example: "USD"
        ),
        new OA\Property(
            property: "currency_symbol",
            type: "string",
            nullable: true,
            description: "Currency symbol",
            example: "$"
        ),
        new OA\Property(
            property: "quantity_2_sell",
            type: "integer",
            format: "int32",
            nullable: true,
            description: "Total quantity available for sale",
            example: 500
        ),
        new OA\Property(
            property: "max_quantity_per_order",
            type: "integer",
            format: "int32",
            nullable: true,
            description: "Maximum quantity per order",
            example: 5
        ),
        new OA\Property(
            property: "sales_start_date",
            type: "integer",
            format: "int64",
            nullable: true,
            description: "Sales start date (Unix timestamp)"
        ),
        new OA\Property(
            property: "sales_end_date",
            type: "integer",
            format: "int64",
            nullable: true,
            description: "Sales end date (Unix timestamp)"
        ),
        new OA\Property(
            property: "badge_type_id",
            type: "integer",
            format: "int64",
            nullable: true,
            description: "Badge type ID"
        ),
        new OA\Property(
            property: "quantity_sold",
            type: "integer",
            format: "int32",
            nullable: true,
            description: "Quantity sold",
            example: 350
        ),
        new OA\Property(
            property: "audience",
            type: "string",
            nullable: true,
            description: "Target audience",
            example: "General Public"
        ),
        new OA\Property(
            property: "allows_to_delegate",
            type: "boolean",
            description: "Whether ticket can be delegated",
            example: true
        ),
        new OA\Property(
            property: "allows_to_reassign",
            type: "boolean",
            description: "Whether related tickets can be reassigned",
            example: true
        ),
        new OA\Property(
            property: "created",
            type: "integer",
            format: "int64"
        ),
        new OA\Property(
            property: "last_edited",
            type: "integer",
            format: "int64"
        ),
    ]
)]
class SummitTicketTypeSchema {}
