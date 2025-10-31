<?php

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'RSVPTemplate',
    title: 'RSVP Template',
    description: 'Represents an RSVP Template for a Summit',
    type: 'object',
    required: ['title'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64'),
        new OA\Property(property: 'title', type: 'string'),
        new OA\Property(property: 'is_enabled', type: 'boolean'),
        new OA\Property(property: 'created', type: 'integer', format: 'int64'),
        new OA\Property(property: 'last_edited', type: 'integer', format: 'int64'),
    ]
)]
class RSVPTemplate {}

#[OA\Schema(
    schema: 'PaginatedRSVPTemplatesResponse',
    title: 'Paginated RSVP Templates',
    description: 'Paginated list of RSVP templates',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/RSVPTemplate')
                ),
            ]
        )
    ]
)]
class PaginatedRSVPTemplatesResponse {}

#[OA\Schema(
    schema: 'RSVPTemplateQuestion',
    title: 'RSVP Template Question',
    description: 'Represents a Question in an RSVP Template',
    type: 'object',
    required: ['question', 'question_type'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64'),
        new OA\Property(property: 'question', type: 'string'),
        new OA\Property(property: 'question_type', type: 'string', enum: ['text', 'textarea', 'checkbox', 'radio_button', 'dropdown', 'multi_select']),
        new OA\Property(property: 'mandatory', type: 'boolean'),
        new OA\Property(property: 'order', type: 'integer'),
        new OA\Property(property: 'created', type: 'integer', format: 'int64'),
        new OA\Property(property: 'last_edited', type: 'integer', format: 'int64'),
    ]
)]
class RSVPTemplateQuestion {}

#[OA\Schema(
    schema: 'RSVPTemplateQuestionValue',
    title: 'RSVP Template Question Value',
    description: 'Represents a Value/Option for a Multi-Select Question in an RSVP Template',
    type: 'object',
    required: ['label'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64'),
        new OA\Property(property: 'label', type: 'string'),
        new OA\Property(property: 'value', type: 'string'),
        new OA\Property(property: 'order', type: 'integer'),
        new OA\Property(property: 'created', type: 'integer', format: 'int64'),
        new OA\Property(property: 'last_edited', type: 'integer', format: 'int64'),
    ]
)]
class RSVPTemplateQuestionValue {}

#[OA\Schema(
    schema: 'RSVPTemplateQuestionMetadata',
    title: 'RSVP Template Question Metadata',
    description: 'Metadata about available question types and their configurations',
    type: 'object',
    properties: [
        new OA\Property(property: 'question_types', type: 'array', items: new OA\Items(type: 'string')),
        new OA\Property(property: 'validators', type: 'object', additionalProperties: true),
    ]
)]
class RSVPTemplateQuestionMetadata {}