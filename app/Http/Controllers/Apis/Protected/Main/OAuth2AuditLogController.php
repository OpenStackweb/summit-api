<?php

namespace App\Http\Controllers;

/**
 * Copyright 2022 OpenStack Foundation
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 **/

use App\Models\Foundation\Main\Repositories\IAuditLogRepository;
use App\Security\SummitScopes;
use Illuminate\Http\Response;
use models\main\SummitAttendeeBadgeAuditLog;
use models\main\SummitAuditLog;
use models\main\SummitEventAuditLog;
use models\oauth2\IResourceServerContext;
use models\summit\SummitAttendeeBadge;
use ModelSerializers\SerializerRegistry;
use OpenApi\Attributes as OA;

#[OA\SecurityScheme(
        type: 'oauth2',
        securityScheme: 'audit_logs_oauth2',
        flows: [
            new OA\Flow(
                authorizationUrl: L5_SWAGGER_CONST_AUTH_URL,
                tokenUrl: L5_SWAGGER_CONST_TOKEN_URL,
                flow: 'authorizationCode',
                scopes: [
                ],
            ),
        ],
    )
]
class AuditLogAuthSchema{}

/**
 * Class OAuth2AuditLogController
 * @package App\Http\Controllers
 */
final class OAuth2AuditLogController extends OAuth2ProtectedController
{
    use ParametrizedGetAll;

    /**
     * OAuth2AuditLogController constructor.
     * @param IAuditLogRepository $audit_repository
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        IAuditLogRepository $audit_repository,
        IResourceServerContext $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->repository = $audit_repository;
    }

    /**
     * @return mixed
     */
    #[OA\Get(
        path: "/api/v1/audit-logs",
        description: "Get all audit logs with filtering capabilities. Requires OAuth2 authentication with appropriate scope.",
        summary: 'Get all audit logs',
        operationId: 'getAllAuditLogs',
        tags: ['Audit Logs'],
        security: [['audit_logs_oauth2' => [
            SummitScopes::ReadAuditLogs,
        ]]],
        parameters: [
            new OA\Parameter(
                name: 'access_token',
                in: 'query',
                required: false,
                description: 'OAuth2 access token (alternative to Authorization: Bearer)',
                schema: new OA\Schema(type: 'string', example: 'eyJhbGciOi...')
            ),
            new OA\Parameter(
                name: 'page',
                in: 'query',
                required: false,
                description: 'Page number for pagination',
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                required: false,
                description: 'Items per page',
                schema: new OA\Schema(type: 'integer', example: 10, maximum: 100)
            ),
            new OA\Parameter(
                name: 'filter[]',
                in: 'query',
                required: false,
                description: 'Filter expressions. Format: field<op>value. Available fields: class_name (required, ==), user_id (==), summit_id (==), event_id (==), entity_id (==), user_email (==, =@, @@), user_full_name (==, =@, @@), action (=@, @@), metadata (==, =@, @@), created (==, >, <, >=, <=, []). class_name must be one of: SummitAuditLog, SummitEventAuditLog, SummitAttendeeBadgeAuditLog',
                style: 'form',
                explode: true,
                schema: new OA\Schema(
                    type: 'array',
                    items: new OA\Items(type: 'string', example: 'class_name==SummitAuditLog')
                )
            ),
            new OA\Parameter(
                name: 'order',
                in: 'query',
                required: false,
                description: 'Order by field(s). Available fields: id, user_id, event_id, entity_id, created, user_email, user_full_name, metadata. Use "-" prefix for descending order.',
                schema: new OA\Schema(type: 'string', example: '-created')
            ),
            new OA\Parameter(
                name: 'expand',
                in: 'query',
                required: false,
                description: 'Comma-separated list of related resources to include. Available relations: user, summit',
                schema: new OA\Schema(type: 'string', example: 'user,summit')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success - Returns paginated list of audit logs',
                content: new OA\JsonContent(ref: '#/components/schemas/PaginatedAuditLogsResponse')
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request - Invalid parameters"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized - Invalid or missing access token"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden - Insufficient permissions"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error - Missing required filters"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function getAll(){

        return $this->_getAll(
            function () {
                return [
                    'class_name' => ['=='],
                    'user_id'   => ['=='],
                    'summit_id' => ['=='],
                    'event_id'  => ['=='],
                    'entity_id'  => ['=='],
                    'user_email' => ['==', '=@','@@'],
                    'user_full_name'  => ['==', '=@','@@'],
                    'action'  => ['=@','@@'],
                    'metadata'  => ['==', '=@','@@'],
                    'created' => ['==', '>', '<', '>=', '<=','[]'],
                ];
            },
            function () {
                return [
                    'class_name' => 'required|string|in:' . implode(',', [SummitAuditLog::ClassName, SummitEventAuditLog::ClassName, SummitAttendeeBadgeAuditLog::ClassName]),
                    'user_id'   => 'sometimes|integer',
                    'summit_id' => 'sometimes|integer',
                    'event_id'  => 'sometimes|integer',
                    'entity_id'  => 'sometimes|integer',
                    'user_email' => 'sometimes|string',
                    'user_full_name' => 'sometimes|string',
                    'action' => 'sometimes|string',
                    'metadata' => 'sometimes|string',
                    'created' => 'sometimes|date_format:U|epoch_seconds',
                ];
            },
            function () {
                return [
                    'id',
                    'user_id',
                    'event_id',
                    'entity_id',
                    'created',
                    'user_email',
                    'user_full_name',
                    'metadata',
                ];
            },
            function($filter) {
                return $filter;
            },
            function () {
                return SerializerRegistry::SerializerType_Public;
            }
        );
    }
}
