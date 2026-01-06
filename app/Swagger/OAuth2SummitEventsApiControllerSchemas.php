<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

/**
 * Paginated Summit Events Response Schema
 */
#[OA\Schema(
    schema: 'PaginatedSummitEventsResponse',
    type: 'object',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: "#/components/schemas/SummitEvent")
                )
            ]
        )
    ]
)]
class PaginatedSummitEventsResponseSchema {}

/**
 * Paginated Summit Event Feedback Response Schema
 */
#[OA\Schema(
    schema: 'PaginatedSummitEventFeedbackResponse',
    type: 'object',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: "#/components/schemas/SummitEventFeedback")
                )
            ]
        )
    ]
)]
class PaginatedSummitEventFeedbackResponseSchema {}

/**
 * Paginated Tags Response Schema
 */
#[OA\Schema(
    schema: 'PaginatedTagsResponse',
    type: 'object',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: "#/components/schemas/Tag")
                )
            ]
        )
    ]
)]
class PaginatedTagsResponseSchema {}

/**
 * Add Event Request Schema
 */
#[OA\Schema(
    schema: 'AddSummitEventRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'title', type: 'string', description: 'Event title'),
        new OA\Property(property: 'description', type: 'string', description: 'Event description'),
        new OA\Property(property: 'social_description', type: 'string', description: 'Social media summary, the "social_summary" field'),
        new OA\Property(property: 'level', type: 'string', enum: ['Beginner', 'Intermediate', 'Advanced', 'N/A'], description: 'Experience level'),
        new OA\Property(property: 'rsvp_link', type: 'string', description: 'RSVP external link, only if rsvp_template_id is not set'),
        new OA\Property(property: 'rsvp_template_id', type: 'integer', description: 'RSVP template ID, only if rsvp_link is not set'),
        new OA\Property(property: 'streaming_url', type: 'string', description: 'Streaming URL'),
        new OA\Property(property: 'stream_is_secure', type: 'boolean'),
        new OA\Property(property: 'streaming_type', type: 'string', enum: ['VOD', 'LIVE'], description: 'Streaming type'),
        new OA\Property(property: 'etherpad_link', type: 'string', description: 'Etherpad link'),
        new OA\Property(property: 'head_count', type: 'integer', description: 'Head count'),
        new OA\Property(property: 'occupancy', type: 'integer', description: 'Occupancy'),
        new OA\Property(property: 'allow_feedback', type: 'boolean', description: 'Allow event feedback'),
        new OA\Property(property: 'show_sponsors', type: 'boolean', description: 'Show sponsors'),
        new OA\Property(property: 'allowed_ticket_types', type: 'array', items: new OA\Items(type: 'integer'), description: 'Allowed ticket types (SummitTicketType) IDs '),
        new OA\Property(property: 'submission_source', type: 'string', description: 'Submission source'),
        new OA\Property(property: 'rsvp_type', type: 'string', description: 'RSVP type'),
        new OA\Property(property: 'rsvp_max_user_number', type: 'integer', description: 'Max RSVP users'),
        new OA\Property(property: 'rsvp_max_user_wait_list_number', type: 'integer', description: 'Max waitlist users'),
        new OA\Property(property: 'type_id', type: 'integer', description: 'Event type ID'),
        new OA\Property(property: 'track_id', type: 'integer', description: 'Track/category ID'),
        new OA\Property(property: 'location_id', type: 'integer', description: 'Location ID'),
        new OA\Property(property: 'created_by_id', type: 'integer', description: 'Created by user ID'),
        new OA\Property(property: 'start_date', type: 'integer', description: 'Start date (Unix timestamp)'),
        new OA\Property(property: 'end_date', type: 'integer', description: 'End date (Unix timestamp)'),
        new OA\Property(property: 'duration', type: 'integer', description: 'Duration in seconds'),
        new OA\Property(
            property: 'tags',
            type: 'array',
            items: new OA\Items(type: 'string'),
            description: 'Array of tag names'
        ),
        new OA\Property(
            property: 'sponsors',
            type: 'array',
            items: new OA\Items(type: 'integer'),
            description: 'Array of sponsor IDs'
        ),
    ]
)]
class AddSummitEventRequestSchema {}

/**
 * Update Event Request Schema
 */
