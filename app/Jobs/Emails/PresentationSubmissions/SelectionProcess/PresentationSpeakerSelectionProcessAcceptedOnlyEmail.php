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

use App\Services\Utils\Facades\SpeakersAnnouncementEmailConfig;
use Illuminate\Support\Facades\Log;
use models\summit\PresentationSpeaker;
use models\summit\Summit;
use models\summit\SummitRegistrationPromoCode;
use ModelSerializers\IPresentationSerializerTypes;
use ModelSerializers\SerializerRegistry;
use utils\Filter;

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
     * @param Summit $summit
     * @param SummitRegistrationPromoCode|null $promo_code
     * @param PresentationSpeaker $speaker
     * @param string|null $confirmation_token
     * @param Filter|null $filter
     */
    public function __construct
    (
        Summit $summit,
        ?SummitRegistrationPromoCode $promo_code,
        PresentationSpeaker $speaker,
        ?string $confirmation_token = null,
        ?Filter $filter = null
    )
    {

        $this->filter = $filter->getOriginalExp();
        $payload = [];
        $cc_email = [];
        $shouldSendCopy2Submitter = SpeakersAnnouncementEmailConfig::shouldSendCopy2Submitter();

        $payload['accepted_presentations'] = [];
        foreach($speaker->getAcceptedPresentations($summit, PresentationSpeaker::RoleSpeaker, true, [], $filter) as $p){
            if($shouldSendCopy2Submitter && $p->hasCreatedBy() && !in_array($p->getCreatedBy()->getEmail(), $cc_email) && $speaker->getEmail() != $p->getCreatedBy()->getEmail())
                $cc_email[] = $p->getCreatedBy()->getEmail();

            $payload['accepted_presentations'][] =
                SerializerRegistry::getInstance()->getSerializer($p, IPresentationSerializerTypes::SpeakerEmails)->serialize();
        }

        $payload['accepted_moderated_presentations'] = [];
        foreach($speaker->getAcceptedPresentations($summit, PresentationSpeaker::RoleModerator, true, [], $filter) as $p){
            if($shouldSendCopy2Submitter && $p->hasCreatedBy() && !in_array($p->getCreatedBy()->getEmail(), $cc_email) && $speaker->getEmail() != $p->getCreatedBy()->getEmail())
                $cc_email[] = $p->getCreatedBy()->getEmail();
            $payload['accepted_moderated_presentations'][] =
                SerializerRegistry::getInstance()->getSerializer($p, IPresentationSerializerTypes::SpeakerEmails)->serialize();
        }

        if(count($cc_email) > 0){
            $payload['cc_email'] = implode(',', $cc_email);
        }

        parent::__construct($payload, $summit, $speaker, $promo_code);

        if(!empty($confirmation_token)) {
            $this->payload['speaker_confirmation_link'] =
                sprintf("%s?t=%s", $this->payload['speaker_confirmation_link'], base64_encode($confirmation_token));
        }

        Log::debug(sprintf("PresentationSpeakerSelectionProcessAcceptedOnlyEmail::__construct payload %s", json_encode($payload)));

    }
}