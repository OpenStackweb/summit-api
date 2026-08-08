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
     * $actor is unused by the implementation and is kept because the signed-off SDS specifies it:
     * clearing nulls the ByID column rather than restamping it, and the caller is already captured
     * by generic request auditing. Do not "tidy" it away without amending the SDS.
     *
     * @throws EntityNotFoundException if the presentation is not in $summit
     */
    public function closeNow(Summit $summit, int $presentation_id, Member $actor): void;
}
