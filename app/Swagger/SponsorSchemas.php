<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;



#[OA\Schema(
    schema: 'SponsorCreateRequest',
    type: 'object',
    required: ['company_id', 'sponsorship_type_id'],
    properties: [
        new OA\Property(property: 'company_id', type: 'integer', example: 1),
        new OA\Property(property: 'sponsorship_type_id', type: 'integer', example: 1),
        new OA\Property(property: 'order', type: 'integer', example: 1, nullable: true),
        new OA\Property(property: 'marquee', type: 'boolean', example: false, nullable: true),
        new OA\Property(property: 'intro', type: 'string', example: 'Sponsor intro text', nullable: true),
        new OA\Property(property: 'is_published', type: 'boolean', example: true, nullable: true),
        new OA\Property(
            property: 'links',
            type: 'array',
            items: new OA\Items(type: 'object'),
            nullable: true
        ),
        new OA\Property(
            property: 'sponsorships',
            type: 'array',
            items: new OA\Items(type: 'object'),
            nullable: true
        ),
    ]
)]
class SponsorCreateRequestSchema {}

#[OA\Schema(
    schema: 'SponsorUpdateRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'company_id', type: 'integer', example: 1, nullable: true),
        new OA\Property(property: 'sponsorship_type_id', type: 'integer', example: 1, nullable: true),
        new OA\Property(property: 'order', type: 'integer', example: 1, nullable: true),
        new OA\Property(property: 'marquee', type: 'boolean', example: false, nullable: true),
        new OA\Property(property: 'intro', type: 'string', example: 'Sponsor intro text', nullable: true),
        new OA\Property(property: 'is_published', type: 'boolean', example: true, nullable: true),
        new OA\Property(
            property: 'links',
            type: 'array',
            items: new OA\Items(type: 'object'),
            nullable: true
        ),
        new OA\Property(
            property: 'sponsorships',
            type: 'array',
            items: new OA\Items(type: 'object'),
            nullable: true
        ),
    ]
)]
class SponsorUpdateRequestSchema {}


#[OA\Schema(
    schema: 'SponsorAdCreateRequest',
    type: 'object',
    required: ['text'],
    properties: [
        new OA\Property(property: 'text', type: 'string', example: 'Ad text'),
        new OA\Property(property: 'alt', type: 'string', example: 'Alt text for image', nullable: true),
        new OA\Property(property: 'link', type: 'string', example: 'https://example.com', nullable: true),
        new OA\Property(property: 'order', type: 'integer', example: 1, nullable: true),
    ]
)]
class SponsorAdCreateRequestSchema {}

#[OA\Schema(
    schema: 'SponsorAdUpdateRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'text', type: 'string', example: 'Ad text', nullable: true),
        new OA\Property(property: 'alt', type: 'string', example: 'Alt text for image', nullable: true),
        new OA\Property(property: 'link', type: 'string', example: 'https://example.com', nullable: true),
        new OA\Property(property: 'order', type: 'integer', example: 1, nullable: true),
    ]
)]
class SponsorAdUpdateRequestSchema {}


#[OA\Schema(
    schema: 'SponsorMaterialCreateRequest',
    type: 'object',
    required: ['type', 'name'],
    properties: [
        new OA\Property(property: 'type', type: 'string', example: 'Presentation', enum: ['Presentation', 'Demo', 'Handout', 'Other']),
        new OA\Property(property: 'name', type: 'string', example: 'Material Name'),
        new OA\Property(property: 'description', type: 'string', example: 'Material description', nullable: true),
        new OA\Property(property: 'order', type: 'integer', example: 1, nullable: true),
    ]
)]
class SponsorMaterialCreateRequestSchema {}

