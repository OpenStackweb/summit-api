<?php namespace App\Services\Model\Strategies\EmailActions;
/**
 * Copyright 2023 OpenStack Foundation
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

use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSubmitterSelectionProcessAcceptedAlternateEmail;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSubmitterSelectionProcessAcceptedOnlyEmail;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSubmitterSelectionProcessAcceptedRejectedEmail;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSubmitterSelectionProcessAlternateOnlyEmail;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSubmitterSelectionProcessAlternateRejectedEmail;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSubmitterSelectionProcessEmailFactory;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSubmitterSelectionProcessRejectedOnlyEmail;
use App\Services\Utils\Facades\EmailExcerpt;
use Illuminate\Support\Facades\Log;
use models\main\Member;
use models\summit\SubmitterAnnouncementSummitEmail;
use models\summit\Summit;
use utils\Filter;

/**
 * Class SubmitterActionsEmailStrategy
 * @package App\Services\Model\Strategies\EmailActions
 */
final class SubmitterActionsEmailStrategy
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
     * SubmitterActionsEmailStrategy constructor.
     * @param Summit $summit
     * @param string $flow_event
     */
    public function __construct(Summit $summit, string $flow_event)
    {
        $this->summit = $summit;
        $this->flow_event = $flow_event;
    }

    /**
     * @param Member $submitter
     * @param string|null $test_email_recipient
     * @param Filter|null $filter
     */
    public function process(Member  $submitter,
                            ?string $test_email_recipient,
                            ?Filter $filter = null): void
    {
        try {
            $type = null;

            Log::debug("SubmitterActionsEmailStrategy::send processing submitter {$submitter->getEmail()} - original flow event {$this->flow_event}");

            $has_accepted_presentations = $submitter->hasAcceptedPresentations($this->summit, $filter);

            $has_alternate_presentations = $submitter->hasAlternatePresentations($this->summit, $filter);

            $has_rejected_presentations = $submitter->hasRejectedPresentations($this->summit, $filter);

            Log::debug
            (
                sprintf
                (
                    "SubmitterActionsEmailStrategy::send submitter %s accepted %b alternates %b rejected %b.",
                    $submitter->getEmail(),
                    $has_accepted_presentations,
                    $has_alternate_presentations,
                    $has_rejected_presentations
                )
            );

            EmailExcerpt::addInfoMessage(
                sprintf
                (
                    "trying to send email %s to submitter %s accepted %b alternate %b rejected %b",
                    $this->flow_event,
                    $submitter->getEmail(),
                    $has_accepted_presentations,
                    $has_alternate_presentations,
                    $has_rejected_presentations
                )
            );

            switch ($this->flow_event) {
                case PresentationSubmitterSelectionProcessAcceptedAlternateEmail::EVENT_SLUG:
                    $type = SubmitterAnnouncementSummitEmail::TypeAcceptedAlternate;
                    break;
                case PresentationSubmitterSelectionProcessAcceptedOnlyEmail::EVENT_SLUG:
                    $type = SubmitterAnnouncementSummitEmail::TypeAccepted;
                    break;
                case PresentationSubmitterSelectionProcessAcceptedRejectedEmail::EVENT_SLUG:
                    $type = SubmitterAnnouncementSummitEmail::TypeAcceptedRejected;
                    break;
                case PresentationSubmitterSelectionProcessAlternateOnlyEmail::EVENT_SLUG:
                    $type = SubmitterAnnouncementSummitEmail::TypeAlternate;
                    break;
                case PresentationSubmitterSelectionProcessAlternateRejectedEmail::EVENT_SLUG:
                    $type = SubmitterAnnouncementSummitEmail::TypeAlternateRejected;
                    break;
                case PresentationSubmitterSelectionProcessRejectedOnlyEmail::EVENT_SLUG:
                    $type = SubmitterAnnouncementSummitEmail::TypeRejected;
                    break;
                default:
                    break;
            }

            if (!is_null($type)) {
                EmailExcerpt::addInfoMessage(
                    sprintf
                    (
                        "submitter %s accepted %b alternate %b rejected %b already has an email of type %s.",
                        $submitter->getEmail(),
                        $has_accepted_presentations,
                        $has_alternate_presentations,
                        $has_rejected_presentations,
                        $this->flow_event
                    )
                );

                PresentationSubmitterSelectionProcessEmailFactory::send
                (
                    $this->summit,
                    $submitter,
                    $type,
                    $test_email_recipient,
                    $filter
                );
                EmailExcerpt::addEmailSent();
                return;
            }

            EmailExcerpt::addInfoMessage(
                sprintf
                (
                    "excluded submitter %s accepted %b alternate %b rejected %b for original email %s",
                    $submitter->getEmail(),
                    $has_accepted_presentations,
                    $has_alternate_presentations,
                    $has_rejected_presentations,
                    $this->flow_event
                )
            );
        } catch (\Exception $ex) {
            Log::error($ex);
            EmailExcerpt::addErrorMessage($ex->getMessage());
        }
    }
}