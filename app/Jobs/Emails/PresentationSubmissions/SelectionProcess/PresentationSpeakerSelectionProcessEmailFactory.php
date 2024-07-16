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
use App\Services\utils\IEmailExcerptService;
use Illuminate\Support\Facades\Log;
use models\summit\PresentationSpeaker;
use models\summit\PresentationSpeakerSummitAssistanceConfirmationRequest;
use models\summit\SpeakerAnnouncementSummitEmail;
use models\summit\Summit;
use models\summit\SummitRegistrationPromoCode;
use utils\Filter;

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
        SpeakerAnnouncementSummitEmail::TypeAlternate,
        SpeakerAnnouncementSummitEmail::TypeAlternate,
        SpeakerAnnouncementSummitEmail::TypeAcceptedAlternate,
        SpeakerAnnouncementSummitEmail::TypeAcceptedRejected,
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
     * @param string|null $test_email_recipient
     * @param SpeakersAnnouncementEmailConfigDTO $speaker_announcement_email_config
     * @param Filter|null $filter
     * @param SummitRegistrationPromoCode|null $promo_code
     * @param PresentationSpeakerSummitAssistanceConfirmationRequest|null $speaker_assistance
     */
    public static function send
    (
        Summit $summit,
        PresentationSpeaker $speaker,
        string $type,
        ?string $test_email_recipient,
        SpeakersAnnouncementEmailConfigDTO $speaker_announcement_email_config,
        ?Filter $filter = null,
        ?SummitRegistrationPromoCode $promo_code = null,
        ?PresentationSpeakerSummitAssistanceConfirmationRequest $speaker_assistance = null,
        callable $onSuccess = null
    ){

        Log::debug
        (
            sprintf
            (
                "PresentationSpeakerSelectionProcessEmailFactory::send speaker %s type %s filter %s",
                $speaker->getEmail(),
                $type,
                is_null($filter) ? "NOT SET" : $filter->__toString()
            )
        );

        switch ($type){
            case SpeakerAnnouncementSummitEmail::TypeAccepted:
                PresentationSpeakerSelectionProcessAcceptedOnlyEmail::dispatch
                (
                    $summit,
                    $promo_code,
                    $speaker,
                    $test_email_recipient,
                    $speaker_announcement_email_config,
                    is_null($speaker_assistance) ? null: $speaker_assistance->getToken(),
                    $filter
                );
            break;
            case SpeakerAnnouncementSummitEmail::TypeAlternate:
                PresentationSpeakerSelectionProcessAlternateOnlyEmail::dispatch
                (
                    $summit,
                    $promo_code,
                    $speaker,
                    $test_email_recipient,
                    $speaker_announcement_email_config,
                    is_null($speaker_assistance) ? null: $speaker_assistance->getToken(),
                    $filter,
                );
                break;
            case SpeakerAnnouncementSummitEmail::TypeRejected:
                PresentationSpeakerSelectionProcessRejectedOnlyEmail::dispatch
                (
                    $summit,
                    $speaker,
                    $test_email_recipient,
                    $speaker_announcement_email_config,
                    $filter
                );
            break;
            case SpeakerAnnouncementSummitEmail::TypeAcceptedAlternate:
                PresentationSpeakerSelectionProcessAcceptedAlternateEmail::dispatch
                (
                    $summit,
                    $promo_code,
                    $speaker,
                    $test_email_recipient,
                    $speaker_announcement_email_config,
                    is_null($speaker_assistance) ? null: $speaker_assistance->getToken(),
                    $filter
                );
                break;
            case SpeakerAnnouncementSummitEmail::TypeAcceptedRejected:
                PresentationSpeakerSelectionProcessAcceptedRejectedEmail::dispatch
                (
                    $summit,
                    $promo_code,
                    $speaker,
                    $test_email_recipient,
                    $speaker_announcement_email_config,
                    is_null($speaker_assistance) ? null: $speaker_assistance->getToken(),
                    $filter
                );
                break;
            case SpeakerAnnouncementSummitEmail::TypeAlternateRejected:
                PresentationSpeakerSelectionProcessAlternateRejectedEmail::dispatch
                (
                    $summit,
                    $promo_code,
                    $speaker,
                    $test_email_recipient,
                    $speaker_announcement_email_config,
                    is_null($speaker_assistance) ? null: $speaker_assistance->getToken(),
                    $filter
                );
                break;
        }

        if (!is_null($onSuccess)) {
            $onSuccess($speaker->getEmail(), IEmailExcerptService::EmailLineType, $type);
        }
    }
}