<?php namespace App\Http\Controllers;
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

use App\Http\ValidationRulesFactories\AbstractValidationRulesFactory;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSubmitterSelectionProcessAcceptedAlternateEmail;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSubmitterSelectionProcessAcceptedOnlyEmail;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSubmitterSelectionProcessAcceptedRejectedEmail;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSubmitterSelectionProcessAlternateOnlyEmail;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSubmitterSelectionProcessAlternateRejectedEmail;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSubmitterSelectionProcessRejectedOnlyEmail;

/**
 * Class SummitSubmittersEmailsValidationRulesFactory
 * @package App\Http\Controllers
 */
final class SummitSubmittersEmailsValidationRulesFactory extends AbstractValidationRulesFactory
{
    /**
     * @param array $payload
     * @return array
     */
    public static function buildForAdd(array $payload = []): array
    {
        return [
            'email_flow_event' => 'required|string|in:' . join(',', [
                    PresentationSubmitterSelectionProcessAcceptedAlternateEmail::EVENT_SLUG,
                    PresentationSubmitterSelectionProcessAcceptedOnlyEmail::EVENT_SLUG,
                    PresentationSubmitterSelectionProcessAcceptedRejectedEmail::EVENT_SLUG,
                    PresentationSubmitterSelectionProcessAlternateOnlyEmail::EVENT_SLUG,
                    PresentationSubmitterSelectionProcessAlternateRejectedEmail::EVENT_SLUG,
                    PresentationSubmitterSelectionProcessRejectedOnlyEmail::EVENT_SLUG
                ]),
            'submitter_ids'             => 'sometimes|int_array',
            'excluded_submitter_ids'    => 'sometimes|int_array',
            'test_email_recipient'      => 'sometimes|email',
            'outcome_email_recipient'   => 'sometimes|email',
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