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
use models\summit\SponsorBadgeScan;
use Illuminate\Support\Facades\Log;

class SponsorBadgeScanAuditLogFormatter extends AbstractAuditLogFormatter
{
    public function format($subject, array $change_set): ?string
    {
        if (!$subject instanceof SponsorBadgeScan) {
            return null;
        }

        try {
            $id = $subject->getId() ?? 'unknown';
            $scan_date = $subject->getScanDate();
            $scan_date_str = $scan_date ? $scan_date->format('Y-m-d H:i:s') : 'Unknown Date';
            $user = $subject->getUser();
            $user_email = $user ? ($user->getEmail() ?? 'Unknown User') : 'Unknown User';
            $sponsor = $subject->getSponsor();
            $sponsor_id = $sponsor ? ($sponsor->getId() ?? 'unknown') : 'unknown';

            switch ($this->event_type) {
                case IAuditStrategy::EVENT_ENTITY_CREATION:
                    return sprintf(
                        "Sponsor Badge Scan (ID: %s) for user '%s' scanned on '%s' by Sponsor %s created by user %s",
                        $id,
                        $user_email,
                        $scan_date_str,
                        $sponsor_id,
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_ENTITY_UPDATE:
                    $details = $this->buildChangeDetails($change_set);
                    return sprintf(
                        "Sponsor Badge Scan (ID: %s) for Sponsor %s updated: %s by user %s",
                        $id,
                        $sponsor_id,
                        $details,
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_ENTITY_DELETION:
                    return sprintf(
                        "Sponsor Badge Scan (ID: %s) for user '%s' by Sponsor %s deleted by user %s",
                        $id,
                        $user_email,
                        $sponsor_id,
                        $this->getUserInfo()
                    );
            }
        } catch (\Exception $ex) {
            Log::warning("SponsorBadgeScanAuditLogFormatter error: " . $ex->getMessage());
        }

        return null;
    }
}
