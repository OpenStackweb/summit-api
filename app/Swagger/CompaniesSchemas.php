<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

// Companies Schemas

#[OA\Schema(
    schema: "Company",
    description: "Company",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "created", type: "integer", format: "int64", description: "Creation timestamp (epoch)", example: 1234567890),
        new OA\Property(property: "last_edited", type: "integer", format: "int64", description: "Last edit timestamp (epoch)", example: 1234567890),
        new OA\Property(property: "name", type: "string", example: "Acme Corporation"),
        new OA\Property(property: "url", type: "string", format: "uri", nullable: true, example: "https://www.acme.com"),
        new OA\Property(property: "url_segment", type: "string", nullable: true, example: "acme-corporation"),
        new OA\Property(property: "display_on_site", type: "boolean", example: true),
        new OA\Property(property: "featured", type: "boolean", example: false),
        new OA\Property(property: "city", type: "string", nullable: true, example: "San Francisco"),
        new OA\Property(property: "state", type: "string", nullable: true, example: "California"),
        new OA\Property(property: "country", type: "string", nullable: true, example: "United States"),
        new OA\Property(property: "description", type: "string", nullable: true, example: "Leading technology company"),
        new OA\Property(property: "industry", type: "string", nullable: true, example: "Technology"),
        new OA\Property(property: "products", type: "string", nullable: true, example: "Cloud services, Software"),
        new OA\Property(property: "contributions", type: "string", nullable: true, example: "OpenStack contributions"),
        new OA\Property(property: "contact_email", type: "string", format: "email", nullable: true, example: "contact@acme.com"),
        new OA\Property(property: "member_level", type: "string", nullable: true, example: "Platinum"),
        new OA\Property(property: "admin_email", type: "string", format: "email", nullable: true, example: "admin@acme.com"),
        new OA\Property(property: "color", type: "string", nullable: true, example: "#FF5733"),
        new OA\Property(property: "logo", type: "string", format: "uri", nullable: true, example: "https://cdn.example.com/logo.png"),
        new OA\Property(property: "big_logo", type: "string", format: "uri", nullable: true, example: "https://cdn.example.com/big_logo.png"),
        new OA\Property(property: "overview", type: "string", nullable: true, example: "Company overview"),
        new OA\Property(property: "commitment", type: "string", nullable: true, example: "Commitment to open source"),
        new OA\Property(property: "commitment_author", type: "string", nullable: true, example: "John Doe, CEO"),
    ],
    anyOf: [
        new OA\Property(
            property: "sponsorships",
            type: "array",
            items: new OA\Items(type: "integer"),
            description: "Array of sponsorship IDs (only when relations=sponsorships)",
            example: [1, 2, 3]
        ),
        new OA\Property(
            property: "sponsorships",
            type: "array",
            items: new OA\Items(type: "SummitSponsorship"),
            description: "Array of SummitSponsorship models (only when expand=sponsorships)",
        ),
        new OA\Property(
            property: "project_sponsorships",
            type: "array",
            items: new OA\Items(type: "integer"),
            description: "Array of project sponsorship IDs (only when relations=project_sponsorships)",
            example: [4, 5, 6]
        ),
        new OA\Property(
            property: "project_sponsorships",
            type: "array",
            items: new OA\Items(type: "ProjectSponsorshipType"),
            description: "Array of project ProjectSponsorshipType models (only when expand=project_sponsorships)",
        ),
    ],
    type: "object"
)]
class CompanySchema
{
}

#[OA\Schema(
    schema: "PaginatedCompaniesResponse",
    description: "Paginated response for Companies",
    properties: [
        new OA\Property(property: "total", type: "integer", example: 100),
        new OA\Property(property: "per_page", type: "integer", example: 15),
        new OA\Property(property: "current_page", type: "integer", example: 1),
        new OA\Property(property: "last_page", type: "integer", example: 7),
        new OA\Property(
            property: "data",
            type: "array",
            items: new OA\Items(ref: "#/components/schemas/Company")
        ),
    ],
    type: "object"
)]
class PaginatedCompaniesResponseSchema
{
}

