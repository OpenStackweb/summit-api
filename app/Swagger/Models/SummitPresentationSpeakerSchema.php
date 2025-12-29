<?php

namespace App\Swagger\Summit;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'SummitPresentationSpeaker',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', example: 1, format: "time_epoch"),
        new OA\Property(property: 'last_edited', type: 'integer', example: 1, format: "time_epoch"),
        new OA\Property(property: 'first_name', type: 'string', example: 'John'),
        new OA\Property(property: 'last_name', type: 'string', example: 'Doe'),
        new OA\Property(property: 'title', type: 'string', example: 'Software Engineer'),
        new OA\Property(property: 'bio', type: 'string', example: 'Experienced software engineer with 10 years in cloud computing'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john.doe@example.com'),
        new OA\Property(property: 'twitter', type: 'string', example: '@johndoe'),
        new OA\Property(property: 'irc', type: 'string', example: 'johndoe-irc'),
        new OA\Property(property: 'pic', type: 'string', format: 'uri', example: 'https://example.com/photos/johndoe.jpg'),
        new OA\Property(property: 'big_pic', type: 'string', format: 'uri'),
        new OA\Property(property: 'member_id', type: 'integer', format: 'int64'),
        new OA\Property(property: 'registration_request_id', type: 'integer', format: 'int64'),
        new OA\Property(property: 'funded_travel', type: 'boolean'),
        new OA\Property(property: 'willing_to_travel', type: 'boolean'),
        new OA\Property(property: 'willing_to_present_video', type: 'boolean'),
        new OA\Property(property: 'org_has_cloud', type: 'boolean'),
        new OA\Property(property: 'available_for_bureau', type: 'boolean'),
        new OA\Property(property: 'country', type: 'string', example: 'US'),
        new OA\Property(property: 'company', type: 'string'),
        new OA\Property(property: 'phone_number', type: 'string'),
    ]
)]
class SummitPresentationSpeakerSchema
{
}