#[OA\Schema(
    schema: 'UpdateSummitEventRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'title', type: 'string', description: 'Event title'),
        new OA\Property(property: 'description', type: 'string', description: 'Event description'),
        new OA\Property(property: 'social_description', type: 'string', description: 'Social media summary, the "social_summary" field'),
        new OA\Property(property: 'level', type: 'string', enum: ['Beginner', 'Intermediate', 'Advanced', 'N/A'], description: 'Experience level'),
        new OA\Property(property: 'rsvp_link', type: 'string', description: 'RSVP external link, only if rsvp_template_id is not set'),
        new OA\Property(property: 'rsvp_template_id', type: 'integer', description: 'RSVP template ID, only if rsvp_link is not set'),
        new OA\Property(property: 'streaming_url', type: 'string', description: 'Streaming URL'),
        new OA\Property(property: 'stream_is_secure', type: 'boolean'),
        new OA\Property(property: 'streaming_type', type: 'string', enum: ['VOD', 'LIVE'], description: 'Streaming type'),
        new OA\Property(property: 'etherpad_link', type: 'string', description: 'Etherpad link'),
        new OA\Property(property: 'head_count', type: 'integer', description: 'Head count'),
        new OA\Property(property: 'occupancy', type: 'integer', description: 'Occupancy'),
        new OA\Property(property: 'allow_feedback', type: 'boolean', description: 'Allow event feedback'),
        new OA\Property(property: 'show_sponsors', type: 'boolean', description: 'Show sponsors'),
        new OA\Property(property: 'allowed_ticket_types', type: 'array', items: new OA\Items(type: 'integer'), description: 'Allowed ticket types (SummitTicketType) IDs '),
        new OA\Property(property: 'submission_source', type: 'string', description: 'Submission source'),
        new OA\Property(property: 'rsvp_type', type: 'string', description: 'RSVP type'),
        new OA\Property(property: 'rsvp_max_user_number', type: 'integer', description: 'Max RSVP users'),
        new OA\Property(property: 'rsvp_max_user_wait_list_number', type: 'integer', description: 'Max waitlist users'),
        new OA\Property(property: 'type_id', type: 'integer', description: 'Event type ID'),
        new OA\Property(property: 'track_id', type: 'integer', description: 'Track/category ID'),
        new OA\Property(property: 'location_id', type: 'integer', description: 'Location ID'),
        new OA\Property(property: 'created_by_id', type: 'integer', description: 'Created by user ID'),
        new OA\Property(property: 'start_date', type: 'integer', description: 'Start date (Unix timestamp)'),
        new OA\Property(property: 'end_date', type: 'integer', description: 'End date (Unix timestamp)'),
        new OA\Property(property: 'duration', type: 'integer', description: 'Duration in seconds'),
        new OA\Property(
            property: 'tags',
            type: 'array',
            items: new OA\Items(type: 'string'),
            description: 'Array of tag names'
        ),
        new OA\Property(
            property: 'sponsors',
            type: 'array',
            items: new OA\Items(type: 'integer'),
            description: 'Array of sponsor IDs'
        ),
    ]
)]
class UpdateSummitEventRequestSchema {}

/**
 * Publish Event Request Schema
 */
#[OA\Schema(
    schema: 'PublishSummitEventRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'location_id', type: 'integer', description: 'Location ID'),
        new OA\Property(property: 'start_date', type: 'integer', description: 'Start date (Unix timestamp)'),
        new OA\Property(property: 'end_date', type: 'integer', description: 'End date (Unix timestamp)'),
        new OA\Property(property: 'duration', type: 'integer', minimum: 0, description: 'Duration in seconds'),
    ]
)]
class PublishSummitEventRequestSchema {}

/**
 * Unpublish Events Request Schema
 */
#[OA\Schema(
    schema: 'UnpublishEventsRequest',
    type: 'object',
    required: ['events'],
    properties: [
        new OA\Property(
            property: 'events',
            type: 'array',
            items: new OA\Items(type: 'integer'),
            description: 'Array of event IDs to unpublish'
        ),
    ]
)]
class UnpublishEventsRequestSchema {}

/**
 * Update and Publish Events Request Schema
 */
#[OA\Schema(
    schema: 'UpdateAndPublishEventsRequest',
    type: 'object',
    required: ['events'],
    properties: [
        new OA\Property(
            property: 'events',
            type: 'array',
            items: new OA\Items(
                type: 'object',
                required: ['id', 'location_id', 'start_date', 'end_date'],
                properties: [
                    new OA\Property(property: 'id', type: 'integer', description: 'Event ID'),
                    new OA\Property(property: 'location_id', type: 'integer', description: 'Location ID'),
                    new OA\Property(property: 'start_date', type: 'integer', description: 'Start date (Unix timestamp)'),
                    new OA\Property(property: 'end_date', type: 'integer', description: 'End date (Unix timestamp)'),
                ]
            ),
            description: 'Array of event objects to update and publish'
        ),
    ]
)]
class UpdateAndPublishEventsRequestSchema {}

/**
 * Update Events Request Schema
 */