#[OA\Schema(
    schema: "CompanyCreateRequest",
    description: "Request to create a Company",
    required: ["name"],
    properties: [
        new OA\Property(property: "name", type: "string", example: "Acme Corporation"),
        new OA\Property(property: "url", type: "string", format: "uri", nullable: true, example: "https://www.acme.com"),
        new OA\Property(property: "display_on_site", type: "boolean", nullable: true, example: true),
        new OA\Property(property: "featured", type: "boolean", nullable: true, example: false),
        new OA\Property(property: "city", type: "string", nullable: true, example: "San Francisco"),
        new OA\Property(property: "state", type: "string", nullable: true, example: "California"),
        new OA\Property(property: "country", type: "string", nullable: true, example: "United States"),
        new OA\Property(property: "description", type: "string", nullable: true, example: "Leading technology company"),
        new OA\Property(property: "industry", type: "string", nullable: true, example: "Technology"),
        new OA\Property(property: "products", type: "string", nullable: true, example: "Cloud services, Software"),
        new OA\Property(property: "contributions", type: "string", nullable: true, example: "OpenStack contributions"),
        new OA\Property(property: "contact_email", type: "string", format: "email", nullable: true, example: "contact@acme.com"),
        new OA\Property(property: "member_level", type: "string", nullable: true, example: "Platinum"),
        new OA\Property(property: "admin_email", type: "string", format: "email", nullable: true, example: "admin@acme.com"),
        new OA\Property(property: "color", type: "string", description: "Hex color code", nullable: true, example: "#FF5733"),
        new OA\Property(property: "overview", type: "string", nullable: true, example: "Company overview"),
        new OA\Property(property: "commitment", type: "string", nullable: true, example: "Commitment to open source"),
        new OA\Property(property: "commitment_author", type: "string", nullable: true, example: "John Doe, CEO"),
    ],
    type: "object"
)]
class CompanyCreateRequestSchema
{
}

#[OA\Schema(
    schema: "CompanyUpdateRequest",
    description: "Request to update a Company",
    properties: [
        new OA\Property(property: "name", type: "string", nullable: true, example: "Acme Corporation"),
        new OA\Property(property: "url", type: "string", format: "uri", nullable: true, example: "https://www.acme.com"),
        new OA\Property(property: "display_on_site", type: "boolean", nullable: true, example: true),
        new OA\Property(property: "featured", type: "boolean", nullable: true, example: false),
        new OA\Property(property: "city", type: "string", nullable: true, example: "San Francisco"),
        new OA\Property(property: "state", type: "string", nullable: true, example: "California"),
        new OA\Property(property: "country", type: "string", nullable: true, example: "United States"),
        new OA\Property(property: "description", type: "string", nullable: true, example: "Leading technology company"),
        new OA\Property(property: "industry", type: "string", nullable: true, example: "Technology"),
        new OA\Property(property: "products", type: "string", nullable: true, example: "Cloud services, Software"),
        new OA\Property(property: "contributions", type: "string", nullable: true, example: "OpenStack contributions"),
        new OA\Property(property: "contact_email", type: "string", format: "email", nullable: true, example: "contact@acme.com"),
        new OA\Property(property: "member_level", type: "string", nullable: true, example: "Platinum"),
        new OA\Property(property: "admin_email", type: "string", format: "email", nullable: true, example: "admin@acme.com"),
        new OA\Property(property: "color", type: "string", description: "Hex color code", nullable: true, example: "#FF5733"),
        new OA\Property(property: "overview", type: "string", nullable: true, example: "Company overview"),
        new OA\Property(property: "commitment", type: "string", nullable: true, example: "Commitment to open source"),
        new OA\Property(property: "commitment_author", type: "string", nullable: true, example: "John Doe, CEO"),
    ],
    type: "object"
)]
class CompanyUpdateRequestSchema
{
}
