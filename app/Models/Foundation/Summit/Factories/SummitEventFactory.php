<?php namespace models\summit;
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

use Illuminate\Support\Facades\Log;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
/**
 * Class SummitEventFactory
 * @package models\summit
 */
final class SummitEventFactory
{

    /**
     * @param SummitEventType $type
     * @param Summit $summit
     * @param array $payload
     * @return SummitEvent
     * @throws ValidationException
     */
    static public function build(SummitEventType $type, Summit $summit, array $payload = [])
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

        return self::populate($summit, $event, $payload);
    }

    /**
     * @param Summit $summit
     * @param SummitEvent $event
     * @param array $payload
     * @return SummitEvent
     * @throws ValidationException
     */
    static public function populate(Summit $summit, SummitEvent $event, array $payload):SummitEvent{

        // selection plan

        if ($event instanceof Presentation) {
            if (isset($payload['selection_plan_id'])) {
                $selection_plan_id = intval($payload['selection_plan_id']);
                if ($selection_plan_id > 0) {
                    $selection_plan = $event->getSummit()->getSelectionPlanById($selection_plan_id);
                    if (!is_null($selection_plan)) {

                        $track = $event->getCategory();
                        $type = $event->getType();

                        if (!is_null($track) && !$selection_plan->hasTrack($track)) {
                            throw new ValidationException
                            (
                                sprintf
                                (
                                    "Track %s (%s) does not belongs to Selection Plan %s (%s).",
                                    $track->getTitle(),
                                    $track->getId(),
                                    $selection_plan->getName(),
                                    $selection_plan->getId()
                                )
                            );
                        }

                        if (!is_null($type) && !$selection_plan->hasEventType($type)) {
                            throw new ValidationException
                            (
                                sprintf
                                (
                                    "Type %s (%s) does not belongs to Selection Plan %s (%s).",
                                    $type->getType(),
                                    $type->getId(),
                                    $selection_plan->getName(),
                                    $selection_plan->getId()
                                )
                            );
                        }

                        $event->setSelectionPlan($selection_plan);
                    }
                } else {
                    Log::debug
                    (
                        sprintf
                        (
                            "SummitService::saveOrUpdatePresentationData clearing selection plan for presentation %s",
                            $event->getId()
                        )
                    );

                    $event->clearSelectionPlan();
                }
            }

            $event_selection_plan = $event->getSelectionPlan();
            if (!is_null($event_selection_plan))
                $payload = $event_selection_plan->curatePayloadByPresentationAllowedQuestions($payload);
        }

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

        if (isset($payload['stream_is_secure'])) {
            $event->setStreamIsSecure(filter_var($payload['stream_is_secure'], FILTER_VALIDATE_BOOLEAN));
        }

        if (isset($payload['streaming_type'])) {
            $event->setStreamingType(trim($payload['streaming_type']));
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

        if(isset($payload['show_sponsors'])) {
            $event->setShowSponsors(filter_var($payload['show_sponsors'], FILTER_VALIDATE_BOOLEAN));
        }

        if(isset($payload['allowed_ticket_types'])){
            $event->clearAllowedTicketTypes();;

            foreach ($payload['allowed_ticket_types'] as $ticket_type_id){
                $ticket_type = $summit->getTicketTypeById(intval($ticket_type_id));
                if(is_null($ticket_type))
                    throw new EntityNotFoundException
                    (
                        sprintf
                        (
                            "Ticket type %s not found.",
                            $ticket_type_id
                        )
                    );
                $event->addAllowedTicketType($ticket_type);
            }
        }

        if(isset($payload['submission_source'])) {
            $event->setSubmissionSource(trim($payload['submission_source']));
        }

        return $event;
    }
}