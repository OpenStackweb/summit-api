<?php namespace App\Jobs\Emails\PresentationSubmissions;
/**
 * Copyright 2026 OpenStack Foundation
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

/**
 * Class PresentationSubmissionReopenedEmail
 *
 * One job for all recipients (submitter, speaker, moderator) -- the reopen copy is
 * role-independent, unlike the sibling trio (Creator/Speaker/Moderator notification), so the
 * recipient is passed as (email, name) rather than as a typed entity.
 *
 * @package App\Jobs\Emails\PresentationSubmissions
 */
class PresentationSubmissionReopenedEmail extends AbstractSummitEmailJob
{
    protected function getEmailEventSlug(): string
    {
        return self::EVENT_SLUG;
    }

    // metadata
    const EVENT_SLUG = 'SUMMIT_SUBMISSIONS_PRESENTATION_SUBMISSION_REOPENED';
    const EVENT_NAME = 'SUMMIT_SUBMISSIONS_PRESENTATION_SUBMISSION_REOPENED';
    const DEFAULT_TEMPLATE = 'SUMMIT_SUBMISSIONS_PRESENTATION_SUBMISSION_REOPENED';

    /**
     * PresentationSubmissionReopenedEmail constructor.
     * @param Presentation $presentation
     * @param string $to_email
     * @param string $to_full_name
     */
    public function __construct(Presentation $presentation, string $to_email, string $to_full_name)
    {
        $summit = $presentation->getSummit();
        $selection_plan = $presentation->getSelectionPlan();

        if (is_null($selection_plan))
            throw new \InvalidArgumentException('Presentation selection plan is null.');

        $support_email = $summit->getSupportEmail();
        $support_email = !empty($support_email) ? $support_email : Config::get("cfp.support_email", null);

        if (empty($support_email))
            throw new \InvalidArgumentException('cfp.support_email is null.');

        $payload = [];

        $payload[IMailTemplatesConstants::full_name] = $to_full_name;
        $payload[IMailTemplatesConstants::presentation_title] = $presentation->getTitle();
        $payload[IMailTemplatesConstants::selection_plan_name] = $selection_plan->getName();
        $payload[IMailTemplatesConstants::summit_slug] = $summit->getRawSlug();
        $payload[IMailTemplatesConstants::selection_plan_id] = $selection_plan->getId();
        $payload[IMailTemplatesConstants::presentation_id] = $presentation->getId();
        $payload[IMailTemplatesConstants::support_email] = $support_email;

        // until_date deliberately breaks the sibling format (date-only): a reopen window is
        // measured in hours, so render summit-local date, time and zone label.
        $until = $presentation->getSubmissionReopenedUntil();
        $local = $selection_plan->convertDateFromUTC2TimeZone($until);
        $payload[IMailTemplatesConstants::until_date] = is_null($local)
            ? $until->format('F d, Y g:i a') . ' UTC'
            : $local->format('F d, Y g:i a') . ' ' . $summit->getTimeZoneLabel();

        $template_identifier = $this->getEmailTemplateIdentifierFromEmailEvent($summit);

        parent::__construct($summit, $payload, $template_identifier, $to_email);
    }

    /**
     * @return array
     */
    public static function getEmailTemplateSchema(): array
    {
        $payload = parent::getEmailTemplateSchema();

        $payload[IMailTemplatesConstants::full_name]['type'] = 'string';
        $payload[IMailTemplatesConstants::presentation_title]['type'] = 'string';
        $payload[IMailTemplatesConstants::until_date]['type'] = 'string';
        $payload[IMailTemplatesConstants::selection_plan_name]['type'] = 'string';
        $payload[IMailTemplatesConstants::summit_slug]['type'] = 'string';
        $payload[IMailTemplatesConstants::support_email]['type'] = 'string';
        $payload[IMailTemplatesConstants::selection_plan_id]['type'] = 'int';
        $payload[IMailTemplatesConstants::presentation_id]['type'] = 'int';

        return $payload;
    }
}
