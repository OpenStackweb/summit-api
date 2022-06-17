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
use models\summit\PresentationSpeaker;
use models\summit\Summit;
use models\summit\SummitRegistrationPromoCode;
/**
 * Class PresentationSpeakerSelectionProcessAcceptedOnlyEmail
 * @package App\Jobs\Emails\PresentationSubmissions\SelectionProcess
 */
class PresentationSpeakerSelectionProcessAcceptedOnlyEmail extends PresentationSpeakerSelectionProcessEmail
{
    protected function getEmailEventSlug(): string
    {
        return self::EVENT_SLUG;
    }

    // metadata
    const EVENT_SLUG = 'SUMMIT_SUBMISSIONS_PRESENTATION_SPEAKER_ACCEPTED_ONLY';
    const EVENT_NAME = 'SUMMIT_SUBMISSIONS_PRESENTATION_SPEAKER_ACCEPTED_ONLY';
    const DEFAULT_TEMPLATE = 'SUMMIT_SUBMISSIONS_PRESENTATION_SPEAKER_ACCEPTED_ONLY';

    /**
     * PresentationSpeakerSelectionProcessAcceptedOnlyEmail constructor.
     * @param Summit $summit
     * @param SummitRegistrationPromoCode $promo_code
     * @param PresentationSpeaker $speaker
     * @param string $speaker_role
     * @param string $confirmation_token
     */
    public function __construct
    (
        Summit $summit,
        SummitRegistrationPromoCode $promo_code,
        PresentationSpeaker $speaker,
        string $speaker_role,
        string $confirmation_token
    )
    {
        parent::__construct($summit, $speaker, $promo_code);

        $summit = $promo_code->getSummit();
        $this->payload['accepted_presentations'] = [];
        foreach($speaker->getPublishedRegularPresentations($summit, $speaker_role) as $p){
            $this->payload['accepted_presentations'][] = ['title' => $p->getTitle()];
        }

        $payload['speaker_confirmation_link'] = sprintf("%s?t=%s", $this->payload['speaker_confirmation_link'], base64_encode($confirmation_token));
    }
}