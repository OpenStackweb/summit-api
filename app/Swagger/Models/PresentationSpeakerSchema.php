<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PresentationSpeaker',
    type: 'object',
    description: 'Represents a speaker at a summit presentation',
    properties: [
        // Base fields (from SilverStripeSerializer)
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', description: 'Unix timestamp', example: 1640995200),
        new OA\Property(property: 'last_edited', type: 'integer', description: 'Unix timestamp', example: 1640995200),

        // PresentationSpeakerBase fields
        new OA\Property(property: 'first_name', type: 'string', example: 'John'),
        new OA\Property(property: 'last_name', type: 'string', example: 'Doe'),
        new OA\Property(property: 'title', type: 'string', nullable: true, example: 'Senior Developer'),
        new OA\Property(property: 'bio', type: 'string', nullable: true, example: 'John is an experienced cloud architect...'),
        new OA\Property(property: 'irc', type: 'string', nullable: true, example: 'johndoe'),
        new OA\Property(property: 'twitter', type: 'string', nullable: true, example: '@johndoe'),
        new OA\Property(property: 'org_has_cloud', type: 'boolean', example: true),
        new OA\Property(property: 'country', type: 'string', nullable: true, example: 'US'),
        new OA\Property(property: 'available_for_bureau', type: 'boolean', example: true),
        new OA\Property(property: 'funded_travel', type: 'boolean', example: false),
        new OA\Property(property: 'willing_to_travel', type: 'boolean', example: true),
        new OA\Property(property: 'willing_to_present_video', type: 'boolean', example: true),
        new OA\Property(property: 'member_id', type: 'integer', nullable: true, example: 42, description: 'Member ID'),
        new OA\Property(property: 'registration_request_id', type: 'integer', nullable: true, example: 10),
        new OA\Property(property: 'pic', type: 'string', format: 'uri', nullable: true, example: 'https://example.com/photos/johndoe.jpg', description: 'Profile photo URL'),
        new OA\Property(property: 'big_pic', type: 'string', format: 'uri', nullable: true, example: 'https://example.com/photos/johndoe-large.jpg', description: 'Large profile photo URL'),
        new OA\Property(property: 'company', type: 'string', nullable: true, example: 'Acme Corp'),
        new OA\Property(property: 'phone_number', type: 'string', nullable: true, example: '+1-555-1234'),
        new OA\Property(property: 'email', type: 'string', format: 'email', nullable: true, example: 'j***@example.com', description: 'May be obfuscated depending on permissions'),

        // PresentationSpeaker-specific fields
        new OA\Property(property: 'featured', type: 'boolean', example: false, description: 'Whether the speaker is featured at the summit'),
        new OA\Property(property: 'order', type: 'integer', example: 1, description: 'Order for featured speakers'),
        new OA\Property(property: 'gender', type: 'string', nullable: true, example: 'Male'),
        new OA\Property(property: 'member_external_id', type: 'integer', nullable: true, example: 12345, description: 'External user ID'),

        // Relations (arrays of IDs by default, expandable to full objects)
        new OA\Property(
            property: 'presentations',
            type: 'array',
            description: 'Array of Presentation IDs where speaker is presenting, use expand=presentations for full details',
            items: new OA\Items(oneOf: [
                new OA\Schema(type: 'integer'),
                new OA\Schema(ref: '#/components/schemas/Presentation'),
            ]),
            example: [1, 2, 3]
        ),
        new OA\Property(
            property: 'moderated_presentations',
            type: 'array',
            description: 'Array of Presentation IDs where speaker is moderator, use expand=presentations for full details',
            items: new OA\Items(oneOf: [
                new OA\Schema(type: 'integer'),
                new OA\Schema(ref: '#/components/schemas/Presentation'),
            ]),
            example: [4, 5]
        ),
        new OA\Property(
            property: 'accepted_presentations',
            type: 'array',
            description: 'Array of accepted Presentation IDs, use expand=accepted_presentations for full details',
            items: new OA\Items(oneOf: [
                new OA\Schema(type: 'integer'),
                new OA\Schema(ref: '#/components/schemas/Presentation'),
            ]),
            example: [1, 2]
        ),
        new OA\Property(
            property: 'alternate_presentations',
            type: 'array',
            description: 'Array of alternate Presentation IDs, use expand=alternate_presentations for full details',
            items: new OA\Items(oneOf: [
                new OA\Schema(type: 'integer'),
                new OA\Schema(ref: '#/components/schemas/Presentation'),
            ]),
            example: [3]
        ),
        new OA\Property(
            property: 'rejected_presentations',
            type: 'array',
            description: 'Array of rejected Presentation IDs, use expand=rejected_presentations for full details',
            items: new OA\Items(oneOf: [
                new OA\Schema(type: 'integer'),
                new OA\Schema(ref: '#/components/schemas/Presentation'),
            ]),
            example: [6]
        ),
        new OA\Property(
            property: 'affiliations',
            type: 'array',
            description: 'Array of current affiliations',
            items: new OA\Items(type: 'object'),
            example: []
        ),
        new OA\Property(
            property: 'languages',
            type: 'array',
            description: 'Array of languages the speaker knows',
            items: new OA\Items(type: 'object'),
            example: []
        ),
        new OA\Property(
            property: 'other_presentation_links',
            type: 'array',
            description: 'Array of other presentation links',
            items: new OA\Items(type: 'object'),
            example: []
        ),
        new OA\Property(
            property: 'areas_of_expertise',
            type: 'array',
            description: 'Array of areas of expertise',
            items: new OA\Items(type: 'object'),
            example: []
        ),
        new OA\Property(
            property: 'travel_preferences',
            type: 'array',
            description: 'Array of travel preferences',
            items: new OA\Items(type: 'object'),
            example: []
        ),
        new OA\Property(
            property: 'active_involvements',
            type: 'array',
            description: 'Array of active involvements',
            items: new OA\Items(type: 'object'),
            example: []
        ),
        new OA\Property(
            property: 'organizational_roles',
            type: 'array',
            description: 'Array of organizational roles',
            items: new OA\Items(type: 'object'),
            example: []
        ),
        new OA\Property(
            property: 'badge_features',
            type: 'array',
            description: 'Array of badge features from attendee tickets',
            items: new OA\Items(ref: '#/components/schemas/SummitBadgeFeatureType'),
            example: []
        ),

        // Expandable relations
        new OA\Property(property: 'member', ref: '#/components/schemas/Member', description: 'Expanded when using expand=member'),
    ]
)]
class PresentationSpeakerSchema {}
