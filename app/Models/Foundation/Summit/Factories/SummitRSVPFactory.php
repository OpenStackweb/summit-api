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
/**
 * Class SummitRSVPFactory
 * @package App\Models\Foundation\Summit\Factories
 */
final class SummitRSVPFactory
{
    /**
     * @param SummitEvent $summitEvent
     * @param Member $owner
     * @param array $data
     * @return RSVP
     * @throws ValidationException
     */
    public static function build(SummitEvent $summitEvent, Member $owner, array $data):RSVP{
        return self::populate(new RSVP, $summitEvent, $owner, $data);
    }

    /**
     * @param RSVP $rsvp
     * @param SummitEvent $summitEvent
     * @param Member $owner
     * @param array $data
     * @return RSVP
     * @throws ValidationException
     */
    public static function populate(RSVP $rsvp, SummitEvent $summitEvent, Member $owner, array $data):RSVP {

        $rsvp->setOwner($owner);

        if(!$rsvp->hasSeatTypeSet())
            $rsvp->setSeatType($summitEvent->getCurrentRSVPSubmissionSeatType());

        if(isset($data['event_uri']) && !empty($data['event_uri'])){
            $rsvp->setEventUri($data['event_uri']);
        }

        $template = $summitEvent->getRSVPTemplate();

        if(!is_null($template)) {
            // template is optional
            $answers = $data['answers'] ?? [];

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

        $summitEvent->addRSVPSubmission($rsvp);

        return $rsvp;
    }
}