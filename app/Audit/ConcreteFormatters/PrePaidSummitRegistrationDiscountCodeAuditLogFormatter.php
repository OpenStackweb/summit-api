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
use models\summit\PrePaidSummitRegistrationDiscountCode;
use Illuminate\Support\Facades\Log;

class PrePaidSummitRegistrationDiscountCodeAuditLogFormatter extends AbstractAuditLogFormatter
{

    private function buildDiscountDetails($subject): string
    {
        $details = [];

        $rate = $subject->getRate();
        $amount = $subject->getAmount();
        $quantity_available = $subject->getQuantityAvailable();

        if ($rate > 0) {
            $details[] = sprintf("rate: %.2f%%", $rate);
        }

        if ($amount > 0) {
            $details[] = sprintf("amount: $%.2f", $amount);
        }

        if ($quantity_available > 0) {
            $details[] = sprintf("quantity: %d", $quantity_available);
        }

        return implode(", ", $details);
    }

    public function format($subject, array $change_set): ?string
    {
        if (!$subject instanceof PrePaidSummitRegistrationDiscountCode) {
            return null;
        }

        try {
            $code = $subject->getCode() ?? 'Unknown';
            $id = $subject->getId() ?? 'unknown';
            $summit = $subject->getSummit();
            $summit_name = $summit ? ($summit->getName() ?? 'Unknown Summit') : 'Unknown Summit';
            $discount_details = $this->buildDiscountDetails($subject);

            switch ($this->event_type) {
                case IAuditStrategy::EVENT_ENTITY_CREATION:
                    return sprintf(
                        "Pre-Paid Discount Code '%s' (%d) created for Summit '%s' with %s by user %s",
                        $code,
                        $id,
                        $summit_name,
                        $discount_details,
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_ENTITY_UPDATE:
                    $change_details = $this->buildChangeDetails($change_set);
                    return sprintf(
                        "Pre-Paid Discount Code '%s' (%d) for Summit '%s' updated: %s (current: %s) by user %s",
                        $code,
                        $id,
                        $summit_name,
                        $change_details,
                        $discount_details,
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_ENTITY_DELETION:
                    return sprintf(
                        "Pre-Paid Discount Code '%s' (%d) for Summit '%s' with %s was deleted by user %s",
                        $code,
                        $id,
                        $summit_name,
                        $discount_details,
                        $this->getUserInfo()
                    );
            }
        } catch (\Exception $ex) {
            Log::warning("PrePaidSummitRegistrationDiscountCodeAuditLogFormatter error: " . $ex->getMessage());
        }

        return null;
    }
}
