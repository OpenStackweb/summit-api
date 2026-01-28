<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

// PRESENTATION VIDEO SCHEMAS

#[OA\Schema(
    schema: "PresentationVideo",
    required: ["id", "created", "last_edited"],
    properties: [
        new OA\Property(property: "id", type: "integer"),
        new OA\Property(property: "created", type: "integer", format: "int64", description: "Epoch timestamp"),
        new OA\Property(property: "last_edited", type: "integer", format: "int64", description: "Epoch timestamp"),
        new OA\Property(property: "name", type: "string", nullable: true),
        new OA\Property(property: "description", type: "string", nullable: true),
        new OA\Property(property: "display_on_site", type: "boolean"),
        new OA\Property(property: "featured", type: "boolean"),
        new OA\Property(property: "order", type: "integer"),
        new OA\Property(property: "presentation_id", type: "integer"),
        new OA\Property(property: "class_name", type: "string"),
        new OA\Property(property: "youtube_id", type: "string", nullable: true),
        new OA\Property(property: "external_url", type: "string", format: "uri", nullable: true),
        new OA\Property(property: "data_uploaded", type: "integer", format: "int64", nullable: true, description: "Epoch timestamp"),
        new OA\Property(property: "highlighted", type: "boolean"),
        new OA\Property(property: "views", type: "integer"),
    ]
)]
class PresentationVideoSchema
{
}

#[OA\Schema(
    schema: "PresentationVideoRequest",
    required: [],
    properties: [
        new OA\Property(property: "name", type: "string", nullable: true),
        new OA\Property(property: "description", type: "string", nullable: true),
        new OA\Property(property: "display_on_site", type: "boolean", nullable: true),
        new OA\Property(property: "featured", type: "boolean", nullable: true),
        new OA\Property(property: "order", type: "integer", nullable: true),
        new OA\Property(property: "youtube_id", type: "string", nullable: true),
        new OA\Property(property: "external_url", type: "string", format: "uri", nullable: true),
        new OA\Property(property: "highlighted", type: "boolean", nullable: true),
    ]
)]
class PresentationVideoRequestSchema
{
}

// PRESENTATION SLIDE SCHEMAS

#[OA\Schema(
    schema: "PresentationSlide",
    required: ["id", "created", "last_edited"],
    properties: [
        new OA\Property(property: "id", type: "integer"),
        new OA\Property(property: "created", type: "integer", format: "int64", description: "Epoch timestamp"),
        new OA\Property(property: "last_edited", type: "integer", format: "int64", description: "Epoch timestamp"),
        new OA\Property(property: "name", type: "string", nullable: true),
        new OA\Property(property: "description", type: "string", nullable: true),
        new OA\Property(property: "display_on_site", type: "boolean"),
        new OA\Property(property: "featured", type: "boolean"),
        new OA\Property(property: "order", type: "integer"),
        new OA\Property(property: "link", type: "string", nullable: true),
        new OA\Property(property: "has_file", type: "boolean"),
    ]
)]
class PresentationSlideSchema
{
}

#[OA\Schema(
    schema: "PresentationSlideRequest",
    required: [],
    properties: [
        new OA\Property(property: "name", type: "string", nullable: true),
        new OA\Property(property: "description", type: "string", nullable: true),
        new OA\Property(property: "display_on_site", type: "boolean", nullable: true),
        new OA\Property(property: "featured", type: "boolean", nullable: true),
        new OA\Property(property: "order", type: "integer", nullable: true),
        new OA\Property(property: "link", type: "string", nullable: true),
        new OA\Property(property: "file", type: "string", format: "binary", nullable: true, description: "Slide file upload"),
    ]
)]
class PresentationSlideRequestSchema
{
}

// PRESENTATION LINK SCHEMAS

#[OA\Schema(
    schema: "PresentationLink",
    required: ["id", "created", "last_edited"],
    properties: [
        new OA\Property(property: "id", type: "integer"),
        new OA\Property(property: "created", type: "integer", format: "int64", description: "Epoch timestamp"),
        new OA\Property(property: "last_edited", type: "integer", format: "int64", description: "Epoch timestamp"),
        new OA\Property(property: "name", type: "string", nullable: true),
        new OA\Property(property: "description", type: "string", nullable: true),
        new OA\Property(property: "display_on_site", type: "boolean"),
        new OA\Property(property: "featured", type: "boolean"),
        new OA\Property(property: "order", type: "integer"),
        new OA\Property(property: "link", type: "string", nullable: true),
    ]
)]
class PresentationLinkSchema
{
}

