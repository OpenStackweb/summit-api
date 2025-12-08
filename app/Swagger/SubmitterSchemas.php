<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Submitter',
    type: 'object',
    description: 'Submitter extends Member with presentation data',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/Member'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'accepted_presentations',
                    type: 'array',
                    items: new OA\Items(type: 'integer'),
                    description: 'Array of accepted presentation IDs. Use expand=accepted_presentations to get full objects'
                ),
                new OA\Property(
                    property: 'alternate_presentations',
                    type: 'array',
                    items: new OA\Items(type: 'integer'),
                    description: 'Array of alternate presentation IDs. Use expand=alternate_presentations to get full objects'
                ),
                new OA\Property(
                    property: 'rejected_presentations',
                    type: 'array',
                    items: new OA\Items(type: 'integer'),
                    description: 'Array of rejected presentation IDs. Use expand=rejected_presentations to get full objects'
                ),
            ]
        )
    ]
)]
class SubmitterSchemas {}

/**
 * Paginated Submitters Response
 */
#[OA\Schema(
    schema: 'PaginatedSubmittersResponse',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    description: 'List of submitters',
                    items: new OA\Items(ref: '#/components/schemas/Submitter')
                )
            ]
        )
    ]
)]
class PaginatedSubmittersResponseSchema {}

/**
 * Send Emails to Submitters Request
 */
#[OA\Schema(
    schema: 'SendSubmittersEmailsRequest',
    type: 'object',
    required: ['subject', 'body'],
    properties: [
        new OA\Property(
            property: 'subject',
            type: 'string',
            example: 'Important Update for Submitters',
            description: 'Email subject'
        ),
        new OA\Property(
            property: 'body',
            type: 'string',
            example: 'Dear Submitter, here is an important update...',
            description: 'Email body content'
        ),
        new OA\Property(
            property: 'filter',
            type: 'string',
            example: 'has_accepted_presentations==true',
            description: 'Optional filter to select specific submitters'
        ),
    ]
)]
class SendSubmittersEmailsRequestSchema {}