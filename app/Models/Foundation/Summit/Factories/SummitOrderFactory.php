<?php namespace App\Models\Foundation\Summit\Factories;
/**
 * Copyright 2019 OpenStack Foundation
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
use models\main\Company;
use models\main\Member;
use models\summit\Summit;
use models\summit\SummitOrder;
use models\summit\SummitOrderExtraQuestionAnswer;
use models\summit\SummitOrderExtraQuestionType;
use models\summit\SummitOrderExtraQuestionTypeConstants;
use LaravelDoctrine\ORM\Facades\EntityManager;
/**
 * Class SummitOrderFactory
 * @package App\Models\Foundation\Summit\Factories
 */
final class SummitOrderFactory
{
    /**
     * @param Summit $summit
     * @param array $payload
     * @return SummitOrder
     * @throws ValidationException
     */
    public static function build(Summit $summit, array $payload): SummitOrder
    {
        return self::populate($summit, new SummitOrder, $payload);
    }

    /**
     * @param Summit $summit
     * @param SummitOrder $order
     * @param array $payload
     * @return SummitOrder
     * @throws ValidationException
     */
    public static function populate(Summit $summit, SummitOrder $order, array $payload): SummitOrder
    {

        $company_repository = EntityManager::getRepository(Company::class);

        $order->setSummit($summit);

        if(isset($payload['external_id']))
            $order->setExternalId(trim($payload['external_id']));

        $owner_email = $payload['owner_email'] ?? null;
        $current_owner_email = $order->getOwnerEmail();

        if(!empty($current_owner_email) && !empty($owner_email) && $owner_email != $current_owner_email) {
            Log::debug
            (
                sprintf
                (
                    "SummitOrderFactory::populate order %s current_owner_email %s owner_email %s, clearing owner",
                    $order->getId(),
                    $current_owner_email,
                    $owner_email
                )
            );

            $order->clearOwner();
        }

        /**
         * setting owner
         */
        if(isset($payload['owner']) && !is_null($payload['owner']) && $payload['owner'] instanceof Member){
            $owner = $payload['owner'];
            Log::debug
            (
                sprintf
                (
                    "SummitOrderFactory::populate order %s setting new owner %s (%s).",
                    $order->getId(),
                    $owner->getEmail(),
                    $owner->getId()
                )
            );
            $order->setOwner($owner);
        }

        if (!empty($owner_email))
            $order->setOwnerEmail(trim($owner_email));

        if(isset($payload['owner_first_name']) && !is_null($payload['owner_first_name']))
            $order->setOwnerFirstName(trim($payload['owner_first_name']));

        if (isset($payload['owner_last_name']) && !is_null($payload['owner_last_name']))
            $order->setOwnerSurname(trim($payload['owner_last_name']));

        // company
        if (isset($payload['owner_company']) && !is_null($payload['owner_company'])) {
            $order->setOwnerCompanyName(trim($payload['owner_company']));
            $order->clearOwnerCompany();
            $company = $company_repository->getByName(trim($payload['owner_company']));
            if(!is_null($company)){
                $order->setOwnerCompany($company);
            }
        }
        else if (isset($payload['owner_company_id']) && !is_null($payload['owner_company_id'])) {
            $ownerCompanyId = intval($payload['owner_company_id']);
            if($ownerCompanyId > 0) {
                $company = $company_repository->getById($ownerCompanyId);
                if (is_null($company)) {
                    throw new ValidationException(sprintf('Owner company with id %d not found as a registered company for summit %d',
                        $ownerCompanyId, $summit->getId()));
                }
                $order->setOwnerCompany($company);
                $order->setOwnerCompanyName($company->getName());
            }
        }

        if (isset($payload['billing_address_1']) && !is_null($payload['billing_address_1']))
            $order->setBillingAddress1(trim($payload['billing_address_1']));

        if (isset($payload['billing_address_2']) && !is_null($payload['billing_address_2']))
            $order->setBillingAddress2(trim($payload['billing_address_2']));

        if (isset($payload['billing_address_city']) && !is_null($payload['billing_address_city']))
            $order->setBillingAddressCity(trim($payload['billing_address_city']));

        if (isset($payload['billing_address_zip_code']) && !is_null($payload['billing_address_zip_code']))
            $order->setBillingAddressZipCode(trim($payload['billing_address_zip_code']));

        if (isset($payload['billing_address_state']) && !is_null($payload['billing_address_state']))
            $order->setBillingAddressState(trim($payload['billing_address_state']));

        if (isset($payload['billing_address_country']) && !is_null($payload['billing_address_country']))
            $order->setBillingAddressCountryIsoCode(trim($payload['billing_address_country']));

        // extra questions

        $extra_questions = $payload['extra_questions'] ?? [];

        if (count($extra_questions) > 0) {
            $mandatory_questions = $summit->getMandatoryOrderExtraQuestionsByUsage(SummitOrderExtraQuestionTypeConstants::OrderQuestionUsage);

            if (count($extra_questions) < $mandatory_questions->count()) {
                throw new ValidationException("You neglected to fill in all mandatory questions for the order.");
            }

            $questions = $summit->getOrderExtraQuestionsByUsage(SummitOrderExtraQuestionTypeConstants::OrderQuestionUsage);

            if ($questions->count() > 0) {
                $order->clearExtraQuestionAnswers();
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
                            $order->addExtraQuestionAnswer($answer);

                            break;
                        }
                    }
                }
            }
        }

        return $order;
    }
}