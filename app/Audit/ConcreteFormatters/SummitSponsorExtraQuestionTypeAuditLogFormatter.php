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
use App\Models\Foundation\Summit\ExtraQuestions\SummitSponsorExtraQuestionType;
use Illuminate\Support\Facades\Log;

class SummitSponsorExtraQuestionTypeAuditLogFormatter extends AbstractAuditLogFormatter
{
    public function format($subject, array $change_set): ?string
    {
        if (!$subject instanceof SummitSponsorExtraQuestionType) {
            return null;
        }

        try {
            $id = $subject->getId() ?? 'unknown';
            $label = $subject->getLabel() ?? 'Unknown Question';
            $question_type = $subject->getType() ?? 'Unknown Type';
            $sponsor = $subject->getSponsor();
            $sponsor_info = $sponsor ? (($sponsor->getCompany() ? $sponsor->getCompany()->getName() : 'Unknown Company') . " (ID: {$sponsor->getId()})") : 'Unknown Sponsor';

            switch ($this->event_type) {
                case IAuditStrategy::EVENT_ENTITY_CREATION:
                    return sprintf(
                        "Sponsor Extra Question '%s' (ID: %s) of type '%s' for Sponsor %s created by user %s",
                        $label,
                        $id,
                        $question_type,
                        $sponsor_info,
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_ENTITY_UPDATE:
                    $details = $this->buildChangeDetails($change_set);
                    return sprintf(
                        "Sponsor Extra Question '%s' (ID: %s) for Sponsor %s updated: %s by user %s",
                        $label,
                        $id,
                        $sponsor_info,
                        $details,
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_ENTITY_DELETION:
                    return sprintf(
                        "Sponsor Extra Question '%s' (ID: %s) for Sponsor %s deleted by user %s",
                        $label,
                        $id,
                        $sponsor_info,
                        $this->getUserInfo()
                    );
            }
        } catch (\Exception $ex) {
            Log::warning("SummitSponsorExtraQuestionTypeAuditLogFormatter error: " . $ex->getMessage());
        }

        return null;
    }
}
