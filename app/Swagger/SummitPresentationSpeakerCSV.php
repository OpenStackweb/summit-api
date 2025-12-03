<?php


namespace App\Swagger\Summit;

use OpenApi\Attributes as OA;

class SummitPresentationSpeakerCSV
{
    #[OA\Schema(
        schema: 'SummitPresentationSpeakerCSV',
        type: 'string',
        format: 'binary',
        description: 'CSV file with speaker data containing columns: id, first_name, last_name, email, accepted_presentations, accepted_presentations_count, alternate_presentations, alternate_presentations_count, rejected_presentations, rejected_presentations_count'
    )]
    public function model() {}
}