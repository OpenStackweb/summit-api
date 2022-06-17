<?php namespace App\Jobs\Emails\PresentationSubmissions\SelectionProcess;
/**
 * Copyright 2022 OpenStack Foundation
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
use App\Services\Utils\Facades\EmailExcerpt;
use models\summit\Summit;
/**
 * Class PresentationSpeakerSelectionProcessExcerptEmail
 * @package App\Jobs\Emails\PresentationSubmissions\SelectionProcess
 */
class PresentationSpeakerSelectionProcessExcerptEmail extends AbstractEmailJob
{
    protected function getEmailEventSlug(): string
    {
        return self::EVENT_SLUG;
    }

    // metadata
    const EVENT_SLUG = 'SUMMIT_SUBMISSIONS_PRESENTATION_SPEAKER_EXCERPT';
    const EVENT_NAME = 'SUMMIT_SUBMISSIONS_PRESENTATION_SPEAKER_EXCERPT';
    const DEFAULT_TEMPLATE = 'SUMMIT_SUBMISSIONS_PRESENTATION_SPEAKER_EXCERPT';

    /**
     * PresentationSpeakerSelectionProcessExcerptEmail constructor.
     * @param Summit $summit
     */
    public function __construct
    (
        Summit $summit,
        string $outcome_email_recipient
    ){
        $payload = [];

        $report = EmailExcerpt::getReport();
        $report_lines = [];
        $itemsCount = count($report);

        foreach ($report as $reportItem) {
            $report_lines[] = "Email type {$reportItem['email_type']} sent to speaker {$reportItem['speaker_email']}";
        }

        $payload['report'] = $report_lines;
        $payload['report_summary'] = "A total of {$itemsCount} emails were sent";

        $template_identifier = $this->getEmailTemplateIdentifierFromEmailEvent($summit);

        parent::__construct($payload, $template_identifier, $outcome_email_recipient);
    }
}