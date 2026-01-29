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
use models\main\Affiliation;
use Illuminate\Support\Facades\Log;

class AffiliationAuditLogFormatter extends AbstractAuditLogFormatter
{
    public function format($subject, array $change_set): ?string
    {
        if (!$subject instanceof Affiliation) {
            return null;
        }

        try {
            $id = $subject->getId() ?? 'unknown';
            $owner = $subject->getOwner();
            $owner_name = $owner ? trim(($owner->getFirstName() ?? '') . ' ' . ($owner->getLastName() ?? '')) : 'Unknown Owner';
            $job_title = $subject->getJobTitle() ?? 'Unknown Job Title';

            switch ($this->event_type) {
                case IAuditStrategy::EVENT_ENTITY_CREATION:
                    return sprintf("Affiliation (%s) for '%s' (%s) created by user %s", $id, $owner_name, $job_title, $this->getUserInfo());

                case IAuditStrategy::EVENT_ENTITY_UPDATE:
                    $details = $this->buildChangeDetails($change_set);
                    return sprintf("Affiliation (%s) for '%s' (%s) updated: %s by user %s", $id, $owner_name, $job_title, $details, $this->getUserInfo());

                case IAuditStrategy::EVENT_ENTITY_DELETION:
                    return sprintf("Affiliation (%s) for '%s' (%s) deleted by user %s", $id, $owner_name, $job_title, $this->getUserInfo());
            }
        } catch (\Exception $ex) {
            Log::warning("AffiliationAuditLogFormatter error: " . $ex->getMessage());
        }

        return null;
    }
}
