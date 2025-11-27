<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;


#[OA\Schema(
    schema: 'Distribution',
    ref: '#/components/schemas/OpenStackImplementation',
)]
class DistributionSchema
{
}