#[OA\Schema(
    schema: 'SponsorMaterialUpdateRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'type', type: 'string', example: 'Presentation', enum: ['Presentation', 'Demo', 'Handout', 'Other'], nullable: true),
        new OA\Property(property: 'name', type: 'string', example: 'Material Name', nullable: true),
        new OA\Property(property: 'description', type: 'string', example: 'Material description', nullable: true),
        new OA\Property(property: 'order', type: 'integer', example: 1, nullable: true),
    ]
)]
class SponsorMaterialUpdateRequestSchema {}


#[OA\Schema(
    schema: 'SponsorSocialNetworkCreateRequest',
    type: 'object',
    required: ['link'],
    properties: [
        new OA\Property(property: 'link', type: 'string', example: 'https://twitter.com/example'),
        new OA\Property(property: 'enabled', type: 'boolean', example: true, nullable: true),
        new OA\Property(property: 'icon_css_class', type: 'string', example: 'fab fa-twitter', nullable: true),
    ]
)]
class SponsorSocialNetworkCreateRequestSchema {}

#[OA\Schema(
    schema: 'SponsorSocialNetworkUpdateRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'link', type: 'string', example: 'https://twitter.com/example', nullable: true),
        new OA\Property(property: 'enabled', type: 'boolean', example: true, nullable: true),
        new OA\Property(property: 'icon_css_class', type: 'string', example: 'fab fa-twitter', nullable: true),
    ]
)]
class SponsorSocialNetworkUpdateRequestSchema {}

#[OA\Schema(
    schema: 'SponsorExtraQuestionCreateRequest',
    type: 'object',
    required: ['type', 'label'],
    properties: [
        new OA\Property(property: 'type', type: 'string', example: 'TEXT', enum: ['TEXT', 'CHECKBOX', 'RADIO_BUTTON', 'DROP_DOWN']),
        new OA\Property(property: 'label', type: 'string', example: 'Question Label'),
        new OA\Property(property: 'mandatory', type: 'boolean', example: false, nullable: true),
        new OA\Property(property: 'order', type: 'integer', example: 1, nullable: true),
        new OA\Property(
            property: 'values',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/ExtraQuestionValueCreateRequest'),
            nullable: true
        ),
    ]
)]
class SponsorExtraQuestionCreateRequestSchema {}

#[OA\Schema(
    schema: 'SponsorExtraQuestionUpdateRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'type', type: 'string', example: 'TEXT', enum: ['TEXT', 'CHECKBOX', 'RADIO_BUTTON', 'DROP_DOWN'], nullable: true),
        new OA\Property(property: 'label', type: 'string', example: 'Question Label', nullable: true),
        new OA\Property(property: 'mandatory', type: 'boolean', example: false, nullable: true),
        new OA\Property(property: 'order', type: 'integer', example: 1, nullable: true),
        new OA\Property(
            property: 'values',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/ExtraQuestionValueCreateRequest'),
            nullable: true
        ),
    ]
)]
class SponsorExtraQuestionUpdateRequestSchema {}

#[OA\Schema(
    schema: 'ExtraQuestionValueCreateRequest',
    type: 'object',
    required: ['value'],
    properties: [
        new OA\Property(property: 'value', type: 'string', example: 'Option 1'),
        new OA\Property(property: 'order', type: 'integer', example: 1, nullable: true),
    ]
)]
class ExtraQuestionValueCreateRequestSchema {}

#[OA\Schema(
    schema: 'ExtraQuestionValueUpdateRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'value', type: 'string', example: 'Option 1', nullable: true),
        new OA\Property(property: 'order', type: 'integer', example: 1, nullable: true),
    ]
)]
class ExtraQuestionValueUpdateRequestSchema {}

#[OA\Schema(
    schema: 'LeadReportSettingsCreateRequest',
    type: 'object',
    required: ['columns'],
    properties: [
        new OA\Property(
            property: 'columns',
            type: 'array',
            items: new OA\Items(type: 'string', example: 'first_name'),
            description: 'Array of column names to include in report'
        ),
    ]
)]
class LeadReportSettingsCreateRequestSchema {}

