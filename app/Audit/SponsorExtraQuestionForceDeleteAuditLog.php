<?php namespace App\Audit;
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

use App\Audit\Interfaces\IAuditStrategy;
use App\Jobs\EmitAuditLogJob;
use App\Jobs\Utils\JobDispatcher;
use App\Models\Foundation\ExtraQuestions\ExtraQuestionTypeValue;
use App\Models\Foundation\Summit\ExtraQuestions\SummitSponsorExtraQuestionType;
use Illuminate\Support\Facades\Log;
use models\main\Member;
use models\summit\Sponsor;
use models\summit\Summit;

/**
 * Audit entry for an admin force delete of a sponsor extra question or answer option.
 *
 * The onFlush listener already audits the entities removed by the delete, but it
 * cannot carry the reason the admin gave nor how many collected answers were
 * destroyed or modified, so this entry records them explicitly.
 * @package App\Audit
 */
final class SponsorExtraQuestionForceDeleteAuditLog
{
    public const TargetQuestion = 'question';
    public const TargetOption   = 'option';

    public const LogMessage = 'audit.sponsor_extra_question.force_deleted';

    /**
     * @param Member $by
     * @param Summit $summit
     * @param Sponsor $sponsor
     * @param string $target_type TargetQuestion | TargetOption
     * @param int $target_id
     * @param string $target_label
     * @param string $reason
     * @param int $answers_deleted answers removed from the server
     * @param int $answers_modified answers that only lost the deleted option
     * @return array the audit data that was emitted
     */
    public static function record
    (
        Member $by,
        Summit $summit,
        Sponsor $sponsor,
        string $target_type,
        int    $target_id,
        string $target_label,
        string $reason,
        int    $answers_deleted,
        int    $answers_modified = 0
    ): array
    {
        $is_question = $target_type === self::TargetQuestion;
        $description = sprintf
        (
            "Sponsor %s %s '%s' (ID: %s) of Sponsor %s (Summit %s) force deleted by user %s (%s). Reason: %s. Collected answers deleted: %s, modified: %s",
            $is_question ? 'extra question' : 'extra question option',
            $target_type,
            $target_label,
            $target_id,
            $sponsor->getId(),
            $summit->getId(),
            $by->getEmail(),
            $by->getId(),
            $reason,
            $answers_deleted,
            $answers_modified
        );

        $request = request();

        $data = [
            'audit.action'            => IAuditStrategy::ACTION_DELETE,
            'audit.event_type'        => IAuditStrategy::EVENT_ENTITY_DELETION,
            'audit.entity'            => $is_question ? 'SummitSponsorExtraQuestionType' : 'ExtraQuestionTypeValue',
            'audit.entity_id'         => (string)$target_id,
            'audit.entity_class'      => $is_question ? SummitSponsorExtraQuestionType::class : ExtraQuestionTypeValue::class,
            'audit.timestamp'         => now()->toISOString(),
            'audit.summit_id'         => (string)$summit->getId(),
            'audit.sponsor_id'        => (string)$sponsor->getId(),
            'audit.force'             => 'true',
            'audit.reason'            => $reason,
            'audit.target_label'      => $target_label,
            'audit.answers_deleted'   => $answers_deleted,
            'audit.answers_modified'  => $answers_modified,
            'audit.description'       => $description,
            'auth.user.id'            => $by->getId(),
            'auth.user.email'         => $by->getEmail(),
            'auth.user.first_name'    => $by->getFirstName(),
            'auth.user.last_name'     => $by->getLastName(),
            'http.route'              => $request?->path(),
            'http.method'             => $request?->method(),
            'client.ip'               => $request?->ip(),
            'user_agent'              => $request?->userAgent(),
            'elasticsearch.index'     => config('opentelemetry.logs.elasticsearch_index', 'logs-audit'),
        ];

        // always leave a trace in the application log, the OTLP pipeline may be off
        Log::warning(self::LogMessage, $data);

        if (config('opentelemetry.enabled', false)) {
            JobDispatcher::withDbFallback(job: new EmitAuditLogJob(self::LogMessage, $data));
        }

        return $data;
    }
}
