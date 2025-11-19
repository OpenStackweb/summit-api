<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "PaginatedTrackTagGroupAllowedTagsResponse",
    type: "object",
    properties: [
        new OA\Property(
            property: "data",
            type: "array",
            items: new OA\Items(ref: "#/components/schemas/TrackTagGroupAllowedTag")
        ),
        new OA\Property(
            property: "total",
            type: "integer",
            example: 15
        ),
        new OA\Property(
            property: "per_page",
            type: "integer",
            example: 5
        ),
        new OA\Property(
            property: "current_page",
            type: "integer",
            example: 1
        ),
    ]
)]
class PaginatedTrackTagGroupAllowedTagsResponseSchema {}
#[OA\Schema(
    schema: "TrackTagGroup",
    type: "object",
    required: ["id", "name", "label", "is_mandatory", "summit_id"],
    properties: [
        new OA\Property(
            property: "id",
            type: "integer",
            format: "int64",
            description: "Track tag group ID",
            example: 1
        ),
        new OA\Property(
            property: "name",
            type: "string",
            maxLength: 50,
            description: "Track tag group name",
            example: "Difficulty Level"
        ),
        new OA\Property(
            property: "label",
            type: "string",
            maxLength: 50,
            description: "Display label for the tag group",
            example: "Difficulty"
        ),
        new OA\Property(
            property: "is_mandatory",
            type: "boolean",
            description: "Whether this tag group is mandatory",
            example: false
        ),
        new OA\Property(
            property: "order",
            type: "integer",
            description: "Display order of the tag group",
            example: 1
        ),
        new OA\Property(
            property: "summit_id",
            type: "integer",
            format: "int64",
            description: "Summit ID this tag group belongs to",
            example: 1
        ),
        new OA\Property(
            property: "allowed_tags",
            type: "array",
            description: "Array of allowed tag IDs. Use expand=allowed_tags to get full TrackTagGroupAllowedTag objects",
            items: new OA\Items(
                oneOf: [
                    new OA\Schema(type: "integer", format: "int64", example: 1),
                    new OA\Schema(ref: "#/components/schemas/TrackTagGroupAllowedTag")
                ]
            ),
        ),
    ]
)]
class TrackTagGroupSchema {}

// TrackTagGroupAllowedTag schema
#[OA\Schema(
    schema: "TrackTagGroupAllowedTag",
    type: "object",
    required: ["id", "tag_id", "track_tag_group_id"],
    properties: [
        new OA\Property(
            property: "id",
            type: "integer",
            format: "int64",
            description: "Allowed tag ID",
            example: 1
        ),
        new OA\Property(
            property: "tag_id",
            type: "integer",
            format: "int64",
            description: "Tag ID",
            example: 1
        ),
        new OA\Property(
            property: "track_tag_group_id",
            type: "integer",
            format: "int64",
            description: "Track tag group ID",
            example: 1
        ),
        new OA\Property(
            property: "summit_id",
            type: "integer",
            format: "int64",
            description: "Summit ID",
            example: 1
        ),
        new OA\Property(
            property: "is_default",
            type: "boolean",
            description: "Whether this tag is the default for the group",
            example: false
        ),
        new OA\Property(
            property: "tag",
            type: "object",
            description: "Tag object (when expanded=tag)",
            ref: "#/components/schemas/Tag"
        ),
        new OA\Property(
            property: "track_tag_group",
            type: "object",
            description: "Track tag group object (when expanded=track_tag_group)",
            ref: "#/components/schemas/TrackTagGroup"
        ),
    ]
)]
class TrackTagGroupAllowedTagSchema {}

// TrackTagGroupsList schema
#[OA\Schema(
    schema: "TrackTagGroupsList",
    type: "object",
    properties: [
        new OA\Property(
            property: "data",
            type: "array",
            items: new OA\Items(ref: "#/components/schemas/TrackTagGroup")
        ),
        new OA\Property(
            property: "total",
            type: "integer",
            example: 5
        ),
        new OA\Property(
            property: "per_page",
            type: "integer",
            example: 5
        ),
        new OA\Property(
            property: "current_page",
            type: "integer",
            example: 1
        ),
    ]
)]
class TrackTagGroupsListSchema {}

// PaginatedTrackTagGroupResponse schema
#[OA\Schema(
    schema: "PaginatedTrackTagGroupResponse",
    type: "object",
    properties: [
        new OA\Property(
            property: "data",
            type: "array",
            items: new OA\Items(ref: "#/components/schemas/TrackTagGroup")
        ),
        new OA\Property(
            property: "total",
            type: "integer",
            example: 15
        ),
        new OA\Property(
            property: "per_page",
            type: "integer",
            example: 5
        ),
        new OA\Property(
            property: "current_page",
            type: "integer",
            example: 1
        ),
        new OA\Property(
            property: "last_page",
            type: "integer",
            example: 3
        ),
        new OA\Property(
            property: "from",
            type: "integer",
            example: 1
        ),
        new OA\Property(
            property: "to",
            type: "integer",
            example: 5
        ),
    ]
)]
class PaginatedTrackTagGroupResponseSchema {}

// CreateTrackTagGroupRequest schema
#[OA\Schema(
    schema: "CreateTrackTagGroupRequest",
    type: "object",
    required: ["name", "label", "is_mandatory"],
    properties: [
        new OA\Property(
            property: "name",
            type: "string",
            maxLength: 50,
            description: "Track tag group name",
            example: "Difficulty Level"
        ),
        new OA\Property(
            property: "label",
            type: "string",
            maxLength: 50,
            description: "Display label",
            example: "Difficulty"
        ),
        new OA\Property(
            property: "is_mandatory",
            type: "boolean",
            description: "Whether this group is mandatory",
            example: false
        ),
        new OA\Property(
            property: "allowed_tags",
            type: "array",
            description: "Tag IDs to include in this group",
            items: new OA\Items(type: "integer", format: "int64"),
            example: [1, 2, 3]
        ),
    ]
)]
class CreateTrackTagGroupRequestSchema {}

// UpdateTrackTagGroupRequest schema
#[OA\Schema(
    schema: "UpdateTrackTagGroupRequest",
    type: "object",
    properties: [
        new OA\Property(
            property: "name",
            type: "string",
            maxLength: 50,
            description: "Track tag group name"
        ),
        new OA\Property(
            property: "label",
            type: "string",
            maxLength: 50,
            description: "Display label"
        ),
        new OA\Property(
            property: "is_mandatory",
            type: "boolean",
            description: "Whether this group is mandatory"
        ),
        new OA\Property(
            property: "order",
            type: "integer",
            minimum: 1,
            description: "Display order"
        ),
        new OA\Property(
            property: "allowed_tags",
            type: "array",
            description: "Tag IDs to include",
            items: new OA\Items(type: "integer", format: "int64")
        ),
    ]
)]
class UpdateTrackTagGroupRequestSchema {}
