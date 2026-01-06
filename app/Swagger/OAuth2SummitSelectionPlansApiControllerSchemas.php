<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

/**
 * Paginated Responses
 */

#[OA\Schema(
    schema: 'PaginatedSelectionPlansResponse',
    type: 'object',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/SelectionPlan')
                )
            ]
        )
    ]
)]
class PaginatedSelectionPlansResponseSchema {}

#[OA\Schema(
    schema: 'PaginatedSummitSelectionPlanExtraQuestionTypesResponse',
    type: 'object',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/SummitSelectionPlanExtraQuestionType')
                )
            ]
        )
    ]
)]
class PaginatedSummitSelectionPlanExtraQuestionTypesResponseSchema {}

#[OA\Schema(
    schema: 'PaginatedAssignedSelectionPlanExtraQuestionTypesResponse',
    type: 'object',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/AssignedSelectionPlanExtraQuestionType')
                )
            ]
        )
    ]
)]
class PaginatedAssignedSelectionPlanExtraQuestionTypesResponseSchema {}

#[OA\Schema(
    schema: 'PaginatedSelectionPlanAllowedMembersResponse',
    type: 'object',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/SelectionPlanAllowedMember')
                )
            ]
        )
    ]
)]
class PaginatedSelectionPlanAllowedMembersResponseSchema {}

#[OA\Schema(
    schema: 'PaginatedSummitCategoryChangesResponse',
    type: 'object',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/SummitCategoryChange')
                )
            ]
        )
    ]
)]
class PaginatedSummitCategoryChangesResponseSchema {}

#[OA\Schema(
    schema: 'PaginatedPresentationActionTypesResponse',
    type: 'object',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/PresentationActionType')
                )
            ]
        )
    ]
)]
class PaginatedPresentationActionTypesResponseSchema {}

/**
 * Metadata Response Schemas
 */

#[OA\Schema(
    schema: 'ExtraQuestionTypeMetadata',
    type: 'object',
    description: 'Metadata about available extra question types',
    properties: [
        new OA\Property(
            property: 'types',
            type: 'array',
            items: new OA\Items(type: 'string'),
            example: ['Text', 'TextArea', 'ComboBox', 'CheckBoxList', 'RadioButtonList', 'CheckBox', 'CountryComboBox']
        ),
    ]
)]
class ExtraQuestionTypeMetadataSchema {}

/**
 * Extra Question Value Schema
 */

#[OA\Schema(
    schema: 'ExtraQuestionTypeValue',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', description: 'Unix timestamp', example: 1640995200),
        new OA\Property(property: 'last_edited', type: 'integer', description: 'Unix timestamp', example: 1640995200),
        new OA\Property(property: 'value', type: 'string', example: 'beginner'),
        new OA\Property(property: 'label', type: 'string', example: 'Beginner'),
        new OA\Property(property: 'order', type: 'integer', example: 1),
    ]
)]
class ExtraQuestionTypeValueSchema {}

/**
 * Request Body Schemas
 */

#[OA\Schema(
    schema: 'PresentationCommentPayload',
    type: 'object',
    required: ['body', 'is_public'],
    properties: [
        new OA\Property(property: 'body', type: 'string', description: 'The comment body text'),
        new OA\Property(property: 'is_public', type: 'boolean', description: 'Whether the comment is public'),
    ]
)]
class PresentationCommentPayloadSchema {}

#[OA\Schema(
    schema: 'CategoryChangeRequestPayload',
    type: 'object',
    required: ['new_category_id'],
    properties: [
        new OA\Property(property: 'new_category_id', type: 'integer', description: 'The ID of the new category/track'),
    ]
)]
class CategoryChangeRequestPayloadSchema {}

#[OA\Schema(
    schema: 'ResolveCategoryChangeRequestPayload',
    type: 'object',
    required: ['approved'],
    properties: [
        new OA\Property(property: 'approved', type: 'boolean', description: 'Whether to approve the category change request'),
        new OA\Property(property: 'reason', type: 'string', description: 'Optional reason for rejection'),
    ]
)]
class ResolveCategoryChangeRequestPayloadSchema {}

#[OA\Schema(
    schema: 'AllowedMemberPayload',
    type: 'object',
    required: ['email'],
    properties: [
        new OA\Property(property: 'email', type: 'string', format: 'email', description: 'The email of the allowed member'),
    ]
)]
class AllowedMemberPayloadSchema {}
