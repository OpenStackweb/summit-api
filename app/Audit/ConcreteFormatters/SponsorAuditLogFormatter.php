<?php

namespace App\Audit\ConcreteFormatters;

/**
 * Copyright 2025 OpenStack Foundation
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
use models\summit\Sponsor;
use Illuminate\Support\Facades\Log;

class SponsorAuditLogFormatter extends AbstractAuditLogFormatter
{
    public function format($subject, array $change_set): ?string
    {
        if (!$subject instanceof Sponsor) {
            return null;
        }

        try {
            $company = $subject->getCompany();
            $company_name = $company ? ($company->getName() ?? 'Unknown Company') : 'Unknown Company';
            $id = $subject->getId() ?? 'unknown';
            $summit = $subject->getSummit();
            $summit_name = $summit ? ($summit->getName() ?? 'Unknown Summit') : 'Unknown Summit';
            $order = $subject->getOrder() ?? 0;
            
            switch ($this->event_type) {
                case IAuditStrategy::EVENT_ENTITY_CREATION:
                    return sprintf(
                        "Sponsor (%d) for Company '%s' created for Summit '%s' with order %d by user %s",
                        $id,
                        $company_name,
                        $summit_name,
                        $order,
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_ENTITY_UPDATE:
                    $change_details = $this->buildChangeDetails($change_set);
                    return sprintf(
                        "Sponsor (%d) for Company '%s' in Summit '%s' updated: %s by user %s",
                        $id,
                        $company_name,
                        $summit_name,
                        $change_details,
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_ENTITY_DELETION:
                    return sprintf(
                        "Sponsor (%d) for Company '%s' in Summit '%s' was deleted by user %s",
                        $id,
                        $company_name,
                        $summit_name,
                        $this->getUserInfo()
                    );
            }
        } catch (\Exception $ex) {
            Log::warning("SponsorAuditLogFormatter error: " . $ex->getMessage());
        }

        return null;
    }
}
