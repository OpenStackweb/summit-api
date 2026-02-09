<?php

namespace App\Audit\ConcreteFormatters;

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

use App\Audit\AbstractAuditLogFormatter;
use App\Audit\Interfaces\IAuditStrategy;
use models\summit\SponsorBadgeScanExtraQuestionAnswer;
use Illuminate\Support\Facades\Log;

class SponsorBadgeScanExtraQuestionAnswerAuditLogFormatter extends AbstractAuditLogFormatter
{
    public function format($subject, array $change_set): ?string
    {
        if (!$subject instanceof SponsorBadgeScanExtraQuestionAnswer) {
            return null;
        }

        try {
            $id = $subject->getId() ?? 'unknown';
            $value = $subject->getValue() ?? 'No Value';
            $question = $subject->getQuestion();
            $question_label = $question ? ($question->getLabel() ?? 'Unknown Question') : 'Unknown Question';
            $badge_scan = $subject->getBadgeScan();
            $sponsor_id = $badge_scan && $badge_scan->getSponsor() ? ($badge_scan->getSponsor()->getId() ?? 'unknown') : 'unknown';

            switch ($this->event_type) {
                case IAuditStrategy::EVENT_ENTITY_CREATION:
                    return sprintf(
                        "Sponsor Badge Scan Extra Question Answer (ID: %s) for question '%s' with value '%s' created for Sponsor %s by user %s",
                        $id,
                        $question_label,
                        $value,
                        $sponsor_id,
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_ENTITY_UPDATE:
                    $details = $this->buildChangeDetails($change_set);
                    return sprintf(
                        "Sponsor Badge Scan Extra Question Answer (ID: %s) for question '%s' updated: %s by user %s",
                        $id,
                        $question_label,
                        $details,
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_ENTITY_DELETION:
                    return sprintf(
                        "Sponsor Badge Scan Extra Question Answer (ID: %s) for question '%s' deleted by user %s",
                        $id,
                        $question_label,
                        $this->getUserInfo()
                    );
            }
        } catch (\Exception $ex) {
            Log::warning("SponsorBadgeScanExtraQuestionAnswerAuditLogFormatter error: " . $ex->getMessage());
        }

        return null;
    }
}
