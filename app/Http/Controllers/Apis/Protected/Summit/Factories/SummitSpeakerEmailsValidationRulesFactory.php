<?php namespace App\Http\Controllers;
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

use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSpeakerSelectionProcessAcceptedAlternateEmail;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSpeakerSelectionProcessAcceptedOnlyEmail;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSpeakerSelectionProcessAcceptedRejectedEmail;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSpeakerSelectionProcessAlternateOnlyEmail;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSpeakerSelectionProcessAlternateRejectedEmail;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSpeakerSelectionProcessRejectedEmail;

/**
 * Class SummitSpeakerValidationRulesFactory
 * @package App\Http\Controllers
 */
final class SummitSpeakerEmailsValidationRulesFactory
{
    /**
     * @param array $payload
     * @return array
     */
    public static function build(array $payload = []): array
    {
        return [
            'email_flow_event' => 'required|string|in:' . join(',', [
                    PresentationSpeakerSelectionProcessAcceptedAlternateEmail::EVENT_SLUG,
                    PresentationSpeakerSelectionProcessAcceptedOnlyEmail::EVENT_SLUG,
                    PresentationSpeakerSelectionProcessAcceptedRejectedEmail::EVENT_SLUG,
                    PresentationSpeakerSelectionProcessAlternateOnlyEmail::EVENT_SLUG,
                    PresentationSpeakerSelectionProcessAlternateRejectedEmail::EVENT_SLUG,
                    PresentationSpeakerSelectionProcessRejectedEmail::EVENT_SLUG
                ]),
            'speaker_ids'               => 'sometimes|int_array',
            'test_email_recipient'      => 'sometimes|email',
            'outcome_email_recipient'   => 'sometimes|email',
        ];
    }
}