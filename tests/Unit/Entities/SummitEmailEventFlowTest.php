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

use App\Models\Foundation\Summit\EmailFlows\SummitEmailEventFlow;
use App\Models\Foundation\Summit\EmailFlows\SummitEmailEventFlowType;
use App\Models\Foundation\Summit\EmailFlows\SummitEmailFlowType;
use Tests\InsertSummitTestData;
use Tests\TestCase;

class SummitEmailEventFlowTest extends TestCase
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

    /**
     * Helper method to create a mock SummitEmailFlowType
     * @return SummitEmailFlowType
     */
    private static function mockSummitEmailFlowType(): SummitEmailFlowType
    {
        $flow_type = new SummitEmailFlowType();
        $flow_type->setName("Test Email Flow Type " . str_random(5));

        return $flow_type;
    }

    /**
     * Helper method to create a mock SummitEmailEventFlowType
     * @param SummitEmailFlowType $flow_type
     * @return SummitEmailEventFlowType
     */
    private static function mockSummitEmailEventFlowType(SummitEmailFlowType $flow_type): SummitEmailEventFlowType
    {
        $event_flow_type = new SummitEmailEventFlowType();
        $event_flow_type->setName("Test Email Event Flow Type " . str_random(5));
        $event_flow_type->setSlug("test-email-event-flow-type-" . str_random(5));
        $event_flow_type->setDefaultEmailTemplate("default_email_template_" . str_random(5));
        $event_flow_type->setFlow($flow_type);

        return $event_flow_type;
    }

    public function testAddSummitEmailEventFlow(){
        // Create the flow type
        $flow_type = self::mockSummitEmailFlowType();
        self::$em->persist($flow_type);

        // Create the event flow type
        $event_flow_type = self::mockSummitEmailEventFlowType($flow_type);
        self::$em->persist($event_flow_type);

        // Create another event flow type
        $new_event_flow_type = self::mockSummitEmailEventFlowType($flow_type);
        self::$em->persist($new_event_flow_type);

        // Create the email event flow
        $email_event_flow = new SummitEmailEventFlow();
        $email_event_flow->setEmailTemplateIdentifier("email_template_" . str_random(5));
        $email_event_flow->setEmailRecipients(["test@example.com", "test2@example.com"]);

        // Set the event type (ManyToOne relationship)
        $email_event_flow->setEventType($event_flow_type);

        // Set the summit (ManyToOne relationship from SummitOwned trait)
        $email_event_flow->setSummit(self::$summit);

        self::$em->persist($email_event_flow);
        self::$em->flush();
        self::$em->clear();

        // Retrieve the email event flow from the database
        $repository = self::$em->getRepository(SummitEmailEventFlow::class);
        $found_email_event_flow = $repository->find($email_event_flow->getId());

        // Test ManyToOne relationship with event type
        $found_event_flow_type = $found_email_event_flow->getEventType();
        $this->assertEquals($event_flow_type->getId(), $found_event_flow_type->getId());

        // Test ManyToOne relationship with summit
        $found_summit = $found_email_event_flow->getSummit();
        $this->assertEquals(self::$summit->getId(), $found_summit->getId());
    }

    public function testChangeSummitEmailEventFlow(){
        // Create the flow type
        $flow_type = self::mockSummitEmailFlowType();
        self::$em->persist($flow_type);

        // Create the event flow type
        $event_flow_type = self::mockSummitEmailEventFlowType($flow_type);
        self::$em->persist($event_flow_type);

        // Create another event flow type for changing
        $new_event_flow_type = self::mockSummitEmailEventFlowType($flow_type);
        self::$em->persist($new_event_flow_type);

        // Create the email event flow
        $email_event_flow = new SummitEmailEventFlow();
        $email_event_flow->setEmailTemplateIdentifier("email_template_" . str_random(5));
        $email_event_flow->setEmailRecipients(["test@example.com", "test2@example.com"]);
        $email_event_flow->setEventType($event_flow_type);
        $email_event_flow->setSummit(self::$summit);

        self::$em->persist($email_event_flow);
        self::$em->flush();
        self::$em->clear();

        // Retrieve the email event flow from the database
        $repository = self::$em->getRepository(SummitEmailEventFlow::class);
        $found_email_event_flow = $repository->find($email_event_flow->getId());

        // Change the event type (ManyToOne relationship)
        $event_flow_type_repository = self::$em->getRepository(SummitEmailEventFlowType::class);
        $found_new_event_flow_type = $event_flow_type_repository->find($new_event_flow_type->getId());
        $found_email_event_flow->setEventType($found_new_event_flow_type);

        // Create a new summit for testing
        $new_summit = TestUtils::mockSummit();
        self::$em->persist($new_summit);

        // Change the summit (ManyToOne relationship from SummitOwned trait)
        $found_email_event_flow->setSummit($new_summit);

        self::$em->flush();
        self::$em->clear();

        // Retrieve the updated email event flow
        $updated_email_event_flow = $repository->find($email_event_flow->getId());

        // Test changed ManyToOne relationship with event type
        $updated_event_flow_type = $updated_email_event_flow->getEventType();
        $this->assertEquals($new_event_flow_type->getId(), $updated_event_flow_type->getId());

        // Test changed ManyToOne relationship with summit
        $updated_summit = $updated_email_event_flow->getSummit();
        $this->assertEquals($new_summit->getId(), $updated_summit->getId());
    }
}
