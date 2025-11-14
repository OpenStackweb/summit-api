<?php namespace App\Audit;
/**
 * Copyright 2025 OpenStack Foundation
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
class AuditContext
{
    public function __construct(
        public ?int    $userId = null,
        public ?string $userEmail = null,
        public ?string $userFirstName = null,
        public ?string $userLastName = null,
        public ?string $uiApp = null,
        public ?string $uiFlow = null,
        public ?string $route = null,
        public ?string $httpMethod = null,
        public ?string $clientIp = null,
        public ?string $userAgent = null,
    ) {}
}
