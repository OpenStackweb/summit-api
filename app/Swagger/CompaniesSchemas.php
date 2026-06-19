<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

// Companies Schemas

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
    schema: "PaginatedBaseCompaniesResponse",
    description: "Paginated response for Companies (Public View)",
    properties: [
        new OA\Property(property: "total", type: "integer", example: 100),
        new OA\Property(property: "per_page", type: "integer", example: 15),
        new OA\Property(property: "current_page", type: "integer", example: 1),
        new OA\Property(property: "last_page", type: "integer", example: 7),
        new OA\Property(
            property: "data",
            type: "array",
            items: new OA\Items(ref: "#/components/schemas/BaseCompany")
        ),
    ],
    type: "object"
)]
class PaginatedBaseCompaniesResponseSchema
{
}

#[OA\Schema(
    schema: "FileDTO",
    description: "File metadata payload produced by the File API after a successful upload",
    required: ["filepath", "filename", "md5", "size"],
    properties: [
        new OA\Property(property: "filepath", type: "string", description: "Remote storage path of the uploaded file", example: "companies/1/tmp/logo.png"),
        new OA\Property(property: "filename", type: "string", description: "Original file name", example: "logo.png"),
        new OA\Property(property: "md5", type: "string", description: "MD5 hash of the file for integrity verification", example: "d41d8cd98f00b204e9800998ecf8427e"),
        new OA\Property(property: "size", type: "integer", description: "File size in bytes", example: 204800),
        new OA\Property(property: "mime_type", type: "string", nullable: true, description: "MIME type of the file", example: "image/png"),
        new OA\Property(property: "source_bucket", type: "string", nullable: true, description: "Source storage bucket", example: "my-uploads-bucket"),
    ],
    type: "object"
)]
class FileDTOSchema
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
        new OA\Property(property: "logo", nullable: true, ref: "#/components/schemas/FileDTO", description: "Company logo (File API payload)"),
        new OA\Property(property: "big_logo", nullable: true, ref: "#/components/schemas/FileDTO", description: "Company big logo (File API payload)"),
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
        new OA\Property(property: "logo", nullable: true, ref: "#/components/schemas/FileDTO", description: "Company logo (File API payload)"),
        new OA\Property(property: "big_logo", nullable: true, ref: "#/components/schemas/FileDTO", description: "Company big logo (File API payload)"),
    ],
    type: "object"
)]
class CompanyUpdateRequestSchema
{
}
