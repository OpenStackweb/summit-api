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
use models\summit\SummitRefundPolicyType;
use Illuminate\Support\Facades\Log;

class SummitRefundPolicyTypeAuditLogFormatter extends AbstractAuditLogFormatter
{
    public function format($subject, array $change_set): ?string
    {
        if (!$subject instanceof SummitRefundPolicyType) {
            return null;
        }

        try {
            $name = $subject->getName() ?? 'Unknown';
            $id = $subject->getId() ?? 'unknown';
            $summit = $subject->getSummit();
            $summit_name = $summit ? ($summit->getName() ?? 'Unknown Summit') : 'Unknown Summit';
            $days_before = $subject->getUntilXDaysBeforeEventStarts();
            $refund_rate = $subject->getRefundRate();

            switch ($this->event_type) {
                case IAuditStrategy::EVENT_ENTITY_CREATION:
                    return sprintf(
                        "Refund Policy '%s' (%d) created for Summit '%s': %s%% refund if cancelled %s days before event by user %s",
                        $name,
                        $id,
                        $summit_name,
                        ($refund_rate !== null ? sprintf("%.0f", $refund_rate) : 'N/A'),
                        ($days_before !== null ? $days_before : 'N/A'),
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_ENTITY_UPDATE:
                    $change_details = $this->buildChangeDetails($change_set);
                    return sprintf(
                        "Refund Policy '%s' (%d) for Summit '%s' updated: %s by user %s",
                        $name,
                        $id,
                        $summit_name,
                        $change_details,
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_ENTITY_DELETION:
                    return sprintf(
                        "Refund Policy '%s' (%d) for Summit '%s' (%s%% refund, %s days before) was deleted by user %s",
                        $name,
                        $id,
                        $summit_name,
                        ($refund_rate !== null ? sprintf("%.0f", $refund_rate) : 'N/A'),
                        ($days_before !== null ? $days_before : 'N/A'),
                        $this->getUserInfo()
                    );
            }
        } catch (\Exception $ex) {
            Log::warning("SummitRefundPolicyTypeAuditLogFormatter error: " . $ex->getMessage());
        }

        return null;
    }
}
