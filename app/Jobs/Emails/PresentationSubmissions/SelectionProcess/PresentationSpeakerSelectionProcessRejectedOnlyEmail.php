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
use utils\Filter;

/**
 * Class PresentationSpeakerSelectionProcessRejectedOnlyEmail
 * @package App\Jobs\Emails\PresentationSubmissions\SelectionProcess
 */
class PresentationSpeakerSelectionProcessRejectedOnlyEmail extends PresentationSpeakerSelectionProcessEmail
{
    protected function getEmailEventSlug(): string
    {
        return self::EVENT_SLUG;
    }

    // metadata
    const EVENT_SLUG = 'SUMMIT_SUBMISSIONS_PRESENTATION_SPEAKER_REJECTED_ONLY';
    const EVENT_NAME = 'SUMMIT_SUBMISSIONS_PRESENTATION_SPEAKER_REJECTED_ONLY';
    const DEFAULT_TEMPLATE = 'SUMMIT_SUBMISSIONS_PRESENTATION_SPEAKER_REJECTED_ONLY';

    /**
     * @param Summit $summit
     * @param PresentationSpeaker $speaker
     * @param string $test_email_recipient
     * @param SpeakersAnnouncementEmailConfigDTO $speaker_announcement_email_config
     * @param Filter|null $filter
     */
    public function __construct
    (
        Summit                              $summit,
        PresentationSpeaker                 $speaker,
        string                              $test_email_recipient,
        SpeakersAnnouncementEmailConfigDTO  $speaker_announcement_email_config,
        ?Filter                             $filter = null
    )
    {
        parent::__construct($summit, $speaker, $test_email_recipient, $speaker_announcement_email_config, null, $filter);
        Log::debug(sprintf("PresentationSpeakerSelectionProcessRejectedEmail::__construct payload %s", json_encode($this->payload)));
    }
}