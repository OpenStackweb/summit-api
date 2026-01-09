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
use models\main\Company;
use Illuminate\Support\Facades\Log;

class CompanyAuditLogFormatter extends AbstractAuditLogFormatter
{
    public function format($subject, array $change_set): ?string
    {
        if (!$subject instanceof Company) {
            return null;
        }

        try {
            $name = $subject->getName() ?? 'Unknown Company';
            $id = $subject->getId() ?? 'unknown';
            $city = $subject->getCity() ?? 'N/A';
            $country = $subject->getCountry() ?? 'N/A';
            $display_on_site = $subject->isDisplayOnSite() ? 'yes' : 'no';
            
            switch ($this->event_type) {
                case IAuditStrategy::EVENT_ENTITY_CREATION:
                    return sprintf(
                        "Company '%s' (%d) created located in %s, %s, display on site: %s by user %s",
                        $name,
                        $id,
                        $city,
                        $country,
                        $display_on_site,
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_ENTITY_UPDATE:
                    $change_details = $this->buildChangeDetails($change_set);
                    return sprintf(
                        "Company '%s' (%d) updated: %s by user %s",
                        $name,
                        $id,
                        $change_details,
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_ENTITY_DELETION:
                    return sprintf(
                        "Company '%s' (%d) located in %s, %s was deleted by user %s",
                        $name,
                        $id,
                        $city,
                        $country,
                        $this->getUserInfo()
                    );
            }
        } catch (\Exception $ex) {
            Log::warning("CompanyAuditLogFormatter error: " . $ex->getMessage());
        }

        return null;
    }
}
