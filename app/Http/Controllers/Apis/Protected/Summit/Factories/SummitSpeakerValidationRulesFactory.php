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
use App\Jobs\Emails\PresentationSubmissions\SpeakerAcceptedAlternateEmail;
use App\Jobs\Emails\PresentationSubmissions\SpeakerAcceptedOnlyEmail;
use App\Jobs\Emails\PresentationSubmissions\SpeakerAcceptedRejectedEmail;
use App\Jobs\Emails\PresentationSubmissions\SpeakerAlternateOnlyEmail;
use App\Jobs\Emails\PresentationSubmissions\SpeakerAlternateRejectedEmail;
use App\Jobs\Emails\PresentationSubmissions\SpeakerRejectedOnlyEmail;

/**
 * Class SummitSpeakerValidationRulesFactory
 * @package App\Http\Controllers
 */
final class SummitSpeakerValidationRulesFactory
{
    /**
     * @param array $payload
     * @return array
     */
    public static function build(array $payload = []): array
    {
        return [
            'email_flow_event' => 'required|string|in:' . join(',', [
                    SpeakerAcceptedAlternateEmail::EVENT_SLUG,
                    SpeakerAcceptedOnlyEmail::EVENT_SLUG,
                    SpeakerAcceptedRejectedEmail::EVENT_SLUG,
                    SpeakerAlternateOnlyEmail::EVENT_SLUG,
                    SpeakerAlternateRejectedEmail::EVENT_SLUG,
                    SpeakerRejectedOnlyEmail::EVENT_SLUG
                ]),
            'speaker_ids'               => 'sometimes|int_array',
            'test_email_recipient'      => 'sometimes|email',
            'outcome_email_recipient'   => 'sometimes|email',
        ];
    }
}