<?php

namespace App\Swagger\Summit;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "SummitSpeakerPublic",
    type: "object",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "title", type: "string", nullable: true, example: "Dr."),
        new OA\Property(property: "first_name", type: "string", example: "John"),
        new OA\Property(property: "last_name", type: "string", example: "Doe"),
        new OA\Property(property: "bio", type: "string", nullable: true),
        new OA\Property(property: "email", type: "string", format: "email", nullable: true),
        new OA\Property(property: "twitter", type: "string", nullable: true),
        new OA\Property(property: "irc", type: "string", nullable: true),
        new OA\Property(property: "pic", type: "string", format: "uri", nullable: true),
        new OA\Property(property: "big_pic", type: "string", format: "uri", nullable: true),
        new OA\Property(property: "company", type: "string", nullable: true),
        new OA\Property(property: "country", type: "string", nullable: true),
        new OA\Property(property: "phone_number", type: "string", nullable: true),
        new OA\Property(property: "available_for_bureau", type: "boolean"),
        new OA\Property(property: "funded_travel", type: "boolean"),
        new OA\Property(property: "willing_to_travel", type: "boolean"),
        new OA\Property(property: "willing_to_present_video", type: "boolean"),
        new OA\Property(property: "org_has_cloud", type: "boolean"),
        new OA\Property(property: "member_id", type: "integer", nullable: true),
        new OA\Property(property: "member_external_id", type: "integer", nullable: true),
        new OA\Property(property: "featured", type: "boolean"),
        new OA\Property(property: "order", type: "integer", nullable: true),
        new OA\Property(property: "created", type: "integer", format: "int64"),
        new OA\Property(property: "last_edited", type: "integer", format: "int64"),
    ]
)]
class SummitSpeakerPublic {}

