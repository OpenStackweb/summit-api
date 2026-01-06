<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'SelectionPlan',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', description: 'Unix timestamp', example: 1640995200),
        new OA\Property(property: 'last_edited', type: 'integer', description: 'Unix timestamp', example: 1640995200),
        new OA\Property(property: 'name', type: 'string', example: 'Call for Presentations 2024'),
        new OA\Property(property: 'is_enabled', type: 'boolean', example: true),
        new OA\Property(property: 'is_hidden', type: 'boolean', example: false),
        new OA\Property(property: 'submission_begin_date', type: 'integer', description: 'Unix timestamp', example: 1640995200, nullable: true),
        new OA\Property(property: 'submission_end_date', type: 'integer', description: 'Unix timestamp', example: 1643587200, nullable: true),
        new OA\Property(property: 'submission_lock_down_presentation_status_date', type: 'integer', description: 'Unix timestamp', example: 1643587200, nullable: true),
        new OA\Property(property: 'max_submission_allowed_per_user', type: 'integer', example: 3),
        new OA\Property(property: 'voting_begin_date', type: 'integer', description: 'Unix timestamp', example: 1643587200, nullable: true),
        new OA\Property(property: 'voting_end_date', type: 'integer', description: 'Unix timestamp', example: 1646092800, nullable: true),
        new OA\Property(property: 'selection_begin_date', type: 'integer', description: 'Unix timestamp', example: 1646092800, nullable: true),
        new OA\Property(property: 'selection_end_date', type: 'integer', description: 'Unix timestamp', example: 1648771200, nullable: true),
        new OA\Property(property: 'summit_id', type: 'integer', description: 'Summit ID', example: 123),
        new OA\Property(property: 'allow_new_presentations', type: 'boolean', example: true),
        new OA\Property(property: 'submission_period_disclaimer', type: 'string', nullable: true, example: 'By submitting a presentation...'),
        new OA\Property(property: 'presentation_creator_notification_email_template', type: 'string', nullable: true),
        new OA\Property(property: 'presentation_moderator_notification_email_template', type: 'string', nullable: true),
        new OA\Property(property: 'presentation_speaker_notification_email_template', type: 'string', nullable: true),
        new OA\Property(property: 'type', type: 'string', example: 'Presentation', description: 'Type of selection plan'),
        new OA\Property(property: 'allow_proposed_schedules', type: 'boolean', example: false),
        new OA\Property(property: 'allow_track_change_requests', type: 'boolean', example: true),
        new OA\Property(
            property: 'track_groups',
            type: 'array',
            description: 'Array of PresentationCategoryGroup IDs, use expand=track_groups for full details',
            items: new OA\Items(type: 'integer'),
            example: [1, 2, 3]
        ),
        new OA\Property(
            property: 'extra_questions',
            type: 'array',
            description: 'Array of SummitSelectionPlanExtraQuestionType IDs, use expand=extra_questions for full details',
            items: new OA\Items(type: 'integer'),
            example: [1, 2, 3]
        ),
        new OA\Property(
            property: 'event_types',
            type: 'array',
            description: 'Array of SummitEventType IDs, use expand=event_types for full details',
            items: new OA\Items(type: 'integer'),
            example: [1, 2, 3]
        ),
        new OA\Property(
            property: 'track_chair_rating_types',
            type: 'array',
            description: 'Array of TrackChairRatingType IDs, use expand=track_chair_rating_types for full details',
            items: new OA\Items(type: 'integer'),
            example: [1, 2, 3]
        ),
        new OA\Property(
            property: 'allowed_presentation_action_types',
            type: 'array',
            description: 'Array of PresentationActionType IDs, use expand=allowed_presentation_action_types for full details',
            items: new OA\Items(type: 'integer'),
            example: [1, 2, 3]
        ),
        new OA\Property(
            property: 'allowed_presentation_questions',
            type: 'array',
            description: 'Array of allowed presentation question types',
            items: new OA\Items(type: 'string'),
            example: ['title', 'abstract', 'level']
        ),
        new OA\Property(
            property: 'allowed_presentation_editable_questions',
            type: 'array',
            description: 'Array of editable presentation question types',
            items: new OA\Items(type: 'string'),
            example: ['title', 'abstract']
        ),
    ]
)]
class SelectionPlanSchema {}
