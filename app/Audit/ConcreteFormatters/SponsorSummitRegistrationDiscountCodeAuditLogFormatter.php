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
use models\summit\SponsorSummitRegistrationDiscountCode;
use Illuminate\Support\Facades\Log;

class SponsorSummitRegistrationDiscountCodeAuditLogFormatter extends AbstractAuditLogFormatter
{
    private function buildDiscountDetails($subject): string
    {
        $details = [];
        
        $rate = $subject->getRate();
        $amount = $subject->getAmount();
        
        if ($rate > 0) {
            $details[] = sprintf("rate: %.2f%%", $rate);
        }
        
        if ($amount > 0) {
            $details[] = sprintf("amount: $%.2f", $amount);
        }
        
        return implode(", ", $details);
    }

    public function format($subject, array $change_set): ?string
    {
        if (!$subject instanceof SponsorSummitRegistrationDiscountCode) {
            return null;
        }

        try {
            $id = $subject->getId() ?? 'unknown';
            $code = $subject->getCode() ?? 'Unknown Code';
            $summit = $subject->getSummit();
            $summit_name = $summit ? ($summit->getName() ?? 'Unknown Summit') : 'Unknown Summit';
            $sponsor_id = $subject->getSponsorId() ?? 'unknown';
            $discount_details = $this->buildDiscountDetails($subject);

            switch ($this->event_type) {
                case IAuditStrategy::EVENT_ENTITY_CREATION:
                    return sprintf(
                        "Sponsor Registration Discount Code '%s' (ID: %s) for Sponsor %s in Summit '%s' with %s created by user %s",
                        $code,
                        $id,
                        $sponsor_id,
                        $summit_name,
                        $discount_details,
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_ENTITY_UPDATE:
                    $details = $this->buildChangeDetails($change_set);
                    return sprintf(
                        "Sponsor Registration Discount Code '%s' (ID: %s) in Summit '%s' updated: %s (current: %s) by user %s",
                        $code,
                        $id,
                        $summit_name,
                        $details,
                        $discount_details,
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_ENTITY_DELETION:
                    return sprintf(
                        "Sponsor Registration Discount Code '%s' (ID: %s) in Summit '%s' with %s was deleted by user %s",
                        $code,
                        $id,
                        $summit_name,
                        $discount_details,
                        $this->getUserInfo()
                    );
            }
        } catch (\Exception $ex) {
            Log::warning("SponsorSummitRegistrationDiscountCodeAuditLogFormatter error: " . $ex->getMessage());
        }

        return null;
    }
}