#[OA\Schema(
    schema: "PresentationLinkRequest",
    required: ["link"],
    properties: [
        new OA\Property(property: "name", type: "string", nullable: true),
        new OA\Property(property: "description", type: "string", nullable: true),
        new OA\Property(property: "display_on_site", type: "boolean", nullable: true),
        new OA\Property(property: "featured", type: "boolean", nullable: true),
        new OA\Property(property: "order", type: "integer", nullable: true),
        new OA\Property(property: "link", type: "string"),
    ]
)]
class PresentationLinkRequestSchema
{
}

// PRESENTATION MEDIA UPLOAD SCHEMAS

#[OA\Schema(
    schema: "PresentationMediaUpload",
    required: ["id", "created", "last_edited", "media_upload_type_id"],
    properties: [
        new OA\Property(property: "id", type: "integer"),
        new OA\Property(property: "created", type: "integer", format: "int64", description: "Epoch timestamp"),
        new OA\Property(property: "last_edited", type: "integer", format: "int64", description: "Epoch timestamp"),
        new OA\Property(property: "name", type: "string", nullable: true),
        new OA\Property(property: "description", type: "string", nullable: true),
        new OA\Property(property: "display_on_site", type: "boolean"),
        new OA\Property(property: "order", type: "integer"),
        new OA\Property(property: "filename", type: "string", nullable: true),
        new OA\Property(property: "media_upload_type_id", type: "integer"),
        new OA\Property(property: "public_url", type: "string", format: "uri", nullable: true),
    ]
)]
class PresentationMediaUploadSchema
{
}

#[OA\Schema(
    schema: "PresentationMediaUploadRequest",
    required: ["media_upload_type_id"],
    properties: [
        new OA\Property(property: "media_upload_type_id", type: "integer"),
        new OA\Property(property: "display_on_site", type: "boolean", nullable: true),
        new OA\Property(property: "file", type: "string", format: "binary", nullable: true, description: "Media file upload"),
    ]
)]
class PresentationMediaUploadRequestSchema
{
}

// PRESENTATION SCHEMAS

