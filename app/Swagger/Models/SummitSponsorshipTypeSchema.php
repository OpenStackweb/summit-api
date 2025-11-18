<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;


#[OA\Schema(
    schema: 'SummitSponsorshipType',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', example: 1),
        new OA\Property(property: 'last_edited', type: 'integer', example: 1),
        new OA\Property(property: 'widget_title', type: 'string', ),
        new OA\Property(property: 'lobby_template', type: 'string', ),
        new OA\Property(property: 'expo_hall_template', type: 'string', ),
        new OA\Property(property: 'sponsor_page_template', type: 'string', ),
        new OA\Property(property: 'event_page_template', type: 'string', ),
        new OA\Property(property: 'sponsor_page_use_disqus_widget', type: 'boolean', ),
        new OA\Property(property: 'sponsor_page_use_live_event_widget', type: 'boolean', ),
        new OA\Property(property: 'sponsor_page_use_schedule_widget', type: 'boolean', ),
        new OA\Property(property: 'sponsor_page_use_banner_widget', type: 'boolean', ),
        new OA\Property(property: 'type_id', type: 'integer', description: "SponsorshipType ID only available when NOT expanded"),
        new OA\Property(property: 'badge_image', type: 'string', ),
        new OA\Property(property: 'badge_image_alt_text', type: 'string', ),
        new OA\Property(property: 'summit_id', type: 'integer', ),
        new OA\Property(property: 'order', type: 'integer', ),
        new OA\Property(property: 'should_display_on_expo_hall_page', type: 'boolean', ),
        new OA\Property(property: 'should_display_on_lobby_page', type: 'boolean', ),
        new OA\Property(property: 'summit', ref: '#/components/schemas/Summit', description: "Summit object, only available when expanded"),
        new OA\Property(property: 'type', ref: '#/components/schemas/SponsorshipType', description: "SponsorshipType object, only available when expanded"),
    ])
]
class SummitSponsorshipTypeSchema
{
}