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
     * @param callable|null $onSuccess
     * @param callable|null $onInfo
     * @param callable|null $onError
     * @return void
     */
    public function process(Member  $submitter,
                            ?string $test_email_recipient,
                            ?Filter $filter = null,
                            callable $onSuccess = null,
                            callable $onInfo = null,
                            callable $onError = null
    ): void
    {
        try {
            $type = null;

            Log::debug
            (
                sprintf(
                    "SubmitterActionsEmailStrategy::send processing submitter %s original flow event %s filter %s",
                    $submitter->getEmail(),
                    $this->flow_event,
                    is_null($filter) ? "NOT SET" : $filter->__toString()
                )
            );

            $accepted_presentations_count = $submitter->getAcceptedPresentationsCount($this->summit, $filter);

            $alternate_presentations_count = $submitter->getAlternatePresentationsCount($this->summit, $filter);

            $rejected_presentations_count = $submitter->getRejectedPresentationsCount($this->summit, $filter);

            Log::debug
            (
                sprintf
                (
                    "SubmitterActionsEmailStrategy::send submitter %s accepted %s alternates %s rejected %s.",
                    $submitter->getEmail(),
                    $accepted_presentations_count,
                    $alternate_presentations_count,
                    $rejected_presentations_count
                )
            );

            if(!is_null($onInfo))
                $onInfo(
                    sprintf
                    (
                        "Trying to send email %s to submitter %s accepted %s alternate %s rejected %s.",
                        $this->flow_event,
                        $submitter->getEmail(),
                        $accepted_presentations_count,
                        $alternate_presentations_count,
                        $rejected_presentations_count
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
                if(!is_null($onInfo))
                    $onInfo(
                        sprintf
                        (
                            "Submitter %s accepted %s alternate %s rejected %s already has an email of type %s.",
                            $submitter->getEmail(),
                            $accepted_presentations_count,
                            $alternate_presentations_count,
                            $rejected_presentations_count,
                            $this->flow_event
                        )
                    );

                PresentationSubmitterSelectionProcessEmailFactory::send
                (
                    $this->summit,
                    $submitter,
                    $type,
                    $test_email_recipient,
                    $filter,
                    $onSuccess
                );
                return;
            }

            if(!is_null($onInfo))
                $onInfo(
                    sprintf
                    (
                        "Excluded submitter %s accepted %b alternate %s rejected %s for original email %s.",
                        $submitter->getEmail(),
                        $accepted_presentations_count,
                        $alternate_presentations_count,
                        $rejected_presentations_count,
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