#[OA\Schema(
    schema: "Presentation",
    required: ["id", "created", "last_edited", "title"],
    properties: [
        new OA\Property(property: "id", type: "integer"),
        new OA\Property(property: "created", type: "integer", format: "int64", description: "Epoch timestamp"),
        new OA\Property(property: "last_edited", type: "integer", format: "int64", description: "Epoch timestamp"),
        new OA\Property(property: "title", type: "string"),
        new OA\Property(property: "description", type: "string"),
        new OA\Property(property: "social_description", type: "string"),
        new OA\Property(property: "start_date", type: "integer", format: "int64", description: "Epoch timestamp"),
        new OA\Property(property: "end_date", type: "integer", format: "int64", description: "Epoch timestamp"),
        new OA\Property(property: "location_id", type: "integer"),
        new OA\Property(property: "summit_id", type: "integer"),
        new OA\Property(property: "type_id", type: "integer"),
        new OA\Property(property: "class_name", type: "string"),
        new OA\Property(property: "allow_feedback", type: "boolean"),
        new OA\Property(property: "avg_feedback_rate", type: "float"),
        new OA\Property(property: "is_published", type: "boolean"),
        new OA\Property(property: "published_date", type: "ime_epoch"),
        new OA\Property(property: "head_count", type: "integer"),
        new OA\Property(property: "track_id", type: "integer"),
        new OA\Property(property: "meeting_url", type: "string"),
        new OA\Property(property: "attendance_count", type: "integer"),
        new OA\Property(property: "current_attendance_count", type: "integer"),
        new OA\Property(property: "image", type: "url"),
        new OA\Property(property: "level", type: "string"),
        new OA\Property(property: "created_by_id", type: "integer"),
        new OA\Property(property: "updated_by_id", type: "integer"),
        new OA\Property(property: "show_sponsors", type: "boolean"),
        new OA\Property(property: "duration", type: "integer"),
        new OA\Property(property: "stream_is_secure", type: "boolean"),
        new OA\Property(property: "submission_source", type: "string"),
        new OA\Property(property: "rsvp_link", type: "string"),
        new OA\Property(property: "rsvp_template_id", type: "integer"),
        new OA\Property(property: "rsvp_max_user_number", type: "integer"),
        new OA\Property(property: "rsvp_max_user_wait_list_number", type: "integer"),
        new OA\Property(property: "rsvp_regular_count", type: "integer"),
        new OA\Property(property: "rsvp_wait_count", type: "integer"),
        new OA\Property(property: "rsvp_external", type: "boolean"),
        new OA\Property(property: "rsvp_type", type: "string"),
        new OA\Property(property: "rsvp_capacity", type: "string"),

        new OA\Property(property: "creator_id", type: "integer"),
        new OA\Property(property: "moderator_speaker_id", type: "integer"),
        new OA\Property(property: "selection_plan_id", type: "integer"),
        new OA\Property(property: "problem_addressed", type: "string"),
        new OA\Property(property: "attendees_expected_learnt", type: "string"),
        new OA\Property(property: "to_record", type: "boolean"),
        new OA\Property(property: "attending_media", type: "boolean"),
        new OA\Property(property: "status", type: "string"),
        new OA\Property(property: "progress", type: "integer", description: "Progress percentage"),
        new OA\Property(property: "slug", type: "string"),
        new OA\Property(property: "selection_status", type: "string"),
        new OA\Property(property: "disclaimer_accepted_date", type: "integer", format: "int64", description: "Epoch timestamp"),
        new OA\Property(property: "disclaimer_accepted", type: "boolean"),
        new OA\Property(property: "custom_order", type: "integer"),
        new OA\Property(property: "attendee_votes_count", type: "integer"),
        new OA\Property(property: "review_status", type: "string"),

        new OA\Property(property: "speakers", type: "array", items: new OA\Items(oneOf: [
            new OA\Schema(type: "integer"),
            new OA\Schema(type: "PresentationSpeaker"),
        ]), description: "List of speakers associated with the presentation. Ids when the is present in relations, PresentationSpeaker when is present in expand."),
        new OA\Property(property: "moderator", type: "PresentationSpeaker"),
        new OA\Property(property: "creator", type: "Member"),
        new OA\Property(property: "selection_plan", type: "SelectionPlan"),
        new OA\Property(property: "slides", type: "array", items: new OA\Items(oneOf: [
            new OA\Schema(type: "PresentationSlide"),
            new OA\Schema(type: "integer"),
        ]), description: "List of slides associated with the presentation. PresentationSlide objects when present in expand, Ids when present in relations."),
        new OA\Property(property: "public_comments", type: "array", items: new OA\Items(oneOf: [
            new OA\Schema(type: "SummitPresentationComment"),
            new OA\Schema(type: "integer"),
        ]), description: "List of public comments associated with the presentation. SummitPresentationComment objects when present in expand, Ids when present in relations."),
        new OA\Property(property: "links", type: "array", items: new OA\Items(oneOf: [
            new OA\Schema(type: "PresentationLink"),
            new OA\Schema(type: "integer"),
        ]), description: "List of links associated with the presentation. PresentationLink objects when present in expand, Ids when present in relations."),
        new OA\Property(property: "videos", type: "array", items: new OA\Items(oneOf: [
            new OA\Schema(type: "PresentationVideo"),
            new OA\Schema(type: "integer"),
        ]), description: "List of videos associated with the presentation. PresentationVideo objects when present in expand, Ids when present in relations."),
        new OA\Property(property: "media_uploads", type: "array", items: new OA\Items(oneOf: [
            new OA\Schema(ref: "#/components/schemas/PresentationMediaUpload"),
            new OA\Schema(type: "integer"),
        ]), description: "List of media uploads associated with the presentation. MediaUpload objects when present in expand, Ids when present in relations."),
        new OA\Property(property: "extra_questions", type: "array", items: new OA\Items(oneOf: [
            new OA\Schema(type: "PresentationExtraQuestionAnswer"),
            new OA\Schema(type: "integer"),
        ]), description: "List of extra questions associated with the presentation. PresentationExtraQuestionAnswer objects when present in expand, Ids when present in relations."),
        new OA\Property(property: "actions", type: "array", items: new OA\Items(oneOf: [
            new OA\Schema(type: "PresentationAction"),
            new OA\Schema(type: "integer"),
        ]), description: "List of actions associated with the presentation. PresentationAction objects when present in expand, Ids when present in relations."),

        new OA\Property(property: "sponsors", type: "array", items: new OA\Items(oneOf: [
            new OA\Schema(type: "Company"),
            new OA\Schema(type: "integer"),
        ]), description: "List of sponsors associated with the presentation. Company objects when present in expand, Ids when present in relations."),
        new OA\Property(property: "tags", type: "array", items: new OA\Items(oneOf: [
            new OA\Schema(type: "Tag"),
            new OA\Schema(type: "integer"),
        ]), description: "List of tags associated with the presentation. Tag objects when present in expand, Ids when present in relations."),
        new OA\Property(property: "feedback", type: "array", items: new OA\Items(oneOf: [
            new OA\Schema(type: "SummitEventFeedback"),
            new OA\Schema(type: "integer"),
        ]), description: "List of feedback entries associated with the presentation. SummitEventFeedback objects when present in expand, Ids when present in relations."),
        new OA\Property(property: "current_attendance", type: "array", items: new OA\Items(oneOf: [
            new OA\Schema(type: "SummitEventAttendanceMetric"),
            new OA\Schema(type: "integer"),
        ]), description: "List of current attendance metrics associated with the presentation. SummitEventAttendanceMetric objects when present in expand, Ids when present in relations."),
        new OA\Property(
            property: "location",
            type: ["SummitAbstractLocation", "integer"],
            description: "List of locations associated with the presentation. SummitAbstractLocation objects when present in expand, Ids when present in relations."
        ),
        new OA\Property(
            property: "rsvp_template",
            type: "RSVPTemplate",
            description: "RSVP template associated with the presentation, only present in expand and if the presentation is has the field rsvp_template_id with a value greater than zero (0).",
        ),
        new OA\Property(
            property: "track",
            type: ["PresentationCategory", "integer"],
            description: "List of presentation categories associated with the presentation. PresentationCategory objects when present in expand, Ids when present in relations.",
        ),
        new OA\Property(
            property: "type",
            type: ["SummitEventType", "integer"],
            description: "List of presentation types associated with the presentation. SummitEventType objects when present in expand, Ids when present in relations.",
        ),
        new OA\Property(
            property: "created_by",
            type: "Member",
            description: "Member who created the presentation, only present in expand.",
        ),
        new OA\Property(
            property: "updated_by",
            type: "Member",
            description: "Member who updated the presentation, only present in expand.",
        ),
        new OA\Property(property: "allowed_ticket_types", type: "array", items: new OA\Items(oneOf: [
            new OA\Schema(type: "SummitTicketType"),
            new OA\Schema(type: "integer"),
        ]), description: "List of allowed ticket types associated with the presentation. SummitTicketType objects when present in expand, Ids when present in relations."),

    ]

)]
class PresentationSchema
{
}

