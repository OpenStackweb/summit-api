<?php

namespace App\Swagger\Summit;

use OpenApi\Attributes as OA;

class SummitPresentationSpeakerPhotoRequest
{
    #[OA\Schema(
        schema: 'SummitPresentationSpeakerPhotoRequest',
        type: 'object',
        description: 'Request to upload speaker photo',
        properties: [
            new OA\Property(
                property: 'file',
                type: 'string',
                format: 'binary',
                description: 'Speaker photo file (JPG, PNG)'
            ),
        ],
        required: ['file']
    )]
    public function model() {}
}