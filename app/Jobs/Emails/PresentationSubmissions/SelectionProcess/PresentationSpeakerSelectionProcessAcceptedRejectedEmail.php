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
use ModelSerializers\IPresentationSerializerTypes;
use ModelSerializers\SerializerRegistry;

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
     * PresentationSpeakerSelectionProcessAcceptedRejectedEmail constructor.
     * @param Summit $summit
     * @param SummitRegistrationPromoCode $promo_code
     * @param PresentationSpeaker $speaker
     * @param string $confirmation_token
     */
    public function __construct
    (
        Summit $summit,
        SummitRegistrationPromoCode $promo_code,
        PresentationSpeaker $speaker,
        string $confirmation_token
    )
    {
        parent::__construct($summit, $speaker, $promo_code);

        $this->payload['accepted_presentations'] = [];
        foreach($speaker->getAcceptedPresentations($summit, PresentationSpeaker::RoleSpeaker) as $p){
            $this->payload['accepted_presentations'][] =
                SerializerRegistry::getInstance()->getSerializer($p, IPresentationSerializerTypes::SpeakerEmails)->serialize();
        }

        $this->payload['accepted_moderated_presentations'] = [];
        foreach($speaker->getAcceptedPresentations($summit, PresentationSpeaker::RoleModerator) as $p){
            $this->payload['accepted_moderated_presentations'][] =
                SerializerRegistry::getInstance()->getSerializer($p, IPresentationSerializerTypes::SpeakerEmails)->serialize();
        }

        $this->payload['rejected_presentations'] = [];
        foreach($speaker->getRejectedPresentations($summit, PresentationSpeaker::RoleSpeaker) as $p){
            $this->payload['rejected_presentations'][] =
                SerializerRegistry::getInstance()->getSerializer($p, IPresentationSerializerTypes::SpeakerEmails)->serialize();
        }

        $this->payload['rejected_moderated_presentations'] = [];
        foreach($speaker->getRejectedPresentations($summit, PresentationSpeaker::RoleModerator) as $p){
            $this->payload['rejected_moderated_presentations'][] =
                SerializerRegistry::getInstance()->getSerializer($p, IPresentationSerializerTypes::SpeakerEmails)->serialize();
        }

        $payload['speaker_confirmation_link'] = sprintf("%s?t=%s", $this->payload['speaker_confirmation_link'], base64_encode($confirmation_token));
    }
}