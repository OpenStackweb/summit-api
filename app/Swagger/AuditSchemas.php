<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'AuditLog',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1, description: 'Unique identifier'),
        new OA\Property(property: 'created', type: 'integer', example: 1630500518, description: 'Creation timestamp (Unix epoch)'),
        new OA\Property(property: 'last_edited', type: 'integer', example: 1630500518, description: 'Last modification timestamp (Unix epoch)'),
        new OA\Property(property: 'class_name', type: 'string', example: 'SummitAuditLog', description: 'Audit log type: SummitAuditLog, SummitEventAuditLog, or SummitAttendeeBadgeAuditLog'),
        new OA\Property(property: 'action', type: 'string', example: 'UPDATED', description: 'Action performed (e.g., CREATED, UPDATED, DELETED)'),
        new OA\Property(property: 'metadata', type: 'string', example: 'Additional audit information', description: 'Metadata about the audit action', nullable: true),
        new OA\Property(property: 'user_id', type: 'integer', example: 123, description: 'ID of the user who performed the action'),
        new OA\Property(property: 'summit_id', type: 'integer', example: 45, description: 'Summit ID (for SummitAuditLog, SummitEventAuditLog, SummitAttendeeBadgeAuditLog)', nullable: true),
        new OA\Property(property: 'event_id', type: 'integer', example: 789, description: 'Event ID (for SummitEventAuditLog)', nullable: true),
        new OA\Property(property: 'attendee_badge_id', type: 'integer', example: 456, description: 'Attendee Badge ID (for SummitAttendeeBadgeAuditLog)', nullable: true),
    ]
)]
class AuditLogSchema {}

#[OA\Schema(
    schema: 'PaginatedAuditLogsResponse',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/AuditLog')
                )
            ]
        )
    ]
)]
class PaginatedAuditLogsResponseSchema {}
