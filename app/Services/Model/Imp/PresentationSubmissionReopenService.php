<?php namespace services\model;
/**
 * Copyright 2026 OpenStack Foundation
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

use App\Services\Model\AbstractService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\main\Member;
use models\summit\Presentation;
use models\summit\Summit;

/**
 * Class PresentationSubmissionReopenService
 * @package services\model
 */
final class PresentationSubmissionReopenService
    extends AbstractService
    implements IPresentationSubmissionReopenService
{
    public function reopen(Summit $summit, int $presentation_id, ?int $hours, Member $actor): Presentation
    {
        return $this->tx_service->transaction(function () use ($summit, $presentation_id, $hours, $actor) {

            // summit-scoped unconditionally for every caller role -- deliberately stricter than
            // the media-endpoint precedent, which scopes only its non-admin branch.
            $presentation = $summit->getEvent($presentation_id);
            if (!$presentation instanceof Presentation)
                throw new EntityNotFoundException(sprintf("Presentation %s not found.", $presentation_id));

            $max = intval(Config::get('cfp.max_reopen_hours', 168));

            // whole hours rule in one place: null means "unspecified", so the default is resolved
            // here rather than in the controller, right next to the ceiling it has to respect.
            // The resolved default is clamped to the ceiling on purpose: a deployment that
            // configures default_reopen_hours above max_reopen_hours would otherwise reject every
            // request that omits hours, which is a config error the caller cannot see or fix.
            // An explicitly supplied $hours is still validated strictly below, never clamped.
            if (is_null($hours))
                $hours = min(intval(Config::get('cfp.default_reopen_hours', 24)), $max);

            // The lower bound is currently unreachable over HTTP -- the endpoint validates
            // 'hours' => 'sometimes|integer|min:1' and refuses first -- but it is deliberate, not
            // dead code: this is the only guard for any non-HTTP caller (job, console, another
            // service), and a persisted non-positive value would make
            // Presentation::getSubmissionReopenedUntil() throw on every read, since
            // new \DateInterval('PT-1H') is invalid. Do not remove it as unused.
            if ($hours < 1 || $hours > $max)
                throw new ValidationException(sprintf("hours must be between 1 and %s.", $max));

            // Same invariants isSubmissionReopened() enforces, checked up front so the admin
            // gets a clear error rather than a grant that silently never activates.
            $selection_plan = $presentation->getSelectionPlan();
            if (is_null($selection_plan))
                throw new ValidationException("Presentation is not assigned to any selection plan.");
            if (!$selection_plan->IsEnabled())
                throw new ValidationException("Selection plan is not enabled.");

            $submission_end_date = $selection_plan->getSubmissionEndDate();
            if (is_null($submission_end_date))
                throw new ValidationException("Selection plan has no submission end date.");

            $now = new \DateTime('now', new \DateTimeZone('UTC'));
            if ($now <= $submission_end_date)
                throw new ValidationException("Submission period has not ended yet; nothing to reopen.");

            $presentation->reopenSubmission($hours, $actor);

            return $presentation;
        });
    }

    public function closeNow(Summit $summit, int $presentation_id, Member $actor): void
    {
        // closeSubmissionNow() nulls all three columns, the granting actor included, so the audit
        // fields have to be read before the write and carried out of the closure. The line itself
        // is emitted only after transaction() returns: flush and commit happen after the closure,
        // and a retryable failure re-runs it, so logging inside would announce a revocation that
        // had not happened yet and could announce it more than once.
        $audit = $this->tx_service->transaction(function () use ($summit, $presentation_id) {

            $presentation = $summit->getEvent($presentation_id);
            if (!$presentation instanceof Presentation)
                throw new EntityNotFoundException(sprintf("Presentation %s not found.", $presentation_id));

            $granted_until = $presentation->getSubmissionReopenedUntil();
            $granted_by_id = $presentation->getSubmissionReopenedById();

            // no plan-state checks on purpose: a stale grant must always be clearable
            $presentation->closeSubmissionNow();

            return [
                // the accessor returns 0, not null, for an absent relation, and closing with no
                // active grant is a legitimate no-op rather than a member whose id is zero
                'granted_by' => $granted_by_id > 0 ? sprintf("member %s", $granted_by_id) : 'no active grant',
                // stamped UTC on write; normalized again here because Doctrine hydrates datetimes
                // in the application default timezone
                'granted_until' => is_null($granted_until)
                    ? 'n/a'
                    : $granted_until->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z'),
            ];
        });

        Log::info(
            sprintf(
                "PresentationSubmissionReopenService::closeNow summit %s presentation %s revoked by member %s (granted by %s, ran until %s).",
                $summit->getId(),
                $presentation_id,
                $actor->getId(),
                $audit['granted_by'],
                $audit['granted_until']
            )
        );
    }
}
