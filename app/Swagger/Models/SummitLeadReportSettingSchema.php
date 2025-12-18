<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'SummitLeadReportSetting',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'sponsor_id', type: 'integer', example: 1),
        new OA\Property(
            property: 'columns',
            type: 'array',
            items: new OA\Items(type: 'string', example: 'first_name'),
            description: 'Array of column names'
        ),
    ]
)]
class SummitLeadReportSettingSchema {}
