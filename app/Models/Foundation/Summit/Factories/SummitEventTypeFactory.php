<?php namespace App\Models\Foundation\Summit\Factories;
/**
 * Copyright 2018 OpenStack Foundation
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

use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\summit\PresentationType;
use models\summit\Summit;
use models\summit\SummitEventType;
/**
 * Class SummitEventTypeFactory
 * @package App\Models\Foundation\Summit\Factories
 */
final class SummitEventTypeFactory
{
    /**
     * @param Summit $summit
     * @param array $data
     * @return SummitEventType|null
     */
    public static function build(Summit $summit, array $data){
        $event_type = null;

        switch (trim($data['class_name'])){
            case SummitEventType::ClassName:{
                $event_type = new SummitEventType();
            }
            break;
            case PresentationType::ClassName:{
                $event_type = new PresentationType();
            }
            break;
        }

        if(is_null($event_type)) return null;
        return self::populate($event_type, $summit, $data);
    }

    /**
     * @param SummitEventType $event_type
     * @param Summit $summit
     * @param array $data
     * @return SummitEventType
     */
    public static function populate(SummitEventType $event_type, Summit $summit, array $data){
        switch ($data['class_name']){

            case PresentationType::ClassName: {
                if ($event_type instanceof PresentationType) {

                    if(isset($data['min_speakers'])) {
                        $event_type->setMinSpeakers(intval($data['min_speakers']));
                    }

                    if(isset($data['max_speakers'])) {
                        $event_type->setMaxSpeakers(intval($data['max_speakers']));
                    }

                    if(isset($data['min_moderators'])) {
                        $event_type->setMinModerators(intval($data['min_moderators']));
                    }

                    if(isset($data['max_moderators'])) {
                        $event_type->setMaxModerators(intval($data['max_moderators']));
                    }

                    if(isset($data['use_speakers'])) {
                        $event_type->setUseSpeakers(boolval($data['use_speakers']));
                    }

                    if(isset($data['are_speakers_mandatory'])) {
                        $event_type->setAreSpeakersMandatory(boolval($data['are_speakers_mandatory']));
                    }

                    if(isset($data['use_moderator'])) {
                        $event_type->setUseModerator(boolval($data['use_moderator']));
                    }

                    if(isset($data['is_moderator_mandatory'])) {
                        $event_type->setIsModeratorMandatory(boolval($data['is_moderator_mandatory']));
                    }

                    if(isset($data['moderator_label'])) {
                        $event_type->setModeratorLabel(trim($data['moderator_label']));
                    }

                    if(isset($data['should_be_available_on_cfp'])) {
                        $event_type->setShouldBeAvailableOnCfp(boolval($data['should_be_available_on_cfp']));
                    }

                    if(isset($data['allow_custom_ordering'])) {
                        $event_type->setAllowCustomOrdering(boolval($data['allow_custom_ordering']));
                    }

                    if(isset($data['allow_attendee_vote'])) {
                        $event_type->setAllowAttendeeVote(boolval($data['allow_attendee_vote']));
                    }

                    if(isset($data['allows_speaker_event_collision'])) {
                        $event_type->setAllowsSpeakerEventCollision(boolval($data['allows_speaker_event_collision']));
                    }

                    if(isset($data['min_duration'])) {
                        $event_type->setMinDuration(intval($data['min_duration']));
                    }

                    if(isset($data['max_duration'])) {
                        $event_type->setMaxDuration(intval($data['max_duration']));
                    }
                }
            }
           break;
        }

        if(isset($data['name']))
            $event_type->setType(trim($data['name']));

        if(isset($data['color']))
            $event_type->setColor(trim($data['color']));

        if(isset($data['black_out_times']))
            $event_type->setBlackoutTimes(trim($data['black_out_times']));

        if(isset($data['use_sponsors']))
            $event_type->setUseSponsors(boolval($data['use_sponsors']));

        if(isset($data['are_sponsors_mandatory']))
            $event_type->setAreSponsorsMandatory(boolval($data['are_sponsors_mandatory']));

        if(isset($data['allows_attachment']))
            $event_type->setAllowsAttachment(boolval($data['allows_attachment']));

        if(isset($data['is_private']))
            $event_type->setIsPrivate(boolval($data['is_private']));

        if(isset($data['is_default']))
            $event_type->setIsDefault(boolval($data['is_default']));

        if(isset($data['allows_level']))
            $event_type->setAllowsLevel(boolval($data['allows_level']));

        if(isset($data['allows_publishing_dates']))
            $event_type->setAllowsPublishingDates(boolval($data['allows_publishing_dates']));

        if(isset($data['allows_location']))
            $event_type->setAllowsLocation(boolval($data['allows_location']));

        if(isset($data['allows_location_timeframe_collision']))
            $event_type->setAllowsLocationTimeframeCollision(boolval($data['allows_location_timeframe_collision']));

        if(isset($data['show_always_on_schedule']))
            $event_type->setShowAlwaysOnSchedule(boolval($data['show_always_on_schedule']));

        if(isset($data['allowed_ticket_types'])){
            $event_type->clearAllowedTicketTypes();
            if($event_type->isShowAlwaysOnSchedule() && count($data['allowed_ticket_types']) > 0)
                throw new ValidationException
                (
                    "allowed_ticket_types can not be set on show_always_on_schedule event types."
                );
            foreach ($data['allowed_ticket_types'] as $ticket_type_id){
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
                $event_type->addAllowedTicketType($ticket_type);
            }
        }

        $summit->addEventType($event_type);
        return $event_type;
    }
}