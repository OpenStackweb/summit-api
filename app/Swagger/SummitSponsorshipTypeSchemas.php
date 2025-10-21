<?php
namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    title: "Summit Sponsorship Type",
    description: "Summit Sponsorship Type Schema",
    type: "object",
    properties: [
        new OA\Property(property: "id", type: "integer", format: "int64"),
        new OA\Property(property: "widget_title", type: "string"),
        new OA\Property(property: "lobby_template", type: "string"),
        new OA\Property(property: "expo_hall_template", type: "string"),
        new OA\Property(property: "sponsor_page_template", type: "string"),
        new OA\Property(property: "event_page_template", type: "string"),
        new OA\Property(property: "sponsor_page_use_disqus_widget", type: "boolean"),
        new OA\Property(property: "sponsor_page_use_live_event_widget", type: "boolean"),
        new OA\Property(property: "sponsor_page_use_schedule_widget", type: "boolean"),
        new OA\Property(property: "sponsor_page_use_banner_widget", type: "boolean"),
        new OA\Property(property: "type_id", type: "integer", format: "int64"),
        new OA\Property(property: "badge_image", type: "string"),
        new OA\Property(property: "badge_image_alt_text", type: "string"),
        new OA\Property(property: "summit_id", type: "integer", format: "int64"),
        new OA\Property(property: "order", type: "integer", format: "int32"),
        new OA\Property(property: "should_display_on_expo_hall_page", type: "boolean"),
        new OA\Property(property: "should_display_on_lobby_page", type: "boolean"),
    ]
)]
class SummitSponsorshipTypeSchemas {}