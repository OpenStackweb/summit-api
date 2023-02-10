<?php namespace App\Jobs\Emails\PresentationSubmissions\SelectionProcess;
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

use App\Services\Utils\Email\SpeakersAnnouncementEmailConfigDTO;
use Illuminate\Support\Facades\Log;
use models\summit\PresentationSpeaker;
use models\summit\Summit;
use models\summit\SummitRegistrationPromoCode;
use utils\Filter;

/**
 * Class PresentationSpeakerSelectionProcessAcceptedRejectedEmail
 * @package App\Jobs\Emails\PresentationSubmissions\SelectionProcess
 */
class PresentationSpeakerSelectionProcessAcceptedRejectedEmail extends PresentationSpeakerSelectionProcessEmail
{
    protected function getEmailEventSlug(): string
    {
        return self::EVENT_SLUG;
    }

    // metadata
    const EVENT_SLUG = 'SUMMIT_SUBMISSIONS_PRESENTATION_SPEAKER_ACCEPTED_REJECTED';
    const EVENT_NAME = 'SUMMIT_SUBMISSIONS_PRESENTATION_SPEAKER_ACCEPTED_REJECTED';
    const DEFAULT_TEMPLATE = 'SUMMIT_SUBMISSIONS_PRESENTATION_SPEAKER_ACCEPTED_REJECTED';

    /**
     * @param Summit $summit
     * @param SummitRegistrationPromoCode|null $promo_code
     * @param PresentationSpeaker $speaker
     * @param string $test_email_recipient
     * @param SpeakersAnnouncementEmailConfigDTO $speaker_announcement_email_config
     * @param string|null $confirmation_token
     * @param Filter|null $filter
     */
    public function __construct
    (
        Summit                              $summit,
        ?SummitRegistrationPromoCode        $promo_code,
        PresentationSpeaker                 $speaker,
        string                              $test_email_recipient,
        SpeakersAnnouncementEmailConfigDTO  $speaker_announcement_email_config,
        ?string                             $confirmation_token = null,
        ?Filter                             $filter = null
    )
    {
        parent::__construct($summit, $speaker, $test_email_recipient, $speaker_announcement_email_config, $promo_code, $filter);

        if (!empty($confirmation_token)) {
            $this->payload['speaker_confirmation_link'] = sprintf("%s?t=%s", $this->payload['speaker_confirmation_link'], base64_encode($confirmation_token));
        }

        Log::debug(sprintf("PresentationSpeakerSelectionProcessAcceptedRejectedEmail::__construct payload %s", json_encode($this->payload)));

    }
}