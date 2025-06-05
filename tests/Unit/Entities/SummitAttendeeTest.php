<?php

namespace Tests\Unit\Entities;

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

use App\Models\Foundation\Main\IGroup;
use models\main\Company;
use models\main\Member;
use models\main\Tag;
use models\summit\SummitAttendee;
use models\summit\SummitAttendeeNote;
use models\summit\SummitAttendeeTicket;
use Tests\InsertMemberTestData;
use Tests\InsertSummitTestData;
use Tests\TestCase;

class SummitAttendeeTest extends TestCase
{
    use InsertSummitTestData;

    use InsertMemberTestData;

    /**
     * @throws \Exception
     */
    protected function setUp():void
    {
        parent::setUp();
        self::insertMemberTestData(IGroup::TrackChairs);
        self::$defaultMember = self::$member;
        self::insertSummitTestData();
    }

    public function tearDown():void
    {
        self::clearSummitTestData();
        self::clearMemberTestData();
        parent::tearDown();
    }

    public function testAddSummitAttendee(){
        // Get an existing attendee from the test data
        $attendee_id = self::$summit->getAttendees()[0]->getId();
        $repository = self::$em->getRepository(SummitAttendee::class);
        $attendee = $repository->find($attendee_id);

        // Create a new company
        $company = TestUtils::mockCompany();
        self::$em->persist($company);

        // Set the company (ManyToOne relationship)
        $attendee->setCompany($company);

        // Create a new note
        $note = new SummitAttendeeNote("Test Note " . str_random(5), $attendee);
        
        // Add the note (OneToMany relationship)
        $attendee->addNote($note);

        // Create a new ticket
        $ticket = new SummitAttendeeTicket();
        $ticket->setTicketType(self::$default_ticket_type);
        
        // Add the ticket (OneToMany relationship)
        $attendee->addTicket($ticket);

        self::$em->flush();
        self::$em->clear();

        // Retrieve the attendee from the database
        $found_attendee = $repository->find($attendee->getId());

        // Test ManyToOne relationship with company
        $found_company = $found_attendee->getCompany();
        $this->assertEquals($company->getId(), $found_company->getId());

        // Test OneToMany relationship with notes
        $found_notes = $found_attendee->getNotes()->toArray();
        $this->assertNotEmpty($found_notes);
        $found_note = null;
        foreach ($found_notes as $n) {
            if ($n->getNote() === $note->getNote()) {
                $found_note = $n;
                break;
            }
        }
        $this->assertNotNull($found_note);

        // Test OneToMany relationship with tickets
        $found_tickets = $found_attendee->getTickets()->toArray();
        $this->assertNotEmpty($found_tickets);
        $this->assertCount(count($attendee->getTickets()), $found_tickets);
    }

    public function testDeleteSummitAttendeeChildren(){
        // Get an existing attendee from the test data
        $attendee_id = self::$summit->getAttendees()[0]->getId();
        $repository = self::$em->getRepository(SummitAttendee::class);
        $attendee = $repository->find($attendee_id);

        // Get the current counts
        $previous_tickets_count = count($attendee->getTickets()->toArray());
        $previous_notes_count = count($attendee->getNotes()->toArray());

        // Remove a ticket if there are any
        if ($previous_tickets_count > 0) {
            $tickets = $attendee->getTickets()->toArray();
            $attendee->removeTicket($tickets[0]);
        }

        // Remove a note if there are any
        if ($previous_notes_count > 0) {
            $notes = $attendee->getNotes()->toArray();
            $attendee->removeNote($notes[0]);
        }

        // Clear tags
        $attendee->clearTags();

        // Clear company
        if ($attendee->hasCompany()) {
            $attendee->clearCompany();
        }

        self::$em->flush();
        self::$em->clear();

        // Retrieve the attendee from the database
        $found_attendee = $repository->find($attendee->getId());

        // Test ManyToOne relationship with company
        $this->assertFalse($found_attendee->hasCompany());

        // Test ManyToMany relationship with tags
        $this->assertEmpty($found_attendee->getTags()->toArray());

        // Test OneToMany relationship with notes
        if ($previous_notes_count > 0) {
            $this->assertCount($previous_notes_count - 1, $found_attendee->getNotes()->toArray());
        }

        // Test OneToMany relationship with tickets
        if ($previous_tickets_count > 0) {
            $this->assertCount($previous_tickets_count - 1, $found_attendee->getTickets()->toArray());
        }

    }
}