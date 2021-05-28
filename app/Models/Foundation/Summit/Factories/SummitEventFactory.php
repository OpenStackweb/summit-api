<?php namespace models\summit;
use models\exceptions\ValidationException;

/**
 * Copyright 2015 OpenStack Foundation
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

/**
 * Class SummitEventFactory
 * @package models\summit
 */
final class SummitEventFactory
{
    /**
     * @param SummitEventType $type
     * @param Summit $summit
     * @return SummitEvent
     */
    static public function build(SummitEventType $type, Summit $summit, array $payload)
    {
        $event = new SummitEvent();

        if($type instanceof PresentationType)
            $event = new Presentation();

        if($type->isPrivate())
            $event = new SummitGroupEvent();

        if($type->isAllowsAttachment())
            $event = new SummitEventWithFile();

        $event->setSummit($summit);
        $event->setType($type);

        return self::populate($event, $payload);
    }

    /**
     * @param SummitEvent $event
     * @param array $payload
     * @return SummitEvent
     * @throws ValidationException
     */
    static public function populate(SummitEvent $event, array $payload):SummitEvent{

        if (isset($payload['title']))
            $event->setTitle(html_entity_decode(trim($payload['title'])));

        if (isset($payload['description']))
            $event->setAbstract(html_entity_decode(trim($payload['description'])));

        if (isset($payload['social_description']))
            $event->setSocialSummary(strip_tags(trim($payload['social_description'])));

        $event_type = $event->getType();
        if (isset($payload['level']) && !is_null($event_type) && $event_type->isAllowsLevel())
            $event->setLevel($payload['level']);

        if (isset($payload['rsvp_link']) && isset($payload['rsvp_template_id'])) {
            throw new ValidationException
            (
                "rsvp_link and rsvp_template_id are both set, you need to specify only one."
            );
        }

        if (isset($payload['rsvp_link'])) {
            $event->setRSVPLink(html_entity_decode(trim($payload['rsvp_link'])));
        }

        if (isset($payload['streaming_url'])) {
            $event->setStreamingUrl(html_entity_decode(trim($payload['streaming_url'])));
        }

        if (isset($payload['etherpad_link'])) {
            $event->setEtherpadLink(html_entity_decode(trim($payload['etherpad_link'])));
        }

        if (isset($payload['meeting_url'])) {
            $event->setMeetingUrl(html_entity_decode(trim($payload['meeting_url'])));
        }

        if (isset($payload['head_count']))
            $event->setHeadCount(intval($payload['head_count']));

        if (isset($payload['occupancy']))
            $event->setOccupancy($payload['occupancy']);

        $event->setAllowFeedBack(isset($payload['allow_feedback']) ?
            filter_var($payload['allow_feedback'], FILTER_VALIDATE_BOOLEAN) :
            false);

        return $event;
    }
}