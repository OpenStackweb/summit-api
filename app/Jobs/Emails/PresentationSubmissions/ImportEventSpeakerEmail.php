<?php namespace App\Jobs\Emails\PresentationSubmissions;
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
use Illuminate\Support\Facades\Config;
use models\summit\Presentation;
use models\summit\PresentationSpeaker;

/**
 * Class ImportEventSpeakerEmail
 * @package App\Jobs\Emails\PresentationSubmissions
 */
class ImportEventSpeakerEmail extends AbstractEmailJob
{
    protected function getEmailEventSlug(): string
    {
        return self::EVENT_SLUG;
    }

    // metadata
    const EVENT_SLUG = 'SUMMIT_SUBMISSIONS_IMPORT_EVENT_SPEAKER';
    const EVENT_NAME = 'SUMMIT_SUBMISSIONS_IMPORT_EVENT_SPEAKER';
    const DEFAULT_TEMPLATE = 'SUMMIT_SUBMISSIONS_IMPORT_EVENT_SPEAKER';

    /**
     * ImportEventSpeakerEmail constructor.
     * @param Presentation $presentation
     * @param PresentationSpeaker $speaker
     * @param string|null $setPasswordLink
     */
    public function __construct(Presentation $presentation, PresentationSpeaker $speaker, ?string $setPasswordLink)
    {

        $summit = $presentation->getSummit();
        $creator = $presentation->getCreator();
        $selection_plan = $presentation->getSelectionPlan();

        $speaker_management_base_url = Config::get('cfp.base_url');
        $idp_base_url = Config::get('idp.base_url');
        $support_email = $summit->getSupportEmail();
        $support_email = !empty($support_email) ? $support_email: Config::get("cfp.support_email", null);

        if(empty($speaker_management_base_url))
            throw new \InvalidArgumentException('cfp.base_url is null.');

        if(empty($idp_base_url))
            throw new \InvalidArgumentException('idp.base_url is null.');

        if(empty($support_email))
            throw new \InvalidArgumentException('cfp.support_email is null.');

        $payload = [];
        $payload['summit_name'] = $summit->getName();
        $payload['summit_logo'] = $summit->getLogoUrl();
        $payload['creator_full_name'] = is_null($creator) ? '' : $creator->getFullName();
        $payload['creator_email'] = is_null($creator) ? '': $creator->getEmail();
        $payload['presentation_name'] = $presentation->getTitle();
        $payload['presentation_start_date'] = $presentation->getStartDateNice();
        $payload['presentation_end_date'] = $presentation->getEndDateNice();
        $payload['presentation_location'] = $presentation->getLocationName();

        $payload['selection_plan_name'] = is_null($selection_plan) ? '': $selection_plan->getName();
        $payload['presentation_edit_link'] = $presentation->getEditLink();
        $payload['summit_date'] = $summit->getMonthYear();
        $payload['until_date'] =is_null($selection_plan) ? '' : $selection_plan->getSubmissionEndDate()->format('d F, Y');
        $payload['selection_process_link'] = sprintf("%s/app/%s/selection_process", $speaker_management_base_url, $summit->getRawSlug());
        $payload['speaker_management_link'] = sprintf("%s/app/%s", $speaker_management_base_url, $summit->getRawSlug());
        $payload['bio_edit_link'] = sprintf("%s/app/%s/profile", $speaker_management_base_url, $summit->getRawSlug());
        if(!empty($setPasswordLink)){
            $payload['bio_edit_link'] = $setPasswordLink;
        }
        $payload['reset_password_link'] = sprintf("%s/auth/password/reset", $idp_base_url);
        $payload['support_email'] = $support_email;
        $payload['speaker_full_name'] = $speaker->getFullName(' ');
        if(empty($payload['speaker_full_name'])){
            $payload['speaker_full_name'] = $speaker->getEmail();
        }
        $payload['speaker_email'] = $speaker->getEmail();

        $template_identifier = $this->getEmailTemplateIdentifierFromEmailEvent($summit);

        parent::__construct($payload, $template_identifier, $payload['speaker_email']);
    }
}