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

use models\oauth2\IResourceServerContext;
use models\summit\Summit;
use models\summit\SummitAttendee;
use ModelSerializers\SummitAttendeeAdminSerializer;
use ModelSerializers\SummitAttendeeSerializer;
use Mockery;

/**
 * Class SummitAttendeeSerializerTest
 * @package Tests
 */
final class SummitAttendeeSerializerTest extends TestCase
{
    public function tearDown(): void
    {
        Mockery::close();
    }

    /**
     * @param int $identifier unique per test method - RequestScopedCache keys solely on
     * ("SummitAttendeeSerializer", identifier, expand, fields, relations), so two test methods
     * calling serialize() with the same arguments over two different mocks with the same
     * identifier would silently share a cached result.
     * @param array $tickets passed through, not set via a second shouldReceive('getTickets')
     * call on the test's own mock - Mockery matches the FIRST matching expectation added for a
     * method with no differentiating args/times(), so a later override is silently ignored.
     * @param array $tags same reasoning as $tickets, for getTags().
     * @return SummitAttendee
     */
    private function buildAttendee(int $identifier, array $tickets = [], array $tags = []): SummitAttendee
    {
        $summit = Mockery::mock(Summit::class);
        $attendee = Mockery::mock(SummitAttendee::class)->makePartial();
        $attendee->shouldReceive('getSummit')->andReturn($summit);
        $attendee->shouldReceive('getEmail')->andReturn('test@test.com');
        $attendee->shouldReceive('updateStatus')->andReturn('Complete');
        $attendee->shouldReceive('getIdentifier')->andReturn($identifier);
        $attendee->shouldReceive('getTickets')->andReturn($tickets);
        $attendee->shouldReceive('getExtraQuestionAnswers')->andReturn([]);
        $attendee->shouldReceive('getPresentationVotes')->andReturn([]);
        $attendee->shouldReceive('getTags')->andReturn($tags);
        $attendee->shouldReceive('getVotesCount')->andReturn(0);
        return $attendee;
    }

    public function testShouldSkipTicketFiltersInactiveCancelledAndTypelessTickets()
    {
        $noType = Mockery::mock();
        $noType->shouldReceive('hasTicketType')->andReturn(false);
        $this->assertTrue(SummitAttendeeSerializer::shouldSkipTicket($noType, []));

        $cancelled = Mockery::mock();
        $cancelled->shouldReceive('hasTicketType')->andReturn(true);
        $cancelled->shouldReceive('isCancelled')->andReturn(true);
        $this->assertTrue(SummitAttendeeSerializer::shouldSkipTicket($cancelled, []));

        $inactive = Mockery::mock();
        $inactive->shouldReceive('hasTicketType')->andReturn(true);
        $inactive->shouldReceive('isCancelled')->andReturn(false);
        $inactive->shouldReceive('isActive')->andReturn(false);
        $this->assertTrue(SummitAttendeeSerializer::shouldSkipTicket($inactive, []));

        $active = Mockery::mock();
        $active->shouldReceive('hasTicketType')->andReturn(true);
        $active->shouldReceive('isCancelled')->andReturn(false);
        $active->shouldReceive('isActive')->andReturn(true);
        $this->assertFalse(SummitAttendeeSerializer::shouldSkipTicket($active, []));
    }

    public function testDefaultRelationsProduceIdListsWithoutExpand()
    {
        $ticket = Mockery::mock();
        $ticket->shouldReceive('hasTicketType')->andReturn(true);
        $ticket->shouldReceive('isCancelled')->andReturn(false);
        $ticket->shouldReceive('isActive')->andReturn(true);
        $ticket->shouldReceive('getId')->andReturn(55);

        $tag = Mockery::mock();
        $tag->shouldReceive('getId')->andReturn(7);

        $attendee = $this->buildAttendee(90001, [$ticket], [$tag]);
        $resource_server_context = Mockery::mock(IResourceServerContext::class);
        $serializer = new SummitAttendeeSerializer($attendee, $resource_server_context);

        $values = $serializer->serialize(null, [], ['tickets', 'tags']);

        $this->assertSame([55], $values['tickets']);
        $this->assertSame([7], $values['tags']);
    }

    public function testExpandIgnoredWithoutMatchingRelation()
    {
        $attendee = $this->buildAttendee(90002);
        $resource_server_context = Mockery::mock(IResourceServerContext::class);
        $serializer = new SummitAttendeeSerializer($attendee, $resource_server_context);

        // A non-empty $relations that deliberately excludes 'tickets' - NOT an empty array.
        // parent::serialize() -> AbstractSerializer::serialize() auto-defaults an EMPTY
        // $relations to getAllowedRelations() internally (the same behavior SerializerDecorator
        // applies before every real call), which would make 'tickets' pass the relation-verify
        // gate regardless of $expand. 'tags' here keeps $relations non-empty so that auto-default
        // never triggers, correctly exercising the "expand requested, relation NOT requested" gate.
        $values = $serializer->serialize('tickets', [], ['tags']);

        $this->assertArrayNotHasKey('tickets', $values);
    }

    public function testExpandAppliedWhenRelationAlsoRequested()
    {
        $attendee = $this->buildAttendee(90003);
        $resource_server_context = Mockery::mock(IResourceServerContext::class);
        $serializer = new SummitAttendeeSerializer($attendee, $resource_server_context);

        $values = $serializer->serialize('tickets', [], ['tickets']);

        $this->assertSame([], $values['tickets']);
    }

    public function testCompanyManagerSummitSpeakerAbsentWhenEntityHasNone()
    {
        $attendee = $this->buildAttendee(90004);
        // 'company' is ALSO the array_mapping attribute for CompanyName ('CompanyName' =>
        // 'company:json_string'), so it is never absent - unlike manager/summit/speaker, it
        // always holds the company name string. Absence of the expand is proven here by that
        // string surviving unreplaced, instead of being overwritten with a nested company object.
        $attendee->shouldReceive('getCompanyName')->andReturn('Acme Inc');
        $resource_server_context = Mockery::mock(IResourceServerContext::class);
        $serializer = new SummitAttendeeSerializer($attendee, $resource_server_context);

        $values = $serializer->serialize('company,manager,summit,speaker', [], []);

        $this->assertSame('Acme Inc', $values['company']);
        $this->assertArrayNotHasKey('manager', $values);
        $this->assertArrayNotHasKey('summit', $values);
        $this->assertArrayNotHasKey('speaker', $values);
        $this->assertArrayHasKey('company_id', $values);
        $this->assertArrayHasKey('manager_id', $values);
        $this->assertArrayHasKey('summit_id', $values);
    }

    public function testAdminNotesRelationFallbackReturnsIdsWithoutExpand()
    {
        $note = Mockery::mock();
        $note->shouldReceive('getId')->andReturn(42);

        $attendee = $this->buildAttendee(90005);
        $attendee->shouldReceive('getNotes')->andReturn([$note]);

        $resource_server_context = Mockery::mock(IResourceServerContext::class);
        $serializer = new SummitAttendeeAdminSerializer($attendee, $resource_server_context);

        $values = $serializer->serialize(null, [], ['notes']);

        $this->assertSame([42], $values['notes']);
    }

}
