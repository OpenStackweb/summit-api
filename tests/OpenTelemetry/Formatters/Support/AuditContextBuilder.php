<?php

namespace Tests\OpenTelemetry\Formatters\Support;

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

use App\Audit\AuditContext;

class AuditContextBuilder
{
    private ?int $userId = null;
    private ?string $userEmail = null;
    private ?string $userFirstName = null;
    private ?string $userLastName = null;
    private ?string $uiApp = null;
    private ?string $uiFlow = null;
    private ?string $route = null;
    private ?string $httpMethod = null;
    private ?string $clientIp = null;
    private ?string $userAgent = null;

    /**
     * Create a new builder with default values
     */
    public static function default(): self
    {
        $builder = new self();
        return $builder
            ->withUserId(1)
            ->withUserEmail('test@example.com')
            ->withUserName('Test', 'User')
            ->withUiApp('test-app')
            ->withUiFlow('test-flow')
            ->withRoute('api.test.route')
            ->withHttpMethod('POST')
            ->withClientIp('127.0.0.1')
            ->withUserAgent('Test-Agent/1.0');
    }

    public function withUserId(?int $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    public function withUserEmail(?string $userEmail): self
    {
        $this->userEmail = $userEmail;
        return $this;
    }

    public function withUserName(?string $firstName, ?string $lastName): self
    {
        $this->userFirstName = $firstName;
        $this->userLastName = $lastName;
        return $this;
    }

    public function withUiApp(?string $uiApp): self
    {
        $this->uiApp = $uiApp;
        return $this;
    }

    public function withUiFlow(?string $uiFlow): self
    {
        $this->uiFlow = $uiFlow;
        return $this;
    }

    public function withRoute(?string $route): self
    {
        $this->route = $route;
        return $this;
    }

    public function withHttpMethod(?string $httpMethod): self
    {
        $this->httpMethod = $httpMethod;
        return $this;
    }

    public function withClientIp(?string $clientIp): self
    {
        $this->clientIp = $clientIp;
        return $this;
    }

    public function withUserAgent(?string $userAgent): self
    {
        $this->userAgent = $userAgent;
        return $this;
    }

    /**
     * Build the AuditContext with the configured values
     */
    public function build(): AuditContext
    {
        return new AuditContext(
            userId: $this->userId,
            userEmail: $this->userEmail,
            userFirstName: $this->userFirstName,
            userLastName: $this->userLastName,
            uiApp: $this->uiApp,
            uiFlow: $this->uiFlow,
            route: $this->route,
            rawRoute: null,
            httpMethod: $this->httpMethod,
            clientIp: $this->clientIp,
            userAgent: $this->userAgent,
        );
    }
}
