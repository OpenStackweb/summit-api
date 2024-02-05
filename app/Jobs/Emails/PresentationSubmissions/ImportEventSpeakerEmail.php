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
use App\Jobs\Emails\AbstractSummitEmailJob;
use App\Jobs\Emails\IMailTemplatesConstants;
use Illuminate\Support\Facades\Config;
use models\summit\Presentation;
use models\summit\PresentationSpeaker;

/**
 * Class ImportEventSpeakerEmail
 * @package App\Jobs\Emails\PresentationSubmissions
 */
class ImportEventSpeakerEmail extends AbstractSummitEmailJob
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
        $payload[IMailTemplatesConstants::creator_full_name] = is_null($creator) ? '' : $creator->getFullName();
        $payload[IMailTemplatesConstants::creator_email] = is_null($creator) ? '': $creator->getEmail();
        $payload[IMailTemplatesConstants::presentation_name] = $presentation->getTitle();
        $payload[IMailTemplatesConstants::presentation_start_date] = $presentation->getStartDateNice();
        $payload[IMailTemplatesConstants::presentation_end_date] = $presentation->getEndDateNice();
        $payload[IMailTemplatesConstants::presentation_location] = $presentation->getLocationName();

        $payload[IMailTemplatesConstants::selection_plan_name] = is_null($selection_plan) ? '': $selection_plan->getName();
        $payload[IMailTemplatesConstants::presentation_edit_link] = $presentation->getEditLink();
        $payload[IMailTemplatesConstants::until_date] =is_null($selection_plan) ? '' : $selection_plan->getSubmissionEndDate()->format('d F, Y');
        $payload[IMailTemplatesConstants::selection_process_link] = sprintf("%s/app/%s/selection_process", $speaker_management_base_url, $summit->getRawSlug());
        $payload[IMailTemplatesConstants::speaker_management_link] = sprintf("%s/app/%s", $speaker_management_base_url, $summit->getRawSlug());
        $payload[IMailTemplatesConstants::bio_edit_link] = sprintf("%s/app/%s/profile", $speaker_management_base_url, $summit->getRawSlug());
        if(!empty($setPasswordLink)){
            $payload[IMailTemplatesConstants::bio_edit_link] = $setPasswordLink;
        }
        $payload[IMailTemplatesConstants::reset_password_link] = sprintf("%s/auth/password/reset", $idp_base_url);
        $payload[IMailTemplatesConstants::support_email] = $support_email;
        $payload[IMailTemplatesConstants::speaker_full_name] = $speaker->getFullName(' ');
        if(empty($payload[IMailTemplatesConstants::speaker_full_name])){
            $payload[IMailTemplatesConstants::speaker_full_name] = $speaker->getEmail();
        }
        $payload[IMailTemplatesConstants::speaker_email] = $speaker->getEmail();

        $template_identifier = $this->getEmailTemplateIdentifierFromEmailEvent($summit);

        parent::__construct($summit, $payload, $template_identifier, $payload[IMailTemplatesConstants::speaker_email]);
    }

    /**
     * @return array
     */
    public static function getEmailTemplateSchema(): array{

        $payload = parent::getEmailTemplateSchema();

        $payload[IMailTemplatesConstants::creator_full_name]['type'] = 'string';
        $payload[IMailTemplatesConstants::creator_email]['type'] = 'string';
        $payload[IMailTemplatesConstants::presentation_name]['type'] = 'string';
        $payload[IMailTemplatesConstants::presentation_start_date]['type'] = 'string';
        $payload[IMailTemplatesConstants::presentation_end_date]['type'] = 'string';
        $payload[IMailTemplatesConstants::presentation_location]['type'] = 'string';
        $payload[IMailTemplatesConstants::selection_plan_name]['type'] = 'string';
        $payload[IMailTemplatesConstants::presentation_edit_link]['type'] = 'string';
        $payload[IMailTemplatesConstants::until_date]['type'] = 'string';
        $payload[IMailTemplatesConstants::selection_process_link]['type'] = 'string';
        $payload[IMailTemplatesConstants::speaker_management_link]['type'] = 'string';
        $payload[IMailTemplatesConstants::bio_edit_link]['type'] = 'string';
        $payload[IMailTemplatesConstants::reset_password_link]['type'] = 'string';
        $payload[IMailTemplatesConstants::support_email]['type'] = 'string';
        $payload[IMailTemplatesConstants::speaker_full_name]['type'] = 'string';
        $payload[IMailTemplatesConstants::speaker_email]['type'] = 'string';

        return $payload;
    }
}