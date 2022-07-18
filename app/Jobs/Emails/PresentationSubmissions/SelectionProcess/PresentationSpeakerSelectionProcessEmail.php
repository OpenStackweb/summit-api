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
use App\Services\Utils\Facades\SpeakersAnnouncementEmailConfig;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use models\summit\PresentationSpeaker;
use models\summit\Summit;
use models\summit\SummitRegistrationPromoCode;
use ModelSerializers\IPresentationSerializerTypes;
use ModelSerializers\SerializerRegistry;
use utils\Filter;

/**
 * Class PresentationSpeakerSelectionProcessEmail
 * @package App\Jobs\Emails\PresentationSubmissions
 */
abstract class PresentationSpeakerSelectionProcessEmail extends AbstractEmailJob
{
    protected $filter;

    /**
     * @param Summit $summit
     * @param PresentationSpeaker $speaker
     * @param SummitRegistrationPromoCode|null $promo_code
     * @param Filter|null $filter
     */
    public function __construct
    (
        Summit $summit,
        PresentationSpeaker $speaker,
        ?SummitRegistrationPromoCode $promo_code = null,
        ?Filter $filter = null
    ){

        if(!is_null($filter))
            $this->filter = $filter->getOriginalExp();
        $payload  = [];
        $cc_email = [];
        $shouldSendCopy2Submitter = SpeakersAnnouncementEmailConfig::shouldSendCopy2Submitter();

        // accepted ones
        $payload['accepted_presentations'] = [];
        foreach($speaker->getAcceptedPresentations($summit, PresentationSpeaker::RoleSpeaker, true, [], $filter) as $p){
            if($shouldSendCopy2Submitter && $p->hasCreatedBy() && !in_array($p->getCreatedBy()->getEmail(), $cc_email) && $speaker->getEmail() != $p->getCreatedBy()->getEmail())
                $cc_email[] = $p->getCreatedBy()->getEmail();
            $payload['accepted_presentations'][] =
                SerializerRegistry::getInstance()->getSerializer($p, IPresentationSerializerTypes::SpeakerEmails)->serialize();
        }

        $payload['accepted_moderated_presentations'] = [];
        foreach($speaker->getAcceptedPresentations($summit, PresentationSpeaker::RoleModerator, true, [], $filter) as $p){
            if($shouldSendCopy2Submitter && $p->hasCreatedBy() && !in_array($p->getCreatedBy()->getEmail(), $cc_email) && $speaker->getEmail() != $p->getCreatedBy()->getEmail())
                $cc_email[] = $p->getCreatedBy()->getEmail();

            $payload['accepted_moderated_presentations'][] =
                SerializerRegistry::getInstance()->getSerializer($p, IPresentationSerializerTypes::SpeakerEmails)->serialize();
        }

        // alternates
        $payload['alternate_presentations'] = [];
        foreach($speaker->getAlternatePresentations($summit, PresentationSpeaker::RoleSpeaker, false, [], false, $filter) as $p){
            if($shouldSendCopy2Submitter && $p->hasCreatedBy() && !in_array($p->getCreatedBy()->getEmail(), $cc_email) && $speaker->getEmail() != $p->getCreatedBy()->getEmail())
                $cc_email[] = $p->getCreatedBy()->getEmail();

            $payload['alternate_presentations'][] =
                SerializerRegistry::getInstance()->getSerializer($p, IPresentationSerializerTypes::SpeakerEmails)->serialize();
        }

        $payload['alternate_moderated_presentations'] = [];
        foreach($speaker->getAlternatePresentations($summit, PresentationSpeaker::RoleModerator, false, [], false, $filter) as $p){
            if($shouldSendCopy2Submitter && $p->hasCreatedBy() && !in_array($p->getCreatedBy()->getEmail(), $cc_email) && $speaker->getEmail() != $p->getCreatedBy()->getEmail())
                $cc_email[] = $p->getCreatedBy()->getEmail();

            $payload['alternate_moderated_presentations'][] =
                SerializerRegistry::getInstance()->getSerializer($p, IPresentationSerializerTypes::SpeakerEmails)->serialize();
        }

        // rejected

        $payload['rejected_presentations'] = [];
        foreach($speaker->getRejectedPresentations($summit, PresentationSpeaker::RoleSpeaker, false, [] ,$filter) as $p){
            if($shouldSendCopy2Submitter && $p->hasCreatedBy() && !in_array($p->getCreatedBy()->getEmail(), $cc_email) && $speaker->getEmail() != $p->getCreatedBy()->getEmail())
                $cc_email[] = $p->getCreatedBy()->getEmail();

            $payload['rejected_presentations'][] =
                SerializerRegistry::getInstance()->getSerializer($p, IPresentationSerializerTypes::SpeakerEmails)->serialize();
        }

        $payload['rejected_moderated_presentations'] = [];
        foreach($speaker->getRejectedPresentations($summit, PresentationSpeaker::RoleModerator, false, [] , $filter) as $p){
            if($shouldSendCopy2Submitter && $p->hasCreatedBy() && !in_array($p->getCreatedBy()->getEmail(), $cc_email) && $speaker->getEmail() != $p->getCreatedBy()->getEmail())
                $cc_email[] = $p->getCreatedBy()->getEmail();

            $payload['rejected_moderated_presentations'][] =
                SerializerRegistry::getInstance()->getSerializer($p, IPresentationSerializerTypes::SpeakerEmails)->serialize();
        }

        if(count($cc_email) > 0){
            $payload['cc_email'] = implode(',', $cc_email);
        }

        $payload['summit_name'] = $summit->getName();
        $payload['summit_logo'] = $summit->getLogoUrl();
        $payload['summit_schedule_url'] = $summit->getScheduleDefaultPageUrl();
        $payload['summit_site_url'] = $summit->getLink();
        $payload['speaker_full_name'] = $speaker->getFullName();
        $payload['speaker_email'] = $speaker->getEmail();

        $test_email_recipient = EmailTest::getEmailAddress();

        if (!empty($test_email_recipient)) {
            Log::debug
            (
                sprintf
                (
                    "PresentationSpeakerSelectionProcessEmail::__construct replacing original email %s by %s and clearing cc field",
                    $payload['speaker_email'],
                    $test_email_recipient
                )
            );

            $payload['speaker_email'] = $test_email_recipient;
            $payload['cc_email'] = '';
        }

        $speaker_management_base_url = Config::get('cfp.base_url');
        if(empty($speaker_management_base_url))
            throw new \InvalidArgumentException('cfp.base_url is null.');

        $payload['promo_code'] = '';
        $payload['promo_code_until_date'] = '';
        $payload['ticket_type'] = '';
        $payload['registration_link'] = $summit->getRegistrationLink();
        $payload['virtual_event_site_link'] = $summit->getVirtualSiteUrl();

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

        parent::__construct($payload, $template_identifier, $payload['speaker_email'], null,$payload['cc_email'] ?? null);
    }
}