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
use App\Services\utils\IEmailExcerptService;
use Illuminate\Support\Facades\Log;
use models\summit\Summit;
/**
 * Class PresentationSubmitterSelectionProcessExcerptEmail
 * @package App\Jobs\Emails\PresentationSubmissions\SelectionProcess
 */
class PresentationSubmitterSelectionProcessExcerptEmail extends AbstractEmailJob
{
    protected function getEmailEventSlug(): string
    {
        return self::EVENT_SLUG;
    }

    // metadata
    const EVENT_SLUG = 'SUMMIT_SUBMISSIONS_PRESENTATION_SUBMITTER_EXCERPT';
    const EVENT_NAME = 'SUMMIT_SUBMISSIONS_PRESENTATION_SUBMITTER_EXCERPT';
    const DEFAULT_TEMPLATE = 'SUMMIT_SUBMISSIONS_PRESENTATION_SUBMITTER_EXCERPT';

    /**
     * PresentationSubmitterSelectionProcessExcerptEmail constructor.
     * @param Summit $summit
     * @param string $outcome_email_recipient
     * @param array $report
     */
    public function __construct
    (
        Summit $summit,
        string $outcome_email_recipient,
        array $report
    ){
        $payload = [];
        $report_lines = [];

        foreach ($report as $reportItem) {
            $type = $reportItem['type']  ?? null;
            if($type == IEmailExcerptService::SpeakerEmailType)
                $report_lines[] = "Email type {$reportItem['email_type']} sent to submitter {$reportItem['submitter_email']}.";
            else if($type == IEmailExcerptService::ErrorType)
                $report_lines[] = "ERROR {$reportItem['message']}.";
            else if($type == IEmailExcerptService::InfoType)
                $report_lines[] = "INFO {$reportItem['message']}.";
        }

        $payload['report'] = $report_lines;

        $template_identifier = $this->getEmailTemplateIdentifierFromEmailEvent($summit);

        parent::__construct($payload, $template_identifier, $outcome_email_recipient);

        Log::debug(sprintf("PresentationSubmitterSelectionProcessExcerptEmail::__construct payload %s", json_encode($payload)));

    }
}