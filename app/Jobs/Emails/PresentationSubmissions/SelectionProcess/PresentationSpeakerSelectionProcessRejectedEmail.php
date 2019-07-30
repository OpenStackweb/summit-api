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
     * @param string $speaker_role
     */
    public function __construct
    (
        Summit $summit,
        PresentationSpeaker $speaker,
        string $speaker_role
    )
    {
        parent::__construct($summit, $speaker);

        $this->payload['rejected_presentations'] = [];
        foreach($speaker->getRejectedPresentations($summit, $speaker_role) as $p){
            $this->payload['rejected_presentations'][] = ['title' => $p->getTitle()];
        }
    }
}