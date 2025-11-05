<?php

namespace App\Swagger\Summit;

use OpenApi\Attributes as OA;

class SummitPresentationSpeakerDeleteMySpeakerPhoto
{
    #[OA\Schema(
        schema: 'SummitPresentationSpeakerDeleteMySpeakerPhoto',
        type: 'object',
        description: 'Delete my speaker photo response'
    )]
    public function model() {}
}