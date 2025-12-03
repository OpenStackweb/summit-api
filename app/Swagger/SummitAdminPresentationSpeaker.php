<?php

namespace App\Swagger\Summit;

use OpenApi\Attributes as OA;

class SummitAdminPresentationSpeaker
{
    #[OA\Schema(
        schema: 'SummitAdminPresentationSpeaker',
        allOf: [
            new OA\Schema(ref: '#/components/schemas/SummitPresentationSpeaker'),
            new OA\Schema(
                type: 'object',
                properties: [
                    new OA\Property(
                        property: 'notes',
                        type: 'string',
                        description: 'Internal notes (admin only)'
                    ),
                    new OA\Property(
                        property: 'member',
                        description: 'Member ID or expanded Member object',
                        oneOf: [
                            new OA\Schema(type: 'integer'),
                            new OA\Schema(type: 'object', title: 'Member')
                        ],
                        nullable: true
                    ),
                    new OA\Property(
                        property: 'accepted_presentations',
                        type: 'array',
                        description: 'Accepted presentation IDs or expanded Presentation objects',
                        items: new OA\Items(oneOf: [
                            new OA\Schema(type: 'integer'),
                            new OA\Schema(type: 'object', title: 'Presentation')
                        ])
                    ),
                    new OA\Property(
                        property: 'alternate_presentations',
                        type: 'array',
                        description: 'Alternate presentation IDs or expanded Presentation objects',
                        items: new OA\Items(oneOf: [
                            new OA\Schema(type: 'integer'),
                            new OA\Schema(type: 'object', title: 'Presentation')
                        ])
                    ),
                    new OA\Property(
                        property: 'rejected_presentations',
                        type: 'array',
                        description: 'Rejected presentation IDs or expanded Presentation objects',
                        items: new OA\Items(oneOf: [
                            new OA\Schema(type: 'integer'),
                            new OA\Schema(type: 'object', title: 'Presentation')
                        ])
                    ),
                    new OA\Property(
                        property: 'presentations',
                        type: 'array',
                        description: 'Presentation IDs or expanded Presentation objects',
                        items: new OA\Items(oneOf: [
                            new OA\Schema(type: 'integer'),
                            new OA\Schema(type: 'object', title: 'Presentation')
                        ])
                    ),
                    new OA\Property(
                        property: 'moderated_presentations',
                        type: 'array',
                        description: 'Moderated presentation IDs or expanded Presentation objects',
                        items: new OA\Items(oneOf: [
                            new OA\Schema(type: 'integer'),
                            new OA\Schema(type: 'object', title: 'Presentation')
                        ])
                    ),
                    new OA\Property(
                        property: 'affiliations',
                        type: 'array',
                        description: 'Affiliation objects (can be expanded)',
                        items: new OA\Items(type: 'object', title: 'Affiliation')
                    ),
                    new OA\Property(
                        property: 'languages',
                        type: 'array',
                        description: 'Language IDs or expanded Language objects',
                        items: new OA\Items(oneOf: [
                            new OA\Schema(type: 'integer'),
                            new OA\Schema(type: 'object', title: 'Language')
                        ])
                    ),
                    new OA\Property(
                        property: 'other_presentation_links',
                        type: 'array',
                        description: 'Other presentation link objects',
                        items: new OA\Items(type: 'object', title: 'PresentationLink')
                    ),
                    new OA\Property(
                        property: 'areas_of_expertise',
                        type: 'array',
                        description: 'Area of expertise IDs or expanded objects',
                        items: new OA\Items(oneOf: [
                            new OA\Schema(type: 'string'),
                            new OA\Schema(type: 'object', title: 'AreaOfExpertise')
                        ])
                    ),
                    new OA\Property(
                        property: 'travel_preferences',
                        type: 'array',
                        description: 'Travel preference IDs or expanded objects',
                        items: new OA\Items(oneOf: [
                            new OA\Schema(type: 'string'),
                            new OA\Schema(type: 'object', title: 'TravelPreference')
                        ])
                    ),
                    new OA\Property(
                        property: 'organizational_roles',
                        type: 'array',
                        description: 'Organizational role IDs or expanded objects',
                        items: new OA\Items(oneOf: [
                            new OA\Schema(type: 'integer'),
                            new OA\Schema(type: 'object', title: 'OrganizationalRole')
                        ])
                    ),
                    new OA\Property(
                        property: 'active_involvements',
                        type: 'array',
                        description: 'Active involvement IDs or expanded objects',
                        items: new OA\Items(oneOf: [
                            new OA\Schema(type: 'integer'),
                            new OA\Schema(type: 'object', title: 'ActiveInvolvement')
                        ])
                    ),
                    new OA\Property(
                        property: 'badge_features',
                        type: 'array',
                        description: 'Badge feature objects',
                        items: new OA\Items(type: 'object', title: 'BadgeFeature')
                    ),
                ]
            )
        ]
    )]
    public function model() {}
}