#[OA\Schema(
    schema: "SummitSpeakerPrivate",
    type: "object",
    allOf: [
        new OA\Schema(ref: '#/components/schemas/SummitSpeakerPublic'),
        new OA\Schema(
            type: "object",
            properties: [
                new OA\Property(property: "notes", type: "string", nullable: true),
                new OA\Property(
                    property: "member",
                    description: "Member ID or expanded Member object",
                    oneOf: [
                        new OA\Schema(type: "integer"),
                        new OA\Schema(type: "object", title: "Member")
                    ],
                    nullable: true
                ),
                new OA\Property(
                    property: "accepted_presentations",
                    type: "array",
                    description: "Accepted presentation IDs or expanded Presentation objects",
                    items: new OA\Items(oneOf: [
                        new OA\Schema(type: "integer"),
                        new OA\Schema(type: "object", title: "Presentation")
                    ])
                ),
                new OA\Property(
                    property: "alternate_presentations",
                    type: "array",
                    description: "Alternate presentation IDs or expanded Presentation objects",
                    items: new OA\Items(oneOf: [
                        new OA\Schema(type: "integer"),
                        new OA\Schema(type: "object", title: "Presentation")
                    ])
                ),
                new OA\Property(
                    property: "rejected_presentations",
                    type: "array",
                    description: "Rejected presentation IDs or expanded Presentation objects",
                    items: new OA\Items(oneOf: [
                        new OA\Schema(type: "integer"),
                        new OA\Schema(type: "object", title: "Presentation")
                    ])
                ),
                new OA\Property(
                    property: "presentations",
                    type: "array",
                    description: "Presentation IDs or expanded Presentation objects",
                    items: new OA\Items(oneOf: [
                        new OA\Schema(type: "integer"),
                        new OA\Schema(type: "object", title: "Presentation")
                    ])
                ),
                new OA\Property(
                    property: "moderated_presentations",
                    type: "array",
                    description: "Moderated presentation IDs or expanded Presentation objects",
                    items: new OA\Items(oneOf: [
                        new OA\Schema(type: "integer"),
                        new OA\Schema(type: "object", title: "Presentation")
                    ])
                ),
                new OA\Property(
                    property: "affiliations",
                    type: "array",
                    description: "Affiliation objects (can be expanded)",
                    items: new OA\Items(type: "object", title: "Affiliation")
                ),
                new OA\Property(
                    property: "languages",
                    type: "array",
                    description: "Language IDs or expanded Language objects",
                    items: new OA\Items(oneOf: [
                        new OA\Schema(type: "integer"),
                        new OA\Schema(type: "object", title: "Language")
                    ])
                ),
                new OA\Property(
                    property: "other_presentation_links",
                    type: "array",
                    description: "Other presentation link objects",
                    items: new OA\Items(type: "object", title: "PresentationLink")
                ),
                new OA\Property(
                    property: "areas_of_expertise",
                    type: "array",
                    description: "Area of expertise IDs or expanded objects",
                    items: new OA\Items(oneOf: [
                        new OA\Schema(type: "string"),
                        new OA\Schema(type: "object", title: "AreaOfExpertise")
                    ])
                ),
                new OA\Property(
                    property: "travel_preferences",
                    type: "array",
                    description: "Travel preference IDs or expanded objects",
                    items: new OA\Items(oneOf: [
                        new OA\Schema(type: "string"),
                        new OA\Schema(type: "object", title: "TravelPreference")
                    ])
                ),
                new OA\Property(
                    property: "organizational_roles",
                    type: "array",
                    description: "Organizational role IDs or expanded objects",
                    items: new OA\Items(oneOf: [
                        new OA\Schema(type: "integer"),
                        new OA\Schema(type: "object", title: "OrganizationalRole")
                    ])
                ),
                new OA\Property(
                    property: "active_involvements",
                    type: "array",
                    description: "Active involvement IDs or expanded objects",
                    items: new OA\Items(oneOf: [
                        new OA\Schema(type: "integer"),
                        new OA\Schema(type: "object", title: "ActiveInvolvement")
                    ])
                ),
                new OA\Property(
                    property: "badge_features",
                    type: "array",
                    description: "Badge feature objects",
                    items: new OA\Items(type: "object", title: "BadgeFeature")
                ),
            ]
        ),
    ]
)]
class SummitSpeakerPrivate {}

#[OA\Schema(
    schema: "SummitSpeakerCreateRequest",
    type: "object",
    required: ["title", "first_name", "last_name"],
    properties: [
        new OA\Property(property: "title", type: "string", maxLength: 100, example: "Dr."),
        new OA\Property(property: "first_name", type: "string", maxLength: 100, example: "John"),
        new OA\Property(property: "last_name", type: "string", maxLength: 100, example: "Doe"),
        new OA\Property(property: "bio", type: "string"),
        new OA\Property(property: "notes", type: "string"),
        new OA\Property(property: "irc", type: "string", maxLength: 50),
        new OA\Property(property: "twitter", type: "string", maxLength: 50),
        new OA\Property(property: "email", type: "string", format: "email", maxLength: 50),
        new OA\Property(property: "funded_travel", type: "boolean"),
        new OA\Property(property: "willing_to_travel", type: "boolean"),
        new OA\Property(property: "willing_to_present_video", type: "boolean"),
        new OA\Property(property: "org_has_cloud", type: "boolean"),
        new OA\Property(property: "available_for_bureau", type: "boolean"),
        new OA\Property(property: "country", type: "string"),
        new OA\Property(property: "languages", type: "array", items: new OA\Items(type: "integer")),
        new OA\Property(property: "areas_of_expertise", type: "array", items: new OA\Items(type: "string")),
        new OA\Property(property: "other_presentation_links", type: "array", items: new OA\Items(type: "object")),
        new OA\Property(property: "travel_preferences", type: "array", items: new OA\Items(type: "string")),
        new OA\Property(property: "organizational_roles", type: "array", items: new OA\Items(type: "integer")),
        new OA\Property(property: "other_organizational_rol", type: "string", maxLength: 255),
        new OA\Property(property: "active_involvements", type: "array", items: new OA\Items(type: "integer")),
        new OA\Property(property: "company", type: "string", maxLength: 255),
        new OA\Property(property: "phone_number", type: "string", maxLength: 255),
    ]
)]
class SummitSpeakerCreateRequest {}

