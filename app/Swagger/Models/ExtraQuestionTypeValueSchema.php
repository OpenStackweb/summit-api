<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ExtraQuestionTypeValue',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'value', type: 'string', example: 'Option 1'),
        new OA\Property(property: 'order', type: 'integer', example: 1),
    ]
)]
class ExtraQuestionTypeValueSchema {}
