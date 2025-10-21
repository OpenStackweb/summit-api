<?php
namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    title: "Summit Sponsorship",
    description: "Summit Sponsorship Schema",
    type: "object",
    properties: [
        new OA\Property(property: "id", type: "integer", format: "int64"),
        new OA\Property(property: "type_id", type: "integer", format: "int64"),
        new OA\Property(property: "add_ons", type: "array", items: new OA\Items(type: "integer", format: "int64")),
    ]
)]
class SummitSponsorshipSchemas {}