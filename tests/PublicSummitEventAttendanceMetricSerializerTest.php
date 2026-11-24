<?php namespace Tests;
/*
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

use Mockery;
use models\oauth2\IResourceServerContext;
use models\main\Member;
use models\summit\SummitEvent;
use models\summit\SummitEventAttendanceMetric;
use models\summit\SummitGroupEvent;
use ModelSerializers\AdminSummitEventCSVSerializer;
use ModelSerializers\AdminSummitEventSerializer;
use ModelSerializers\AdminSummitEventWithFileCSVSerializer;
use ModelSerializers\AdminSummitEventWithFileSerializer;
use ModelSerializers\AdminSummitGroupEventSerializer;
use ModelSerializers\PublicSummitEventAttendanceMetricSerializer;
use ModelSerializers\SerializerRegistry;
use ModelSerializers\SummitEventAttendanceMetricSerializer;
use ModelSerializers\SummitEventSerializer;
use ModelSerializers\SummitEventWithFileSerializer;
use ModelSerializers\SummitGroupEventSerializer;

/**
 * The "current_attendance" expand of the public events API must not leak ip/browser/origin.
 * @package Tests
 */
final class PublicSummitEventAttendanceMetricSerializerTest extends TestCase
{
    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function buildMetric(): SummitEventAttendanceMetric
    {
        $member = Mockery::mock(Member::class);
        $member->shouldReceive('isPublicProfileShowPhoto')->andReturn(true);
        $member->shouldReceive('isPublicProfileShowFullname')->andReturn(true);

        $metric = Mockery::mock(SummitEventAttendanceMetric::class);
        $metric->shouldReceive('hasMember')->andReturn(true);
        $metric->shouldReceive('getMember')->andReturn($member);
        $metric->shouldReceive('getIp')->andReturn('10.1.2.3');
        $metric->shouldReceive('getBrowser')->andReturn('Firefox');
        $metric->shouldReceive('getOrigin')->andReturn('https://example.org');
        $metric->shouldReceive('getCreatedById')->andReturn(7);
        $metric->shouldReceive('getAttendeeId')->andReturn(5);
        $metric->shouldReceive('getMemberFirstName')->andReturn('Jane');
        $metric->shouldReceive('getMemberLastName')->andReturn('Doe');
        $metric->shouldReceive('getMemberProfilePhotoUrl')->andReturn('https://example.org/p.png');
        $metric->shouldReceive('getType')->andReturn('EVENT');
        $metric->shouldReceive('getEventId')->andReturn(1);
        $metric->shouldReceive('getRoomId')->andReturn(2);
        $metric->shouldReceive('getSubType')->andReturn('IN_PERSON');
        $metric->shouldReceive('getOutgressDate')->andReturn(null);
        $metric->shouldReceive('getIngressDate')->andReturn(new \DateTime());
        return $metric;
    }

    private function inner($decorator)
    {
        $prop = new \ReflectionProperty($decorator, 'serializer_imp');
        $prop->setAccessible(true);
        return $prop->getValue($decorator);
    }

    public function testPublicSerializerOmitsSensitiveFields()
    {
        $values = (new PublicSummitEventAttendanceMetricSerializer(
            $this->buildMetric(), Mockery::mock(IResourceServerContext::class)
        ))->serialize();
        foreach (['ip', 'browser', 'origin', 'created_by_id'] as $key) {
            $this->assertArrayNotHasKey($key, $values);
        }
        $this->assertArrayHasKey('member_first_name', $values);
    }

    public function testRegistryKeepsFullSerializerForPrivateAndSelectsPublicOneForAttendance()
    {
        $registry = SerializerRegistry::getInstance();
        // the registry resolves by short class name, so it needs a real entity (not a mock)
        $metric = new SummitEventAttendanceMetric();
        $this->assertInstanceOf(
            PublicSummitEventAttendanceMetricSerializer::class,
            $this->inner($registry->getSerializer($metric, SerializerRegistry::SerializerType_PublicAttendance))
        );
        $this->assertInstanceOf(
            SummitEventAttendanceMetricSerializer::class,
            $this->inner($registry->getSerializer($metric, SerializerRegistry::SerializerType_Private))
        );
        $this->assertInstanceOf(
            SummitEventAttendanceMetricSerializer::class,
            $this->inner($registry->getSerializer($metric))
        );
    }

    private function attendanceTypeFor(string $serializer_class): string
    {
        $serializer = new $serializer_class(
            Mockery::mock(SummitEvent::class), Mockery::mock(IResourceServerContext::class)
        );
        $method = new \ReflectionMethod($serializer, 'getSerializerType');
        $method->setAccessible(true);
        return $method->invoke($serializer, 'current_attendance');
    }

    public function testPublicEventSerializersSelectPublicAttendance()
    {
        foreach ([
            SummitEventSerializer::class,
            SummitEventWithFileSerializer::class,
            SummitGroupEventSerializer::class,
        ] as $serializer_class) {
            $this->assertEquals(
                SerializerRegistry::SerializerType_PublicAttendance,
                $this->attendanceTypeFor($serializer_class),
                $serializer_class
            );
        }
    }

    public function testAdminEventSerializersKeepFullAttendance()
    {
        foreach ([
            AdminSummitEventSerializer::class,
            AdminSummitEventCSVSerializer::class,
            AdminSummitEventWithFileSerializer::class,
            AdminSummitEventWithFileCSVSerializer::class,
            AdminSummitGroupEventSerializer::class,
        ] as $serializer_class) {
            $this->assertNotEquals(
                SerializerRegistry::SerializerType_PublicAttendance,
                $this->attendanceTypeFor($serializer_class),
                $serializer_class
            );
        }
    }

    public function testRegistryMapsAdminGroupEventSerializerForPrivateAndCsv()
    {
        $registry = SerializerRegistry::getInstance();
        $event = new SummitGroupEvent();
        $this->assertInstanceOf(
            AdminSummitGroupEventSerializer::class,
            $this->inner($registry->getSerializer($event, SerializerRegistry::SerializerType_Private))
        );
        $this->assertInstanceOf(
            AdminSummitGroupEventSerializer::class,
            $this->inner($registry->getSerializer($event, SerializerRegistry::SerializerType_CSV))
        );
        $inner = $this->inner($registry->getSerializer($event, SerializerRegistry::SerializerType_Public));
        $this->assertInstanceOf(SummitGroupEventSerializer::class, $inner);
        $this->assertNotInstanceOf(AdminSummitGroupEventSerializer::class, $inner);
    }
}
