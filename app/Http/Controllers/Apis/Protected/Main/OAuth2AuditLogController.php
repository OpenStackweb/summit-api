<?php namespace App\Http\Controllers;
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
use models\main\SummitAttendeeBadgeAuditLog;
use models\main\SummitAuditLog;
use models\main\SummitEventAuditLog;
use models\oauth2\IResourceServerContext;
use models\summit\SummitAttendeeBadge;
use ModelSerializers\SerializerRegistry;

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