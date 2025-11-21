<?php
namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    title: "Summit Sponsorship Add On",
    description: "Summit Sponsorship Add On Schema",
    type: "object",
    properties: [
        new OA\Property(property: "id", type: "integer", format: "int64"),
        new OA\Property(property: "name", type: "string"),
        new OA\Property(property: "label", type: "string"),
        new OA\Property(property: "type", type: "string"),
        new OA\Property(property: "size", type: "string"),
    ]
)]
class SummitSponsorshipAddOnSchemas {}