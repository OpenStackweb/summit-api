<?php namespace App\Jobs\Emails\PresentationSubmissions\SelectionProcess;
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
use App\Jobs\Emails\AbstractEmailJob;
use App\Services\Utils\Facades\EmailTest;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use models\summit\PresentationSpeaker;
use models\summit\Summit;
use models\summit\SummitRegistrationPromoCode;
/**
 * Class PresentationSpeakerSelectionProcessEmail
 * @package App\Jobs\Emails\PresentationSubmissions
 */
abstract class PresentationSpeakerSelectionProcessEmail extends AbstractEmailJob
{

    /**
     * @param array $payload
     * @param Summit $summit
     * @param PresentationSpeaker $speaker
     * @param SummitRegistrationPromoCode|null $promo_code
     */
    public function __construct
    (
        array &$payload,
        Summit $summit,
        PresentationSpeaker $speaker,
        ?SummitRegistrationPromoCode $promo_code = null
    ){
        $payload['summit_name'] = $summit->getName();
        $payload['summit_logo'] = $summit->getLogoUrl();
        $payload['summit_schedule_url'] = $summit->getScheduleDefaultPageUrl();
        $payload['summit_site_url'] = $summit->getDefaultPageUrl();
        $payload['speaker_full_name'] = $speaker->getFullName();
        $payload['speaker_email'] = $speaker->getEmail();

        $test_email_recipient = EmailTest::getEmailAddress();

        if (!empty($test_email_recipient)) {
            Log::debug
            (
                sprintf
                (
                    "PresentationSpeakerSelectionProcessEmail::__construct replacing original email %s by %s",
                    $payload['speaker_email'],
                    $test_email_recipient
                )
            );

            $payload['speaker_email'] = $test_email_recipient;
        }

        $speaker_management_base_url = Config::get('cfp.base_url');
        if(empty($speaker_management_base_url))
            throw new \InvalidArgumentException('cfp.base_url is null.');

        $payload['promo_code'] = '';
        $payload['promo_code_until_date'] = '';
        $payload['ticket_type'] = '';
        $payload['registration_link'] = $summit->getRegistrationLink();

        if(!is_null($promo_code)){
            $payload['promo_code'] = $promo_code->getCode();
            if(!is_null($promo_code->getValidUntilDate()))
            $payload['promo_code_until_date'] = $promo_code->getValidUntilDate()->format("Y-m-d H:i:s");
            $allowed_ticket_types = $promo_code->getAllowedTicketTypes();
            if(count($allowed_ticket_types) > 0)
                $payload['ticket_type'] = $allowed_ticket_types[0]->getName();
        }

        $payload['bio_edit_link'] = sprintf("%s/app/profile", $speaker_management_base_url);
        $payload['speaker_confirmation_link'] = $summit->getSpeakerConfirmationDefaultPageUrl();
        $template_identifier = $this->getEmailTemplateIdentifierFromEmailEvent($summit);

        parent::__construct($payload, $template_identifier, $payload['speaker_email']);
    }
}