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
use ModelSerializers\IPresentationSerializerTypes;
use ModelSerializers\SerializerRegistry;

/**
 * Class PresentationSpeakerSelectionProcessRejectedEmail
 * @package App\Jobs\Emails\PresentationSubmissions\SelectionProcess
 */
class PresentationSpeakerSelectionProcessRejectedEmail extends PresentationSpeakerSelectionProcessEmail
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
     * PresentationSpeakerSelectionProcessRejectedEmail constructor.
     * @param Summit $summit
     * @param PresentationSpeaker $speaker
     */
    public function __construct
    (
        Summit $summit,
        PresentationSpeaker $speaker
    )
    {
        parent::__construct($summit, $speaker, null);

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
    }
}