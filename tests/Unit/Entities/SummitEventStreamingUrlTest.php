<?php

namespace Tests\Unit\Entities;

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

use models\summit\SummitEvent;
use Tests\TestCase;

/**
 * The primary stream and the overflow stream are two independent URLs on an event.
 * Setting one must never overwrite the other: the overflow readers (the public ?k= door,
 * occupancy/overflow) read overflow_streaming_url only, and the primary readers read
 * streaming_url only.
 */
final class SummitEventStreamingUrlTest extends TestCase
{
    private const PrimaryUrl = 'https://stream.mux.com/primary123.m3u8';
    private const OverflowUrl = 'https://stream.mux.com/overflow456.m3u8';

    private function eventWithSummit(): SummitEvent
    {
        $summit = TestUtils::mockSummit();
        $event  = new SummitEvent();
        $summit->addEvent($event);
        return $event;
    }

    public function testSetOverflowLeavesThePrimaryStreamUntouched(): void
    {
        $event = $this->eventWithSummit();
        $event->setStreamingUrl(self::PrimaryUrl);
        $event->setStreamIsSecure(false);

        $event->setOverflow(self::OverflowUrl, true);

        $this->assertEquals(self::OverflowUrl, $event->getOverflowStreamingUrl());
        $this->assertTrue($event->getOverflowStreamIsSecure());
        $this->assertEquals(SummitEvent::OccupancyOverflow, $event->getOccupancy());

        $this->assertEquals(self::PrimaryUrl, $event->getStreamingUrl(), 'setOverflow must not overwrite streaming_url');
        $this->assertFalse($event->IsSecureStream(), 'setOverflow must not overwrite stream_is_secure');
    }

    public function testSetStreamingUrlLeavesTheOverflowStreamUntouched(): void
    {
        $event = $this->eventWithSummit();
        $event->setOverflow(self::OverflowUrl, true);

        $event->setStreamingUrl(self::PrimaryUrl);

        $this->assertEquals(self::PrimaryUrl, $event->getStreamingUrl());
        $this->assertEquals(self::OverflowUrl, $event->getOverflowStreamingUrl(), 'setStreamingUrl must not overwrite overflow_streaming_url');
    }
}