#[OA\Schema(
    schema: 'LeadReportSettingsUpdateRequest',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'columns',
            type: 'array',
            items: new OA\Items(type: 'string', example: 'first_name'),
            description: 'Array of column names to include in report',
            nullable: true
        ),
    ]
)]
class LeadReportSettingsUpdateRequestSchema {}

#[OA\Schema(
    schema: 'PaginatedSponsorResponse',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/Sponsor')
                )
            ]
        )
    ]
)]
class PaginatedSponsorResponseSchema {}

#[OA\Schema(
    schema: 'PaginatedSponsorV2Response',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/SponsorV2')
                )
            ]
        )
    ]
)]
class PaginatedSponsorV2ResponseSchema {}

#[OA\Schema(
    schema: 'SponsorServicesStatisticsUpsertRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'forms_qty', type: 'integer', example: 1, nullable: true),
        new OA\Property(property: 'purchases_qty', type: 'integer', example: 1, nullable: true),
        new OA\Property(property: 'pages_qty', type: 'integer', example: 1, nullable: true),
        new OA\Property(property: 'documents_qty', type: 'integer', example: 1, nullable: true)
    ]
)]
class SponsorServicesStatisticsUpsertRequestSchema {}

#[OA\Schema(
    schema: 'SponsorImportRequest',
    type: 'object',
    description: 'Request to import sponsors from a UTF-8 CSV file with company and tier columns (tiers separated by |)',
    required: ['file'],
    properties: [
        new OA\Property(property: 'file', type: 'string', format: 'binary', description: 'CSV file with company and tier columns'),
        new OA\Property(property: 'dry_run', type: 'integer', enum: [0, 1], description: 'Send 1 to plan only and write nothing, 0 (default) to import. true/false strings are rejected.'),
    ]
)]
class SponsorImportRequestSchema {}

#[OA\Schema(
    schema: 'SponsorImportReportRow',
    type: 'object',
    properties: [
        new OA\Property(property: 'row', type: 'integer', example: 2),
        new OA\Property(property: 'company_input', type: 'string', example: 'Acme Robotics'),
        new OA\Property(property: 'tier_input', type: 'string', example: 'Gold|Silver'),
        new OA\Property(property: 'action', type: 'string', enum: ['create', 'skip_existing_sponsor', 'skip_duplicate', 'error', 'not_attempted']),
        new OA\Property(
            property: 'company',
            type: 'object',
            nullable: true,
            properties: [
                new OA\Property(property: 'id', type: 'integer', nullable: true),
                new OA\Property(property: 'name', type: 'string'),
                new OA\Property(property: 'match', type: 'string', enum: ['exact', 'normalized', 'new']),
            ]
        ),
        new OA\Property(property: 'tiers', type: 'array', items: new OA\Items(type: 'string')),
        new OA\Property(property: 'sponsor_id', type: 'integer', nullable: true),
        new OA\Property(property: 'duplicate_of_row', type: 'integer', nullable: true),
        new OA\Property(property: 'candidates', type: 'array', items: new OA\Items(type: 'string')),
        new OA\Property(property: 'error', type: 'string', nullable: true),
    ]
)]
class SponsorImportReportRowSchema {}

#[OA\Schema(
    schema: 'SponsorImportReport',
    type: 'object',
    properties: [
        new OA\Property(property: 'dry_run', type: 'boolean'),
        new OA\Property(
            property: 'summary',
            type: 'object',
            properties: [
                new OA\Property(property: 'total', type: 'integer'),
                new OA\Property(property: 'sponsors_created', type: 'integer'),
                new OA\Property(property: 'companies_created', type: 'integer'),
                new OA\Property(property: 'skipped', type: 'integer'),
                new OA\Property(property: 'errors', type: 'integer'),
                new OA\Property(property: 'not_attempted', type: 'integer'),
            ]
        ),
        new OA\Property(property: 'rows', type: 'array', items: new OA\Items(ref: '#/components/schemas/SponsorImportReportRow')),
    ]
)]
class SponsorImportReportSchema {}