#[OA\Schema(
    schema: "PresentationSubmission",
    required: [
        "id",
        "created",
        "last_edited",
        "title",
        "description",
        "social_summary",
        "level",
        "attendees_expected_learnt",
        "type_id",
        "track_id",
        "selection_plan_id",
        "selection_status",
        "progress",
        "completed",
    ],
    properties: [
        new OA\Property(property: "id", type: "integer"),
        new OA\Property(property: "created", type: "integer", format: "int64", description: "Epoch timestamp"),
        new OA\Property(property: "last_edited", type: "integer", format: "int64", description: "Epoch timestamp"),
        new OA\Property(property: "title", type: "string"),
        new OA\Property(property: "description", type: "string"),
        new OA\Property(property: "social_summary", type: "string"),
        new OA\Property(property: "level", type: "string"),
        new OA\Property(property: "attendees_expected_learnt", type: "string"),
        new OA\Property(property: "type_id", type: "integer"),
        new OA\Property(property: "track_id", type: "integer"),
        new OA\Property(property: "selection_plan_id", type: "integer"),
        new OA\Property(property: "selection_status", type: "string"),
        new OA\Property(property: "progress", type: "integer", description: "Progress percentage"),
        new OA\Property(property: "completed", type: "boolean"),
    ]
)]
class PresentationSubmissionSchema
{
}

#[OA\Schema(
    schema: "PresentationSubmissionRequest",
    required: ["title", "type_id", "track_id", "selection_plan_id"],
    properties: [
        new OA\Property(property: "title", type: "string", maxLength: 255),
        new OA\Property(property: "description", type: "string", maxLength: 2200, nullable: true),
        new OA\Property(property: "social_description", type: "string", maxLength: 300, nullable: true),
        new OA\Property(property: "social_summary", type: "string", maxLength: 100, nullable: true),
        new OA\Property(property: "attendees_expected_learnt", type: "string", maxLength: 1100, nullable: true),
        new OA\Property(property: "will_all_speakers_attend", type: "boolean"),
        new OA\Property(property: "type_id", type: "integer"),
        new OA\Property(property: "track_id", type: "integer", nullable: true),
        new OA\Property(property: "attending_media", type: "boolean", nullable: true),
        new OA\Property(property: "links", type: "array", items: new OA\Items(type: "string"), nullable: true),
        new OA\Property(property: "tags", type: "array", items: new OA\Items(type: "string"), nullable: true),
        new OA\Property(property: "extra_questions", type: "array", items: new OA\Items(type: "object"), nullable: true),
        new OA\Property(property: "disclaimer_accepted", type: "boolean"),
        new OA\Property(property: "selection_plan_id", type: "integer", nullable: false),
        new OA\Property(property: "duration", type: "integer", minimum: 0),
        new OA\Property(property: "level", type: "string", nullable: true),
        new OA\Property(property: "submission_source", type: "enum", enum: ["Submission", "Admin"]),
    ]
)]
class PresentationSubmissionRequestSchema
{
}