#[OA\Schema(
    schema: "SummitSpeakerUpdateRequest",
    type: "object",
    required: ["title", "first_name", "last_name"],
    properties: [
        new OA\Property(property: "title", type: "string", maxLength: 100),
        new OA\Property(property: "first_name", type: "string", maxLength: 100),
        new OA\Property(property: "last_name", type: "string", maxLength: 100),
        new OA\Property(property: "bio", type: "string"),
        new OA\Property(property: "notes", type: "string"),
        new OA\Property(property: "irc", type: "string", maxLength: 50),
        new OA\Property(property: "twitter", type: "string", maxLength: 50),
        new OA\Property(property: "email", type: "string", format: "email", maxLength: 50),
        new OA\Property(property: "on_site_phone", type: "string", maxLength: 50),
        new OA\Property(property: "registered", type: "boolean"),
        new OA\Property(property: "is_confirmed", type: "boolean"),
        new OA\Property(property: "checked_in", type: "boolean"),
        new OA\Property(property: "registration_code", type: "string"),
        new OA\Property(property: "available_for_bureau", type: "boolean"),
        new OA\Property(property: "funded_travel", type: "boolean"),
        new OA\Property(property: "willing_to_travel", type: "boolean"),
        new OA\Property(property: "willing_to_present_video", type: "boolean"),
        new OA\Property(property: "org_has_cloud", type: "boolean"),
        new OA\Property(property: "country", type: "string"),
        new OA\Property(property: "languages", type: "array", items: new OA\Items(type: "integer")),
        new OA\Property(property: "areas_of_expertise", type: "array", items: new OA\Items(type: "string")),
        new OA\Property(property: "other_presentation_links", type: "array", items: new OA\Items(type: "object")),
        new OA\Property(property: "travel_preferences", type: "array", items: new OA\Items(type: "string")),
        new OA\Property(property: "organizational_roles", type: "array", items: new OA\Items(type: "integer")),
        new OA\Property(property: "other_organizational_rol", type: "string", maxLength: 255),
        new OA\Property(property: "active_involvements", type: "array", items: new OA\Items(type: "integer")),
        new OA\Property(property: "company", type: "string", maxLength: 255),
        new OA\Property(property: "phone_number", type: "string", maxLength: 255),
    ]
)]
class SummitSpeakerUpdateRequest {}

#[OA\Schema(
    schema: "PaginatedPresentationSpeakersResponse",
    type: "object",
    allOf: [
        new OA\Schema(ref: "#/components/schemas/PaginateDataSchemaResponse"),
        new OA\Schema(
            type: "object",
            properties: [
                new OA\Property(
                    property: "data",
                    type: "array",
                    items: new OA\Items(
                        oneOf: [
                            new OA\Schema(ref: "#/components/schemas/SummitPresentationSpeaker"),
                            new OA\Schema(ref: "#/components/schemas/AdminPresentationSpeaker"),
                        ]
                    )
                )
            ]
        )
    ]
)]
class PaginatedPresentationSpeakersResponseSchema {}

#[OA\Schema(
    schema: "PaginatedSummitPresentationSpeakersResponsePublic",
    type: "object",
    allOf: [
        new OA\Schema(ref: "#/components/schemas/PaginateDataSchemaResponse"),
        new OA\Schema(
            type: "object",
            properties: [
                new OA\Property(
                    property: "data",
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/SummitPresentationSpeaker"),
                )
            ]
        )
    ]
)]
class PaginatedSummitPresentationSpeakersResponsePublicSchema {}
