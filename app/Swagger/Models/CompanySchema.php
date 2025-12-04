<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;



#[OA\Schema(
    schema: "Company",
    description: "Company",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "created", type: "integer", format: "int64", description: "Creation timestamp (epoch)", example: 1234567890),
        new OA\Property(property: "last_edited", type: "integer", format: "int64", description: "Last edit timestamp (epoch)", example: 1234567890),
        new OA\Property(property: "name", type: "string", example: "Acme Corporation"),
        new OA\Property(property: "url", type: "string", format: "uri", example: "https://www.acme.com"),
        new OA\Property(property: "url_segment", type: "string", example: "acme-corporation"),
        new OA\Property(property: "city", type: "string", example: "San Francisco"),
        new OA\Property(property: "state", type: "string", example: "California"),
        new OA\Property(property: "country", type: "string", example: "United States"),
        new OA\Property(property: "description", type: "string", example: "Leading technology company"),
        new OA\Property(property: "industry", type: "string", example: "Technology"),
        new OA\Property(property: "contributions", type: "string", example: "OpenStack contributions"),
        new OA\Property(property: "member_level", type: "string", example: "Platinum"),
        new OA\Property(property: "overview", type: "string", example: "Company overview"),
        new OA\Property(property: "products", type: "string", example: "Cloud services, Software"),
        new OA\Property(property: "commitment", type: "string", example: "Commitment to open source"),
        new OA\Property(property: "commitment_author", type: "string", example: "John Doe, CEO"),
        new OA\Property(property: "logo", type: "string", format: "uri", example: "https://cdn.example.com/logo.png"),
        new OA\Property(property: "big_logo", type: "string", format: "uri", example: "https://cdn.example.com/big_logo.png"),
        new OA\Property(property: "color", type: "string", example: "#FF5733"),
        new OA\Property(property: "display_on_site", type: "boolean", example: true),
        new OA\Property(property: "featured", type: "boolean", example: false),
        new OA\Property(property: "contact_email", type: "string", format: "email", example: "contact@acme.com"),
        new OA\Property(property: "admin_email", type: "string", format: "email", example: "admin@acme.com"),
        new OA\Property(
            property: "sponsorships",
            type: "array",
            items: new OA\Items(oneOf: [
                new OA\Schema(type: "integer"),
                new OA\Schema(ref: "#/components/schemas/SummitSponsorship"),
            ]),
            description: "Array of sponsorship IDs (only when relations=sponsorships)",
        ),
        new OA\Property(
            property: "project_sponsorships",
            type: "array",
            items: new OA\Items(oneOf: [
                new OA\Schema(type: "integer"),
                new OA\Schema(ref: "#/components/schemas/ProjectSponsorshipType"),
            ]),
            description: "Array of project sponsorship IDs (only when relations=project_sponsorships)",
        ),
    ],
    type: "object"
)]
class CompanySchema
{
}
