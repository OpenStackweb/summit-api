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
use models\summit\SponsorAd;
use Illuminate\Support\Facades\Log;

class SponsorAdAuditLogFormatter extends AbstractAuditLogFormatter
{
    public function format($subject, array $change_set): ?string
    {
        if (!$subject instanceof SponsorAd) {
            return null;
        }

        try {
            $text = $subject->getText() ?? 'No Text';
            $sponsor = $subject->getSponsor();
            $sponsor_id = $sponsor ? ($sponsor->getId() ?? 'unknown') : 'unknown';
            $sponsor_company = $sponsor && $sponsor->getCompany() ? ($sponsor->getCompany()->getName() ?? 'Unknown Company') : 'Unknown Company';

            switch ($this->event_type) {
                case IAuditStrategy::EVENT_ENTITY_CREATION:
                    return sprintf(
                        "Sponsor Ad '%s' for Sponsor %s ('%s') created by user %s",
                        $text,
                        $sponsor_id,
                        $sponsor_company,
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_ENTITY_UPDATE:
                    $details = $this->buildChangeDetails($change_set);
                    return sprintf(
                        "Sponsor Ad '%s' for Sponsor %s updated: %s by user %s",
                        $text,
                        $sponsor_id,
                        $details,
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_ENTITY_DELETION:
                    return sprintf(
                        "Sponsor Ad '%s' for Sponsor %s deleted by user %s",
                        $text,
                        $sponsor_id,
                        $this->getUserInfo()
                    );
            }
        } catch (\Exception $ex) {
            Log::warning("SponsorAdAuditLogFormatter error: " . $ex->getMessage());
        }

        return null;
    }
}
