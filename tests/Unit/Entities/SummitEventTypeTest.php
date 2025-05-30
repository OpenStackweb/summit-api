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

use models\summit\SummitDocument;
use models\summit\SummitEventType;
use models\summit\SummitTicketType;
use Tests\InsertSummitTestData;
use Tests\TestCase;

class SummitEventTypeTest extends TestCase
{
    use InsertSummitTestData;

    /**
     * @throws \Exception
     */
    protected function setUp():void
    {
        parent::setUp();
        self::insertSummitTestData();
    }

    public function tearDown():void
    {
        self::clearSummitTestData();
        parent::tearDown();
    }

    public function testAddSummitEventType(){
        // Get an existing event type from the test data
        $event_type_id = self::$defaultEventType->getId();
        $repository = self::$em->getRepository(SummitEventType::class);
        $event_type = $repository->find($event_type_id);

        // Create a new document
        $document = new SummitDocument();
        $document->setName("Test Document " . str_random(5));
        $document->setLabel("Test Document Label " . str_random(5));
        $document->setSummit(self::$summit);
        $document->addEventType($event_type);
        self::$em->persist($document);

        // Add the document (ManyToMany relationship)
        $event_type->addSummitDocument($document);

        // Add a ticket type (ManyToMany relationship)
        $event_type->addAllowedTicketType(self::$default_ticket_type);

        self::$em->flush();
        self::$em->clear();

        // Retrieve the event type from the database
        $found_event_type = $repository->find($event_type->getId());

        // Test ManyToMany relationship with documents
        $found_documents = $found_event_type->getSummitDocuments()->toArray();
        $found_document = null;
        foreach ($found_documents as $doc) {
            if ($doc->getName() === $document->getName()) {
                $found_document = $doc;
                break;
            }
        }
        $this->assertNotNull($found_document);
    }

    public function testDeleteSummitEventTypeChildren(){
        // Get an existing event type from the test data
        $event_type_id = self::$defaultEventType->getId();
        $repository = self::$em->getRepository(SummitEventType::class);
        $event_type = $repository->find($event_type_id);

        // Create a new document
        $document = new SummitDocument();
        $document->setName("Test Document " . str_random(5));
        $document->setLabel("Test Document Label " . str_random(5));
        $document->setSummit(self::$summit);
        self::$em->persist($document);

        // Add the document (ManyToMany relationship)
        $event_type->addSummitDocument($document);

        // Add a ticket type (ManyToMany relationship)
        $event_type->addAllowedTicketType(self::$default_ticket_type);

        self::$em->flush();
        self::$em->clear();

        // Retrieve the event type from the database
        $event_type = $repository->find($event_type_id);

        // Get the current counts
        $documents = $event_type->getSummitDocuments()->toArray();
        $previous_documents_count = count($documents);

        $ticket_types = $event_type->getAllowedTicketTypes()->toArray();
        $previous_ticket_types_count = count($ticket_types);

        // Remove a document if there are any
        if ($previous_documents_count > 0) {
            $event_type->removeSummitDocument($documents[0]);
        }

        // Clear ticket types
        $event_type->clearAllowedTicketTypes();

        self::$em->flush();
        self::$em->clear();

        // Retrieve the event type from the database
        $found_event_type = $repository->find($event_type->getId());

        // Test ManyToMany relationship with documents
        if ($previous_documents_count > 0) {
            $this->assertCount($previous_documents_count - 1, $found_event_type->getSummitDocuments()->toArray());
        }

        // Test ManyToMany relationship with ticket types
        $this->assertEmpty($found_event_type->getAllowedTicketTypes()->toArray());

    }
}