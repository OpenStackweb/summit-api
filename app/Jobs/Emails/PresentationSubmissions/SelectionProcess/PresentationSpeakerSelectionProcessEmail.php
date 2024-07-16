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
use App\Jobs\Emails\AbstractSummitEmailJob;
use App\Jobs\Emails\IMailTemplatesConstants;
use App\Services\Utils\Email\SpeakersAnnouncementEmailConfigDTO;
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
abstract class PresentationSpeakerSelectionProcessEmail extends AbstractSummitEmailJob
{
    protected $filter;

    /**
     * @param Summit $summit
     * @param PresentationSpeaker $speaker
     * @param string|null $test_email_recipient
     * @param SpeakersAnnouncementEmailConfigDTO $speaker_announcement_email_config
     * @param SummitRegistrationPromoCode|null $promo_code
     * @param Filter|null $filter
     */
    public function __construct
    (
        Summit $summit,
        PresentationSpeaker $speaker,
        ?string $test_email_recipient,
        SpeakersAnnouncementEmailConfigDTO $speaker_announcement_email_config,
        ?SummitRegistrationPromoCode $promo_code = null,
        ?Filter $filter = null
    ){

        if(!is_null($filter)) {
            Log::debug
            (
                sprintf
                (
                    "PresentationSpeakerSelectionProcessEmail::__construct summit %s speaker %s (%s) filter %s",
                    $summit->getId(),
                    $speaker->getId(),
                    $speaker->getEmail(),
                    $filter->__toString()
                )
            );
            $this->filter = $filter->getOriginalExp();
        }

        $payload  = [];
        $cc_email = [];
        $shouldSendCopy2Submitter = $speaker_announcement_email_config->shouldSendCopy2Submitter();

        // accepted ones
        $payload[IMailTemplatesConstants::accepted_presentations] = [];
        foreach($speaker->getAcceptedPresentations($summit, PresentationSpeaker::RoleSpeaker, true, [], $filter) as $p){
            if($shouldSendCopy2Submitter && $p->hasCreatedBy() && !in_array($p->getCreatedBy()->getEmail(), $cc_email) && $speaker->getEmail() != $p->getCreatedBy()->getEmail())
                $cc_email[] = $p->getCreatedBy()->getEmail();
            $payload[IMailTemplatesConstants::accepted_presentations][] =
                SerializerRegistry::getInstance()->getSerializer($p, IPresentationSerializerTypes::SpeakerEmails)->serialize();
        }

        $payload[IMailTemplatesConstants::accepted_moderated_presentations] = [];
        foreach($speaker->getAcceptedPresentations($summit, PresentationSpeaker::RoleModerator, true, [], $filter) as $p){
            if($shouldSendCopy2Submitter && $p->hasCreatedBy() && !in_array($p->getCreatedBy()->getEmail(), $cc_email) && $speaker->getEmail() != $p->getCreatedBy()->getEmail())
                $cc_email[] = $p->getCreatedBy()->getEmail();

            $payload[IMailTemplatesConstants::accepted_moderated_presentations][] =
                SerializerRegistry::getInstance()->getSerializer($p, IPresentationSerializerTypes::SpeakerEmails)->serialize();
        }

        // alternates
        $payload[IMailTemplatesConstants::alternate_presentations] = [];
        foreach($speaker->getAlternatePresentations($summit, PresentationSpeaker::RoleSpeaker, false, [], false, $filter) as $p){
            if($shouldSendCopy2Submitter && $p->hasCreatedBy() && !in_array($p->getCreatedBy()->getEmail(), $cc_email) && $speaker->getEmail() != $p->getCreatedBy()->getEmail())
                $cc_email[] = $p->getCreatedBy()->getEmail();

            $payload[IMailTemplatesConstants::alternate_presentations][] =
                SerializerRegistry::getInstance()->getSerializer($p, IPresentationSerializerTypes::SpeakerEmails)->serialize();
        }

        $payload[IMailTemplatesConstants::alternate_moderated_presentations] = [];
        foreach($speaker->getAlternatePresentations($summit, PresentationSpeaker::RoleModerator, false, [], false, $filter) as $p){
            if($shouldSendCopy2Submitter && $p->hasCreatedBy() && !in_array($p->getCreatedBy()->getEmail(), $cc_email) && $speaker->getEmail() != $p->getCreatedBy()->getEmail())
                $cc_email[] = $p->getCreatedBy()->getEmail();

            $payload[IMailTemplatesConstants::alternate_moderated_presentations][] =
                SerializerRegistry::getInstance()->getSerializer($p, IPresentationSerializerTypes::SpeakerEmails)->serialize();
        }

        // rejected

        $payload[IMailTemplatesConstants::rejected_presentations] = [];
        foreach($speaker->getRejectedPresentations($summit, PresentationSpeaker::RoleSpeaker, false, [] ,$filter) as $p){
            if($shouldSendCopy2Submitter && $p->hasCreatedBy() && !in_array($p->getCreatedBy()->getEmail(), $cc_email) && $speaker->getEmail() != $p->getCreatedBy()->getEmail())
                $cc_email[] = $p->getCreatedBy()->getEmail();

            $payload[IMailTemplatesConstants::rejected_presentations][] =
                SerializerRegistry::getInstance()->getSerializer($p, IPresentationSerializerTypes::SpeakerEmails)->serialize();
        }

        $payload[IMailTemplatesConstants::rejected_moderated_presentations] = [];
        foreach($speaker->getRejectedPresentations($summit, PresentationSpeaker::RoleModerator, false, [], $filter) as $p){
            if($shouldSendCopy2Submitter && $p->hasCreatedBy() && !in_array($p->getCreatedBy()->getEmail(), $cc_email) && $speaker->getEmail() != $p->getCreatedBy()->getEmail())
                $cc_email[] = $p->getCreatedBy()->getEmail();

            $payload[IMailTemplatesConstants::rejected_moderated_presentations][] =
                SerializerRegistry::getInstance()->getSerializer($p, IPresentationSerializerTypes::SpeakerEmails)->serialize();
        }

        if(count($cc_email) > 0){
            $payload[IMailTemplatesConstants::cc_email] = implode(',', $cc_email);
        }

        $payload[IMailTemplatesConstants::speaker_full_name] = $speaker->getFullName();
        $payload[IMailTemplatesConstants::speaker_email] = $speaker->getEmail();

        if (!empty($test_email_recipient)) {
            Log::debug
            (
                sprintf
                (
                    "PresentationSpeakerSelectionProcessEmail::__construct replacing original email %s by %s and clearing cc field",
                    $payload[IMailTemplatesConstants::speaker_email],
                    $test_email_recipient
                )
            );

            $payload[IMailTemplatesConstants::speaker_email] = $test_email_recipient;
            $payload[IMailTemplatesConstants::cc_email] = '';
        }

        $speaker_management_base_url = Config::get('cfp.base_url');
        if(empty($speaker_management_base_url))
            throw new \InvalidArgumentException('cfp.base_url is null.');

        $payload[IMailTemplatesConstants::promo_code] = '';
        $payload[IMailTemplatesConstants::promo_code_until_date] = '';
        $payload[IMailTemplatesConstants::ticket_type] = '';


        if(!is_null($promo_code)){
            $payload[IMailTemplatesConstants::promo_code] = $promo_code->getCode();
            if(!is_null($promo_code->getValidUntilDate()))
            $payload[IMailTemplatesConstants::promo_code_until_date] = $promo_code->getValidUntilDate()->format("Y-m-d H:i:s");
            $allowed_ticket_types = $promo_code->getAllowedTicketTypes();
            if(count($allowed_ticket_types) > 0)
                $payload[IMailTemplatesConstants::ticket_type] = $allowed_ticket_types[0]->getName();
        }

        $payload[IMailTemplatesConstants::bio_edit_link] = sprintf("%s/app/profile", $speaker_management_base_url);
        $payload[IMailTemplatesConstants::speaker_confirmation_link] = $summit->getSpeakerConfirmationDefaultPageUrl();
        $template_identifier = $this->getEmailTemplateIdentifierFromEmailEvent($summit);

        parent::__construct($summit, $payload, $template_identifier, $payload[IMailTemplatesConstants::speaker_email], null,$payload[IMailTemplatesConstants::cc_email] ?? null);
    }

