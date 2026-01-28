<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ImportRegistrationCompaniesRequest',
    type: 'object',
    description: 'Request to import registration companies from CSV file',
    required: ['file'],
    properties: [
        new OA\Property(
            property: 'file',
            type: 'string',
            format: 'binary',
            description: 'CSV file with company data'
        ),
    ]
)]
class ImportRegistrationCompaniesRequestSchema {}
