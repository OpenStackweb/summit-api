<?php

namespace App\Swagger\Summit;

use OpenApi\Attributes as OA;

class SummitPushNotification
{
    #[OA\Schema(
        schema: 'SummitPushNotificationRequest',
        type: 'object',
        required: ['title', 'body', 'channel'],
        properties: [
            new OA\Property(property: 'title', type: 'string', description: 'Notification title'),
            new OA\Property(property: 'body', type: 'string', description: 'Notification body'),
            new OA\Property(property: 'channel', type: 'string', enum: ['Event', 'Group', 'Members'], description: 'Channel type'),
            new OA\Property(property: 'event_id', type: 'integer', description: 'Event ID (required if channel is Event)'),
            new OA\Property(property: 'group_id', type: 'integer', description: 'Group ID (required if channel is Group)'),
            new OA\Property(property: 'recipients', type: 'array', items: new OA\Items(type: 'integer'), description: 'Array of member IDs (required if channel is Members)'),
            new OA\Property(property: 'approved', type: 'boolean', description: 'Is notification approved'),
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
            new OA\Property(property: 'id', type: 'integer'),
            new OA\Property(property: 'title', type: 'string'),
            new OA\Property(property: 'body', type: 'string'),
            new OA\Property(property: 'channel', type: 'string', enum: ['Event', 'Group', 'Members']),
            new OA\Property(property: 'summit_id', type: 'integer'),
            new OA\Property(property: 'event_id', type: 'integer', nullable: true),
            new OA\Property(property: 'group_id', type: 'integer', nullable: true),
            new OA\Property(property: 'recipients', type: 'array', items: new OA\Items(type: 'integer')),
            new OA\Property(property: 'approved', type: 'boolean'),
            new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
            new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
        ]
    )]
    public function __construct() {}
}

class SummitPushNotificationResponseExpanded
{
    #[OA\Schema(
        schema: 'SummitPushNotificationResponseExpanded',
        type: 'object',
        properties: [
            new OA\Property(property: 'id', type: 'integer'),
            new OA\Property(property: 'title', type: 'string'),
            new OA\Property(property: 'body', type: 'string'),
            new OA\Property(property: 'channel', type: 'string', enum: ['Event', 'Group', 'Members']),
            new OA\Property(property: 'summit_id', type: 'integer'),
            new OA\Property(
                property: 'event_id',
                type: 'array',
                items: new OA\Items(type: ['integer', 'SummitEventResponse']),
                nullable: true,
                description: 'Event ID or expanded event object'
            ),
            new OA\Property(
                property: 'group_id',
                type: 'array',
                items: new OA\Items(type: ['integer', 'ChatTeamResponse']),
                nullable: true,
                description: 'Group ID or expanded group object'
            ),
            new OA\Property(
                property: 'recipients',
                type: 'array',
                items: new OA\Items(type: ['integer', 'PublicMemberResponse']),
                description: 'Array of member IDs or expanded member objects'
            ),
            new OA\Property(property: 'approved', type: 'boolean'),
            new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
            new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
        ]
    )]
    public function __construct() {}
}