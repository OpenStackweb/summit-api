<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Presentation',
    type: 'object',
    description: 'Represents a presentation (talk/session) at a summit event',
    properties: [
        // Base fields (from SilverStripeSerializer)
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', description: 'Unix timestamp', example: 1640995200),
        new OA\Property(property: 'last_edited', type: 'integer', description: 'Unix timestamp', example: 1640995200),

        // SummitEvent fields
        new OA\Property(property: 'title', type: 'string', example: 'Introduction to Cloud Native Architecture'),
        new OA\Property(property: 'description', type: 'string', example: 'A comprehensive overview of cloud native concepts...'),
        new OA\Property(property: 'social_description', type: 'string', nullable: true, example: 'Join us for cloud native insights!'),
        new OA\Property(property: 'start_date', type: 'integer', description: 'Unix timestamp', example: 1640995200, nullable: true),
        new OA\Property(property: 'end_date', type: 'integer', description: 'Unix timestamp', example: 1641081600, nullable: true),
        new OA\Property(property: 'location_id', type: 'integer', nullable: true, example: 10, description: 'SummitVenue or SummitVenueRoom ID'),
        new OA\Property(property: 'summit_id', type: 'integer', example: 1, description: 'Summit ID'),
        new OA\Property(property: 'type_id', type: 'integer', nullable: true, example: 5, description: 'SummitEventType ID'),
        new OA\Property(property: 'class_name', type: 'string', example: 'Presentation'),
        new OA\Property(property: 'allow_feedback', type: 'boolean', example: true),
        new OA\Property(property: 'avg_feedback_rate', type: 'number', format: 'float', example: 4.5),
        new OA\Property(property: 'is_published', type: 'boolean', example: true),
        new OA\Property(property: 'published_date', type: 'integer', description: 'Unix timestamp', example: 1640995200, nullable: true),
        new OA\Property(property: 'head_count', type: 'integer', example: 100),
        new OA\Property(property: 'track_id', type: 'integer', nullable: true, example: 3, description: 'PresentationCategory ID'),
        new OA\Property(property: 'meeting_url', type: 'string', nullable: true, example: 'https://meet.example.com/room123'),
        new OA\Property(property: 'attendance_count', type: 'integer', example: 85),
        new OA\Property(property: 'current_attendance_count', type: 'integer', example: 50),
        new OA\Property(property: 'image', type: 'string', format: 'uri', nullable: true, example: 'https://example.com/images/presentation.jpg'),
        new OA\Property(property: 'stream_thumbnail', type: 'string', format: 'uri', nullable: true),
        new OA\Property(property: 'level', type: 'string', nullable: true, example: 'Intermediate', description: 'Beginner, Intermediate, Advanced, N/A'),
        new OA\Property(property: 'created_by_id', type: 'integer', nullable: true, example: 42),
        new OA\Property(property: 'updated_by_id', type: 'integer', nullable: true, example: 42),
        new OA\Property(property: 'show_sponsors', type: 'boolean', example: false),
        new OA\Property(property: 'duration', type: 'integer', example: 3600, description: 'Duration in seconds'),
        new OA\Property(property: 'stream_is_secure', type: 'boolean', example: false),
        new OA\Property(property: 'submission_source', type: 'string', nullable: true, example: 'Admin'),
        new OA\Property(property: 'streaming_url', type: 'string', nullable: true),
        new OA\Property(property: 'streaming_type', type: 'string', nullable: true),
        new OA\Property(property: 'etherpad_link', type: 'string', nullable: true),

        // RSVP Fields
        new OA\Property(property: 'rsvp_link', type: 'string', nullable: true, example: 'https://rsvp.example.com/event123'),
        new OA\Property(property: 'rsvp_template_id', type: 'integer', nullable: true, example: 1),
        new OA\Property(property: 'rsvp_max_user_number', type: 'integer', nullable: true, example: 100),
        new OA\Property(property: 'rsvp_max_user_wait_list_number', type: 'integer', nullable: true, example: 50),
        new OA\Property(property: 'rsvp_regular_count', type: 'integer', example: 75),
        new OA\Property(property: 'rsvp_wait_count', type: 'integer', example: 10),
        new OA\Property(property: 'rsvp_external', type: 'boolean', example: false),
        new OA\Property(property: 'rsvp_type', type: 'string', nullable: true),
        new OA\Property(property: 'rsvp_capacity', type: 'string', nullable: true),

        // Presentation-specific fields
        new OA\Property(property: 'creator_id', type: 'integer', nullable: true, example: 100, description: 'Member ID of the presentation creator'),
        new OA\Property(property: 'moderator_speaker_id', type: 'integer', nullable: true, example: 200, description: 'PresentationSpeaker ID of the moderator'),
        new OA\Property(property: 'selection_plan_id', type: 'integer', nullable: true, example: 5, description: 'SelectionPlan ID'),
        new OA\Property(property: 'problem_addressed', type: 'string', nullable: true, example: 'This presentation addresses the challenge of...'),
        new OA\Property(property: 'attendees_expected_learnt', type: 'string', nullable: true, example: 'Attendees will learn how to...'),
        new OA\Property(property: 'to_record', type: 'boolean', example: true, description: 'Whether the presentation should be recorded'),
        new OA\Property(property: 'attending_media', type: 'boolean', example: false, description: 'Whether media will be attending'),
        new OA\Property(property: 'status', type: 'string', nullable: true, example: 'Received', description: 'Presentation submission status'),
        new OA\Property(property: 'progress', type: 'string', nullable: true, example: 'Summary', description: 'Submission progress stage'),
        new OA\Property(property: 'slug', type: 'string', example: 'introduction-to-cloud-native-architecture'),
        new OA\Property(property: 'selection_status', type: 'string', nullable: true, example: 'selected', description: 'Selection status (selected, unselected, etc.)'),
        new OA\Property(property: 'disclaimer_accepted_date', type: 'integer', description: 'Unix timestamp', nullable: true, example: 1640995200),
        new OA\Property(property: 'disclaimer_accepted', type: 'boolean', example: true),
        new OA\Property(property: 'custom_order', type: 'integer', example: 1),
        new OA\Property(property: 'attendee_votes_count', type: 'integer', example: 42, description: 'Number of attendee votes received'),
        new OA\Property(property: 'review_status', type: 'string', nullable: true, example: 'Reviewed', description: 'Review status of the presentation'),

        // Relations from SummitEvent (arrays of IDs by default, expandable to full objects)
        new OA\Property(
            property: 'sponsors',
            type: 'array',
            description: 'Array of Sponsor IDs, use expand=sponsors for full details',
            items: new OA\Items(oneOf: [
                new OA\Schema(type: 'integer'),
                new OA\Schema(ref: '#/components/schemas/Company'),
            ]),
            example: [1, 2, 3]
        ),
        new OA\Property(
            property: 'tags',
            type: 'array',
            description: 'Array of Tag IDs, use expand=tags for full details',
            items: new OA\Items(oneOf: [
                new OA\Schema(type: 'integer'),
                new OA\Schema(ref: '#/components/schemas/Tag'),
            ]),
            example: [1, 2, 3]
        ),
        new OA\Property(
            property: 'feedback',
            type: 'array',
            description: 'Array of SummitEventFeedback IDs, use expand=feedback for full details',
            items: new OA\Items(oneOf: [
                new OA\Schema(type: 'integer'),
                new OA\Schema(ref: '#/components/schemas/SummitEventFeedback'),
            ]),
            example: [1, 2, 3]
        ),
        new OA\Property(
            property: 'current_attendance',
            type: 'array',
            description: 'Array of SummitAttendee IDs currently attending, use expand=current_attendance for full details',
            items: new OA\Items(oneOf: [
                new OA\Schema(type: 'integer'),
                new OA\Schema(ref: '#/components/schemas/SummitAttendee'),
            ]),
            example: [1, 2, 3]
        ),
        new OA\Property(
            property: 'allowed_ticket_types',
            type: 'array',
            description: 'Array of SummitTicketType IDs allowed for this event, use expand=allowed_ticket_types for full details',
            items: new OA\Items(oneOf: [
                new OA\Schema(type: 'integer'),
                new OA\Schema(ref: '#/components/schemas/SummitTicketType'),
            ]),
            example: [1, 2, 3]
        ),

        // Presentation-specific relations
        new OA\Property(
            property: 'speakers',
            type: 'array',
            description: 'Array of PresentationSpeaker IDs, use expand=speakers for full details',
            items: new OA\Items(oneOf: [
                new OA\Schema(type: 'integer'),
                new OA\Schema(ref: '#/components/schemas/PresentationSpeaker'),
            ]),
            example: [1, 2, 3]
        ),
        new OA\Property(
            property: 'slides',
            type: 'array',
            description: 'Array of PresentationSlide IDs, use expand=slides for full details',
            items: new OA\Items(oneOf: [
                new OA\Schema(type: 'integer'),
                new OA\Schema(ref: '#/components/schemas/PresentationSlide'),
            ]),
            example: [1, 2]
        ),
        new OA\Property(
            property: 'links',
            type: 'array',
            description: 'Array of PresentationLink IDs, use expand=links for full details',
            items: new OA\Items(oneOf: [
                new OA\Schema(type: 'integer'),
                new OA\Schema(ref: '#/components/schemas/PresentationLink'),
            ]),
            example: [1, 2]
        ),
        new OA\Property(
            property: 'videos',
            type: 'array',
            description: 'Array of PresentationVideo IDs, use expand=videos for full details',
            items: new OA\Items(oneOf: [
                new OA\Schema(type: 'integer'),
                new OA\Schema(ref: '#/components/schemas/PresentationVideo'),
            ]),
            example: [1]
        ),
        new OA\Property(
            property: 'media_uploads',
            type: 'array',
            description: 'Array of PresentationMediaUpload IDs, use expand=media_uploads for full details',
            items: new OA\Items(oneOf: [
                new OA\Schema(type: 'integer'),
                new OA\Schema(ref: '#/components/schemas/PresentationMediaUpload'),
            ]),
            example: [1, 2, 3]
        ),
        new OA\Property(
            property: 'public_comments',
            type: 'array',
            description: 'Array of SummitPresentationComment IDs, use expand=public_comments for full details',
            items: new OA\Items(oneOf: [
                new OA\Schema(type: 'integer'),
                new OA\Schema(ref: '#/components/schemas/SummitPresentationComment'),
            ]),
            example: [1, 2]
        ),
        new OA\Property(
            property: 'extra_questions',
            type: 'array',
            description: 'Array of ExtraQuestionAnswer IDs, use expand=extra_questions for full details',
            items: new OA\Items(oneOf: [
                new OA\Schema(type: 'integer'),
                new OA\Schema(ref: '#/components/schemas/ExtraQuestionAnswer'),
            ]),
            example: [1, 2, 3]
        ),
        new OA\Property(
            property: 'actions',
            type: 'array',
            description: 'Array of PresentationAction IDs, use expand=actions for full details',
            items: new OA\Items(oneOf: [
                new OA\Schema(type: 'integer'),
                new OA\Schema(ref: '#/components/schemas/PresentationAction'),
            ]),
            example: [1, 2]
        ),

        // Expandable relations (objects)
        new OA\Property(property: 'creator', ref: '#/components/schemas/Member', description: 'Expanded when using expand=creator'),
        new OA\Property(property: 'moderator', description: 'PresentationSpeaker object, expanded when using expand=moderator or expand=speakers'),
        new OA\Property(property: 'selection_plan', ref: '#/components/schemas/SelectionPlan', description: 'Expanded when using expand=selection_plan'),
        new OA\Property(property: 'location', description: 'SummitVenue or SummitVenueRoom object, expanded when using expand=location'),
        new OA\Property(property: 'track', description: 'PresentationCategory object, expanded when using expand=track'),
        new OA\Property(property: 'type', description: 'SummitEventType object, expanded when using expand=type'),
        new OA\Property(property: 'rsvp_template', ref: '#/components/schemas/RSVPTemplate', description: 'RSVPTemplate object, expanded when using expand=rsvp_template'),
        new OA\Property(property: 'created_by', ref: '#/components/schemas/Member', description: 'Expanded when using expand=created_by'),
        new OA\Property(property: 'updated_by', ref: '#/components/schemas/Member', description: 'Expanded when using expand=updated_by'),
    ]
)]
class PresentationSchema {}
