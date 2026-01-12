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

#[OA\Schema(
    schema: 'AddSelectionPlanPayload',
    type: 'object',
    description: 'Payload for creating a new selection plan',
    required: ['name', 'is_enabled', 'is_hidden', 'allow_new_presentations'],
    properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 255, description: 'Name of the selection plan'),
        new OA\Property(property: 'is_enabled', type: 'boolean', description: 'Whether the selection plan is enabled'),
        new OA\Property(property: 'is_hidden', type: 'boolean', description: 'Whether the selection plan is hidden'),
        new OA\Property(property: 'allow_new_presentations', type: 'boolean', description: 'Whether new presentations are allowed'),
        new OA\Property(property: 'max_submission_allowed_per_user', type: 'integer', minimum: 1, description: 'Maximum submissions allowed per user'),
        new OA\Property(property: 'submission_begin_date', type: 'integer', description: 'Unix timestamp for submission begin date', nullable: true),
        new OA\Property(property: 'submission_end_date', type: 'integer', description: 'Unix timestamp for submission end date', nullable: true),
        new OA\Property(property: 'submission_lock_down_presentation_status_date', type: 'integer', description: 'Unix timestamp for submission lockdown date', nullable: true),
        new OA\Property(property: 'voting_begin_date', type: 'integer', description: 'Unix timestamp for voting begin date', nullable: true),
        new OA\Property(property: 'voting_end_date', type: 'integer', description: 'Unix timestamp for voting end date', nullable: true),
        new OA\Property(property: 'selection_begin_date', type: 'integer', description: 'Unix timestamp for selection begin date', nullable: true),
        new OA\Property(property: 'selection_end_date', type: 'integer', description: 'Unix timestamp for selection end date', nullable: true),
        new OA\Property(property: 'submission_period_disclaimer', type: 'string', description: 'Disclaimer text for the submission period'),
        new OA\Property(property: 'presentation_creator_notification_email_template', type: 'string', maxLength: 255, description: 'Email template for presentation creator notifications'),
        new OA\Property(property: 'presentation_moderator_notification_email_template', type: 'string', maxLength: 255, description: 'Email template for presentation moderator notifications'),
        new OA\Property(property: 'presentation_speaker_notification_email_template', type: 'string', maxLength: 255, description: 'Email template for presentation speaker notifications'),
        new OA\Property(property: 'allowed_presentation_questions', type: 'array', items: new OA\Items(type: 'string'), description: 'List of allowed presentation questions'),
        new OA\Property(property: 'allow_proposed_schedules', type: 'boolean', description: 'Whether proposed schedules are allowed'),
        new OA\Property(property: 'allowed_presentation_editable_questions', type: 'array', items: new OA\Items(type: 'string'), description: 'List of allowed editable presentation questions'),
        new OA\Property(property: 'allow_track_change_requests', type: 'boolean', description: 'Whether track change requests are allowed'),
    ]
)]
class AddSelectionPlanPayloadSchema {}

#[OA\Schema(
    schema: 'UpdateSelectionPlanPayload',
    type: 'object',
    description: 'Payload for updating an existing selection plan',
    properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 255, description: 'Name of the selection plan'),
        new OA\Property(property: 'is_enabled', type: 'boolean', description: 'Whether the selection plan is enabled'),
        new OA\Property(property: 'is_hidden', type: 'boolean', description: 'Whether the selection plan is hidden'),
        new OA\Property(property: 'allow_new_presentations', type: 'boolean', description: 'Whether new presentations are allowed'),
        new OA\Property(property: 'max_submission_allowed_per_user', type: 'integer', minimum: 1, description: 'Maximum submissions allowed per user'),
        new OA\Property(property: 'submission_begin_date', type: 'integer', description: 'Unix timestamp for submission begin date', nullable: true),
        new OA\Property(property: 'submission_end_date', type: 'integer', description: 'Unix timestamp for submission end date', nullable: true),
        new OA\Property(property: 'submission_lock_down_presentation_status_date', type: 'integer', description: 'Unix timestamp for submission lockdown date', nullable: true),
        new OA\Property(property: 'voting_begin_date', type: 'integer', description: 'Unix timestamp for voting begin date', nullable: true),
        new OA\Property(property: 'voting_end_date', type: 'integer', description: 'Unix timestamp for voting end date', nullable: true),
        new OA\Property(property: 'selection_begin_date', type: 'integer', description: 'Unix timestamp for selection begin date', nullable: true),
        new OA\Property(property: 'selection_end_date', type: 'integer', description: 'Unix timestamp for selection end date', nullable: true),
        new OA\Property(property: 'submission_period_disclaimer', type: 'string', description: 'Disclaimer text for the submission period'),
        new OA\Property(property: 'presentation_creator_notification_email_template', type: 'string', maxLength: 255, description: 'Email template for presentation creator notifications'),
        new OA\Property(property: 'presentation_moderator_notification_email_template', type: 'string', maxLength: 255, description: 'Email template for presentation moderator notifications'),
        new OA\Property(property: 'presentation_speaker_notification_email_template', type: 'string', maxLength: 255, description: 'Email template for presentation speaker notifications'),
        new OA\Property(property: 'allowed_presentation_questions', type: 'array', items: new OA\Items(type: 'string'), description: 'List of allowed presentation questions'),
        new OA\Property(property: 'allow_proposed_schedules', type: 'boolean', description: 'Whether proposed schedules are allowed'),
        new OA\Property(property: 'allowed_presentation_editable_questions', type: 'array', items: new OA\Items(type: 'string'), description: 'List of allowed editable presentation questions'),
        new OA\Property(property: 'allow_track_change_requests', type: 'boolean', description: 'Whether track change requests are allowed'),
    ]
)]
class UpdateSelectionPlanPayloadSchema {}

#[OA\Schema(
    schema: 'AddAllowedPresentationActionTypePayload',
    type: 'object',
    description: 'Payload for adding an allowed presentation action type to a selection plan',
    properties: [
        new OA\Property(property: 'order', type: 'integer', minimum: 1, description: 'Order of the action type'),
    ]
)]
class AddAllowedPresentationActionTypePayloadSchema {}

#[OA\Schema(
    schema: 'UpdateAllowedPresentationActionTypePayload',
    type: 'object',
    description: 'Payload for updating an allowed presentation action type in a selection plan',
    required: ['order'],
    properties: [
        new OA\Property(property: 'order', type: 'integer', minimum: 1, description: 'Order of the action type'),
    ]
)]
class UpdateAllowedPresentationActionTypePayloadSchema {}
