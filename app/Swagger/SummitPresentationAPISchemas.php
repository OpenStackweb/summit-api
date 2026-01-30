<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

// PRESENTATION VIDEO SCHEMAS moved to Models/PresentationVideoSchema.php

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
