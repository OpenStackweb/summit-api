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
use models\summit\SponsorSummitRegistrationPromoCode;
use Illuminate\Support\Facades\Log;

class SponsorSummitRegistrationPromoCodeAuditLogFormatter extends AbstractAuditLogFormatter
{
    public function format($subject, array $change_set): ?string
    {
        if (!$subject instanceof SponsorSummitRegistrationPromoCode) {
            return null;
        }

        try {
            $id = $subject->getId() ?? 'unknown';
            $code = $subject->getCode() ?? 'Unknown Code';
            $summit = $subject->getSummit();
            $summit_name = $summit ? ($summit->getName() ?? 'Unknown Summit') : 'Unknown Summit';
            $sponsor_id = $subject->getSponsorId() ?? 'unknown';

            switch ($this->event_type) {
                case IAuditStrategy::EVENT_ENTITY_CREATION:
                    return sprintf(
                        "Sponsor Registration Promo Code '%s' (ID: %s) for Sponsor %s in Summit '%s' created by user %s",
                        $code,
                        $id,
                        $sponsor_id,
                        $summit_name,
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_ENTITY_UPDATE:
                    $details = $this->buildChangeDetails($change_set);
                    return sprintf(
                        "Sponsor Registration Promo Code '%s' (ID: %s) in Summit '%s' updated: %s by user %s",
                        $code,
                        $id,
                        $summit_name,
                        $details,
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_ENTITY_DELETION:
                    return sprintf(
                        "Sponsor Registration Promo Code '%s' (ID: %s) in Summit '%s' deleted by user %s",
                        $code,
                        $id,
                        $summit_name,
                        $this->getUserInfo()
                    );
            }
        } catch (\Exception $ex) {
            Log::warning("SponsorSummitRegistrationPromoCodeAuditLogFormatter error: " . $ex->getMessage());
        }

        return null;
    }
}
