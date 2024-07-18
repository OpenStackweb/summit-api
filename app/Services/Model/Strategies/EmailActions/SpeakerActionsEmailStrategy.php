<?php namespace App\Services\Model\Strategies\EmailActions;
/**
 * Copyright 2022 OpenStack Foundation
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

use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSpeakerSelectionProcessAcceptedAlternateEmail;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSpeakerSelectionProcessAcceptedOnlyEmail;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSpeakerSelectionProcessAcceptedRejectedEmail;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSpeakerSelectionProcessAlternateOnlyEmail;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSpeakerSelectionProcessAlternateRejectedEmail;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSpeakerSelectionProcessEmailFactory;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSpeakerSelectionProcessRejectedOnlyEmail;
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
 * Class SpeakerActionsEmailStrategy
 * @package App\Services\Model\Strategies\EmailActions
 */
final class SpeakerActionsEmailStrategy
{
    /**
     * @var Summit
     */
    private $summit;

    /**
     * @var string
     */
    private $flow_event;

    /**
     * SpeakerActionsEmailStrategy constructor.
     * @param Summit $summit
     * @param string $flow_event
     */
    public function __construct(Summit $summit, string $flow_event)
    {
        $this->summit = $summit;
        $this->flow_event = $flow_event;
    }

    /**
     * @param PresentationSpeaker $speaker
     * @param string|null $test_email_recipient
     * @param SpeakersAnnouncementEmailConfigDTO $speaker_announcement_email_config
     * @param Filter|null $filter
     * @param SummitRegistrationPromoCode|null $promo_code
     * @param PresentationSpeakerSummitAssistanceConfirmationRequest|null $assistance
     * @param callable|null $onSuccess
     * @param callable|null $onInfo
     * @param callable|null $onError
     * @return void
     */
    public function process(PresentationSpeaker                                     $speaker,
                            ?string                                                 $test_email_recipient,
                            SpeakersAnnouncementEmailConfigDTO                      $speaker_announcement_email_config,
                            ?Filter                                                 $filter = null,
                            ?SummitRegistrationPromoCode                            $promo_code = null,
                            ?PresentationSpeakerSummitAssistanceConfirmationRequest $assistance = null,
                            callable $onSuccess = null,
                            callable $onInfo = null,
                            callable $onError = null
    ): void
    {
        try {
            $type = null;

            Log::debug
            (
                sprintf
                (
                    "SpeakerActionsEmailStrategy::send processing speaker %s - original flow event %s filter %s",
                    $speaker->getEmail(),
                    $this->flow_event,
                    is_null($filter) ? "NOT SET" : $filter->__toString()
                )
            );

            $accepted_count =
                $speaker->getAcceptedPresentationsCount(
                    $this->summit,
                    PresentationSpeaker::RoleModerator, true,
                    $this->summit->getExcludedCategoriesForAcceptedPresentations(),
                    $filter
                ) +
                $speaker->getAcceptedPresentationsCount(
                    $this->summit, PresentationSpeaker::RoleSpeaker, true,
                    $this->summit->getExcludedCategoriesForAcceptedPresentations(), $filter
                );

            $alternate_count =
                $speaker->getAlternatePresentationsCount(
                    $this->summit, PresentationSpeaker::RoleModerator, true,
                    $this->summit->getExcludedCategoriesForAlternatePresentations(),
                    $filter
                ) +
                $speaker->getAlternatePresentationsCount(
                    $this->summit, PresentationSpeaker::RoleSpeaker, true,
                    $this->summit->getExcludedCategoriesForAlternatePresentations(),
                    $filter
                );

            $rejected_count =
                $speaker->getRejectedPresentationsCount(
                    $this->summit, PresentationSpeaker::RoleModerator, true,
                    $this->summit->getExcludedCategoriesForRejectedPresentations(),
                    $filter
                ) +
                $speaker->getRejectedPresentationsCount(
                    $this->summit, PresentationSpeaker::RoleSpeaker, true,
                    $this->summit->getExcludedCategoriesForRejectedPresentations(),
                    $filter
                );

            $has_promo_code = !is_null($promo_code);
            $has_assistance = !is_null($assistance);

            Log::debug
            (
                sprintf
                (
                    "SpeakerActionsEmailStrategy::send speaker %s accepted %s alternates %s rejected %s has_promo_code %b has_summit_assistance %b.",
                    $speaker->getEmail(),
                    $accepted_count,
                    $alternate_count,
                    $rejected_count,
                    $has_promo_code,
                    $has_assistance
                )
            );

            if(!is_null($onInfo))
                $onInfo(
                    sprintf
                    (
                        "Trying to send email %s to speaker %s accepted %s alternate %s rejected %s.",
                        $this->flow_event,
                        $speaker->getEmail(),
                        $accepted_count,
                        $alternate_count,
                        $rejected_count,
                    )
                );

            switch ($this->flow_event) {
                case PresentationSpeakerSelectionProcessAcceptedAlternateEmail::EVENT_SLUG:
                    $type = SpeakerAnnouncementSummitEmail::TypeAcceptedAlternate;
                    break;
                case PresentationSpeakerSelectionProcessAcceptedOnlyEmail::EVENT_SLUG:
                    $type = SpeakerAnnouncementSummitEmail::TypeAccepted;
                    break;
                case PresentationSpeakerSelectionProcessAcceptedRejectedEmail::EVENT_SLUG:
                    $type = SpeakerAnnouncementSummitEmail::TypeAcceptedRejected;
                    break;
                case PresentationSpeakerSelectionProcessAlternateOnlyEmail::EVENT_SLUG:
                    $type = SpeakerAnnouncementSummitEmail::TypeAlternate;
                    break;
                case PresentationSpeakerSelectionProcessAlternateRejectedEmail::EVENT_SLUG:
                    $type = SpeakerAnnouncementSummitEmail::TypeAlternateRejected;
                    break;
                case PresentationSpeakerSelectionProcessRejectedOnlyEmail::EVENT_SLUG:
                    $type = SpeakerAnnouncementSummitEmail::TypeRejected;
                    break;
                default:
                    if (!is_null($onSuccess)) {
                        $onSuccess
                        (
                            $speaker->getEmail(),
                            IEmailExcerptService::EmailLineType,
                            SpeakerAnnouncementSummitEmail::TypeNone
                        );
                    }
                    break;
            }

            if (!is_null($type)) {
                if ($speaker->hasAnnouncementEmailTypeSent($this->summit, $type) &&
                    !$speaker_announcement_email_config->shouldResend()) {

                    if(!is_null($onInfo))
                        $onInfo
                        (
                            sprintf
                            (
                                "Speaker %s accepted %s alternate %s rejected %s already has an email of type %s.",
                                $speaker->getEmail(),
                                $accepted_count,
                                $alternate_count,
                                $rejected_count,
                                $this->flow_event
                            )
                        );

                    Log::debug
                    (
                        sprintf
                        (
                            "SpeakerActionsEmailStrategy::send speaker %s already has an email of type %s.",
                            $speaker->getEmail(),
                            $type
                        )
                    );
                    return;
                }

                PresentationSpeakerSelectionProcessEmailFactory::send
                (
                    $this->summit,
                    $speaker,
                    $type,
                    $test_email_recipient,
                    $speaker_announcement_email_config,
                    $filter,
                    $promo_code,
                    $assistance,
                    $onSuccess
                );

                // mark the promo code as sent
                if (!is_null($promo_code))
                    $promo_code->markSent( $speaker->getEmail());

                // generate email proof
                $proof = new SpeakerAnnouncementSummitEmail();
                $proof->setType($type);
                $speaker->addAnnouncementSummitEmail($proof);
                $this->summit->addAnnouncementSummitEmail($proof);
                $proof->markAsSent();
                return;
            }

            if(!is_null($onInfo))
                $onInfo
                (
                    sprintf
                    (
                        "Excluded speaker %s accepted %s alternate %s rejected %s for original email %s.",
                        $speaker->getEmail(),
                        $accepted_count,
                        $alternate_count,
                        $rejected_count,
                        $this->flow_event
                    )
                );
        } catch (\Exception $ex) {
            Log::error($ex);
            if(!is_null($onError))
                $onError($ex->getMessage());
        }
    }
}