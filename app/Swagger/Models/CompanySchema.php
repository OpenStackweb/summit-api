<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "Company",
    description: "Company",
    type: "object",
    allOf: [
        new OA\Schema(ref: '#/components/schemas/BaseCompany'),
        new OA\Schema(
            type: 'object',
            properties: [
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
                    description: "Array of sponsorship IDs (only when relations=sponsorships), full objects when ?expand=sponsorships",
                ),
                new OA\Property(
                    property: "project_sponsorships",
                    type: "array",
                    items: new OA\Items(oneOf: [
                        new OA\Schema(type: "integer"),
                        new OA\Schema(ref: "#/components/schemas/ProjectSponsorshipType"),
                    ]),
                    description: "Array of project sponsorship IDs (only when relations=project_sponsorships), full objects when ?expand=project_sponsorships",
                ),
            ],
        )
    ]
)]
class CompanySchema
{
}
