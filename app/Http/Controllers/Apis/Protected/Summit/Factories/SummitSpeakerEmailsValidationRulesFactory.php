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

use App\Http\ValidationRulesFactories\AbstractValidationRulesFactory;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSpeakerSelectionProcessAcceptedAlternateEmail;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSpeakerSelectionProcessAcceptedOnlyEmail;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSpeakerSelectionProcessAcceptedRejectedEmail;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSpeakerSelectionProcessAlternateOnlyEmail;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSpeakerSelectionProcessAlternateRejectedEmail;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSpeakerSelectionProcessRejectedOnlyEmail;

/**
 * Class SummitSpeakerValidationRulesFactory
 * @package App\Http\Controllers
 */
final class SummitSpeakerEmailsValidationRulesFactory extends AbstractValidationRulesFactory
{
    /**
     * @param array $payload
     * @return array
     */
    public static function buildForAdd(array $payload = []): array
    {
        return [
            'email_flow_event' => 'required|string|in:' . join(',', [
                    PresentationSpeakerSelectionProcessAcceptedAlternateEmail::EVENT_SLUG,
                    PresentationSpeakerSelectionProcessAcceptedOnlyEmail::EVENT_SLUG,
                    PresentationSpeakerSelectionProcessAcceptedRejectedEmail::EVENT_SLUG,
                    PresentationSpeakerSelectionProcessAlternateOnlyEmail::EVENT_SLUG,
                    PresentationSpeakerSelectionProcessAlternateRejectedEmail::EVENT_SLUG,
                    PresentationSpeakerSelectionProcessRejectedOnlyEmail::EVENT_SLUG
                ]),
            'speaker_ids'               => 'sometimes|int_array',
            'test_email_recipient'      => 'sometimes|email',
            'outcome_email_recipient'   => 'sometimes|email',
            'should_send_copy_2_submitter' => 'sometimes|boolean',
            'should_resend' => 'sometimes|boolean',
        ];
    }

    /**
     * @param array $payload
     * @return array
     */
    public static function buildForUpdate(array $payload = []): array
    {
        return self::buildForAdd($payload);
    }
}