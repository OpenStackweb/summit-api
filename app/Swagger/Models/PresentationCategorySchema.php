<?php
namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "PresentationCategory",
    description: "Summit Track/Presentation Category. Expandable relations: , allowed_tags, allowed_access_levels, extra_questions, proposed_schedule_allowed_locations, parent, subtracks",
    type: "object",
    properties: [
        new OA\Property(property: "id", description: "Track ID", type: "integer", format: "int64"),
        new OA\Property(property: "name", description: "Track Name", type: "string"),
        new OA\Property(property: "description", description: "Track Description", type: "string"),
        new OA\Property(property: "code", description: "Track Code", type: "string"),
        new OA\Property(property: "group_name", description: "Track Group Name", type: "string"),
        new OA\Property(property: "color", description: "Track Color", type: "string"),
        new OA\Property(property: "session_count", description: "Number of Sessions", type: "integer"),
        new OA\Property(property: "voting_visible", description: "Is Voting Visible", type: "boolean"),
        new OA\Property(property: "chair_visible", description: "Is Chair Visible", type: "boolean"),
        new OA\Property(property: "has_parent", description: "Has Parent Track", type: "boolean"),
        new OA\Property(property: "has_subtracks", description: "Has Sub-Tracks", type: "boolean"),
        new OA\Property(property: "has_proposed_schedule_allowed_locations", description: "Has Proposed Schedule Allowed Locations", type: "boolean"),
        new OA\Property(property: "created", description: "Creation Timestamp", type: "integer", format: "int64"),
        new OA\Property(property: "last_edited", description: "Last Edit Timestamp", type: "integer", format: "int64"),
        new OA\Property(
            property: "icon",
            type: "object",
            description: "Track icon (see File schema)"
        ),
    ]
)]
class PresentationCategorySchema {}
