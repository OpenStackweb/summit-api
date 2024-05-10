<?php namespace Tests;
/*
 * Copyright 2024 OpenStack Foundation
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

use models\oauth2\IResourceServerContext;
use models\summit\Summit;
use models\summit\SummitAttendee;
use ModelSerializers\SerializerDecorator;
use ModelSerializers\SummitAttendeeAdminSerializer;
use Mockery;

/**
 * Class SerializerTests
 * @package Tests
 */
final class SerializerTests extends TestCase
{
    public function tearDown():void
    {
        Mockery::close();
    }

    public function testAdminSummitAttendeeSerilizer(){
        $summit = Mockery::mock(Summit::class);
        $attendee =  Mockery::mock(SummitAttendee::class)->makePartial();
        $attendee->shouldReceive('getSummit')->andReturn($summit);
        $attendee->shouldReceive("getEmail")->andReturn("test@test.com");
        $attendee->shouldReceive("updateStatus")->andReturn("Complete");
        $attendee->shouldReceive('getIdentifier')->andReturn(1);
        $attendee->shouldReceive('getNotes')->andReturn([]);
        $attendee->shouldReceive('getTickets')->andReturn([]);
        $attendee->shouldReceive('getExtraQuestionAnswers')->andReturn([]);
        $attendee->shouldReceive('getPresentationVotes')->andReturn([]);
        $attendee->shouldReceive('getTags')->andReturn([]);
        $attendee->shouldReceive('getVotesCount')->andReturn(0);
        $resource_server_context = Mockery::mock(IResourceServerContext::class);
        $serializer = new SerializerDecorator(new SummitAttendeeAdminSerializer($attendee, $resource_server_context));
        $values = $serializer->serialize('notes');
        $this->assertTrue(is_array($values));
        $this->assertTrue(isset($values['notes']));
    }
}