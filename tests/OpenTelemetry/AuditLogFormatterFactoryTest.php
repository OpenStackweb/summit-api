<?php 
namespace Tests\OpenTelemetry;

/**
 * Copyright 2026 OpenStack Foundation
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
use App\Audit\AuditLogFormatterFactory;
use PHPUnit\Framework\TestCase;

/**
 * Class AuditLogFormatterFactoryTest
 * Tests for AuditLogFormatterFactory::matchesStrategy() null guard
 */
class AuditLogFormatterFactoryTest extends TestCase
{
    private const ROUTE_CREATE_EVENT = 'POST|api/v1/summits/{id}/events';
    private const ROUTE_UPDATE_PRESENTATION = 'PUT|api/v1/summits/{id}/presentations';
    private const FORMATTER_CLASS = 'TestFormatterClass';

    private AuditLogFormatterFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new AuditLogFormatterFactory();
    }


    public function testMatchesStrategyHandlesNullRawRouteWithRouteRequired(): void
    {
        $strategy = [
            'route' => self::ROUTE_CREATE_EVENT,
            'formatter' => self::FORMATTER_CLASS
        ];

        $ctx = new AuditContext(
            userId: null,
            userEmail: null,
            userFirstName: null,
            userLastName: null,
            uiApp: null,
            uiFlow: null,
            route: null,
            rawRoute: null,  // null rawRoute from console command
            httpMethod: null,
            clientIp: null,
            userAgent: null
        );

        $reflection = new \ReflectionClass($this->factory);
        $method = $reflection->getMethod('matchesStrategy');
        $method->setAccessible(true);

        $result = $method->invoke($this->factory, $strategy, $ctx);
        $this->assertFalse($result, 'matchesStrategy should return false when rawRoute is null and route is required');
    }


    public function testMatchesStrategyReturnsTrueWhenNoRouteRequiredAndRawRouteNull(): void
    {
        $strategy = [
            'formatter' => self::FORMATTER_CLASS
        ];

        $ctx = new AuditContext(
            userId: null,
            userEmail: null,
            userFirstName: null,
            userLastName: null,
            uiApp: null,
            uiFlow: null,
            route: null,
            rawRoute: null,
            httpMethod: null,
            clientIp: null,
            userAgent: null
        );

        $reflection = new \ReflectionClass($this->factory);
        $method = $reflection->getMethod('matchesStrategy');
        $method->setAccessible(true);

        $result = $method->invoke($this->factory, $strategy, $ctx);
        $this->assertTrue($result, 'matchesStrategy should return true when no route is required');
    }


    public function testMatchesStrategyReturnsTrueWhenRouteMatches(): void
    {
        $strategy = [
            'route' => self::ROUTE_CREATE_EVENT,
            'formatter' => self::FORMATTER_CLASS
        ];

        $ctx = new AuditContext(
            userId: null,
            userEmail: null,
            userFirstName: null,
            userLastName: null,
            uiApp: null,
            uiFlow: null,
            route: null,
            rawRoute: self::ROUTE_CREATE_EVENT,  // matching rawRoute
            httpMethod: null,
            clientIp: null,
            userAgent: null
        );

        $reflection = new \ReflectionClass($this->factory);
        $method = $reflection->getMethod('matchesStrategy');
        $method->setAccessible(true);

        $result = $method->invoke($this->factory, $strategy, $ctx);
        $this->assertTrue($result, 'matchesStrategy should return true when routes match');
    }

    
    public function testMatchesStrategyReturnsFalseWhenRouteDoesNotMatch(): void
    {
        $strategy = [
            'route' => self::ROUTE_CREATE_EVENT,
            'formatter' => self::FORMATTER_CLASS
        ];

        $ctx = new AuditContext(
            userId: null,
            userEmail: null,
            userFirstName: null,
            userLastName: null,
            uiApp: null,
            uiFlow: null,
            route: null,
            rawRoute: self::ROUTE_UPDATE_PRESENTATION,  // non-matching rawRoute
            httpMethod: null,
            clientIp: null,
            userAgent: null
        );

        $reflection = new \ReflectionClass($this->factory);
        $method = $reflection->getMethod('matchesStrategy');
        $method->setAccessible(true);

        $result = $method->invoke($this->factory, $strategy, $ctx);
        $this->assertFalse($result, 'matchesStrategy should return false when routes do not match');
    }
}
