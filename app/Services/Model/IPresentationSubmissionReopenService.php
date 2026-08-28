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

use models\summit\Summit;
use models\main\Member;
use models\summit\Presentation;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;

/**
 * Interface IPresentationSubmissionReopenService
 * @package services\model
 */
interface IPresentationSubmissionReopenService
{
    /**
     * $hours null means "unspecified" -- the service resolves cfp.default_reopen_hours. The whole
     * hours rule (default + ceiling) lives in the service so no caller has to re-derive half of it.
     *
     * Declared ?int with no default on purpose: a default here would sit before the required $actor
     * and PHP 8 deprecates "optional parameter declared before required parameter".
     *
     * @throws EntityNotFoundException if the presentation is not in $summit
     * @throws ValidationException     if $hours is out of range or the plan cannot host a reopen
     */
    public function reopen(Summit $summit, int $presentation_id, ?int $hours, Member $actor): Presentation;

    /**
     * Clears any grant. Deliberately tolerant of plan state so a stale grant is always clearable.
     *
     * $actor is logged as the revoking member in the audit line this method emits. Removing it
     * silently drops that attribution from the revocation record.
     *
     * @throws EntityNotFoundException if the presentation is not in $summit
     */
    public function closeNow(Summit $summit, int $presentation_id, Member $actor): void;

    /**
     * Queues one reopen-notification email per SELECTED, distinct recipient for the presentation's
     * CURRENTLY ACTIVE grant.
     *
     * The admin chooses who is notified. $speaker_ids names speakers and/or the moderator (the
     * moderator IS a PresentationSpeaker, so it needs no separate parameter); $include_submitter
     * covers the submitter -- SummitEvent::getCreatedBy(), a Member with no speaker id. Every id is
     * verified to belong to THIS presentation -- see the trust-boundary note in the implementation.
     *
     * Not a delivery count. PresentationSubmissionReopenedEmail is a ShouldQueue job, so this
     * returns before any mail has been handed to mailing-api, let alone sent. Delivery outcome
     * lives in mailing-api's Mail rows.
     *
     * Repeatable by design, with a different selection each time if the admin wants: there is no
     * once-only marker and no persisted selection.
     *
     * @return array{queued: int, skipped: int} queued = distinct recipients with a usable email
     *         that were queued; skipped = selected recipients dropped for a missing email.
     * @throws EntityNotFoundException if the presentation is not in $summit
     * @throws ValidationException     if no grant is in force, if the selection is empty, if any id
     *                                 is not attached to this presentation, or if no selected
     *                                 recipient has an email
     */
    public function notify(
        Summit $summit,
        int $presentation_id,
        array $speaker_ids,
        bool $include_submitter,
        Member $actor
    ): array;
}
