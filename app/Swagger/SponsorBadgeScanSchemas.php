<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'SponsorBadgeScan',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'created', type: 'integer'),
        new OA\Property(property: 'last_edited', type: 'integer'),
        new OA\Property(property: 'qr_code', type: 'string'),
        new OA\Property(property: 'scan_date', type: 'integer', description: 'Unix timestamp'),
        new OA\Property(property: 'notes', type: 'string', nullable: true),
        new OA\Property(property: 'sponsor_id', type: 'integer', description: 'Sponsor ID. Use expand=sponsor to get full object'),
        new OA\Property(property: 'scanned_by_id', type: 'integer', description: 'User ID who scanned. Use expand=scanned_by to get full object'),
        new OA\Property(property: 'badge_id', type: 'integer', description: 'Badge ID. Use expand=badge to get full object'),
        new OA\Property(
            property: 'extra_questions',
            type: 'array',
            items: new OA\Items(type: 'integer'),
            description: 'Array of extra question answer IDs. Use expand=extra_questions to get full objects. Use relations=extra_questions to include'
        ),
    ]
)]
class SponsorBadgeScanSchemas {}

#[OA\Schema(
    schema: 'SponsorBadgeScanCSV',
    type: 'object',
    description: 'CSV export format for badge scans',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'scan_date', type: 'string', description: 'Formatted date'),
        new OA\Property(property: 'scanned_by', type: 'string', description: 'Full name of scanner'),
        new OA\Property(property: 'qr_code', type: 'string'),
        new OA\Property(property: 'sponsor_id', type: 'integer'),
        new OA\Property(property: 'user_id', type: 'integer'),
        new OA\Property(property: 'badge_id', type: 'integer'),
        new OA\Property(property: 'attendee_first_name', type: 'string'),
        new OA\Property(property: 'attendee_last_name', type: 'string'),
        new OA\Property(property: 'attendee_email', type: 'string', format: 'email'),
        new OA\Property(property: 'attendee_company', type: 'string'),
        new OA\Property(property: 'notes', type: 'string'),
        new OA\Property(
            property: 'extra_questions',
            type: 'object',
            description: 'Dynamic properties based on summit extra questions. Each question becomes a column'
        ),
    ]
)]
class SponsorBadgeScanCSVSchemas {}

#[OA\Schema(
    schema: 'SponsorUserInfoGrant',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'created', type: 'integer'),
        new OA\Property(property: 'last_edited', type: 'integer'),
        new OA\Property(property: 'scan_date', type: 'integer', description: 'Unix timestamp (created date)'),
        new OA\Property(property: 'sponsor_id', type: 'integer'),
        new OA\Property(property: 'allowed_user_id', type: 'integer', description: 'User ID who was granted access'),
        new OA\Property(property: 'attendee_first_name', type: 'string'),
        new OA\Property(property: 'attendee_last_name', type: 'string'),
        new OA\Property(property: 'attendee_email', type: 'string', format: 'email'),
        new OA\Property(property: 'attendee_company', type: 'string', nullable: true),
    ]
)]
class SponsorUserInfoGrantSchemas {}

#[OA\Schema(
    schema: 'BadgeScanAddRequest',
    type: 'object',
    required: ['qr_code', 'scan_date'],
    properties: [
        new OA\Property(property: 'qr_code', type: 'string', description: 'Attendee QR code'),
        new OA\Property(property: 'scan_date', type: 'integer', description: 'Scan date (Unix timestamp)'),
        new OA\Property(property: 'notes', type: 'string', description: 'Optional notes', maxLength: 1024, nullable: true),
        new OA\Property(
            property: 'extra_questions',
            type: 'array',
            items: new OA\Items(
                type: 'object',
                properties: [
                    new OA\Property(property: 'question_id', type: 'integer'),
                    new OA\Property(property: 'answer', type: 'string')
                ]
            ),
            nullable: true
        ),
    ]
)]
class BadgeScanAddRequestSchema {}

#[OA\Schema(
    schema: 'BadgeScanUpdateRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'notes', type: 'string', description: 'Optional notes', maxLength: 1024, nullable: true),
        new OA\Property(
            property: 'extra_questions',
            type: 'array',
            items: new OA\Items(
                type: 'object',
                properties: [
                    new OA\Property(property: 'question_id', type: 'integer'),
                    new OA\Property(property: 'answer', type: 'string')
                ]
            ),
            nullable: true
        ),
    ]
)]
class BadgeScanUpdateRequestSchema {}

#[OA\Schema(
    schema: 'BadgeScanCheckInRequest',
    type: 'object',
    required: ['qr_code'],
    properties: [
        new OA\Property(property: 'qr_code', type: 'string', description: 'Attendee QR code'),
    ]
)]
class BadgeScanCheckInRequestSchema {}

#[OA\Schema(
    schema: 'PaginatedBadgeScansResponse',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/SponsorBadgeScan')
                )
            ]
        )
    ]
)]
class PaginatedBadgeScansResponseSchema {}