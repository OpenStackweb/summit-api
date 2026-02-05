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
use models\summit\SummitOrder;
use Illuminate\Support\Facades\Log;

class SummitOrderAuditLogFormatter extends AbstractAuditLogFormatter
{
    public function format($subject, array $change_set): ?string
    {
        if (!$subject instanceof SummitOrder) {
            return null;
        }

        try {
            $id = $subject->getId() ?? 'unknown';
            $order_number = $subject->getNumber() ?? 'Unknown Order';
            $status = $subject->getStatus() ?? 'Unknown';
            $summit = $subject->getSummit();
            $summit_name = $summit ? ($summit->getName() ?? 'Unknown Summit') : 'Unknown Summit';
            $owner = $subject->getOwner();
            $owner_email = $owner ? ($owner->getEmail() ?? 'Unknown Owner') : 'No Owner';

            switch ($this->event_type) {
                case IAuditStrategy::EVENT_ENTITY_CREATION:
                    return sprintf("Order '%s' (%s) created for '%s' in Summit '%s' with status '%s' by user %s", $order_number, $id, $owner_email, $summit_name, $status, $this->getUserInfo());
                case IAuditStrategy::EVENT_ENTITY_UPDATE:
                    $details = $this->buildChangeDetails($change_set);
                    return sprintf("Order '%s' (%s) in Summit '%s' updated: %s by user %s", $order_number, $id, $summit_name, $details, $this->getUserInfo());
                case IAuditStrategy::EVENT_ENTITY_DELETION:
                    return sprintf("Order '%s' (%s) in Summit '%s' deleted by user %s", $order_number, $id, $summit_name, $this->getUserInfo());
            }
        } catch (\Exception $ex) {
            Log::warning("SummitOrderAuditLogFormatter error: " . $ex->getMessage());
        }

        return null;
    }
}
