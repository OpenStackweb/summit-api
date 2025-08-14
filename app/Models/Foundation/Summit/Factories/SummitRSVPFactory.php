<?php namespace App\Models\Foundation\Summit\Factories;
/**
 * Copyright 2020 OpenStack Foundation
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

use App\Models\Foundation\Summit\Events\RSVP\RSVPQuestionTemplate;
use models\main\Member;
use models\summit\RSVP;
use models\summit\RSVPAnswer;
use models\summit\SummitEvent;
use models\exceptions\ValidationException;


abstract class BaseSummitRSVPFactory {
    /**
     * @param SummitEvent $summit_event
     * @param Member $owner
     * @param array $payload
     * @return RSVP
     * @throws ValidationException
     */
    public static function build(SummitEvent $summit_event, Member $owner, array $payload):RSVP{
        return self::populate(new RSVP, $summit_event, $owner, $payload);
    }

    /**
     * @param RSVP $rsvp
     * @param SummitEvent $summit_event
     * @param Member $owner
     * @param array $payload
     * @return RSVP
     * @throws ValidationException
     */
    public static function populate(RSVP $rsvp, SummitEvent $summit_event, Member $owner = null, array $payload = []):RSVP {

        if(!is_null($owner))
            $rsvp->setOwner($owner);

        if(isset($payload['event_uri']) && !empty($payload['event_uri'])){
            $rsvp->setEventUri($payload['event_uri']);
        }

        $template = $summit_event->getRSVPTemplate();

        if(!is_null($template)) {
            // template is optional
            $answers = $payload['answers'] ?? [];

            // restructuring for a quick search
            if (count($answers)) {
                $bucket = [];
                foreach ($answers as $answer_dto) {
                    $bucket[intval($answer_dto['question_id'])] = $answer_dto;
                }
                $answers = $bucket;
            }

            foreach ($template->getQuestions() as $question) {

                if (!$question instanceof RSVPQuestionTemplate) continue;
                $answer_dto = $answers[$question->getId()] ?? null;
                $value = $answer_dto['value'] ?? null;

                if ($question->isMandatory() &&
                    (
                        is_null($value) ||
                        (is_string($value) && empty($value)) ||
                        (is_array($value)) && count($value) == 0
                    )
                )
                    throw new ValidationException(sprintf("Question '%s' is mandatory.", $question->getLabel()));

                $answer = $rsvp->findAnswerByQuestion($question);
                if (is_null($answer))
                    $answer = new RSVPAnswer();

                if (!$question->isValidValue($value))
                    throw new ValidationException(sprintf("Value is not valid for Question '%s'.", $question->getLabel()));

                $answer->setValue($value);
                $answer->setQuestion($question);
                $rsvp->addAnswer($answer);
            }

        }

        $summit_event->addRSVPSubmission($rsvp);

        return $rsvp;
    }
}

/**
 * Class SummitRSVPFactory
 * @package App\Models\Foundation\Summit\Factories
 */
final class SummitRSVPFactory extends BaseSummitRSVPFactory
{

    /**
     * @param RSVP $rsvp
     * @param SummitEvent $summit_event
     * @param Member $owner
     * @param array $payload
     * @return RSVP
     * @throws ValidationException
     */
    public static function populate(RSVP $rsvp, SummitEvent $summit_event, Member $owner = null, array $payload = []):RSVP {
        $rsvp = parent::populate($rsvp, $summit_event, $owner, $payload);
        if(!$rsvp->hasSeatTypeSet())
            $rsvp->setSeatType($summit_event->getCurrentRSVPSubmissionSeatType());
        return $rsvp;
    }
}


final class AdminSummitRSVPFactory extends BaseSummitRSVPFactory{
    /**
     * @param RSVP $rsvp
     * @param SummitEvent $summit_event
     * @param Member $owner
     * @param array $payload
     * @return RSVP
     * @throws ValidationException
     */
    public static function populate(RSVP $rsvp, SummitEvent $summit_event, Member $owner = null, array $payload = []):RSVP {
        $rsvp = parent::populate($rsvp, $summit_event, $owner, $payload);
        if(isset($payload['seat_type']) && !empty($payload['seat_type'])){
            $rsvp->setSeatType($payload['seat_type']);
        }
        if(isset($payload['status']) && !empty($payload['status'])){
            $rsvp->setStatus($payload['status']);
        }
        return $rsvp;
    }
}