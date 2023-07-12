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
use LaravelDoctrine\ORM\Facades\EntityManager;
use models\exceptions\ValidationException;
use models\main\Company;
use models\main\Member;
use models\summit\Summit;
use models\summit\SummitAttendee;

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
     * @param bool $validate_extra_questions
     * @return SummitAttendee
     * @throws ValidationException
     */
    public static function populate
    (
        Summit         $summit,
        SummitAttendee $attendee,
        array          $payload,
        ?Member        $member = null,
        bool           $validate_extra_questions = true
    )
    {
        $company_repository = EntityManager::getRepository(Company::class);

        if (!is_null($member)) {
            Log::debug(sprintf("SummitAttendeeFactory::populate setting member %s to attendee %s", $member->getId(), $member->getEmail()));
            $attendee->setEmail($member->getEmail());
            $attendee->setMember($member);
        } else {
            $attendee->clearMember();
        }

        if (isset($payload['email']) && !empty($payload['email']))
            $attendee->setEmail(trim($payload['email']));

        $summit->addAttendee($attendee);

        if (isset($payload['external_id']))
            $attendee->setExternalId(trim($payload['external_id']));

        if (isset($payload['first_name']))
            $attendee->setFirstName(trim($payload['first_name']));

        if (isset($payload['last_name']))
            $attendee->setSurname(trim($payload['last_name']));

        // company by name
        if (isset($payload['company'])) {
            $attendee->clearCompany();
            if( !empty($payload['company'])) {
                $attendee->setCompanyName(trim($payload['company']));
                $company = $company_repository->getByName(trim($payload['company']));
                if (!is_null($company)) {
                    $attendee->setCompany($company);
                }
            }
        } else if (isset($payload['company_id']) && !is_null($payload['company_id'])) {
            $companyId = intval($payload['company_id']);
            if ($companyId > 0) {
                $company = $company_repository->getById($companyId);
                if (is_null($company)) {
                    throw new ValidationException(sprintf('company with id %d not found as a registered company for summit %d',
                        $companyId, $summit->getId()));
                }
                $attendee->setCompany($company);
                $attendee->setCompanyName($company->getName());
            }
        }

        if (isset($payload['admin_notes']) && !empty($payload['admin_notes']))
            $attendee->setAdminNotes(trim($payload['admin_notes']));

        if (isset($payload['shared_contact_info']))
            $attendee->setShareContactInfo(boolval($payload['shared_contact_info']));

        if (isset($payload['summit_hall_checked_in'])) {
            $attendee->setSummitHallCheckedIn(boolval($payload['summit_hall_checked_in']));
        }

        if (isset($payload['disclaimer_accepted']) && !empty($payload['disclaimer_accepted'])) {
            $disclaimer_accepted = boolval($payload['disclaimer_accepted']);
            if ($disclaimer_accepted && !$attendee->isDisclaimerAccepted()) {
                $attendee->setDisclaimerAcceptedDate
                (
                    new \DateTime('now', new \DateTimeZone('UTC'))
                );
            }
            if (!$disclaimer_accepted) {
                $attendee->clearDisclaimerAcceptedDate();
            }
        }

        // extra questions

        $extra_questions = $payload['extra_questions'] ?? [];

        if (count($extra_questions)) {
            $res = $attendee->hadCompletedExtraQuestions($extra_questions);
            if (!$res && $validate_extra_questions) {
                throw new ValidationException("You neglected to fill in all mandatory questions for the attendee.");
            }
        }

        return $attendee;
    }
}