// PRESENTATION COMMENT SCHEMAS

#[OA\Schema(
    schema: "SummitPresentationComment",
    required: ["id", "created", "last_edited", "body"],
    properties: [
        new OA\Property(property: "id", type: "integer"),
        new OA\Property(property: "created", type: "integer", format: "int64", description: "Epoch timestamp"),
        new OA\Property(property: "last_edited", type: "integer", format: "int64", description: "Epoch timestamp"),
        new OA\Property(property: "body", type: "string"),
        new OA\Property(property: "is_public", type: "boolean"),
        new OA\Property(property: "is_activity", type: "boolean"),
        new OA\Property(property: "creator_id", type: "integer"),
        new OA\Property(property: "creator", type: "object", nullable: true),
    ]
)]
class SummitPresentationCommentSchema
{
}

#[OA\Schema(
    schema: "PresentationCommentRequest",
    required: ["body"],
    properties: [
        new OA\Property(property: "body", type: "string"),
        new OA\Property(property: "is_public", type: "boolean", nullable: true),
        new OA\Property(property: "is_activity", type: "boolean", nullable: true),
    ]
)]
class PresentationCommentRequestSchema
{
}

#[OA\Schema(
    schema: "PaginatedPresentationComments",
    properties: [
        new OA\Property(property: "total", type: "integer"),
        new OA\Property(property: "last_page", type: "integer"),
        new OA\Property(property: "current_page", type: "integer"),
        new OA\Property(property: "per_page", type: "integer"),
        new OA\Property(
            property: "data",
            type: "array",
            items: new OA\Items(ref: "#/components/schemas/SummitPresentationComment")
        ),
    ]
)]
class PaginatedPresentationCommentsSchema
{
}

// PRESENTATION VOTE SCHEMAS

#[OA\Schema(
    schema: "PresentationVote",
    required: ["id", "created", "presentation_id", "voter_id"],
    properties: [
        new OA\Property(property: "id", type: "integer"),
        new OA\Property(property: "created", type: "integer", format: "int64", description: "Epoch timestamp"),
        new OA\Property(property: "presentation_id", type: "integer"),
        new OA\Property(property: "voter_id", type: "integer"),
    ]
)]
class PresentationVoteSchema
{
}

// TRACK CHAIR SCORE SCHEMAS

#[OA\Schema(
    schema: "PresentationTrackChairScore",
    required: ["id", "created", "score_type_id", "presentation_id"],
    properties: [
        new OA\Property(property: "id", type: "integer"),
        new OA\Property(property: "created", type: "integer", format: "int64", description: "Epoch timestamp"),
        new OA\Property(property: "score_type_id", type: "integer"),
        new OA\Property(property: "presentation_id", type: "integer"),
        new OA\Property(property: "reviewer_id", type: "integer"),
    ]
)]
class PresentationTrackChairScoreSchema
{
}

// SPEAKER SCHEMAS

#[OA\Schema(
    schema: "PresentationSpeakerRequest",
    properties: [
        new OA\Property(property: "order", type: "integer", minimum: 1, nullable: true),
    ]
)]
class PresentationSpeakerRequestSchema
{
}

// MUX IMPORT SCHEMAS

#[OA\Schema(
    schema: "MuxImportRequest",
    required: ["mux_token_id", "mux_token_secret"],
    properties: [
        new OA\Property(property: "mux_token_id", type: "string"),
        new OA\Property(property: "mux_token_secret", type: "string"),
        new OA\Property(property: "email_to", type: "string", format: "email", nullable: true),
    ]
)]
class MuxImportRequestSchema
{
}

// EXTRA QUESTIONS SCHEMAS

#[OA\Schema(
    schema: "PaginatedExtraQuestionAnswers",
    properties: [
        new OA\Property(property: "total", type: "integer"),
        new OA\Property(property: "last_page", type: "integer"),
        new OA\Property(property: "current_page", type: "integer"),
        new OA\Property(property: "per_page", type: "integer"),
        new OA\Property(
            property: "data",
            type: "array",
            items: new OA\Items(type: "object")
        ),
    ]
)]
class PaginatedExtraQuestionAnswersSchema
{
}
