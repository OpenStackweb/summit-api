<?php namespace App\Jobs\Emails\PresentationSubmissions\SelectionProcess;
/**
 * Copyright 2023 OpenStack Foundation
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
use App\Jobs\Emails\IMailTemplatesConstants;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use models\main\Member;
use models\summit\Summit;
use ModelSerializers\IPresentationSerializerTypes;
use ModelSerializers\SerializerRegistry;
use utils\Filter;

/**
 * Class PresentationSubmitterSelectionProcessEmail
 * @package App\Jobs\Emails\PresentationSubmissions
 */
abstract class PresentationSubmitterSelectionProcessEmail extends AbstractEmailJob
{
    protected $filter;

    /**
     * @param Summit $summit
     * @param Member $submitter
     * @param string|null $test_email_recipient
     * @param Filter|null $filter
     */
    public function __construct
    (
        Summit $summit,
        Member $submitter,
        ?string $test_email_recipient,
        ?Filter $filter = null
    ){

        if(!is_null($filter))
            $this->filter = $filter->getOriginalExp();
        $payload  = [];

        // accepted ones
        $payload[IMailTemplatesConstants::accepted_presentations] = [];
        foreach($submitter->getAcceptedPresentations($summit, $filter) as $p){
            $payload[IMailTemplatesConstants::accepted_presentations][] =
                SerializerRegistry::getInstance()->getSerializer($p, IPresentationSerializerTypes::SubmitterEmails)->serialize();
        }

        // alternates
        $payload[IMailTemplatesConstants::alternate_presentations] = [];
        foreach($submitter->getAlternatePresentations($summit, $filter) as $p){
            $payload[IMailTemplatesConstants::alternate_presentations][] =
                SerializerRegistry::getInstance()->getSerializer($p, IPresentationSerializerTypes::SubmitterEmails)->serialize();
        }

        // rejected
        $payload[IMailTemplatesConstants::rejected_presentations] = [];
        foreach($submitter->getRejectedPresentations($summit, $filter) as $p){
            $payload[IMailTemplatesConstants::rejected_presentations][] =
                SerializerRegistry::getInstance()->getSerializer($p, IPresentationSerializerTypes::SubmitterEmails)->serialize();
        }

        $payload[IMailTemplatesConstants::summit_name] = $summit->getName();
        $payload[IMailTemplatesConstants::summit_logo] = $summit->getLogoUrl();
        $payload[IMailTemplatesConstants::summit_schedule_url] = $summit->getScheduleDefaultPageUrl();
        $payload[IMailTemplatesConstants::summit_site_url] = $summit->getLink();
        $payload[IMailTemplatesConstants::submitter_full_name] = $submitter->getFullName();
        $payload[IMailTemplatesConstants::submitter_email] = $submitter->getEmail();

        if (!empty($test_email_recipient)) {
            Log::debug
            (
                sprintf
                (
                    "PresentationSubmitterSelectionProcessEmail::__construct replacing original email %s by %s",
                    $payload['submitter_email'],
                    $test_email_recipient
                )
            );

            $payload[IMailTemplatesConstants::submitter_email] = $test_email_recipient;
        }

        $submitter_management_base_url = Config::get('cfp.base_url');
        if(empty($submitter_management_base_url))
            throw new \InvalidArgumentException('cfp.base_url is null.');

        $payload[IMailTemplatesConstants::registration_link] = $summit->getRegistrationLink();
        $payload[IMailTemplatesConstants::virtual_event_site_link] = $summit->getVirtualSiteUrl();

        $payload[IMailTemplatesConstants::bio_edit_link] = sprintf("%s/app/profile", $submitter_management_base_url);
        $template_identifier = $this->getEmailTemplateIdentifierFromEmailEvent($summit);

        parent::__construct($payload, $template_identifier, $payload[IMailTemplatesConstants::submitter_email], null,null);
    }

    /**
     * @return array
     */
    public static function getEmailTemplateSchema(): array{
        $payload = [];
        $payload[IMailTemplatesConstants::summit_name]['type'] = 'string';
        $payload[IMailTemplatesConstants::summit_logo]['type'] = 'string';
        $payload[IMailTemplatesConstants::summit_schedule_url]['type'] = 'string';
        $payload[IMailTemplatesConstants::summit_site_url]['type'] = 'string';
        $payload[IMailTemplatesConstants::submitter_full_name]['type'] = 'string';
        $payload[IMailTemplatesConstants::submitter_email]['type'] = 'string';
        $payload[IMailTemplatesConstants::registration_link]['type'] = 'string';
        $payload[IMailTemplatesConstants::virtual_event_site_link]['type'] = 'string';
        $payload[IMailTemplatesConstants::bio_edit_link]['type'] = 'string';

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

        $payload[IMailTemplatesConstants::alternate_presentations]['type'] = 'array';
        $payload[IMailTemplatesConstants::alternate_presentations]['items'] = $presentations_schema;

        $payload[IMailTemplatesConstants::rejected_presentations]['type'] = 'array';
        $payload[IMailTemplatesConstants::rejected_presentations]['items'] = $presentations_schema;

        return $payload;
    }
}