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

use App\Jobs\Emails\PresentationSubmissions\PresentationSubmissionReopenedEmail;
use App\Jobs\Utils\JobDispatcher;
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

    public function notify(
        Summit $summit,
        int $presentation_id,
        array $speaker_ids,
        bool $include_submitter,
        Member $actor
    ): array {
        // Read inside the transaction, dispatch outside it. Same reasoning as closeNow()'s
        // deferred audit line: flush/commit happen after the closure and a retryable failure
        // re-runs it, so dispatching inside would queue mail for a read that had not committed
        // and could queue it more than once.
        [$presentation, $recipients, $skipped, $deadline] = $this->tx_service->transaction(
            function () use ($summit, $presentation_id, $speaker_ids, $include_submitter) {

                // summit-scoped unconditionally, matching reopen() and closeNow()
                $presentation = $summit->getEvent($presentation_id);
                if (!$presentation instanceof Presentation)
                    throw new EntityNotFoundException(sprintf("Presentation %s not found.", $presentation_id));

                // The single grant gate. isSubmissionReopened() is false both when no grant exists
                // AND when the plan's submission_end_date has since been extended past now -- in
                // that second case the speaker is editing under normal open-window rules and the
                // grant is not what is letting them in, so there is no reopen deadline to announce.
                if (!$presentation->isSubmissionReopened())
                    throw new ValidationException("Submission is not currently reopened for this presentation.");

                $speaker_ids = array_values(array_unique(array_map('intval', $speaker_ids)));

                if (empty($speaker_ids) && !$include_submitter)
                    throw new ValidationException("Select at least one recipient.");

                // ---------------------------------------------------------------------------
                // TRUST BOUNDARY. The caller now names recipients, so the set of people this
                // endpoint may mail must be derived from the PRESENTATION, never from the request.
                // Without this check the endpoint mails any speaker id in the system on behalf of
                // any summit admin: an authenticated mail relay. Build the allowed map first, then
                // intersect -- do not look speakers up by id from the repository.
                // ---------------------------------------------------------------------------
                $allowed = [];                                                  // speaker id => PresentationSpeaker
                $roles   = [];                                                  // speaker id => 'speaker' | 'moderator' | 'speaker, moderator'
                foreach ($presentation->getSpeakers() as $speaker) {
                    $allowed[$speaker->getId()] = $speaker;
                    $roles[$speaker->getId()]   = 'speaker';
                }

                // Separate association, NOT necessarily a member of getSpeakers(). Dropping this
                // line makes every moderator-only recipient fail the intersect below as "not on
                // this presentation".
                $moderator = $presentation->getModerator();
                if (!is_null($moderator)) {
                    $allowed[$moderator->getId()] = $moderator;
                    $roles[$moderator->getId()]   = isset($roles[$moderator->getId()])
                        ? 'speaker, moderator' : 'moderator';
                }

                $unknown = array_diff($speaker_ids, array_keys($allowed));
                if (!empty($unknown))
                    throw new ValidationException(sprintf(
                        "Speaker(s) %s are not on this presentation.", implode(', ', $unknown)
                    ));

                // keyed by normalized email -> display name
                $recipients = []; $skipped = 0;

                $add = function (?string $email, ?string $name, string $role) use (&$recipients, &$skipped) {
                    $key = strtolower(trim($email ?? ''));
                    if ($key === '') {
                        $skipped++;
                        // Logged, never fatal: one incomplete record must not block the others.
                        Log::warning(sprintf("PresentationSubmissionReopenService::notify: %s has no usable email; skipped.", $role));
                        return;
                    }
                    if (!array_key_exists($key, $recipients)) $recipients[$key] = $name;
                };

                // Submitter first: on a self-submitted talk they are also a speaker, and
                // first-write wins in $add, so the submitter's own name is the one used and they
                // get ONE email even when both boxes are ticked.
                if ($include_submitter) {
                    // getCreatedBy(), NOT getCreator(): the latter is @deprecated.
                    $submitter = $presentation->getCreatedBy();
                    if (is_null($submitter))
                        throw new ValidationException("This presentation has no submitter to notify.");
                    $add($submitter->getEmail(), $submitter->getFullName(), sprintf('submitter (member %s)', $submitter->getId()));
                }

                foreach ($speaker_ids as $id)
                    $add($allowed[$id]->getEmail(), $allowed[$id]->getFullName(), sprintf('%s %s', $roles[$id], $id));

                if (empty($recipients))
                    throw new ValidationException("None of the selected recipients has an email address.");

                return [$presentation, $recipients, $skipped, $presentation->getSubmissionReopenedUntil()];
            }
        );

        // JobDispatcher, not ::dispatch(): a queue-backend failure part-way through this loop would
        // otherwise abort the request with some recipients already queued, and the operator's retry
        // would mail them twice. withDbFallback() fails over to the database queue (and runs sync on
        // a double failure) so the loop completes and the returned count stays true. The job is
        // constructed here, before any push, so a missing SummitEmailEventFlowType row or
        // cfp.support_email still fails the request loudly rather than reporting a false "queued".
        // primaryConnection follows queue.default so this mail is routed like every other
        // AbstractEmailJob instead of JobDispatcher's hardcoded redis primary.
        foreach ($recipients as $email => $name)
            JobDispatcher::withDbFallback(
                job: new PresentationSubmissionReopenedEmail($presentation, $email, $name ?? ''),
                logContext: ['summit_id' => $summit->getId(), 'presentation_id' => $presentation_id],
                primaryConnection: Config::get('queue.default')
            );

        // Report queued and skipped, NOT "queued of selected". Selection is counted in rows by the
        // client and in ids by the server, and one merged row (a submitter who is also a speaker)
        // sets two channels, so "1 of 2 selected" would be a true statement about a single ticked
        // box. Queued plus skipped is unambiguous at both ends.
        Log::info(sprintf(
            "PresentationSubmissionReopenService::notify summit %s presentation %s queued %s recipient(s), %s skipped for missing email, by member %s (window ends %s).",
            $summit->getId(), $presentation_id, count($recipients), $skipped, $actor->getId(),
            $deadline->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z')
        ));

        return ['queued' => count($recipients), 'skipped' => $skipped];
    }
}
