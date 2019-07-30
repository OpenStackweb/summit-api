<?php namespace models\summit\factories;
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
use Illuminate\Support\Facades\Log;
use models\exceptions\ValidationException;
use models\main\Member;
use models\summit\Summit;
use models\summit\SummitAttendee;
use models\summit\SummitOrderExtraQuestionAnswer;
use models\summit\SummitOrderExtraQuestionType;
use models\summit\SummitOrderExtraQuestionTypeConstants;
/**
 * Class SummitAttendeeFactory
 * @package models\summit\factories
 */
final class SummitAttendeeFactory
{
    /**
     * @param Summit $summit
     * @param array $payload
     * @param Member|null $member
     * @return SummitAttendee
     * @throws ValidationException
     */
    public static function build(Summit $summit, array $payload, ?Member $member = null)
    {
        return self::populate($summit, new SummitAttendee, $payload, $member);
    }

    /**
     * @param Summit $summit
     * @param SummitAttendee $attendee
     * @param array $payload
     * @param Member|null $member
     * @return SummitAttendee
     * @throws ValidationException
     */
    public static function populate
    (
        Summit $summit,
        SummitAttendee $attendee,
        array $payload,
        ?Member $member = null
    )
    {

        if (!is_null($member))
            $attendee->setMember($member);

        $summit->addAttendee($attendee);

        if(isset($payload['external_id']))
            $attendee->setExternalId(trim($payload['external_id']));

        if(isset($payload['first_name']))
            $attendee->setFirstName(trim($payload['first_name']));

        if (isset($payload['last_name']))
            $attendee->setSurname(trim($payload['last_name']));

        if (isset($payload['email']) && !empty($payload['email']))
            $attendee->setEmail(trim($payload['email']));

        if (isset($payload['company']) && !empty($payload['company']))
            $attendee->setCompanyName(trim($payload['company']));

        if (isset($payload['shared_contact_info']))
            $attendee->setShareContactInfo(boolval($payload['shared_contact_info']));

        if (isset($payload['summit_hall_checked_in']))
            $attendee->setSummitHallCheckedIn(boolval($payload['summit_hall_checked_in']));

        if (isset($payload['summit_hall_checked_in_date']) && !empty($payload['summit_hall_checked_in_date']))
            $attendee->setSummitHallCheckedInDate
            (
                new \DateTime(intval($payload['summit_hall_checked_in_date']))
            );

        if (isset($payload['disclaimer_accepted']) && !empty($payload['disclaimer_accepted'])) {
            $disclaimer_accepted = boolval($payload['disclaimer_accepted']);
            if ($disclaimer_accepted && !$attendee->hasDisclaimerAccepted()) {
                $attendee->setDisclaimerAcceptedDate
                (
                    new \DateTime('now', new \DateTimeZone('UTC'))
                );
            }
        }

        // extra questions

        $extra_questions = $payload['extra_questions'] ?? [];

        if (count($extra_questions)) {

            $mandatory_questions = $summit->getMandatoryOrderExtraQuestionsByUsage(SummitOrderExtraQuestionTypeConstants::TicketQuestionUsage);
            if (count($extra_questions) < $mandatory_questions->count()) {
                throw new ValidationException("You neglected to fill in all mandatory questions for the attendee.");
            }

            $questions = $summit->getOrderExtraQuestionsByUsage(SummitOrderExtraQuestionTypeConstants::TicketQuestionUsage);
            if ($questions->count() > 0) {
                $attendee->clearExtraQuestionAnswers();
                foreach ($questions as $question) {
                    if (!$question instanceof SummitOrderExtraQuestionType) continue;
                    foreach ($extra_questions as $question_answer) {
                        if (intval($question_answer['question_id']) == $question->getId()) {
                            $value = trim($question_answer['answer']);

                            if (empty($value) && $question->isMandatory())
                                throw new ValidationException(sprintf('Question "%s" is mandatory', $question->getLabel()));

                            if ($question->allowsValues() && !$question->allowValue($value)) {
                                Log::warning(sprintf("value %s is not allowed for question %s", $value, $question->getName()));
                                throw new ValidationException("The answer you provided is invalid");
                            }

                            $answer = new SummitOrderExtraQuestionAnswer();
                            $answer->setQuestion($question);
                            $answer->setValue($value);
                            $attendee->addExtraQuestionAnswer($answer);

                            break;
                        }
                    }
                }
            }
        }

        return $attendee;
    }
}