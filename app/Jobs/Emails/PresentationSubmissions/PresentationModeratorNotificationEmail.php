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
 * Class PresentationModeratorNotificationEmail
 * @package App\Jobs\Emails\PresentationSubmissions
 */
class PresentationModeratorNotificationEmail extends AbstractEmailJob
{
    protected function getEmailEventSlug(): string
    {
        return self::EVENT_SLUG;
    }

    // metadata
    const EVENT_SLUG = 'SUMMIT_SUBMISSIONS_PRESENTATION_MODERATOR_NOTIFICATION';
    const EVENT_NAME = 'SUMMIT_SUBMISSIONS_PRESENTATION_MODERATOR_NOTIFICATION';
    const DEFAULT_TEMPLATE = 'SUMMIT_SUBMISSIONS_PRESENTATION_MODERATOR_NOTIFICATION';

    /**
     * PresentationModeratorNotificationEmail constructor.
     * @param PresentationSpeaker $moderator
     * @param Presentation $presentation
     */
    public function __construct(PresentationSpeaker $moderator, Presentation $presentation)
    {

        $summit = $presentation->getSummit();
        $creator = $presentation->getCreator();
        $selection_plan = $presentation->getSelectionPlan();

        if(is_null($selection_plan))
            throw new \InvalidArgumentException('Presentation selection plan is null.');

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
        $payload['speaker_full_name'] = $moderator->getFullName(" ");
        $payload['speaker_email'] = $moderator->getEmail();
        $payload['creator_full_name'] = $creator->getFullName();
        $payload['creator_email'] = $creator->getEmail();
        $payload['selection_plan_name'] = $selection_plan->getName();
        $payload['presentation_edit_link'] = $presentation->getEditLink();

        $summitBeginDate = $summit->getLocalBeginDate();
        $payload['summit_date'] = !is_null($summitBeginDate)? $summitBeginDate->format("F d, Y") : "";
        $submissionEndDateLocal = $selection_plan->getSubmissionEndDateLocal();
        $payload['until_date'] = !is_null($submissionEndDateLocal) ? $submissionEndDateLocal->format('F d, Y') : "";

        $payload['selection_process_link'] = sprintf("%s/app/%s/%s/selection_process", $speaker_management_base_url, $summit->getRawSlug(), $selection_plan->getId());
        $payload['speaker_management_link'] = sprintf("%s/app/%s/%s", $speaker_management_base_url, $summit->getRawSlug(), $selection_plan->getId());
        $payload['bio_edit_link'] = sprintf("%s/app/%s/profile", $speaker_management_base_url, $summit->getRawSlug());
        $payload['reset_password_link'] = sprintf("%s/auth/password/reset", $idp_base_url);
        $payload['support_email'] = $support_email;

        $template_identifier = $this->getEmailTemplateIdentifierFromEmailEvent($summit);

        parent::__construct($payload, $template_identifier, $payload['speaker_email']);
    }

}