    /**
     * @return array
     */
    public static function getEmailTemplateSchema(): array{

        $payload = parent::getEmailTemplateSchema();

        $payload[IMailTemplatesConstants::cc_email]['type'] = 'string';
        $payload[IMailTemplatesConstants::speaker_full_name]['type'] = 'string';
        $payload[IMailTemplatesConstants::speaker_email]['type'] = 'string';
        $payload[IMailTemplatesConstants::promo_code]['type'] = 'string';
        $payload[IMailTemplatesConstants::promo_code_until_date]['type'] = 'string';
        $payload[IMailTemplatesConstants::ticket_type]['type'] = 'string';
        $payload[IMailTemplatesConstants::bio_edit_link]['type'] = 'string';
        $payload[IMailTemplatesConstants::speaker_confirmation_link]['type'] = 'string';

        $track_schema = [];
        $track_schema['type'] = 'object';
        $track_schema['properties'][IMailTemplatesConstants::id]['type'] = 'int';
        $track_schema['properties'][IMailTemplatesConstants::name]['type'] = 'string';

        $selection_plan_schema = [];
        $selection_plan_schema['type'] = 'object';
        $selection_plan_schema['properties'][IMailTemplatesConstants::id]['type'] = 'int';
        $selection_plan_schema['properties'][IMailTemplatesConstants::name]['type'] = 'string';

        $speaker_schema = [];
        $speaker_schema['type'] = 'object';
        $speaker_schema['properties'][IMailTemplatesConstants::id]['type'] = 'int';
        $speaker_schema['properties'][IMailTemplatesConstants::full_name]['type'] = 'string';
        $speaker_schema['properties'][IMailTemplatesConstants::email]['type'] = 'string';

        $presentations_schema = [];
        $presentations_schema['type'] = 'object';
        $presentations_schema['properties'][IMailTemplatesConstants::id]['type'] = 'int';
        $presentations_schema['properties'][IMailTemplatesConstants::title]['type'] = 'string';
        $presentations_schema['properties'][IMailTemplatesConstants::track] = $track_schema;
        $presentations_schema['properties'][IMailTemplatesConstants::selection_plan] = $selection_plan_schema;
        $presentations_schema['properties'][IMailTemplatesConstants::moderator] = $speaker_schema;
        $presentations_schema['properties'][IMailTemplatesConstants::speakers]['type'] = 'array';
        $presentations_schema['properties'][IMailTemplatesConstants::speakers]['items'] = $speaker_schema;

        $payload[IMailTemplatesConstants::accepted_presentations]['type'] = 'array';
        $payload[IMailTemplatesConstants::accepted_presentations]['items'] = $presentations_schema;

        $payload[IMailTemplatesConstants::accepted_moderated_presentations]['type'] = 'array';
        $payload[IMailTemplatesConstants::accepted_moderated_presentations]['items'] = $presentations_schema;

        $payload[IMailTemplatesConstants::alternate_presentations]['type'] = 'array';
        $payload[IMailTemplatesConstants::alternate_presentations]['items'] = $presentations_schema;

        $payload[IMailTemplatesConstants::alternate_moderated_presentations]['type'] = 'array';
        $payload[IMailTemplatesConstants::alternate_moderated_presentations]['items'] = $presentations_schema;

        $payload[IMailTemplatesConstants::rejected_presentations]['type'] = 'array';
        $payload[IMailTemplatesConstants::rejected_presentations]['items'] = $presentations_schema;

        $payload[IMailTemplatesConstants::rejected_moderated_presentations]['type'] = 'array';
        $payload[IMailTemplatesConstants::rejected_moderated_presentations]['items'] = $presentations_schema;

        return $payload;
    }
}