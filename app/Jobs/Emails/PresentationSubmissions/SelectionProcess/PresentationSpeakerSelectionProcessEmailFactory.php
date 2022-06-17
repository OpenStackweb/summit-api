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

use App\Services\Utils\Facades\EmailExcerpt;
use models\summit\PresentationSpeaker;
use models\summit\PresentationSpeakerSummitAssistanceConfirmationRequest;
use models\summit\SpeakerAnnouncementSummitEmail;
use models\summit\Summit;
use models\summit\SummitRegistrationPromoCode;
/**
 * Class PresentationSpeakerSelectionProcessEmailFactory
 * @package App\Jobs\Emails\PresentationSubmissions\SelectionProcess
 */
final class PresentationSpeakerSelectionProcessEmailFactory
{
    /**
     * @var array
     */
    public static $valid_types = [
        SpeakerAnnouncementSummitEmail::TypeAccepted,
        SpeakerAnnouncementSummitEmail::TypeAcceptedAlternate,
        SpeakerAnnouncementSummitEmail::TypeAcceptedRejected,
        SpeakerAnnouncementSummitEmail::TypeAlternate,
        SpeakerAnnouncementSummitEmail::TypeAlternateRejected,
    ];

    /**
     * @param string $type
     * @return bool
     */
    public static function isValidType($type){
        return in_array($type, self::$valid_types);
    }

    /**
     * @param Summit $summit
     * @param PresentationSpeaker $speaker
     * @param string $type
     * @param SummitRegistrationPromoCode|null $promo_code
     * @param PresentationSpeakerSummitAssistanceConfirmationRequest|null $speaker_assistance
     */
    public static function send
    (
        Summit $summit,
        PresentationSpeaker $speaker,
        string $type,
        ?SummitRegistrationPromoCode $promo_code,
        ?PresentationSpeakerSummitAssistanceConfirmationRequest $speaker_assistance
    ){

        switch ($type){
            case SpeakerAnnouncementSummitEmail::TypeAccepted:
                PresentationSpeakerSelectionProcessAcceptedOnlyEmail::dispatch
                (
                    $summit,
                    $promo_code,
                    $speaker,
                    $speaker_assistance->getToken()
                );
            break;
            case SpeakerAnnouncementSummitEmail::TypeRejected:
                PresentationSpeakerSelectionProcessRejectedEmail::dispatch
                (
                    $summit,
                    $speaker
                );
            break;
            case SpeakerAnnouncementSummitEmail::TypeAcceptedAlternate:
                PresentationSpeakerSelectionProcessAcceptedAlternateEmail::dispatch
                (
                    $summit,
                    $promo_code,
                    $speaker,
                    $speaker_assistance->getToken()
                );
                break;
            case SpeakerAnnouncementSummitEmail::TypeAcceptedRejected:
                PresentationSpeakerSelectionProcessAcceptedRejectedEmail::dispatch
                (
                    $summit,
                    $promo_code,
                    $speaker,
                    $speaker_assistance->getToken()
                );
                break;
            case SpeakerAnnouncementSummitEmail::TypeAlternate:
                PresentationSpeakerSelectionProcessAlternateOnlyEmail::dispatch
                (
                    $summit,
                    $promo_code,
                    $speaker,
                    $speaker_assistance->getToken()
                );
                break;
            case SpeakerAnnouncementSummitEmail::TypeAlternateRejected:
                PresentationSpeakerSelectionProcessAlternateRejectedEmail::dispatch
                (
                    $summit,
                    $promo_code,
                    $speaker,
                    $speaker_assistance->getToken()
                );
                break;
        }

        EmailExcerpt::add(
            [
                'speaker_email' => $speaker->getEmail(),
                'email_type'    => $type
            ]
        );
    }
}