#[OA\Schema(
    schema: 'UpdateEventsRequest',
    type: 'object',
    required: ['events'],
    properties: [
        new OA\Property(
            property: 'events',
            type: 'array',
            items: new OA\Items(
                type: 'object',
                required: ['id'],
                properties: [
                    new OA\Property(property: 'id', type: 'integer', description: 'Event ID'),
                    new OA\Property(property: 'title', type: 'string', description: 'Event title'),
                    new OA\Property(property: 'description', type: 'string', description: 'Event description'),
                    new OA\Property(property: 'location_id', type: 'integer', description: 'Location ID'),
                    new OA\Property(property: 'start_date', type: 'integer', description: 'Start date (Unix timestamp)'),
                    new OA\Property(property: 'end_date', type: 'integer', description: 'End date (Unix timestamp)'),
                ]
            ),
            description: 'Array of event objects to update'
        ),
    ]
)]
class UpdateEventsRequestSchema {}

/**
 * Add Event Feedback Request Schema
 */
#[OA\Schema(
    schema: 'AddEventFeedbackRequest',
    type: 'object',
    required: ['rate'],
    properties: [
        new OA\Property(property: 'rate', type: 'integer', minimum: 0, maximum: 5, description: 'Rating from 0 to 5'),
        new OA\Property(property: 'note', type: 'string', maxLength: 500, description: 'Optional feedback note'),
    ]
)]
class AddEventFeedbackRequestSchema {}

/**
 * Share Event By Email Request Schema
 */
#[OA\Schema(
    schema: 'ShareEventByEmailRequest',
    type: 'object',
    required: ['from', 'to'],
    properties: [
        new OA\Property(property: 'from', type: 'string', format: 'email', description: 'Sender email address'),
        new OA\Property(property: 'to', type: 'string', format: 'email', description: 'Recipient email address'),
        new OA\Property(property: 'event_uri', type: 'string', format: 'uri', description: 'Event URI'),
    ]
)]
class ShareEventByEmailRequestSchema {}

/**
 * Update Event Live Info Request Schema
 */
#[OA\Schema(
    schema: 'UpdateEventLiveInfoRequest',
    type: 'object',
    required: ['streaming_url', 'streaming_type'],
    properties: [
        new OA\Property(property: 'streaming_url', type: 'string', format: 'uri', description: 'Streaming URL'),
        new OA\Property(property: 'streaming_type', type: 'string', enum: ['VOD', 'LIVE'], description: 'Streaming type'),
    ]
)]
class UpdateEventLiveInfoRequestSchema {}

/**
 * Set Overflow Request Schema
 */
#[OA\Schema(
    schema: 'SetOverflowRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'overflow_streaming_url', type: 'string', format: 'uri', description: 'Overflow streaming URL'),
    ]
)]
class SetOverflowRequestSchema {}

/**
 * Clear Overflow Request Schema
 */
#[OA\Schema(
    schema: 'ClearOverflowRequest',
    type: 'object',
    properties: []
)]
class ClearOverflowRequestSchema {}

/**
 * Import Events CSV Request Schema
 */
#[OA\Schema(
    schema: 'ImportEventDataRequest',
    type: 'object',
    required: ['file', 'send_speaker_email'],
    properties: [
        new OA\Property(property: 'file', type: 'string', format: 'binary', description: 'CSV file to import'),
        new OA\Property(property: 'send_speaker_email', type: 'boolean', description: 'Send email notifications to speakers'),
    ]
)]
class ImportEventDataRequestSchema {}

/**
 * Paginated Summit Schedule Empty Spots Response Schema
 */
#[OA\Schema(
    schema: 'PaginatedSummitScheduleEmptySpotsResponse',
    type: 'object',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: "#/components/schemas/SummitScheduleEmptySpot")
                )
            ]
        )
    ]
)]
class PaginatedSummitScheduleEmptySpotsResponseSchema {}

/**
 * Summit Event Secure Stream Response Schema
 */
#[OA\Schema(
    schema: 'SummitEventSecureStreamResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', description: 'Event ID'),
        new OA\Property(property: 'title', type: 'string', description: 'Event title'),
        new OA\Property(property: 'jwt', type: 'string', description: 'JWT token for secure streaming'),
        new OA\Property(property: 'streaming_url', type: 'string', description: 'Streaming URL'),
    ]
)]
class SummitEventSecureStreamResponseSchema {}

/**
 * Summit Event Overflow Stream Response Schema
 */
#[OA\Schema(
    schema: 'SummitEventOverflowStreamResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', description: 'Event ID'),
        new OA\Property(property: 'title', type: 'string', description: 'Event title'),
        new OA\Property(property: 'overflow_streaming_url', type: 'string', description: 'Overflow streaming URL'),
    ]
)]
class SummitEventOverflowStreamResponseSchema {}

/**
 * Summit Event Streaming Info Response Schema
 */
#[OA\Schema(
    schema: 'SummitEventStreamingInfoResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', description: 'Event ID'),
        new OA\Property(property: 'title', type: 'string', description: 'Event title'),
        new OA\Property(property: 'streaming_url', type: 'string', description: 'Streaming URL'),
        new OA\Property(property: 'streaming_type', type: 'string', enum: ['VOD', 'LIVE'], description: 'Streaming type'),
    ]
)]
class SummitEventStreamingInfoResponseSchema {}
