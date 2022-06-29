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

use Illuminate\Support\Facades\Log;
use models\summit\PresentationSpeaker;
use models\summit\Summit;
use models\summit\SummitRegistrationPromoCode;
use ModelSerializers\IPresentationSerializerTypes;
use ModelSerializers\SerializerRegistry;

/**
 * Class PresentationSpeakerSelectionProcessAlternateOnlyEmail
 * @package App\Jobs\Emails\PresentationSubmissions\SelectionProcess
 */
class PresentationSpeakerSelectionProcessAlternateOnlyEmail extends PresentationSpeakerSelectionProcessEmail
{
    protected function getEmailEventSlug(): string
    {
        return self::EVENT_SLUG;
    }

    // metadata
    const EVENT_SLUG = 'SUMMIT_SUBMISSIONS_PRESENTATION_SPEAKER_ALTERNATE_ONLY';
    const EVENT_NAME = 'SUMMIT_SUBMISSIONS_PRESENTATION_SPEAKER_ALTERNATE_ONLY';
    const DEFAULT_TEMPLATE = 'SUMMIT_SUBMISSIONS_PRESENTATION_SPEAKER_ALTERNATE_ONLY';

    /**
     * @param Summit $summit
     * @param SummitRegistrationPromoCode $promo_code
     * @param PresentationSpeaker $speaker
     * @param string|null $confirmation_token
     */
    public function __construct
    (
        Summit $summit,
        ?SummitRegistrationPromoCode $promo_code,
        PresentationSpeaker $speaker,
        ?string $confirmation_token = null
    )
    {
      
        $payload = [];
        $payload['alternate_presentations'] = [];
        foreach($speaker->getAlternatePresentations($summit, PresentationSpeaker::RoleSpeaker) as $p){
            $payload['alternate_presentations'][] =
                SerializerRegistry::getInstance()->getSerializer($p, IPresentationSerializerTypes::SpeakerEmails)->serialize();
        }

        $payload['alternate_moderated_presentations'] = [];
        foreach($speaker->getAlternatePresentations($summit, PresentationSpeaker::RoleModerator) as $p){
            $payload['alternate_moderated_presentations'][] =
                SerializerRegistry::getInstance()->getSerializer($p, IPresentationSerializerTypes::SpeakerEmails)->serialize();
        }

        parent::__construct($payload, $summit, $speaker, $promo_code);

        if(!empty($confirmation_token)) {
            $this->payload['speaker_confirmation_link'] = sprintf("%s?t=%s", $this->payload['speaker_confirmation_link'], base64_encode($confirmation_token));
        }

        Log::debug(sprintf("PresentationSpeakerSelectionProcessAlternateOnlyEmail::__construct payload %s", json_encode($payload)));

    }
}