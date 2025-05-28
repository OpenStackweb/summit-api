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

use DateInterval;
use DateTime;
use DateTimeZone;
use models\main\Tag;
use models\summit\ISummitEventType;
use models\summit\Presentation;
use models\summit\PresentationCategory;
use models\summit\RSVP;
use models\summit\Summit;
use models\summit\SummitEvent;
use models\summit\SummitEventType;

class TestUtils
{
    public static function mockSummit(): Summit
    {
        $summit = new Summit();
        $summit->setActive(true);
        $summit->setAvailableOnApi(true);
        $summit->setExternalSummitId("8888");
        $summit->setApiFeedUrl("");
        $summit->setApiFeedKey("");
        $summit->setTimeZoneId("America/Chicago");
        $time_zone = new DateTimeZone("America/Chicago");
        $begin_date = new DateTime("now", $time_zone);
        $begin_date = $begin_date->add(new DateInterval("P1D"));
        $summit->setBeginDate($begin_date);
        $summit->setEndDate((clone $begin_date)->add(new DateInterval("P30D")));
        $summit->setRegistrationBeginDate($begin_date);
        $summit->setRegistrationEndDate((clone $begin_date)->add(new DateInterval("P30D")));
        $summit->setName(sprintf("TEST SUMMIT %s", str_random(4)));
        $summit->setRawSlug(sprintf("testsummit%s", str_random(4)));
        $summit->setRegistrationSlugPrefix(sprintf("TEST_REG_%s", str_random(4)));

        return $summit;
    }

    public static function mockPresentationCategory(Summit $summit): PresentationCategory
    {
        $category = new PresentationCategory();
        $category->setTitle(sprintf('DEFAULT TRACK %s', str_random(2)));
        $category->setCode(sprintf('DFT%s', str_random(2)));
        $category->setSessionCount(3);
        $category->setAlternateCount(3);
        $category->setLightningCount(3);
        $category->setChairVisible(true);
        $category->setVotingVisible(true);
        $category->setSummit($summit);

        return $category;
    }

    public static function mockSummitEventType(Summit $summit): SummitEventType
    {
        $event_type = new SummitEventType();
        $event_type->setType(ISummitEventType::Breaks);
        $event_type->setBlackoutTimes('All');
        $summit->addEventType($event_type);

        return $event_type;
    }

    public static function mockSummitEvent(Summit $summit, PresentationCategory $category, SummitEventType $event_type): SummitEvent
    {
        $event = new SummitEvent();
        $event->setTitle(sprintf("Raw Event Title %s", str_random(16)));
        $event->setAbstract(sprintf("Raw Event Abstract %s", str_random(16)));
        $event->setCategory($category);
        $event->setType($event_type);
        $event->setSubmissionSource( SummitEvent::SOURCE_ADMIN);
        $event->setOverflowStreamingUrl(sprintf("https://testoverflowurl_%s.org", str_random(5)));
        $event->setOverflowStreamIsSecure(true);
        $summit->addEvent($event);

        return $event;
    }

    public static function mockRSVP(SummitEvent $event): RSVP
    {
        $rsvp = new RSVP();
        $rsvp->setSeatType(RSVP::SeatTypeRegular);
        $rsvp->setEventUri("https://testoverflowurl_rsvp.org");
        $rsvp->setEvent($event);

        return $rsvp;
    }
}
