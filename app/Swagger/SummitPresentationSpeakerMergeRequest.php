<?php

namespace App\Swagger\Summit;

use OpenApi\Attributes as OA;

class SummitPresentationSpeakerMergeRequest
{
    #[OA\Schema(
        schema: 'SummitPresentationSpeakerMergeRequest',
        type: 'object',
        description: 'Request to merge two speakers',
        properties: [],
        example: []
    )]
    public function model() {}
}