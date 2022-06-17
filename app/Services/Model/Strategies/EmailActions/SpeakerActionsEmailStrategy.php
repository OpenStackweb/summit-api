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
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSpeakerSelectionProcessRejectedEmail;
use Illuminate\Support\Facades\Log;
use models\summit\PresentationSpeaker;
use models\summit\PresentationSpeakerSummitAssistanceConfirmationRequest;
use models\summit\SpeakerAnnouncementSummitEmail;
use models\summit\SpeakerSummitRegistrationPromoCode;
use models\summit\Summit;

class SpeakerActionsEmailStrategy
{
    private $summit;

    private $flow_event;

    /**
     * SpeakerActionsEmailStrategy constructor.
     * @param Summit $summit
     * @param String $flow_event
     */
    public function __construct(Summit $summit, String $flow_event)
    {
        $this->summit = $summit;
        $this->flow_event = $flow_event;
    }

    public function process(PresentationSpeaker $speaker,
                            PresentationSpeakerSummitAssistanceConfirmationRequest $assistance,
                            SpeakerSummitRegistrationPromoCode $promo_code)
    {
        try {
            $type = null;

            Log::debug("SpeakerActionsEmailStrategy::send processing speaker {$speaker->getEmail()} - original flow event {$this->flow_event}");

            $has_accepted_presentations =
                $speaker->hasAcceptedPresentations(
                    $this->summit, PresentationSpeaker::RoleModerator, true,
                    $this->summit->getExcludedCategoriesForAcceptedPresentations()
                ) ||
                $speaker->hasAcceptedPresentations(
                    $this->summit, PresentationSpeaker::RoleSpeaker, true,
                    $this->summit->getExcludedCategoriesForAcceptedPresentations()
                );

            $has_alternate_presentations =
                $speaker->hasAlternatePresentations(
                    $this->summit, PresentationSpeaker::RoleModerator, true,
                    $this->summit->getExcludedCategoriesForAlternatePresentations()
                ) ||
                $speaker->hasAlternatePresentations(
                    $this->summit, PresentationSpeaker::RoleSpeaker, true,
                    $this->summit->getExcludedCategoriesForAlternatePresentations()
                );

            $has_rejected_presentations =
                $speaker->hasRejectedPresentations(
                    $this->summit, PresentationSpeaker::RoleModerator, true,
                    $this->summit->getExcludedCategoriesForRejectedPresentations()
                ) ||
                $speaker->hasRejectedPresentations(
                    $this->summit, PresentationSpeaker::RoleSpeaker, true,
                    $this->summit->getExcludedCategoriesForRejectedPresentations()
                );

            switch ($this->flow_event) {
                case PresentationSpeakerSelectionProcessAcceptedAlternateEmail::EVENT_SLUG:
                    if ($has_accepted_presentations && $has_alternate_presentations && !$has_rejected_presentations) {
                        $type = SpeakerAnnouncementSummitEmail::TypeAcceptedAlternate;
                    }
                    break;
                case PresentationSpeakerSelectionProcessAcceptedOnlyEmail::EVENT_SLUG:
                    if ($has_accepted_presentations && !$has_alternate_presentations && !$has_rejected_presentations) {
                        $type = SpeakerAnnouncementSummitEmail::TypeAccepted;
                    }
                    break;
                case PresentationSpeakerSelectionProcessAcceptedRejectedEmail::EVENT_SLUG:
                    if ($has_accepted_presentations && !$has_alternate_presentations && $has_rejected_presentations) {
                        $type = SpeakerAnnouncementSummitEmail::TypeAcceptedRejected;
                    }
                    break;
                case PresentationSpeakerSelectionProcessAlternateOnlyEmail::EVENT_SLUG:
                    if (!$has_accepted_presentations && $has_alternate_presentations && !$has_rejected_presentations) {
                        $type = SpeakerAnnouncementSummitEmail::TypeAlternate;
                    }
                    break;
                case PresentationSpeakerSelectionProcessAlternateRejectedEmail::EVENT_SLUG:
                    if (!$has_accepted_presentations && $has_alternate_presentations && $has_rejected_presentations) {
                        $type = SpeakerAnnouncementSummitEmail::TypeAlternateRejected;
                    }
                    break;
                case PresentationSpeakerSelectionProcessRejectedEmail::EVENT_SLUG:
                    if (!$has_accepted_presentations && !$has_alternate_presentations && $has_rejected_presentations) {
                        $type = SpeakerAnnouncementSummitEmail::TypeRejected;
                    }
                    break;
                default:
                    return null;
            }

            if (!is_null($type)) {
                $role = $speaker->isModeratorFor($this->summit) ?
                    PresentationSpeaker::RoleModerator : PresentationSpeaker::RoleSpeaker;

                PresentationSpeakerSelectionProcessEmailFactory::send
                (
                    $this->summit,
                    $speaker,
                    $role,
                    $type,
                    $promo_code,
                    $assistance
                );
            }
        } catch (\Exception $ex) {
            Log::error($ex);
        }
    }
}