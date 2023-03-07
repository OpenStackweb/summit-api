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
        $payload['accepted_presentations'] = [];
        foreach($submitter->getAcceptedPresentations($summit, $filter) as $p){
            $payload['accepted_presentations'][] =
                SerializerRegistry::getInstance()->getSerializer($p, IPresentationSerializerTypes::SubmitterEmails)->serialize();
        }

        // alternates
        $payload['alternate_presentations'] = [];
        foreach($submitter->getAlternatePresentations($summit, $filter) as $p){
            $payload['alternate_presentations'][] =
                SerializerRegistry::getInstance()->getSerializer($p, IPresentationSerializerTypes::SubmitterEmails)->serialize();
        }

        // rejected
        $payload['rejected_presentations'] = [];
        foreach($submitter->getRejectedPresentations($summit, $filter) as $p){
            $payload['rejected_presentations'][] =
                SerializerRegistry::getInstance()->getSerializer($p, IPresentationSerializerTypes::SubmitterEmails)->serialize();
        }

        $payload['summit_name'] = $summit->getName();
        $payload['summit_logo'] = $summit->getLogoUrl();
        $payload['summit_schedule_url'] = $summit->getScheduleDefaultPageUrl();
        $payload['summit_site_url'] = $summit->getLink();
        $payload['submitter_full_name'] = $submitter->getFullName();
        $payload['submitter_email'] = $submitter->getEmail();

        if (!empty($test_email_recipient)) {
            Log::debug
            (
                sprintf
                (
                    "PresentationSubmitterSelectionProcessEmail::__construct replacing original email %s by %s and clearing cc field",
                    $payload['submitter_email'],
                    $test_email_recipient
                )
            );

            $payload['submitter_email'] = $test_email_recipient;
            $payload['cc_email'] = '';
        }

        $submitter_management_base_url = Config::get('cfp.base_url');
        if(empty($submitter_management_base_url))
            throw new \InvalidArgumentException('cfp.base_url is null.');

        $payload['registration_link'] = $summit->getRegistrationLink();
        $payload['virtual_event_site_link'] = $summit->getVirtualSiteUrl();

        $payload['bio_edit_link'] = sprintf("%s/app/profile", $submitter_management_base_url);
        $template_identifier = $this->getEmailTemplateIdentifierFromEmailEvent($summit);

        parent::__construct($payload, $template_identifier, $payload['submitter_email'], null,null);
    }
}