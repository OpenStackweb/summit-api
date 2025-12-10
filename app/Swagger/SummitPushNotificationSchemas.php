<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

/**
 * Summit Push Notification Schema Definitions
 */
class SummitPushNotificationRequest
{
    #[OA\Schema(
        schema: 'SummitPushNotificationRequest',
        type: 'object',
        required: ['channel', 'message'],
        properties: [
            new OA\Property(property: 'channel', type: 'string', description: 'Notification channel (EVERYONE, SPEAKERS, ATTENDEES, MEMBERS, SUMMIT, EVENT, GROUP)'),
            new OA\Property(property: 'message', type: 'string', description: 'Notification message'),
            new OA\Property(property: 'priority', type: 'string', description: 'Message priority', nullable: true),
            new OA\Property(property: 'platform', type: 'string', description: 'Target platform', nullable: true),
            new OA\Property(property: 'event_id', type: 'integer', description: 'Event ID (required if channel is EVENT)', nullable: true),
            new OA\Property(property: 'group_id', type: 'integer', description: 'Group ID (required if channel is GROUP)', nullable: true),
            new OA\Property(property: 'recipients', type: 'array', items: new OA\Items(type: 'integer'), description: 'Array of member IDs (required if channel is MEMBERS)', nullable: true),
        ]
    )]
    public function __construct() {}
}

class SummitPushNotificationResponse
{
    #[OA\Schema(
        schema: 'SummitPushNotificationResponse',
        type: 'object',
        properties: [
            new OA\Property(property: 'id', type: 'integer', description: 'Notification ID'),
            new OA\Property(property: 'message', type: 'string', description: 'Notification message'),
            new OA\Property(property: 'priority', type: 'string', description: 'Message priority'),
            new OA\Property(property: 'platform', type: 'string', description: 'Target platform'),
            new OA\Property(property: 'channel', type: 'string', description: 'Notification channel'),
            new OA\Property(property: 'summit_id', type: 'integer', description: 'Summit ID'),
            new OA\Property(property: 'event_id', type: 'integer', description: 'Event ID', nullable: true),
            new OA\Property(property: 'group_id', type: 'integer', description: 'Group ID', nullable: true),
            new OA\Property(property: 'recipients', type: 'array', items: new OA\Items(type: 'integer'), description: 'Array of member IDs'),
            new OA\Property(property: 'created', type: 'integer', format: 'epoch', description: 'Creation timestamp'),
            new OA\Property(property: 'sent_date', type: 'integer', format: 'epoch', description: 'Sent date timestamp', nullable: true),
            new OA\Property(property: 'is_sent', type: 'boolean', description: 'Is notification sent'),
            new OA\Property(property: 'approved', type: 'boolean', description: 'Is notification approved'),
            new OA\Property(property: 'owner_id', type: 'integer', description: 'Owner member ID'),
            new OA\Property(property: 'approved_by_id', type: 'integer', description: 'Approved by member ID', nullable: true),
        ]
    )]
    public function __construct() {}
}

class SummitPushNotificationResponseExpanded
{
    #[OA\Schema(
        schema: 'SummitPushNotificationResponseExpanded',
        type: 'object',
        description: 'Expanded notification response with related objects populated',
        properties: [
            new OA\Property(property: 'id', type: 'integer', description: 'Notification ID'),
            new OA\Property(property: 'message', type: 'string', description: 'Notification message'),
            new OA\Property(property: 'priority', type: 'string', description: 'Message priority'),
            new OA\Property(property: 'platform', type: 'string', description: 'Target platform'),
            new OA\Property(property: 'channel', type: 'string', description: 'Notification channel'),
            new OA\Property(property: 'summit_id', type: 'integer', description: 'Summit ID'),
            new OA\Property(property: 'event', ref: '#/components/schemas/SummitEvent', description: 'Expanded event object', nullable: true),
            new OA\Property(property: 'group', ref: '#/components/schemas/ChatTeam', description: 'Expanded group object', nullable: true),
            new OA\Property(property: 'recipients', type: 'array', items: new OA\Items(ref: '#/components/schemas/Member'), description: 'Array of expanded member objects'),
            new OA\Property(property: 'created', type: 'integer', format: 'epoch', description: 'Creation timestamp'),
            new OA\Property(property: 'sent_date', type: 'integer', format: 'epoch', description: 'Sent date timestamp', nullable: true),
            new OA\Property(property: 'is_sent', type: 'boolean', description: 'Is notification sent'),
            new OA\Property(property: 'approved', type: 'boolean', description: 'Is notification approved'),
            new OA\Property(property: 'owner', ref: '#/components/schemas/Member', description: 'Expanded owner object'),
            new OA\Property(property: 'approved_by', ref: '#/components/schemas/Member', description: 'Expanded approved_by object', nullable: true),
        ]
    )]
    public function __construct() {}
}

class PaginatedNotificationsResponse
{
    #[OA\Schema(
        schema: 'PaginatedNotificationsResponse',
        type: 'object',
        description: 'Paginated list of notifications',
        properties: [
            new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/SummitPushNotificationResponse')),
            new OA\Property(property: 'total', type: 'integer', description: 'Total number of items'),
            new OA\Property(property: 'per_page', type: 'integer', description: 'Items per page'),
            new OA\Property(property: 'current_page', type: 'integer', description: 'Current page number'),
            new OA\Property(property: 'last_page', type: 'integer', description: 'Last page number'),
            new OA\Property(property: 'from', type: 'integer', description: 'Starting index', nullable: true),
            new OA\Property(property: 'to', type: 'integer', description: 'Ending index', nullable: true),
        ]
    )]
    public function __construct() {}
}

class SummitPushNotificationCSVResponse
{
    #[OA\Schema(
        schema: 'SummitPushNotificationCSVResponse',
        type: 'string',
        format: 'binary',
        description: 'CSV file with notifications data'
    )]
    public function __construct() {}
}
